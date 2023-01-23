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
 * Replaces the table `pickware_dhl_page_format` with a JSON column `pickware_dhl_document.page_format`.
 */
class Migration1570473516RemovePageFormatTable extends MigrationStep
{
    /**
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1570473516;
    }

    /**
     * @param Connection $connection
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document` ADD `page_format` JSON NULL AFTER `mime_type`;'
        );
        $connection->executeStatement(
            'UPDATE `pickware_dhl_document` `document`
            INNER JOIN `pickware_dhl_page_format` `page_format`
                ON `page_format`.`technical_name` = `document`.`page_format_technical_name`
            SET `page_format`= JSON_OBJECT(
                "description",
                `page_format`.`description`,
                "size",
                `page_format`.`size`
            )'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document` CHANGE `page_format` `page_format` JSON NOT NULL'
        );
        $connection->executeStatement(
            'ALTER TABLE pickware_dhl_document
            DROP FOREIGN KEY `fk.pickware_dhl_document.page_format_technical_name`;'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document` DROP `page_format_technical_name`;'
        );
        $connection->executeStatement(
            'DROP TABLE `pickware_dhl_page_format`;'
        );
    }

    /**
     * @param Connection $connection
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
