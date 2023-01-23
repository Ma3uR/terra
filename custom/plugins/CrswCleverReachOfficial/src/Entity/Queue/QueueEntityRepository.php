<?php

namespace Crsw\CleverReachOfficial\Entity\Queue;

use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Migration\Migration1568040595CreateQueuesTable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class QueueEntityRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Queue
 */
class QueueEntityRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * QueueEntityRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     * @param Connection $connection
     */
    public function __construct(EntityRepositoryInterface $baseRepository, Connection $connection)
    {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
    }

    /**
     * @param string $type
     * @param int|null $finishTimestamp
     *
     * @return int
     * @throws DBALException
     */
    public function deleteCompletedItems(string $type, int $finishTimestamp = null): int
    {
        $whereClause = $this->buildBaseWhereClause($finishTimestamp);
        $whereClause .= " AND type = '{$type}'";

        $sql = $this->buildDeleteQuery($whereClause);

        return $this->connection->executeUpdate($sql);
    }

    /**
     * @param array $excludeTypes
     * @param int|null $timestamp
     * @param int $limit
     *
     * @return int
     * @throws DBALException
     */
    public function deleteBy(array $excludeTypes = [], int $timestamp = null, int $limit = 1000): int
    {
        $whereClause = $this->buildBaseWhereClause($timestamp);
        if (!empty($excludeTypes)) {
            $excludedTypesArray = implode("','", $excludeTypes);
            $whereClause .= " AND type NOT IN ('{$excludedTypesArray}') ";
        }

        $sql = $this->buildDeleteQuery($whereClause, $limit);

        return $this->connection->executeUpdate($sql);
    }

    /**
     * @param int|null $finishTimestamp
     *
     * @return string
     */
    private function buildBaseWhereClause(?int $finishTimestamp): string
    {
        $completedStatus = QueueItem::COMPLETED;
        $whereClause = "WHERE status = '{$completedStatus}' ";

        if ($finishTimestamp) {
            $whereClause .= "finishTimestamp < {$finishTimestamp} ";
        }

        return $whereClause;
    }

    /**
     * @param string $whereClause
     * @param int|null $limit
     *
     * @return string
     */
    private function buildDeleteQuery(string $whereClause, int $limit = null): string
    {
        $tableName = Migration1568040595CreateQueuesTable::QUEUES_TABLE;
        $sql =  "DELETE FROM {$tableName} {$whereClause} ";
        if ($limit) {
            $sql .= "LIMIT {$limit}";
        }

        return $sql;
    }

    /**
     * @param string|null $id
     * @param array $data
     * @param array $additionalConditions
     * @param Context $context
     *
     * @return string|null
     * @throws InconsistentCriteriaIdsException
     */
    public function save(?string $id, array $data, array $additionalConditions, Context $context)
    {
        /** @var QueueEntity $queueEntity */
        if ($id) {
            $queueEntity = $this->baseRepository->search($this->buildCriteria($id, $additionalConditions), $context)->first();
            if ($queueEntity) {
                $updateData = array_merge(['id' => $queueEntity->getId()], $data);
                $this->baseRepository->update([$updateData], $context);

                return $queueEntity->getId();
            }
        }

        $event = $this->baseRepository->create([$data], $context)->getEventByEntityName(QueueEntity::class);

        return $event ? $event->getIds()[0] : null;
    }

    /**
     * Returns QueueEntity by its id
     *
     * @param string $id
     * @param Context $context
     *
     * @return QueueEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getById(string $id, Context $context): ?QueueEntity
    {
        return $this->baseRepository->search(new Criteria([$id]), $context)->first();
    }

    /**
     * @param string $type
     * @param Context $context
     *
     * @return QueueEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function findLatestByType(string $type, Context $context): ?QueueEntity
    {
        $filter = ['type' => $type];
        $sortBy = ['queueTimestamp' => FieldSorting::DESCENDING];

        return $this->baseRepository->search($this->buildCriteria(null, $filter, $sortBy), $context)->first();
    }

    /**
     * @param Context $context
     * @param int $limit
     * @param string|null $additionalQueueForSkip
     *
     * @return mixed[]
     * @throws DBALException
     * @throws InconsistentCriteriaIdsException
     */
    public function findOldestQueuedEntities(Context $context, int $limit = 10, string $additionalQueueForSkip = null)
    {
        $queuesForSkip = $this->getRunningQueues($context);
        if ($additionalQueueForSkip) {
            $queuesForSkip[] = $additionalQueueForSkip;
        }

        $queuedStatus = QueueItem::QUEUED;
        $tableName = Migration1568040595CreateQueuesTable::QUEUES_TABLE;

        $result = [];
        $priorities = [
            QueueItem::PRIORITY_HIGH,
            QueueItem::PRIORITY_MEDIUM,
            QueueItem::PRIORITY_LOW,
        ];

        foreach ($priorities as $priority) {
            $additionalWhere = '';
            if (!empty($queuesForSkip)) {
                $queueArray = implode("','", $queuesForSkip);
                $additionalWhere = "AND queueName NOT IN ('{$queueArray}') ";
            }

            $query = "SELECT *
                  FROM {$tableName}
                  WHERE internalId IN (
                    SELECT MIN(internalId) as internalId
                    FROM  {$tableName}
                    WHERE
                        priority = {$priority} AND
                        status = '{$queuedStatus}'
                        {$additionalWhere}
                    GROUP BY queueName
                )
                ORDER BY internalId
                LIMIT {$limit};
            ";

            $priorityItems = $this->connection->executeQuery($query)->fetchAll();

            $result = array_merge($result, $priorityItems);
            $limit -= count($priorityItems);
            if ($limit <= 0) {
                break;
            }

            $queuesForSkip = array_merge(
                $queuesForSkip,
                array_map(
                    function ($item) {
                        return $item['queueName'];
                    },
                    $priorityItems
                )
            );
        }

        return $result;
    }

    /**
     * @param Context $context
     * @param array $filterBy
     * @param array $sortBy
     * @param int $start
     * @param int $limit
     *
     * @return EntityCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function findAll(
        Context $context,
        array $filterBy = [],
        array $sortBy = [],
        $start = 0,
        $limit = 10
    ): EntityCollection
    {
        return $this->baseRepository
            ->search($this->buildCriteria(null, $filterBy, $sortBy, $limit, $start), $context)
            ->getEntities();
    }

    /**
     * Returns queue names which has queue items 'in_progress' status
     *
     * @param Context $context
     *
     * @return array
     * @throws InconsistentCriteriaIdsException
     */
    private function getRunningQueues(Context $context): array
    {
        $runningQueues = [];
        $runningItems = $this->findAll($context, ['status' => QueueItem::IN_PROGRESS]);
        /** @var QueueEntity $runningItem */
        foreach ($runningItems as $runningItem) {
            if (!in_array($runningItem->get('status'), $runningQueues, true)) {
                $runningQueues[] = $runningItem->get('queueName');
            }
        }

        return $runningQueues;
    }

    /**
     * @param string|null $id
     * @param array $additionalConditions
     *
     * @param array $sorting
     * @param int $limit
     * @param int $offset
     *
     * @return Criteria
     * @throws InconsistentCriteriaIdsException
     */
    private function buildCriteria(
        ?string $id,
        array $additionalConditions,
        array $sorting = [],
        int $limit = 50,
        int $offset = 0
    ): Criteria
    {
        $ids = $id ? [$id] : [];
        $criteria = new Criteria($ids);
        foreach ($additionalConditions as $key => $value) {
            $criteria->addFilter(new EqualsFilter($key, $value));
        }

        foreach ($sorting as $field => $direction) {
            $criteria->addSorting(new FieldSorting($field, $direction));
        }

        $criteria->setLimit($limit);
        $criteria->setOffset($offset);

        return $criteria;
    }
}
