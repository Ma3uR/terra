<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment;

use JsonSerializable;

class ShipmentsOperationResult implements JsonSerializable
{
    /**
     * @var bool
     */
    private $successful;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string[]|null
     */
    private $errorMessages;

    /**
     * @var string[]
     */
    private $processedShipmentIds;

    private function __construct()
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'successful' => $this->successful,
            'description' => $this->description,
            'errorMessages' => $this->errorMessages,
            'processedShipmentIds' => $this->processedShipmentIds,
        ];
    }

    /**
     * @param string[] $processesShipmentIds
     * @param string $description
     * @return ShipmentsOperationResult
     */
    public static function createSuccessfulOperationResult(
        array $processesShipmentIds,
        string $description
    ): self {
        $self = new self();
        $self->successful = true;
        $self->processedShipmentIds = $processesShipmentIds;
        $self->errorMessages = null;
        $self->description = $description;

        return $self;
    }

    /**
     * Use this when you want to mark a shipment as "processed" without actually processing it.
     *
     * Example: The request is to cancel the shipment but the shipment is cancelled already.
     *
     * @param string[] $processesShipmentIds
     * @return self
     */
    public static function createNoOperationResult(array $processesShipmentIds): self
    {
        return self::createSuccessfulOperationResult($processesShipmentIds, 'No operation');
    }

    /**
     * @param string[] $processesShipmentIds
     * @param string $description
     * @param array $errorMessages
     * @return ShipmentsOperationResult
     */
    public static function createFailedOperationResult(
        array $processesShipmentIds,
        string $description,
        array $errorMessages
    ): self {
        $self = new self();
        $self->successful = false;
        $self->processedShipmentIds = $processesShipmentIds;
        $self->errorMessages = $errorMessages;
        $self->description = $description;

        return $self;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string[]|null
     */
    public function getErrorMessages(): ?array
    {
        return $this->errorMessages;
    }

    /**
     * @return string[]
     */
    public function getProcessedShipmentIds(): array
    {
        return $this->processedShipmentIds;
    }
}
