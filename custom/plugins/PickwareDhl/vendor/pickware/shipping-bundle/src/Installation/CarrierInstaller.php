<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Installation;

use Doctrine\DBAL\Connection;
use Pickware\ShippingBundle\Carrier\ShipmentConfigDescription;
use Pickware\ShippingBundle\ParcelPacking\ParcelPackingConfiguration;

class CarrierInstaller
{
    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function installCarrier(array $carrier): void
    {
        if (isset($carrier['shipmentConfigDescriptionFilePath'])) {
            $shipmentConfigDescription = ShipmentConfigDescription::readFromYamlFile(
                $carrier['shipmentConfigDescriptionFilePath']
            );
        } else {
            $shipmentConfigDescription = ShipmentConfigDescription::createEmpty();
        }

        if (isset($carrier['defaultParcelPackingConfiguration'])) {
            $defaultParcelPackingConfiguration = $carrier['defaultParcelPackingConfiguration'];
        } else {
            $defaultParcelPackingConfiguration = ParcelPackingConfiguration::createDefault();
        }

        $this->db->executeStatement(
            'INSERT INTO `pickware_shipping_carrier` (
                `technical_name`,
                `name`,
                `abbreviation`,
                `config_domain`,
                `shipment_config_default_values`,
                `shipment_config_options`,
                `default_parcel_packing_configuration`,
                `return_label_mail_template_type_technical_name`,
                `created_at`
            ) VALUES (
                :technicalName,
                :name,
                :abbreviation,
                :configDomain,
                :shipmentConfigDefaultValues,
                :shipmentConfigOptions,
                :defaultParcelPackingConfiguration,
                :returnLabelMailTemplateTechnicalName,
                NOW(3)
            ) ON DUPLICATE KEY UPDATE
                `name` = VALUES(`name`),
                `abbreviation` = VALUES(`abbreviation`),
                `config_domain` = VALUES(`config_domain`),
                `shipment_config_default_values` = VALUES(`shipment_config_default_values`),
                `shipment_config_options` = VALUES(`shipment_config_options`),
                `default_parcel_packing_configuration` = VALUES(`default_parcel_packing_configuration`),
                `return_label_mail_template_type_technical_name` = VALUES(`return_label_mail_template_type_technical_name`),
                `updated_at` = NOW(3)',
            [
                'technicalName' => $carrier['technicalName'],
                'name' => $carrier['name'],
                'abbreviation' => $carrier['abbreviation'],
                'configDomain' => $carrier['configDomain'],
                'shipmentConfigDefaultValues' => json_encode($shipmentConfigDescription->getDefaultValues()),
                'shipmentConfigOptions' => json_encode($shipmentConfigDescription->getOptions()),
                'defaultParcelPackingConfiguration' => json_encode($defaultParcelPackingConfiguration),
                'returnLabelMailTemplateTechnicalName' => $carrier['returnLabelMailTemplateTechnicalName'] ?? null,
            ]
        );
    }
}
