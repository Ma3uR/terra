<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

class ExchangeAccessTokenTask extends BaseSyncTask
{
    /**
     * Refreshes CleverReach tokens.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function execute()
    {
        $this->reportProgress(5);

        $configService = $this->getConfigService();
        $configService->setAccessTokenExpirationTime(10000);

        $result = $this->getProxy()->exchangeToken();

        $configService->setAuthInfo($result);

        $this->reportProgress(100);
    }
}