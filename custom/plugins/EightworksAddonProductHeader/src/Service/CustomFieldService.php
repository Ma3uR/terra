<?php declare(strict_types=1);

namespace Eightworks\EightworksAddonProductHeader\Service;

/**
 * \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 *  ______ ________ _______ ______ __  __ _______
 * |  __  |  |  |  |       |   __ \  |/  |     __|
 * |  __  |  |  |  |   -   |      <     <|__     |
 * |______|________|_______|___|__|__|\__|_______|
 *
 *  ####+++---   C  O  N  T  A  C  T   –--++++####
 *
 *  Internetagentur 8works
 *  WEB: 8works.de
 *  MAIL: info@8works.de
 *
 * \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 **/

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Content\Product\ProductDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomFieldService
{
    /**
     * ContainerInterface
     *
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * EntityRepositoryInterface
     *
     * @var EntityRepositoryInterface $customFieldSetRepository
     */
    protected $customFieldSetRepository;

    /**
     * Construct
     *
     * @param ContainerInterface $container
     * @param EntityRepositoryInterface $customFieldSetRepository
     */
    public function __construct($container, EntityRepositoryInterface $customFieldSetRepository)
    {
        $this->container = $container;
        $this->customFieldSetRepository = $customFieldSetRepository;
    }

    /**
     * Add custom fields
     *
     * @param Context $context
     */
    public function addCustomFields(Context $context)
    {
        try {
            $this->customFieldSetRepository->upsert([[
                'name' => 'eightworks_addon_product_header_fields',
                'id' => 'eb4bbeac2ced10c5dce120f84b8965f1',
                'config' => [
                    'label' => [
                        'en-GB' => 'Product-Header',
                        'de-DE' => 'Produkt-Header'
                    ]
                ],
                'customFields' => [
                    [
                        'name' => 'eightworks_addon_product_header_activation',
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'componentName' => 'sw-field',
                            'customFieldType' => 'checkbox',
                            'customFieldPosition' => 10,
                            'label' => [
                                'de-DE' => 'Aktiviere Produkt Header',
                                'en-GB' => 'Activate product header'
                            ],
                            'helpText' => [
                                'de-DE' => 'Ist diese Option gesetzt wird der Produkt-Header für diesen Artikel angezeigt',
                                'en-GB' => 'If this option is set, the product header for this article is displayed'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_fullwidth',
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'componentName' => 'sw-field',
                            'customFieldType' => 'checkbox',
                            'customFieldPosition' => 20,
                            'label' => [
                                'de-DE' => 'Full-Width Header',
                                'en-GB' => 'Full-Width header'
                            ],
                            'helpText' => [
                                'de-DE' => 'Produkt-Header auf voller Breite anzeigen',
                                'en-GB' => 'Show product header as full width banner'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_image',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'componentName' => 'sw-media-field',
                            'customFieldType' => 'media',
                            'customFieldPosition' => 30,
                            'label' => [
                                'de-DE' => 'Header Bild',
                                'en-GB' => 'Header Image'
                            ],
                            'helpText' => [
                                'de-DE' => 'Bild für Produkt-Header',
                                'en-GB' => 'Image for product header'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_image_position',
                        'type' => CustomFieldTypes::SELECT,
                        'config' => [
                            'componentName' => 'sw-single-select',
                            'customFieldType' => 'select',
                            'customFieldPosition' => 31,
                            'options' => [
                                [
                                    'label' => ['en-GB' => 'Center', 'de-DE' => 'Center'],
                                    'value' => 'align-self-center',
                                    'default' => true
                                ],
                                [
                                    'label' => ['en-GB' => 'Bottom', 'de-DE' => 'Bottom'],
                                    'value' => 'align-self-end'
                                ],
                                [
                                    'label' => ['en-GB' => 'Top', 'de-DE' => 'Top'],
                                    'value' => 'align-self-start'
                                ]
                            ],
                            'label' => [
                                'de-DE' => 'Bilder-Position',
                                'en-GB' => 'Image position'
                            ],
                            'helpText' => [
                                'de-DE' => 'Optional: Feld um die Position des Headers festzulegen',
                                'en-GB' => 'Optional: Field to define the position of the header'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_embed',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'componentName' => 'sw-field',
                            'customFieldType' => 'text',
                            'customFieldPosition' => 40,
                            'label' => [
                                'de-DE' => 'Video-Background (Ersetzt Header Bild)',
                                'en-GB' => 'Video-Background (Replaces header image)'
                            ],
                            'helpText' => [
                                'de-DE' => 'Optional: Feld für Video-URLs (Vimeo, Youtube, ..)',
                                'en-GB' => 'Optional: Field for Video-URLs (Vimeo, Youtube, ..)'
                            ],
                            'placeholder' => [
                                'de-DE' => 'https://player.vimeo.com/video/1084537?autoplay=1&loop=1&title=0&byline=0&portrait=0&autopause=0&muted=1',
                                'en-GB' => 'https://player.vimeo.com/video/1084537?autoplay=1&loop=1&title=0&byline=0&portrait=0&autopause=0&muted=1'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_maxheight',
                        'type' => CustomFieldTypes::SELECT,
                        'config' => [
                            'componentName' => 'sw-single-select',
                            'customFieldType' => 'select',
                            'customFieldPosition' => 50,
                            'options' => [
                                [
                                    'label' => ['en-GB' => 'Auto', 'de-DE' => 'Auto'],
                                    'value' => 'h-auto',
                                    'default' => true
                                ],
                                [
                                    'label' => ['en-GB' => 'Small', 'de-DE' => 'Small'],
                                    'value' => '150'
                                ],
                                [
                                    'label' => ['en-GB' => 'Medium', 'de-DE' => 'Medium'],
                                    'value' => '300'
                                ],
                                [
                                    'label' => ['en-GB' => 'Large', 'de-DE' => 'Large'],
                                    'value' => '450'
                                ],
                                [
                                    'label' => ['en-GB' => 'X-Large', 'de-DE' => 'X-Large'],
                                    'value' => '600'
                                ]
                            ],
                            'label' => [
                                'de-DE' => 'Vertikale Ausdehnung begrenzen',
                                'en-GB' => 'Max height'
                            ],
                            'placeholder' => [
                                'de-DE' => 'Optional: Feld um eine maximale vertikale Ausdehnung für den Header festzulegen',
                                'en-GB' => 'Optional: Field to set a maximum height for the header'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_htmloverlay',
                        'type' => CustomFieldTypes::HTML,
                        'config' => [
                            'componentName' => 'sw-text-editor',
                            'customFieldType' => 'text',
                            'customFieldPosition' => 60,
                            'label' => [
                                'de-DE' => 'Overlay-Text',
                                'en-GB' => 'Overlay-Text'
                            ],
                            'helpText' => [
                                'de-DE' => 'Optional: Feld für eine Headline, Text und mehr (Overlay)',
                                'en-GB' => 'Optional: Field for a heading, text and more (overlay)'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_htmloverlay_mobile_scaling',
                        'type' => CustomFieldTypes::SELECT,
                        'config' => [
                            'componentName' => 'sw-single-select',
                            'customFieldType' => 'select',
                            'customFieldPosition' => 61,
                            'options' => [
                                [
                                    'label' => ['en-GB' => '100%', 'de-DE' => '100%'],
                                    'value' => '100',
                                    'default' => true
                                ],
                                [
                                    'label' => ['en-GB' => '75%', 'de-DE' => '75%'],
                                    'value' => '75'
                                ],
                                [
                                    'label' => ['en-GB' => '50%', 'de-DE' => '50%'],
                                    'value' => '50'
                                ],
                                [
                                    'label' => ['en-GB' => '25%', 'de-DE' => '25%'],
                                    'value' => '25'
                                ],
                                [
                                    'label' => ['en-GB' => 'Hide', 'de-DE' => 'Ausblenden'],
                                    'value' => '0'
                                ]
                            ],
                            'label' => [
                                'de-DE' => 'Skalierung Overlay auf Smartphones',
                                'en-GB' => 'Scaling of overlay on smartphones'
                            ],
                            'placeholder' => [
                                'de-DE' => 'Optional: Overlay auf Smartphones verkleinert darstellen oder gar ausblenden',
                                'en-GB' => 'Optional: Downsize or even hide the overlay on smartphones'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_schedule_start',
                        'type' => CustomFieldTypes::DATETIME,
                        'config' => [
                            'componentName' => 'sw-field',
                            'customFieldType' => 'datepicker',
                            'customFieldPosition' => 70,
                            'label' => [
                                'de-DE' => 'Start-Datum (Zeitsteuerung)',
                                'en-GB' => 'Start-Date (Time control)'
                            ],
                            'helpText' => [
                                'de-DE' => 'Optional: Zeitsteuerung Startdatum. Ab diesem Moment wird der Header angezeigt.',
                                'en-GB' => 'Optional: Time control start date. From this moment the header will be shown'
                            ]
                        ]
                    ],
                    [
                        'name' => 'eightworks_addon_product_header_schedule_end',
                        'type' => CustomFieldTypes::DATETIME,
                        'config' => [
                            'componentName' => 'sw-field',
                            'customFieldType' => 'datepicker',
                            'customFieldPosition' => 80,
                            'label' => [
                                'de-DE' => 'Ablaufdatum (Zeitsteuerung)',
                                'en-GB' => 'Expiration-Date (Time control)'
                            ],
                            'helpText' => [
                                'de-DE' => 'Optional: Zeitsteuerung Ablaufdatum. Ab diesem Moment wird der Header versteckt.',
                                'en-GB' => 'Optional: Time control expiration date. From this moment the header will be hidden'
                            ]
                        ]
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => $this->container->get(ProductDefinition::class)->getEntityName()
                    ]
                ]
            ]], $context);
        } catch (Exception $e) {
            // @todo Handle Exception
        }
    }

    /**
     * Delete custom fields
     *
     * @param Context $context
     */
    public function deleteCustomFields(Context $context)
    {
        try {
            $this->customFieldSetRepository->delete([[
                'name' => 'eightworks_addon_product_header_fields',
                'id' => 'eb4bbeac2ced10c5dce120f84b8965f1'
            ]], $context);
        } catch (Exception $e) {
            // @todo Handle Exception
        }
    }
}
