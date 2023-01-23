<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Components;

use Shopware\Core\Framework\Struct\Struct;

class TanmarProductReviewsData extends Struct {

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var array
     */
    protected $data;

    public function __construct() {
        $this->active = false;
        $this->data = [];
    }

    public function getActive(): bool {
        return $this->active;
    }

    public function getData(): array {
        return $this->data;
    }

}
