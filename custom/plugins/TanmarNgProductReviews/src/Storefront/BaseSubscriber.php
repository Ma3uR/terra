<?php

namespace Tanmar\ProductReviews\Storefront;

use Shopware\Core\Framework\Event\NestedEvent;
use Tanmar\ProductReviews\Components\Config;
use Tanmar\ProductReviews\Components\TanmarProductReviewsData;
use Tanmar\ProductReviews\Service\ConfigService;

class BaseSubscriber {

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $extensionName = "tanmarProductReviewsData";

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
     * @return TanmarProductReviewsData
     */
    protected function getExtension(NestedEvent $event): TanmarProductReviewsData {
        try {
            $extension = $event->getContext()->getExtension($this->extensionName);
            if (is_null($extension)) {
                $extension = $this->initializePluginData();
            }
        } catch (\Exception $e) {
            $extension = $this->initializePluginData();
        }
        return $extension;
    }

    /**
     *
     * Adds an extension to the storefront context.
     *
     * @param NestedEvent $event
     * @param TanmarProductReviewsData $extension
     * @return bool
     */
    protected function addExtension(NestedEvent $event, TanmarProductReviewsData $extension): bool {
        try {
            $event->getContext()->addExtension($this->extensionName, $extension);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Values active and optin are initially set to config values
     * @return TanmarProductReviewsData
     */
    private function initializePluginData(): TanmarProductReviewsData {
        $pluginData = new TanmarProductReviewsData();
        if ($this->getConfig() && is_object($this->getConfig())) {
            $pluginData->assign([
                'active' => $this->getConfig()->isActive(),
                'optin' => $this->getConfig()->isOptin()
            ]);
        }
        return $pluginData;
    }

}
