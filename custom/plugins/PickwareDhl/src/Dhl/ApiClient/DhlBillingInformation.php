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

class DhlBillingInformation
{
    /**
     * Customer number is also known as EKP
     *
     * @var string
     */
    private $customerNumber;

    /**
     * Associative array with: string:productCode => string:participation
     *
     * @var string[]
     */
    private $participations = [];

    /**
     * Associative array with: string:productCode => string:participation
     *
     * @var string[]
     */
    private $returnParticipations = [];

    public function __construct(string $customerNumber)
    {
        $this->customerNumber = $customerNumber;
    }

    /**
     * @return string
     */
    public function getCustomerNumber(): string
    {
        return $this->customerNumber;
    }

    /**
     * The return shipment billing number is also known as invoicing number. It is always put together like this:
     *
     * customer number (EKP) + return procedure (depends on product) + participation (depends on contract)
     *
     * @param DhlProduct $product
     * @return string
     */
    public function getReturnShipmentBillingNumber(DhlProduct $product): string
    {
        if (!isset($this->returnParticipations[$product->getCode()])) {
            throw DhlApiClientException::noReturnParticipationConfiguredForProduct($product);
        }

        return sprintf(
            '%s%s%s',
            $this->getCustomerNumber(),
            $product->getReturnProcedure(),
            $this->returnParticipations[$product->getCode()]
        );
    }

    /**
     * The billing number is also known as account number. It is always put together like this:
     *
     * customer number (EKP) + procedure (depends on product) + participation (depends on contract)
     *
     * @param DhlProduct $product
     * @return string
     */
    public function getBillingNumberForProduct(DhlProduct $product): string
    {
        if (!isset($this->participations[$product->getCode()])) {
            throw DhlApiClientException::noParticipationConfiguredForProduct($product);
        }

        return sprintf(
            '%s%s%s',
            $this->getCustomerNumber(),
            $product->getProcedure(),
            $this->participations[$product->getCode()]
        );
    }

    public function setParticipationForProduct(DhlProduct $dhlProduct, string $participation): void
    {
        $this->participations[$dhlProduct->getCode()] = $participation;
    }

    public function setReturnParticipationForProduct(DhlProduct $dhlProduct, string $returnParticipation): void
    {
        $this->returnParticipations[$dhlProduct->getCode()] = $returnParticipation;
    }
}
