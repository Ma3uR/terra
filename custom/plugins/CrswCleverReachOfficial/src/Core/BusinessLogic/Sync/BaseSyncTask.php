<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class BaseSyncTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 */
abstract class BaseSyncTask extends Task
{
    /**
     * Instance of proxy class.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * Gets proxy class instance.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy
     *   Instance of proxy class.
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}
