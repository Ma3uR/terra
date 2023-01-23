<?php declare(strict_types=1);

namespace TerraAustralia\Subscriber;

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Cms\DataResolver\CmsSlotsDataResolver;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Cms\Events\CmsPageLoaderCriteriaEvent;
use Shopware\Core\Content\Cms\CmsPageEvents;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ListingPageSubscriber implements EventSubscriberInterface
{
    public const LISTING_LIMIT = 24;
    public const PAGE_LISTING_TYPE = 'product_list';

    /* @var ContainerInterface */
    private $container;
    
    /**
     * @var CmsSlotsDataResolver
     */
    private $slotDataResolver;
    
    private $productListingPerPage = self::LISTING_LIMIT;

    public function __construct(
        ContainerInterface $container,
        SystemConfigService $systemConfigService,
        CmsSlotsDataResolver $slotDataResolver
    )
    {
        $this->container = $container;
        $this->systemConfigService = $systemConfigService;
        $this->slotDataResolver = $slotDataResolver;

        $this->productListingPerPage = (int)$this->systemConfigService->get('TerraAustralia.config.productListingPerPage') ?? 0;

        if($this->productListingPerPage < 1) {
            $this->productListingPerPage = self::LISTING_LIMIT;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'onListingCriteriaEvent',
            ProductSearchCriteriaEvent::class => 'onListingCriteriaEvent',
            
            ProductListingResultEvent::class => 'onListingResultEvent',
            ProductSearchResultEvent::class => 'onListingResultEvent',
            
            CmsPageLoadedEvent::class => 'onCmsPageEvent',

        ];
    }
    
    private function reloadCmsPageOnlyBenefits(string $id, SalesChannelContext $salesChannelContext): CmsPageEntity
    {
        $criteria = new Criteria([$id]);
        $criteria
            ->getAssociation('sections');

        $criteria
            ->getAssociation('sections.blocks')
            ->addFilter(new EqualsFilter('type', 'tr-benefits'))
            ->addAssociation('slots');
            
        return $this->container->get('cms_page.repository')->search($criteria, $salesChannelContext->getContext())->getEntities()->first();
    }
    
    private function loadListingBenefits(CmsPageEntity $page, SalesChannelContext $salesChannelContext): ?CmsBlockEntity
    {
        $cmsPage = $this->reloadCmsPageOnlyBenefits($page->getId(), $salesChannelContext);
        
        return $this->getFirstBlockOfType($cmsPage, 'tr-benefits', $salesChannelContext);
    }
    
    private function getFirstBlockOfType(CmsPageEntity $page, string $type, $salesChannelContext)
    {
        $result = null;

        if (!$page->getSections()) {
            return null;
        }

        foreach ($page->getSections() as $section) {
            if(!$section->getBlocks()) {
                continue;
            }
            
            $section->getBlocks()->sort(function (CmsBlockEntity $a, CmsBlockEntity $b) {
                return $a->getPosition() <=> $b->getPosition();
            });
        }

        foreach ($page->getSections()->getBlocks() as $block) {
            if ($block->getType() === $type) {
                $result = $block;
                
                if( $page->getType() == self::PAGE_LISTING_TYPE ) {
                    $block->assign([
                        'noDisplayOnFront' => true
                    ]);
                }
                
                break;
            }
        }
        
        return $result;
    }

    public function onCmsPageEvent(CmsPageLoadedEvent $event): void
    {
        if( $cmsPage = $event->getResult()->first() ) {
            $block = $this->getFirstBlockOfType($cmsPage, 'tr-benefits', $event->getSalesChannelContext());

            if( !$block ) {
                $block = $this->loadListingBenefits($cmsPage, $event->getSalesChannelContext());
                
                if( $block ) {
                    $resolverContext = new ResolverContext($event->getSalesChannelContext(), $event->getRequest());
                    $slots = $this->slotDataResolver->resolve($block->getSlots(), $resolverContext);
                    $block->setSlots($slots);
                }
            }
            
            $cmsPage->assign([
                'listingBenefits' => $block
            ]);
        }
    }

    public function onListingCriteriaEvent(ProductListingCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();

        $request = $event->getRequest();

        $page = $request->get('p') ?? 1;
        $perPage = $request->get('perpage') ? (int)$request->get('perpage') : $this->productListingPerPage;

        if( $page < 1) {
            $page = 1;
        }

        $offset = ($page - 1 ) * $perPage;
        
        $criteria->setLimit($perPage);
        $criteria->setOffset($offset);
        $criteria->addAssociation('categories');
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
    
    public function onListingResultEvent(ProductListingResultEvent $event): void
    {
        $result = $event->getResult();
        
        $request = $event->getRequest();
        
        $perPage = $request->get('perpage') ? (int)$request->get('perpage') : $this->productListingPerPage;
        
        $result->setLimit($perPage);

        $origin = $this->setParentProducts($result->getEntities(), $event->getSalesChannelContext());
        
        $total = $origin->count();
        
        if( $total > 8) {
            $length = $total;
            $mod = 4;
            $center = (int)ceil( $total / 2);
            
            for($i=$mod; $i<$total; $i+=$mod){
                if( $i >= 8 && $i >= $center ) {
                    $length = $i; break;
                }
            }
            
            $length2 = $total - $length;
            
            $searchResultFirst = $origin->slice(0, $length);
            $searchResultSecond = $origin->slice($length, $length2);
        } else {
            $searchResultFirst = $origin->slice(0, $origin->count());
            $searchResultSecond = null;
        }
        
        $result->assign([
            'searchResultFirst' => $searchResultFirst,
            'searchResultSecond' => $searchResultSecond
        ]);
    }

}
