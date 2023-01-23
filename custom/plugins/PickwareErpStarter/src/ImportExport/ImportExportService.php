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

use Pickware\PickwareErpStarter\ImportExport\DependencyInjection\ImporterRegistry;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportDefinition;
use Pickware\PickwareErpStarter\ImportExport\Exception\ImporterServiceDoesNotExistException;
use Pickware\PickwareErpStarter\PickwareErpStarter;
use Pickware\DalBundle\EntityManager;
use Pickware\DocumentBundle\DocumentContentsService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SplFileInfo;

class ImportExportService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DocumentContentsService
     */
    private $documentContentsService;

    /**
     * @var ImportExportScheduler
     */
    private $importExportScheduler;

    /**
     * @var ImporterRegistry
     */
    private $importerRegistry;

    public function __construct(
        EntityManager $entityManager,
        DocumentContentsService $documentContentsService,
        ImportExportScheduler $importExportScheduler,
        ImporterRegistry $importerRegistry
    ) {
        $this->entityManager = $entityManager;
        $this->documentContentsService = $documentContentsService;
        $this->importExportScheduler = $importExportScheduler;
        $this->importerRegistry = $importerRegistry;
    }

    /**
     * @param SplFileInfo $csvFile
     * @param array $options
     * @param Context $context
     * @return string
     */
    public function importCsvFileAsync(SplFileInfo $csvFile, array $options, Context $context): string
    {
        if (!$this->importerRegistry->hasImporter($options['profileTechnicalName'])) {
            throw new ImporterServiceDoesNotExistException($options['profileTechnicalName']);
        }

        $stream = fopen($csvFile->getPathname(), 'rb');
        $documentId = $this->documentContentsService->saveStreamAsDocument($stream, $context, [
            'documentTypeTechnicalName' => PickwareErpStarter::DOCUMENT_TYPE_TECHNICAL_NAME_IMPORT,
            'mimeType' => 'text/csv',
            'fileName' => $options['fileName'] ?? $csvFile->getFilename(),
        ]);

        $importId = Uuid::randomHex();
        $importPayload = [
            'id' => $importId,
            'type' => ImportExportDefinition::TYPE_IMPORT,
            'profileTechnicalName' => $options['profileTechnicalName'],
            'state' => ImportExportDefinition::STATE_PENDING,
            'documentId' => $documentId,
            'config' => $options['config'] ?? [],
            'userId' => $options['userId'] ?? null,
            'userComment' => $options['userComment'] ?? null,
        ];
        $this->entityManager->create(ImportExportDefinition::class, [$importPayload], $context);

        $this->importExportScheduler->scheduleImport($importId, $context);

        return $importId;
    }

    public function exportCsvFileAsync(array $options, Context $context): string
    {
        $exportId = Uuid::randomHex();
        $exportPayload = [
            'id' => $exportId,
            'type' => ImportExportDefinition::TYPE_EXPORT,
            'profileTechnicalName' => $options['profileTechnicalName'],
            'config' => $options['config'] ?? [],
            'state' => ImportExportDefinition::STATE_PENDING,
            'userId' => $options['userId'] ?? null,
            'userComment' => $options['userComment'] ?? null,
        ];
        $this->entityManager->create(ImportExportDefinition::class, [$exportPayload], $context);

        $this->importExportScheduler->scheduleExport($exportId, $context);

        return $exportId;
    }
}
