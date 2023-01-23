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

use Pickware\PickwareDhl\Dhl\ApiClient\DhlBankTransferData;

class CashOnDeliveryServiceOption extends ServiceOption
{
    /**
     * @var DhlBankTransferData
     */
    private $bankTransferData;

    /**
     * @param DhlBankTransferData $bankTransferData
     * @param float $amountInEuro
     * @param bool $addFee Configuration whether the transmission fee to be added to the COD amount or not by DHL.
     *     Select the option then the new COD amount will automatically printed on the shipping label and will
     *     transferred to the end of the day to DHL. Do not select the option and the specified COD amount remains
     *     unchanged.
     */
    public function __construct(DhlBankTransferData $bankTransferData, float $amountInEuro, bool $addFee)
    {
        parent::__construct(
            'CashOnDelivery',
            [
                'codAmount' => round($amountInEuro, 2),
                'addFee' => (int) $addFee,
            ]
        );
        $this->bankTransferData = $bankTransferData;
    }

    public function applyToShipmentOrderArray(array &$array): void
    {
        parent::applyToShipmentOrderArray($array);
        $array['Shipment']['ShipmentDetails']['BankData'] = $this->bankTransferData->getAsArrayForShipmentDetails(
            $array['Shipment']['ShipmentDetails']['customerReference']
        );
    }
}
