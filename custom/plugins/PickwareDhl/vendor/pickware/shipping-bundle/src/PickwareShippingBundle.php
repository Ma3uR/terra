<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle;

use Doctrine\DBAL\Connection;
use Pickware\DalBundle\DalBundle;
use Pickware\MoneyBundle\MoneyBundle;
use Pickware\ShippingBundle\Carrier\CarrierAdapterRegistryCompilerPass;
use Pickware\DocumentBundle\DocumentBundle;
use Pickware\ShippingBundle\Installation\PickwareShippingBundleInstaller;
use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class PickwareShippingBundle extends Bundle
{
    private const ADDITIONAL_BUNDLES = [
        DalBundle::class,
        DocumentBundle::class,
        MoneyBundle::class,
    ];
    public const DOCUMENT_TYPE_TECHNICAL_NAME_DESCRIPTION_MAPPING = [
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_SHIPPING_LABEL => 'Versandetikett',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_RETURN_LABEL => 'Retourenetikett',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_CUSTOMS_DECLARATION_CN23 => 'Zollinhaltserklärung CN23',
        PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_STAMP => 'Briefmarke',
    ];
    public const DOCUMENT_TYPE_TECHNICAL_NAME_STAMP = 'stamp';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_CUSTOMS_DECLARATION_CN23 = 'customs_declaration_cn23';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_SHIPPING_LABEL = 'shipping_label';
    public const DOCUMENT_TYPE_TECHNICAL_NAME_RETURN_LABEL = 'return_label';

    /**
     * @var self|null
     */
    private static $instance;

    /**
     * @var bool
     */
    private static $registered = false;

    /**
     * @var bool
     */
    private static $migrationsRegistered = false;

    public static function register(Collection $bundleCollection): void
    {
        if (self::$registered) {
            return;
        }

        $bundleCollection->add(self::getInstance());
        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::register($bundleCollection);
        }

        self::$registered = true;
    }

    public static function registerMigrations(MigrationSource $migrationSource): void
    {
        if (self::$migrationsRegistered) {
            return;
        }
        $migrationsPath = self::getInstance()->getMigrationPath();
        $migrationNamespace = self::getInstance()->getMigrationNamespace();
        $migrationSource->addDirectory($migrationsPath, $migrationNamespace);

        self::$migrationsRegistered = true;

        foreach (self::ADDITIONAL_BUNDLES as $bundle) {
            $bundle::registerMigrations($migrationSource);
        }
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function build(ContainerBuilder $containerBuilder): void
    {
        parent::build($containerBuilder);

        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('Carrier/DependencyInjection/model.xml');
        $loader->load('Carrier/DependencyInjection/service.xml');
        $loader->load('Carrier/DependencyInjection/subscriber.xml');
        $loader->load('Config/DependencyInjection/command.xml');
        $loader->load('Config/DependencyInjection/model.xml');
        $loader->load('Config/DependencyInjection/service.xml');
        $loader->load('DemodataGeneration/DependencyInjection/command.xml');
        $loader->load('Logging/DependencyInjection/service.xml');
        $loader->load('Mail/DependencyInjection/controller.xml');
        $loader->load('Mail/DependencyInjection/service.xml');
        $loader->load('ParcelPacking/DependencyInjection/service.xml');
        $loader->load('ParcelHydration/DependencyInjection/service.xml');
        $loader->load('Shipment/DependencyInjection/controller.xml');
        $loader->load('Shipment/DependencyInjection/model.xml');
        $loader->load('Shipment/DependencyInjection/service.xml');

        $containerBuilder->addCompilerPass(new CarrierAdapterRegistryCompilerPass());
    }

    public function boot(): void
    {
        parent::boot();

        // Shopware may reboot the kernel under certain circumstances. After the kernel was rebooted, our bundles have
        // to be registered again. Since there does not seem to be a way to detect a reboot, we reset the registration
        // flag immediately after the bundle has been booted. This will cause the bundles to be registered again when
        // the method self::register is called the next time, which will only happen in the case of a kernel reboot.
        self::$registered = false;
    }

    public function postInstall(InstallContext $installContext): void
    {
        $installer = new PickwareShippingBundleInstaller(
            $this->container->get(Connection::class),
            $this->container->get(SystemConfigService::class)
        );
        $installer->postInstall();
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        $installer = new PickwareShippingBundleInstaller(
            $this->container->get(Connection::class),
            $this->container->get(SystemConfigService::class)
        );
        $installer->postUpdate();
    }
}
