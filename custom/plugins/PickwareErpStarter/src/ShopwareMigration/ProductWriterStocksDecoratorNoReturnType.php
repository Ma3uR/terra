<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ShopwareMigration;

use Pickware\PickwareErpStarter\StockApi\StockLocationReference;
use Pickware\PickwareErpStarter\StockApi\TotalStockWriter;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationAssistant\Migration\Writer\WriterInterface;

class ProductWriterStocksDecoratorNoReturnType implements WriterInterface
{
    /**
     * @var WriterInterface
     */
    private $decoratedWriter;

    /**
     * @var TotalStockWriter
     */
    private $totalStockWriter;

    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var EntityDefinition
     */
    private $definition;

    public function __construct(
        WriterInterface $decoratedProductWriter,
        TotalStockWriter $totalStockWriter,
        EntityWriterInterface $entityWriter,
        EntityDefinition $definition
    ) {
        $this->decoratedWriter = $decoratedProductWriter;
        $this->totalStockWriter = $totalStockWriter;
        $this->entityWriter = $entityWriter;
        $this->definition = $definition;
    }

    public function supports(): string
    {
        return $this->decoratedWriter->supports();
    }

    public function writeData(array $data, Context $context): void
    {
        $writeResults = [];
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data, &$writeResults): void {
            $writeResults = $this->entityWriter->upsert(
                $this->definition,
                $data,
                WriteContext::createFromContext($context)
            );
        });
        $productWriteResults = $writeResults['product'] ?? [];
        $productStocks = [];
        /** @var EntityWriteResult $productWriteResult */
        foreach ($productWriteResults as $productWriteResult) {
            $payload = $productWriteResult->getPayload();
            // Filter out instances of EntityWriteResult with empty payload. Somehow they are introduced by a bug in
            // the Shopware DAL.
            if (count($payload) === 0) {
                continue;
            }
            if ($payload['versionId'] !== Defaults::LIVE_VERSION) {
                continue;
            }
            if (!array_key_exists('stock', $payload)) {
                continue;
            }
            $productStocks[$payload['id']] = $payload['stock'];
        }
        if (count($productStocks) > 0) {
            $this->totalStockWriter->setTotalStockForProducts(
                $productStocks,
                StockLocationReference::shopwareMigration(),
                $context
            );
        }
    }
}
