<?php declare(strict_types=1);

namespace TerraAustralia\Struct;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Struct\Struct;

class TrProductsStruct extends Struct
{
    /**
     * @var ProductCollection|null
     */
    protected $products;

    public function getProducts(): ?ProductCollection
    {
        return $this->products;
    }

    public function setProducts(ProductCollection $products): void
    {
        $this->products = $products;
    }

    public function getApiAlias(): string
    {
        return 'cms_tr_e_products';
    }
}
