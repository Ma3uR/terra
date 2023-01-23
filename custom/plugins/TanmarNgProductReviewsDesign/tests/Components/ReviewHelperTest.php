<?php

declare(strict_types=1);

namespace Tanmar\ProductReviewsDesign\Test;

use PHPUnit\Framework\TestCase;
use Tanmar\ProductReviewsDesign\Components\Config;
use Tanmar\ProductReviewsDesign\Components\ReviewHelper;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * Description of ReviewHelperTest
 *
 * @author Laura
 */
class ReviewHelperTest extends TestCase {

    use IntegrationTestBehaviour;

    const REVIEW_CONTENT_50 = 'Lorem ipsum dolor sit amet, consetetur sadipscinge';
    const REVIEW_CONTENT_100 = 'Lorem ipsum dolor sit amet, consetetur sadipscingeelitr, sed diam nonumy eirmod tempor invidunt ut l';
    const REVIEW_POINTS_5 = 5.0;
    const REVIEW_POINTS_4 = 4.0;
    const REVIEW_POINTS_3 = 3.0;
    const REVIEW_POINTS_2 = 2.0;
    const REVIEW_POINTS_1 = 1.0;

    public function testValidBestAndWorst(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase1(), $this->prepareConfig(true, 50, 5, 4, 2));
        $bestReview = $reviewHelper->getBestReview();
        static::assertSame(self::REVIEW_POINTS_4, $bestReview->getPoints());
        static::assertSame(self::REVIEW_CONTENT_100, $bestReview->getContent());
        $worstReview = $reviewHelper->getWorstReview();
        static::assertSame(self::REVIEW_POINTS_2, $worstReview->getPoints());
        static::assertSame(self::REVIEW_CONTENT_100, $worstReview->getContent());
    }

    public function testNoValidDueConfig(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase1(), $this->prepareConfig(false, 50, 5, 4, 2));
        $bestReview = $reviewHelper->getBestReview();
        static::assertNull($bestReview);
        $worstReview = $reviewHelper->getWorstReview();
        static::assertNull($worstReview);
    }

    public function testNoValidDueToLength(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase1(), $this->prepareConfig(true, 101, 5, 4, 2));
        $bestReview = $reviewHelper->getBestReview();
        static::assertNull($bestReview);
        $worstReview = $reviewHelper->getWorstReview();
        static::assertNull($worstReview);
    }

    public function testNoValidDueMinReviews(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase1(), $this->prepareConfig(true, 50, 6, 4, 2));
        $bestReview = $reviewHelper->getBestReview();
        static::assertNull($bestReview);
        $worstReview = $reviewHelper->getWorstReview();
        static::assertNull($worstReview);
    }

    /**
     * no valid best review, because there is no review with min points
     * also no valid worst review, cause no valid best review found
     * @return void
     */
    public function testNoValidDueToMinStars(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase1(), $this->prepareConfig(true, 50, 5, 5, 2));
        $bestReview = $reviewHelper->getBestReview();
        static::assertNull($bestReview);
        $worstReview = $reviewHelper->getWorstReview();
        static::assertNull($worstReview);
    }
    
    public function testNoValidWorstDueToMaxStars(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase1(), $this->prepareConfig(true, 50, 5, 4, 1));
        $bestReview = $reviewHelper->getBestReview();
        static::assertSame(self::REVIEW_POINTS_4, $bestReview->getPoints());
        static::assertSame(self::REVIEW_CONTENT_100, $bestReview->getContent());
        $worstReview = $reviewHelper->getWorstReview();
        static::assertNull($worstReview);
    }

    public function testNoValidWorstDueToLength(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase2(), $this->prepareConfig(true, 100, 5, 4, 2));
        $bestReview = $reviewHelper->getBestReview();
        static::assertSame(self::REVIEW_POINTS_4, $bestReview->getPoints());
        static::assertSame(self::REVIEW_CONTENT_100, $bestReview->getContent());
        $worstReview = $reviewHelper->getWorstReview();
        static::assertNull($worstReview);
    }
    
    /**
     * no valid best review, because there is no review with min length
     * also no valid worst review, cause no valid best review found
     * @return void
     */
    public function testNoValidBestDueToLength(): void {
        $reviewHelper = new ReviewHelper($this->prepareTestcase3(), $this->prepareConfig(true, 100, 5, 4, 2));
        $bestReview = $reviewHelper->getBestReview();
        static::assertNull($bestReview);
        $worstReview = $reviewHelper->getWorstReview();
        static::assertNull($worstReview);
    }
    
    /**
     * reviews: 5
     * max stars: 4
     * min stars: 2
     * best length 50 and 100
     * worst length 50 and 100
     * @return array
     */
    private function prepareTestcase1(): array {
        $reviews = array(
            $this->prepareProductReview(self::REVIEW_POINTS_4, self::REVIEW_CONTENT_100),
            $this->prepareProductReview(self::REVIEW_POINTS_4, self::REVIEW_CONTENT_50),
            $this->prepareProductReview(self::REVIEW_POINTS_3, self::REVIEW_CONTENT_100),
            $this->prepareProductReview(self::REVIEW_POINTS_2, self::REVIEW_CONTENT_100),
            $this->prepareProductReview(self::REVIEW_POINTS_2, self::REVIEW_CONTENT_50),
        );
        return $reviews;
    }
    
    /**
     * reviews: 5
     * max stars: 4
     * min stars: 2
     * best length 50 and 100
     * worst length 50
     * @return array
     */
    private function prepareTestcase2(): array {
        $reviews = array(
            $this->prepareProductReview(self::REVIEW_POINTS_4, self::REVIEW_CONTENT_100),
            $this->prepareProductReview(self::REVIEW_POINTS_4, self::REVIEW_CONTENT_50),
            $this->prepareProductReview(self::REVIEW_POINTS_3, self::REVIEW_CONTENT_100),
            $this->prepareProductReview(self::REVIEW_POINTS_2, self::REVIEW_CONTENT_50),
            $this->prepareProductReview(self::REVIEW_POINTS_2, self::REVIEW_CONTENT_50),
        );
        return $reviews;
    }
    
    /**
     * reviews: 5
     * max stars: 4
     * min stars: 2
     * best length 50
     * worst length 50 and 100
     * @return array
     */
    private function prepareTestcase3(): array {
        $reviews = array(
            $this->prepareProductReview(self::REVIEW_POINTS_4, self::REVIEW_CONTENT_50),
            $this->prepareProductReview(self::REVIEW_POINTS_4, self::REVIEW_CONTENT_50),
            $this->prepareProductReview(self::REVIEW_POINTS_3, self::REVIEW_CONTENT_100),
            $this->prepareProductReview(self::REVIEW_POINTS_2, self::REVIEW_CONTENT_100),
            $this->prepareProductReview(self::REVIEW_POINTS_2, self::REVIEW_CONTENT_50),
        );
        return $reviews;
    }

    private function prepareProductReview(float $points, string $length) {
        $review = new ProductReviewEntity();
        $review->assign([
            'points' => $points,
            'content' => $length
        ]);
        return $review;
    }

    private function prepareConfig($goodVsBadActive = true, $goodVsBadMinStrlen = 50, $goodVsBadMinReviews = 5, $goodVsBadMinPointsBest = 4, $goodVsBadMaxPointsWorst = 3): Config {
        $path = Config::PLUGIN_NAME . '.config.';
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $systemConfigService->set($path . 'goodVsBadActive', $goodVsBadActive);
        $systemConfigService->set($path . 'goodVsBadMinStrlen', $goodVsBadMinStrlen);
        $systemConfigService->set($path . 'goodVsBadMinReviews', $goodVsBadMinReviews);
        $systemConfigService->set($path . 'goodVsBadMinPointsBest', $goodVsBadMinPointsBest);
        $systemConfigService->set($path . 'goodVsBadMaxPointsWorst', $goodVsBadMaxPointsWorst);
        $config = new Config($systemConfigService);
        return $config;
    }

}
