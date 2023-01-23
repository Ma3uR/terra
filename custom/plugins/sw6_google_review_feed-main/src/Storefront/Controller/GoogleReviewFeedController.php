<?php declare(strict_types=1);

namespace Webmp\GoogleReviewFeed\Storefront\Controller;

use League\Flysystem\FilesystemInterface;
use Shopware\Core\Content\Cms\Exception\PageNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webmp\GoogleReviewFeed\Helper\GoogleReviewFeedHelper;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class GoogleReviewFeedController
 * @RouteScope(scopes={"storefront"})
 */
class GoogleReviewFeedController extends StorefrontController
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    /**
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * GoogleReviewFeedController constructor.
     * @param SystemConfigService $systemConfigService
     * @param FilesystemInterface $filesystem
     * @param EntityRepositoryInterface $salesChannelRepository
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        FilesystemInterface $filesystem,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->filesystem = $filesystem;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * @Route(
     *     "/webmasterei/google-product-review/{token}/feed",
     *     name="webmasterei.frontend.feed",
     *     defaults={"_format"="xml"},
     *     methods={"GET"}
     *     )
     * @param Context $criteriaContext
     * @param mixed $token
     * @return Response
     */
    public function feedAction(
        Context $criteriaContext,
        $token
    ): Response {
        $salesChannelsIds = $this->salesChannelRepository->search(new Criteria(), $criteriaContext)->getEntities()->getIds();
        foreach ($salesChannelsIds as $salesChannelId) {
            $accessToken = $this->systemConfigService->get(
                'WebmpGoogleReviewFeed.settings.accessToken',
                $salesChannelId
            ) ?: $this->systemConfigService->get('WebmpGoogleReviewFeed.settings.ratingFilter');
            $filePath = sprintf(GoogleReviewFeedHelper::FILE_PATH, $salesChannelId);

            if ($accessToken === $token && $this->filesystem->has($filePath)) {
                return new Response($this->filesystem->read($filePath));
            }
        }

        throw new PageNotFoundException("/webmasterei/google-product-review/$token/feed");
    }
}
