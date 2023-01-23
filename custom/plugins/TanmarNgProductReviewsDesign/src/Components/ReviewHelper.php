<?php

declare(strict_types=1);

namespace Tanmar\ProductReviewsDesign\Components;

use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;

class ReviewHelper {

    private $reviews = array();
    private $goodVsBadActive = false;
    private $minStrlen = false;
    private $minReviews = false;
    private $minPointsBest = false;
    private $maxPointsWorst = false;
    private $bestReview;
    private $worstReview;
    private $totalCount;

    /**
     * 
     * @param array $reviews
     * @param Config $config
     */
    public function __construct(array $reviews, $config) {
        $this->reviews = $reviews;
        $this->totalCount = count($reviews);
        $this->goodVsBadActive = $config->isGoodVsBadActive();
        $this->minStrlen = $config->getGoodVsBadMinStrlen();
        $this->minReviews = $config->getGoodVsBadMinReviews();
        $this->minPointsBest = $config->getGoodVsBadMinPointsBest();
        $this->maxPointsWorst = $config->getGoodVsBadMaxPointsWorst();
        $this->findBestAndWorst();
    }

    /**
     * 
     * @return ProductReviewEntity
     */
    public function getBestReview(): ?ProductReviewEntity {
        return $this->bestReview;
    }

    /**
     * 
     * @return ProductReviewEntity
     */
    public function getWorstReview(): ?ProductReviewEntity {
        return $this->worstReview;
    }

    /**
     * 
     * @return void
     */
    private function findBestAndWorst(): void {
        $bestReview = null;
        $worstReview = null;
        if ($this->goodVsBadActive && $this->hasReviews() && $this->isReviewsCoundValid()) {
            foreach ($this->reviews as $review) {
                if ($review->getStatus() && $this->isLongEnough($review) && (!$bestReview || $this->hasMorePoints($bestReview, $review) || $this->samePointsButLongerComment($bestReview, $review))) {
                    $bestReview = $review;
                }
                if ($review->getStatus() && $this->isLongEnough($review) && (!$worstReview || $this->hasLessPoints($worstReview, $review) || $this->samePointsButLongerComment($worstReview, $review))) {
                    $worstReview = $review;
                }
            }
            if (!$this->isBestValid($bestReview)) {
                $bestReview = null;
            }
            if (!$this->isWorstValid($worstReview)) {
                $worstReview = null;
            }
            if (!$this->hasDifferentPoints($bestReview, $worstReview)) {
                $worstReview = null;
            }
        }
        
        $this->bestReview = $bestReview;
        $this->worstReview = $worstReview;
    }

    /**
     * 
     * @return bool
     */
    public function hasReviews(): bool {
        return isset($this->reviews) && $this->reviews && is_array($this->reviews) && $this->totalCount > 0;
    }

    /**
     * 
     * @return bool
     */
    private function isReviewsCoundValid(): bool {
        return (!$this->minReviews || (count($this->reviews) >= ($this->minReviews)));
    }

    /**
     * 
     * @param ProductReviewEntity $review
     * @return bool
     */
    private function isLongEnough(?ProductReviewEntity $review): bool {
        return (!$this->minStrlen || (!is_null($review) && !is_null($review->getContent()) && (strlen($review->getContent()) >= $this->minStrlen)));
    }

    /**
     * 
     * @param ProductReviewEntity $bestReview
     * @return bool
     */
    private function isBestValid(?ProductReviewEntity $bestReview): bool {
        return (!$this->minPointsBest || ($bestReview && !is_null($bestReview->getPoints()) && ($bestReview->getPoints() >= $this->minPointsBest)));
    }

    /**
     * 
     * @param ProductReviewEntity $worstReview
     * @return bool
     */
    private function isWorstValid(?ProductReviewEntity $worstReview): bool {
        return (!$this->maxPointsWorst || ($worstReview && !is_null($worstReview->getPoints()) && ($worstReview->getPoints() <= $this->maxPointsWorst)));
    }

    /**
     * 
     * @param ProductReviewEntity $bestReview
     * @param ProductReviewEntity $worstReview
     * @return bool
     */
    private function hasDifferentPoints(?ProductReviewEntity $bestReview, ?ProductReviewEntity $worstReview): bool {
        return ($bestReview && $worstReview && !is_null($bestReview->getPoints()) && !is_null($worstReview->getPoints()) && ($bestReview->getPoints() > $worstReview->getPoints()));
    }

    /**
     * 
     * @param ProductReviewEntity $bestReview
     * @param ProductReviewEntity $review
     * @return bool
     */
    private function hasMorePoints(?ProductReviewEntity $bestReview, ?ProductReviewEntity $review): bool {
        return ($bestReview && $review && !is_null($bestReview->getPoints()) && !is_null($review->getPoints()) && ($bestReview->getPoints() < $review->getPoints()));
    }

    /**
     * 
     * @param ProductReviewEntity $worstReview
     * @param ProductReviewEntity $review
     * @return bool
     */
    private function hasLessPoints(?ProductReviewEntity $worstReview, ?ProductReviewEntity $review): bool {
        return ($worstReview && $review && !is_null($worstReview->getPoints()) && !is_null($review->getPoints()) && ($worstReview->getPoints() > $review->getPoints()));
    }

    /**
     * 
     * @param ProductReviewEntity $currentReview
     * @param ProductReviewEntity $review
     * @return bool
     */
    private function samePointsButLongerComment(?ProductReviewEntity $currentReview, ?ProductReviewEntity $review): bool {
        return ($currentReview && $review && !is_null($currentReview->getPoints()) && !is_null($review->getPoints()) && !is_null($currentReview->getContent()) && !is_null($review->getContent()) && ($currentReview->getPoints() == $review->getPoints()) && (strlen($currentReview->getContent()) < strlen($review->getContent())));
    }

}
