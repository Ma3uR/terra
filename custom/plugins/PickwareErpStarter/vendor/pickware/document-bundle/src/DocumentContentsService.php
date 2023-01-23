<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DocumentBundle;

use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use Pickware\DalBundle\EntityManager;
use Pickware\DocumentBundle\Model\DocumentDefinition;
use Pickware\DocumentBundle\Model\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Throwable;

class DocumentContentsService
{
    /**
     * @var FilesystemInterface
     */
    private $privateFilesystem;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(FilesystemInterface $privateFilesystem, EntityManager $entityManager)
    {
        $this->privateFilesystem = $privateFilesystem;
        $this->entityManager = $entityManager;
    }

    /**
     * @deprecated 2.0.0 Use self::saveStringAsDocument instead
     * @param DocumentEntity $document
     * @param string $contents
     */
    public function persistDocumentContents(DocumentEntity $document, string $contents): void
    {
        $this->privateFilesystem->write($document->getPathInPrivateFileSystem(), $contents);
        $this->entityManager->update(DocumentDefinition::class, [
            [
                'id' => $document->getId(),
                'fileSizeInBytes' => $this->privateFilesystem->getSize($document->getPathInPrivateFileSystem()),
            ],
        ], Context::createDefaultContext());
    }

    /**
     * @deprecated 2.0.0 Use the private file system of the document bundle directly
     * @param DocumentEntity $document
     * @return string
     */
    public function readDocumentContents(DocumentEntity $document): string
    {
        return $this->privateFilesystem->read($document->getPathInPrivateFileSystem());
    }

    /**
     * @param resource $resource A resource for the stream
     * @param Context $context
     * @param array $options Possible options are: string documentTypeTechnicalName (required), ?string memeType,
     *        ?PageFormat pageFormat, ?string orientation ("portrait" or "landscape"), ?string fileName,
     *        ?array extensions
     * @return string
     */
    public function saveStreamAsDocument($resource, Context $context, array $options = []): string
    {
        return $this->saveFileAsDocument(function (FilesystemInterface $filesystem, string $filePath) use ($resource) {
            $filesystem->putStream($filePath, $resource);
        }, $context, $options);
    }

    /**
     * @param string $documentContents A string containing the document contents
     * @param Context $context
     * @param array $options Possible options are: string documentType (required), ?string memeType,
     *        ?PageFormat pageFormat, ?string orientation ("portrait" or "landscape"), ?string fileName,
     *        ?array extensions
     * @return string
     */
    public function saveStringAsDocument(string $documentContents, Context $context, array $options = []): string
    {
        return $this->saveFileAsDocument(
            function (FilesystemInterface $filesystem, string $filePath) use ($documentContents) {
                $filesystem->put($filePath, $documentContents);
            },
            $context,
            $options
        );
    }

    private function saveFileAsDocument(callable $saveCallback, Context $context, array $options = []): string
    {
        if (!isset($options['documentTypeTechnicalName'])) {
            throw new InvalidArgumentException('Option "documentTypeTechnicalName" is required.');
        }
        if (isset($options['deepLinkCode'])) {
            throw new InvalidArgumentException(
                'Option "deepLinkCode" is not allowed. The deepLinkCode is generated automatically.'
            );
        }

        $documentId = Uuid::randomHex();
        $filePath = sprintf('documents/%s', $documentId);
        $saveCallback($this->privateFilesystem, $filePath);

        $payload = array_merge($options, [
            'id' => $documentId,
            'fileSizeInBytes' => $this->privateFilesystem->getSize($filePath),
            'pathInPrivateFileSystem' => $filePath,
        ]);
        try {
            $this->entityManager->create(DocumentDefinition::class, [$payload], $context);
        } catch (Throwable $e) {
            $this->privateFilesystem->delete($filePath);
            throw $e;
        }

        return $documentId;
    }
}
