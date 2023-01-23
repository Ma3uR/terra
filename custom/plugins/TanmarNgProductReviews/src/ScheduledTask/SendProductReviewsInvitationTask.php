<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class SendProductReviewsInvitationTask extends ScheduledTask {

    public static function getTaskName(): string {
        return 'tanmar.productreviews.send.invitation';
    }

    public static function getDefaultInterval(): int {
        return 600; // 86400 24std
    }

}
