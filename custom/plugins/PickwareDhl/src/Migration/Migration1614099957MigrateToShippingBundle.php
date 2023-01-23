<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1614099957MigrateToShippingBundle extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1614099957;
    }

    public function update(Connection $db): void
    {
        $this->migrateTables($db);
        $this->migrateShippingMethodConfig($db);
        $this->migrateCommonConfig($db);
        $this->migrateCustomsInformation($db);
        $this->removeCustomsInformationFields($db);
        $this->dropTables($db);
    }

    public function updateDestructive(Connection $db): void
    {
    }

    private function migrateTables(Connection $db): void
    {
        $db->executeStatement(
            'INSERT INTO `pickware_shipping_carrier` (
                `technical_name`,
                `name`,
                `abbreviation`,
                `config_domain`,
                `shipment_config_default_values`,
                `shipment_config_options`,
                `default_parcel_packing_configuration`,
                `return_label_mail_template_type_technical_name`,
                `created_at`,
                `updated_at`
            ) SELECT
                `technical_name`,
                `name`,
                `abbreviation`,
                `config_domain`,
                `shipment_config_default_values`,
                `shipment_config_options`,
                "{}", -- This value will be set in the postUpdate method anyway, so it can be left blank
                `return_label_mail_template_type_technical_name`,
                `created_at`,
                `updated_at`
            FROM `pickware_dhl_carrier`
            ON DUPLICATE KEY UPDATE
                `technical_name` = VALUES(`technical_name`)'
        );
        $db->executeStatement(
            'INSERT INTO `pickware_shipping_shipment` (
                `id`,
                `shipment_blueprint`,
                `carrier_technical_name`,
                `sales_channel_id`,
                `cancelled`,
                `created_at`,
                `updated_at`
            ) SELECT
                `id`,
                `shipment_blueprint`,
                `carrier_technical_name`,
                `sales_channel_id`,
                `cancelled`,
                `created_at`,
                `updated_at`
            FROM `pickware_dhl_shipment`
            ON DUPLICATE KEY UPDATE
                `id` = VALUES(`id`)'
        );
        $db->executeStatement(
            'INSERT INTO `pickware_shipping_tracking_code` (
                `id`,
                `tracking_code`,
                `tracking_url`,
                `meta_information`,
                `shipment_id`,
                `created_at`,
                `updated_at`
            ) SELECT
                `id`,
                `tracking_code`,
                `tracking_url`,
                `meta_information`,
                `shipment_id`,
                `created_at`,
                `updated_at`
            FROM `pickware_dhl_tracking_code`
            ON DUPLICATE KEY UPDATE
                `id` = VALUES(`id`)'
        );
        $db->executeStatement(
            'INSERT INTO `pickware_shipping_document_shipment_mapping` (
                `document_id`,
                `shipment_id`
            ) SELECT
                `document_id`,
                `shipment_id`
            FROM `pickware_dhl_document_shipment_mapping`
            ON DUPLICATE KEY UPDATE
                `document_id` = VALUES(`document_id`)'
        );
        $db->executeStatement(
            'INSERT INTO `pickware_shipping_document_tracking_code_mapping` (
                `document_id`,
                `tracking_code_id`
            ) SELECT
                `document_id`,
                `tracking_code_id`
            FROM `pickware_dhl_document_tracking_code_mapping`
            ON DUPLICATE KEY UPDATE
                `document_id` = VALUES(`document_id`)'
        );
        $db->executeStatement(
            'INSERT INTO `pickware_shipping_shipment_order_mapping` (
                `shipment_id`,
                `order_id`,
                `order_version_id`
            ) SELECT
                `shipment_id`,
                `order_id`,
                `order_version_id`
            FROM `pickware_dhl_shipment_order_mapping`
            ON DUPLICATE KEY UPDATE
                `shipment_id` = VALUES(`shipment_id`)'
        );
        $db->executeStatement(
            'INSERT INTO `pickware_shipping_shipment_order_mapping` (
                `shipment_id`,
                `order_id`,
                `order_version_id`
            ) SELECT
                `shipment_id`,
                `order_id`,
                `order_version_id`
            FROM `pickware_dhl_shipment_order_mapping`
            ON DUPLICATE KEY UPDATE
                `shipment_id` = VALUES(`shipment_id`)'
        );
    }

    private function migrateShippingMethodConfig(Connection $db): void
    {
        $queryResult = $db->fetchAllAssociative(
            'SELECT
                `configuration_key`,
                JSON_UNQUOTE(JSON_EXTRACT(`configuration_value`, "$._value")) AS `value`
            FROM `system_config`
            WHERE `configuration_key` LIKE "PickwareDhl.common.weightCalculation%"'
        );
        $currentWeightConfig = [];
        foreach ($queryResult as $row) {
            $currentWeightConfig[$row['configuration_key']] = $row['value'];
        }
        $newWeightConfiguration = [];
        if (isset($currentWeightConfig['PickwareDhl.common.weightCalculationFallbackParcelWeightInKg'])) {
            $newWeightConfiguration['fallbackParcelWeight'] = [
                'value' => (float) $currentWeightConfig['PickwareDhl.common.weightCalculationFallbackParcelWeightInKg'],
                'unit' => 'kg',
            ];
        }
        if (isset($currentWeightConfig['PickwareDhl.common.weightCalculationMaxParcelWeightInKg'])) {
            $newWeightConfiguration['maxParcelWeight'] = [
                'value' => (float) $currentWeightConfig['PickwareDhl.common.weightCalculationMaxParcelWeightInKg'],
                'unit' => 'kg',
            ];
        }
        if (isset($currentWeightConfig['PickwareDhl.common.weightCalculationFillerWeightPerParcelInKg'])) {
            $newWeightConfiguration['fillerWeightPerParcel'] = [
                'value' => (float) $currentWeightConfig['PickwareDhl.common.weightCalculationFillerWeightPerParcelInKg'],
                'unit' => 'kg',
            ];
        }

        $db->executeStatement(
            'INSERT INTO `pickware_shipping_shipping_method_config` (
                `id`,
                `shipping_method_id`,
                `carrier_technical_name`,
                `shipment_config`,
                `parcel_packing_configuration`,
                `created_at`,
                `updated_at`
            ) SELECT
                `id`,
                `shipping_method_id`,
                `carrier_technical_name`,
                `shipment_config`,
                :parcelPackingConfiguration,
                `created_at`,
                `updated_at`
            FROM `pickware_dhl_shipping_method_config`
            ON DUPLICATE KEY UPDATE
                `id` = VALUES(`id`)',
            [
                'parcelPackingConfiguration' => json_encode($newWeightConfiguration),
            ]
        );
    }

    private function migrateCommonConfig(Connection $db): void
    {
        $db->executeStatement(
            'UPDATE `system_config`
            LEFT JOIN `system_config` AS `system_config_migrated`
                ON
                    `system_config`.`sales_channel_id` <=> `system_config_migrated`.`sales_channel_id`
                    AND REPLACE(
                        `system_config`.`configuration_key`,
                        "PickwareDhl.common",
                        "PickwareShippingBundle.common"
                    ) = `system_config_migrated`.`configuration_key`
            SET `system_config`.`configuration_key` = REPLACE(
                `system_config`.`configuration_key`,
                "PickwareDhl.common",
                "PickwareShippingBundle.common"
            )
            WHERE `system_config_migrated`.`id` IS NULL'
        );
        $db->executeStatement(
            'DELETE FROM `system_config`
            WHERE `configuration_key` LIKE "PickwareDhl.common%"'
        );
        // Delete weight configuration that has been migrated to the shipping method config
        $db->executeStatement(
            'DELETE FROM `system_config`
            WHERE `configuration_key` LIKE "PickwareShippingBundle.common.weightCalculation%"'
        );
    }

    private function migrateCustomsInformation(Connection $db): void
    {
        $db->executeStatement(
            'UPDATE `product_translation`
            SET `custom_fields` = REPLACE(
                `custom_fields`,
                "pickware_dhl_customs_information_",
                "pickware_shipping_customs_information_"
            )
            WHERE `custom_fields` NOT LIKE "%pickware_shipping_customs_information_%"'
        );
    }

    private function removeCustomsInformationFields(Connection $db): void
    {
        $db->executeStatement(
            'DELETE FROM `custom_field_set`
            WHERE name = "pickware_dhl_customs_information"'
        );
    }

    private function dropTables(Connection $db): void
    {
        $db->executeStatement(
            'SET FOREIGN_KEY_CHECKS=0;
            DROP TABLE IF EXISTS `pickware_dhl_carrier`;
            DROP TABLE IF EXISTS `pickware_dhl_document`;
            DROP TABLE IF EXISTS `pickware_dhl_document_shipment_mapping`;
            DROP TABLE IF EXISTS `pickware_dhl_document_tracking_code_mapping`;
            DROP TABLE IF EXISTS `pickware_dhl_shipment`;
            DROP TABLE IF EXISTS `pickware_dhl_shipment_order_mapping`;
            DROP TABLE IF EXISTS `pickware_dhl_shipping_method_config`;
            DROP TABLE IF EXISTS `pickware_dhl_tracking_code`;
            SET FOREIGN_KEY_CHECKS=1;'
        );
    }
}
