<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Http;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class HttpLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpSanitizer[]
     */
    private $httpSanitizers;

    public function __construct(LoggerInterface $logger, array $httpSanitizers)
    {
        $this->logger = $logger;
        $this->httpSanitizers = $httpSanitizers;
    }

    public function logSuccess(RequestInterface $request, ResponseInterface $response): void
    {
        $this->logger->debug('HTTP request successful', $this->getContext($request, $response));
    }

    public function logError(Throwable $reason, RequestInterface $request, ?ResponseInterface $response): void
    {
        $this->logger->error('HTTP request failed: ' . $reason->getMessage(), $this->getContext($request, $response));
    }

    private function getContext(RequestInterface $request, ?ResponseInterface $response): array
    {
        return [
            'request' => Message::toString($this->sanitizeRequest($request)),
            'response' => $response ? Message::toString($this->sanitizeResponse($response)) : null,
        ];
    }

    private function sanitizeRequest(RequestInterface $request): RequestInterface
    {
        // phpcs:ignore VIISON.Arrays.ArrayDeclaration.SingleLineNotAllowed
        [$body, $headers] = $this->sanitizeMessage($request);

        return new Request(
            $request->getMethod(),
            $request->getUri(),
            $headers,
            $body,
            $request->getProtocolVersion()
        );
    }

    private function sanitizeResponse(ResponseInterface $response): ResponseInterface
    {
        // phpcs:ignore VIISON.Arrays.ArrayDeclaration.SingleLineNotAllowed
        [$body, $headers] = $this->sanitizeMessage($response);

        return new Response(
            $response->getStatusCode(),
            $headers,
            $body,
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    private function sanitizeMessage(MessageInterface $request): array
    {
        $body = (string) $request->getBody();
        $headers = $request->getHeaders();
        foreach ($this->httpSanitizers as $httpSanitizer) {
            $body = $httpSanitizer->filterBody($body);
            foreach ($headers as $headerName => $headerValues) {
                foreach ($headerValues as $key => $headerValue) {
                    $headers[$headerName][$key] = $httpSanitizer->filterHeader($headerName, $headerValue);
                }
            }
        }

        return [
            $body,
            $headers,
        ];
    }
}
