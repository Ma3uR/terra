<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DocumentBundle\Controller;

use iio\libmergepdf\Merger as PdfMerger;
use League\Flysystem\FilesystemInterface;
use Pickware\DalBundle\EntityManager;
use Pickware\DocumentBundle\Model\DocumentCollection;
use Pickware\HttpUtils\ResponseFactory;
use Pickware\DocumentBundle\Model\DocumentDefinition;
use Pickware\DocumentBundle\Model\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class DocumentController
{
    /**
     * @var FilesystemInterface
     */
    private $privateFileSystem;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param FilesystemInterface $privateFileSystem
     * @param EntityManager $entityManager
     */
    public function __construct(FilesystemInterface $privateFileSystem, EntityManager $entityManager)
    {
        $this->privateFileSystem = $privateFileSystem;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/api/pickware-document/{documentId}/contents",
     *     defaults={"auth_required"=false},
     *     name="api.pickware-document.contents",
     *     methods={"GET"},
     *     requirements={"documentId"="[a-fA-F0-9]{32}"}
     * )
     * @param string $documentId
     * @param Request $request
     * @param Context $context
     * @return Response
     */
    public function documentContents(string $documentId, Request $request, Context $context): Response
    {
        $deepLinkCode = $request->get('deepLinkCode');
        if ($deepLinkCode === null) {
            return ResponseFactory::createParameterMissingResponse('deepLinkCode');
        }
        /** @var DocumentEntity $document */
        $document = $this->entityManager->findOneBy(DocumentDefinition::class, [
            'id' => $documentId,
            'deepLinkCode' => $deepLinkCode,
        ], $context);
        if (!$document) {
            return ResponseFactory::createNotFoundResponse();
        }

        $stream = $this->privateFileSystem->readStream($document->getPathInPrivateFileSystem());

        $headers = [
            'Content-Length' => $document->getFileSizeInBytes(),
        ];
        if ($document->getMimeType()) {
            $headers['Content-Type'] = $document->getMimeType();
        }
        if ($document->getFileName()) {
            $download = $request->query->getBoolean('download', false);
            $headers['Content-Disposition'] = HeaderUtils::makeDisposition(
                $download ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
                $document->getFileName(),
                // only printable ascii
                preg_replace('/[\\x00-\\x1F\\x7F-\\xFF]/', '_', $document->getFileName())
            );
        }

        return new StreamedResponse(
            function () use ($stream) {
                fpassthru($stream);
            },
            Response::HTTP_OK,
            $headers
        );
    }

    /**
     * @Route(
     *     "/api/_action/pickware-merge-documents",
     *     defaults={"auth_required"=false},
     *     name="api._action.pickware-merge-documents",
     *     methods={"GET"},
     * )
     * @param Request $request
     * @param Context $context
     * @return Response
     */
    public function mergeDocuments(Request $request, Context $context): Response
    {
        $deepLinkCodes = $request->get('deepLinkCodes');
        if (!$deepLinkCodes || !is_array($deepLinkCodes)) {
            return ResponseFactory::createParameterMissingResponse('deepLinkCodes');
        }

        /** @var DocumentCollection $documents */
        $documents = $this->entityManager->findBy(DocumentDefinition::class, [
            'deepLinkCode' => $deepLinkCodes,
        ], $context);

        if ($documents->count() === 0) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        foreach ($documents as $document) {
            if ($document->getMimeType() !== 'application/pdf') {
                return new Response(sprintf(
                    'Document with id=%s is not in supported format application/pdf.',
                    $document->getId()
                ), Response::HTTP_BAD_REQUEST);
            }
        }

        // In case of only one document skip merging to save execution time
        if ($documents->count() === 1) {
            $responseContent = $this->privateFileSystem->read($documents->first()->getPathInPrivateFileSystem());
        } else {
            $pdfMerger = new PdfMerger();
            foreach ($documents as $document) {
                $pdfMerger->addRaw($this->privateFileSystem->read($document->getPathInPrivateFileSystem()));
            }
            $responseContent = $pdfMerger->merge();
        }

        return new Response($responseContent, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
