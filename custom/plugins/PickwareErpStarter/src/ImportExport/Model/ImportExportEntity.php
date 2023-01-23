<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport\Model;

use DateTimeInterface;
use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Pickware\DocumentBundle\Model\DocumentEntity;
use Pickware\HttpUtils\JsonApi\JsonApiErrors;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\User\UserEntity;

class ImportExportEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $profileTechnicalName;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var UserEntity|null
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $userId;

    /**
     * @var string
     */
    protected $userComment;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var int|null
     */
    protected $currentItem;

    /**
     * @var int|null
     */
    protected $totalNumberOfItems;

    /**
     * @var bool
     */
    protected $isDownloadReady;

    /**
     * @var null|DateTimeInterface
     */
    protected $startedAt;

    /**
     * @var null|DateTimeInterface
     */
    protected $completedAt;

    /**
     * @var JsonApiErrors|null
     */
    protected $errors;

    /**
     * @var string|null
     */
    protected $documentId;

    /**
     * @var DocumentEntity|null
     */
    protected $document;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getProfileTechnicalName(): string
    {
        return $this->profileTechnicalName;
    }

    public function setProfileTechnicalName(string $profileTechnicalName): void
    {
        $this->profileTechnicalName = $profileTechnicalName;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        if ($this->user && $this->user->getId() !== $userId) {
            $this->user = null;
        }
        $this->userId = $userId;
    }

    public function getUser(): ?UserEntity
    {
        if (!$this->user && $this->userId) {
            throw new AssociationNotLoadedException('user', $this);
        }

        return $this->user;
    }

    public function setUser(?UserEntity $user): void
    {
        if ($user) {
            $this->userId = $user->getId();
        }
        $this->user = $user;
    }

    public function getUserComment(): string
    {
        return $this->userComment;
    }

    public function setUserComment(string $userComment): void
    {
        $this->userComment = $userComment;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getCurrentItem(): ?int
    {
        return $this->currentItem;
    }

    public function setCurrentItem(?int $currentItem): void
    {
        $this->currentItem = $currentItem;
    }

    public function getTotalNumberOfItems(): ?int
    {
        return $this->totalNumberOfItems;
    }

    public function setTotalNumberOfItems(?int $totalNumberOfItems): void
    {
        $this->totalNumberOfItems = $totalNumberOfItems;
    }

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeInterface $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeInterface $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getErrors(): ?JsonApiErrors
    {
        return $this->errors;
    }

    public function setErrors(?JsonApiErrors $errors): void
    {
        $this->errors = $errors;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        if ($this->document && $this->document->getId() !== $documentId) {
            $this->document = null;
        }
        $this->documentId = $documentId;
    }

    public function getDocument(): ?DocumentEntity
    {
        if (!$this->document && $this->documentId) {
            throw new AssociationNotLoadedException('document', $this);
        }

        return $this->document;
    }

    public function setDocument(?DocumentEntity $document): void
    {
        if ($document) {
            $this->documentId = $document->getId();
        }
        $this->document = $document;
    }

    public function isDownloadReady(): bool
    {
        return $this->isDownloadReady;
    }
}
