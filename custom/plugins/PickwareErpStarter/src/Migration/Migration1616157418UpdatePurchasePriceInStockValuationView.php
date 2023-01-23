<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1616157418UpdatePurchasePriceInStockValuationView extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1616157418;
    }

    public function update(Connection $connection): void
    {
        // This is a copy of the SQL View creation code from Migration1606220870CreateStockValuationView except for
        // variants it chooses the purchase price string of the parent product instead of null
        $selectParentOrVariantPurchasePriceJson = 'COALESCE(product.purchase_prices, parentProduct.purchase_prices)';

        $defaultCurrencyKey = 'c' . Defaults::CURRENCY;

        $extractPurchasePriceString = 'COALESCE(
            JSON_UNQUOTE(JSON_EXTRACT(' . $selectParentOrVariantPurchasePriceJson . ', "$.' . $defaultCurrencyKey . '.%1$s")),
            JSON_UNQUOTE(JSON_EXTRACT(
                ' . $selectParentOrVariantPurchasePriceJson . ',
                CONCAT("$.", JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(' . $selectParentOrVariantPurchasePriceJson . '), "$[0]")), ".%1$s")
            ))
        )';
        $extractPurchasePriceCurrencyString = 'UNHEX(IF(
            JSON_EXTRACT(' . $selectParentOrVariantPurchasePriceJson . ', "$.' . $defaultCurrencyKey . '") IS NOT NULL,
            "' . Defaults::CURRENCY . '",
            SUBSTR(JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(' . $selectParentOrVariantPurchasePriceJson . '), "$[0]")), 2)
        ))';

        $connection->executeStatement(
            'CREATE OR REPLACE VIEW pickware_erp_stock_valuation_view AS
                SELECT
                    warehouseStock.id AS id,
                    warehouseStock.id AS warehouse_stock_id,
                    product.id AS product_id,
                    product.version_id AS product_version_id,
                    currency.id AS currency_id,

                    ROUND(' . sprintf($extractPurchasePriceString, 'net') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) AS purchase_price_net,
                    ROUND(' . sprintf($extractPurchasePriceString, 'gross') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) AS purchase_price_gross,
                    -- Calculate the stock valuation with the rounded purchase price so the user can replicate this
                    -- result manually within this view.
                    warehouseStock.quantity * ROUND(' . sprintf($extractPurchasePriceString, 'net') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) AS stock_valuation_net,
                    warehouseStock.quantity * ROUND(' . sprintf($extractPurchasePriceString, 'gross') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) AS stock_valuation_gross,

                    -- Use the rounded purchase price when converting it to the default currency so the user can
                    -- replicate the results manually within this view.
                    ROUND(ROUND(' . sprintf($extractPurchasePriceString, 'net') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) * currency.factor, JSON_UNQUOTE(JSON_EXTRACT(defaultCurrency.item_rounding, "$.decimals"))) AS purchase_price_net_in_default_currency,
                    ROUND(ROUND(' . sprintf($extractPurchasePriceString, 'gross') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) * currency.factor, JSON_UNQUOTE(JSON_EXTRACT(defaultCurrency.item_rounding, "$.decimals"))) AS purchase_price_gross_in_default_currency,
                    -- Calculate the stock valuation (in default currency) with the rounded purchase price (in default
                    -- currency) so the user can replicate this result manually within this view.
                    warehouseStock.quantity * ROUND(ROUND(' . sprintf($extractPurchasePriceString, 'net') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) * currency.factor, JSON_UNQUOTE(JSON_EXTRACT(defaultCurrency.item_rounding, "$.decimals"))) AS stock_valuation_net_in_default_currency,
                    warehouseStock.quantity * ROUND(ROUND(' . sprintf($extractPurchasePriceString, 'gross') . ', JSON_UNQUOTE(JSON_EXTRACT(currency.item_rounding, "$.decimals"))) * currency.factor, JSON_UNQUOTE(JSON_EXTRACT(defaultCurrency.item_rounding, "$.decimals"))) AS stock_valuation_gross_in_default_currency,

                    NOW() as updated_at,
                    NOW() as created_at

                FROM pickware_erp_warehouse_stock warehouseStock
                INNER JOIN product ON product.id = warehouseStock.product_id AND product.version_id = warehouseStock.product_version_id
                LEFT JOIN product parentProduct ON product.parent_id = parentProduct.id
                LEFT JOIN currency ON currency.id = ' . $extractPurchasePriceCurrencyString . '
                LEFT JOIN currency defaultCurrency ON defaultCurrency.id = UNHEX(:defaultCurrencyId)',
            [
                'defaultCurrencyId' => Defaults::CURRENCY,
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
