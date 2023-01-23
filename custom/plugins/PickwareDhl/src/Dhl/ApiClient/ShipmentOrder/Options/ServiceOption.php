<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options;

class ServiceOption extends AbstractShipmentOrderOption
{
    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var array
     */
    private $serviceValue;

    /**
     * @param string $serviceName
     * @param array $serviceValue
     */
    public function __construct(string $serviceName, array $serviceValue = [])
    {
        if (!isset($serviceValue['active'])) {
            $serviceValue['active'] = 1;
        }

        $this->serviceName = $serviceName;
        $this->serviceValue = $serviceValue;
    }

    public function applyToShipmentOrderArray(array &$shipmentOrderArray): void
    {
        $shipmentDetails = &$shipmentOrderArray['Shipment']['ShipmentDetails'];

        if (!isset($shipmentDetails['Service'])) {
            $shipmentDetails['Service'] = [];
        }
        $shipmentDetails['Service'][$this->serviceName] = $this->serviceValue;
    }
}
