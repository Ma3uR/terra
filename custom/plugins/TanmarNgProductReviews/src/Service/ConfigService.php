<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Service;

use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;
use Tanmar\ProductReviews\Components\Config;

class ConfigService {

    /**
     *
     * @var SystemConfigService $systemConfigService
     */
    private $systemConfigService;

    /**
     *
     * @var Config $config
     */
    private $config;

    /**
     *
     * @var array $configs
     */
    private $configs;

    /**
     *
     * @param SystemConfigService $systemConfigService
     * @param RequestStack $requestStack
     */
    public function __construct(SystemConfigService $systemConfigService, RequestStack $requestStack) {
        $this->systemConfigService = $systemConfigService;

        $salesChannelId = $this->getSalesChannelId($requestStack);

        $this->configs = [];
        $this->config = $this->getSalesChannelConfig($salesChannelId);
    }

    /**
     *
     * @param RequestStack $requestStack
     * @return string
     */
    private function getSalesChannelId(RequestStack $requestStack): string {
        if (!is_null($requestStack->getCurrentRequest())) {
            $salesChannelId = $requestStack->getCurrentRequest()->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);
            if (!is_null($salesChannelId)) {
                return $salesChannelId;
            }
        }
        return \Shopware\Core\Defaults::SALES_CHANNEL;
    }

    /**
     *
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }

    /**
     *
     * @param string $salesChannelId
     * @return Config
     */
    public function getSalesChannelConfig(string $salesChannelId): Config {
        if (!isset($this->configs[$salesChannelId])) {
            $this->configs[$salesChannelId] = new Config($this->systemConfigService, $salesChannelId);
        }
        return $this->configs[$salesChannelId];
    }

}
