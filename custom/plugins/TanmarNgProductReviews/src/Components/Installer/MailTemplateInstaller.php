<?php

declare(strict_types=1);

namespace Tanmar\ProductReviews\Components\Installer;

use Shopware\Core\Defaults;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Context;
use \Tanmar\ProductReviews\Components\Installer\InstallerInterface;

class MailTemplateInstaller implements InstallerInterface {

    private $templates = ['invitation', 'coupon', 'notification'];
    private $translations = [
        'invitation' => [
            self::GER_ISO => [
                'typeName' => 'Bewertung Einladung Kunde'
            ],
            self::EN_ISO => [
                'typeName' => 'Rating invitation Customer'
            ]
        ],
        'coupon' => [
            self::GER_ISO => [
                'typeName' => 'Bewertung Gutschein Kunde'
            ],
            self::EN_ISO => [
                'typeName' => 'Rating voucher Customer'
            ]
        ],
        'notification' => [
            self::GER_ISO => [
                'typeName' => 'Bewertung Benachrichtigung Shopbetreiber'
            ],
            self::EN_ISO => [
                'typeName' => 'Rating notification shop owner'
            ]
        ]
    ];

    const TECHNICAL_NAME_PREFIX = 'tanmar_product_reviews_mail';
    const MAIL_TEMPLATE_PATH = '/../../Resources/views/mail/';

    private $context;
    private $mailTemplateRepository;
    private $mailTemplateTypeRepository;
    private $languageRepository;
    protected $germanLanguageId;

    public function __construct(Context $context, $container) {
        $this->context = $context;
        $this->mailTemplateTypeRepository = $container->get('mail_template_type.repository');
        $this->mailTemplateRepository = $container->get('mail_template.repository');
        $this->languageRepository = $container->get('language.repository');
        $this->germanLanguageId = $this->getGermanLanguageId();
    }

    /**
     * Installs a mail template with the given name.
     *
     * @param string $templateName
     */
    public function install() {
        foreach ($this->templates as $templateName) {
            if (is_null($this->getTemplateType($templateName))) {
                $template = $this->createTemplate($templateName);
                if (count($template) > 0) {
                    $this->mailTemplateRepository->create($template, $this->context);
                }
            }
        }
    }

    /**
     * Uninstalls a mail template with the given name.
     *
     * @param string $templateName
     */
    public function uninstall() {
        foreach ($this->templates as $templateName) {
            $templateType = $this->getTemplateType($templateName);
            if ($templateType instanceof MailTemplateTypeEntity) {
                $this->deleteTemplatesOfType($templateType->getId());
                $this->deleteTemplateType($templateType->getId());
            }
        }
    }

    /**
     * Builds and returns an array with all necessary data, that is needed to create a new mail template with the given name.
     * Reads subject, text and html content from twig files, which can be found in Resources/views/mail.
     *
     * @param string $name
     * @return array
     */
    private function createTemplate(string $name): array {
        try {
            $technicalName = self::TECHNICAL_NAME_PREFIX . '.' . $name;
            $template = [
                [
                    'systemDefault' => false,
                    'translations' => [
                        Defaults::LANGUAGE_SYSTEM => [
                            'languageId' => Defaults::LANGUAGE_SYSTEM,
                            'senderName' => '{{ salesChannel.name }}',
                            'description' => 'Tanmar Product Reviews',
                            'subject' => file_get_contents(__DIR__ . self::MAIL_TEMPLATE_PATH . self::EN_ISO . '/' . $name . '.subject.twig'),
                            'contentPlain' => file_get_contents(__DIR__ . self::MAIL_TEMPLATE_PATH . self::EN_ISO . '/' . $name . '.text.twig'),
                            'contentHtml' => file_get_contents(__DIR__ . self::MAIL_TEMPLATE_PATH . self::EN_ISO . '/' . $name . '.html.twig')
                        ]
                    ],
                    'mailTemplateType' => [
                        'technicalName' => $technicalName,
                        'availableEntities' => [
                            'salesChannel' => 'sales_channel',
                            'order' => 'order',
                            'tanmarProductReviews' => 'tanmar_product_reviews'
                        ],
                        'translations' => [
                            Defaults::LANGUAGE_SYSTEM => [
                                'languageId' => Defaults::LANGUAGE_SYSTEM,
                                'name' => $this->translations[$name][self::EN_ISO]['typeName']
                            ]
                        ]
                    ]
                ]
            ];
            if ($this->germanLanguageId) {
                $template[0]['translations'][self::GER_ISO] = [
                    'languageId' => $this->germanLanguageId,
                    'senderName' => '{{ salesChannel.name }}',
                    'description' => 'Tanmar Produkt Bewertung',
                    'subject' => file_get_contents(__DIR__ . self::MAIL_TEMPLATE_PATH . self::GER_ISO . '/' . $name . '.subject.twig'),
                    'contentPlain' => file_get_contents(__DIR__ . self::MAIL_TEMPLATE_PATH . self::GER_ISO . '/' . $name . '.text.twig'),
                    'contentHtml' => file_get_contents(__DIR__ . self::MAIL_TEMPLATE_PATH . self::GER_ISO . '/' . $name . '.html.twig')
                ];
                $template[0]['mailTemplateType']['translations'][self::GER_ISO] = [
                    'languageId' => $this->germanLanguageId,
                    'name' => $this->translations[$name][self::GER_ISO]['typeName']
                ];
            }
            return $template;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * deletes mail template with given id
     *
     * @param string $id
     * @return void
     */
    private function deleteTemplatesOfType(string $id): void {
        //$templates = $templateType->getMailTemplates();
        $templates = $this->getTemplates($id);
        if ($templates instanceof EntityCollection) {
            foreach ($templates->getIds() as $id) {
                $this->mailTemplateRepository->delete([['id' => $id]], $this->context);
            }
        }
    }

    /**
     * deletes mail template type with given id
     *
     * @param string $id
     * @return void
     */
    private function deleteTemplateType(string $id): void {
        $this->mailTemplateTypeRepository->delete([['id' => $id]], $this->context);
    }

    /**
     * searches for german language id by iso
     *
     * @return string
     */
    private function getGermanLanguageId(): string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('language.translationCode.code', self::GER_ISO));
        $language = $this->languageRepository->search($criteria, $this->context)->first();
        if ($language === null) {
            return '';
        } else {
            return $language->getId();
        }
    }

    /**
     * searches for a mail template type entity by technical name
     *
     * @param string $name
     * @return MailTemplateTypeEntity|null
     */
    private function getTemplateType(string $name): ?MailTemplateTypeEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', self::TECHNICAL_NAME_PREFIX . '.' . $name));
        $templateType = $this->mailTemplateTypeRepository->search($criteria, $this->context)->first();
        if ($templateType instanceof MailTemplateTypeEntity) {
            return $templateType;
        } else {
            return null;
        }
    }

    /**
     * searches for all mail templates of a special mail template type by mail template type id
     *
     * @param string $id
     * @return EntityCollection|null
     */
    private function getTemplates(string $id): ?EntityCollection {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $id));
        $templates = $this->mailTemplateRepository->search($criteria, $this->context)->getEntities();
        if ($templates instanceof EntityCollection) {
            return $templates;
        } else {
            return null;
        }
    }

}
