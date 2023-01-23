<?php

namespace Crsw\CleverReachOfficial\Service\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Entity\Config\SystemConfigurationRepository;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ConfigService
 *
 * @package Crsw\CleverReachOfficial\Service\Infrastructure
 */
class ConfigService extends Configuration
{
    const INTEGRATION_NAME = 'Shopware 6';

    /**
     * @var Context
     */
    private static $shopwareContext;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var SystemConfigurationRepository
     */
    private $systemConfigurationRepository;

    /**
     * ConfigService constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param SystemConfigurationRepository $systemConfigurationRepository
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        SystemConfigurationRepository $systemConfigurationRepository
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->systemConfigurationRepository = $systemConfigurationRepository;
    }

    /**
     * Returns Shopware context.
     *
     * @return Context
     */
    public function getShopwareContext(): Context
    {
        return static::$shopwareContext;
    }

    /**
     * Returns Shopware context.
     *
     * @param Context $shopwareContext
     */
    public function setShopwareContext(Context $shopwareContext): void
    {
        static::$shopwareContext = $shopwareContext;
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->getIntegrationName() . ' - Default';
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getCrEventHandlerURL(): string
    {
        return $this->urlGenerator->generate('cleverreach.webhook', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @return string
     */
    public function getPluginUrl(): string
    {
        // Notifications are not implemented, so this method is useless
        return '';
    }

    /**
     * @return string
     */
    public function getNotificationMessage(): string
    {
        // Surveys are not required in Shopware plugin, so notifications are not implemented
        return '';
    }

    /**
     * @return string
     */
    public function getIntegrationName(): string
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getClientId(): string
    {
        return 'n4VcLY2We5';
    }

    /**
     * @inheritdoc
     */
    public function getClientSecret(): string
    {
        return 'xUWd13GnxnXHmKy6dL1qvpvqBgpEdDKK';
    }

    /**
     * Return whether product search is enabled or not.
     *
     * @return bool
     *   If search is enabled returns true, otherwise false.
     */
    public function isProductSearchEnabled(): bool
    {
        return true;
    }

    /**
     * Retrieves parameters needed for product search registrations.
     *
     * @return array
     *   Associative array with keys name, url, password.
     */
    public function getProductSearchParameters(): array
    {
        try {
            $salesChannel = $this->systemConfigurationRepository->getDefaultShopName($this->getShopwareContext());
            $name = $salesChannel;
        } catch (\Exception $exception) {
            $name = '';
        }

        return [
            'name' => self::INTEGRATION_NAME . ' (' . $name . ') ' . 'ProductSearch',
            'url' => $this->urlGenerator->generate('cleverreach.search', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'password' => $this->getProductSearchEndpointPassword()
        ];
    }

    /**
     * @return string
     */
    public function getAuthIframeColor(): string
    {
        return 'f9fafb';
    }
}
