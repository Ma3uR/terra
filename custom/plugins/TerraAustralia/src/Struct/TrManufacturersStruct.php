<?php declare(strict_types=1);

namespace TerraAustralia\Struct;

use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection;
use Shopware\Core\Framework\Struct\Struct;

class TrManufacturersStruct extends Struct
{
    /**
     * @var ProductManufacturerCollection|null
     */
    protected $manufacturers;

    public function getManufacturers(): ?ProductManufacturerCollection
    {
        return $this->manufacturers;
    }

    public function setManufacturers(ProductManufacturerCollection $manufacturers): void
    {
        $this->manufacturers = $manufacturers;
    }

    public function getApiAlias(): string
    {
        return 'cms_tr_e_manufacturers';
    }
}
