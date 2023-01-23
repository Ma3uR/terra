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
use Pickware\ShippingBundle\Config\CommonShippingConfig;
use Pickware\ShippingBundle\Config\ConfigService;
use Pickware\ShippingBundle\ParcelHydration\ParcelHydrator;
use Pickware\DocumentBundle\Installation\EnsureDocumentTypeInstallationStep;
use Pickware\ShippingBundle\PickwareShippingBundle;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PickwareShippingBundleInstaller
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(Connection $db, SystemConfigService $systemConfigService)
    {
        $this->db = $db;
        $this->systemConfigService = $systemConfigService;
    }

    public function postInstall(): void
    {
        $this->postUpdate();

        $this->upsertDefaultConfiguration();
    }

    public function postUpdate(): void
    {
        $this->upsertDocumentTypes();
        $this->upsertExportInformationCustomFields();
    }

    private function upsertDefaultConfiguration(): void
    {
        $shippingConfigService = new ConfigService($this->systemConfigService);
        $currentConfig = $shippingConfigService->getConfigForSalesChannel(
            CommonShippingConfig::CONFIG_DOMAIN,
            null
        );
        $defaultConfig = CommonShippingConfig::createDefault();
        $defaultConfig->apply(new CommonShippingConfig($currentConfig));
        $shippingConfigService->saveConfigForSalesChannel($defaultConfig->getConfig(), null);
    }

    private function upsertDocumentTypes(): void
    {
        (new EnsureDocumentTypeInstallationStep(
            $this->db,
            PickwareShippingBundle::DOCUMENT_TYPE_TECHNICAL_NAME_DESCRIPTION_MAPPING
        ))->install();
    }

    private function upsertExportInformationCustomFields(): void
    {
        $technicalName = 'pickware_shipping_customs_information';
        $config = [
            'label' => [
                'de-DE' => 'Zollinformationen',
                'en-GB' => 'Customs information',
            ],
            'translated' => true,
        ];
        $this->db->executeStatement(
            'INSERT IGNORE INTO `custom_field_set`
                (id, name, config, active, created_at)
            VALUES
                (:id, :name, :config, 1, NOW(3))
            ON DUPLICATE KEY UPDATE
                `config` = VALUES(`config`),
                `updated_at` = NOW(3)',
            [
                'id' => md5($technicalName),
                'name' => $technicalName,
                'config' => json_encode($config),
            ]
        );

        $setId = $this->db->fetchOne(
            'SELECT `id` FROM `custom_field_set` WHERE `name` = :name',
            [
                'name' => $technicalName,
            ]
        );

        $fields = [
            [
                'name' => ParcelHydrator::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_DESCRIPTION,
                'type' => 'text',
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Beschreibung',
                        'en-GB' => 'Description',
                    ],
                    'helpText' => [
                        'de-DE' => 'Eine detaillierte Beschreibung des Artikels, z.B. "Herren-Baumwollhemden". ' .
                            'Allgemeine Beschreibungen wie z.B. "Ersatzteile", "Muster" oder "Lebensmittel" sind ' .
                            'nicht erlaubt. Wenn du das Feld freilässt, wird der Produktname verwendet.',
                        'en-GB' => 'A detailed description of the item, e.g. "men\'s cotton shirts". General ' .
                            'descriptions e.g. "spare parts", "samples" or "food products" are not permitted. If ' .
                            'you leave this field blank the product name will be used.',
                    ],
                    'placeholder' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 1,
                ],
            ],
            [
                'name' => ParcelHydrator::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_TARIFF_NUMBER,
                'type' => 'text',
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Zolltarifnummer (nach HS)',
                        'en-GB' => 'HS customs tariff number',
                    ],
                    'helpText' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'placeholder' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 4,
                ],
            ],
            [
                'name' => ParcelHydrator::CUSTOM_FIELD_NAME_CUSTOMS_INFORMATION_COUNTRY_OF_ORIGIN,
                'type' => 'text',
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Herkunftsland (2-stelliger Code, z.B. "DE" für Deutschland)',
                        'en-GB' => 'Country of origin (2-characters Code, e.g. "DE" for Germany)',
                    ],
                    'placeholder' => [
                        'de-DE' => null,
                        'en-GB' => null,
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 5,
                ],
            ],
        ];

        foreach ($fields as $field) {
            $field['id'] = Uuid::randomBytes();
            $field['setId'] = $setId;
            $field['config'] = json_encode($field['config']);

            $this->db->executeStatement(
                'INSERT IGNORE INTO `custom_field`
                    (id, name, type, config, active, set_id, created_at)
                VALUES
                    (:id, :name, :type, :config, 1, :setId, NOW(3))
                ON DUPLICATE KEY UPDATE
                    `config` = VALUES(`config`),
                    `type` = VALUES(`type`),
                    `updated_at` = NOW(3)',
                $field
            );
        }

        $this->db->executeStatement(
            'INSERT INTO custom_field_set_relation
                (id, set_id, entity_name, created_at)
            VALUES
                (:id, :setId, :entityName, NOW(3))
            ON DUPLICATE KEY UPDATE
                `updated_at` = NOW(3)',
            [
                'id' => Uuid::randomBytes(),
                'setId' => $setId,
                'entityName' => ProductDefinition::ENTITY_NAME,
            ]
        );
    }
}
