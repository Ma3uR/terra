<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemandPlanning;

use DateTime;
use Doctrine\DBAL\Connection;
use Pickware\PickwareErpStarter\DemandPlanning\Model\DemandPlanningSessionDefinition;
use Pickware\PickwareErpStarter\DemandPlanning\Model\DemandPlanningSessionEntity;
use Pickware\DalBundle\EntityManager;
use Pickware\DalBundle\Sql\SqlUuid;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;

class DemandPlanningCalculationService
{

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(Connection $db, EntityManager $entityManager)
    {
        $this->db = $db;
        $this->entityManager = $entityManager;
    }

    public function calculateDemand(string $sessionId, Context $context): void
    {
        /** @var DemandPlanningSessionEntity $session */
        $session = $this->entityManager->findByPrimaryKey(
            DemandPlanningSessionDefinition::class,
            $sessionId,
            $context
        );
        if (!$session) {
            throw DemandPlanningException::sessionNotFound($sessionId);
        }
        $configuration = $session->getConfiguration();

        // Delete all existing demand planning list items for the given session before recalculating its items
        $this->db->executeStatement(
            'DELETE FROM `pickware_erp_demand_planning_list_item`
            WHERE `demand_planning_session_id` = :sessionId;',
            [
                'sessionId' => hex2bin($sessionId),
            ]
        );

        $additionalFilter = '';
        if ($configuration->getShowOnlyStockAtOrBelowReorderPoint()) {
            // When a product has no reorder point, this filter should remove it from the result. And since (n <= NULL)
            // is evaluated NULL in SQL, we can use IFNULL(.., FALSE) here.
            $additionalFilter .= 'AND IFNULL(`product`.`stock` <= `productConfiguration`.`reorder_point`, FALSE)';
        }

        // Create demand planning list items with new configuration
        $salesPredictionCalculation = '(CEIL(SUM(`orderLineItemsInSalesInterval`.quantity) * :referenceSalesToPredictionFactor))';
        $reservedStockCalculation = '(`product`.stock - `product`.available_stock)';
        $purchaseSuggestionCalculation = 'GREATEST(
            0,
            (IFNULL(`productConfiguration`.`reorder_point`, 0) + (' . $reservedStockCalculation . ' * :considerOpenOrdersInPurchaseSuggestion) - `product`.`stock`),
            (' . $salesPredictionCalculation . ' + (' . $reservedStockCalculation . ' * :considerOpenOrdersInPurchaseSuggestion) - `product`.`stock`)
        )';
        $this->db->executeStatement(
            'INSERT INTO `pickware_erp_demand_planning_list_item` (
                `id`,
                `demand_planning_session_id`,
                `product_id`,
                `product_version_id`,
                `sales`,
                `sales_prediction`,
                `reserved_stock`,
                `purchase_suggestion`,
                `created_at`
            ) SELECT
                ' . SqlUuid::UUID_V4_GENERATION . ',
                :sessionId,
                `product`.`id`,
                `product`.`version_id`,
                IFNULL(SUM(`orderLineItemsInSalesInterval`.`quantity`), 0),
                IFNULL(' . $salesPredictionCalculation . ', 0),
                IFNULL(' . $reservedStockCalculation . ', 0),
                IFNULL(' . $purchaseSuggestionCalculation . ', 0),
                NOW(3)
            FROM `product`
            LEFT JOIN `pickware_erp_product_configuration` AS `productConfiguration`
                ON `productConfiguration`.`product_id` = `product`.`id`
                AND `productConfiguration`.`product_version_id` = `product`.`version_id`
            LEFT JOIN (
                SELECT
                    `order_line_item`.`quantity`,
                    `order_line_item`.`product_id`,
                    `order_line_item`.`product_version_id`
                FROM `order_line_item`
                INNER JOIN `order`
                    ON `order`.`id` = `order_line_item`.`order_id`
                    AND `order`.`version_id` = `order_line_item`.`order_version_id`
                    AND `order`.`order_date` >= :fromDate
                    AND `order`.`order_date` <= :toDate
            ) AS `orderLineItemsInSalesInterval`
                ON `orderLineItemsInSalesInterval`.`product_id` = `product`.`id`
                AND `orderLineItemsInSalesInterval`.`product_version_id` = `product`.`version_id`
            WHERE `product`.`version_id` = :liveVersionId
            ' . $additionalFilter . '
            GROUP BY `product`.id',
            [
                'sessionId' => hex2bin($sessionId),
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                'fromDate' => $configuration->getSalesReferenceIntervalFromDate()->format('Y-m-d'),
                'toDate' => $configuration->getSalesReferenceIntervalToDate()->format('Y-m-d'),
                'referenceSalesToPredictionFactor' => $configuration->getReferenceSalesToPredictionFactor(),
                'considerOpenOrdersInPurchaseSuggestion' => $configuration->getConsiderOpenOrdersInPurchaseSuggestion() ? 1 : 0,
            ]
        );

        $this->db->executeStatement(
            'UPDATE `pickware_erp_demand_planning_session`
            SET `last_calculation` = :now
            WHERE id = :sessionId',
            [
                'now' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'sessionId' => hex2bin($sessionId),
            ]
        );
    }
}
