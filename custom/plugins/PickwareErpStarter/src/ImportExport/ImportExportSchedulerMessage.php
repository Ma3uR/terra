<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport;

use Shopware\Core\Framework\Context;

class ImportExportSchedulerMessage
{
    public const STATE_EXECUTE_IMPORT = 'execute-import';
    public const STATE_READ_CSV_TO_DATABASE = 'read-csv-to-database';
    public const STATE_CSV_FILE_VALIDATION = 'csv-file-validation';

    public const STATE_EXECUTE_EXPORT = 'execute-export';
    public const STATE_WRITE_DATABASE_TO_CSV = 'write-database-to-csv';

    /**
     * @var string
     */
    private $importExportId;

    /**
     * @var string
     */
    private $state;

    /**
     * @var array
     */
    private $stateData;

    /**
     * @var Context
     */
    private $context;

    public function __construct(string $importExportId, string $state, array $stateData, Context $context)
    {
        $this->importExportId = $importExportId;
        $this->state = $state;
        $this->stateData = $stateData;
        $this->context = $context;
    }

    public function getImportExportId(): string
    {
        return $this->importExportId;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getStateData(): array
    {
        return $this->stateData;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
