<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Components\Installer;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\Context;
use \Tanmar\ProductReviews\Components\Installer\InstallerInterface;

class OrderCustomFieldInstaller implements InstallerInterface {

    const CUSTOM_FIELD_SET_NAME = 'tanmar_product_reviews';
    const CUSTOM_FIELD_PROMOTION_CODE = self::CUSTOM_FIELD_SET_NAME . '_promotion_code';
    const CUSTOM_FIELD_STATUS = self::CUSTOM_FIELD_SET_NAME . '_status';
    const CUSTOM_FIELD_SENT = self::CUSTOM_FIELD_SET_NAME . '_sent';
    const CUSTOM_FIELD_OPTIN = self::CUSTOM_FIELD_SET_NAME . '_optin';

    private $context;

    /**
     *
     * @var type
     */
    private $customFieldSetRepository;

    public function __construct(Context $context, $container) {
        $this->context = $context;
        $this->customFieldSetRepository = $container->get('custom_field_set.repository');
    }

    /**
     * Installs a custom field set and all needed custom fields for an order
     *
     */
    public function install() {
        if (is_null($this->getCustomFieldSet())) {
            try {
                $this->customFieldSetRepository->create([$this->createAttributeSet()], $this->context);
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // already exist
            }
        }
    }

    /**
     * Uninstalls the order custom fields which had been installed by the plugin
     *
     */
    public function uninstall() {
        $customFieldSet = $this->getCustomFieldSet();
        if ($customFieldSet instanceof CustomFieldSetEntity) {
            $this->customFieldSetRepository->delete([['id' => $customFieldSet->getId()]], $this->context);
        }
    }

    /**
     * Builds and returns an array with all necessary data, that is needed to create a new custom field set.
     *
     * @return array
     */
    private function createAttributeSet(): array {
        $attributeSet = [
            'name' => self::CUSTOM_FIELD_SET_NAME,
            'config' => [
                'label' => [
                    self::GER_ISO => 'Produktbewertungen',
                    self::EN_ISO => 'Product Reviews',
                    Defaults::LANGUAGE_SYSTEM => 'Product Reviews',
                ]
            ],
            'customFields' => [
                [
                    'name' => self::CUSTOM_FIELD_PROMOTION_CODE,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'customFieldType' => CustomFieldTypes::TEXT,
                        'label' => [
                            self::GER_ISO => 'Gutschein Code',
                            self::EN_ISO => 'Promotion Code',
                            Defaults::LANGUAGE_SYSTEM => 'Promotion Code',
                        ]
                    ],
                ],
                [
                    'name' => self::CUSTOM_FIELD_STATUS,
                    'type' => CustomFieldTypes::SELECT,
                    'config' => [
                        'customFieldType' => CustomFieldTypes::SELECT,
                        'componentName' => 'sw-single-select',
                        'label' => [
                            self::GER_ISO => 'Bewertungsstatus',
                            self::EN_ISO => 'Review status',
                            Defaults::LANGUAGE_SYSTEM => 'Review status',
                        ],
                        'options' => [
                            [
                                'value' => 'open',
                                'label' => [
                                    self::GER_ISO => 'Anfrage ausstehend',
                                    self::EN_ISO => 'Awaiting invitation',
                                    Defaults::LANGUAGE_SYSTEM => 'Awaiting invitation',
                                ],
                            ],
                            [
                                'value' => 'invited',
                                'label' => [
                                    self::GER_ISO => 'Anfrage gesendet',
                                    self::EN_ISO => 'Invitation sent',
                                    Defaults::LANGUAGE_SYSTEM => 'Invitation sent',
                                ],
                            ],
//                            [
//                                'value' => 'couponsent',
//                                'label' => [
//                                    self::GER_ISO => 'Gutschien gesendet',
//                                    self::EN_ISO => 'Coupon sent',
//                                    Defaults::LANGUAGE_SYSTEM => 'Coupon sent',
//                                ],
//                            ],
                            [
                                'value' => 'denied',
                                'label' => [
                                    self::GER_ISO => 'Keine Bewertungsaufforderung',
                                    self::EN_ISO => 'Won\'t invite',
                                    Defaults::LANGUAGE_SYSTEM => 'Won\'t invite',
                                ],
                            ],
                        ]
                    ]
                ],
                [
                    'name' => self::CUSTOM_FIELD_SENT,
                    'type' => CustomFieldTypes::DATETIME,
                    'config' => [
                        'customFieldType' => CustomFieldTypes::DATETIME,
                        'label' => [
                            self::GER_ISO => 'Produktbewertungen gesendet',
                            self::EN_ISO => 'Request for product review sent',
                            Defaults::LANGUAGE_SYSTEM => 'Request for product review sent',
                        ]
                    ],
                ],
                [
                    'name' => self::CUSTOM_FIELD_OPTIN,
                    'type' => CustomFieldTypes::SELECT,
                    'config' => [
                        'customFieldType' => CustomFieldTypes::SELECT,
                        'componentName' => 'sw-single-select',
                        'label' => [
                            self::GER_ISO => 'Produktbewertungen Opt-It',
                            self::EN_ISO => 'Product Reviews Opt-In',
                            Defaults::LANGUAGE_SYSTEM => 'Product Reviews Opt-In',
                        ],
                        'options' => [
                            [
                                'value' => 'agreed',
                                'label' => [
                                    self::GER_ISO => 'Zugestimmt',
                                    self::EN_ISO => 'Agreed',
                                    Defaults::LANGUAGE_SYSTEM => 'Agreed',
                                ],
                            ],
                            [
                                'value' => 'not asked',
                                'label' => [
                                    self::GER_ISO => 'Nicht gefragt',
                                    self::EN_ISO => 'Not asked',
                                    Defaults::LANGUAGE_SYSTEM => 'Not asked',
                                ],
                            ],
                            [
                                'value' => 'denied',
                                'label' => [
                                    self::GER_ISO => 'Abgelehnt',
                                    self::EN_ISO => 'Denied',
                                    Defaults::LANGUAGE_SYSTEM => 'Denied',
                                ],
                            ],
                        ]
                    ],
                ],
            ],
            'relations' => [
                [
                    'entityName' => 'order',
                ],
            ],
        ];
        return $attributeSet;
    }

    /**
     * searches for a custom field set entity by name
     *
     * @return CustomFieldSetEntity|null
     */
    private function getCustomFieldSet(): ?CustomFieldSetEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::CUSTOM_FIELD_SET_NAME));
        $criteria->addAssociation('customFields');
        $criteria->addAssociation('relations');
        /** @var \Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity $customFieldSet */
        $customFieldSet = $this->customFieldSetRepository->search($criteria, $this->context)->first();

        if ($customFieldSet instanceof CustomFieldSetEntity) {
            return $customFieldSet;
        } else {
            return null;
        }
    }

}
