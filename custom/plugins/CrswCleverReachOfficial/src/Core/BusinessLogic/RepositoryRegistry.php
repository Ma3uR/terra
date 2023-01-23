<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Interfaces\ScheduleRepositoryInterface;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\Models\Schedule;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry as InfrastructureRepositoryRegistry;

/**
 * Class RepositoryRegistry
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic
 */
class RepositoryRegistry extends InfrastructureRepositoryRegistry
{
    /**
     * Returns schedule repository
     *
     * @return ScheduleRepositoryInterface
     *
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public static function getScheduleRepository()
    {
        /** @var ScheduleRepositoryInterface $repository */
        $repository = static::getRepository(Schedule::getClassName());
        if (!($repository instanceof ScheduleRepositoryInterface)) {
            throw new RepositoryClassException('Instance class is not implementation of ScheduleRepositoryInterface');
        }

        return $repository;
    }
}
