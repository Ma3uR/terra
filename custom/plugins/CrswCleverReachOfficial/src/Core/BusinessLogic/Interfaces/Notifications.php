<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Notification;

/**
 * Interface Notifications
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces
 */
interface Notifications
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates a new notification in system integration.
     *
     * @param Notification $notification Notification object that contains info such as
     *   identifier, name, date, description, url.
     *
     * @return boolean
     */
    public function push(Notification $notification);
}
