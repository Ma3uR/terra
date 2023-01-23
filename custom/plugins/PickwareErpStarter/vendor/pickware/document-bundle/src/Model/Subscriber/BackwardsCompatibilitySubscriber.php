<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DocumentBundle\Model\Subscriber;

use League\Flysystem\FilesystemInterface;
use Pickware\DalBundle\EntityManager;
use Pickware\DocumentBundle\Model\DocumentDefinition;
use Pickware\DocumentBundle\Model\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Some fields in the table pickware_document are nullable for backwards compatibility reasons. This fields are not
 * marked as optional in the DAL. This subscriber completes entities with such "null" fields with their actual value.
 * When releasing new major version 2.0.0 remove this subscriber and replace it with appropriate migrations.
 *
 * @deprecated 2.0.0 Remove this subscriber with next major version and replace it with migrations.
 */
class BackwardsCompatibilitySubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var FilesystemInterface
     */
    private $privateFileSystem;

    public function __construct(EntityManager $entityManager, FilesystemInterface $privateFileSystem)
    {
        $this->entityManager = $entityManager;
        $this->privateFileSystem = $privateFileSystem;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentDefinition::ENTITY_LOADED_EVENT => 'onDocumentEntityLoaded',
        ];
    }

    public function onDocumentEntityLoaded(EntityLoadedEvent $event): void
    {
        $documentPayloads = [];
        /** @var DocumentEntity $document */
        foreach ($event->getEntities() as $document) {
            $documentPayload = [];
            if ($document->get('pathInPrivateFileSystem') === null) {
                // A value of null means, that the document was written directly via database or in an old version of
                // the bundle. In that case the convention that the path = documents/ + ID was applied.
                $pathInPrivateFileSystem = 'documents/' . $document->getId();
                $document->setPathInPrivateFileSystem($pathInPrivateFileSystem);
                $documentPayload['pathInPrivateFileSystem'] = $pathInPrivateFileSystem;
            }
            if ($document->get('fileSizeInBytes') === -1) {
                if ($this->privateFileSystem->has($document->getPathInPrivateFileSystem())) {
                    // A value of -1 means: The file size is not known yet.
                    $fileSizeInBytes = $this->privateFileSystem->getSize($document->getPathInPrivateFileSystem());
                    $document->setFileSizeInBytes($fileSizeInBytes);
                    $documentPayload['fileSizeInBytes'] = $fileSizeInBytes;
                } else {
                    $document->setFileSizeInBytes(0);
                    $documentPayload['fileSizeInBytes'] = 0;
                }
            }
            if (count($documentPayload) !== 0) {
                $documentPayload['id'] = $document->getId();
                $documentPayloads[] = $documentPayload;
            }
        }
        if (count($documentPayloads) === 0) {
            return;
        }
        $event->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($documentPayloads) {
            $this->entityManager->update(DocumentDefinition::class, $documentPayloads, $context);
        });
    }
}
