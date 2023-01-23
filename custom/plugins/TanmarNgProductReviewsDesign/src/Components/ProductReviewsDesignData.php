<?php

declare(strict_types=1);

namespace Tanmar\ProductReviewsDesign\Components;

use Shopware\Core\Framework\Struct\Struct;

class ProductReviewsDesignData extends Struct {

    /**
     * @var bool
     */
    protected $active;
    /**
     * @var bool 
     */
    protected $postCommentActive;
    /**
     * @var bool 
     */
    protected $goodVsBadActive;
    /**
     * @var int 
     */
    protected $readMoreCounter;

    public function __construct() {
        $this->active = false;
        $this->postCommentActive = false;
        $this->goodVsBadActive = false;
        $this->readMoreCounter = 0;
    }

    /**
     * 
     * @return bool
     */
    public function getActive(): bool {
        return $this->active;
    }

    /**
     * 
     * @return bool
     */
    public function getPostCommentActive(): bool {
        return $this->postCommentActive;
    }

    /**
     * 
     * @return bool
     */
    public function getGoodVsBadActive(): bool {
        return $this->goodVsBadActive;
    }

    /**
     * 
     * @return int
     */
    public function getReadMoreCounter(): int {
        return $this->readMoreCounter;
    }

}
