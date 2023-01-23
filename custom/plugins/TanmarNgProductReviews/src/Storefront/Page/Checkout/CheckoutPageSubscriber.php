<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Storefront\Page\Checkout;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Tanmar\ProductReviews\Storefront\BaseSubscriber;
use Tanmar\ProductReviews\Components\Installer\OrderCustomFieldInstaller;
use Tanmar\ProductReviews\Service\ConfigService;
use Tanmar\ProductReviews\Service\OrderService;

class CheckoutPageSubscriber extends BaseSubscriber implements EventSubscriberInterface {

    /**
     * 
     * @var OrderService
     */
    protected $orderService;
    protected $requestStack;
    protected $orderRepository;

    public function __construct(ConfigService $configService, OrderService $orderService, RequestStack $requestStack, EntityRepositoryInterface $orderRepository) {
        parent::__construct($configService);
        $this->orderService = $orderService;
        $this->requestStack = $requestStack;
        $this->orderRepository = $orderRepository;
    }

    public static function getSubscribedEvents(): array {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            CheckoutOrderPlacedEvent::class => 'onCheckoutOrderPlacedEvent'
        ];
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void {
        try {
            $productReviewsData = $this->getExtension($event);
            $this->addExtension($event, $productReviewsData);
        } catch (\Exception $e) {
            
        }
    }

    public function onCheckoutOrderPlacedEvent(CheckoutOrderPlacedEvent $event): void {
        $optin = 'not asked';

        if ($this->requestStack->getCurrentRequest()->request->get('tanmar_product_reviews_consent_contained', 0)) {
            if ($this->requestStack->getCurrentRequest()->request->get('tanmar_product_reviews_optin', 0)) {
                $optin = 'agreed';
            } else {
                $optin = 'denied';
            }
        }
        $this->orderService->initializeOrderFields($event->getOrder(), $event->getContext(), $optin);
    }

}
