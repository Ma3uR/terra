<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport\Csv;

use Franzose\DoctrineBulkInsert\Query;
use League\Flysystem\FilesystemInterface;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportEntity;
use Pickware\DalBundle\EntityManager;
use Shopware\Core\Content\ImportExport\Processing\Reader\CsvReader;
use Shopware\Core\Content\ImportExport\Struct\Config;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

class CsvToDatabaseReader
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var FilesystemInterface
     */
    private $documentBundleFileSystem;

    /**
     * @var Query
     */
    private $bulkInserter;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        EntityManager $entityManager,
        FilesystemInterface $documentBundleFileSystem,
        Query $bulkInserter,
        int $batchSize
    ) {
        $this->entityManager = $entityManager;
        $this->documentBundleFileSystem = $documentBundleFileSystem;
        $this->bulkInserter = $bulkInserter;
        $this->batchSize = $batchSize;
    }

    /**
     * @param string $importId
     * @param array $offset Array with keys 'nextLineToRead' and 'nextByteToRead', both representing where to start to read the
     * CSV file. Both values have to be provided.
     * @param Context $context
     * @return array|null
     */
    public function readChunk(string $importId, array $offset, Context $context): ?array
    {
        /** @var ImportExportEntity $import */
        $import = $this->entityManager->findByPrimaryKey(
            ImportExportDefinition::class,
            $importId,
            $context,
            ['document']
        );

        if ($offset === null) {
            $offset = [
                'nextLineToRead' => 0,
                'nextByteToRead' => 0,
            ];
        }

        // The CSV file has a header, so first read record is always from second line (index: 1)
        // The byte offset can be left 0 because the reader automatically skips over the header (and then the correct
        // byte-offset is set later in this method)
        if ($offset['nextLineToRead'] === 0) {
            $offset['nextLineToRead'] = 1;
        }
        $csvReader = new CsvReader(';', '"', '\\', true);
        $csvStream = $this->documentBundleFileSystem->readStream($import->getDocument()->getPathInPrivateFileSystem());

        $payload = [];
        $csvIterator = $csvReader->read(new Config([], []), $csvStream, $offset['nextByteToRead']);
        foreach ($csvIterator as $rowData) {
            $payload[] = [
                'id' => Uuid::randomBytes(),
                'import_export_id' => hex2bin($import->getId()),
                'row_number' => $offset['nextLineToRead'],
                'row_data' => json_encode($rowData),
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s.u'),
            ];
            $offset['nextLineToRead'] = $offset['nextLineToRead'] + 1;
            if (count($payload) >= $this->batchSize) {
                break;
            }
        }
        $this->bulkInserter->execute('pickware_erp_import_export_element', $payload);

        $offset['nextByteToRead'] = ftell($csvStream);
        if (feof($csvStream)) {
            return null;
        }

        return $offset;
    }
}
