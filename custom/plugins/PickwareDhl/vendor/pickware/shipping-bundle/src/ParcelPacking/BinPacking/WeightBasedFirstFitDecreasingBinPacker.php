<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelPacking\BinPacking;

use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\Parcel\ParcelItem;

/**
 * Splits the contents of a parcel into multiple parcels based on the "first fit decreasing" packaging algorithm.
 *
 * Algorithm steps:
 * A) Sort the items descending by weight
 * B) Insert the items one after the other, so that each is placed in the first bin where there is still enough room.
 * C) If there is not enough space in any of the already opened bins, open a new one.
 */
class WeightBasedFirstFitDecreasingBinPacker implements BinPacker
{
    /**
     * @param ParcelItem[] $itemsToDistribute
     * @param array $configuration
     * @return ParcelItem[][]
     * @throws BinPackingException
     */
    public function packIntoBins(array $itemsToDistribute, array $configuration): array
    {
        // A)
        self::sortItemsDescendingByWeight($itemsToDistribute);

        /** @var Parcel[] $parcels */
        $parcels = [];
        while (count($itemsToDistribute) !== 0) {
            $itemToDistribute = array_shift($itemsToDistribute);

            if ($itemToDistribute->getUnitWeight()->isGreaterThan($configuration['binCapacity'])) {
                throw new BinPackingException(sprintf(
                    'The item %s with a weight of %.3f kg is heavier than the maximum configured bin capacity ' .
                    'of %.3f kg. Therefore the item could not be put in any bin.',
                    $itemToDistribute->getName(),
                    $itemToDistribute->getUnitWeight()->convertTo('kg'),
                    $configuration['binCapacity']->convertTo('kg')
                ));
            }

            // B)
            foreach ($parcels as $parcel) {
                $availableWeight = $configuration['binCapacity']->subtract($parcel->getTotalWeight());
                $maxQuantityToPutIn = (int) floor($availableWeight->divideBy($itemToDistribute->getUnitWeight()));
                $quantityToPutIn = min($itemToDistribute->getQuantity(), $maxQuantityToPutIn);
                if ($quantityToPutIn > 0) {
                    $itemToDistribute->setQuantity($itemToDistribute->getQuantity() - $quantityToPutIn);
                    $itemInParcel = clone $itemToDistribute;
                    $parcel->addItem($itemInParcel);
                    $itemInParcel->setQuantity($quantityToPutIn);
                }
            }

            // C)
            if ($itemToDistribute->getQuantity() > 0) {
                $parcels[] = new Parcel();
                array_unshift($itemsToDistribute, $itemToDistribute);
            }
        }

        return array_map(function (Parcel $parcel) {
            return $parcel->getItems();
        }, $parcels);
    }

    /**
     * @param ParcelItem[] $items
     */
    private static function sortItemsDescendingByWeight(array &$items): void
    {
        usort($items, function (ParcelItem $a, ParcelItem $b) {
            return -1 * $a->getUnitWeight()->compareTo($b->getUnitWeight());
        });
    }
}
