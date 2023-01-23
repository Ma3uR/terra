<?php declare(strict_types=1);

namespace Webmp\GoogleReviewFeed\ScheduledTask;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Webmp\GoogleReviewFeed\Helper\GoogleReviewFeedHelper;

/**
 * Class GoogleReviewFeedTaskHandler
 * @package Webmp\GoogleReviewFeed\ScheduledTask
 */
class GoogleReviewFeedTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var GoogleReviewFeedHelper
     */
    private $googleReviewFeedHelper;

    /**
     * GoogleReviewFeedTaskHandler constructor.
     * @param EntityRepositoryInterface $scheduledTaskRepository
     * @param GoogleReviewFeedHelper $googleReviewFeedHelper
     */
    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        GoogleReviewFeedHelper $googleReviewFeedHelper
    ) {
        parent::__construct($scheduledTaskRepository);

        $this->googleReviewFeedHelper = $googleReviewFeedHelper;
    }

    /**
     * @return iterable
     */
    public static function getHandledMessages(): iterable
    {
        return [ GenerateGoogleReviewFeedTask::class ];
    }

    public function run(): void
    {
        $this->googleReviewFeedHelper->generateFeedFile();
    }
}
