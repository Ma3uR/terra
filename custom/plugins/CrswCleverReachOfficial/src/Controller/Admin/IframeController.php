<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy\AuthProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Entity\User\UserRepository;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\User\UserEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class IframeController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class IframeController extends AbstractController
{
    /**
     * @var AuthProxy
     */
    private $proxy;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * IframeController constructor.
     *
     * @param AuthProxy $proxy
     * @param UrlGeneratorInterface $urlGenerator
     * @param Initializer $initializer
     * @param UserRepository $userRepository
     * @param Configuration $configService
     */
    public function __construct(
        AuthProxy $proxy,
        UrlGeneratorInterface $urlGenerator,
        Initializer $initializer,
        UserRepository $userRepository,
        Configuration $configService
    ) {
        $initializer->registerServices();
        $this->proxy = $proxy;
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
        $this->configService = $configService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/iframe/url/{type}", name="api.cleverreach.iframe.url", methods={"GET", "POST"})
     * @param string $type
     * @param Context $context
     *
     * @return JsonApiResponse
     * @throws InconsistentCriteriaIdsException
     */
    public function getAuthUrl(string $type, Context $context): JsonApiResponse
    {
        $this->configService->setShopwareContext($context);

        $isRefresh = $type === 'refresh';
        $routeName = $isRefresh ? 'cleverreach.refresh' : 'cleverreach.auth';
        $user = $this->getAdminUser($context);
        $lang = $this->getUserLanguage($user);
        $redirectUrl = $this->urlGenerator->generate($routeName, [], UrlGeneratorInterface::ABSOLUTE_URL);
        $authUrl = $this->proxy->getAuthUrl($redirectUrl, $this->getRegistrationData($user), ['lang' => $lang]);
        $authUrl .= $isRefresh ? '#login' : '';

        return new JsonApiResponse(['authUrl' => $authUrl]);
    }

    /**
     * @param UserEntity|null $user
     *
     * @return string
     */
    private function getRegistrationData(?UserEntity $user): string
    {
        $registrationData = [];
        if ($user) {
            $registrationData = [
                'email' => $user->getEmail(),
                'company' => '',
                'firstname' => $user->getFirstName() ?? $user->getLastName(),
                'lastname' => $user->getLastName(),
                'gender' => '',
                'street' => '',
                'zip' => '',
                'city' => '',
                'country' => '',
                'phone' => ''
            ];
        }

        return base64_encode(json_encode($registrationData));
    }

    /**
     * Get user entity
     *
     * @param Context $context
     *
     * @return UserEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    private function getAdminUser(Context $context): ?UserEntity
    {
        /** @var AdminApiSource $source */
        $source = $context->getSource();
        if (!($source instanceof AdminApiSource)) {
            return null;
        }

        $userId = $source->getUserId();

        return $this->userRepository->getUserBy($userId, $context);
    }

    /**
     * @param UserEntity|null $userEntity
     *
     * @return string
     */
    private function getUserLanguage(?UserEntity $userEntity): string
    {
        if ($userEntity && $userEntity->getLocale()) {
            $code = $userEntity->getLocale()->getCode();
            $this->configService->setLanguage($code);

            return $this->formatLanguageCodeForCleverReach($code);
        }

        return 'en';
    }

    /**
     * @param string $shopwareCode
     *
     * @return string
     */
    private function formatLanguageCodeForCleverReach(string $shopwareCode): string
    {
        $codeArray = explode('-', $shopwareCode);

        return array_key_exists(0, $codeArray) ? $codeArray[0] : 'en';
    }
}
