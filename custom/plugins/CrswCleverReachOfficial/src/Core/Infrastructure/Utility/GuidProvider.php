<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Utility;

/**
 * Class GuidProvider
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Utility
 */
class GuidProvider
{
    const CLASS_NAME = __CLASS__;

    /**
     * Unique identifier generator.
     *
     * @return string
     *   Generated guid.
     */
    public function generateGuid()
    {
        return uniqid(getmypid() . '_', true);
    }
}
