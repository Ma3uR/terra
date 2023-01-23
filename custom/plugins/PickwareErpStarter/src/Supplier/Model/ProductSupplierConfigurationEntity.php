<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Supplier\Model;

use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductSupplierConfigurationEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var ProductEntity|null
     */
    protected $product;

    /**
     * @var string|null
     */
    protected $supplierId;

    /**
     * @var SupplierEntity|null
     */
    protected $supplier;

    /**
     * @var string|null
     */
    protected $supplierProductNumber;

    /**
     * @var int
     */
    protected $minPurchase;

    /**
     * @var int
     */
    protected $purchaseSteps;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        if ($this->product && $this->product->getId() !== $productId) {
            $this->product = null;
        }
        $this->productId = $productId;
    }

    public function getProduct(): ProductEntity
    {
        if (!$this->product) {
            throw new AssociationNotLoadedException('product', $this);
        }

        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        if ($product) {
            $this->productId = $product->getId();
        }
        $this->product = $product;
    }

    public function getSupplierId(): ?string
    {
        return $this->supplierId;
    }

    public function setSupplierId(string $supplierId): void
    {
        if ($this->supplier && $this->supplier->getId() !== $supplierId) {
            $this->supplier = null;
        }
        $this->supplierId = $supplierId;
    }

    public function getSupplier(): ?SupplierEntity
    {
        if ($this->supplierId && !$this->supplier) {
            throw new AssociationNotLoadedException('supplier', $this);
        }

        return $this->supplier;
    }

    public function setSupplier(?SupplierEntity $supplier): void
    {
        if ($supplier) {
            $this->supplierId = $supplier->getId();
        }
        $this->supplier = $supplier;
    }

    public function getSupplierProductNumber(): ?string
    {
        return $this->supplierProductNumber;
    }

    public function setSupplierProductNumber(?string $supplierProductNumber): void
    {
        $this->supplierProductNumber = $supplierProductNumber;
    }

    public function getMinPurchase(): int
    {
        return $this->minPurchase;
    }

    public function setMinPurchase(int $minPurchase): void
    {
        $this->minPurchase = $minPurchase;
    }

    public function getPurchaseSteps(): int
    {
        return $this->purchaseSteps;
    }

    public function setPurchaseSteps(int $purchaseSteps): void
    {
        $this->purchaseSteps = $purchaseSteps;
    }
}
