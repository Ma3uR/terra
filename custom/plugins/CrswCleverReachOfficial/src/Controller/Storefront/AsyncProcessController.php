<?php

namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Exposed\Runnable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Serializer;
use Crsw\CleverReachOfficial\Entity\Process\ProcessEntityRepository;
use Crsw\CleverReachOfficial\Service\Infrastructure\ConfigService;
use Crsw\CleverReachOfficial\Service\Utility\Initializer;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AsyncProcessController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class AsyncProcessController extends AbstractController
{
    /**
     * @var ProcessEntityRepository
     */
    private $processRepository;

    /**
     * AsyncProcessController constructor.
     *
     * @param Initializer $initializer
     * @param ProcessEntityRepository $processRepository
     */
    public function __construct(ProcessEntityRepository $processRepository, Initializer $initializer)
    {
        $initializer->registerServices();
        $this->processRepository = $processRepository;
    }

    /**
     * Async process starter endpoint
     *
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/cleverreach/async/{guid}", name="cleverreach.async", methods={"GET"})
     *
     * @param Context $context
     * @param string $guid
     *
     * @return JsonResponse
     */
    public function run(Context $context, string $guid): JsonResponse
    {
        /** @var ConfigService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setShopwareContext($context);

        try {
            $processEntity = $this->processRepository->getProcessByGuid($guid, $context);
            if ($processEntity) {
                /** @var Runnable $runner */
                $runner = Serializer::unserialize($processEntity->get('runner'));
                $runner->run();
            }

            $this->processRepository->deleteByGuid($guid, $context);
        } catch (\Exception $exception) {
            Logger::logError("An error occurred when accessing process table: {$exception->getMessage()}");
        }

        return new JsonResponse(['success' => true]);
    }
}
