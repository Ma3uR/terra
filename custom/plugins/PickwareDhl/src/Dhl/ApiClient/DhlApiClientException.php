<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\ApiClient;

use Pickware\PickwareDhl\Dhl\DhlAdapterException;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Parcel\ParcelItem;
use Pickware\MoneyBundle\Currency;

class DhlApiClientException extends DhlAdapterException
{
    public static function parcelHasItemsWithUndefinedWeight(): self
    {
        return new self(
            'The parcel has at least one item with an undefined weight. Therefore the total weight ' .
            'of the shipment cannot be determined.'
        );
    }

    public static function noParticipationConfiguredForProduct(DhlProduct $product): self
    {
        return new self(sprintf(
            'No participation configured for product %s.',
            $product->getName()
        ));
    }

    public static function noReturnParticipationConfiguredForProduct(DhlProduct $product): self
    {
        return new self(sprintf(
            'No return participation configured for product %s.',
            $product->getName()
        ));
    }

    /**
     * @param string $addressOwner The owner of the address (i.e. 'receiver' or 'sender')
     * @param string $propertyName
     * @return self
     */
    public static function missingAddressProperty(string $addressOwner, string $propertyName): self
    {
        return new self(sprintf(
            'The %s address is missing the following property: %s.',
            $addressOwner,
            ucfirst($propertyName)
        ));
    }

    public static function missingCustomsInformationForParcel(Parcel $parcel): self
    {
        return new self(sprintf(
            'No customs information configured for %s.',
            $parcel->getDescription()
        ));
    }

    public static function missingCustomsInformationForParcelItem(ParcelItem $item): self
    {
        return new self(sprintf(
            'No customs information configured for item %s.',
            $item->getName()
        ));
    }

    public static function missingCustomsValueForItem(ParcelItem $item): self
    {
        return new self(sprintf(
            'No customs value configured for item %s.',
            $item->getName()
        ));
    }

    public static function missingWeightForItem(ParcelItem $item): self
    {
        return new self(sprintf(
            'No weight configured for item %s.',
            $item->getName()
        ));
    }

    public static function feeGivenInUnsupportedCurrency(string $feeType, Currency $currency): self
    {
        return new self(sprintf(
            'The parcel has a fee for "%s" that is given in an unsupported currency "%s". ' .
            'The DHL BCP currently supports EUR only.',
            $feeType,
            $currency->getIsoCode()
        ));
    }

    public static function customsValueGivenInUnsupportedCurrency(ParcelItem $item, Currency $currency)
    {
        return new self(sprintf(
            'The the customs value for parcel item "%s" is given in an unsupported currency "%s". ' .
            'The DHL BCP currently supports EUR only.',
            $item->getName(),
            $currency->getIsoCode()
        ));
    }

    public static function typeOfShipmentMissing(): self
    {
        return new self(
            'The type of the shipment is missing in the customs information for the shipment.'
        );
    }
}
