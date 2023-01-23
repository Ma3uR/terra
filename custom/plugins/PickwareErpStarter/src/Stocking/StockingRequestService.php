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

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareErpStarter\Stock\OrderStockInitializer;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class StockingRequestService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createStockingRequestForOrder(string $orderId, string $warehouseId, Context $context): StockingRequest
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('orderId', $orderId),
            new EqualsAnyFilter('type', OrderStockInitializer::ORDER_STOCK_RELEVANT_LINE_ITEM_TYPES),
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [ new EqualsFilter('productId', null) ]
            )
        );
        $orderLineItems = $this->entityManager->findBy(OrderLineItemDefinition::class, $criteria, $context);

        return new StockingRequest(
            array_values(
                $orderLineItems->map(function (OrderLineItemEntity $orderLineItem) {
                    return new ProductQuantity($orderLineItem->getProductId(), $orderLineItem->getQuantity());
                })
            ),
            $warehouseId
        );
    }
}
