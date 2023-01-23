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

use Pickware\PickwareErpStarter\Supplier\Model\ProductSupplierConfigurationDefinition;
use Pickware\PickwareErpStarter\Supplier\Model\SupplierDefinition;
use Pickware\DalBundle\EntityManager;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Demodata\DemodataContext;
use Shopware\Core\Framework\Demodata\DemodataGeneratorInterface;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * This generator generates product-supplier-configurations.
 */
class ProductSupplierConfigurationGenerator implements DemodataGeneratorInterface
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
        return ProductSupplierConfigurationDefinition::class;
    }

    public function generate(int $number, DemodataContext $demodataContext, array $options = []): void
    {
        $products = $this->entityManager->findAll(ProductDefinition::class, $demodataContext->getContext());
        $supplierIds = $this->entityManager
            ->findAll(SupplierDefinition::class, $demodataContext->getContext())->getKeys();

        $demodataContext->getConsole()->progressStart($products->count());
        $payloads = [];
        $numberOfWrittenItems = 0;
        foreach ($products as $product) {
            $payload = [
                'productId' => $product->getId(),
                'supplierId' => $supplierIds[array_rand($supplierIds)],
            ];
            $payloads[] = $this->getProductSupplierConfigurationPayload($demodataContext, $payload);

            if (count($payloads) >= 50) {
                $this->entityManager->create(
                    ProductSupplierConfigurationDefinition::class,
                    $payloads,
                    $demodataContext->getContext()
                );
                $numberOfWrittenItems += count($payloads);
                $demodataContext->getConsole()->progressAdvance($numberOfWrittenItems);
                $payloads = [];
            }
        }
        $this->entityManager->create(
            ProductSupplierConfigurationDefinition::class,
            $payloads,
            $demodataContext->getContext()
        );

        $demodataContext->getConsole()->progressFinish();
        $demodataContext->getConsole()->text(sprintf(
            '%s products have been assigned to suppliers.',
            $products->count()
        ));
    }

    private function getProductSupplierConfigurationPayload(
        DemodataContext $demodataContext,
        array $payload = []
    ): array {
        $faker = $demodataContext->getFaker();

        $purchaseStepOptions = [
            1,
            5,
            10,
            25,
            50,
        ];
        $purchaseSteps = $purchaseStepOptions[array_rand($purchaseStepOptions)];
        // 5er steps in [5..50]
        $minPurchase = random_int(1, 10) * 5;

        return array_merge(
            [
                'id' => Uuid::randomHex(),
                'minPurchase' => $minPurchase,
                'purchaseSteps' => $purchaseSteps,
                'supplierProductNumber' => sprintf(
                    '%s%s',
                    mb_strtoupper($faker->randomLetter),
                    random_int(10000, 99999)
                ),
            ],
            $payload
        );
    }
}
