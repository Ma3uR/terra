<?php

declare(strict_types=1);

namespace Tanmar\ProductReviewsDesign\Storefront\Page\Product;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tanmar\ProductReviewsDesign\Service\ConfigService;
use Shopware\Storefront\Page\Product\Review\ProductReviewsLoadedEvent;
use Tanmar\ProductReviewsDesign\Storefront\BaseSubscriber;
use Tanmar\ProductReviewsDesign\Components\ReviewHelper;

class ProductPageSubscriber extends BaseSubscriber implements EventSubscriberInterface {

    public function __construct(ConfigService $configService) {
        parent::__construct($configService);
    }

    public static function getSubscribedEvents(): array {
        return [
            ProductReviewsLoadedEvent::class => 'onProductPageLoaded'
        ];
    }

    public function onProductPageLoaded(ProductReviewsLoadedEvent $event): void {
        try {
            $productReviewsDesignData = $this->getExtension($event);
            if (!is_null($productReviewsDesignData) && $this->getConfig()->isActive()) {
                $reviews = $event->getSearchResult()->getEntities()->getElements();
                if ($this->getConfig()->isGoodVsBadActive()) {
                    $reviewHelper = new ReviewHelper($reviews, $this->getConfig());
                    $bestReview = $reviewHelper->getBestReview();
                    $worstReview = $reviewHelper->getWorstReview();
                    $productReviewsDesignData->assign([
                        'bestReview' => is_null($bestReview) ? false : $bestReview,
                        'worstReview' => is_null($worstReview) ? false : $worstReview
                    ]);
                }
                $productReviewsDesignData->assign([
                    'readMoreCounter' => (count($reviews) > $this->getConfig()->getReadMoreCounter()) ? $this->getConfig()->getReadMoreCounter() : 0,
                    'readMoreCounterMore' => (count($reviews) > $this->getConfig()->getReadMoreCounter()) ? (count($reviews) - $this->getConfig()->getReadMoreCounter()) : 0
                ]);
            }
            $this->addExtension($event, $productReviewsDesignData);
        } catch (\Exception $e) {
            
        }
    }

}
