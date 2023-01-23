<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Storefront\Page;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Tanmar\ProductReviews\Storefront\BaseSubscriber;
use Tanmar\ProductReviews\Service\ConfigService;

class StorefrontRenderSubscriber extends BaseSubscriber implements EventSubscriberInterface {

    public function __construct(ConfigService $configService) {
        parent::__construct($configService);
    }

    public static function getSubscribedEvents(): array {
        return [
            StorefrontRenderEvent::class => 'onStorefrontRender'
        ];
    }

    public function onStorefrontRender(StorefrontRenderEvent $event): void {
        $active = $this->getConfig()->isActive();
    }

}
