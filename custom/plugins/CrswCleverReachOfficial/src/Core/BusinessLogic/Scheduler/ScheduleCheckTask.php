<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Exceptions\ScheduleSaveException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces\ScheduleRepositoryInterface;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;

/**
 * Class ScheduleCheckTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler
 */
class ScheduleCheckTask extends Task
{
    /**
     * @var ScheduleRepositoryInterface
     */
    private $repository;

    /**
     * Runs task logic.
     *
     * @throws RepositoryNotRegisteredException
     * @throws QueryFilterInvalidParamException
     * @throws ScheduleSaveException
     * @throws RepositoryClassException
     */
    public function execute()
    {
        /** @var Queue $queueService */
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);

        /** @var Schedule $schedule */
        foreach ($this->getSchedules() as $schedule) {
            $lastTask = $queueService->findLatestByType($schedule->getTaskType(), $schedule->getContext());
            if ($lastTask && in_array($lastTask->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS))) {
                continue;
            }

            try {
                if ($schedule->isRecurring()) {
                    $lastUpdateTimestamp = $schedule->getLastUpdateTimestamp();
                    $schedule->setNextSchedule();
                    $schedule->setLastUpdateTimestamp($this->now()->getTimestamp());
                    $this->getScheduleRepository()->saveWithCondition(
                        $schedule,
                        array('lastUpdateTimestamp' => $lastUpdateTimestamp)
                    );
                } else {
                    $this->getScheduleRepository()->delete($schedule);
                }

                $task = $schedule->getTask();
                $queueService->enqueue($schedule->getQueueName(), $task, $schedule->getContext());
            } catch (QueueStorageUnavailableException $ex) {
                Logger::logDebug(
                    json_encode(array(
                        'Message' => 'Failed to enqueue task ' . $task->getType(),
                        'ExceptionMessage' => $ex->getMessage(),
                        'ExceptionTrace' => $ex->getTraceAsString()
                    ))
                );
            }
        }

        $this->reportProgress(100);
    }

    /**
     * Returns an array of Schedules that are due for execution
     *
     * @return Schedule[]
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     * @throws RepositoryClassException
     */
    private function getSchedules()
    {
        $queryFilter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $queryFilter->where('nextSchedule', '<=', $this->now());
        $queryFilter->orderBy('nextSchedule', QueryFilter::ORDER_ASC);
        $queryFilter->setLimit(1000);

        return $this->getScheduleRepository()->select($queryFilter);
    }

    /**
     * Returns current date and time
     *
     * @return \DateTime
     */
    protected function now()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        return $timeProvider->getCurrentLocalTime();
    }

    /**
     * Returns repository instance
     *
     * @return ScheduleRepositoryInterface
     * @throws RepositoryNotRegisteredException
     * @throws RepositoryClassException
     */
    private function getScheduleRepository()
    {
        if ($this->repository === null) {
            /** @var ScheduleRepositoryInterface $repository */
            $this->repository = RepositoryRegistry::getScheduleRepository();
        }

        return $this->repository;
    }
}
