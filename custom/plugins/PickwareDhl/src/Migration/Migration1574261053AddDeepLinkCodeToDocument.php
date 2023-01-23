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
use Shopware\Core\Framework\Util\Random;

class Migration1574261053AddDeepLinkCodeToDocument extends MigrationStep
{
    /**
     * @inheritDoc
     */
    public function getCreationTimestamp(): int
    {
        return 1574261053;
    }

    /**
     * @inheritDoc
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document`
                ADD `deep_link_code` CHAR(32) NULL AFTER `id`,
                ADD UNIQUE `uniq.pickware_dhl_document.deep_link_code` (`deep_link_code`)'
        );

        do {
            $affectedRows = $connection->executeStatement(
                'UPDATE `pickware_dhl_document`
                SET `deep_link_code` = :deepLinkCode
                WHERE `deep_link_code` IS NULL
                LIMIT 1',
                [
                    'deepLinkCode' => Random::getString(32, implode(range('a', 'z')) . implode(range(0, 9))),
                ]
            );
        } while ($affectedRows !== 0);

        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document` CHANGE `deep_link_code` `deep_link_code` CHAR(32) NOT NULL;'
        );
    }

    /**
     * @inheritDoc
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
