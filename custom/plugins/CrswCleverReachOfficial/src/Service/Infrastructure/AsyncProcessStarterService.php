<?php

namespace Crsw\CleverReachOfficial\Service\Infrastructure;

use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Exposed\Runnable;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\GuidProvider;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Serializer;
use Crsw\CleverReachOfficial\Entity\Process\ProcessEntityRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AsyncProcessStarterService
 *
 * @package Crsw\CleverReachOfficial\Service\Infrastructure
 */
class AsyncProcessStarterService implements AsyncProcessStarter
{
    /**
     * @var HttpClient
     */
    private $httpClient;
    /**
     * @var ProcessEntityRepository
     */
    private $processRepository;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * AsyncProcessStarterService constructor.
     *
     * @param HttpClient $httpClient
     * @param ProcessEntityRepository $processRepository
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        HttpClient $httpClient,
        ProcessEntityRepository $processRepository,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->httpClient = $httpClient;
        $this->processRepository = $processRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Starts given runner asynchronously (in new process/web request or similar)
     *
     * @param Runnable $runner Runner that should be started async
     *
     * @throws ProcessStarterSaveException
     * @throws HttpRequestException
     */
    public function start(Runnable $runner): void
    {
        $guidProvider = new GuidProvider();
        $guid = trim($guidProvider->generateGuid());

        $this->saveGuidAndRunner($guid, $runner);
        $this->startRunnerAsynchronously($guid);
    }

    /**
     * Saves guid and runner into process table
     *
     * @param string $guid process identifier
     * @param Runnable $runner instance of TaskRunnerStarter or QueueItemStarter
     *
     * @throws ProcessStarterSaveException
     */
    private function saveGuidAndRunner(string $guid, Runnable $runner): void
    {
        /** @var ConfigService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        try {
            $this->processRepository->saveGuidAndRunner(
                $guid,
                Serializer::serialize($runner),
                $configService->getShopwareContext()
            );
        } catch (\Exception $e) {
            Logger::logError('Failed to save process: ' . $e->getMessage(), 'Integration');
            throw new ProcessStarterSaveException($e->getMessage());
        }
    }

    /**
     * Sends async request to AsyncProcessController with guid as query parameter
     *
     * @param string $guid process identifier
     *
     * @throws HttpRequestException
     */
    public function startRunnerAsynchronously(string $guid): void
    {
        try {
            $this->httpClient->requestAsync('GET', $this->formatAsyncProcessStartUrl($guid));
        } catch (\Exception $e) {
            Logger::logError('Failed to send async request: ' . $e->getMessage(), 'Integration');
            throw new HttpRequestException($e->getMessage());
        }
    }

    /**
     * Returns async process controller url
     *
     * @param string $guid
     *
     * @return string
     */
    private function formatAsyncProcessStartUrl(string $guid): string
    {
        return $this->urlGenerator->generate('cleverreach.async', ['guid' => $guid], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
