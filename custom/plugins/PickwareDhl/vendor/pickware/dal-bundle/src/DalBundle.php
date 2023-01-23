<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DalBundle;

use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Struct\Collection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class DalBundle extends Bundle
{
    private static ?self $instance = null;
    private static bool $registered = false;
    private static bool $migrationsRegistered = false;

    public static function register(Collection $bundleCollection): void
    {
        if (!self::$registered) {
            $bundleCollection->add(self::getInstance());
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
        $loader->load('DependencyInjection/field.xml');
        $loader->load('DependencyInjection/service.xml');
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
}
