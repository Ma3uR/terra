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
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1607589493UseOrderInsteadOfOrderDeliveryPositionInOrderPickabilityView extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1607589493;
    }

    public function update(Connection $connection): void
    {
        // This is a copy of the SQL View creation code from Migration1605002744CreateOrderPickabilityView except that
        // the referenced quantity that has to be shipped is the order line item quantity (not the order delivery
        // position quantity)
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
                            MIN(`order_state`.`technical_name`) NOT IN (:relevantOrderStates)
                        THEN :pickabilityStatusCancelledOrShipped
                        -- mark the order as "completely pickable" if all relevant order lie items are completely
                        -- pickable. Please notice, that non-relevant order line items are considered to be
                        -- "completely pickable"
                        WHEN
                            SUM(IFNULL(`order_line_item_pickability`.`completely_pickable`, 1)) = COUNT(*)
                        THEN :pickabilityStatusCompletelyPickable
                        -- mark the order as "partially pickable" if at least one order line item is (partially)
                        -- pickable
                        WHEN
                            SUM(IFNULL(`order_line_item_pickability`.`pickable`, 0)) > 0
                        THEN :pickabilityStatusPartiallyPickable
                        -- otherwise mark the order as "not pickable"
                        ELSE :pickabilityStatusNotPickable
                    END AS `order_pickability_status`,
                    NOW() as updated_at,
                    NOW() as created_at
                FROM `order`
                LEFT JOIN `state_machine_state` AS `order_state`
                    ON `order`.`state_id` = `order_state`.`id`
                LEFT JOIN (
                    SELECT
                        `order`.`id` AS `order_id`,
                        `order`.`version_id` AS `order_version_id`,
                        -- order line items not related to an existing product are always considered as completely pickable
                        `product`.`stock` IS NULL OR `product`.`stock` >= GREATEST(0, `order_line_item`.`quantity` - IFNULL(SUM(`pickware_erp_stock`.`quantity`), 0)) AS `completely_pickable`,
                        `product`.`stock` IS NULL OR `product`.`stock` > 0 AS `pickable`
                    FROM `order_line_item`
                    INNER JOIN `order`
                        ON `order`.`id` = `order_line_item`.`order_id`
                        AND `order`.`version_id` = `order_line_item`.`order_version_id`
                    INNER JOIN `state_machine_state` AS `order_state`
                        ON `order`.`state_id` = `order_state`.`id`
                    LEFT JOIN `product`
                        ON `product`.`id` = `order_line_item`.`product_id`
                        AND `product`.`version_id` = `order_line_item`.`product_version_id`
                    LEFT JOIN `pickware_erp_stock`
                        ON `pickware_erp_stock`.`order_id` = `order`.`id`
                        AND `pickware_erp_stock`.`order_version_id` = `order`.`version_id`
                        AND `pickware_erp_stock`.`product_id` = `product`.`id`
                        AND `pickware_erp_stock`.`product_version_id` = `product`.`version_id`
                    WHERE
                        `order_line_item`.`version_id` = :liveVersionId
                        -- in order to correctly determine the pickability status of an order even if one or more of its
                        -- order deliveries is already shipped (or in has a state which is not considered) we need to
                        -- filter the order line items by order status and order line item types
                        AND `order_state`.`technical_name` IN (:relevantOrderStates)
                        AND `order_line_item`.`type` IN (:relevantOrderLineItemTypes)
                    GROUP BY `order_line_item`.`id`, `order_line_item`.`version_id`
                ) AS `order_line_item_pickability`
                    ON `order_line_item_pickability`.`order_id` = `order`.`id`
                    AND `order_line_item_pickability`.`order_version_id` = `order`.`version_id`
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
                'relevantOrderLineItemTypes' => [
                    LineItem::PRODUCT_LINE_ITEM_TYPE,
                ],
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'relevantOrderStates' => Connection::PARAM_STR_ARRAY,
                'relevantOrderLineItemTypes' => Connection::PARAM_STR_ARRAY,
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
