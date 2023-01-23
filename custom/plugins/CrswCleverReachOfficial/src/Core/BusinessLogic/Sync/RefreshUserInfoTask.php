<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\AuthInfo;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Serializer;

/**
 * Class RefreshUserInfoTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 */
class RefreshUserInfoTask extends BaseSyncTask
{
    /**
     * Authentication info.
     *
     * @var AuthInfo
     */
    private $authInfo;

    /**
     * RefreshUserInfoTask constructor.
     *
     * @param AuthInfo $authInfo Authentication data.
     */
    public function __construct(AuthInfo $authInfo)
    {
        $this->authInfo = $authInfo;
    }

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        return new static(new AuthInfo(
            $array['accessToken'],
            $array['accessTokenDuration'],
            $array['refreshToken']
        ));
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->authInfo);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->authInfo = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
          'accessToken' => $this->authInfo->getAccessToken(),
          'accessTokenDuration' => $this->authInfo->getAccessTokenDuration(),
          'refreshToken' => $this->authInfo->getRefreshToken(),
        );
    }

    /**
     * Runs task execution.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $this->reportProgress(5);

        $configService = $this->getConfigService();
        $userInfo = $this->getProxy()->getUserInfo($this->authInfo->getAccessToken());
        if (!empty($userInfo)) {
            $configService->setAuthInfo($this->authInfo);
            $configService->setUserInfo($userInfo);
        }

        $this->reportProgress(100);
    }
}
