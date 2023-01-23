<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl;

use Pickware\ShippingBundle\Carrier\CarrierAdapterException;
use Pickware\MoneyBundle\CurrencyConverterException;

class DhlAdapterException extends CarrierAdapterException
{

    public static function customsValuesCouldNotBeConvertedToEuro(
        CurrencyConverterException $currencyConverterException
    ): self {
        return new self(sprintf(
            'The DHL BCP API does support customs values in EUR only. At least one customs value of your shipment ' .
            'was not provided in EUR and could not be converted to EUR because of the following reason: %s',
            $currencyConverterException->getMessage()
        ), 0, $currencyConverterException);
    }

    public static function invalidProductCode(string $productCode): self
    {
        if ($productCode === '') {
            return new self('No DHL BCP product was specified.');
        }

        return new self(sprintf(
            'The specified value "%s" is not a valid code for a DHL BCP product.',
            $productCode
        ));
    }

    public static function shipmentBlueprintHasNoParcels(): self
    {
        return new self('The shipment has no parcels and therefore a label cannot be created.');
    }

    public static function shipmentConfigIsMissingTermsOfTrade(): self
    {
        return new self(
            'It was requested to create export documents for the shipment but neither an incoterm nor an frankatur ' .
            'were given in the configuration.'
        );
    }

    public static function shipmentConfigIsMissingDateOfBirthOrInWrongFormat(): self
    {
        return new self(
            'The date of birth is missing or in wrong format for service option Ident-Check.'
        );
    }

    public static function shipmentNotFound(string $shipmentId): self
    {
        return new self(
            sprintf('The shipment with ID %s was not found.', $shipmentId)
        );
    }
}
