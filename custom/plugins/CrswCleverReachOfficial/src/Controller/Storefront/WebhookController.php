<?php

namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Recipient;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\RecipientSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\InvalidConfigurationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;
use Crsw\CleverReachOfficial\Entity\Customer\CustomerRepository;
use Crsw\CleverReachOfficial\Entity\NewsletterRecipient\NewsletterRecipientRepository;
use Crsw\CleverReachOfficial\Entity\SalutationTranslation\SalutationTranslationRepository;
use Crsw\CleverReachOfficial\Service\Business\RecipientService;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\TaskQueue;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class WebhookController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class WebhookController extends AbstractController
{
    public const RECIPIENT_SUBSCRIBED = 'receiver.subscribed';
    public const RECIPIENT_UNSUBSCRIBED = 'receiver.unsubscribed';

    public const ALLOWED_EVENTS = [self::RECIPIENT_SUBSCRIBED, self::RECIPIENT_UNSUBSCRIBED];

    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var NewsletterRecipientRepository
     */
    private $newsletterRecipientRepository;
    /**
     * @var NewsletterSubscribeRoute
     */
    private $newsletterSubscribeRoute;
    /**
     * @var SalutationTranslationRepository
     */
    private $salutationTranslationRepository;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * WebhookController constructor.
     *
     * @param Configuration $configService
     * @param ProxyInterface $proxy
     * @param NewsletterSubscribeRoute $newsletterSubscribeRoute
     * @param CustomerRepository $customerRepository
     * @param SalutationTranslationRepository $salutationTranslationRepository
     * @param NewsletterRecipientRepository $newsletterRecipientRepository
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        Configuration $configService,
        ProxyInterface $proxy,
        NewsletterSubscribeRoute $newsletterSubscribeRoute,
        CustomerRepository $customerRepository,
        SalutationTranslationRepository $salutationTranslationRepository,
        NewsletterRecipientRepository $newsletterRecipientRepository,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->configService = $configService;
        $this->proxy = $proxy;
        $this->customerRepository = $customerRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->salutationTranslationRepository = $salutationTranslationRepository;
        $this->newsletterSubscribeRoute = $newsletterSubscribeRoute;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="cleverreach/webhook", name="cleverreach.webhook", defaults={"csrf_protected"=false}, methods={"GET", "POST"})
     * @param Request $request
     * @param SalesChannelContext $context
     * @param Context $defaultContext
     *
     * @return Response
     */
    public function webhookHandler(Request $request, SalesChannelContext $context, Context $defaultContext): Response
    {
        $this->getConfigService()->setShopwareContext($defaultContext);

        $status = 200;

        try {
            if ($request->getMethod() === 'GET') {
                $responseBody = $this->configService->getCrEventHandlerVerificationToken() . ' ' . $request->get('secret');

                return new Response($responseBody);
            }

            // Handling post request
            $this->checkTokens($request);
            $requestBody = (array)json_decode($request->getContent(), true);

            $this->handleSubscriptionEvent($requestBody, $context);
        } catch (\Exception $exception) {
            Logger::logError("An error occurred during webhook request from CleverReach: {$exception->getMessage()}", 'Integration');
            $status = 400;
        }

        return new Response('', $status);
    }

    /**
     * @param array $requestBody
     *
     * @param SalesChannelContext $context
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidConfigurationException
     * @throws QueueStorageUnavailableException
     * @throws RefreshTokenExpiredException
     * @throws \Shopware\Core\Framework\Validation\Exception\ConstraintViolationException
     */
    private function handleSubscriptionEvent(array $requestBody, SalesChannelContext $context): void
    {
        try {
            $this->validatePayload($requestBody);
        } catch (HttpRequestException $exception) {
            Logger::logError("An error occurred during payload validation: {$exception->getMessage()}", 'Integration');
            return;
        }

        $recipient = $this->proxy->getRecipient($requestBody['payload']['group_id'], $requestBody['payload']['pool_id']);
        $isSubscribed = $requestBody['event'] === self::RECIPIENT_SUBSCRIBED;

        $customer = $this->processCustomerEntity($recipient, $context, $isSubscribed);
        $subscriber = $this->processNewsletterRecipientEntity($recipient, $context, $isSubscribed);

        if ($customer) {
            $idsForSync = [RecipientService::CUSTOMER_PREFIX . $customer->getId()];
        } elseif ($subscriber) {
            $idsForSync = [RecipientService::SUBSCRIBER_PREFIX . $subscriber->getId()];
        }

        if (isset($idsForSync)) {
            TaskQueue::enqueue(new RecipientSyncTask($idsForSync));
        }
    }

    /**
     * @param Recipient $recipient
     * @param bool $isSubscribed
     *
     * @return RequestDataBag
     * @throws InconsistentCriteriaIdsException
     */
    private function createRequestDataBag(Recipient $recipient, bool $isSubscribed): RequestDataBag
    {
        $option = $isSubscribed ? 'subscribe' : 'unsubscribe';
        $baseUrl = $this->urlGenerator->generate('root.fallback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseUrl = rtrim($baseUrl, '/');

        return new RequestDataBag(
            [
                'email' => $recipient->getEmail(),
                'title' => $recipient->getTitle(),
                'firstName' => $recipient->getFirstName(),
                'lastName' => $recipient->getLastName(),
                'zipCode' => $recipient->getZip(),
                'city' => $recipient->getCity(),
                'street' => $recipient->getStreet(),
                'storefrontUrl' => $baseUrl,
                'option' => $option,
                'salutationId' => $this->getSalutationId((string)$recipient->getSalutation()),
            ]
        );
    }

    /**
     * @param string $salutation
     *
     * @return string|null
     * @throws InconsistentCriteriaIdsException
     */
    private function getSalutationId(string $salutation): ?string
    {
        $salutationTranslation = $this->salutationTranslationRepository->getSalutationByDisplayName(
            $salutation,
            $this->getConfigService()->getShopwareContext()
        );

        return $salutationTranslation ? $salutationTranslation->getSalutationId() : null;
    }

    /**
     * Validates request payload.
     *
     * @param array $requestBody Body of the request.
     *
     * @throws HttpRequestException
     */
    private function validatePayload($requestBody): void
    {
        if (empty($requestBody['payload'])
            || (empty($requestBody['event']))
            || (!in_array($requestBody['event'], self::ALLOWED_EVENTS, true))
            || (((int)$requestBody['payload']['group_id']) !== $this->configService->getIntegrationId())
        ) {
            throw new HttpRequestException('Payload sent from cleverreach not valid!', 400);
        }
    }

    /**
     * Validates webhook request from CleverReach.
     *
     * @param Request $request Request object.
     *
     * @throws HttpRequestException
     */
    private function checkTokens($request): void
    {
        if (empty($this->configService->getAccessToken())
            || $request->headers->get('x-cr-calltoken') !== $this->configService->getCrEventHandlerCallToken()
        ) {
            throw new HttpRequestException('User is not authorized', 401);
        }
    }

    private function setNewsletterFlag(CustomerEntity $customer, bool $newsletter, SalesChannelContext $context): void
    {
        $customer->setNewsletter($newsletter);

        $this->customerRepository->updateStatus($customer->getId(), $newsletter, $context->getContext());
    }

    /**
     * Update customer newsletter status if exists
     *
     * @param Recipient $recipient
     * @param SalesChannelContext $context
     * @param bool $isSubscribed
     *
     * @return CustomerEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    private function processCustomerEntity(Recipient $recipient, SalesChannelContext $context, bool $isSubscribed): ?CustomerEntity
    {
        $customer = $this->customerRepository->getCustomerByEmail(
            $recipient->getEmail(),
            $this->getConfigService()->getShopwareContext()
        );
        if ($customer) {
            $this->setNewsletterFlag($customer, $isSubscribed, $context);
        }

        return $customer;
    }

    /**
     * @param Recipient $recipient
     * @param SalesChannelContext $context
     * @param bool $isSubscribed
     *
     * @return NewsletterRecipientEntity|null
     * @throws InconsistentCriteriaIdsException
     * @throws \Shopware\Core\Framework\Validation\Exception\ConstraintViolationException
     */
    private function processNewsletterRecipientEntity(
        Recipient $recipient,
        SalesChannelContext $context,
        bool $isSubscribed
    ): ?NewsletterRecipientEntity {
        $subscriber = $this->newsletterRecipientRepository->getNewsletterSubscriberByEmail(
            $recipient->getEmail(),
            $this->getConfigService()->getShopwareContext()
        );

        if ($subscriber === null) {
            $requestDataBag = $this->createRequestDataBag($recipient, $isSubscribed);
            $this->newsletterSubscribeRoute->subscribe($requestDataBag, $context);
            $subscriber = $this->newsletterRecipientRepository->getNewsletterSubscriberByEmail(
                $recipient->getEmail(),
                $this->getConfigService()->getShopwareContext()
            );
        }

        $status = $isSubscribed ? NewsletterSubscribeRoute::STATUS_OPT_IN
            : NewsletterSubscribeRoute::STATUS_OPT_OUT;
        $this->newsletterRecipientRepository->updateStatus(
            $subscriber->getId(),
            $status,
            $this->getConfigService()->getShopwareContext()
        );

        return $subscriber;
    }

    /**
     * Returns an instance of config service.
     *
     * @return ConfigService
     */
    private function getConfigService(): ConfigService
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
