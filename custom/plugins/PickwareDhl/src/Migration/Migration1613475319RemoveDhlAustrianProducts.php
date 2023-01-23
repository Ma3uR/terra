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
 * Remove shipping method configs and configured participations from DB for Austrian products.
 */
class Migration1613475319RemoveDhlAustrianProducts extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1613475319;
    }

    public function update(Connection $db): void
    {
        $db->executeStatement(
            'DELETE FROM `pickware_dhl_shipping_method_config`
            WHERE
                `carrier_technical_name` = "dhl"
                AND JSON_UNQUOTE(JSON_EXTRACT(`shipment_config`, "$.product")) IN (
                    "V82PARCEL",
                    "V86PARCEL",
                    "V87PARCEL"
                )'
        );
        $db->executeStatement(
            'DELETE FROM `system_config`
            WHERE `configuration_key` IN (
                "PickwareDhl.dhl.participationV82PARCEL",
                "PickwareDhl.dhl.participationV86PARCEL",
                "PickwareDhl.dhl.participationV87PARCEL"
            )'
        );
    }

    public function updateDestructive(Connection $db): void
    {
    }
}
