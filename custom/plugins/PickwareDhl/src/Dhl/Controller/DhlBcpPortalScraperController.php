<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\Controller;

use Pickware\PickwareDhl\Dhl\DhlBcpConfigScraper\DhlBcpConfigScraper;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DhlBcpPortalScraperController
{
    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/_action/pickware-dhl/check-dhl-bcp-credentials", name="api.action.pickware-dhl.check-dhl-bcp-credentials", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function checkDhlBcpCredentials(Request $request): JsonResponse
    {
        $username = (string) $request->request->get('username');
        $password = (string) $request->request->get('password');

        $dhlService = new DhlBcpConfigScraper($username, $password);

        return new JsonResponse($dhlService->checkCredentials());
    }
    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/_action/pickware-dhl/fetch-dhl-contract-data", name="api.action.pickware-dhl.fetch-dhl-contract-data", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchDhlContractData(Request $request): JsonResponse
    {
        $username = (string) $request->request->get('username');
        $password = (string) $request->request->get('password');

        $dhlService = new DhlBcpConfigScraper($username, $password);

        return new JsonResponse($dhlService->fetchContractData());
    }
}
