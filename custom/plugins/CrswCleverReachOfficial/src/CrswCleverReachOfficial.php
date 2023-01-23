<?php

namespace Crsw\CleverReachOfficial;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Proxy\AuthProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\FilterSyncTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Sync\RecipientSyncTask;
use Crsw\CleverReachOfficial\Entity\Config\ConfigEntityRepository;
use Crsw\CleverReachOfficial\Entity\Config\SystemConfigurationRepository;
use Crsw\CleverReachOfficial\Entity\Customer\CustomerRepository;
use Crsw\CleverReachOfficial\Service\Business\RecipientService;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigRepositoryService;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Infrastructure\HttpClientService;
use Crsw\CleverReachOfficial\Service\Infrastructure\LoggerService;
use Crsw\CleverReachOfficial\Service\Utility\DatabaseProvider;
use Crsw\CleverReachOfficial\Service\Utility\TaskQueue;
use Crsw\CleverReachOfficial\Service\Utility\UninstallHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Kernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class CleverReach
 *
 * @package Crsw\CleverReachOfficial
 */
class CrswCleverReachOfficial extends Plugin
{
    /**
     * @param UpdateContext $context
     *
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function update(UpdateContext $context): void
    {
        if (version_compare($context->getCurrentPluginVersion(),'1.1.1', '<')) {
            /** @noinspection PhpParamsInspection */
            $customerRepository = new CustomerRepository(
                $this->container->get('customer.repository'),
                $this->container->get(Connection::class)
            );

            $guestCustomerIds = $customerRepository->getGuestCustomerIds($context->getContext());

            TaskQueue::enqueue(new FilterSyncTask());
            if (!empty($guestCustomerIds)) {
                array_walk($guestCustomerIds, function (&$id) {
                    $id = RecipientService::CUSTOMER_PREFIX . $id;
                });

                TaskQueue::enqueue(new RecipientSyncTask($guestCustomerIds));
            }
        }
    }

    /**
     * @param UninstallContext $uninstallContext
     *
     * @throws DBALException
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            /** @var UrlGeneratorInterface $urlGenerator */
            $urlGenerator = $this->container->get('router');
            /** @var Kernel $kernel */
            $kernel = $this->container->get('kernel');
            $systemConfigurationRepository = new SystemConfigurationRepository($this->container->get('system_config.repository'));
            $configRepository = new ConfigEntityRepository($this->container->get(Connection::class));
            $configRepositoryService = new ConfigRepositoryService($configRepository);
            $configService = new ConfigService($urlGenerator, $systemConfigurationRepository);
            $httpClient = new HttpClientService($configService);
            $proxy = new  Proxy();
            $authProxy = new AuthProxy();
            $logger = new LoggerService($kernel, $configService);

            $uninstallHandler = new UninstallHandler($configRepositoryService, $configService, $httpClient, $proxy, $authProxy, $logger);
            $uninstallHandler->removeCleverReachApiEndpointsAndRevokeToken();

            $this->removeAllTables();
        }

        parent::uninstall($uninstallContext);
    }

    /**
     * Removes all CleverReach tables
     *
     * @throws DBALException
     */
    private function removeAllTables(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $databaseProvider = new DatabaseProvider($connection);
        $databaseProvider->removeCleverReachTables();
    }
}
