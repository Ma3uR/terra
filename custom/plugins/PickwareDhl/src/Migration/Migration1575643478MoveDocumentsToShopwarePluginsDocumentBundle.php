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
 * The document entity has been moved to the bundle pickware/shopware-plugins-document-bundle.
 *
 * This migration moves all data from pickware_dhl_document (the table from the pickware_dhl_document) to
 * pickware_document (the table from the bundle).
 *
 */
class Migration1575643478MoveDocumentsToShopwarePluginsDocumentBundle extends MigrationStep
{
    /**
     * @inheritDoc
     */
    public function getCreationTimestamp(): int
    {
        return 1575643478;
    }

    /**
     * @inheritDoc
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'INSERT INTO `pickware_document_type` (
                `technical_name`,
                `description`,
                `created_at`,
                `updated_at`
            )
            SELECT
                `technical_name`,
                `description`,
                `created_at`,
                `updated_at`
            FROM `pickware_dhl_document_type`
            ON DUPLICATE KEY UPDATE
            `technical_name` = VALUES(`technical_name`)'
        );
        $connection->executeStatement(
            'INSERT INTO `pickware_document` (
                `id`,
                `deep_link_code`,
                `document_type_technical_name`,
                `mime_type`,
                `page_format`,
                `orientation`,
                `created_at`,
                `updated_at`,
                `file_size_in_bytes`,
                `path_in_private_file_system`
            )
            SELECT
                `id`,
                `deep_link_code`,
                `document_type_technical_name`,
                `mime_type`,
                `page_format`,
                `orientation`,
                `created_at`,
                `updated_at`,
                /* Use "old" DB defaults for `file_size_in_bytes` and `path_in_private_file_system`. These defaults have
                   been removed in a later version of the document bundle via migration. Since bundle migrations are
                   executed before plugin migrations we expect the tables to be in their most recent state. */
                -1,
                CONCAT("documents/", LOWER(HEX(`id`)))
            FROM `pickware_dhl_document`
            ON DUPLICATE KEY UPDATE
            `id` = VALUES(`id`)'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document_tracking_code_mapping`
                DROP FOREIGN KEY `fk.pickware_dhl_document_tracking_code_mapping.document_id`'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document_tracking_code_mapping`
                ADD CONSTRAINT `fk.pickware_dhl_document_tracking_code_mapping.document_id`
                    FOREIGN KEY (`document_id`)
                    REFERENCES `pickware_document`(`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document_shipment_mapping`
                DROP FOREIGN KEY `fk.pickware_dhl_document_shipment_mapping.document_id`'
        );
        $connection->executeStatement(
            'ALTER TABLE `pickware_dhl_document_shipment_mapping`
                ADD CONSTRAINT `fk.pickware_dhl_document_shipment_mapping.document_id`
                    FOREIGN KEY (`document_id`)
                    REFERENCES `pickware_document`(`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE'
        );

        $connection->executeStatement('DROP TABLE pickware_dhl_document');
        $connection->executeStatement('DROP TABLE pickware_dhl_document_type');
    }

    /**
     * @inheritDoc
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
