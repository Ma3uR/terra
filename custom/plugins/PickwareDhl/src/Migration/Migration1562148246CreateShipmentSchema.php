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

class Migration1562148246CreateShipmentSchema extends MigrationStep
{
    /**
     * @inheritDoc
     */
    public function getCreationTimestamp(): int
    {
        return 1562148246;
    }

    /**
     * @inheritDoc
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_shipment` (
                `id` BINARY(16) NOT NULL,
                `shipment_blueprint` JSON NOT NULL,
                `carrier_technical_name` VARCHAR(255) NULL,
                `cancelled` TINYINT(1),
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.pickware_dhl_shipment.shipment_blueprint` CHECK (JSON_VALID(`shipment_blueprint`)),
                KEY `fk.pickware_dhl_shipment.carrier_technical_name` (`carrier_technical_name`),
                CONSTRAINT `fk.pickware_dhl_shipment.carrier_technical_name` FOREIGN KEY (`carrier_technical_name`) REFERENCES `pickware_dhl_carrier` (`technical_name`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_tracking_code` (
                `id` BINARY(16) NOT NULL,
                `tracking_code` VARCHAR(255) NOT NULL,
                `tracking_url` VARCHAR(255) NULL,
                `meta_information` JSON NOT NULL,
                `shipment_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.pickware_dhl_tracking_code.meta_information` CHECK (JSON_VALID(`meta_information`)),
                KEY `fk.pickware_dhl_tracking_code.shipment_id` (`shipment_id`),
                CONSTRAINT `fk.pickware_dhl_tracking_code.shipment_id` FOREIGN KEY (`shipment_id`) REFERENCES `pickware_dhl_shipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_document_type` (
                `id` BINARY(16) NOT NULL,
                `technical_name` VARCHAR(255) NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `unique.technical_name` UNIQUE (`technical_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_page_format` (
                `id` BINARY(16) NOT NULL,
                `technical_name` VARCHAR(255) NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `size` JSON NOT NULL,
                `din_name` VARCHAR(255) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.pickware_dhl_page_format.size` CHECK (JSON_VALID(`size`)),
                CONSTRAINT `unique.technical_name` UNIQUE (`technical_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_document` (
                `id` BINARY(16) NOT NULL,
                `page_format_technical_name` VARCHAR(255) NOT NULL,
                `document_type_technical_name` VARCHAR(255) NOT NULL,
                `mime_type` VARCHAR(255) NOT NULL,
                `orientation` VARCHAR(255) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.pickware_dhl_document.page_format_technical_name` (`page_format_technical_name`),
                KEY `fk.pickware_dhl_document.document_type_technical_name` (`document_type_technical_name`),
                CONSTRAINT `fk.pickware_dhl_document.page_format_technical_name` FOREIGN KEY (`page_format_technical_name`) REFERENCES `pickware_dhl_page_format` (`technical_name`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.pickware_dhl_document.document_type_technical_name` FOREIGN KEY (`document_type_technical_name`) REFERENCES `pickware_dhl_document_type` (`technical_name`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_shipment_order_delivery_mapping` (
                `shipment_id` BINARY(16) NOT NULL,
                `order_delivery_id` BINARY(16) NOT NULL,
                `order_delivery_version_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`shipment_id`,`order_delivery_id`,`order_delivery_version_id`),
                KEY `fk.pickware_dhl_shipment_order_delivery_mapping.shipme` (`shipment_id`),
                KEY `fk.pickware_dhl_shipment_order_delivery_mapping.order_id` (`order_delivery_id`,`order_delivery_version_id`),
                CONSTRAINT `fk.pickware_dhl_shipment_order_delivery_mapping.shipment_id` FOREIGN KEY (`shipment_id`) REFERENCES `pickware_dhl_shipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.pickware_dhl_shipment_order_delivery_mapping.order_delive` FOREIGN KEY (`order_delivery_id`,`order_delivery_version_id`) REFERENCES `order_delivery` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_document_tracking_code_mapping` (
                `tracking_code_id` BINARY(16) NOT NULL,
                `document_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`tracking_code_id`,`document_id`),
                KEY `fk.pickware_dhl_document_tracking_code_mapping.tracking_code_id` (`tracking_code_id`),
                KEY `fk.pickware_dhl_document_tracking_code_mapping.document_id` (`document_id`),
                CONSTRAINT `fk.pickware_dhl_document_tracking_code_mapping.tracking_code_id` FOREIGN KEY (`tracking_code_id`) REFERENCES `pickware_dhl_tracking_code` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.pickware_dhl_document_tracking_code_mapping.document_id` FOREIGN KEY (`document_id`) REFERENCES `pickware_dhl_document` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `pickware_dhl_document_shipment_mapping` (
                `shipment_id` BINARY(16) NOT NULL,
                `document_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`shipment_id`,`document_id`),
                KEY `fk.pickware_dhl_document_shipment_mapping.shipment_id` (`shipment_id`),
                KEY `fk.pickware_dhl_document_shipment_mapping.document_id` (`document_id`),
                CONSTRAINT `fk.pickware_dhl_document_shipment_mapping.shipment_id` FOREIGN KEY (`shipment_id`) REFERENCES `pickware_dhl_shipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.pickware_dhl_document_shipment_mapping.document_id` FOREIGN KEY (`document_id`) REFERENCES `pickware_dhl_document` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    /**
     * @inheritDoc
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
