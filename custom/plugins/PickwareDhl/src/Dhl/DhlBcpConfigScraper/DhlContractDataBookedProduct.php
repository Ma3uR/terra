<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\DhlBcpConfigScraper;

use JsonSerializable;
use Pickware\PickwareDhl\Dhl\ApiClient\DhlProduct;

class DhlContractDataBookedProduct implements JsonSerializable
{
    /**
     * @var DhlProduct
     */
    private $product;

    /**
     * @var string[]
     */
    private $billingNumbers;

    /**
     * @var string[]
     */
    private $returnBillingNumbers;

    /**
     * @param array $billingNumbers
     * @param DhlProduct $product
     */
    public function __construct(DhlProduct $product, array $billingNumbers, array $returnBillingNumbers)
    {
        $this->product = $product;
        $this->billingNumbers = $billingNumbers;
        $this->returnBillingNumbers = $returnBillingNumbers;
    }

    /**
     * @return DhlProduct
     */
    public function getProduct(): DhlProduct
    {
        return $this->product;
    }

    /**
     * @return array
     */
    public function getBillingNumbers(): array
    {
        return $this->billingNumbers;
    }

    public function getReturnBillingNumbers(): array
    {
        return $this->returnBillingNumbers;
    }

    /**
     * @return string[]
     */
    public function getParticipations(): array
    {
        return array_map(
            function ($billingNumber) {
                return mb_substr($billingNumber, 12, 2, 'UTF-8');
            },
            $this->billingNumbers
        );
    }

    /**
     * @return string[]
     */
    public function getReturnParticipations(): array
    {
        return array_map(
            function (string $returnBillingNumber) {
                return mb_substr($returnBillingNumber, 12, 2, 'UTF-8');
            },
            $this->returnBillingNumbers
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'product' => $this->product,
            'participations' => $this->getParticipations(),
            'returnParticipations' => $this->getReturnParticipations(),
            'billingNumbers' => $this->getBillingNumbers(),
            'returnBillingNumbers' => $this->getReturnBillingNumbers(),
        ];
    }

    /**
     * @param string[][] $productBillingNumbersMapping Mapping string:bcpProductName => string:billingNumber[]
     * @return self[]
     */
    public static function createFromBcpProductNameBillingNumbersMapping(array $productBillingNumbersMapping): array
    {
        $allProducts = DhlProduct::getList();
        $bookedProducts = array_map(function (DhlProduct $product) {
            return new self($product, [], []);
        }, $allProducts);

        foreach ($productBillingNumbersMapping as $bcpProductName => $billingNumbers) {
            foreach ($bookedProducts as $bookedProduct) {
                if ($bookedProduct->getProduct()->getName() === $bcpProductName) {
                    $bookedProduct->billingNumbers = $billingNumbers;
                } elseif ($bookedProduct->getProduct()->getReturnName() === $bcpProductName) {
                    $bookedProduct->returnBillingNumbers = $billingNumbers;
                } elseif ($bookedProduct->getProduct()->getCode() === DhlProduct::CODE_DHL_WARENPOST
                    && str_contains($bcpProductName, 'Warenpost')
                ) {
                    // Special case for Warenpost as its name in the BCP is not the same as the product name.
                    // The product name is "DHL Warenpost".
                    // In the BCP they name the product in different ways, depending on the customers contracts.
                    // Examples:
                    // - Warenpost P. RC 200-999
                    // - Warenpost P. RC 3000-5999
                    // - Warenpost
                    // - or even other names.
                    // See also issue: https://github.com/pickware/shopware-plugins/issues/1161
                    $bookedProduct->billingNumbers = $billingNumbers;
                }
            }
        }

        return array_values(array_filter($bookedProducts, function (self $bookedProduct) {
            return count($bookedProduct->billingNumbers) !== 0 || count($bookedProduct->returnBillingNumbers) !== 0;
        }));
    }
}
