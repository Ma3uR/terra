<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport\Controller;

use Pickware\PickwareErpStarter\ImportExport\DependencyInjection\ExporterRegistry;
use Pickware\PickwareErpStarter\ImportExport\Exception\ExporterServiceDoesNotExistException;
use Pickware\PickwareErpStarter\ImportExport\Exception\ImporterServiceDoesNotExistException;
use Pickware\PickwareErpStarter\ImportExport\ImportExportService;
use Pickware\HttpUtils\ResponseFactory;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;

class ImportExportController
{
    /**
     * @var ImportExportService
     */
    private $importExportService;

    /**
     * @var RequestCriteriaBuilder
     */
    private $requestCriteriaBuilder;

    /**
     * @var ExporterRegistry
     */
    private $exporterRegistry;

    public function __construct(
        ImportExportService $importExportService,
        RequestCriteriaBuilder $requestCriteriaBuilder,
        ExporterRegistry $exporterRegistry
    ) {
        $this->importExportService = $importExportService;
        $this->requestCriteriaBuilder = $requestCriteriaBuilder;
        $this->exporterRegistry = $exporterRegistry;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/import-csv",
     *     methods={"POST"}
     * )
     */
    public function importCsv(Request $request, Context $context): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        if (!$file) {
            return ResponseFactory::createParameterMissingResponse('file');
        }
        if (!$file->isValid()) {
            return ResponseFactory::createFileUploadErrorResponse($file, 'file');
        }
        $profileTechnicalName = $request->request->get('profileTechnicalName');
        if (!$profileTechnicalName) {
            return ResponseFactory::createParameterMissingResponse('profileTechnicalName');
        }
        $config = [];

        $source = $context->getSource();
        $userId = ($source instanceof AdminApiSource) ? $source->getUserId() : null;

        try {
            $importExportId = $this->importExportService->importCsvFileAsync($file, [
                'profileTechnicalName' => $profileTechnicalName,
                'userId' => $userId,
                'config' => $config,
                'fileName' => $file->getClientOriginalName(),
                'userComment' => $request->request->get('userComment', null),
            ], $context);
        } catch (ImporterServiceDoesNotExistException $e) {
            return new JsonResponse([
                'errors' => [
                    [
                        'status' => (string) Response::HTTP_BAD_REQUEST,
                        'title' => 'Invalid value for parameter.',
                        'detail' => $e->getMessage(),
                        'source' => [
                            'parameter' => 'profileTechnicalName',
                        ],
                    ],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['importExportId' => $importExportId], Response::HTTP_ACCEPTED);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/_action/pickware-erp/export-csv",
     *     methods={"POST"}
     * )
     */
    public function exportCsv(Request $request, Context $context): Response
    {
        $profileTechnicalName = $request->request->get('profileTechnicalName');
        if (!$profileTechnicalName) {
            return ResponseFactory::createParameterMissingResponse('profileTechnicalName');
        }

        if (!$this->exporterRegistry->hasExporter($profileTechnicalName)) {
            return new JsonResponse([
                'errors' => [
                    [
                        'status' => (string) Response::HTTP_BAD_REQUEST,
                        'title' => 'Invalid value for parameter.',
                        'detail' => (new ExporterServiceDoesNotExistException($profileTechnicalName))->getMessage(),
                        'source' => [
                            'parameter' => 'profileTechnicalName',
                        ],
                    ],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $exporter = $this->exporterRegistry->getExporterByTechnicalName($profileTechnicalName);

        $criteria = $this->requestCriteriaBuilder->handleRequest(
            $request,
            new Criteria(),
            $exporter->getEntityDefinition(),
            $context
        );

        $config = [
            'locale' => $request->headers->get('sw-admin-locale', 'en-GB'),
            'criteria' => $this->requestCriteriaBuilder->toArray($criteria),
            'totalCount' => $request->request->get('totalCount', 0),
        ];

        $source = $context->getSource();
        $userId = ($source instanceof AdminApiSource) ? $source->getUserId() : null;

        $importExportId = $this->importExportService->exportCsvFileAsync([
            'profileTechnicalName' => $profileTechnicalName,
            'config' => $config,
            'userId' => $userId,
            'userComment' => $request->request->get('userComment', null),
        ], $context);

        return new JsonResponse(['importExportId' => $importExportId], Response::HTTP_ACCEPTED);
    }
}
