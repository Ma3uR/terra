<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Administration\Controller;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tanmar\ProductReviews\Components\MailHelper;

/**
 * @RouteScope(scopes={"api"})
 */
class TanmarProductReviewsTestMailController extends AbstractController {

    /**
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var MailHelper
     */
    protected $mailHelper;

    /**
     *
     * @param MailHelper $mailHelper
     */
    public function __construct(MailHelper $mailHelper, EntityRepositoryInterface $orderRepository) {
        $this->mailHelper = $mailHelper;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @OA\Post(
     *     path="/_action/tanmar/productreviews/testmail/invitation",
     *     description="Test invitation mail",
     *     operationId="testInvitation",
     *     tags={"Admin Api", "TanmarNgProductReviews"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="salesChannelId",
     *                 description="The id of the Saleschannel where the mail should take it configuration from.",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="204"
     *     )
     * )
     *
     * @Since("6.4.0.0")
     * @Route("/api/_action/tanmar/productreviews/testmail/invitation", name="api.action.tanmar.productreviews.testmail.invitation", methods={"POST"})
     * @Acl({"tanmar_productreviews.editor"})
     */
    public function invitation(Request $request, Context $context): Response {
        $orderEntity = $this->loadOrder($request->request->get('salesChannelId'), $context);
        $this->mailHelper->sendInvitationMail($context, $orderEntity, true);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @OA\Post(
     *     path="/_action/tanmar/productreviews/testmail/notification",
     *     description="Test invitation mail",
     *     operationId="testNotification",
     *     tags={"Admin Api", "TanmarNgProductReviews"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="salesChannelId",
     *                 description="The id of the Saleschannel where the mail should take it configuration from.",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="204"
     *     )
     * )
     *
     * @Since("6.4.0.0")
     * @Route("/api/_action/tanmar/productreviews/testmail/notification", name="api.action.tanmar.productreviews.testmail.notification", methods={"POST"})
     * @Acl({"tanmar_productreviews.editor"})
     */
    public function notification(Request $request, Context $context): Response {
        $orderEntity = $this->loadOrder($request->request->get('salesChannelId'), $context);
        $this->mailHelper->sendNotificationMail($context, $orderEntity, random_int(0, 5), true);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @OA\Post(
     *     path="/_action/tanmar/productreviews/testmail/coupon",
     *     description="Test coupon mail",
     *     operationId="testCoupon",
     *     tags={"Admin Api", "TanmarNgProductReviews"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="salesChannelId",
     *                 description="The id of the Saleschannel where the mail should take it configuration from.",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="204"
     *     )
     * )
     * @Since("6.4.0.0")
     * @Route("/api/_action/tanmar/productreviews/testmail/coupon", name="api.action.tanmar.productreviews.testmail.coupon", methods={"POST"})
     * @Acl({"tanmar_productreviews.editor"})
     */
    public function coupon(Request $request, Context $context): Response {
        $orderEntity = $this->loadOrder($request->request->get('salesChannelId'), $context);
        $this->mailHelper->sendVoucherMail($context, $orderEntity, strtoupper(uniqid('test-')), true);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     *
     * @param string $salesChannelId
     * @param Context $context
     * @return OrderEntity
     */
    protected function loadOrder(?string $salesChannelId, Context $context): OrderEntity {
        $criteria = new Criteria();
        $criteria->addAssociation('lineItems.product.cover.media');
        $criteria->addAssociation('orderCustomer.salutation');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('currency');

        if (is_null($salesChannelId)) {
            $criteria->addAssociation('salesChannel');
            $criteria->addFilter(new EqualsFilter('salesChannel.typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        } else {
            $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        }

        $criteria->setLimit(1);
        $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::DESCENDING));

        $result = $this->orderRepository->search($criteria, $context);
        return $result->first();
    }

}
