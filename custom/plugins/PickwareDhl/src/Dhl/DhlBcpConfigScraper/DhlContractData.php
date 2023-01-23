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

class DhlContractData implements JsonSerializable
{
    /**
     * @var string
     */
    private $customerNumber;

    /**
     * @var DhlContractDataBookedProduct[]
     */
    private $bookedProducts;

    /**
     * @param string $customerNumber
     * @param DhlContractDataBookedProduct[] $bookedProducts
     */
    public function __construct(string $customerNumber, array $bookedProducts)
    {
        $this->customerNumber = $customerNumber;
        $this->bookedProducts = $bookedProducts;
    }

    /**
     * @return string
     */
    public function getCustomerNumber(): string
    {
        return $this->customerNumber;
    }

    /**
     * @return DhlContractDataBookedProduct[]
     */
    public function getBookedProducts(): array
    {
        return $this->bookedProducts;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'customerNumber' => $this->customerNumber,
            'bookedProducts' => $this->bookedProducts,
        ];
    }
}
