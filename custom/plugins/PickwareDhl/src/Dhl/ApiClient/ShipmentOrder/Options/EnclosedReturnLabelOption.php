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

use Pickware\PickwareDhl\Dhl\ApiClient\DhlBillingInformation;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlProduct;

/**
 * "Beilegretoure"
 */
class EnclosedReturnLabelOption extends AbstractShipmentOrderOption
{
    /**
     * @var DhlBillingInformation|string
     */
    private $dhlBillingInformation;

    public function __construct(DhlBillingInformation $dhlBillingInformation)
    {
        $this->dhlBillingInformation = $dhlBillingInformation;
    }

    public function applyToShipmentOrderArray(array &$shipmentOrderArray): void
    {
        $shipmentOrderArray['Shipment']['ReturnReceiver'] = $shipmentOrderArray['Shipment']['Shipper'];
        $shipmentDetails = &$shipmentOrderArray['Shipment']['ShipmentDetails'];
        $product = DhlProduct::getByCode($shipmentDetails['product']);
        $shipmentDetails['returnShipmentAccountNumber'] = $this->dhlBillingInformation->getReturnShipmentBillingNumber(
            $product
        );
    }
}
