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

use InvalidArgumentException;
use Pickware\HttpUtils\JsonApi\JsonApiErrors;
use Shopware\Core\Framework\Context;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class ImportExportScheduler
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var float
     */
    private $messageQueueProcessStartTimestamp = null;

    /**
     * @var ImportExportSchedulerMessageHandler
     */
    private $importExportSchedulerMessageHandler;

    /**
     * @var int
     */
    private $executionTimeLimitInSeconds;

    /**
     * @var ImportExportStateService
     */
    private $importExportStateService;

    public function __construct(
        MessageBusInterface $bus,
        ImportExportSchedulerMessageHandler $importExportSchedulerMessageHandler,
        ImportExportStateService $importExportStateService,
        int $executionTimeLimitInSeconds = 15
    ) {
        $this->bus = $bus;
        $this->importExportSchedulerMessageHandler = $importExportSchedulerMessageHandler;
        $this->executionTimeLimitInSeconds = $executionTimeLimitInSeconds;
        $this->importExportStateService = $importExportStateService;
    }

    public function scheduleImport(string $importId, Context $context): void
    {
        $this->schedule(new ImportExportSchedulerMessage(
            $importId,
            ImportExportSchedulerMessage::STATE_CSV_FILE_VALIDATION,
            [],
            $context
        ));
    }

    public function scheduleExport(string $exportId, Context $context): void
    {
        $this->schedule(new ImportExportSchedulerMessage(
            $exportId,
            ImportExportSchedulerMessage::STATE_EXECUTE_EXPORT,
            [],
            $context
        ));
    }

    public function __invoke(ImportExportSchedulerMessage $message)
    {
        $this->messageQueueProcessStartTimestamp = microtime(true);
        try {
            $this->process($message);
        } catch (Throwable $e) {
            // Catch every exception so the message is not retried by the message queue. Failed messages currently
            // cannot be retried because they are not implemented idempotently. Instead we assume that in any
            // case of an exception the import/export has failed hard.
            $errors = new JsonApiErrors([CsvErrorFactory::unknownError($e)]);
            $this->importExportStateService->fail($message->getImportExportId(), $errors, $message->getContext());
        }
    }

    private function process(ImportExportSchedulerMessage $message): void
    {
        switch ($message->getState()) {
            case ImportExportSchedulerMessage::STATE_CSV_FILE_VALIDATION:
                $nextMessage = $this->importExportSchedulerMessageHandler->handleCsvFileValidationMessage($message);
                break;
            case ImportExportSchedulerMessage::STATE_READ_CSV_TO_DATABASE:
                $nextMessage = $this->importExportSchedulerMessageHandler->handleReadCsvToDatabaseMessage($message);
                break;
            case ImportExportSchedulerMessage::STATE_EXECUTE_IMPORT:
                $nextMessage = $this->importExportSchedulerMessageHandler->handleExecuteImportMessage($message);
                break;
            case ImportExportSchedulerMessage::STATE_EXECUTE_EXPORT:
                $nextMessage = $this->importExportSchedulerMessageHandler->handleExecuteExportMessage($message);
                break;
            case ImportExportSchedulerMessage::STATE_WRITE_DATABASE_TO_CSV:
                $nextMessage = $this->importExportSchedulerMessageHandler->handleWriteDatabaseToCsvMessage($message);
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    'Invalid state passed to method %s',
                    __METHOD__
                ));
        }
        if ($nextMessage !== null) {
            $this->schedule($nextMessage);
        }
    }

    private function schedule(ImportExportSchedulerMessage $message): void
    {
        // $this->messageQueueProcessStartTimestamp === null means we are not coming from the message queue. In that
        // case we directly queue the message to the message queue so that the complete import will be handled by the
        // message queue. This makes the controller respond faster.
        // If the method $this->process() was called here immediately, the controller would take longer to respond as
        // the first import step is made in the controller.
        $consumedExecutionTimeInSeconds = microtime(true) - $this->messageQueueProcessStartTimestamp;
        $executionTimeLimitExceeded = $consumedExecutionTimeInSeconds > $this->executionTimeLimitInSeconds;
        if ($this->messageQueueProcessStartTimestamp === null || $executionTimeLimitExceeded) {
            $this->bus->dispatch($message);
        } else {
            $this->process($message);
        }
    }
}
