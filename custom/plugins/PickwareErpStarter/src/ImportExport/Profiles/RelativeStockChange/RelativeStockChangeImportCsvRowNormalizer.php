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

use Pickware\PickwareErpStarter\StockApi\StockLocationReferenceFinder;

class RelativeStockChangeImportCsvRowNormalizer
{
    public function normalizeRow(array $row): array
    {
        $row = array_combine(
            array_map('trim', array_keys($row)),
            array_map('trim', array_values($row))
        );

        $row = $this->mapTranslations($row);
        $row = $this->mapTypes($row);

        return $row;
    }

    public function normalizeColumnNames(array $columnNames): array
    {
        return array_values(array_filter(array_map(function (string $columnName) {
            return $this->normalizeColumnName($columnName);
        }, $columnNames)));
    }

    /**
     * @param string[] List of the original column names
     * @return string[][] Mapping [normalized column name] => [original column names[]]
     */
    public function mapNormalizedToOriginalColumnNames(array $originalColumnNames): array
    {
        $mapping = [];
        foreach ($originalColumnNames as $originalColumnName) {
            $normalizedColumnName = $this->normalizeColumnName($originalColumnName);
            if ($normalizedColumnName === null) {
                continue;
            }
            if (!isset($mapping[$normalizedColumnName])) {
                $mapping[$normalizedColumnName] = [];
            }
            if (!in_array($originalColumnName, $mapping[$normalizedColumnName], true)) {
                $mapping[$normalizedColumnName][] = $originalColumnName;
            }
        }

        return $mapping;
    }

    private function normalizeColumnName(string $columnName): ?string
    {
        switch (mb_strtolower(trim($columnName))) {
            case 'produktnummer':
            case 'product number':
                return 'productNumber';
            case 'lagerplatz':
            case 'bin location':
                return 'binLocationCode';
            case 'warehouse code':
            case 'lagerkürzel':
                return 'warehouseCode';
            case 'lager':
            case 'warehouse':
                return 'warehouseName';
            case 'change':
            case 'änderung':
                return 'change';
            default:
                return null;
        }
    }

    private function mapTranslations(array $row): array
    {
        $row = array_combine(
            array_map(function (string $columnName) {
                return $this->normalizeColumnName($columnName);
            }, array_keys($row)),
            array_values($row)
        );
        unset($row['']);

        if (isset($row['binLocationCode'])
            && in_array(mb_strtolower($row['binLocationCode']), ['unknown', 'unbekannt'])
        ) {
            $row['binLocationCode'] = StockLocationReferenceFinder::BIN_LOCATION_CODE_UNKNOWN;
        }

        return $row;
    }

    private function mapTypes(array $row): array
    {
        if (isset($row['change']) && self::isIntegerString($row['change'])) {
            $row['change'] = (int)$row['change'];
        }

        return $row;
    }

    private static function isIntegerString(string $string): bool
    {
        return preg_match('/^[+-]?\\d+$/', $string) === 1;
    }
}
