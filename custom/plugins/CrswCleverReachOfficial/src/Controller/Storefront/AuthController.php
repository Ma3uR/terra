<?php

namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\AuthInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy\AuthProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\RefreshUserInfoTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BadAuthInfoException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Queue;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class AuthController extends AbstractController
{
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var Queue
     */
    private $queueService;
    /**
     * @var AuthProxy
     */
    private $authProxy;
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * AuthController constructor.
     *
     * @param Configuration $configService
     * @param Queue $queueService
     * @param AuthProxy $authProxy
     * @param ProxyInterface $proxy
     * @param UrlGeneratorInterface $urlGenerator
     * @param Initializer $initializer
     */
    public function __construct(
        Configuration $configService,
        Queue $queueService,
        AuthProxy $authProxy,
        ProxyInterface $proxy,
        UrlGeneratorInterface $urlGenerator,
        Initializer $initializer
    ) {
        $this->configService = $configService;
        $this->queueService = $queueService;
        $this->authProxy = $authProxy;
        $this->proxy = $proxy;
        $this->urlGenerator = $urlGenerator;
        $initializer->registerServices();
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="cleverreach/auth", name="cleverreach.auth", methods={"GET"})
     *
     * @param Request $request
     * @param Context $context
     *
     * @return Response|JsonResponse
     */
    public function callback(Request $request, Context $context)
    {
        $this->configService->setShopwareContext($context);

        try {
            $authInfo = $this->getAuthInfo($request, 'auth');
            $this->queueService->enqueue(
                $this->configService->getQueueName(),
                new RefreshUserInfoTask($authInfo),
                QueueItem::PRIORITY_HIGH
            );
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => $exception->getCode(), 'message' => $exception->getMessage()]);
        }

        $content = $this->getResponse();

        return new Response($content);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="cleverreach/refresh", name="cleverreach.refresh", methods={"GET"})
     *
     * @param Request $request
     * @param Context $context
     *
     * @return Response|JsonResponse
     */
    public function refreshToken(Request $request, Context $context)
    {
        $this->configService->setShopwareContext($context);

        try {
            $authInfo = $this->getAuthInfo($request, 'refresh');

            try {
                $userInfo = $this->proxy->getUserInfo($authInfo->getAccessToken());
            } catch (\Exception $e) {
                $userInfo = [];
            }

            $localInfo = $this->configService->getUserInfo();

            if (isset( $userInfo['id']) && $userInfo['id'] === $localInfo['id']) {
                $this->configService->setAuthInfo($authInfo);
            }
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => $exception->getCode(), 'message' => $exception->getMessage()]);
        }

        $content = $this->getResponse();

        return new Response($content);
    }

    /**
     * @param Request $request
     *
     * @param string $type
     *
     * @return AuthInfo
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    private function getAuthInfo(Request $request, string $type): AuthInfo
    {
        $code = $request->get('code');
        if (empty($code)) {
            throw new HttpRequestException('Wrong parameters. Code not set.', 400);
        }

        try {
            $routeName = "cleverreach.{$type}";
            $authInfo = $this->authProxy->getAuthInfo($code, $this->urlGenerator->generate($routeName, [], UrlGeneratorInterface::ABSOLUTE_URL));
        } catch (BadAuthInfoException $e) {
            throw new HttpRequestException($e->getMessage() ?: 'Unsuccessful connection.');
        }

        return $authInfo;
    }

    /**
     * Returns response content
     *
     * @return string
     */
    private function getResponse(): string
    {
        $checkStatusUrl = $this->urlGenerator->generate(
            'api.cleverreach.status',
            ['version' => PlatformRequest::API_VERSION],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $content = $this->render('/administration/iframe/close.html.twig', ['checkStatusUrl' => $checkStatusUrl])->getContent();
        $content .= $this->render('/administration/iframe/cleverreach.ajax.html.script.twig')->getContent();
        $content .= $this->render('/administration/iframe/cleverreach.authorization.script.html.twig')->getContent();
        $content .= $this->render('/administration/iframe/cleverreach.auth-iframe.script.html.twig', ['checkStatusUrl' => $checkStatusUrl])->getContent();
        $content .= $this->render('/administration/iframe/cleverreach.spinner.style.html.twig')->getContent();

        return (string)$content;
    }
}
