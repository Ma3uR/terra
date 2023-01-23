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

use DateTime;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlProduct;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\AdditionalInsuranceServiceOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\BulkyGoodsServiceOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\CashOnDeliveryServiceOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\EndorsementServiceOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\IdentCheckServiceOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\NamedPersonOnlyServiceOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\PreferredDayServiceOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\PrintOnlyIfCodeableOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\EnclosedReturnLabelOption;
use Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options\VisualCheckOfAgeServiceOption;

class DhlShipmentConfig
{
    /**
     * @var array
     */
    private $shipmentConfig;

    public function __construct(array $shipmentConfig)
    {
        $this->shipmentConfig = $shipmentConfig;
    }

    public function getShipmentOrderOptions(DhlConfig $dhlConfig): array
    {
        $shipmentOrderOptions = [];
        if (isset($this->shipmentConfig['bulkyGoods']) && $this->shipmentConfig['bulkyGoods']) {
            $shipmentOrderOptions[] = new BulkyGoodsServiceOption();
        }
        if (isset($this->shipmentConfig['enclosedReturnLabel']) && $this->shipmentConfig['enclosedReturnLabel']) {
            $shipmentOrderOptions[] = new EnclosedReturnLabelOption($dhlConfig->getBillingInformation());
        }
        if (isset($this->shipmentConfig['printOnlyIfCodeable']) && $this->shipmentConfig['printOnlyIfCodeable']) {
            $shipmentOrderOptions[] = new PrintOnlyIfCodeableOption();
        }
        if (isset($this->shipmentConfig['namedPersonOnly']) && $this->shipmentConfig['namedPersonOnly']) {
            $shipmentOrderOptions[] = new NamedPersonOnlyServiceOption();
        }
        if (isset($this->shipmentConfig['visualCheckOfAge'])
            && in_array(
                intval($this->shipmentConfig['visualCheckOfAge']),
                VisualCheckOfAgeServiceOption::SUPPORTED_AGES,
                true
            )
        ) {
            $shipmentOrderOptions[] = new VisualCheckOfAgeServiceOption(
                intval($this->shipmentConfig['visualCheckOfAge'])
            );
        }
        if (isset($this->shipmentConfig['additionalInsurance'])
            && is_numeric($this->shipmentConfig['additionalInsurance'])
            && floatval($this->shipmentConfig['additionalInsurance']) > 0
        ) {
            $shipmentOrderOptions[] = new AdditionalInsuranceServiceOption(
                floatval($this->shipmentConfig['additionalInsurance'])
            );
        }
        if (isset($this->shipmentConfig['codEnabled']) && $this->shipmentConfig['codEnabled']) {
            $shipmentOrderOptions[] = new CashOnDeliveryServiceOption(
                $dhlConfig->getBankTransferData(),
                $this->shipmentConfig['codAmount'],
                true
            );
        }
        if (isset($this->shipmentConfig['identCheckEnabled']) && $this->shipmentConfig['identCheckEnabled']) {
            $dateOfBirth = DateTime::createFromFormat('Y-m-d', $this->shipmentConfig['identCheckDateOfBirth'] ?? '');
            if (!$dateOfBirth) {
                throw DhlAdapterException::shipmentConfigIsMissingDateOfBirthOrInWrongFormat();
            }

            $shipmentOrderOptions[] = new IdentCheckServiceOption(
                $this->shipmentConfig['identCheckGivenName'],
                $this->shipmentConfig['identCheckSurname'],
                $dateOfBirth,
                intval($this->shipmentConfig['identCheckMinimumAge'])
            );
        }
        if (isset($this->shipmentConfig['preferredDay']) && $this->shipmentConfig['preferredDay'] !== '') {
            $shipmentOrderOptions[] = new PreferredDayServiceOption(
                \DateTimeImmutable::createFromFormat('Y-m-d', $this->shipmentConfig['preferredDay'])
            );
        }
        if (isset($this->shipmentConfig['endorsement']) && $this->shipmentConfig['endorsement'] !== '') {
            $shipmentOrderOptions[] = new EndorsementServiceOption($this->shipmentConfig['endorsement']);
        }

        return $shipmentOrderOptions;
    }

    public function getProduct(): DhlProduct
    {
        $productCode = $this->shipmentConfig['product'] ?? '';
        if (!DhlProduct::isValidProductCode($productCode)) {
            throw DhlAdapterException::invalidProductCode($productCode);
        }

        return DhlProduct::getByCode($productCode);
    }

    public function getTermsOfTrade(): ?string
    {
        if (isset($this->shipmentConfig['createExportDocuments']) && $this->shipmentConfig['createExportDocuments']) {
            if (!isset($this->shipmentConfig['incoterm']) && !isset($this->shipmentConfig['frankatur'])) {
                throw DhlAdapterException::shipmentConfigIsMissingTermsOfTrade();
            }

            return $this->shipmentConfig['incoterm'] ?? $this->shipmentConfig['frankatur'];
        }

        return null;
    }
}
