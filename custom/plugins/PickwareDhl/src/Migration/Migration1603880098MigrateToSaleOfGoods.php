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

/**
 * With DHL BCP API 3.1 a new value "sale-of-goods" was introduces for the type of a shipment in the customs
 * information. Formerly this type was represented by type "other" and comment "goods for sale". This also was the
 * default value in the configuration when installing DHL.
 * This migration automatically converts settings with type "other" and comment "goods for sale" to type "sale-of-goods"
 * with no comment to save the user some manual re-configuration.
 */
class Migration1603880098MigrateToSaleOfGoods extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1603880098;
    }

    public function update(Connection $connection): void
    {
        $salesChannels = $connection->executeQuery(
            'SELECT
                IF(
                    `sc_type_of_shipment`.`sales_channel_id` IS NULL,
                    NULL,
                    LOWER(HEX(`sc_type_of_shipment`.`sales_channel_id`))
                ) AS `id`
            FROM
                `system_config` AS `sc_type_of_shipment`
            INNER JOIN
                `system_config` AS `sc_comment`
            ON
                `sc_comment`.`configuration_key` = "PickwareDhl.common.customsInformationExplanation"
                AND `sc_type_of_shipment`.sales_channel_id <=> `sc_comment`.`sales_channel_id`
            WHERE
                `sc_type_of_shipment`.`configuration_key` = "PickwareDhl.common.customsInformationTypeOfShipment"
                AND JSON_UNQUOTE(JSON_EXTRACT(`sc_type_of_shipment`.`configuration_value`,"$._value")) = "other"
                AND JSON_UNQUOTE(JSON_EXTRACT(`sc_comment`.`configuration_value`,"$._value")) = "goods for sale"'
        );

        foreach ($salesChannels as $salesChannel) {
            $connection->executeStatement(
                'UPDATE `system_config`
                SET
                    `configuration_value` = JSON_OBJECT("_value", "sale-of-goods")
                WHERE
                    `configuration_key` = "PickwareDhl.common.customsInformationTypeOfShipment"
                    AND `sales_channel_id` <=> :salesChannelId',
                ['salesChannelId' => $salesChannel['id'] ? hex2bin($salesChannel['id']) : null]
            );
            $connection->executeStatement(
                'UPDATE `system_config`
                SET
                    `configuration_value` = JSON_OBJECT("_value", "")
                WHERE
                    `configuration_key` = "PickwareDhl.common.customsInformationExplanation"
                    AND `sales_channel_id` <=> :salesChannelId',
                ['salesChannelId' => $salesChannel['id'] ? hex2bin($salesChannel['id']) : null]
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
