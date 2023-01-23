<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Config;

use Pickware\ShippingBundle\Parcel\ParcelCustomsInformation;
use Pickware\ShippingBundle\Shipment\Address;

class CommonShippingConfig
{
    use ConfigDecoratorTrait;

    public const CONFIG_DOMAIN = 'PickwareShippingBundle.common';

    public static function createDefault(): self
    {
        return new self(new Config(self::CONFIG_DOMAIN, [
            'customsInformationTypeOfShipment' => ParcelCustomsInformation::SHIPMENT_TYPE_SALE_OF_GOODS,
        ]));
    }

    public function getSenderAddress(): Address
    {
        $senderAddress = [];

        foreach ($this->config as $key => $value) {
            if (mb_strpos($key, 'senderAddress') === 0) {
                $senderAddress[lcfirst(str_replace('senderAddress', '', $key))] = $value;
            }
        }

        return Address::fromArray($senderAddress);
    }

    public function assignCustomsInformation(ParcelCustomsInformation $customsInformation): void
    {
        $customsInformation->setTypeOfShipment(
            $this->config['customsInformationTypeOfShipment'] ?? ParcelCustomsInformation::SHIPMENT_TYPE_OTHER
        );
        $customsInformation->setExplanationIfTypeOfShipmentIsOther(
            $this->config['customsInformationExplanation'] ?? null
        );
        $customsInformation->setComment($this->config['customsInformationComment'] ?? '');
        $customsInformation->setOfficeOfOrigin($this->config['customsInformationOfficeOfOrigin'] ?? '');
        $customsInformation->setPermitNumbers(
            $this->config->getMultilineConfigValueAsArray('customsInformationPermitNumbers')
        );
        $customsInformation->setCertificateNumbers(
            $this->config->getMultilineConfigValueAsArray('customsInformationCertificateNumbers')
        );
    }
}
