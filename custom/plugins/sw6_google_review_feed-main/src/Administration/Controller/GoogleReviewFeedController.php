<?php declare(strict_types=1);

namespace Webmp\GoogleReviewFeed\Administration\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Webmp\GoogleReviewFeed\Helper\GoogleReviewFeedHelper;

/**
 * Class GoogleReviewFeedController
 * @RouteScope(scopes={"api"})
 */
class GoogleReviewFeedController extends StorefrontController
{
    /**
     * @var GoogleReviewFeedHelper
     */
    private $googleReviewFeedHelper;

    /**
     * GoogleReviewFeedController constructor.
     * @param GoogleReviewFeedHelper $googleReviewFeedHelper
     */
    public function __construct(
        GoogleReviewFeedHelper $googleReviewFeedHelper
    ) {
        $this->googleReviewFeedHelper = $googleReviewFeedHelper;
    }

    /**
     * @Route(
     *     "/api/v{version}/webmp/generate-feed",
     *     name="api.action.webmp.generateFeed",
     *     methods={"POST"}
     *     )
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function generateFeed(Request $request, Context $context): JsonResponse
    {
        try {
            $this->googleReviewFeedHelper->generateFeedFile();

            $responseData = [
                'success' => 1
            ];
        } catch (\Throwable $exception) {
            $responseData = [
                'message' => $exception->getMessage(),
                'success' => 0
            ];
        }

        return $this->json($responseData);
    }
}
