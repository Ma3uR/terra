<?php declare(strict_types=1);

namespace Webmp\GoogleReviewFeed\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * Class GenerateGoogleReviewFeedTask
 * @package Webmp\GoogleReviewFeed\ScheduledTask
 */
class GenerateGoogleReviewFeedTask extends ScheduledTask
{
    /**
     *
     */
    const INTERVAL_DAILY = 86400;

    /**
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'webmasterei.generate_google_review_feed';
    }

    /**
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return self::INTERVAL_DAILY;
    }
}
