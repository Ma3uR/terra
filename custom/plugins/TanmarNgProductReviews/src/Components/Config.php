<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Components;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class Config {

    private $pluginName = 'TanmarNgProductReviews';
    private $path;
    private $active;
    private $loggingLevel;
    private $optin;
    private $sendMails;
    private $sendInvitationCopy;
    private $sendCouponCopy;
    private $daysAfterShipping;
    private $ignoreOlderThan;
    private $askOldCustomers;
    private $daysMaxBacklog;
    private $maximumMails;
    private $excludeCustomerGroup;
    private $excludePaymentMethod;
    private $excludeShippingMethod;
    private $reviewSkipModeration;
    private $reviewWordsHeadline;
    private $starsPreselected;
    private $headlineRequired;
    private $sendNewReviewNotification;
    private $sendNewReviewNotificationEmail;
    private $sendVoucherMail;
    private $sendVoucherMailPromotionId;
    private $sendVoucherMailCopy;
    private $sendVoucherMailCopyTo;

    /**
     *
     * @param SystemConfigService $systemConfigService
     * @param string $salesChannelId
     */
    public function __construct(SystemConfigService $systemConfigService, string $salesChannelId) {
        $this->path = $this->pluginName . '.config.';

        $this->active = !is_null($systemConfigService->get($this->path . 'active', $salesChannelId)) ? $systemConfigService->get($this->path . 'active', $salesChannelId) : false;
        $this->loggingLevel = !is_null($systemConfigService->get($this->path . 'loggingLevel', $salesChannelId)) ? $systemConfigService->get($this->path . 'loggingLevel', $salesChannelId) : 200;
        $this->optin = !is_null($systemConfigService->get($this->path . 'optin', $salesChannelId)) ? $systemConfigService->get($this->path . 'optin', $salesChannelId) : false;
        $this->sendMails = !is_null($systemConfigService->get($this->path . 'sendMails', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendMails', $salesChannelId) : false;
        $this->sendInvitationCopy = !is_null($systemConfigService->get($this->path . 'sendInvitationCopy', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendInvitationCopy', $salesChannelId) : false;
        $this->sendCouponCopy = !is_null($systemConfigService->get($this->path . 'sendCouponCopy', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendCouponCopy', $salesChannelId) : false;

        $this->daysAfterShipping = !is_null($systemConfigService->get($this->path . 'daysAfterShipping', $salesChannelId)) ? $systemConfigService->get($this->path . 'daysAfterShipping', $salesChannelId) : 14;
        $this->ignoreOlderThan = !is_null($systemConfigService->get($this->path . 'ignoreOlderThan', $salesChannelId)) ? $systemConfigService->get($this->path . 'ignoreOlderThan', $salesChannelId) : '';
        $this->askOldCustomers = !is_null($systemConfigService->get($this->path . 'askOldCustomers', $salesChannelId)) ? $systemConfigService->get($this->path . 'askOldCustomers', $salesChannelId) : false;
        $this->daysMaxBacklog = !is_null($systemConfigService->get($this->path . 'daysMaxBacklog', $salesChannelId)) ? $systemConfigService->get($this->path . 'daysMaxBacklog', $salesChannelId) : 30;
        $this->maximumMails = !is_null($systemConfigService->get($this->path . 'maximumMails', $salesChannelId)) ? $systemConfigService->get($this->path . 'maximumMails', $salesChannelId) : 100;

        $this->excludeCustomerGroup = !is_null($systemConfigService->get($this->path . 'excludeCustomerGroup', $salesChannelId)) ? $systemConfigService->get($this->path . 'excludeCustomerGroup', $salesChannelId) : [];
        $this->excludePaymentMethod = !is_null($systemConfigService->get($this->path . 'excludePaymentMethod', $salesChannelId)) ? $systemConfigService->get($this->path . 'excludePaymentMethod', $salesChannelId) : [];
        $this->excludeShippingMethod = !is_null($systemConfigService->get($this->path . 'excludeShippingMethod', $salesChannelId)) ? $systemConfigService->get($this->path . 'excludeShippingMethod', $salesChannelId) : [];
        $this->reviewSkipModeration = !is_null($systemConfigService->get($this->path . 'reviewSkipModeration', $salesChannelId)) ? $systemConfigService->get($this->path . 'reviewSkipModeration', $salesChannelId) : false;

        $this->reviewWordsHeadline = !is_null($systemConfigService->get($this->path . 'reviewWordsHeadline', $salesChannelId)) ? $systemConfigService->get($this->path . 'reviewWordsHeadline', $salesChannelId) : 0;
        $this->starsPreselected = !is_null($systemConfigService->get($this->path . 'starsPreselected', $salesChannelId)) ? $systemConfigService->get($this->path . 'starsPreselected', $salesChannelId) : 0;
        $this->headlineRequired = !is_null($systemConfigService->get($this->path . 'headlineRequired', $salesChannelId)) ? $systemConfigService->get($this->path . 'headlineRequired', $salesChannelId) : false;

        $this->sendNewReviewNotification = !is_null($systemConfigService->get($this->path . 'sendNewReviewNotification', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendNewReviewNotification', $salesChannelId) : 0;
        $this->sendNewReviewNotificationEmail = !is_null($systemConfigService->get($this->path . 'sendNewReviewNotificationEmail', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendNewReviewNotificationEmail', $salesChannelId) : '';

        $this->sendVoucherMail = !is_null($systemConfigService->get($this->path . 'sendVoucherMail', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendVoucherMail', $salesChannelId) : 0;
        $this->sendVoucherMailPromotionId = !is_null($systemConfigService->get($this->path . 'sendVoucherMailPromotionId', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendVoucherMailPromotionId', $salesChannelId) : '';
        $this->sendVoucherMailCopy = !is_null($systemConfigService->get($this->path . 'sendVoucherMailCopy', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendVoucherMailCopy', $salesChannelId) : 0;
        $this->sendVoucherMailCopyTo = !is_null($systemConfigService->get($this->path . 'sendVoucherMailCopyTo', $salesChannelId)) ? $systemConfigService->get($this->path . 'sendVoucherMailCopyTo', $salesChannelId) : '';
    }

    /**
     *
     * @return string
     */
    public function getPluginName(): string {
        return $this->pluginName;
    }

    /**
     *
     * @return bool
     */
    public function isActive(): bool {
        return $this->active;
    }

    /**
     *
     * @return int
     */
    public function getLoggingLevel(): int {
        return (int) $this->loggingLevel;
    }

    /**
     *
     * @return bool
     */
    public function getReviewSkipModeration(): bool {
        return $this->reviewSkipModeration;
    }

    /**
     *
     * @return int
     */
    public function getReviewWordsHeadline(): int {
        return (int) $this->reviewWordsHeadline;
    }

    /**
     *
     * @return int
     */
    public function getStarsPreselected(): int {
        return (int) $this->starsPreselected;
    }

    /**
     *
     * @return bool
     */
    public function isOptin(): bool {
        return $this->optin;
    }

    /**
     *
     * @return bool
     */
    public function isSendMails(): bool {
        return $this->sendMails;
    }

    /**
     *
     * @return bool
     */
    public function isSendInvitationCopy(): bool {
        return $this->sendInvitationCopy;
    }

    /**
     *
     * @return bool
     */
    public function isSendCouponCopy(): bool {
        return $this->sendCouponCopy;
    }

    /**
     *
     * @return int
     */
    public function getDaysAfterShipping(): int {
        return (int) $this->daysAfterShipping;
    }

    /**
     *
     * @return bool
     */
    public function isAskOldCustomers(): bool {
        return $this->askOldCustomers;
    }

    /**
     *
     * @return int
     */
    public function getDaysMaxBacklog(): int {
        return (int) $this->daysMaxBacklog;
    }

    /**
     *
     * @return int
     */
    public function getMaximumMails(): int {
        return (int) $this->maximumMails;
    }

    /**
     *
     * @return array
     */
    public function getExcludeCustomerGroup(): array {
        return $this->excludeCustomerGroup;
    }

    /**
     *
     * @return array
     */
    public function getExcludePaymentMethod(): array {
        return $this->excludePaymentMethod;
    }

    /**
     *
     * @return array
     */
    public function getExcludeShippingMethod(): array {
        return $this->excludeShippingMethod;
    }

    /**
     *
     * @return bool
     */
    public function getHeadlineRequired(): bool {
        return $this->headlineRequired;
    }

    /**
     * 
     * @return int
     */
    public function getSendNewReviewNotification(): int {
        return (int) $this->sendNewReviewNotification;
    }

    /**
     * 
     * @return string
     */
    public function getSendNewReviewNotificationEmail(): string {
        return $this->sendNewReviewNotificationEmail;
    }

    /**
     *
     * @return string
     */
    public function getIgnoreOlderThan(): string {
        return $this->ignoreOlderThan;
    }

    /**
     * 
     * @return bool
     */
    public function getSendVoucherMail(): bool {
        return $this->sendVoucherMail;
    }

    /**
     * 
     * @return string
     */
    public function getSendVoucherMailPromotionId(): string {
        return $this->sendVoucherMailPromotionId;
    }

    /**
     * 
     * @return bool
     */
    public function getSendVoucherMailCopy(): bool {
        return $this->sendVoucherMailCopy;
    }

    /**
     * 
     * @return string
     */
    public function getSendVoucherMailCopyTo(): string {
        return $this->sendVoucherMailCopyTo;
    }

}
