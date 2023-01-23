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
 * Use `technical_name` as the primary key for all tables which have this column.
 * Drops the former primary key `id` column.
 */
class Migration1570636494UseTechnicalNameAsPrimaryKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1570636494;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document_type`
                DROP PRIMARY KEY,
                DROP `id`,
                DROP INDEX `unique.technical_name`,
                ADD PRIMARY KEY(`technical_name`);'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_carrier`
                DROP PRIMARY KEY,
                DROP `id`,
                DROP INDEX `unique.technical_name`,
                ADD PRIMARY KEY(`technical_name`);'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
