<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Carrier\Model;

use Pickware\ShippingBundle\ParcelPacking\ParcelPackingConfiguration;
use Pickware\DalBundle\Association\Exception\AssociationNotLoadedException;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class CarrierEntity extends Entity
{
    /**
     * @var string
     */
    protected $technicalName;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $abbreviation;

    /**
     * @var string
     */
    protected $configDomain;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @var array|null
     */
    protected $capabilities;

    /**
     * @var array
     */
    protected $shipmentConfigDefaultValues;

    /**
     * @var array
     */
    protected $shipmentConfigOptions;

    /**
     * @var ParcelPackingConfiguration
     */
    protected $defaultParcelPackingConfiguration;

    /**
     * @var null|MailTemplateTypeEntity
     */
    protected $returnLabelMailTemplateType;

    /**
     * @var string|null
     */
    protected $returnLabelMailTemplateTypeTechnicalName;

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
        $this->_uniqueIdentifier = $technicalName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAbbreviation(): string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): void
    {
        $this->abbreviation = $abbreviation;
    }

    public function getConfigDomain(): string
    {
        return $this->configDomain;
    }

    public function setConfigDomain(string $configDomain): void
    {
        $this->configDomain = $configDomain;
    }

    public function getShipmentConfigDefaultValues(): array
    {
        return $this->shipmentConfigDefaultValues;
    }

    public function setShipmentConfigDefaultValues(array $shipmentConfigDefaultValues): void
    {
        $this->shipmentConfigDefaultValues = $shipmentConfigDefaultValues;
    }

    public function getShipmentConfigOptions(): array
    {
        return $this->shipmentConfigOptions;
    }

    public function setShipmentConfigOptions(array $shipmentConfigOptions): void
    {
        $this->shipmentConfigOptions = $shipmentConfigOptions;
    }

    public function getDefaultParcelPackingConfiguration(): ParcelPackingConfiguration
    {
        return $this->defaultParcelPackingConfiguration;
    }

    public function setDefaultParcelPackingConfiguration(
        ParcelPackingConfiguration $defaultParcelPackingConfiguration
    ): void {
        $this->defaultParcelPackingConfiguration = $defaultParcelPackingConfiguration;
    }

    public function isInstalled(): bool
    {
        return $this->installed;
    }

    public function setInstalled(bool $installed): void
    {
        $this->installed = $installed;
    }

    public function getCapabilities(): ?array
    {
        return $this->capabilities;
    }

    public function setCapabilities(?array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    /**
     * @return string|null
     */
    public function getReturnLabelMailTemplateTypeTechnicalName(): ?string
    {
        return $this->returnLabelMailTemplateTypeTechnicalName;
    }

    /**
     * @param string|null $returnLabelMailTemplateTypeTechnicalName
     */
    public function setReturnLabelMailTemplateTypeTechnicalName(?string $returnLabelMailTemplateTypeTechnicalName): void
    {
        if ($this->returnLabelMailTemplateType
            && $this->returnLabelMailTemplateType->getTechnicalName() !== $returnLabelMailTemplateTypeTechnicalName
        ) {
            $this->returnLabelMailTemplateType = null;
        }
        $this->returnLabelMailTemplateTypeTechnicalName = $returnLabelMailTemplateTypeTechnicalName;
    }

    /**
     * @return MailTemplateTypeEntity|null
     */
    public function getReturnLabelMailTemplateType(): ?MailTemplateTypeEntity
    {
        if (!$this->returnLabelMailTemplateType && $this->returnLabelMailTemplateTypeTechnicalName) {
            throw new AssociationNotLoadedException('returnLabelMailTemplateType', $this);
        }

        return $this->returnLabelMailTemplateType;
    }

    /**
     * @param MailTemplateTypeEntity|null $returnLabelMailTemplateType
     */
    public function setReturnLabelMailTemplateType(?MailTemplateTypeEntity $returnLabelMailTemplateType): void
    {
        if ($returnLabelMailTemplateType) {
            $this->returnLabelMailTemplateTypeTechnicalName = $returnLabelMailTemplateType->getTechnicalName();
        }
        $this->returnLabelMailTemplateType = $returnLabelMailTemplateType;
    }
}
