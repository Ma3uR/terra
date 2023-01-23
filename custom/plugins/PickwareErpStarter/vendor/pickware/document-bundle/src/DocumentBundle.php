<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DocumentBundle;

use InvalidArgumentException;
use Pickware\DalBundle\DalBundle;
use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Struct\Collection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class DocumentBundle extends Bundle
{
    private const ADDITIONAL_BUNDLES = [
        DalBundle::class,
    ];

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

    /**
     * @param MigrationSource|MigrationCollectionLoader $parameter
     * @deprecated 2.0.0 The usage of the parameter (MigrationCollectionLoader $migrationCollectionLoader) is deprecated
     * and will be removed. Use (MigrationSource $migrationSource) instead.
     */
    public static function registerMigrations($parameter): void
    {
        if (self::$migrationsRegistered) {
            return;
        }
        $migrationsPath = self::getInstance()->getMigrationPath();
        $migrationNamespace = self::getInstance()->getMigrationNamespace();

        if ($parameter instanceof MigrationCollectionLoader) {
            $migrationCollectionLoader = $parameter;
            $migrationCollectionLoader->addSource(new MigrationSource('DocumentBundle', [
                $migrationsPath => $migrationNamespace,
            ]));
        } elseif ($parameter instanceof MigrationSource) {
            $migrationSource = $parameter;
            $migrationSource->addDirectory($migrationsPath, $migrationNamespace);
            $migrationSource->addDirectory(__DIR__ . '/MigrationOldNamespace', 'Pickware\\ShopwarePlugins\\DocumentBundle\\Migration');
        } else {
            throw new InvalidArgumentException(sprintf(
                'Parameter $parameter must be either of type %s or %s.',
                MigrationSource::class,
                MigrationCollectionLoader::class
            ));
        }

        self::$migrationsRegistered = true;
    }

    private static function getInstance(): self
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
        $loader->load('DependencyInjection/controller.xml');
        $loader->load('DependencyInjection/decorator.xml');
        $loader->load('DependencyInjection/model.xml');
        $loader->load('DependencyInjection/service.xml');
        $loader->load('DependencyInjection/subscriber.xml');
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
