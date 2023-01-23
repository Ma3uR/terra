<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Carrier\Model\Subscriber;

use Pickware\ShippingBundle\Carrier\CarrierAdapterRegistry;
use Pickware\ShippingBundle\Carrier\Model\CarrierEntity;
use Pickware\ShippingBundle\Carrier\Model\CarrierEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CarrierEntitySubscriber implements EventSubscriberInterface
{
    /**
     * @var CarrierAdapterRegistry
     */
    private $carrierAdapterRegistry;

    /**
     * @param CarrierAdapterRegistry $carrierAdapterRegistry
     */
    public function __construct(CarrierAdapterRegistry $carrierAdapterRegistry)
    {
        $this->carrierAdapterRegistry = $carrierAdapterRegistry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CarrierEvents::ENTITY_LOADED => 'onEntityLoaded',
        ];
    }

    public function onEntityLoaded(EntityLoadedEvent $event): void
    {
        /** @var CarrierEntity $carrier */
        foreach ($event->getEntities() as $carrier) {
            $technicalName = $carrier->getTechnicalName();
            if ($this->carrierAdapterRegistry->hasCarrierAdapter($technicalName)) {
                $adapter = $this->carrierAdapterRegistry->getCarrierAdapterByTechnicalName($technicalName);
                $carrier->assign([
                    'installed' => true,
                    'capabilities' => $adapter->getCapabilities(),
                ]);
            } else {
                $carrier->assign([
                    'installed' => false,
                    'capabilities' => null,
                ]);
            }
        }
    }
}
