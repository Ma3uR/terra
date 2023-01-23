<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class ScheduleTickHandler
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler
 */
class ScheduleTickHandler
{
    /**
     * Queues ScheduleCheckTask.
     */
    public function handle()
    {
        /** @var Queue $queueService */
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $task = $queueService->findLatestByType('ScheduleCheckTask');
        $threshold = $configService->getSchedulerTimeThreshold();

        if ($task && in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS))) {
            return;
        }

        if ($task === null || $task->getQueueTimestamp() + $threshold < time()) {
            $task = new ScheduleCheckTask();
            try {
                $queueService->enqueue($configService->getSchedulerQueueName(), $task);
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
    }
}
