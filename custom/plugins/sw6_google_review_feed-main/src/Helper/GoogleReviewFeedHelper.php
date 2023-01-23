<?php declare(strict_types=1);

namespace Webmp\GoogleReviewFeed\Helper;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use SimpleXMLElement;

/**
 * Class GoogleReviewFeedHelper
 * @package Webmp\GoogleReviewFeed\Helper
 */
class GoogleReviewFeedHelper
{
    const FILE_PATH = "webmp/%s/product_reviews.xsd";

    /**
     * @var EntityRepositoryInterface
     */
    private $productReviewRepository;
    /**
     * @var SeoUrlPlaceholderHandlerInterface
     */
    private $seoUrlPlaceholderHandler;
    /**
     * @var SalesChannelContextFactory
     */
    private $salesChannelContextFactory;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * GoogleReviewFeedHelper constructor.
     * @param EntityRepositoryInterface $productReviewRepository
     * @param SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler
     * @param SalesChannelContextFactory $salesChannelContextFactory
     * @param SystemConfigService $systemConfigService
     * @param FilesystemInterface $filesystem
     */
    public function __construct(
        EntityRepositoryInterface $productReviewRepository,
        SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler,
        SalesChannelContextFactory $salesChannelContextFactory,
        SystemConfigService $systemConfigService,
        FilesystemInterface $filesystem
    ) {
        $this->productReviewRepository = $productReviewRepository;
        $this->seoUrlPlaceholderHandler = $seoUrlPlaceholderHandler;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->systemConfigService = $systemConfigService;
        $this->filesystem = $filesystem;
    }

    /**
     *
     */
    public function generateFeedFile(): void
    {
        $structuredProductReviews = $this->getStructuredBySalesChannelProductReview();
        $xmlFeedsBySalesChannels = $this->generateXmlFeedByProductReview($structuredProductReviews);
        $this->createXmlFeedFile($xmlFeedsBySalesChannels);
    }

    /**
     * @return mixed[]
     */
    private function getStructuredBySalesChannelProductReview(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociations([
            'product',
            'product.parent',
            'product.manufacturer',
            'customer',
            'salesChannel',
            'salesChannel.domains',
        ]);
        $criteria->addFilter(
            new EqualsFilter('status', true)
        );

        $productReviewResult = $this->productReviewRepository->search($criteria, Context::createDefaultContext());

        $structuredProductReviews = [];
        /** @var ProductReviewEntity $productReview */
        foreach ($productReviewResult->getElements() as $productReview) {
            $filterRating = 0.0;
            $pluginConfigBySalesChannel = $this->systemConfigService->get(
                'WebmpGoogleReviewFeed.settings.ratingFilter',
                $productReview->getSalesChannelId()
            );

            if (!empty($pluginConfigBySalesChannel)) {
                $filterRating = $pluginConfigBySalesChannel;
            } else if (!empty($this->systemConfigService->get('GoogleReviewFeed.config.ratingFilter'))) {
                $filterRating = $this->systemConfigService->get('GoogleReviewFeed.config.ratingFilter');
            }

            if (abs($filterRating) > abs($productReview->getPoints())) {
                continue;
            }

            $structuredProductReviews[$productReview->getSalesChannelId()][] = $productReview;
        }

        return $structuredProductReviews;
    }

    /**
     * @param mixed[] $structuredProductReviews
     * @return mixed[]
     */
    private function generateXmlFeedByProductReview(array $structuredProductReviews): array
    {
        $xmlFeedsBySalesChannels = [];
        /** @var ProductReviewEntity $productReview */
        foreach ($structuredProductReviews as $salesChannelId => $productReviews) {
            $xml = new SimpleXMLElement('<feed/>');
            $xml->addAttribute('xmlns:vc', 'http://www.w3.org/2007/XMLSchema-versioning');
            $xml->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $xml->addAttribute(
                'xsi:noNamespaceSchemaLocation',
                'http://www.google.com/shopping/reviews/schema/product/2.3/product_reviews.xsd'
            );

            /** @var ProductReviewEntity $firstProductReview */
            $firstProductReview = current($productReviews);
            $xml->addChild('version', "2.3");
            $aggregator = $xml->addChild('aggregator');
            $aggregator->addChild('name', htmlspecialchars($firstProductReview->getSalesChannel()->getName() ?: ''));
            $publisher = $xml->addChild('publisher');
            $publisher->addChild('name', htmlspecialchars($firstProductReview->getSalesChannel()->getName() ?: ''));
            $reviews = $xml->addChild('reviews');

            foreach ($productReviews as $productReview) {
                $productUrl = '';
                if (!empty($productReview->getSalesChannel()->getDomains()->first())) {
                    $salesChannelContext = $this->salesChannelContextFactory->create(
                        Uuid::randomHex(),
                        $productReview->getSalesChannel()->getId()
                    );

                    $productUrl = $this->seoUrlPlaceholderHandler->replace(
                        $this->seoUrlPlaceholderHandler->generate(
                            'frontend.detail.page',
                            [
                                'productId' => $productReview->getProduct()->getId(),
                            ]
                        ),
                        $productReview->getSalesChannel()->getDomains()->first()->getUrl(),
                        $salesChannelContext
                    );
                }

                $review = $reviews->addChild('review');

                $review->addChild('review_id', htmlspecialchars($productReview->getId() ?: ''));

                $reviewer = $review->addChild('reviewer');
                if (!empty($productReview->getCustomer())) {
                    $reviewer->addChild('name', htmlspecialchars($productReview->getCustomer()->getFirstName() ?: ''));
                } else {
                    $name = $reviewer->addChild('name', 'Anonymous');
                    $name->addAttribute('is_anonymous', 'true');
                }

                $review->addChild(
                    'review_timestamp',
                    htmlspecialchars($productReview->getCreatedAt()->format('c') ?: '')
                );
                $review->addChild('title', htmlspecialchars($productReview->getTitle() ?: ''));
                $review->addChild('content', htmlspecialchars($productReview->getContent() ?: ''));

                $reviewUrl = $review->addChild('review_url', htmlspecialchars($productUrl ?: ''));
                $reviewUrl->addAttribute('type', 'singleton');

                $ratings = $review->addChild('ratings');
                $overall = $ratings->addChild('overall', htmlspecialchars((string)$productReview->getPoints() ?: ''));
                $overall->addAttribute('min', '1');
                $overall->addAttribute('max', '5');

                $products = $review->addChild('products');
                $product = $products->addChild('product');
                $productIds = $product->addChild('product_ids');

                $gtins = $productIds->addChild('gtins');
                $gtins->addChild('gtin', htmlspecialchars($productReview->getProduct()->getEan() ?: '')); // Empty

                if (!empty($productReview->getProduct()->getManufacturerNumber())) {
                    $mpns = $productIds->addChild('mpns');
                    $mpns->addChild(
                        'mpn',
                        htmlspecialchars($productReview->getProduct()->getManufacturerNumber() ?: '')
                    );
                }

                $skus = $productIds->addChild('skus');
                $skus->addChild('sku', htmlspecialchars($productReview->getProduct()->getProductNumber() ?: ''));

                $brands = $productIds->addChild('brands');
                if (!empty($productReview->getProduct()->getManufacturer())) {
                    $brands->addChild(
                        'brand',
                        htmlspecialchars($productReview->getProduct()->getManufacturer()->getName() ?: '')
                    );
                } else {
                    $brands->addChild('brand', '');
                }

                $product->addChild('product_name', htmlspecialchars($productReview->getProduct()->getName() ?: ''));
                $product->addChild('product_url', htmlspecialchars($productUrl ?: ''));

                $review->addChild('is_spam', 'false');
            }

            $xmlFeedsBySalesChannels[$salesChannelId] = $xml->asXML();
        }

        return $xmlFeedsBySalesChannels;
    }

    /**
     * @param mixed[] $xmlFeedsBySalesChannels
     * @throws \League\Flysystem\FileExistsException
     * @throws \League\Flysystem\FileNotFoundException
     */
    private function createXmlFeedFile(array $xmlFeedsBySalesChannels): void
    {
        foreach ($xmlFeedsBySalesChannels as $salesChannelId => $xmlContent) {
            $filePath = sprintf(self::FILE_PATH, $salesChannelId);
            if ($this->filesystem->has($filePath)) {
                $this->filesystem->update($filePath, $xmlContent);

                continue;
            }

            $this->filesystem->write($filePath, $xmlContent);
        }
    }
}
