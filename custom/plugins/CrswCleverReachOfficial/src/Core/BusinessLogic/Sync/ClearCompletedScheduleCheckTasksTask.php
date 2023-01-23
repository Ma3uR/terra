<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\ScheduleCheckTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class ClearCompletedScheduleCheckTasksTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 */
class ClearCompletedScheduleCheckTasksTask extends Task
{
    const INITIAL_PROGRESS_PERCENT = 10;

    const HOURS = 1;

    /**
     * Removes all completed ScheduleCheckTask items which are older than 1 hour
     */
    public function execute()
    {
        $this->reportProgress(self::INITIAL_PROGRESS_PERCENT);
        /** @var TaskQueueStorage $taskQueueStorage */
        $taskQueueStorage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
        $taskQueueStorage->deleteCompletedQueueItems(ScheduleCheckTask::getClassName(), $this->getFinishedTimestamp());

        $this->reportProgress(100);
    }

    /**
     * Returns queue item finish timestamp.
     *
     * @return int
     */
    private function getFinishedTimestamp()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        $currentTimestamp = $timeProvider->getCurrentLocalTime()->getTimestamp();

        return $currentTimestamp - self::HOURS * 3600;
    }
}
