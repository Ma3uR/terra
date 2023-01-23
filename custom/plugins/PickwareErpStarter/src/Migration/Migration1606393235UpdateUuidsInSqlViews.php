<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Migration;

use Doctrine\DBAL\Connection;
use Pickware\PickwareErpStarter\Order\Model\OrderPickabilityViewDefinition;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1606393235UpdateUuidsInSqlViews extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1606393235;
    }

    public function update(Connection $connection): void
    {
        $this->fixProductReorderViewId($connection);
        $this->fixOrderPickabilityViewId($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function fixProductReorderViewId(Connection $connection): void
    {
        // This is a copy of the SQL View creation code from Migration1599487702CreateProductReorderView except the id
        // selection was updated (use product.id).
        $connection->exec(
            'CREATE OR REPLACE VIEW pickware_erp_product_reorder_view AS
                SELECT
                    product.id AS id,
                    product.id AS product_id,
                    product.version_id AS product_version_id,
                    IFNULL(product.stock <= productConfiguration.reorder_point, 0) AS stock_at_or_below_reorder_point,
                    NOW() as updated_at,
                    NOW() as created_at
                FROM product
                LEFT JOIN pickware_erp_product_configuration productConfiguration
                    ON productConfiguration.product_id = product.id
                    AND productConfiguration.product_version_id = product.version_id
                WHERE productConfiguration.reorder_point > 0'
        );
    }

    private function fixOrderPickabilityViewId(Connection $connection): void
    {
        // This is a copy of the SQL View creation code from Migration1605002744CreateOrderPickabilityView except the id
        // selection was updated (use order.id).
        $connection->executeStatement(
            'CREATE OR REPLACE VIEW pickware_erp_order_pickability_view AS
                SELECT
                    `order`.`id` AS id,
                    `order`.`id` AS `order_id`,
                    `order`.`version_id` AS `order_version_id`,
                    CASE
                        -- mark the order as "cancelled or shipped" if either the order or all of the states of its
                        -- order deliveries do not satisfy our reserved stock "states condition"
                        WHEN
                            SUM(IFNULL(`order_delivery_state`.`technical_name` NOT IN (:relevantOrderDeliveryStates), 1)) = COUNT(*)
                            OR MIN(`order_state`.`technical_name`) NOT IN (:relevantOrderStates)
                        THEN :pickabilityStatusCancelledOrShipped
                        -- mark the order as "completely pickable" if all relevant delivery positions are completely
                        -- pickable. Please notice, that non-relevant order delivery positions are considered to be
                        -- "completely pickable"
                        WHEN
                            SUM(IFNULL(`order_delivery_position_pickability`.`completely_pickable`, 1)) = COUNT(*)
                        THEN :pickabilityStatusCompletelyPickable
                        -- mark the order as "partially pickable" if at least one delivery position is (partially)
                        -- pickable
                        WHEN
                            SUM(IFNULL(`order_delivery_position_pickability`.`pickable`, 0)) > 0
                        THEN :pickabilityStatusPartiallyPickable
                        -- otherwise mark the order as "not pickable"
                        ELSE :pickabilityStatusNotPickable
                    END AS `order_pickability_status`,
                    NOW() as updated_at,
                    NOW() as created_at
                FROM `order`
                LEFT JOIN `state_machine_state` AS `order_state`
                    ON `order`.`state_id` = `order_state`.`id`
                LEFT JOIN `order_delivery`
                    ON `order_delivery`.`order_id` = `order`.`id`
                    AND `order_delivery`.`order_version_id` = `order`.`version_id`
                LEFT JOIN `state_machine_state` AS `order_delivery_state`
                    ON `order_delivery_state`.`id` = `order_delivery`.`state_id`
                LEFT JOIN (
                    SELECT
                        `order_delivery_position`.`order_delivery_id` AS `order_delivery_id`,
                        `order_delivery_position`.`order_delivery_version_id` AS `order_delivery_version_id`,
                        -- order delivery positions not related to an existing product are always considered as completely pickabkle
                        `product`.`stock` IS NULL OR `product`.`stock` >= GREATEST(0, `order_delivery_position`.`quantity` - IFNULL(SUM(`pickware_erp_stock`.`quantity`), 0)) AS `completely_pickable`,
                        `product`.`stock` IS NULL OR `product`.`stock` > 0 AS `pickable`
                    FROM `order_delivery_position`
                    INNER JOIN `order_delivery`
                        ON `order_delivery`.`id` = `order_delivery_position`.`order_delivery_id`
                        AND `order_delivery`.`version_id` = `order_delivery_position`.`order_delivery_version_id`
                    INNER JOIN `state_machine_state` AS `order_delivery_state`
                        ON `order_delivery_state`.`id` = `order_delivery`.`state_id`
                    INNER JOIN `order_line_item`
                        ON `order_line_item`.`id` = `order_delivery_position`.`order_line_item_id`
                        AND `order_line_item`.`version_id` = `order_delivery_position`.`order_line_item_version_id`
                    INNER JOIN `order`
                        ON `order`.`id` = `order_line_item`.`order_id`
                        AND `order`.`version_id` = `order_line_item`.`order_version_id`
                    INNER JOIN `state_machine_state` AS `order_state`
                        ON `order`.`state_id` = `order_state`.`id`
                    LEFT JOIN `product`
                        ON `product`.`id` = `order_line_item`.`product_id`
                        AND `product`.`version_id` = `order_line_item`.`product_version_id`
                    LEFT JOIN `pickware_erp_stock`
                        ON `pickware_erp_stock`.`order_delivery_position_id` = `order_delivery_position`.`id`
                        AND `pickware_erp_stock`.`order_delivery_position_version_id` = `order_delivery_position`.`version_id`
                    WHERE
                        `order_delivery_position`.`version_id` = :liveVersionId
                        -- in order to correctly determine the pickability status of an order even if one or more of its
                        -- order deliveries is already shipped (or in has a state which is not considered) we need to
                        -- filter the delivery positions by order status and order delivery status
                        AND `order_delivery_state`.`technical_name` IN (:relevantOrderDeliveryStates)
                        AND `order_state`.`technical_name` IN (:relevantOrderStates)
                        AND `order_line_item`.`type` IN (:relevantOrderLineItemTypes)
                    GROUP BY `order_delivery_position`.`id`, `order_delivery_position`.`version_id`
                ) AS `order_delivery_position_pickability`
                    ON `order_delivery_position_pickability`.`order_delivery_id` = `order_delivery`.`id`
                    AND `order_delivery_position_pickability`.`order_delivery_version_id` = `order_delivery`.`version_id`
                WHERE `order`.`version_id` = :liveVersionId
                GROUP BY `order`.`id`, `order`.`version_id`',
            [
                'pickabilityStatusCompletelyPickable' => OrderPickabilityViewDefinition::PICKABILITY_STATUS_COMPLETELY_PICKABLE,
                'pickabilityStatusPartiallyPickable' => OrderPickabilityViewDefinition::PICKABILITY_STATUS_PARTIALLY_PICKABLE,
                'pickabilityStatusNotPickable' => OrderPickabilityViewDefinition::PICKABILITY_STATUS_NOT_PICKABLE,
                'pickabilityStatusCancelledOrShipped' => OrderPickabilityViewDefinition::PICKABILITY_STATUS_CANCELLED_OR_SHIPPED,
                'relevantOrderStates' => [
                    OrderStates::STATE_OPEN,
                    OrderStates::STATE_IN_PROGRESS,
                ],
                'relevantOrderDeliveryStates' => [
                    OrderDeliveryStates::STATE_OPEN,
                    OrderDeliveryStates::STATE_PARTIALLY_SHIPPED,
                ],
                'relevantOrderLineItemTypes' => [
                    LineItem::PRODUCT_LINE_ITEM_TYPE,
                ],
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'relevantOrderStates' => Connection::PARAM_STR_ARRAY,
                'relevantOrderDeliveryStates' => Connection::PARAM_STR_ARRAY,
                'relevantOrderLineItemTypes' => Connection::PARAM_STR_ARRAY,
            ]
        );
    }
}
