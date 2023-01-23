<?php declare(strict_types=1);

namespace TerraAustralia\Core\Content\Cms;

use Psr\Container\ContainerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use TerraAustralia\Struct\TrProductsStruct;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use TerraAustralia\Decorators\SlotConfigFieldSerializerDecorator;

class TrProductsCmsElementResolver extends AbstractCmsElementResolver
{
    private const TR_E_PRODUCTS_ENTITY_FALLBACK = 'tr-e-products-entity-fallback';

    private const STATIC_SEARCH_KEY = 'tr-e-product';

    private const FALLBACK_LIMIT = 12;

    /**
     * @var container
     */
    private $container;

    /**
     * @var ProductStreamBuilder
     */
    private $productStreamBuilder;

    public function __construct(
        ContainerInterface $container,
        ProductStreamBuilder $productStreamBuilder
    )
    {
        $this->container = $container;
        $this->productStreamBuilder = $productStreamBuilder;
    }

    public function getType(): string
    {
        return 'tr-e-products';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $collection = new CriteriaCollection();

        if (!$products = $config->get('products')) {
            return null;
        }

        if ($products->isStatic() && $products->getValue()) {
            
            $criteria = new Criteria($products->getValue());
            $criteria->addAssociation('cover');
            $criteria->addAssociation('options.group');
            $criteria->addAssociation('categories');
            $criteria = $this->handleSorting($criteria, $config, $resolverContext);
            
            $collection->add(self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);
        }

        if ($products->isMapped() && $products->getValue() && $resolverContext instanceof EntityResolverContext) {
            if ($criteria = $this->collectByEntity($resolverContext, $products, $config)) {
                
                $collection->add(self::TR_E_PRODUCTS_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);
            }
        }

        if ($products->getSource() === SlotConfigFieldSerializerDecorator::SOURCE_PRODUCT_STREAM && $products->getValue()) {
            $criteria = $this->collectByProductStream($resolverContext, $products, $config);
            
            $collection->add(self::TR_E_PRODUCTS_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);
        }

        return $collection->all() ? $collection : null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $trProducts = new TrProductsStruct();
        $slot->setData($trProducts);

        if (!$productConfig = $config->get('products')) {
            return;
        }

        if ($productConfig->isStatic()) {
            $this->enrichFromSearch($trProducts, $result, self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier(), $resolverContext);
        }

        if ($productConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $products = $this->resolveEntityValue($resolverContext->getEntity(), $productConfig->getValue());
            if (!$products) {
                $this->enrichFromSearch($trProducts, $result, self::TR_E_PRODUCTS_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), $resolverContext);
            } else {
                $this->setTrProducts($trProducts, $products, $resolverContext->getSalesChannelContext());
            }
        }

        if ($productConfig->getSource() === SlotConfigFieldSerializerDecorator::SOURCE_PRODUCT_STREAM && $productConfig->getValue()) {
            /** @var ProductCollection $streamResult */
            $streamResult = $result->get(self::TR_E_PRODUCTS_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier())->getEntities();

            $this->setTrProducts($trProducts, $streamResult, $resolverContext->getSalesChannelContext());
        }
    }

    private function enrichFromSearch(TrProductsStruct $trProducts, ElementDataCollection $result, string $searchKey, ResolverContext $resolverContext): void
    {
        $searchResult = $result->get($searchKey);
        if (!$searchResult) {
            return;
        }

        /** @var ProductCollection|null $products */
        $products = $searchResult->getEntities();
        if (!$products) {
            return;
        }

        $this->setTrProducts($trProducts, $products, $resolverContext->getSalesChannelContext());
    }

    private function collectByEntity(EntityResolverContext $resolverContext, FieldConfig $config, FieldConfigCollection $elementConfig): ?Criteria
    {
        $entityProducts = $this->resolveEntityValue($resolverContext->getEntity(), $config->getValue());
        if ($entityProducts) {
            return null;
        }

        $criteria = $this->resolveCriteriaForLazyLoadedRelations($resolverContext, $config);
        $criteria->addAssociation('cover');
        $criteria->addAssociation('options.group');
        
        return $this->handleSorting($criteria, $elementConfig, $resolverContext);
    }

    private function collectByProductStream(ResolverContext $resolverContext, FieldConfig $config, FieldConfigCollection $elementConfig): Criteria
    {
        $filters = $this->productStreamBuilder->buildFilters(
            $config->getValue(),
            $resolverContext->getSalesChannelContext()->getContext()
        );

        $limit = self::FALLBACK_LIMIT;
        if ($productStreamLimit = $elementConfig->get('productStreamLimit')) {
            $limit = $productStreamLimit->getValue();
        }

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

        $criteria = $this->handleSorting($criteria, $elementConfig, $resolverContext);
        
        return $criteria;
    }

    private function handleSorting(Criteria $criteria, FieldConfigCollection $config, ResolverContext $resolverContext): Criteria
    {
        if( $sorting = $config->get('defaultSorting') ) {
            
            if( $key = $sorting->getValue() ) {
                $criteriaSorting = new Criteria();
                $criteriaSorting->addFilter(new EqualsFilter('key', $key));
                
                $sortingEntity = $this->container
                                      ->get('product_sorting.repository')
                                      ->search($criteriaSorting, $resolverContext->getSalesChannelContext()->getContext())->getEntities()->first() ?? null;
                
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
    
    private function setTrProducts(TrProductsStruct $trProducts, ProductCollection $entities, SalesChannelContext $salesChannelContext)
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
        
        $trProducts->setProducts($entities);
    }
    
}
