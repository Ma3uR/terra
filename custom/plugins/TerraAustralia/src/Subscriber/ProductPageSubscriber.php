<?php declare(strict_types=1);

namespace TerraAustralia\Subscriber;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductLoaderCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductPageSubscriber implements EventSubscriberInterface
{

    /* @var ContainerInterface */
    private $container;
    
    public function __construct(
        ContainerInterface $container,
        SystemConfigService $systemConfigService
    )
    {
        $this->container = $container;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductLoaderCriteriaEvent::class => 'onProductLoaderCriteria',
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }
    
    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();
        
        $customFields = $product->getCustomFields() ?? [];
        
        if( isset($customFields['terra_product_size_table_select']) && !empty($customFields['terra_product_size_table_select'])) {
            $id = (string)$customFields['terra_product_size_table_select'];
            
            if( !empty($id) && 'none' != $id && Uuid::isValid($id)) {
                try{
                    $cmsController = $this->container->get('Shopware\Storefront\Controller\CmsController');
                    
                    $response = $cmsController->page($id, $event->getRequest(), $event->getSalesChannelContext());
                    
                    $cmsPage = $response->getData()['cmsPage'] ?? null;
                    
                    if($cmsPage) {
                        $text = $cmsPage->getFirstElementOfType('text');
                        
                        if($text) {
                            $product->assign([
                                'sizesTable' => $text->getData()->getContent()
                            ]);
                        }
                    }
                } catch (Exception $e){
                    // nothing
                }
            }
        }
    }
    
    public function onProductLoaderCriteria(ProductLoaderCriteriaEvent $event): void
    {
        $event->getCriteria()->addAssociation('categories');
    }
}
