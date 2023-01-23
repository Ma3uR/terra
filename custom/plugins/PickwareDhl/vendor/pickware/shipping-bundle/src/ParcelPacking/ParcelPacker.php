<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelPacking;

use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\ParcelPacking\BinPacking\WeightBasedFirstFitDecreasingBinPacker;

class ParcelPacker
{
    /**
     * @return Parcel[]
     */
    public function repackParcel(Parcel $parcel, ParcelPackingConfiguration $weightConfiguration): array
    {
        $parcel->setFillerWeight($weightConfiguration->getFillerWeightPerParcel());

        if ($parcel->getTotalWeight() === null) {
            if ($weightConfiguration->getFallbackParcelWeight()) {
                $parcel->setWeightOverwrite($weightConfiguration->getFallbackParcelWeight());
            }

            return [$parcel];
        }

        $maxParcelWeight = $weightConfiguration->getMaxParcelWeight();
        if ($maxParcelWeight === null) {
            return [$parcel];
        }

        $binPacker = $this->createBinPacker();
        $bins = $binPacker->packIntoBins($parcel->getItems(), [
            'binCapacity' => $maxParcelWeight->subtract($weightConfiguration->getFillerWeightPerParcel()),
        ]);

        $parcels = [];
        foreach ($bins as $bin) {
            $subParcel = $parcel->createCopyWithoutItems();
            $subParcel->setItems($bin);
            $parcels[] = $subParcel;
        }

        return $parcels;
    }

    protected function createBinPacker(): WeightBasedFirstFitDecreasingBinPacker
    {
        return new WeightBasedFirstFitDecreasingBinPacker();
    }
}
