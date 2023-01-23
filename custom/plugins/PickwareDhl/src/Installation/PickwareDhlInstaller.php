<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Installation;

use Doctrine\DBAL\Connection;
use Pickware\PickwareDhl\Dhl\DhlConfig;
use Pickware\PickwareDhl\Installation\Steps\UpsertReturnLabelMailTemplateInstallationStep;
use Pickware\PickwareDhl\PickwareDhl;
use Pickware\ShippingBundle\Installation\CarrierInstaller;
use Pickware\ShippingBundle\ParcelPacking\ParcelPackingConfiguration;
use Pickware\InstallationLibrary\IdLookUpService;
use Pickware\InstallationLibrary\MailTemplate\MailTemplateInstaller;
use Pickware\UnitsOfMeasurement\PhysicalQuantity\Weight;

class PickwareDhlInstaller
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function postInstall(): void
    {
        $this->postUpdate();
    }

    public function postUpdate(): void
    {
        $mailTemplateInstaller = new MailTemplateInstaller($this->db, new IdLookUpService($this->db));
        (new UpsertReturnLabelMailTemplateInstallationStep($mailTemplateInstaller))->install();

        // The carrier will reference the mail template type, therefore create them after the mail templates
        $carrierInstaller = new CarrierInstaller($this->db);
        $carrierInstaller->installCarrier([
            'technicalName' => PickwareDhl::CARRIER_TECHNICAL_NAME_DHL,
            'name' => 'DHL Geschäftskundenversand',
            'abbreviation' => 'DHL',
            'configDomain' => DhlConfig::CONFIG_DOMAIN,
            'shipmentConfigDescriptionFilePath' => __DIR__ . '/../Dhl/ShipmentConfigDescription.yaml',
            'defaultParcelPackingConfiguration' => new ParcelPackingConfiguration(
                null,
                null,
                new Weight(31.5, 'kg')
            ),
            'returnLabelMailTemplateTechnicalName' => PickwareDhl::MAIL_TEMPLATE_TYPE_TECHNICAL_NAME_RETURN_LABEL,
        ]);
    }
}
