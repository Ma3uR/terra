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

use League\Flysystem\FilesystemInterface;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportElementDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportEntity;
use Pickware\PickwareErpStarter\PickwareErpStarter;
use Pickware\DalBundle\EntityManager;
use Pickware\DocumentBundle\Model\DocumentDefinition;
use Pickware\DocumentBundle\Model\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class DatabaseToCsvWriter
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var FilesystemInterface
     */
    private $fileSystem;

    public function __construct(
        EntityManager $entityManager,
        FilesystemInterface $fileSystem,
        int $batchSize
    ) {
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->batchSize = $batchSize;
    }

    /**
     * @param string $exportId
     * @param array $offset Array with key 'nextElementToWrite', representing at which ExportElement to start to write.
     * Value has to be provided.
     * @param Context $context
     * @return array|null
     */
    public function writeChunk(string $exportId, array $offset, Context $context): ?array
    {
        /** @var ImportExportEntity $export */
        $export = $this->entityManager->findByPrimaryKey(
            ImportExportDefinition::class,
            $exportId,
            $context,
            ['document']
        );

        if ($offset === null || count($offset) === 0) {
            $offset = [
                'nextElementToWrite' => 0,
            ];
        }

        $criteria = EntityManager::createCriteriaFromArray(['importExportId' => $exportId]);
        $criteria->addFilter(new RangeFilter('rowNumber', [
            RangeFilter::GTE => $offset['nextElementToWrite'],
            RangeFilter::LT => $offset['nextElementToWrite'] + $this->batchSize,
        ]));
        $elements = $this->entityManager->findBy(ImportExportElementDefinition::class, $criteria, $context);

        $document = $export->getDocument() ?? $this->createCsvDocument($export, $context);
        $path = $this->downloadDocumentFromFilesystem($document);

        try {
            $csvWriter = new CsvWriter($path);

            if ($offset['nextElementToWrite'] == 0 && count($elements) > 0) {
                $csvWriter->writeHeader($elements->first()->getRowData());
            }

            foreach ($elements as $element) {
                $csvWriter->append($element->getRowData());
                $offset['nextElementToWrite'] = $offset['nextElementToWrite'] + 1;
            }
        } finally {
            $csvWriter->close();
        }

        $this->uploadDocumentToFilesystem($document, $path, $context);

        if (count($elements) < $this->batchSize) {
            return null;
        }

        return $offset;
    }

    private function createCsvDocument(ImportExportEntity $export, Context $context): DocumentEntity
    {
        $documentId = Uuid::randomHex();
        $filePath = sprintf('/documents/%s', $documentId);

        if ($export->getConfig()['locale'] == 'de-DE') {
            $fileName = 'Bestandsübersicht-Export '.date('Y-m-d-H-i-s').'.csv';
        } else {
            $fileName = 'Stock Overview export '.date('Y-m-d-H-i-s').'.csv';
        }

        $payload = [
            'id' => $documentId,
            'fileSizeInBytes' => 0,
            'documentTypeTechnicalName' => PickwareErpStarter::DOCUMENT_TYPE_TECHNICAL_NAME_EXPORT,
            'mimeType' => 'text/csv',
            'fileName' => $fileName,
            'pathInPrivateFileSystem' => $filePath,
        ];

        $this->entityManager->create(DocumentDefinition::class, [$payload], $context);

        /** @var DocumentEntity $documentEntity */
        $documentEntity = $this->entityManager->findOneBy(
            DocumentDefinition::class,
            EntityManager::createCriteriaFromArray(['id' => $documentId]),
            $context
        );

        $this->entityManager->update(
            ImportExportDefinition::class,
            [
                [
                    'id' => $export->getId(),
                    'documentId' => $documentId,
                ],
            ],
            $context
        );

        $export->setDocument($documentEntity);

        return $documentEntity;
    }

    private function uploadDocumentToFilesystem(DocumentEntity $document, string $path, Context $context): void
    {
        $readStream = fopen($path, 'rb');
        // Adding metadata for i.e. Google cloud storage to prohibit caching of the object
        $this->fileSystem->putStream($document->getPathInPrivateFileSystem(), $readStream, [
            'metadata' => [
                'cacheControl' => 'public, max-age=0',
            ],
        ]);
        if (is_resource($readStream)) {
            fclose($readStream);
        }

        $this->entityManager->update(
            DocumentDefinition::class,
            [
                [
                    'id' => $document->getId(),
                    'fileSizeInBytes' => $this->fileSystem->getSize($document->getPathInPrivateFileSystem()),
                ],
            ],
            $context
        );

        unlink($path);
    }

    private function downloadDocumentFromFilesystem(DocumentEntity $document): string
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), '');

        if ($this->fileSystem->has($document->getPathInPrivateFileSystem())) {
            $readStream = $this->fileSystem->readStream($document->getPathInPrivateFileSystem());
            $writeStream = fopen($tempFilePath, 'wb');
            stream_copy_to_stream($readStream, $writeStream);
            fclose($writeStream);
        }

        return $tempFilePath;
    }
}
