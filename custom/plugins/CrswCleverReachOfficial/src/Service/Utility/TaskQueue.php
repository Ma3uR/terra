<?php

namespace Crsw\CleverReachOfficial\Service\Utility;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\InitialSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\TaskRunnerWakeup;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Serializer;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;

/**
 * Class TaskQueue
 *
 * @package Crsw\CleverReachOfficial\Service\Utility
 */
class TaskQueue
{
    /**
     * Enqueues a task to the queue.
     *
     * @param Task $task
     * @param bool $throwException
     *
     * @throws QueueStorageUnavailableException
     */
    public static function enqueue(Task $task, $throwException = false): void
    {
        try {
            /** @var ConfigService $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            /** @var Queue $queueService */
            $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
            $initialSyncTask = $queueService->findLatestByType(InitialSyncTask::getClassName());
            if ($initialSyncTask) {
                $queueService->enqueue($configService->getQueueName(), $task);
            }
        } catch (QueueStorageUnavailableException $ex) {
            Logger::logDebug(
                json_encode(
                    array(
                        'Message' => 'Failed to enqueue task ' . $task->getType(),
                        'ExceptionMessage' => $ex->getMessage(),
                        'ExceptionTrace' => $ex->getTraceAsString(),
                        'TaskData' => Serializer::serialize($task),
                    )
                ),
                'Integration'
            );

            if ($throwException) {
                throw $ex;
            }
        }
    }

    /**
     * Calls the wakeup on task runner.
     */
    public static function wakeup(): void
    {
        /** @var TaskRunnerWakeup $wakeupService */
        $wakeupService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        $wakeupService->wakeup();
    }
}
