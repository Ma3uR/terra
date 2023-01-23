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
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Removes document types that where created in an older plugin version but were actually never used.
 */
class Migration1579791730RemoveUnusedDocumentTypes extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1579791730;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement(
                'DELETE FROM pickware_document_type WHERE `technical_name` = "customs_declaration_c_22"'
            );
            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (DBALException $e) {
            // In the rare case that this type is used by a document, don't crash the migration because of a failing
            // foreign key constraint.
            // Just leave the document type where it is and ignore it forever
        }
        try {
            $connection->executeStatement(
                'DELETE FROM pickware_document_type WHERE `technical_name` = "customs_declaration_c_23"'
            );
            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (DBALException $e) {
            // In the rare case that this type is used by a document, don't crash the migration because of a failing
            // foreign key constraint.
            // Just leave the document type where it is and ignore it forever
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
