<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\OrderShipping;

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\Picking\PickingRequestService;
use Pickware\PickwareErpStarter\Stock\Model\StockDefinition;
use Pickware\PickwareErpStarter\Stock\OrderStockInitializer;
use Pickware\PickwareErpStarter\StockApi\StockLocationReference;
use Pickware\PickwareErpStarter\StockApi\StockMovementService;
use Pickware\PickwareErpStarter\Stocking\StockingRequestService;
use Pickware\PickwareErpStarter\Stocking\StockingStrategy;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseDefinition;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;

class OrderShippingService
{
    /**
     * @var EntityManager;
     */
    private $entityManager;

    /**
     * @var PickingRequestService
     */
    private $pickingRequestService;

    /**
     * @var StockMovementService
     */
    private $stockMovementService;

    /**
     * @var StockingRequestService
     */
    private $stockingRequestService;

    /**
     * @var StockingStrategy
     */
    private $stockingStrategy;

    /**
     * @var OrderStockInitializer
     */
    private $orderStockInitializer;

    public function __construct(
        EntityManager $entityManager,
        PickingRequestService $pickingRequestService,
        StockMovementService $stockMovementService,
        StockingRequestService $stockingRequestService,
        StockingStrategy $stockingStrategy,
        OrderStockInitializer $orderStockInitializer
    ) {
        $this->entityManager = $entityManager;
        $this->pickingRequestService = $pickingRequestService;
        $this->stockMovementService = $stockMovementService;
        $this->stockingRequestService = $stockingRequestService;
        $this->stockingStrategy = $stockingStrategy;
        $this->orderStockInitializer = $orderStockInitializer;
    }

    public function shipOrderCompletely(string $orderId, string $warehouseId, Context $context): void
    {
        $this->checkLiveVersion($context);
        $warehouse = $this->getWarehouse($warehouseId, $context, []);
        $order = $this->getOrder($orderId, $context, []);

        $this->entityManager->transactional(
            $context,
            function (EntityManager $entityManager, Context $context) use ($warehouse, $order) {
                $this->lockProductStocks($order->getId(), $context);

                $pickingRequest = $this->pickingRequestService->createPickingRequestForOrder(
                    [$warehouse->getId()],
                    $order->getId(),
                    $context
                );
                if (!$pickingRequest->isCompletelyPickable()) {
                    // Not all quantity of the order line items could be distributed among the pick locations
                    throw OrderShippingException::notEnoughStock($warehouse, $order);
                }

                $stockMovements = $pickingRequest->createStockMovementsWithDestination(
                    StockLocationReference::order($order->getId())
                );
                $this->stockMovementService->moveStock($stockMovements, $context);
            }
        );
    }

    public function returnOrderCompletely(string $orderId, string $warehouseId, Context $context): void
    {
        $this->checkLiveVersion($context);
        $this->ensureWarehouseExists($warehouseId, $context);
        $this->ensureOrderExists($orderId, $context);
        $this->orderStockInitializer->initializeOrderIfNecessary($orderId, $context);

        $this->entityManager->transactional($context, function () use ($warehouseId, $context, $orderId) {
            $this->lockProductStocks($orderId, $context);

            $stockingRequest = $this->stockingRequestService->createStockingRequestForOrder(
                $orderId,
                $warehouseId,
                $context
            );
            $stockingSolution = $this->stockingStrategy->calculateStockingSolution($stockingRequest, $context);
            $stockMovements = $stockingSolution->createStockMovementsWithSource(
                StockLocationReference::order($orderId)
            );
            $this->stockMovementService->moveStock($stockMovements, $context);
        });
    }

    private function lockProductStocks(string $orderId, Context $context): void
    {
        $this->entityManager->lockPessimistically(
            StockDefinition::class,
            [
                'product.orderLineItems.order.id' => $orderId,
                'product.orderLineItems.type' => OrderStockInitializer::ORDER_STOCK_RELEVANT_LINE_ITEM_TYPES,
            ],
            $context
        );
    }

    private function checkLiveVersion(Context $context): void
    {
        if ($context->getVersionId() !== Defaults::LIVE_VERSION) {
            throw OrderShippingException::notInLiveVersion();
        }
    }

    private function ensureWarehouseExists(string $warehouseId, Context $context): void
    {
        /** @var WarehouseEntity $warehouse */
        $warehouse = $this->entityManager->findByPrimaryKey(WarehouseDefinition::class, $warehouseId, $context);
        if ($warehouse === null) {
            throw OrderShippingException::warehouseDoesNotExist($warehouseId);
        }
    }

    private function ensureOrderExists(string $orderId, Context $context): void
    {
        $this->getOrder($orderId, $context, []);
    }

    private function getOrder(string $orderId, Context $context, array $associations): OrderEntity
    {
        /** @var OrderEntity $order */
        $order = $this->entityManager->findByPrimaryKey(OrderDefinition::class, $orderId, $context, $associations);
        if ($order === null) {
            throw OrderShippingException::orderDoesNotExist($orderId);
        }

        return $order;
    }

    private function getWarehouse(string $warehouseId, Context $context, array $associations): WarehouseEntity
    {
        /** @var WarehouseEntity $warehouse */
        $warehouse = $this->entityManager->findByPrimaryKey(WarehouseDefinition::class, $warehouseId, $context, $associations);
        if ($warehouse === null) {
            throw OrderShippingException::warehouseDoesNotExist($warehouseId);
        }

        return $warehouse;
    }
}
