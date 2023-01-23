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

use Doctrine\DBAL\Connection;
use Pickware\DalBundle\Sql\SqlUuid;

class DemandPlanningListService
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function addAllItemsToPurchaseList(string $sessionId, array $listQuery): void
    {
        $parameterTypes = [];
        $parameters = [];
        $additionalJoins = '';
        $additionalFilters = '';

        // Add filters that are used in the administration list view. See also
        // @pickware-erp-starter/demand-panning/demand-planning-store.js
        if (isset($listQuery['productIds']) && count($listQuery['productIds']) > 0) {
            $additionalFilters .= ' AND `demandPlanningListItem`.`product_id` IN (:productIds)';
            $parameters['productIds'] = array_map('hex2bin', $listQuery['productIds']);
            $parameterTypes['productIds'] = Connection::PARAM_STR_ARRAY;
        }
        if (array_key_exists('showOnlyProductsNotOnPurchaseList', $listQuery)
            && $listQuery['showOnlyProductsNotOnPurchaseList']
        ) {
            $additionalJoins .= '
                LEFT JOIN `product`
                    ON `demandPlanningListItem`.`product_id` = `product`.`id`
                    AND `demandPlanningListItem`.`product_version_id` = `product`.`version_id`
                LEFT JOIN `pickware_erp_purchase_list_item` AS `purchaseListItem`
                    ON `purchaseListItem`.`product_id` = `product`.`id`';
            $additionalFilters .= ' AND `purchaseListItem`.`id` IS NULL';
        }
        if (array_key_exists('showOnlyProductsWithPurchaseSuggestionGreaterZero', $listQuery)
            && $listQuery['showOnlyProductsWithPurchaseSuggestionGreaterZero']
        ) {
            $additionalFilters .= ' AND `demandPlanningListItem`.`purchase_suggestion` > 0';
        }

        $sql = 'SELECT LOWER(HEX(`demandPlanningListItem`.`id`)) AS `id`
            FROM `pickware_erp_demand_planning_list_item` AS `demandPlanningListItem`
            ' . $additionalJoins . '
            WHERE `demandPlanningListItem`.`demand_planning_session_id` = :sessionId
                ' . $additionalFilters;
        $parameters['sessionId'] = hex2bin($sessionId);

        $purchaseListItemIds = array_column($this->db->fetchAllAssociative($sql, $parameters, $parameterTypes), 'id');
        $this->addDemandPlanningItemsToPurchaseList($purchaseListItemIds);
    }

    public function addDemandPlanningItemsToPurchaseList(array $demandPlanningListItemIds): void
    {
        if (count($demandPlanningListItemIds) === 0) {
            return;
        }

        // Consider minimum purchase and purchase steps from the productSupplierConfiguration when using the purchase
        // suggestion as purchase list item quantity
        $selectPurchaseSuggestionOrMinimumPurchase = 'GREATEST(
            `demandPlanningListItem`.`purchase_suggestion`,
            IFNULL(`productSupplierConfiguration`.`min_purchase`, 1)
        )';
        $selectPurchaseStepsOrOne = 'GREATEST(IFNULL(`productSupplierConfiguration`.`purchase_steps`, 1), 1)';
        $quantity = vsprintf('%s * CEIL(%s/%s)', [
            $selectPurchaseStepsOrOne,
            $selectPurchaseSuggestionOrMinimumPurchase,
            $selectPurchaseStepsOrOne,
        ]);

        $this->db->executeStatement(
            'INSERT INTO `pickware_erp_purchase_list_item` (
                `id`,
                `product_id`,
                `product_version_id`,
                `purchase_suggestion`,
                `quantity`,
                `created_at`
            ) SELECT
                ' . SqlUuid::UUID_V4_GENERATION . ',
                `demandPlanningListItem`.`product_id`,
                `demandPlanningListItem`.`product_version_id`,
                `demandPlanningListItem`.`purchase_suggestion`,
                ' . $quantity . ',
                NOW(3)
            FROM `pickware_erp_demand_planning_list_item` AS `demandPlanningListItem`
            LEFT JOIN `product`
                ON `demandPlanningListItem`.`product_id` = `product`.`id`
                AND `demandPlanningListItem`.`product_version_id` = `product`.`version_id`
            LEFT JOIN `pickware_erp_product_supplier_configuration` AS `productSupplierConfiguration`
                ON `productSupplierConfiguration`.`product_id` = `product`.`id`
                AND `productSupplierConfiguration`.`product_version_id` = `product`.`version_id`
            WHERE `demandPlanningListItem`.`id` IN (:demandPlanningListItemIds)
            -- Items that already exist on the purchase list are simply ignored here (not updated or failed)
            ON DUPLICATE KEY UPDATE `pickware_erp_purchase_list_item`.`id` = `pickware_erp_purchase_list_item`.`id`',
            [
                'demandPlanningListItemIds' => array_map('hex2bin', $demandPlanningListItemIds),
            ],
            [
                'demandPlanningListItemIds' => Connection::PARAM_STR_ARRAY,
            ]
        );
    }
}
