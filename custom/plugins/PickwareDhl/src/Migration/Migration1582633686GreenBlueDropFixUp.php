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
 * From version 0.2.1 to 0.3.0 the support for Green-Blue migrations was removed. That means that the destructive part
 * of migrations have been merged into to non-destructive part. (Commit: f9b06fec1902027df88dab9cc8b6db039f679227)
 * Unfortunately Shopware 6 does not enforce the destructive migrations to be executed before a plugin update. That lead
 * to the situation that the update from v0.2.1 to 0.3.0 was executed on systems that did not perform any
 * destructive migrations yet. For that systems the destructive part of the migrations now will never be executed
 * because it was moved to the non-destructive part. And the non-destructive part already did run.
 *
 * To fix this issue on the respective systems, this migration runs the former destructive part of all the affected
 * migrations. Since this migration will also run on systems that DID run the destructive migrations or on systems where
 * the plugin is installed completely from scratch, the atomic parts of this migration have to be idempotent.
 */
class Migration1582633686GreenBlueDropFixUp extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1582633686;
    }

    public function update(Connection $db): void
    {
        // Destructive part of migration "Migration1570473516RemovePageFormatTable"
        if ($this->doesConstraintExist($db, 'fk.pickware_dhl_document.page_format_technical_name')) {
            $db->executeStatement(
                'ALTER TABLE pickware_dhl_document
                DROP FOREIGN KEY `fk.pickware_dhl_document.page_format_technical_name`;'
            );
        }
        if ($this->doesColumnExist($db, 'pickware_dhl_document', 'page_format_technical_name')) {
            $db->executeStatement(
                'ALTER TABLE `pickware_dhl_document` DROP COLUMN `page_format_technical_name`'
            );
        }
        $db->executeStatement(
            'DROP TABLE IF EXISTS `pickware_dhl_page_format`;'
        );
        $this->dropTriggerIfExists($db, 'pickwareDhl.1570473516.forwardTrigger');
        $this->dropTriggerIfExists($db, 'pickwareDhl.1570473516.backwardTrigger');

        // Destructive part of migration "Migration1571066783AddShipmentConfigColumnsToCarrier"
        $this->dropTriggerIfExists($db, 'pickwareDhl.1571066783.forwardTrigger');
    }

    public function updateDestructive(Connection $db): void
    {
    }

    private function doesColumnExist(Connection $db, string $tableName, string $columnName): bool
    {
        $columnCount = $db->fetchOne(
            'SELECT COUNT(COLUMN_NAME)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = (SELECT DATABASE())
            AND TABLE_NAME = :tableName
            AND COLUMN_NAME = :columnName',
            [
                'tableName' => $tableName,
                'columnName' => $columnName,
            ]
        );

        return $columnCount !== '0';
    }

    public function doesConstraintExist(Connection $db, string $constraintName): bool
    {
        $constraintCount = $db->fetchOne(
            'SELECT COUNT(CONSTRAINT_NAME)
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = (SELECT DATABASE())
            AND CONSTRAINT_NAME = :constraintName',
            [
                'constraintName' => $constraintName,
            ]
        );

        return $constraintCount !== '0';
    }

    private function dropTriggerIfExists(Connection $db, string $triggerName): void
    {
        $result = $db->fetchAllAssociative('SHOW TRIGGERS WHERE `Trigger` = :triggerName', ['triggerName' => $triggerName]);

        if (count($result) === 0) {
            return;
        }

        $db->executeStatement(sprintf('DROP TRIGGER `%s`', $triggerName));
    }
}
