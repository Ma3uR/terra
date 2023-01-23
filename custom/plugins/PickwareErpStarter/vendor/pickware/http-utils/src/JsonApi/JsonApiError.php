<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\HttpUtils\JsonApi;

use JsonSerializable;

/**
 * Error objects provide additional information about problems encountered while performing an operation. Error objects
 * MUST be returned as an array keyed by errors in the top level of a JSON:API document.
 */
class JsonApiError implements JsonSerializable
{
    /**
     * @var mixed A unique identifier for this particular occurrence of the problem.
     */
    private $id = null;

    /**
     * @var null|array a links object containing the following members: "about": a link that leads to further details
     * about this particular occurrence of the problem.
     */
    private $links = null;

    /**
     * @var string|null The HTTP status code applicable to this problem, expressed as an string value.
     */
    private $status = null;

    /**
     * @var string|null An application-specific error code, expressed as a string value.
     */
    private $code = null;

    /**
     * @var string|null A short, human-readable summary of the problem that SHOULD NOT change from occurrence to
     * occurrence of the problem, except for purposes of localization.
     */
    private $title = null;

    /**
     * @var null|string A human-readable explanation specific to this occurrence of the problem. Like title, this
     * field's value can be localized.
     */
    private $detail = null;

    /**
     * @var JsonApiErrorSource|null
     */
    private $source = null;

    /**
     * @var null|array
     */
    private $meta = null;

    public function __construct(array $properties = [])
    {
        $this->setId($properties['id'] ?? null);
        $this->setLinks($properties['links'] ?? null);
        $this->setStatus($properties['status'] ?? null);
        $this->setCode($properties['code'] ?? null);
        $this->setTitle($properties['title'] ?? null);
        $this->setDetail($properties['detail'] ?? null);
        $this->setSource($properties['source'] ?? null);
        $this->setMeta($properties['meta'] ?? null);
    }

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this), function ($value) {
            return $value !== null;
        });
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function setLinks(?array $links): void
    {
        $this->links = $links;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param null|int|string $status
     */
    public function setStatus($status): void
    {
        $this->status = ($status !== null) ? (string) $status : null;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): void
    {
        $this->detail = $detail;
    }

    public function getSource(): ?JsonApiErrorSource
    {
        return $this->source;
    }

    public function setSource(?JsonApiErrorSource $source): void
    {
        $this->source = $source;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }
}
