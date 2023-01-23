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

class Migration1604519974AddReturnLabelEmailTemplateToCarrier extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1604519974;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_carrier`
            ADD `return_label_mail_template_type_technical_name` VARCHAR(255) NULL AFTER `shipment_config_options`,
            ADD FOREIGN KEY (`return_label_mail_template_type_technical_name`)
                REFERENCES `mail_template_type` (`technical_name`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
