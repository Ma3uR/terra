<?php

namespace SwagExtensionStore\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Store\Search\ExtensionCriteria;
use SwagExtensionStore\Services\CategoryProvider;
use SwagExtensionStore\Services\StoreDataProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 * @RouteScope(scopes={"api"})
 * @Acl({"system.plugin_maintain"})
 */
class DataController
{
    private StoreDataProvider $dataProvider;
    private CategoryProvider $categoryProvider;

    public function __construct(StoreDataProvider $dataProvider, CategoryProvider $categoryProvider)
    {
        $this->dataProvider = $dataProvider;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * @Since("6.4.0.0")
     * @Route("/api/_action/extension-store/list", name="api.extension.list", methods={"POST", "GET"})
     */
    public function getExtensionList(Request $request, Context $context): Response
    {
        if ($request->getMethod() === Request::METHOD_POST) {
            $criteria = ExtensionCriteria::fromArray($request->request->all());
        } else {
            $criteria = ExtensionCriteria::fromArray($request->query->all());
        }

        $listing = $this->dataProvider->getListing($criteria, $context);

        return new JsonResponse([
            'data' => $listing,
            'meta' => [
                'total' => $listing->getTotal(),
            ],
        ]);
    }

    /**
     * @Since("6.4.0.0")
     * @Route("/api/_action/extension-store/detail/{id}", name="api.extension.detail", methods={"GET"})
     */
    public function detail(int $id, Context $context): Response
    {
        return new JsonResponse($this->dataProvider->getExtensionDetails($id, $context));
    }

    /**
     * @Since("6.4.0.0")
     * @Route("/api/_action/extension-store/{id}/reviews", name="api.extension.reviews", methods={"GET"})
     */
    public function reviews(int $id, Request $request, Context $context): Response
    {
        $criteria = ExtensionCriteria::fromArray($request->query->all());

        return new JsonResponse($this->dataProvider->getReviews($id, $criteria, $context));
    }

    /**
     * @Since("6.4.0.0")
     * @Route("/api/_action/extension-store/store-filters", name="api.extension.store_filters", methods={"GET"})
     */
    public function listingFilters(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse(
            $this->dataProvider->getListingFilters($request->query->all(), $context)
        );
    }

    /**
     * @Since("6.4.0.0")
     * @Route("/api/_action/extension-store/store-categories", name="api.extension.store_categories", methods={"GET"})
     */
    public function getCategories(Context $context): JsonResponse
    {
        return new JsonResponse($this->categoryProvider->getCategories($context));
    }
}
