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

use Exception;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseEntity;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\HttpUtils\JsonApi\JsonApiErrorSerializable;
use Pickware\DalBundle\EntityManager;
use Shopware\Core\Checkout\Order\OrderEntity;

class OrderShippingException extends Exception implements JsonApiErrorSerializable
{
    private const ERROR_CODE_NAMESPACE = 'PICKWARE_ERP__ORDER_SHIPPING__';
    public const ERROR_CODE_NOT_IN_LIVE_VERSION = self::ERROR_CODE_NAMESPACE . 'NOT_IN_LIVE_VERSION';
    public const ERROR_CODE_ORDER_DOES_NOT_EXIST = self::ERROR_CODE_NAMESPACE . 'ORDER_DOES_NOT_EXIST';
    public const ERROR_CODE_WAREHOUSE_DOES_NOT_EXIST = self::ERROR_CODE_NAMESPACE . 'WAREHOUSE_DOES_NOT_EXIST';
    public const ERROR_CODE_NOT_ENOUGH_STOCK = self::ERROR_CODE_NAMESPACE . 'NOT_ENOUGH_STOCK';

    /**
     * @var JsonApiError
     */
    private $jsonApiError;

    public function __construct(JsonApiError $jsonApiError)
    {
        $this->jsonApiError = $jsonApiError;
        parent::__construct($jsonApiError->getDetail());
    }

    public function serializeToJsonApiError(): JsonApiError
    {
        return $this->jsonApiError;
    }

    public static function notInLiveVersion(): self
    {
        $jsonApiError = new JsonApiError([
            'code' => self::ERROR_CODE_NOT_IN_LIVE_VERSION,
            'title' => 'Not in live context version',
            'detail' => 'Shipping an order is only possible in live version context.',
            'meta' => [],
        ]);

        return new self($jsonApiError);
    }

    public static function orderDoesNotExist(string $orderId): self
    {
        $jsonApiError = new JsonApiError([
            'code' => self::ERROR_CODE_ORDER_DOES_NOT_EXIST,
            'title' => 'Order does not exist',
            'detail' => sprintf(
                'Order with ID=%s does not exist.',
                $orderId
            ),
            'meta' => [
                'orderId' => $orderId,
            ],
        ]);

        return new self($jsonApiError);
    }

    public static function warehouseDoesNotExist(string $warehouseId): self
    {
        $jsonApiError = new JsonApiError([
            'code' => self::ERROR_CODE_WAREHOUSE_DOES_NOT_EXIST,
            'title' => 'Warehouse does not exist',
            'detail' => sprintf(
                'Warehouse with ID=%s does not exist.',
                $warehouseId
            ),
            'meta' => [
                'warehouseId' => $warehouseId,
            ],
        ]);

        return new self($jsonApiError);
    }

    public static function notEnoughStock(WarehouseEntity $warehouse, OrderEntity $order): self
    {
        $jsonApiError = new JsonApiError([
            'code' => self::ERROR_CODE_NOT_ENOUGH_STOCK,
            'title' => 'Operation leads to negative stocks',
            'detail' => sprintf(
                'There is not enough stock in warehouse with ID=%s to ship the order with ID=%s',
                $warehouse->getId(),
                $order->getId()
            ),
            'meta' => [
                'warehouseName' => $warehouse->getName(),
                'warehouseCode' => $warehouse->getCode(),
                'orderNumber' => $order->getOrderNumber(),
            ],
        ]);

        return new self($jsonApiError);
    }
}
