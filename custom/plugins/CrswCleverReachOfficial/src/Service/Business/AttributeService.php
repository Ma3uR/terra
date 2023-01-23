<?php

namespace Crsw\CleverReachOfficial\Service\Business;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Recipient;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\RecipientAttribute;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces\Attributes;
use Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Configuration;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AttributeService
 *
 * @package Crsw\CleverReachOfficial\Service\Business
 */
class AttributeService implements Attributes
{
    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * Attributes which don't exist for Shopware newsletter entities and therefore shouldn't be sent
     *
     * @var array
     */
    private static $skipForNewsletterEntity = ['birthday', 'customernumber', 'country', 'company', 'phone', 'lastorderdate'];

    /**
     * @var array
     */
    private static $attributes = [
        'email' => [
            'id' => 'account.loginMailPlaceholder',
        ],
        'salutation' => [
            'id' => 'account.personalSalutationLabel',
        ],
        'title' => [
            'id' => 'account.personalTitleLabel',
        ],
        'firstname' => [
            'id' => 'account.personalFirstNameLabel',
        ],
        'lastname' => [
            'id' => 'account.personalLastNameLabel',
        ],
        'birthday' => [
            'id' => 'account.personalBirthdayLabel',
        ],
        'lastorderdate' => [
            'id' => 'account.overviewNewestOrderHeader'
        ],
        'customernumber' => [
            'id' => 'Customer number',
        ],
        'language' => [
            'id' => 'Language',
        ],
        'street' => [
            'id' => 'address.streetLabel',
        ],
        'zip' => [
            'id' => 'address.zipcodeLabel',
        ],
        'shop' => [
            'id' => 'Sales Channel',
        ],
        'city' => [
            'id' => 'address.cityLabel',
        ],
        'company' => [
            'id' => 'address.companyNameLabel',
        ],
        'country' => [
            'id' => 'address.countryLabel',
        ],
        'state' => [
            'id' => 'address.countryStateLabel',
        ],
        'phone' => [
            'id' => 'address.phoneNumberLabel',
        ],
        'newsletter' => [
            'id' => 'newsletter.headline',
        ],
    ];

    private static $translations = [
        'Sales Channel' => [
            'en-EN' => 'Sales Channel',
            'de-DE' => 'Verkaufskanal',
            'es-ES' => 'Canal de ventas',
            'it-IT' => 'Canale di vendita',
            'fr-FR' => 'Canal de vente',
        ],
        'Customer number' => [
            'en-EN' => 'Customer number',
            'de-DE' => 'Kunden-Nr',
            'es-ES' => 'Número de cliente',
            'it-IT' => 'Numero cliente',
            'fr-FR' => 'Numéro de client',
        ],
        'Language' => [
            'en-EN' => 'Language',
            'de-DE' => 'Sprache',
            'es-ES' => 'Idioma',
            'it-IT' => 'Lingua',
            'fr-FR' => 'Langue',
        ]

    ];

    /**
     * AttributeService constructor.
     *
     * @param TranslatorInterface $translator
     * @param Configuration $configService
     */
    public function __construct(
        TranslatorInterface $translator,
        Configuration $configService

    ) {
        $this->translator = $translator;
        $this->configService = $configService;
    }

    /**
     * Get attributes from integration with translated params in system language.
     *
     * It should set name, description, preview_value and default_value for each attribute available in system.
     *
     * @return RecipientAttribute[]
     *   List of available attributes in the system.
     */
    public function getAttributes(): array
    {
        $attributes = [];
        $lang = $this->configService->getLanguage();
        $catalogue = $this->translator->getCatalogue($lang);
        foreach (self::$attributes as $attributeName => $attributeDetails) {
            $recipientAttribute = new RecipientAttribute($attributeName);
            if (in_array($attributeName, ['shop', 'customernumber', 'language'], true)) {
                $desc = $this->getTranslation($lang, $attributeDetails['id']);
            } else {
                $desc = $catalogue->get($attributeDetails['id'], 'storefront');
                if ($desc === $attributeDetails['id']) {
                    // Fallback to the default language translation.
                    $desc = $catalogue->get($attributeDetails['id']);
                }
            }

            $recipientAttribute->setDescription($desc);

            $attributes[] = $recipientAttribute;
        }

        return $attributes;
    }


    /**
     * Get recipient specific attributes from integration with translated params in system language.
     *
     * It should set name, description, preview_value and default_value for each attribute available in system for a
     * given Recipient entity instance.
     *
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Recipient $recipient
     *
     * @return RecipientAttribute[]
     *   List of available attributes in the system for a given Recipient.
     */
    public function getRecipientAttributes(Recipient $recipient): array
    {
        $allAttributes = $this->getAttributes();
        if (strpos($recipient->getInternalId(), RecipientService::SUBSCRIBER_PREFIX) === 0) {
            return $this->formatNewsletterAttributes($allAttributes);
        }

        return $allAttributes;
    }

    /**
     * Returns only attributes which exists for Shopware newsletter entity
     *
     * @param RecipientAttribute[] $allAttributes
     *
     * @return array
     */
    private function formatNewsletterAttributes(array $allAttributes): array
    {
        $newsletterAttributes = [];
        foreach ($allAttributes as $attribute) {
            if (!in_array($attribute->getName(), self::$skipForNewsletterEntity, true)) {
                $newsletterAttributes[] = $attribute;
            }
        }

        return $newsletterAttributes;
    }

    /**
     * @param string $lang
     * @param string $id
     *
     * @return mixed|string
     */
    private function getTranslation(string $lang, string $id)
    {
        if (!empty(static::$translations[$id][$lang])) {
            return static::$translations[$id][$lang];
        }

        return $id;
    }
}
