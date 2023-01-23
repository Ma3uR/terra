<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport\Profiles\RelativeStockChange;

use Pickware\PickwareErpStarter\ImportExport\CsvErrorFactory;
use Pickware\PickwareErpStarter\ImportExport\Importer;
use Pickware\PickwareErpStarter\ImportExport\ImportExportStateService;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportElementCollection;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportElementDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportElementEntity;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportDefinition;
use Pickware\PickwareErpStarter\ImportExport\Model\ImportExportEntity;
use Pickware\PickwareErpStarter\Picking\PickingRequestService;
use Pickware\PickwareErpStarter\Stock\Model\StockDefinition;
use Pickware\PickwareErpStarter\StockApi\StockLocationReference;
use Pickware\PickwareErpStarter\StockApi\StockLocationReferenceFinder;
use Pickware\PickwareErpStarter\StockApi\StockMovement;
use Pickware\PickwareErpStarter\StockApi\StockMovementService;
use Pickware\PickwareErpStarter\StockApi\StockMovementServiceValidationException;
use Pickware\PickwareErpStarter\Stocking\ProductQuantity;
use Pickware\PickwareErpStarter\Stocking\StockingRequest;
use Pickware\PickwareErpStarter\Stocking\StockingStrategy;
use Pickware\DalBundle\EntityManager;
use Pickware\HttpUtils\JsonApi\JsonApiError;
use Pickware\HttpUtils\JsonApi\JsonApiErrors;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class RelativeStockChangeImporter implements Importer
{
    private const MANDATORY_COLUMNS = [
        'change',
        'productNumber',
    ];

    private const ERROR_CODE_NAMESPACE = 'PICKWARE_ERP__RELATIVE_STOCK_CHANGE_IMPORTER__';

    private const ERROR_CODE_STOCK_LOCATION_NOT_FOUND = self::ERROR_CODE_NAMESPACE . 'STOCK_LOCATION_NOT_FOUND';
    private const ERROR_CODE_PRODUCT_NOT_FOUND = self::ERROR_CODE_NAMESPACE . 'PRODUCT_NOT_FOUND';
    private const ERROR_CODE_WAREHOUSE_FOR_BIN_LOCATION_MISSING = self::ERROR_CODE_NAMESPACE . 'WAREHOUSE_FOR_BIN_LOCATION_MISSING';
    private const ERROR_CODE_UNSUPPORTED_STOCK_LOCATION = self::ERROR_CODE_NAMESPACE . 'UNSUPPORTED_STOCK_LOCATION';
    private const ERROR_CODE_NOT_ENOUGH_STOCK = self::ERROR_CODE_NAMESPACE . 'NOT_ENOUGH_STOCK';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var StockMovementService
     */
    private $stockMovementService;

    /**
     * @var RelativeStockChangeImportCsvRowNormalizer
     */
    private $normalizer;

    /**
     * @var StockLocationReferenceFinder
     */
    private $stockLocationReferenceFinder;

    /**
     * @var ImportExportStateService
     */
    private $importExportStateService;

    /**
     * @var PickingRequestService
     */
    private $pickingRequestService;

    /**
     * @var StockingStrategy
     */
    private $stockingStrategy;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        EntityManager $entityManager,
        StockMovementService $stockMovementService,
        RelativeStockChangeImportCsvRowNormalizer $normalizer,
        StockLocationReferenceFinder $stockLocationReferenceFinder,
        ImportExportStateService $importExportStateService,
        PickingRequestService $pickingRequestService,
        StockingStrategy $stockingStrategy,
        int $batchSize
    ) {
        $this->entityManager = $entityManager;
        $this->stockMovementService = $stockMovementService;
        $this->normalizer = $normalizer;
        $this->stockLocationReferenceFinder = $stockLocationReferenceFinder;
        $this->importExportStateService = $importExportStateService;
        $this->pickingRequestService = $pickingRequestService;
        $this->stockingStrategy = $stockingStrategy;
        $this->batchSize = $batchSize;
    }

    public function validateHeaderRow(array $headerRow, Context $context): JsonApiErrors
    {
        $errors = new JsonApiErrors();
        $actualColumns = $this->normalizer->normalizeColumnNames($headerRow);
        if (count($actualColumns) === 0) {
            $errors->addError(CsvErrorFactory::missingHeaderRow());

            return $errors;
        }

        $missingColumns = array_values(array_diff(self::MANDATORY_COLUMNS, $actualColumns));
        foreach ($missingColumns as $missingColumn) {
            $errors->addError(CsvErrorFactory::missingColumn($missingColumn));
        }
        if (in_array('binLocationCode', $actualColumns, true)
            && !in_array('warehouseCode', $actualColumns, true)
            && !in_array('warehouseName', $actualColumns, true)
        ) {
            $errors->addError(self::createWarehouseForBinLocationMissing());
        }

        $columnCounts = array_count_values($actualColumns);
        $normalizedToOriginalColumnNameMapping = $this->normalizer->mapNormalizedToOriginalColumnNames($headerRow);
        foreach ($columnCounts as $normalizedColumnName => $columnCount) {
            if ($columnCount === 1) {
                continue;
            }
            $errors->addError(CsvErrorFactory::duplicatedColumns(
                $normalizedColumnName,
                $normalizedToOriginalColumnNameMapping[$normalizedColumnName]
            ));
        }

        return $errors;
    }

    public function importChunk(string $importId, int $nextLineToRead, Context $context): ?int
    {
        /** @var ImportExportEntity $import */
        $import = $this->entityManager->findByPrimaryKey(ImportExportDefinition::class, $importId, $context);

        $criteria = EntityManager::createCriteriaFromArray(['importExportId' => $importId]);
        $criteria->addFilter(new RangeFilter('rowNumber', [
            RangeFilter::GTE => $nextLineToRead,
            RangeFilter::LT => $nextLineToRead + $this->batchSize,
        ]));

        /** @var ImportExportElementCollection $importElements */
        $importElements = $this->entityManager->findBy(
            ImportExportElementDefinition::class,
            $criteria,
            $context
        );
        if ($importElements->count() === 0) {
            return null;
        }

        $normalizedRows = $importElements->map(function (ImportExportElementEntity $importElement) {
            return $this->normalizer->normalizeRow($importElement->getRowData());
        });
        $productNumberIdMapping = $this->getProductNumberIdMapping($normalizedRows, $context);
        // Mapping: normalizedColumnName => originalColumnName
        $normalizedToOriginalColumnNameMapping = $this->normalizer->mapNormalizedToOriginalColumnNames(array_keys(
            $importElements->first()->getRowData()
        ));

        foreach ($importElements->getElements() as $index => $importElement) {
            $normalizedRow = $normalizedRows[$index];

            $errors = $this->validateRowSchema($normalizedRow, $normalizedToOriginalColumnNameMapping);
            if (count($errors)) {
                $this->importExportStateService->failImportExportElement($importElement->getId(), $errors, $context);
                continue;
            }

            if ($normalizedRow['change'] === 0) {
                continue;
            }

            $productId = $productNumberIdMapping[mb_strtolower($normalizedRow['productNumber'])] ?? null;
            if (!$productId) {
                $errors->addError(self::createProductNotFoundError($normalizedRow['productNumber']));
            }
            $location = $this->stockLocationReferenceFinder->findStockLocationReference([
                'binLocationCode' => $normalizedRow['binLocationCode'] ?? null,
                'warehouseCode' => $normalizedRow['warehouseCode'] ?? null,
                'warehouseName' => $normalizedRow['warehouseName'] ?? null,
            ], $context);
            if (!$location) {
                $errors->addError(self::createStockLocationNotFoundError());
            }

            if (count($errors)) {
                $this->importExportStateService->failImportExportElement($importElement->getId(), $errors, $context);
                continue;
            }

            if ($location['type'] === StockLocationReferenceFinder::TYPE_WAREHOUSES) {
                $this->entityManager->transactional(
                    $context,
                    function () use ($location, $errors, $context, $import, $normalizedRow, $productId) {
                        $this->lockProductStocks(
                            $productId,
                            $context
                        );

                        if ($normalizedRow['change'] > 0) {
                            $stockingRequest = new StockingRequest(
                                [new ProductQuantity($productId, $normalizedRow['change'])],
                                $location['warehouseIds'][0] ?? null
                            );
                            $stockingSolution = $this->stockingStrategy->calculateStockingSolution(
                                $stockingRequest,
                                $context
                            );
                            $stockMovements = $stockingSolution->createStockMovementsWithSource(
                                StockLocationReference::import(),
                                [
                                    'userId' => $import->getUserId(),
                                ]
                            );
                            $this->stockMovementService->moveStock($stockMovements, $context);
                        } else {
                            $pickingRequest = $this->pickingRequestService->createPickingRequestForProducts(
                                [
                                    $productId => -1 * $normalizedRow['change'],
                                ],
                                $location['warehouseIds'],
                                $context
                            );
                            if ($pickingRequest->isCompletelyPickable()) {
                                $stockMovements = $pickingRequest->createStockMovementsWithDestination(
                                    StockLocationReference::import()
                                );
                                $this->stockMovementService->moveStock($stockMovements, $context);
                            } else {
                                $errors->addError(self::createNotEnoughStockToPickError());
                            }
                        }
                    }
                );
            } elseif ($location['type'] === StockLocationReferenceFinder::TYPE_SPECIFIC_LOCATION) {
                $stockMovement = StockMovement::create([
                    'productId' => $productId,
                    'source' => StockLocationReference::import(),
                    'destination' => $location['stockLocationReference'],
                    'quantity' => $normalizedRow['change'],
                    'userId' => $import->getUserId(),
                ]);
                try {
                    $this->stockMovementService->moveStock([$stockMovement], $context);
                } catch (StockMovementServiceValidationException $e) {
                    $errors->addError($e->serializeToJsonApiError());
                }
            } else {
                $errors->addError(self::createUnsupportedStockLocationError());
            }

            if (count($errors)) {
                $this->importExportStateService->failImportExportElement($importElement->getId(), $errors, $context);
                continue;
            }
        }

        $nextLineToRead += $this->batchSize;

        return $nextLineToRead;
    }

    private function getProductNumberIdMapping(array $normalizedRows, Context $context): array
    {
        // This is done via SQL instead of DAL for performance reasons.
        $productNumbers = array_column($normalizedRows, 'productNumber');
        /** @var ProductCollection $products */
        $products = $this->entityManager->findBy(ProductDefinition::class, [
            'productNumber' => $productNumbers,
        ], $context);

        $productNumbers = $products->map(function (ProductEntity $product) {
            return mb_strtolower($product->getProductNumber());
        });

        return array_combine($productNumbers, $products->getKeys());
    }

    private function validateRowSchema(array $normalizedRow, array $normalizedToOriginalColumnNameMapping): JsonApiErrors
    {
        $errors = new JsonApiErrors();
        foreach (self::MANDATORY_COLUMNS as $mandatoryColumn) {
            if ($normalizedRow[$mandatoryColumn] === '') {
                $errors->addError(CsvErrorFactory::missingCellValue(
                    $mandatoryColumn,
                    $normalizedToOriginalColumnNameMapping[$mandatoryColumn][0]
                ));
            }
        }
        if (!is_int($normalizedRow['change']) && $normalizedRow['change'] !== '') {
            $errors->addError(CsvErrorFactory::invalidCellValue(
                'change',
                $normalizedToOriginalColumnNameMapping['change'][0]
            ));
        }
        if (isset($normalizedRow['binLocationCode'])
            && !isset($normalizedRow['warehouseCode'])
            && !isset($normalizedRow['warehouseName'])
        ) {
            $errors->addError(self::createWarehouseForBinLocationMissing());
        }

        return $errors;
    }

    private static function createUnsupportedStockLocationError(): JsonApiError
    {
        return new JsonApiError([
            'code' => self::ERROR_CODE_UNSUPPORTED_STOCK_LOCATION,
            'title' => 'Stock location not supported',
            'detail' => 'This stock location is not supported.',
        ]);
    }

    private static function createStockLocationNotFoundError(): JsonApiError
    {
        return new JsonApiError([
            'code' => self::ERROR_CODE_STOCK_LOCATION_NOT_FOUND,
            'title' => 'Stock location not found',
            'detail' => 'This stock location could not be found.',
        ]);
    }

    private static function createProductNotFoundError(string $productNumber): JsonApiError
    {
        return new JsonApiError([
            'code' => self::ERROR_CODE_PRODUCT_NOT_FOUND,
            'title' => 'Product not found',
            'detail' => sprintf('The product with the number "%s" could not be found.', $productNumber),
            'meta' => [
                'productNumber' => $productNumber,
            ],
        ]);
    }

    private static function createWarehouseForBinLocationMissing(): JsonApiError
    {
        return new JsonApiError([
            'code' => self::ERROR_CODE_WAREHOUSE_FOR_BIN_LOCATION_MISSING,
            'title' => 'Warehouse for bin location missing',
            'detail' => 'A bin location cannot be specified without a warehouse.',
        ]);
    }

    private function lockProductStocks(string $productId, Context $context): void
    {
        $this->entityManager->lockPessimistically(StockDefinition::class, ['productId' => $productId], $context);
    }

    private static function createNotEnoughStockToPickError(): JsonApiError
    {
        return new JsonApiError([
            'code' => self::ERROR_CODE_NOT_ENOUGH_STOCK,
            'title' => 'Not enough stock to pick',
            'detail' => 'Not enough stock to pick.',
        ]);
    }
}
