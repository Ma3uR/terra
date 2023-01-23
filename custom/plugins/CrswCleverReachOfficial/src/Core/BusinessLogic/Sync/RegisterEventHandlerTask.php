<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;

/**
 * Class RegisterEventHandlerTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 */
class RegisterEventHandlerTask extends BaseSyncTask
{
    const RECEIVER_EVENT = 'receiver';
    const FORM_EVENT = 'form';

    /**
     * Runs task logic.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $this->reportProgress(5);
        $configService = $this->getConfigService();
        $eventHookParams = array(
            'url' => $configService->getCrEventHandlerURL(),
            'event' => self::RECEIVER_EVENT,
            'verify' => $configService->getCrEventHandlerVerificationToken(),
        );

        if (stripos($eventHookParams['url'], 'https://') === 0) {
            $callToken = $this->getProxy()->registerEventHandler($eventHookParams);
            $configService->setCrEventHandlerCallToken($callToken);
            if ($configService->isFormSyncEnabled()) {
                $eventHookParams['event'] = self::FORM_EVENT;
                $callToken = $this->getProxy()->registerEventHandler($eventHookParams);
                $configService->setCrFormEventHandlerCallToken($callToken);
            }
        } else {
            Logger::logWarning('Cannot register CleverReach event hook for non-HTTPS domains.');
        }

        $this->reportProgress(100);
    }
}
