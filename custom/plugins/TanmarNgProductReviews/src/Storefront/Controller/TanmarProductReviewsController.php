<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Storefront\Controller;

use Exception;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Tanmar\ProductReviews\Components\Config;
use Tanmar\ProductReviews\Components\LoggerHelper;
use Tanmar\ProductReviews\Components\MailHelper;
use Tanmar\ProductReviews\Components\TanmarProductReviewsData;
use Tanmar\ProductReviews\Components\TanmarProductReviewsHelper;
use Tanmar\ProductReviews\Service\ConfigService;
use Tanmar\ProductReviews\Service\PromotionService;
use Tanmar\ProductReviews\Service\OrderService;
use Tanmar\ProductReviews\Service\ProductReviewsService;

/**
 *
 */
class TanmarProductReviewsController extends StorefrontController {

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * 
     * @var PromotionService
     */
    private $promotionService;
    
    /**
     * 
     * @var OrderService
     */
    private $orderService;

    /**
     * @var TanmarProductReviewsHelper
     */
    private $helper;

    /**
     * @var string
     */
    private $extensionName = 'TanmarProductReviews';

    /**
     *
     * @var GenericPageLoader
     */
    private $genericPageLoader;

    /**
     * @var LoggerHelper
     */
    protected $loggerHelper;

    /**
     *
     * @var ProductReviewsService
     */
    protected $productReviewsService;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $productReviewRepository;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    /**
     *
     * @var EntityRepositoryInterface
     */
    protected $productRepository;

    /**
     *
     * @var MailHelper
     */
    protected $mailHelper;

    /**
     *
     * @param ConfigService $configService
     * @param MailHelper $mailHelper
     */
    public function __construct(
        ConfigService $configService,
        PromotionService $promotionService,
        OrderService $orderService,
        LoggerHelper $loggerHelper,
        GenericPageLoader $genericPageLoader,
        ProductReviewsService $productReviewsService,
        EntityRepositoryInterface $productReviewRepository,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $productRepository,
        MailHelper $mailHelper
    ) {
        $this->configService = $configService;
        $this->promotionService = $promotionService;
        $this->orderService = $orderService;
        $this->helper = new TanmarProductReviewsHelper();
        $this->loggerHelper = $loggerHelper;
        $this->genericPageLoader = $genericPageLoader;
        $this->productReviewsService = $productReviewsService;
        $this->productReviewRepository = $productReviewRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->mailHelper = $mailHelper;
    }

    /**
     *
     * @return TanmarProductReviewsHelper
     */
    protected function getHelper(): TanmarProductReviewsHelper {
        return $this->helper;
    }

    /**
     *
     * @return Config
     */
    protected function getConfig(): Config {
        return $this->configService->getConfig();
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/TanmarProductReviews/rating/{orderNumber}/{hash}", name="frontend.tanmarproductreviews.rating", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function rating(string $orderNumber, string $hash, Request $request, SalesChannelContext $salesChannelContext) {

        $returnData = array(
            'type' => 'error',
            'msg' => $this->trans('tanmar-product-reviews.error.missingArticle'),
        );

        $sArticleId = $request->get('sArticle');

        if (empty($sArticleId)) {
            return new JsonResponse($returnData);
        }

        $isAnon = false;

        $anon = $request->get('anon');
        if (!is_null($anon) && $anon == 1) {
            $isAnon = true;
        }

        if ($this->articleDoesNotExist($sArticleId, $salesChannelContext)) {
            $returnData['msg'] = $this->trans('tanmar-product-reviews.error.missingArticle');
            return new JsonResponse($returnData);
        }

        try {

            $orderHash = false;
            $order = $this->loadOrder($orderNumber, $salesChannelContext);
            $orderId = $order->getId();
            $customer = $order->getOrderCustomer();
            $orderHash = $this->getHelper()->getOrderHash($order);
            $hashShort = substr($orderHash, 0, 6);

            if (!$orderHash || $hash != $hashShort) {
                $returnData['msg'] = $this->trans('tanmar-product-reviews.error.validation');
                return new JsonResponse($returnData);
            }

            $commentData = array(
                'points' => $request->get('points'),
                'sArticle' => $sArticleId,
                'comment' => strip_tags($request->get('comment')),
                'email' => (string) $orderNumber,
                'headline' => $request->get('summary'),
                'active' => 0,
                'anon' => $isAnon
            );

            if ($this->getConfig()->getReviewSkipModeration()) {
                $commentData['active'] = 1;
            }

            $commentData['name'] = $this->anonymizeName($isAnon, $customer);

            if ($this->getConfig()->getHeadlineRequired()) {

                // \development\platform\src\Core\Content\Product\SalesChannel\Review\ProductReviewSaveRoute.php:134
                // $definition->add('title', new NotBlank(), new Length(['min' => 5]));
                if (strlen(trim($commentData['headline'])) < 1) {


                    $returnData['msg'] = $this->trans('tanmar-product-reviews.error.missingHeadline');
                    return new JsonResponse($returnData);
                }
            }

            // \development\platform\src\Core\Content\Product\SalesChannel\Review\ProductReviewSaveRoute.php:135
            // $definition->add('content', new NotBlank(), new Length(['min' => 40]));
//            if(strlen(trim($commentData['comment'])) < 40){
//                $returnData['msg'] = $this->trans('tanmar-product-reviews.error.missingComment');
//                return new JsonResponse($returnData);
//            }
            $commentresult = $this->saveComment($commentData, $order, $customer, $salesChannelContext);

            if ($commentresult) {
                $returnData['type'] = 'success';
                $returnData['msg'] = $this->trans('tanmar-product-reviews.reviews.success');
                if (is_array($commentresult)) {
                    $returnData['voucher'] = $commentresult;
                }
            } else {
                $returnData['msg'] = $this->trans('tanmar-product-reviews.error.fillOutCompletely');
            }

            $sendNewReviewNotification = $this->getConfig()->getSendNewReviewNotification();
            if ($sendNewReviewNotification) {
                $orderStars = (int) $commentData['points'];
                $notify = false;
                switch ($sendNewReviewNotification) {
                    case 1: // always
                        $notify = true;
                        break;
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                        $notify = $orderStars < (7 - $sendNewReviewNotification);
                        break;
                }
                if ($notify) {
                    try {
                        $this->mailHelper->sendNotificationMail($salesChannelContext->getContext(), $order, $orderStars);
                    } catch (Exception $e) {
                        $this->log('Exception', [$exc->getMessage()], Logger::ERROR);
                    }
                }
            }

            if ($this->productReviewsService->orderProductsHaveBeenRated($order, $salesChannelContext->getContext())) {
                if ($this->getConfig()->getSendVoucherMail() && $this->getConfig()->getSendVoucherMailPromotionId() && !$this->orderService->hasPromotionCode($order)) {
                    $promotionCode = $this->promotionService->getPromotionCode($this->getConfig()->getSendVoucherMailPromotionId(), $salesChannelContext->getContext());
                    if ($promotionCode != '') {
                        $this->mailHelper->sendVoucherMail($salesChannelContext->getContext(), $order, $promotionCode);
                        $this->orderService->updatePromotionCode($order, $salesChannelContext->getContext(), $promotionCode);
                    } else {
                        $this->loggerHelper->addDirectRecord(Logger::DEBUG, 'Tried to send voucher mail, but no promotion code found. Your chosen promotion needs to use individual codes.');
                    }
                }
            }
        } catch (Exception $exc) {
            $returnData['exception'] = $exc->getMessage();
            $returnData['trace'] = $exc->getTraceAsString();
            $this->log('Exception', [$exc->getMessage()], Logger::ERROR);
        }

        return new JsonResponse($returnData);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/TanmarProductReviews/{orderNumber}/{hash}", name="frontend.tanmarproductreviews", options={"seo"="false"}, methods={"GET"})
     */
    public function index(string $orderNumber, string $hash, SalesChannelContext $salesChannelContext, Request $request) {
        $orderHash = false;

        $debug = [];

        $order = $this->loadOrder($orderNumber, $salesChannelContext);
        if (!$order) {
            return $this->redirectToRoute('frontend.home.page');
        }

        $orderId = $order->get('id');
        $customer = $order->get('orderCustomer');

        $getOrderHash = $this->getHelper()->getOrderHash($order);
        $orderHash = '';

        $orderHash = $this->getHelper()->getOrderHash($order);
        $hashShort = substr($orderHash, 0, 6);

        $orderStatus = $order->getStateMachineState()->getName();

        if (!$orderId || $hash != $hashShort || !$hashShort) {
            return $this->redirectToRoute('frontend.home.page');
        }

        $openvotes = false;
        $productsReview = [];

        foreach ($order->getLineItems() as $orderLineItem) {
            if ($orderLineItem->getType() == 'product') {
                try {
                    $productNumber = $orderLineItem->getPayload();

                    if ($productNumber && $productNumber['productNumber']) {

                        $product = $this->loadProduct($productNumber['productNumber'], $salesChannelContext);

                        $productReviewCollection = $this->loadReviews($product, $customer, $salesChannelContext);

                        if (count($productReviewCollection)) {
                            $product->setProductReviews($productReviewCollection);
                        } else {
                            $openvotes = true;
                        }

                        if (!isset($productsReview[$product->get('id')])) { // $openvotes &&
                            $productsReview[$product->get('id')] = $product;
                            $productsReviewHasReviews[$product->get('id')] = count($productReviewCollection) ? 1 : 0;
                        }
                    } else {
                        $this->log('productNumber is null', [$orderId], Logger::ERROR);
                    }
                } catch (Exception $exc) {
                    $this->log('Exception', [$exc->getMessage()], Logger::ERROR);
                }
            }
        }

        array_multisort($productsReviewHasReviews, SORT_ASC, $productsReview);

        $view = $this->createViewdata();

        $viewData = [
            'products' => $productsReview,
            'ordernumber' => $orderNumber,
            'hash' => $hash,
            'canVote' => $openvotes,
            'debug' => $debug,
            'tanmarReviewsStarsPreselected' => $this->getConfig()->getStarsPreselected(),
            'headlineRequired' => $this->getConfig()->getHeadlineRequired()
        ];

        $view->assign(['data' => $viewData]);
        $salesChannelContext->addExtension($this->extensionName, $view);

        $page = $this->genericPageLoader->load($request, $salesChannelContext);

        return $this->renderStorefront('@Storefront/storefront/page/tanmar_product_reviews/index.html.twig', ["page" => $page]);
    }

    /**
     *
     * @return TanmarProductReviewsData
     */
    protected function createViewdata(): TanmarProductReviewsData {
        $TanmarProductReviewsData = new TanmarProductReviewsData();
        if ($this->getConfig() && is_object($this->getConfig())) {
            $TanmarProductReviewsData->assign([
                'active' => $this->getConfig()->isActive()
            ]);
        }
        return $TanmarProductReviewsData;
    }

    /**
     *
     * @param array $commentData
     * @param OrderEntity $order
     * @param OrderCustomerEntity $customer
     * @param SalesChannelContext $salesChannelContext
     * @return bool
     */
    protected function saveComment(array $commentData, OrderEntity $order, OrderCustomerEntity $customer, SalesChannelContext $salesChannelContext): bool {

        if (
            !isset($commentData['comment']) ||
            !isset($commentData['points']) ||
            !isset($commentData['sArticle']) ||
            !isset($commentData['email']) ||
            empty($commentData['comment']) ||
            empty($commentData['points']) ||
            empty($commentData['sArticle']) ||
            empty($commentData['email'])
        ) {
            return false;
        }

        if ($this->reviewAlreadyExists($commentData, $salesChannelContext)) {
            return false;
        }

        /*
         * die ersten 5 Wörter aus dem Kommentar ziehen
         */
        if (empty($commentData['headline'])) {

            if ($this->getConfig()->getHeadlineRequired()) {
                return false;
            }

            $words = $this->getConfig()->getReviewWordsHeadline();
            if ($words < 1) {
                $words = 5;
            }
            $commentData['headline'] = implode(' ', array_slice(explode(' ', $commentData['comment']), 0, $words));
        }


        $customerId = $customer->getCustomerId();
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $languageId = $salesChannelContext->getContext()->getLanguageId();

        // save user id on anon?
        if ($commentData['anon']) {
            
        }


        $create = [
            [
                'productId' => $commentData['sArticle'],
                'customerId' => $customerId,
                'salesChannelId' => $salesChannelId,
                'languageId' => $languageId,
                'externalUser' => $commentData['name'],
                'externalEmail' => $commentData['email'],
                'title' => $commentData['headline'],
                'content' => $commentData['comment'],
                'points' => $commentData['points'],
                'status' => $commentData['active'] == 1 ? true : false,
            ],
        ];

        /** @var EntityRepositoryInterface $taxRepository */
        $productReviewRepository = $this->container->get('product_review.repository');
        $productReviewRepository->create(
            $create,
            $salesChannelContext->getContext()
        );

        return true;
    }

    /**
     *
     * @param string $sArticleId
     * @param SalesChannelContext $salesChannelContext
     * @return boolean
     */
    protected function articleDoesNotExist(string $sArticleId, SalesChannelContext $salesChannelContext): bool {
        $productEntities = $this->productRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('id', $sArticleId)),
            $salesChannelContext->getContext()
        );
        $productCollection = $productEntities->getEntities();
        $article = $productCollection->first();

        if (empty($article)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param bool $isAnon
     * @param OrderCustomerEntity $customer
     * @return string
     */
    protected function anonymizeName(bool $isAnon, OrderCustomerEntity $customer): string {
        if ($isAnon) {
            return $this->trans('tanmar-product-reviews.reviews.anon');
        } else {
            return ucwords($customer->getFirstName()) . ' ' . ucwords(substr($customer->getLastName(), 0, 1)) . '.';
        }
    }

    /**
     *
     * @param string $orderNumber
     * @param SalesChannelContext $salesChannelContext
     * @return OrderEntity
     */
    protected function loadOrder(string $orderNumber, SalesChannelContext $salesChannelContext): ?OrderEntity {
        $entities = $this->orderRepository->search(
            (new Criteria())->addAssociation('currency')->addFilter(new EqualsFilter('orderNumber', $orderNumber))->addAssociation('lineItems'),
            $salesChannelContext->getContext()
        );
        if ($entities) {
            return $entities->first();
        }
        return null;
    }

    /**
     *
     * @param string $productNumber
     * @param SalesChannelContext $salesChannelContext
     * @return ProductEntity
     */
    protected function loadProduct(string $productNumber, SalesChannelContext $salesChannelContext): ?ProductEntity {
        $productEntities = $this->productRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('productNumber', $productNumber))->addAssociation('cover'),
            $salesChannelContext->getContext()
        );

        if ($productEntities) {
            $productCollection = $productEntities->getEntities();
            $product = $productCollection->first();
            return $product;
        }
        return null;
    }

    /**
     *
     * @param ProductEntity $product
     * @param OrderCustomerEntity $customer
     * @param SalesChannelContext $salesChannelContext
     * @return ProductReviewCollection
     */
    protected function loadReviews(ProductEntity $product, OrderCustomerEntity $customer, SalesChannelContext $salesChannelContext): ?ProductReviewCollection {
        $productReview = $this->productReviewRepository->search(
            (new Criteria())->addFilter(new MultiFilter(
                    MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('productId', $product->get('id')),
                    new MultiFilter(
                        MultiFilter::CONNECTION_OR, [
                        new EqualsFilter('customerId', $customer->getCustomerId()),
                        new EqualsFilter('externalEmail', $customer->getEmail())
                        ]
                    )
                    ]
            )),
            $salesChannelContext->getContext()
        );
        if ($productReview) {
            return $productReview->getEntities();
        }
        return null;
    }

    /**
     *
     * @param array $commentData
     * @param SalesChannelContext $salesChannelContext
     * @return bool
     */
    protected function reviewAlreadyExists(array $commentData, SalesChannelContext $salesChannelContext): bool {
        $productReview = $this->productReviewRepository->search(
            (new Criteria())->addFilter(new MultiFilter(
                    MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('productId', $commentData['sArticle']),
                    new EqualsFilter('externalEmail', $commentData['email'])
                    ]
            )),
            $salesChannelContext->getContext()
        );
        if (!$productReview) {
            return true;
        }
        $productReviewCollection = $productReview->getEntities();
        if ($productReviewCollection && count($productReviewCollection)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param string $text
     * @param array $data
     * @param int $logLevel
     */
    protected function log(string $text, array $data = [], int $logLevel = Logger::DEBUG) {
        $this->loggerHelper->addDirectRecord(
            $logLevel,
            $text,
            $data
        );
    }

}
