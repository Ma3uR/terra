<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\MoneyBundle;

use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Struct\Collection;
use Pickware\DalBundle\DalBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class MoneyBundle extends Bundle
{
    private const REQUIRED_BUNDLES = [
        DalBundle::class,
    ];

    private static ?self $instance = null;
    private static bool $registered = false;
    private static bool $migrationsRegistered = false;

    /**
     * @param Collection $bundleCollection
     */
    public static function register(Collection $bundleCollection): void
    {
        if (self::$registered) {
            return;
        }

        $bundleCollection->add(self::getInstance());
        foreach (self::REQUIRED_BUNDLES as $bundle) {
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
    }

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function build(ContainerBuilder $containerBuilder): void
    {
        parent::build($containerBuilder);

        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('DependencyInjection/service.xml');
    }

    public function boot(): void
    {
        parent::boot();

        // Shopware has the option to reboot the kernel. After the kernel was rebooted, the bundle have to be registered
        // again. Since it seems like there is currently no option to detect a reboot, the registration flag is set
        // to false immediately after the boot process of the bundle. This then leads to that the bundles will be
        // registered again when the method self::register is called again. (Which currently is only because of a
        // reboot)
        self::$registered = false;
    }
}
