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

use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemInterface;
use Pickware\PickwareErpStarter\ImportExport\Csv\CsvToDatabaseReader;
use Pickware\PickwareErpStarter\ImportExport\Csv\DatabaseToCsvWriter;
use Pickware\PickwareErpStarter\ImportExport\DependencyInjection\ImporterRegistry;
use Pickware\PickwareErpStarter\ImportExport\DependencyInjection\ExporterRegistry;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportEntity;
use Pickware\DalBundle\EntityManager;

class ImportExportSchedulerMessageHandler
{
    private const BOM_UTF8 = "\xEF\xBB\xBF";

    /**
     * @var ImportExportStateService
     */
    private $importExportStateService;

    /**
     * @var CsvToDatabaseReader
     */
    private $csvToDatabaseReader;

    /**
     * @var DatabaseToCsvWriter
     */
    private $databaseToCsvWriter;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ImporterRegistry
     */
    private $importerRegistry;

    /**
     * @var ExporterRegistry
     */
    private $exporterRegistry;

    /**
     * @var FilesystemInterface
     */
    private $documentBundleFileSystem;

    public function __construct(
        ImportExportStateService $importExportStateService,
        CsvToDatabaseReader $csvToDatabaseReader,
        DatabaseToCsvWriter $databaseToCsvWriter,
        EntityManager $entityManager,
        Connection $db,
        ImporterRegistry $importerRegistry,
        ExporterRegistry $exporterRegistry,
        FilesystemInterface $documentBundleFileSystem
    ) {
        $this->importExportStateService = $importExportStateService;
        $this->csvToDatabaseReader = $csvToDatabaseReader;
        $this->databaseToCsvWriter = $databaseToCsvWriter;
        $this->entityManager = $entityManager;
        $this->db = $db;
        $this->importerRegistry = $importerRegistry;
        $this->exporterRegistry = $exporterRegistry;
        $this->documentBundleFileSystem = $documentBundleFileSystem;
    }

    public function handleCsvFileValidationMessage(ImportExportSchedulerMessage $message): ?ImportExportSchedulerMessage
    {
        $this->importExportStateService->validate($message->getImportExportId(), $message->getContext());

        /** @var ImportExportEntity $import */
        $import = $this->entityManager->findByPrimaryKey(
            ImportExportDefinition::class,
            $message->getImportExportId(),
            $message->getContext(),
            ['document']
        );
        $stream = $this->documentBundleFileSystem->readStream($import->getDocument()->getPathInPrivateFileSystem());
        $this->skipOverPossibleBom($stream);
        // The max read length value to avoid memory limits and timeouts for the case that a big file is
        // uploaded that does not have line breaks. This can happen when the user accidentally uploads a wrong file
        // format.
        $headerRow = fgetcsv($stream, 8 * 1024, ';', '"', '\\');
        $validationErrors = $this->importerRegistry
            ->getImporterByTechnicalName($import->getProfileTechnicalName())
            ->validateHeaderRow($headerRow, $message->getContext());

        if (count($validationErrors) !== 0) {
            $this->importExportStateService->fail(
                $message->getImportExportId(),
                $validationErrors,
                $message->getContext()
            );

            return null;
        }

        return new ImportExportSchedulerMessage(
            $message->getImportExportId(),
            ImportExportSchedulerMessage::STATE_READ_CSV_TO_DATABASE,
            [],
            $message->getContext()
        );
    }

    public function handleReadCsvToDatabaseMessage(ImportExportSchedulerMessage $message): ?ImportExportSchedulerMessage
    {
        $stateData = $message->getStateData();
        if (!isset($stateData['offset'])) {
            $stateData['offset'] = [
                'nextLineToRead' => 0,
                'nextByteToRead' => 0,
            ];
            $this->importExportStateService->readFile($message->getImportExportId(), $message->getContext());
        }
        $newOffset = $this->csvToDatabaseReader->readChunk(
            $message->getImportExportId(),
            $stateData['offset'],
            $message->getContext()
        );
        if ($newOffset === null) {
            return new ImportExportSchedulerMessage(
                $message->getImportExportId(),
                ImportExportSchedulerMessage::STATE_EXECUTE_IMPORT,
                [],
                $message->getContext()
            );
        }
        $stateData['offset'] = $newOffset;

        return new ImportExportSchedulerMessage(
            $message->getImportExportId(),
            ImportExportSchedulerMessage::STATE_READ_CSV_TO_DATABASE,
            $stateData,
            $message->getContext()
        );
    }

    public function handleExecuteImportMessage(ImportExportSchedulerMessage $message): ?ImportExportSchedulerMessage
    {
        $stateData = $message->getStateData();
        if (!isset($stateData['nextLineToRead'])) {
            $stateData['nextLineToRead'] = 0;
            $rowCount = $this->getImportExportRowCount($message->getImportExportId());
            $this->importExportStateService->startRun($message->getImportExportId(), $rowCount, $message->getContext());
        }
        /** @var ImportExportEntity $import */
        $import = $this->entityManager->findByPrimaryKey(
            ImportExportDefinition::class,
            $message->getImportExportId(),
            $message->getContext()
        );
        $importer = $this->importerRegistry->getImporterByTechnicalName($import->getProfileTechnicalName());
        $newNextLineToRead = $importer->importChunk($import->getId(), $stateData['nextLineToRead'], $message->getContext());
        if ($newNextLineToRead === null) {
            $this->importExportStateService->finish($import->getId(), $message->getContext());

            return null;
        }

        $stateData['nextLineToRead'] = $newNextLineToRead;
        $this->importExportStateService->progressRun($import->getId(), $newNextLineToRead, $message->getContext());

        return new ImportExportSchedulerMessage(
            $message->getImportExportId(),
            ImportExportSchedulerMessage::STATE_EXECUTE_IMPORT,
            $stateData,
            $message->getContext()
        );
    }

    public function handleExecuteExportMessage(ImportExportSchedulerMessage $message): ?ImportExportSchedulerMessage
    {
        /** @var ImportExportEntity $export */
        $export = $this->entityManager->findByPrimaryKey(
            ImportExportDefinition::class,
            $message->getImportExportId(),
            $message->getContext()
        );

        $stateData = $message->getStateData();
        if (!isset($stateData['nextElementToWrite'])) {
            $stateData['nextElementToWrite'] = 0;
            $this->importExportStateService->startRun(
                $message->getImportExportId(),
                $export->getConfig()['totalCount'],
                $message->getContext()
            );
        }

        $exporter = $this->exporterRegistry->getExporterByTechnicalName($export->getProfileTechnicalName());
        $newNextElementToWrite = $exporter->exportChunk($export->getId(), $stateData['nextElementToWrite'], $message->getContext());
        if ($newNextElementToWrite === null) {
            return new ImportExportSchedulerMessage(
                $message->getImportExportId(),
                ImportExportSchedulerMessage::STATE_WRITE_DATABASE_TO_CSV,
                [],
                $message->getContext()
            );
        }

        $stateData['nextElementToWrite'] = $newNextElementToWrite;
        $this->importExportStateService->progressRun($export->getId(), $newNextElementToWrite, $message->getContext());

        return new ImportExportSchedulerMessage(
            $message->getImportExportId(),
            ImportExportSchedulerMessage::STATE_EXECUTE_EXPORT,
            $stateData,
            $message->getContext()
        );
    }

    public function handleWriteDatabaseToCsvMessage(ImportExportSchedulerMessage $message): ?ImportExportSchedulerMessage
    {
        $stateData = $message->getStateData();
        if (!isset($stateData['offset'])) {
            $stateData['offset'] = [
                'nextElementToWrite' => 0,
            ];
            $rowCount = $this->getImportExportRowCount($message->getImportExportId());
            $this->importExportStateService->writeFile($message->getImportExportId(), $rowCount, $message->getContext());
        }
        $newOffset = $this->databaseToCsvWriter->writeChunk(
            $message->getImportExportId(),
            $stateData['offset'],
            $message->getContext()
        );
        if ($newOffset === null) {
            $this->importExportStateService->finish($message->getImportExportId(), $message->getContext());

            return null;
        }
        $stateData['offset'] = $newOffset;

        return new ImportExportSchedulerMessage(
            $message->getImportExportId(),
            ImportExportSchedulerMessage::STATE_WRITE_DATABASE_TO_CSV,
            $stateData,
            $message->getContext()
        );
    }

    private function getImportExportRowCount(string $importExportId): int
    {
        return (int) $this->db->fetchOne(
            'SELECT COUNT(`id`)
            FROM `pickware_erp_import_export_element`
            WHERE `import_export_id` = :importExportId',
            [
                'importExportId' => hex2bin($importExportId),
            ]
        );
    }

    private function skipOverPossibleBom($stream): void
    {
        $maybeBom = fread($stream, 3);

        if ($maybeBom !== self::BOM_UTF8) {
            fseek($stream, 0, SEEK_SET);
        }
    }
}
