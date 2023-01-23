<?php declare(strict_types=1);

namespace TerraAustralia\Subscriber;

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Shopware\Storefront\Pagelet\Header\HeaderPageletLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Event\StorefrontRenderEvent;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use TerraAustralia\Decorators\SlotConfigFieldSerializerDecorator;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Content\Product\ProductCollection;

class NavigationPageSubscriber implements EventSubscriberInterface
{
    
    private const WIDGET_LIMIT = 3;
    
    /* @var ContainerInterface */
    private $container;
    
    /**
     * @var ProductListingLoader
     */
    private $listingLoader;
    
    /**
     * @var ProductStreamBuilder
     */
    private $productStreamBuilder;
    
    public function __construct(
        ContainerInterface $container,
        ProductListingLoader $listingLoader,
        ProductStreamBuilder $productStreamBuilder
    )
    {
        $this->container = $container;
        $this->listingLoader = $listingLoader;
        $this->productStreamBuilder = $productStreamBuilder;
        
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HeaderPageletLoadedEvent::class => 'onHeaderLoaded'
        ];
    }
    
    private function handleSorting(Criteria $criteria, FieldConfigCollection $config, SalesChannelContext $resolverContext): Criteria
    {
        if( $sorting = $config->get('sorting') ) {
            
            if( $key = $sorting->getValue() ) {
                $criteriaSorting = new Criteria();
                $criteriaSorting->addFilter(new EqualsFilter('key', $key));
                
                $sortingEntity = $this->container
                                      ->get('product_sorting.repository')
                                      ->search($criteriaSorting, $resolverContext->getContext())->getEntities()->first() ?? null;
                
                if($sortingEntity) {
                    $sortings = [];
                    
                    foreach ($sortingEntity->getFields() as $field) {
                        if (mb_strtoupper($field['order']) === FieldSorting::ASCENDING) {
                            $criteria->addSorting( new FieldSorting($field['field'], FieldSorting::ASCENDING) );
                        } else {
                            $criteria->addSorting( new FieldSorting($field['field'], FieldSorting::DESCENDING) );
                        }
                    }
                }
            }
        }
        
        return $criteria;
    }
    
    private function getCriteriaByProductStream(FieldConfig $config, FieldConfigCollection $elementConfig, SalesChannelContext $salesChannelContext): Criteria
    {
        $filters = $this->productStreamBuilder->buildFilters(
            $config->getValue(),
            $salesChannelContext->getContext()
        );
        
        $limit = self::WIDGET_LIMIT;
        
        $criteria = new Criteria();
        $criteria->addFilter(...$filters);
        $criteria->setLimit($limit);
        $criteria->addAssociation('options.group');
        $criteria->addAssociation('categories');
        
        $criteria->addGroupField(new FieldGrouping('displayGroup'));
        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('displayGroup', null)]
            )
        );
        
        $criteria = $this->handleSorting($criteria, $elementConfig, $salesChannelContext);
        
        return $criteria;
    }
    
    private function getCategoryWidget(CategoryEntity $category, SalesChannelContext $salesChannelContext)
    {
        $criteria = new Criteria();
        $criteria->addFilter( new EqualsFilter('categoryId', $category->getId()) );
        
        return $this->container->get('tr_category_header_widget.repository')->search($criteria, $salesChannelContext->getContext())->getEntities()->first();
    }
    
    private function setParentProducts(ProductCollection $entities, SalesChannelContext $salesChannelContext)
    {
        $parentIds = $entities->getParentIds();
        
        if( !empty($parentIds) && is_array($parentIds) ) {
            
            $criteria = new Criteria($parentIds);
            $parents = $this->container->get('product.repository')->search($criteria, $salesChannelContext->getContext())->getEntities();
            
            foreach($entities as $entity) {
                if( $entity->getParentId() ) {
                    if( $parent = $parents->get($entity->getParentId()) ) {
                        $entity->setParent($parent);
                    }
                }
            }
        }
        
        return $entities;
    }
    
    private function getProducts(Criteria $criteria, SalesChannelContext $salesChannelContext): array
    {
        $result = $this->listingLoader->load($criteria, $salesChannelContext);
        
        $origin = $this->setParentProducts($result->getEntities(), $salesChannelContext);
        
        return [
            'total' => $result->getTotal(),
            'elements' => $origin->getElements(),
        ];
    }
    
    private function loadProducts(SalesChannelContext $salesChannelContext, CategoryEntity $category): array
    {
        $widget = $this->getCategoryWidget($category, $salesChannelContext);
        
        if($widget) {
            $config = $widget->getFieldConfig();
            
            if (!$products = $config->get('products')) {
                return [
                    'total' => 0,
                    'elements' => null,
                ];
            }
            
            if ($config->get('source')->getValue() === 'static' && $products->getValue()) {
                
                $criteria = new Criteria($products->getValue());
                $criteria->addAssociation('cover');
                $criteria->addAssociation('options.group');
                $criteria->addAssociation('categories');
                $criteria->setLimit(self::WIDGET_LIMIT);
                
                $criteria = $this->handleSorting($criteria, $config, $salesChannelContext);
                
                return $this->getProducts($criteria, $salesChannelContext);
            }
            
            if ($config->get('source')->getValue() === SlotConfigFieldSerializerDecorator::SOURCE_PRODUCT_STREAM && $products->getValue()) {
                
                $criteria = $this->getCriteriaByProductStream($products, $config, $salesChannelContext);
                
                return $this->getProducts($criteria, $salesChannelContext);
            }
        }
        
        $criteria = new Criteria();
        $criteria->addFilter( new ProductAvailableFilter($salesChannelContext->getSalesChannel()->getId(), ProductVisibilityDefinition::VISIBILITY_ALL) );
        $criteria->addFilter( new EqualsFilter('product.categoriesRo.id', $category->getId()) );
        $criteria->setLimit(self::WIDGET_LIMIT);
        
        return $this->getProducts($criteria, $salesChannelContext);
    }
    
    public function onHeaderLoaded($event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        
        $navigation = $event->getPagelet()->getNavigation();
        
        if($navigation) {
            
            $tree = $navigation->getTree();
            
            foreach($tree as $treeItem) {
                $treeItem->assign([
                    'products' => $this->loadProducts($salesChannelContext, $treeItem->getCategory())
                ]);
                
                $childen = $treeItem->getChildren();
                
                if( count($childen) > 0 ) {
                    foreach($childen as $child) {
                        
                        $child->assign([
                            'products' => $this->loadProducts($salesChannelContext, $child->getCategory())
                        ]);
                    }
                }
            }
        }
    }
}
