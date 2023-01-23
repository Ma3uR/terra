<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Notification;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Notifications;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy\SurveyProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Surveys\SurveyType;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class SurveyCheckTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 */
class SurveyCheckTask extends BaseSyncTask
{
    const INITIAL_SYNC_PROGRESS = 10;
    /**
     * @var SurveyProxy
     */
    private $surveyProxy;

    /**
     * Runs task logic
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->reportProgress(self::INITIAL_SYNC_PROGRESS);
        $notification = $this->getNotification();
        if ($notification && ($notification->getId() !== $this->getConfigService()->getLastPollId())) {
            /** @var Notifications $notificationService */
            $notificationService = ServiceRegister::getService(Notifications::CLASS_NAME);
            $notificationService->push($notification);
            $this->getConfigService()->setLastPollId($notification->getId());
        }

        $this->reportProgress(100);
    }

    /**
     * Return notification if there is new poll available on CleverReach
     *
     * @return Notification|null
     *   If there is no new notifications return null, Notification object otherwise
     *
     * @throws \Exception
     */
    private function getNotification()
    {
        $poll = $this->getSurveyProxy()->get(SurveyType::PERIODIC);
        if (empty($poll)) {
            return null;
        }

        $notification = new Notification($poll['meta']['id']);
        $notification->setDescription($this->getConfigService()->getNotificationMessage());
        $notification->setUrl($this->getConfigService()->getPluginUrl());
        $notification->setDate(new \DateTime());

        return $notification;
    }

    /**
     * @return SurveyProxy
     */
    private function getSurveyProxy()
    {
        if ($this->surveyProxy === null) {
            $this->surveyProxy = ServiceRegister::getService(SurveyProxy::CLASS_NAME);
        }

        return $this->surveyProxy;
    }
}
