<?php

namespace Crsw\CleverReachOfficial\Subscriber;

use Shopware\Core\SalesChannelRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class StorefrontSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber
 */
class StorefrontSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['maintenanceResolver', 100],
            ],
        ];
    }

    /**
     * StorefrontSubscriber constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param RequestEvent $event
     */
    public function maintenanceResolver(RequestEvent $event): void
    {
        $master = $this->requestStack->getMasterRequest();

        if (!$master || !$master->attributes->get(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)) {
            return;
        }

        $salesChannelMaintenance = $master->attributes
            ->get(SalesChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE);
        if (!$salesChannelMaintenance) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('resolved-uri');

        if (strpos($route, 'cleverreach') !== false) {
            $master->attributes
                ->set(SalesChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE, false);
        }
    }
}
