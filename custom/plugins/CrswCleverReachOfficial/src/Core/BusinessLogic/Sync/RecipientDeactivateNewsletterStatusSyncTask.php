<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

/**
 * Class RecipientDeactivateNewsletterStatusSyncTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 * @deprecated
 */
class RecipientDeactivateNewsletterStatusSyncTask extends RecipientStatusUpdateSyncTask
{
    /**
     * Runs task execution.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $this->getProxy()->updateNewsletterStatus($this->recipientEmails);
        $this->reportProgress(100);
    }
}
