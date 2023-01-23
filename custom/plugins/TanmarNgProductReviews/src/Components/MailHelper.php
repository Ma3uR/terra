<?php

namespace Tanmar\ProductReviews\Components;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Mime\Email;
use Tanmar\ProductReviews\Components\TanmarProductReviewsHelper;
use Tanmar\ProductReviews\Service\ConfigService;
use Tanmar\ProductReviews\Service\PromotionService;
use Throwable;

class MailHelper {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PromotionService
     */
    protected $promotionService;

    /**
     * @var AbstractMailService
     */
    protected $mailService;

    /**
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $mailTemplateRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelRepository;

    /**
     * @var LoggerHelper
     */
    protected $loggerHelper;

    /**
     * @var EntityRepositoryInterface
     */
    protected $customerGroupRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $paymentMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $shippingMethodRepository;

    /**
     *
     * @var SystemConfigService
     */
    protected $systemConfigService;

    /**
     *
     * @param ConfigService $configService
     * @param AbstractMailService $mailService
     * @param EntityRepositoryInterface $orderRepository
     * @param EntityRepositoryInterface $mailTemplateRepository
     * @param EntityRepositoryInterface $salesChannelRepository
     * @param LoggerHelper $loggerHelper
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $customerGroupRepository
     * @param EntityRepositoryInterface $paymentMethodRepository
     * @param EntityRepositoryInterface $shippingMethodRepository
     */
    public function __construct(
        ConfigService $configService,
        PromotionService $promotionService,
        AbstractMailService $mailService,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        EntityRepositoryInterface $salesChannelRepository,
        LoggerHelper $loggerHelper,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $customerGroupRepository,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $shippingMethodRepository
    ) {
        $this->config = $configService->getConfig();
        $this->promotionService = $promotionService;
        $this->mailService = $mailService;
        $this->orderRepository = $orderRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->loggerHelper = $loggerHelper;
        $this->systemConfigService = $systemConfigService;

        // until NEXT-9294 is fixed
        $this->customerGroupRepository = $customerGroupRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    /**
     *
     * @param Context $context
     * @param OrderEntity $order
     * @return MailTemplateEntity
     */
    public function getInvitationMailTemplate(Context $context, OrderEntity $order): MailTemplateEntity {
        return $this->getMailTemplate($context, 'tanmar_product_reviews_mail.invitation', $order);
    }

    /**
     *
     * @param Context $context
     * @param OrderEntity $order
     * @return MailTemplateEntity
     */
    public function getNotificationMailTemplate(Context $context, OrderEntity $order): MailTemplateEntity {
        return $this->getMailTemplate($context, 'tanmar_product_reviews_mail.notification', $order);
    }

    /**
     *
     * @param Context $context
     * @param OrderEntity $order
     * @return MailTemplateEntity
     */
    public function getVoucherMailTemplate(Context $context, OrderEntity $order): MailTemplateEntity {
        return $this->getMailTemplate($context, 'tanmar_product_reviews_mail.coupon', $order);
    }

    /**
     *
     * @param Context $context
     * @param SalesChannelEntity $salesChannel
     * @return EntitySearchResult
     */
    public function getInvitationOrders(Context $context, SalesChannelEntity $salesChannel): EntitySearchResult {
        $date = new DateTime();
        $date->sub(new DateInterval('P' . $this->getConfig()->getDaysAfterShipping() . 'D'));

        $range = [
            RangeFilter::LTE => $date->format('Y-m-d H:i:s'),
        ];

        if ($this->getConfig()->getDaysMaxBacklog() > 0) {
            $maxDate = new DateTime();
            $maxDate->sub(new DateInterval('P' . $this->getConfig()->getDaysMaxBacklog() . 'D'));
            $range[RangeFilter::GTE] = $maxDate->format('Y-m-d H:i:s');
        }
        $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'RangeFilter', $range);

        $criteria = new Criteria();
        $criteria->addAssociation('lineItems.product.cover.media');
        $criteria->addAssociation('orderCustomer.salutation');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('currency');

        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannel->getId()));
        if ($this->getConfig()->isOptin()) {
            $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'optin active');
            if ($this->getConfig()->isAskOldCustomers()) {
                $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'ask old customers active');
                $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                        new EqualsFilter('customFields.tanmar_product_reviews_optin', 'agreed'),
                        new EqualsFilter('customFields.tanmar_product_reviews_optin', 'not asked'),
                        new EqualsFilter('customFields.tanmar_product_reviews_optin', null),
                ]));
            } else {
                $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'ask old customers inactive');
                $criteria->addFilter(new EqualsFilter('customFields.tanmar_product_reviews_optin', 'agreed'));
            }
        } else {
            $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'optin inactive');
            if ($this->getConfig()->isAskOldCustomers()) {
                $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'ask old customers active');
                $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                        new EqualsFilter('customFields.tanmar_product_reviews_optin', 'agreed'),
                        new EqualsFilter('customFields.tanmar_product_reviews_optin', 'not asked'),
                        new EqualsFilter('customFields.tanmar_product_reviews_optin', null),
                ]));
            }
        }

        if ($this->getConfig()->getIgnoreOlderThan() != '') {
            $criteria->addFilter(new RangeFilter('orderDateTime', [RangeFilter::GTE => $this->getConfig()->getIgnoreOlderThan()]));
        }

        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('customFields.tanmar_product_reviews_status', 'open'),
                new EqualsFilter('customFields.tanmar_product_reviews_status', null),
        ]));
        $criteria->addFilter(new EqualsFilter('deliveries.stateMachineState.technicalName', 'shipped'));
        $criteria->addFilter(new RangeFilter('deliveries.updatedAt', $range));

        $excludes = $this->getConfig()->getExcludeCustomerGroup();
        $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'exclude customer', $excludes);
        if (count($excludes)) {
            $criteria->addAssociation('orderCustomer.customer');
            $includes = [];
            $entitySearchResult = $this->customerGroupRepository->search(new Criteria(), $context);
            foreach ($entitySearchResult as $entity) {
                if (!in_array($entity->getId(), $excludes)) {
                    $includes[] = $entity->getId();
                }
            }
            $criteria->addFilter(new EqualsAnyFilter('orderCustomer.customer.groupId', $includes));
        }

        $excludes = $this->getConfig()->getExcludePaymentMethod();
        $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'exclude payment', $excludes);
        if (count($excludes)) {
            $criteria->addAssociation('transactions');
            $includes = [];
            $entitySearchResult = $this->paymentMethodRepository->search(new Criteria(), $context);
            foreach ($entitySearchResult as $entity) {
                if (!in_array($entity->getId(), $excludes)) {
                    $includes[] = $entity->getId();
                }
            }
            $criteria->addFilter(new EqualsAnyFilter('transactions.paymentMethodId', $includes));
        }

        $excludes = $this->getConfig()->getExcludeShippingMethod();
        $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'exclude shipping', $excludes);
        if (count($excludes)) {
            $includes = [];
            $entitySearchResult = $this->shippingMethodRepository->search(new Criteria(), $context);
            foreach ($entitySearchResult as $entity) {
                if (!in_array($entity->getId(), $excludes)) {
                    $includes[] = $entity->getId();
                }
            }
            $criteria->addFilter(new EqualsAnyFilter('deliveries.shippingMethodId', $includes));
        }

        $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'max mails ' . $this->getConfig()->getMaximumMails());

        $criteria->setLimit($this->getConfig()->getMaximumMails());
        $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::DESCENDING));

        $result = $this->orderRepository->search($criteria, $context);
        return $result;
    }

    /**
     *
     * @param Context $outerContext
     * @param OrderEntity $order
     * @param bool $testMail
     * @return boolean
     */
    public function sendInvitationMail(Context $outerContext, OrderEntity $order, bool $testMail = false) {
        $message = null;
        try {
            $context = $this->getOrderContext($outerContext, $order);
            $mailTemplate = $this->getInvitationMailTemplate($context, $order);
            $salesChannel = $this->getSalesChannel($context, $order);

            $data = new DataBag();
            if ($testMail) {
                $storeOwnerEmail = $this->getStoreOwnerEmail();
                $data->set(
                    'recipients',
                    [
                        $storeOwnerEmail => $storeOwnerEmail,
                    ]
                );
                $this->loggerHelper->addDirectRecord(Logger::INFO, 'Send Test Invitation Mail: ' . $storeOwnerEmail);
            } else {
                $data->set(
                    'recipients',
                    [
                        $order->getOrderCustomer()->getEmail() => $order->getOrderCustomer()->getFirstName() . ' ' . $order->getOrderCustomer()->getLastName(),
                    ]
                );

                if ($this->getConfig()->isSendInvitationCopy()) {
                    $storeOwnerEmail = $this->getStoreOwnerEmail();
                    $data->set('recipientsBcc', $storeOwnerEmail);
                }
            }


            $data->set('senderName', $mailTemplate->getSenderName());
            $data->set('salesChannelId', $order->getSalesChannelId());
            $data->set('contentHtml', $mailTemplate->getContentHtml());
            $data->set('contentPlain', $mailTemplate->getContentPlain());
            $data->set('subject', $mailTemplate->getSubject());

            $helper = new TanmarProductReviewsHelper();
            $orderHash = $helper->getOrderHash($order);
            $hashShort = substr($orderHash, 0, 6);

            $message = $this->sendMail(
                $data->all(),
                $context,
                [
                    'order' => $order,
                    'salesChannel' => $salesChannel,
                    'hash' => $hashShort
                ],
                $testMail
            );
        } catch (Throwable $e) {
            $this->loggerHelper->addDirectRecord(Logger::ERROR, 'Error while sending Mail: ' . $e->getMessage(), ['exception' => $e]);
        }
        if (!is_null($message) && !$testMail) {
            $this->markInvitationAsSent($order, $context);
            return true;
        }
        return false;
    }

    /**
     *
     * @param Context $outerContext
     * @param OrderEntity $order
     * @param int $points
     * @param bool $testMail
     * @return boolean
     */
    public function sendNotificationMail(Context $outerContext, OrderEntity $order, int $points, bool $testMail = false) {
        try {
            $context = $this->getOrderContext($outerContext, $order);
            $mailTemplate = $this->getNotificationMailTemplate($context, $order);
            $salesChannel = $this->getSalesChannel($context, $order);

            $pluginConfig = $this->getConfig();

            $mailsTo = explode(';', $pluginConfig->getSendNewReviewNotificationEmail());
            $recipients = [];
            foreach ($mailsTo as $mailTo) {
                if (!empty(trim($mailTo))) {
                    $recipients[trim($mailTo)] = trim($mailTo);
                }
            }
            if (!(count($recipients) > 0)) {
                $storeOwnerEmail = $this->getStoreOwnerEmail();
                $recipients[$storeOwnerEmail] = $storeOwnerEmail;
            }

            if ($testMail) {
                $storeOwnerEmail = $this->getStoreOwnerEmail();
                $recipients = [$storeOwnerEmail => $storeOwnerEmail];
                $this->loggerHelper->addDirectRecord(Logger::INFO, 'Send Test Notification Mail: ' . $storeOwnerEmail);
            }

            $data = new DataBag();
            $data->set('recipients', $recipients);
            $data->set('senderName', $mailTemplate->getSenderName());
            $data->set('salesChannelId', $order->getSalesChannelId());
            $data->set('contentHtml', $mailTemplate->getContentHtml());
            $data->set('contentPlain', $mailTemplate->getContentPlain());
            $data->set('subject', $mailTemplate->getSubject());

            $message = $this->sendMail(
                $data->all(),
                $context,
                [
                    'order' => $order,
                    'salesChannel' => $salesChannel,
                    'tanmarProductReviews' => array(
                        'points' => $points,
                        'skipModeration' => $pluginConfig->getReviewSkipModeration(),
                    )
                ]
            );
            if (!is_null($message)) {
                return true;
            } else {
                return false;
            }
        } catch (Throwable $e) {
            $this->loggerHelper->addDirectRecord(Logger::ERROR, 'Error while sending Mail: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    /**
     *
     * @param Context $outerContext
     * @param OrderEntity $order
     * @param string $promotionCode
     * @param bool $testMail
     * @return boolean
     */
    public function sendVoucherMail(Context $outerContext, OrderEntity $order, string $promotionCode, bool $testMail = false) {
        try {
            $context = $this->getOrderContext($outerContext, $order);
            $mailTemplate = $this->getVoucherMailTemplate($context, $order);
            $salesChannel = $this->getSalesChannel($context, $order);
            $pluginConfig = $this->getConfig();

            $data = new DataBag();
            if ($testMail) {
                $storeOwnerEmail = $this->getStoreOwnerEmail();
                $data->set(
                    'recipients',
                    [
                        $storeOwnerEmail => $storeOwnerEmail,
                    ]
                );
                $this->loggerHelper->addDirectRecord(Logger::INFO, 'Send Test Voucher Mail: ' . $storeOwnerEmail);
            } else {
                $data->set(
                    'recipients',
                    [
                        $order->getOrderCustomer()->getEmail() => $order->getOrderCustomer()->getFirstName() . ' ' . $order->getOrderCustomer()->getLastName(),
                    ]
                );

                if ($pluginConfig->getSendVoucherMailCopy()) {
                    if ($pluginConfig->getSendVoucherMailCopyTo() != '') {
                        $data->set('recipientsBcc', trim($pluginConfig->getSendVoucherMailCopyTo()));
                    } else {
                        $storeOwnerEmail = $this->getStoreOwnerEmail();
                        $data->set('recipientsBcc', $storeOwnerEmail);
                    }
                }
            }

            $data->set('senderName', $mailTemplate->getSenderName());
            $data->set('salesChannelId', $order->getSalesChannelId());
            $data->set('contentHtml', $mailTemplate->getContentHtml());
            $data->set('contentPlain', $mailTemplate->getContentPlain());
            $data->set('subject', $mailTemplate->getSubject());

            $message = $this->sendMail(
                $data->all(),
                $context,
                [
                    'order' => $order,
                    'salesChannel' => $salesChannel,
                    'tanmarProductReviews' => array(
                        'coupon' => $promotionCode
                    )
                ]
            );
            if (!is_null($message)) {
                return true;
            } else {
                return false;
            }
        } catch (Throwable $e) {
            $this->loggerHelper->addDirectRecord(Logger::ERROR, 'Error while sending Mail: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    /**
     *
     * @param OrderEntity $order
     * @param Context $context
     * @return void
     */
    public function markInvitationAsSent(OrderEntity $order, Context $context): void {
        $fields = $order->getCustomFields();
        if (is_null($fields)) {
            $fields = [];
        }

        $fields['tanmar_product_reviews_sent'] = new DateTimeImmutable();
        $fields['tanmar_product_reviews_status'] = 'invited';

        $this->orderRepository->update([
            [
                'id' => $order->getId(),
                'customFields' => $fields,
            ],
            ], $context);
    }

    /**
     *
     * @param array $data
     * @param Context $context
     * @param array $templateData
     * @return Email|null
     */
    public function sendMail(array $data, Context $context, array $templateData = [], bool $testMail = false): ?Email {
        if (!$testMail && !$this->getConfig()->isSendMails()) {
            $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'Sending Mail blocked by configuration.');
            return null;
        }

        return $this->getMailService()->send(
                $data,
                $context,
                $templateData
        );
    }

    /**
     *
     * @param Context $context
     * @param OrderEntity $order
     * @return SalesChannelEntity|null
     */
    protected function getSalesChannel(Context $context, OrderEntity $order): ?SalesChannelEntity {
        $languageId = $order->getLanguageId();
        $salesChannelCriteria = new Criteria([$order->getSalesChannelId()]);
        $salesChannelCriteria->addAssociation('mailHeaderFooter');
        $salesChannelCriteria->getAssociation('domains')
            ->addFilter(
                new EqualsFilter('languageId', $languageId)
        );

        return $this->salesChannelRepository->search($salesChannelCriteria, $context)->first();
    }

    /**
     *
     * @param OrderEntity $order
     * @return array
     */
    protected function getLanguageIdChain(OrderEntity $order): array {
        return [
            $order->getLanguageId(),
            Defaults::LANGUAGE_SYSTEM,
        ];
    }

    /**
     *
     * @param Context $context
     * @param string $technicalName
     * @param OrderEntity $order
     * @return MailTemplateEntity|null
     */
    protected function getMailTemplate(Context $context, string $technicalName, OrderEntity $order): ?MailTemplateEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $mailTemplate */
        $mailTemplate = $this->mailTemplateRepository->search($criteria, $context)->first();

        return $mailTemplate;
    }

    /**
     *
     * @param Context $context
     * @param OrderEntity $order
     * @return Context
     */
    public function getOrderContext(Context $context, OrderEntity $order): Context {
        $orderContext = new Context(
            $context->getSource(),
            $context->getRuleIds(),
            $order->getCurrencyId(),
            $this->getLanguageIdChain($order),
            $context->getVersionId(),
            $order->getCurrency()->getItemRounding()->getDecimals(),
            true,
            $order->getTaxStatus(),
            $order->getCurrency()->getItemRounding()
        );

        return $orderContext;
    }

    /**
     * @return AbstractMailService
     */
    public function getMailService(): AbstractMailService {
        return $this->mailService;
    }

    /**
     * @return EntityRepositoryInterface
     */
    public function getOrderRepository(): EntityRepositoryInterface {
        return $this->orderRepository;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config) {
        $this->config = $config;
    }

    /**
     *
     * @return string
     */
    public function getStoreOwnerEmail() {
        $mail = $this->systemConfigService->get('core.basicInformation.email');
        return $mail;
    }

}
