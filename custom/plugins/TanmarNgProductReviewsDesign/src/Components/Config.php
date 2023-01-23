<?php

declare(strict_types=1);

namespace Tanmar\ProductReviewsDesign\Components;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class Config {

    public const PLUGIN_NAME = 'TanmarNgProductReviewsDesign';

    private $systemConfigService;
    private $salesChannelId;
    private $path;
    private $active;
    private $postCommentActive;
    private $goodVsBadActive;
    private $readMoreCounter;
    private $goodVsBadMinStrlen;
    private $goodVsBadMinReviews;
    private $goodVsBadMinPointsBest;
    private $goodVsBadMaxPointsWorst;

    public function __construct(SystemConfigService $systemConfigService, string $salesChannelId) {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelId = $salesChannelId;
        $this->path = self::PLUGIN_NAME . '.config.';
        $this->active = (bool) $this->get('active') ?? false;
        $this->postCommentActive = (bool) $this->get('postCommentActive') ?? false;
        $this->goodVsBadActive = (bool) $this->get('goodVsBadActive') ?? false;
        $this->readMoreCounter = (int) $this->get('readMoreCounter') ?? 10;
        $this->goodVsBadMinStrlen = (int) $this->get('goodVsBadMinStrlen') ?? 50;
        $this->goodVsBadMinReviews = (int) $this->get('goodVsBadMinReviews') ?? 5;
        $this->goodVsBadMinPointsBest = (int) $this->get('goodVsBadMinPointsBest') ?? 4;
        $this->goodVsBadMaxPointsWorst = (int) $this->get('goodVsBadMaxPointsWorst') ?? 3;
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
     * @return bool
     */
    public function isPostCommentActive(): bool {
        return $this->postCommentActive;
    }

    /**
     * 
     * @return bool
     */
    public function isGoodVsBadActive(): bool {
        return $this->goodVsBadActive;
    }

    /**
     * 
     * @return int
     */
    public function getReadMoreCounter(): int {
        return $this->readMoreCounter;
    }

    /**
     * 
     * @return int
     */
    public function getGoodVsBadMinStrlen(): int {
        return $this->goodVsBadMinStrlen;
    }

    /**
     * 
     * @return int
     */
    public function getGoodVsBadMinReviews(): int {
        return $this->goodVsBadMinReviews;
    }

    /**
     * 
     * @return int
     */
    public function getGoodVsBadMinPointsBest(): int {
        return $this->goodVsBadMinPointsBest;
    }

    /**
     * 
     * @return int
     */
    public function getGoodVsBadMaxPointsWorst(): int {
        return $this->goodVsBadMaxPointsWorst;
    }

    private function get(string $configValueName) {
        $configValueSalesChannel = $this->systemConfigService->get($this->path . $configValueName, $this->salesChannelId);
        if (!is_null($configValueSalesChannel)) {
            return $configValueSalesChannel;
        } else {
            return false;
        }
    }

}
