<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Stocking;

class StockingRequest
{
    /**
     * @var ProductQuantity[]
     */
    private $productQuantities;

    /**
     * @var string|null
     */
    private $warehouseId;

    /**
     * @param ProductQuantity[] $productQuantities
     */
    public function __construct(array $productQuantities, ?string $warehouseId)
    {
        $this->productQuantities = $productQuantities;
        $this->warehouseId = $warehouseId;
    }

    /**
     * @return ProductQuantity[]
     */
    public function getProductQuantities(): array
    {
        return $this->productQuantities;
    }

    public function getWarehouseId(): ?string
    {
        return $this->warehouseId;
    }

    public function getProductIds(): array
    {
        return array_map(function (ProductQuantity $productQuantity) {
            return $productQuantity->getProductId();
        }, $this->productQuantities);
    }
}
