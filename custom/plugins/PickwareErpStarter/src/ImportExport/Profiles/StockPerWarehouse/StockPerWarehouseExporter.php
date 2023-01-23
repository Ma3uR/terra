<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport\Profiles\StockPerWarehouse;

use Pickware\DalBundle\CriteriaJsonSerializer;
use Pickware\PickwareErpStarter\ImportExport\Exporter;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportElementDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportEntity;
use Pickware\PickwareErpStarter\Product\ProductNameFormatterService;
use Pickware\PickwareErpStarter\Stock\Model\WarehouseStockDefinition;
use Pickware\PickwareErpStarter\Stock\Model\WarehouseStockEntity;
use Pickware\PickwareErpStarter\Translation\Translator;
use Pickware\DalBundle\EntityManager;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;

class StockPerWarehouseExporter implements Exporter
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ProductNameFormatterService
     */
    private $productNameFormatterService;

    /**
     * @var CriteriaJsonSerializer
     */
    private $criteriaJsonSerializer;

    public function __construct(
        EntityManager $entityManager,
        CriteriaJsonSerializer $criteriaJsonSerializer,
        Translator $translator,
        ProductNameFormatterService $productNameFormatterService,
        int $batchSize
    ) {
        $this->entityManager = $entityManager;
        $this->criteriaJsonSerializer = $criteriaJsonSerializer;
        $this->batchSize = $batchSize;
        $this->translator = $translator;
        $this->productNameFormatterService = $productNameFormatterService;
    }

    public function exportChunk(string $exportId, int $nextElementToWrite, Context $context): ?int
    {
        /** @var ImportExportEntity $export */
        $export = $this->entityManager->findByPrimaryKey(ImportExportDefinition::class, $exportId, $context);
        $exportConfig = $export->getConfig();

        $criteria = $this->criteriaJsonSerializer->deserializeFromArray(
            $exportConfig['criteria'],
            new Criteria(),
            $this->getEntityDefinition(),
            $context
        );

        // Retrieve the next batch of matching results
        $criteria->setLimit($this->batchSize);
        $criteria->setOffset($nextElementToWrite);

        $exportRows = $this->getStockOverviewPerWarehouseExportRows($criteria, $exportConfig['locale'], $context);

        $exportElementPayloads = [];
        foreach ($exportRows as $index => $exportRow) {
            $exportElementPayloads[] = [
                'id' => Uuid::randomHex(),
                'importExportId' => $exportId,
                'rowNumber' => $nextElementToWrite + $index,
                'rowData' => $exportRow,
            ];
        }
        $this->generateExportElements($exportElementPayloads, $context);

        $nextElementToWrite += $this->batchSize;

        if (count($exportRows) < $this->batchSize) {
            return null;
        }

        return $nextElementToWrite;
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->entityManager->getEntityDefinition(WarehouseStockDefinition::class);
    }

    /**
     * @param Criteria $criteria Only filters, sorting, limit and offset are respected
     * @param string $locale
     * @param Context $context
     * @return array
     */
    private function getStockOverviewPerWarehouseExportRows(Criteria $criteria, string $locale, Context $context): array
    {
        $csvHeaderTranslations = $this->getCsvHeaderTranslations($locale, $context);

        $warehouseStocks = $context->enableInheritance(function (Context $inheritanceContext) use ($criteria) {
            return $this->entityManager->findBy(
                WarehouseStockDefinition::class,
                $this->sanitizeCriteria($criteria),
                $inheritanceContext,
                [
                    'product.options',
                    'warehouse',
                ]
            );
        });

        $productIds = $warehouseStocks->map(function (WarehouseStockEntity $warehouseStock) {
            return $warehouseStock->getProduct()->getId();
        });
        $productNames = $this->productNameFormatterService->getFormattedProductNames(
            $productIds,
            [],
            $context
        );

        $rows = [];
        /** @var WarehouseStockEntity $warehouseStock */
        foreach ($warehouseStocks as $warehouseStock) {
            $warehouse = $warehouseStock->getWarehouse();
            $rows[] = [
                $csvHeaderTranslations['product-name'] => $productNames[$warehouseStock->getProduct()->getId()],
                $csvHeaderTranslations['product-number'] => $warehouseStock->getProduct()->getProductNumber(),
                $csvHeaderTranslations['warehouse-name'] => $warehouse ? $warehouse->getName() : '',
                $csvHeaderTranslations['warehouse-code'] => $warehouse ? $warehouse->getCode() : '',
                $csvHeaderTranslations['stock'] => $warehouseStock->getQuantity(),
            ];
        }

        return $rows;
    }

    private function getCsvHeaderTranslations(string $locale, Context $context): array
    {
        $this->translator->setTranslationLocale($locale, $context);

        return [
            'product-name' => $this->translator->translate('pickware-erp-starter.stock-export.columns.product-name'),
            'product-number' => $this->translator->translate('pickware-erp-starter.stock-export.columns.product-number'),
            'supplier' => $this->translator->translate('pickware-erp-starter.stock-export.columns.supplier'),
            'warehouse-name' => $this->translator->translate('pickware-erp-starter.stock-export.columns.warehouse-name'),
            'warehouse-code' => $this->translator->translate('pickware-erp-starter.stock-export.columns.warehouse-code'),
            'bin-location' => $this->translator->translate('pickware-erp-starter.stock-export.columns.bin-location'),
            'reorder-point' => $this->translator->translate('pickware-erp-starter.stock-export.columns.reorder-point'),
            'stock' => $this->translator->translate('pickware-erp-starter.stock-export.columns.stock'),
        ];
    }

    /**
     * Creates a new Criteria object with filter, sorting, limit and offset of the given criteria (i.e. associations and
     * other settings are ignored).
     *
     * @param Criteria $criteria
     * @return Criteria
     */
    private function sanitizeCriteria(Criteria $criteria): Criteria
    {
        $sanitizedCriteria = new Criteria();
        $sanitizedCriteria->addFilter(...$criteria->getFilters());
        $sanitizedCriteria->addSorting(...$criteria->getSorting());
        $sanitizedCriteria->setLimit($criteria->getLimit());
        $sanitizedCriteria->setOffset($criteria->getOffset());

        return $sanitizedCriteria;
    }

    private function generateExportElements(array $payloads, Context $context): void
    {
        $this->entityManager->create(
            ImportExportElementDefinition::class,
            $payloads,
            $context
        );
    }
}
