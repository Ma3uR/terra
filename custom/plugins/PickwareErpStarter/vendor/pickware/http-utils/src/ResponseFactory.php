<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\HttpUtils;

use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseFactory
{
    /**
     * @param string $parameterName
     * @return JsonResponse
     */
    public static function createParameterMissingResponse(string $parameterName): JsonResponse
    {
        return new JsonResponse([
            'errors' => [
                [
                    'status' => (string) Response::HTTP_BAD_REQUEST,
                    'title' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'detail' => sprintf('Parameter "%s" is missing.', $parameterName),
                ],
            ],
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return JsonResponse
     */
    public static function createNotFoundResponse(): JsonResponse
    {
        return new JsonResponse([
            'errors' => [
                [
                    'status' => (string) Response::HTTP_NOT_FOUND,
                    'title' => Response::$statusTexts[Response::HTTP_NOT_FOUND],
                ],
            ],
        ], Response::HTTP_NOT_FOUND);
    }

    public static function createFileUploadErrorResponse(UploadedFile $file, string $parameterName): JsonResponse
    {
        if ($file->isValid()) {
            throw new LogicException(sprintf(
                'The method %s can only be called with a file for which the upload failed.',
                __METHOD__
            ));
        }

        return new JsonResponse([
            'errors' => [
                [
                    'status' => (string) Response::HTTP_BAD_REQUEST,
                    'title' => 'File upload has failed.',
                    'detail' => $file->getErrorMessage(),
                    'source' => [
                        'parameter' => $parameterName,
                    ],
                    'meta' => [
                        'fileName' => $file->getClientOriginalName(),
                    ],
                ],
            ],
        ], Response::HTTP_BAD_REQUEST);
    }
}
