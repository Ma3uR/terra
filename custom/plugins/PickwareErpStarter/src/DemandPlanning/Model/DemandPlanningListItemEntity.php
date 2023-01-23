<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemandPlanning\Model;

use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class DemandPlanningListItemEntity extends Entity
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
     * @var string
     */
    protected $demandPlanningSessionId;

    /**
     * @var DemandPlanningSessionEntity|null
     */
    protected $demandPlanningSession;

    /**
     * @var int
     */
    protected $sales;

    /**
     * @var int
     */
    protected $salesPrediction;

    /**
     * @var int
     */
    protected $reservedStock;

    /**
     * @var int
     */
    protected $purchaseSuggestion;

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

    public function setProduct(ProductEntity $product): void
    {
        $this->productId = $product->getId();
        $this->product = $product;
    }

    public function getDemandPlanningSessionId(): string
    {
        return $this->demandPlanningSessionId;
    }

    public function setDemandPlanningSessionId(string $demandPlanningSessionId): void
    {
        if ($this->demandPlanningSession
            && $this->demandPlanningSession->getId() !== $demandPlanningSessionId
        ) {
            $this->demandPlanningSession = null;
        }
        $this->demandPlanningSessionId = $demandPlanningSessionId;
    }

    public function getDemandPlanningSession(): DemandPlanningSessionEntity
    {
        if (!$this->demandPlanningSession) {
            throw new AssociationNotLoadedException('demandPlanningSession', $this);
        }

        return $this->demandPlanningSession;
    }

    public function setDemandPlanningSession(
        DemandPlanningSessionEntity $demandPlanningSession
    ): void {
        $this->demandPlanningSessionId = $demandPlanningSession->getId();
        $this->demandPlanningSession = $demandPlanningSession;
    }

    public function getSales(): int
    {
        return $this->sales;
    }

    public function setSales(int $sales): void
    {
        $this->sales = $sales;
    }

    public function getSalesPrediction(): int
    {
        return $this->salesPrediction;
    }

    public function setSalesPrediction(int $salesPrediction): void
    {
        $this->salesPrediction = $salesPrediction;
    }

    public function getReservedStock(): int
    {
        return $this->reservedStock;
    }

    public function setReservedStock(int $reservedStock): void
    {
        $this->reservedStock = $reservedStock;
    }

    public function getPurchaseSuggestion(): int
    {
        return $this->purchaseSuggestion;
    }

    public function setPurchaseSuggestion(int $purchaseSuggestion): void
    {
        $this->purchaseSuggestion = $purchaseSuggestion;
    }
}
