<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Exposed;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Serializable;

/**
 * Interface Runnable
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Exposed
 */
interface Runnable extends Serializable
{
    /**
     * Starts runnable run logic.
     */
    public function run();
}
