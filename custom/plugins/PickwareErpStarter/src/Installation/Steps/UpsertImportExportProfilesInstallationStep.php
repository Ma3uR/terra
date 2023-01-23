<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Installation\Steps;

use Doctrine\DBAL\Connection;

class UpsertImportExportProfilesInstallationStep
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var array
     */
    private $profiles;

    public function __construct(Connection $db, array $profiles)
    {
        $this->db = $db;
        $this->profiles = $profiles;
    }

    public function install(): void
    {
        foreach ($this->profiles as $profile) {
            $this->db->executeStatement(
                'INSERT INTO `pickware_erp_import_export_profile`
                (`technical_name`)
                VALUES (:technicalName)
                ON DUPLICATE KEY UPDATE `technical_name` = `technical_name`',
                ['technicalName' => $profile['technicalName']]
            );
        }
    }
}
