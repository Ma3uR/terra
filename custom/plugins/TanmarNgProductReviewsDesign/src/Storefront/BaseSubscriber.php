<?php

namespace Tanmar\ProductReviewsDesign\Storefront;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Event\NestedEvent;
use Tanmar\ProductReviewsDesign\Components\Config;
use Tanmar\ProductReviewsDesign\Components\ProductReviewsDesignData;
use Tanmar\ProductReviewsDesign\Components\ArticleIdentifierHelper;
use Tanmar\ProductReviewsDesign\Service\ConfigService;

class BaseSubscriber {

    /**
     * @var SystemConfigService
     */
    private $config;

    /**
     * @var string
     */
    private $extensionName = "tanmarProductReviewsDesignData";

    public function __construct(ConfigService $configService) {
        $this->config = $configService->getConfig();
    }

    /**
     * 
     * @return Config
     */
    protected function getConfig(): Config {
        return $this->config;
    }

    /**
     *
     * Loads an extension from the storefront context if it exists
     * or creates a new extension, with initialized variables.
     * The name of the extension is defined as a class variable.
     * 
     * @param NestedEvent $event
     * @return ProductReviewsDesignData
     */
    protected function getExtension(NestedEvent $event): ProductReviewsDesignData {
        try {
            $extension = $event->getContext()->getExtension($this->extensionName);
            if (is_null($extension)) {
                $extension = $this->initializeProductReviewsDesignData();
            }
        } catch (\Exception $e) {
            $extension = $this->initializeProductReviewsDesignData();
        }
        return $extension;
    }

    /**
     * 
     * Adds an extension to the storefront context.
     * 
     * @param NestedEvent $event
     * @param ProductReviewsDesignData $extension
     * @return bool
     */
    protected function addExtension(NestedEvent $event, ProductReviewsDesignData $extension): bool {
        try {
            $event->getContext()->addExtension($this->extensionName, $extension);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Values active, retargetingActive and conversionId are initially set to config values
     * @return ProductReviewsDesignData
     */
    private function initializeProductReviewsDesignData(): ProductReviewsDesignData {
        $productReviewsDesignData = new ProductReviewsDesignData();
        if ($this->getConfig() && is_object($this->getConfig())) {
            $productReviewsDesignData->assign([
                'active' => $this->getConfig()->isActive(),
                'postCommentActive' => $this->getConfig()->isPostCommentActive(),
                'goodVsBadActive' => $this->getConfig()->isGoodVsBadActive()
            ]);
        }
        return $productReviewsDesignData;
    }

}
