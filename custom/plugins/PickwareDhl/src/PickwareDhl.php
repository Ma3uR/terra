<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl;

use Doctrine\DBAL\Connection;
use Pickware\PickwareDhl\Dhl\DhlConfig;
use Pickware\PickwareDhl\Installation\PickwareDhlInstaller;
use Pickware\ShippingBundle\Installation\CarrierUninstaller;
use Pickware\DalBundle\DalBundle;
use Pickware\DebugBundle\ShopwarePluginsDebugBundle;
use Pickware\DocumentBundle\DocumentBundle;
use Pickware\MoneyBundle\MoneyBundle;
use Pickware\ShippingBundle\Carrier\CarrierAdapterRegistryCompilerPass;
use Pickware\ShippingBundle\PickwareShippingBundle;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Migration\MigrationRuntime;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Struct\Collection;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

if (file_exists(__DIR__ . '/../vendor/pickware/dependency-loader/src/DependencyLoader.php')) {
    require_once __DIR__ . '/../vendor/pickware/dependency-loader/src/DependencyLoader.php';
}

class PickwareDhl extends Plugin
{
    private const ADDITIONAL_BUNDLES = [
        DalBundle::class,
        DocumentBundle::class,
        MoneyBundle::class,
        PickwareShippingBundle::class,
        ShopwarePluginsDebugBundle::class,
    ];
    public const CARRIER_TECHNICAL_NAME_DHL = 'dhl';
    public const MAIL_TEMPLATE_TYPE_TECHNICAL_NAME_RETURN_LABEL = 'pickware_dhl_return_label';

    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        if (isset($GLOBALS['PICKWARE_DEPENDENCY_LOADER'])) {
            $kernelParameters = $parameters->getKernelParameters();

            // Ensure the bundle classes can be loaded via auto-loading.
            $GLOBALS['PICKWARE_DEPENDENCY_LOADER']->ensureLatestDependenciesOfPluginsLoaded(
                $kernelParameters['kernel.plugin_infos'],
                $kernelParameters['kernel.project_dir']
            );
        }

        // For some reason Collection is abstract
        // phpcs:ignore Squiz.WhiteSpace.ScopeClosingBrace.ContentBefore -- PHP CS does not understand the PHP 7 syntax
        $bundleCollection = new class() extends Collection {};
        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::register($bundleCollection);
        }

        return $bundleCollection->getElements();
    }

    public static function getDistPackages(): array
    {
        return include __DIR__ . '/../Packages.php';
    }

    public function build(ContainerBuilder $containerBuilder): void
    {
        parent::build($containerBuilder);

        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('Dhl/DependencyInjection/command.xml');
        $loader->load('Dhl/DependencyInjection/service.xml');
        $loader->load('Installation/DependencyInjection/service.xml');

        $containerBuilder->addCompilerPass(new CarrierAdapterRegistryCompilerPass());
    }

    public function install(InstallContext $installContext): void
    {
        $this->loadDependenciesForSetup();

        $this->executeMigrationsOfBundles();
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->loadDependenciesForSetup();

        $this->executeMigrationsOfBundles();
    }

    private function executeMigrationsOfBundles(): void
    {
        // All the services required for migration execution are private in the DI-Container. As a workaround the
        // services are instantiated explicitly here.
        $db = $this->container->get(Connection::class);
        // See vendor/symfony/monolog-bundle/Resources/config/monolog.xml on how the logger is defined.
        $logger = new Logger('app');
        $logger->useMicrosecondTimestamps($this->container->getParameter('monolog.use_microseconds'));
        $migrationCollectionLoader = new MigrationCollectionLoader($db, new MigrationRuntime($db, $logger));
        $migrationSource = new MigrationSource('PickwareDhl');

        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::registerMigrations($migrationSource);
        }
        $migrationCollectionLoader->addSource($migrationSource);

        foreach ($migrationCollectionLoader->collectAll() as $migrationCollection) {
            $migrationCollection->sync();
            $migrationCollection->migrateInPlace();
        }
    }

    public function postInstall(InstallContext $installContext): void
    {
        foreach (self::ADDITIONAL_BUNDLES as $bundleClass) {
            $bundle = $bundleClass::getInstance();
            $bundle->setContainer($this->container);
            if (method_exists($bundle, 'postInstall')) {
                $bundle->postInstall($installContext);
            }
        }

        $installer = new PickwareDhlInstaller($this->container->get(Connection::class));
        $installer->postInstall();
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        foreach (self::ADDITIONAL_BUNDLES as $bundleClass) {
            $bundle = $bundleClass::getInstance();
            $bundle->setContainer($this->container);
            if (method_exists($bundle, 'postUpdate')) {
                $bundle->postUpdate($updateContext);
            }
        }

        $installer = new PickwareDhlInstaller($this->container->get(Connection::class));
        $installer->postUpdate();

        if ($updateContext->getPlugin()->isActive()) {
            $this->container->get('pickware_dhl.bundle_supporting_asset_service')->copyAssetsFromBundle('PickwareShippingBundle');
            $this->migrateDocumentsOfPluginFileSystemToDocumentBundleFileSystem();
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        if ($context->keepUserData()) {
            return;
        }

        $this->loadDependenciesForSetup();

        /** @var Connection $db */
        $db = $this->container->get(Connection::class);

        // This are actually only tables from old plugin versions. We still remove them here just in case.
        $db->executeStatement('
            SET FOREIGN_KEY_CHECKS=0;
            DROP TABLE IF EXISTS `pickware_dhl_carrier`;
            DROP TABLE IF EXISTS `pickware_dhl_document`;
            DROP TABLE IF EXISTS `pickware_dhl_document_page_format`;
            DROP TABLE IF EXISTS `pickware_dhl_document_shipment_mapping`;
            DROP TABLE IF EXISTS `pickware_dhl_document_tracking_code_mapping`;
            DROP TABLE IF EXISTS `pickware_dhl_document_type`;
            DROP TABLE IF EXISTS `pickware_dhl_shipment`;
            DROP TABLE IF EXISTS `pickware_dhl_shipment_order_delivery_mapping`;
            DROP TABLE IF EXISTS `pickware_dhl_shipment_order_mapping`;
            DROP TABLE IF EXISTS `pickware_dhl_shipping_method_config`;
            DROP TABLE IF EXISTS `pickware_dhl_tracking_code`;
            SET FOREIGN_KEY_CHECKS=1;
        ');

        $db->executeStatement(
            'DELETE FROM system_config
            WHERE configuration_key LIKE :domain',
            [
                'domain' => DhlConfig::CONFIG_DOMAIN . '.%',
            ]
        );

        CarrierUninstaller::createForContainer($this->container)->uninstallCarrier(self::CARRIER_TECHNICAL_NAME_DHL);
    }

    public function activate(ActivateContext $activateContext): void
    {
        foreach (self::ADDITIONAL_BUNDLES as $bundleClass) {
            $bundle = $bundleClass::getInstance();
            $bundle->setContainer($this->container);
            if (method_exists($bundle, 'activate')) {
                $bundle->activate($activateContext);
            }
        }

        $this->container->get('pickware_dhl.bundle_supporting_asset_service')->copyAssetsFromBundle('PickwareShippingBundle');
        $this->migrateDocumentsOfPluginFileSystemToDocumentBundleFileSystem();
    }

    private function migrateDocumentsOfPluginFileSystemToDocumentBundleFileSystem(): void
    {
        $this->container->get(
            'pickware_dhl.plugin_filesystem_to_document_bundle_filesystem_migrator'
        )->moveDirectory('documents');
    }

    /**
     * Run the dependency loader for a setup step like install/update/uninstall
     *
     * When executing one of this steps but no Pickware plugin is activated, the dependency loader did never run until
     * the call of the corresponding method. You can trigger it with a call of this method.
     */
    private function loadDependenciesForSetup(): void
    {
        if (isset($GLOBALS['PICKWARE_DEPENDENCY_LOADER'])) {
            $plugins = $this->container->get('kernel')->getPluginLoader()->getPluginInfos();
            $projectDir = $this->container->getParameter('kernel.project_dir');
            $GLOBALS['PICKWARE_DEPENDENCY_LOADER']->ensureLatestDependenciesOfPluginsLoaded($plugins, $projectDir);
        }
    }
}
