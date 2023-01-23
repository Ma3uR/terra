<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RefreshController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class RefreshController extends AbstractController
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * RefreshController constructor.
     *
     * @param Initializer $initializer
     * @param Configuration $config
     *
     */
    public function __construct(Initializer $initializer, Configuration $config)
    {
        $initializer->registerServices();
        $this->config = $config;
    }

    /**
     * Returns refresh page config
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/refresh", name="api.cleverreach.refresh", methods={"GET", "POST"})
     */
    public function getRefreshConfig(): JsonApiResponse
    {
        $userInfo = $this->config->getUserInfo();

        return new JsonApiResponse($userInfo);
    }
}
