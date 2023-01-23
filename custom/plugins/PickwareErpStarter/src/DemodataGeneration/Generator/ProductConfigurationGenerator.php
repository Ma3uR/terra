<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\DemodataGeneration\Generator;

use Pickware\PickwareErpStarter\Product\Model\ProductConfigurationDefinition;
use Pickware\DalBundle\EntityManager;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Demodata\DemodataContext;
use Shopware\Core\Framework\Demodata\DemodataGeneratorInterface;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * This generator generates product-configurations.
 */
class ProductConfigurationGenerator implements DemodataGeneratorInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getDefinition(): string
    {
        return ProductConfigurationDefinition::class;
    }

    public function generate(int $number, DemodataContext $demodataContext, array $options = []): void
    {
        $products = $this->entityManager->findAll(ProductDefinition::class, $demodataContext->getContext());

        $demodataContext->getConsole()->progressStart($products->count());
        $payloads = [];
        $numberOfWrittenItems = 0;
        foreach ($products as $product) {
            $payload = [
                'productId' => $product->getId(),
            ];
            $payloads[] = $this->getProductConfigurationPayload($payload);

            if (count($payloads) >= 50) {
                $this->entityManager->create(
                    ProductConfigurationDefinition::class,
                    $payloads,
                    $demodataContext->getContext()
                );
                $numberOfWrittenItems += count($payloads);
                $demodataContext->getConsole()->progressAdvance($numberOfWrittenItems);
                $payloads = [];
            }
        }
        $this->entityManager->create(
            ProductConfigurationDefinition::class,
            $payloads,
            $demodataContext->getContext()
        );

        $demodataContext->getConsole()->progressFinish();
        $demodataContext->getConsole()->text(sprintf(
            '%s product configurations have been created.',
            $products->count()
        ));
    }

    private function getProductConfigurationPayload(array $payload = []): array
    {
        // 25er steps in [0..400]
        $reorderPoint = random_int(0, 16) * 25;

        return array_merge(
            [
                'id' => Uuid::randomHex(),
                'reorderPoint' => $reorderPoint,
            ],
            $payload
        );
    }
}
