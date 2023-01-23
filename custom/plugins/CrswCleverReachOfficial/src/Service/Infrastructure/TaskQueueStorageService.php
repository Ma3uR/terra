<?php

namespace Crsw\CleverReachOfficial\Service\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Entity\Queue\QueueEntity;
use Crsw\CleverReachOfficial\Entity\Queue\QueueEntityRepository;

/**
 * Class TaskQueueStorageService
 *
 * @package Crsw\CleverReachOfficial\Service\Infrastructure
 */
class TaskQueueStorageService implements TaskQueueStorage
{
    /**
     * @var QueueEntityRepository
     */
    private $queueEntityRepository;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * TaskQueueStorageService constructor.
     *
     * @param QueueEntityRepository $queueEntityRepository
     * @param Configuration $configService
     */
    public function __construct(QueueEntityRepository $queueEntityRepository, Configuration $configService)
    {
        $this->queueEntityRepository = $queueEntityRepository;
        $this->configService = $configService;
    }

    /**
     * @param QueueItem $queueItem
     * @param array $additionalWhere
     *
     * @return int|void
     * @throws QueueItemSaveException
     */
    public function save(QueueItem $queueItem, array $additionalWhere = [])
    {
        try {
            return $this->queueEntityRepository->save(
                $queueItem->getId(),
                $this->toArray($queueItem),
                $additionalWhere,
                $this->configService->getShopwareContext()
            );
        } catch (\Exception $exception) {
            throw new QueueItemSaveException('Failed to save queue item into database');
        }
    }

    /**
     * Finds queue item by ID.
     *
     * @param int $id ID of a queue item to find.
     * @return QueueItem|null
     *   Found queue item or null when queue item does not exist.
     */
    public function find($id): ?QueueItem
    {
        try {
            $queueEntity = $this->queueEntityRepository->getById($id, $this->configService->getShopwareContext());

            return $queueEntity ? $this->fromDatabaseEntity($queueEntity) : null;
        } catch (\Exception $exception) {
            Logger::logError("Failed to fetch queue item by id from database: {$exception->getMessage()}");

            return null;
        }
    }

    /**
     * Finds latest queue item by type across all queues
     *
     * @param string $type Type of a queue item to find
     * @param string $context Task context restriction if provided search will be limited
     *      to given task context. Leave empty for search across all task contexts
     *
     * @return QueueItem|null
     */
    public function findLatestByType($type, $context = ''): ?QueueItem
    {
        try {
            $queueEntity = $this->queueEntityRepository->findLatestByType($type, $this->configService->getShopwareContext());

            return $queueEntity ? $this->fromDatabaseEntity($queueEntity) : null;
        } catch (\Exception $exception) {
            Logger::logError("Failed to fetch latest queue item by type from database: {$exception->getMessage()}");

            return null;
        }
    }

    /**
     * Finds list of earliest queued queue items per queue.
     *
     * Following list of criteria for searching must be satisfied:
     *  - Queue must be without already running queue items
     *  - For one queue only one (oldest queued) item should be returned
     *
     * @param int $limit Result set limit. By default 10 earliest queue items will be returned.
     *
     * @return QueueItem[]
     *   Found queue item list.
     */
    public function findOldestQueuedItems($limit = 10): array
    {
        try {
            $items = [];
            $additionalQueuesForSkip = !$this->configService->isUserOnline() ?
                $this->configService->getQueueName() : null;
            $rawItems = $this->queueEntityRepository->findOldestQueuedEntities(
                $this->configService->getShopwareContext(),
                $limit,
                $additionalQueuesForSkip
            );

            foreach ($rawItems as $rawItem) {
                $items[] = $this->fromArray($rawItem);
            }

            return  $items;
        } catch (\Exception $exception) {
            Logger::logError("Failed to find queueItems in database. Search parameters: {$exception->getMessage()}", 'Integration');

            return [];
        }
    }

    /**
     * Finds all queue items from all queues
     *
     * @param array $filterBy List of simple search filters, where key is queue item property and
     *      value is condition value for that property. Leave empty for unfiltered result.
     * @param array $sortBy List of sorting options where key is queue item property and value
     *      sort direction ("ASC" or "DESC"). Leave empty for default sorting.
     * @param int $start From which record index result set should start.
     * @param int $limit Max number of records that should be returned (default is 10).
     *
     * @return QueueItem[]
     *   Found queue item list
     */
    public function findAll(array $filterBy = [], array $sortBy = [], $start = 0, $limit = 10): array
    {
        try {
            $items = [];
            $collection = $this->queueEntityRepository->findAll(
                $this->configService->getShopwareContext(),
                $filterBy,
                $sortBy,
                $start,
                $limit
            );
            /** @var QueueEntity $queueEntity */
            foreach ($collection as $queueEntity) {
                $items[] = $this->fromDatabaseEntity($queueEntity);
            }

            return  $items;
        } catch (\Exception $exception) {
            Logger::logError("Failed to find queueItems in database. Search parameters: {$exception->getMessage()}", 'Integration');

            return [];
        }
    }

    /**
     * Removes completed queue items specified by type and finished time
     *
     * @param string $type Type of queue item do delete
     * @param int|null $timestamp Finish timestamp of queue items. All items
     *      which are finished before provided timestamp should be deleted.
     *      If not provided, remove all completed queue items with specified type.
     */
    public function deleteCompletedQueueItems($type, $timestamp = null): void
    {
        try {
            $this->queueEntityRepository->deleteCompletedItems($type, $timestamp);
        } catch (\Exception $exception) {
            Logger::logError("Failed to delete completed queue items: {$exception->getMessage()}", 'Integration');
        }
    }

    /**
     * Removes completed queue items that are not in excluded types and older than finished time
     *
     * @param array $excludeTypes Queue item types that should be ignored. Leave empty to delete all queue item types
     * @param int|null $timestamp Finish timestamp condition. Only items that have finished before provided timestamp
     *      will be removed. Leave default null value to delete all regardless of finish timestamp
     * @param int $limit Delete record limit. Max number of records to delete
     *
     * @return int Count of deleted rows
     */
    public function deleteBy(array $excludeTypes = [], $timestamp = null, $limit = 1000): int
    {
        $deletedRaws = 0;
        try {
            $deletedRaws = $this->queueEntityRepository->deleteBy($excludeTypes, $timestamp, $limit);
        } catch (\Exception $exception) {
            Logger::logError("Failed to delete completed queue items: {$exception->getMessage()}", 'Integration');
        }

        return $deletedRaws;
    }

    /**
     * @param QueueItem $queueItem
     *
     * @return array
     * @throws QueueItemDeserializationException
     */
    public function toArray(QueueItem $queueItem): array
    {
        return [
            'priority' => $queueItem->getPriority(),
            'status' =>  $queueItem->getStatus(),
            'type' => $queueItem->getTaskType(),
            'queueName' => $queueItem->getQueueName(),
            'progress' => $queueItem->getProgressBasePoints(),
            'lastExecutionProgress' => $queueItem->getLastExecutionProgressBasePoints(),
            'retries' => $queueItem->getRetries(),
            'failureDescription' => $queueItem->getFailureDescription(),
            'serializedTask' => $queueItem->getSerializedTask(),
            'createTimestamp' => $queueItem->getCreateTimestamp(),
            'queueTimestamp' => $queueItem->getQueueTimestamp(),
            'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
            'startTimestamp' => $queueItem->getStartTimestamp(),
            'finishTimestamp' => $queueItem->getFinishTimestamp(),
            'failTimestamp' => $queueItem->getFailTimestamp(),
        ];
    }

    /**
     * Creates QueueItem entity from entity model
     *
     * @param QueueEntity $queueEntity
     *
     * @return QueueItem
     */
    private function fromDatabaseEntity(QueueEntity $queueEntity): QueueItem
    {
        $queueItem = new QueueItem();
        $queueItem->setId($queueEntity->getId());
        $queueItem->setPriority($queueEntity->get('priority'));
        $queueItem->setStatus($queueEntity->get('status'));
        $queueItem->setQueueName($queueEntity->get('queueName'));
        $queueItem->setProgressBasePoints($queueEntity->get('progress'));
        $queueItem->setLastExecutionProgressBasePoints($queueEntity->get('lastExecutionProgress'));
        $queueItem->setRetries($queueEntity->get('retries'));
        $queueItem->setFailureDescription($queueEntity->get('failureDescription'));
        $queueItem->setSerializedTask($queueEntity->get('serializedTask'));
        $queueItem->setCreateTimestamp($queueEntity->get('createTimestamp'));
        $queueItem->setQueueTimestamp($queueEntity->get('queueTimestamp'));
        $queueItem->setLastUpdateTimestamp($queueEntity->get('lastUpdateTimestamp'));
        $queueItem->setStartTimestamp($queueEntity->get('startTimestamp'));
        $queueItem->setFinishTimestamp($queueEntity->get('finishTimestamp'));
        $queueItem->setFailTimestamp($queueEntity->get('failTimestamp'));

        return $queueItem;
    }

    /**
     * Creates QueueItem entity from entity model
     *
     * @param array $rawItem
     *
     * @return QueueItem
     */
    private function fromArray(array $rawItem): QueueItem
    {
        $queueItem = new QueueItem();
        $queueItem->setId(bin2hex($rawItem['id']));
        $queueItem->setPriority((int)$rawItem['priority']);
        $queueItem->setStatus($rawItem['status']);
        $queueItem->setQueueName($rawItem['queueName']);
        $queueItem->setProgressBasePoints((int)$rawItem['progress']);
        $queueItem->setLastExecutionProgressBasePoints((int)$rawItem['lastExecutionProgress']);
        $queueItem->setRetries((int)$rawItem['retries']);
        $queueItem->setFailureDescription($rawItem['failureDescription']);
        $queueItem->setSerializedTask($rawItem['serializedTask']);
        $queueItem->setCreateTimestamp((int)$rawItem['createTimestamp']);
        $queueItem->setQueueTimestamp((int)$rawItem['queueTimestamp']);
        $queueItem->setLastUpdateTimestamp((int)$rawItem['lastUpdateTimestamp']);
        $queueItem->setStartTimestamp((int)$rawItem['startTimestamp']);
        $queueItem->setFinishTimestamp((int)$rawItem['finishTimestamp']);
        $queueItem->setFailTimestamp((int)$rawItem['failTimestamp']);

        return $queueItem;
    }
}
