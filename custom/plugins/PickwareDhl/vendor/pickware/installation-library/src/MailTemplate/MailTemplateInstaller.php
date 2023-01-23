<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\InstallationLibrary\MailTemplate;

use Doctrine\DBAL\Connection;
use Exception;
use Pickware\InstallationLibrary\IdLookUpService;
use Shopware\Core\Content\MailTemplate\MailTemplateActions;
use Shopware\Core\Framework\Uuid\Uuid;

class MailTemplateInstaller
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var IdLookUpService
     */
    private $idLookUpService;

    public function __construct(Connection $db, IdLookUpService $idLookUpService)
    {
        $this->db = $db;
        $this->idLookUpService = $idLookUpService;
    }

    /**
     * Ensures that a MailTemplateType exists for the given technical name.
     *
     * @param string $technicalName Matches mail_template_type.technical_name
     * @param array $nameTranslations Mail template type name for the locale codes de-DE and en-GB. Eg. [
     *   'de-DE' => 'Mein Mail Template',
     *   'en-GB' => 'My Mail Template',
     *]
     * @param array $availableEntities
     * @return string id of the MailTemplateType
     */
    public function ensureMailTemplateType(string $technicalName, array $nameTranslations, array $availableEntities): string
    {
        if (!array_key_exists('de-DE', $nameTranslations) || !array_key_exists('en-GB', $nameTranslations)) {
            throw new Exception('Mail template type translations must support locale codes \'de-DE\' and \'en-GB\'');
        }

        $this->db->executeStatement(
            'INSERT INTO `mail_template_type` (
                `id`,
                `technical_name`,
                `available_entities`,
                `created_at`
            ) VALUES (
                :id,
                :technicalName,
                :availableEntities,
                NOW()
            ) ON DUPLICATE KEY UPDATE `id` = `id`',
            [
                'id' => Uuid::randomBytes(),
                'technicalName' => $technicalName,
                'availableEntities' => json_encode($availableEntities),
            ]
        );
        /** @var string $mailTemplateTypeId */
        $mailTemplateTypeId = $this->db->fetchOne(
            'SELECT `id` FROM `mail_template_type` WHERE `technical_name` = :technicalName',
            [
                'technicalName' => $technicalName,
            ]
        );

        foreach ($nameTranslations as $localeCode => $translatedName) {
            $languageId = $this->idLookUpService->lookUpLanguageIdForLocaleCode($localeCode);
            if (!$languageId) {
                continue;
            }
            $this->db->executeStatement(
                'INSERT INTO `mail_template_type_translation` (
                    `mail_template_type_id`,
                    `language_id`,
                    `name`,
                    `created_at`
                ) VALUES (
                    :mailTemplateTypeId,
                    :languageId,
                    :translatedName,
                    NOW(3)
                ) ON DUPLICATE KEY UPDATE `mail_template_type_id` = `mail_template_type_id`',
                [
                    'mailTemplateTypeId' => $mailTemplateTypeId,
                    'languageId' => $languageId,
                    'translatedName' => $translatedName,
                ]
            );
        }

        return $mailTemplateTypeId;
    }

    /**
     * Ensures that a MailTemplate exists for the given MailTemplateType id.
     *
     * @param string $mailTemplateTypeId
     * @return string id of the MailTemplate
     */
    public function ensureMailTemplate(string $mailTemplateTypeId): string
    {
        $mailTemplateId = $this->getMailTemplateId($mailTemplateTypeId);

        if ($mailTemplateId) {
            return $mailTemplateId;
        }

        $this->db->executeStatement(
            'INSERT INTO `mail_template` (
                `id`,
                `mail_template_type_id`,
                `system_default`,
                `created_at`
            ) VALUES (
                :id,
                :mailTemplateTypeId,
                1,
                NOW()
            ) ON DUPLICATE KEY UPDATE `id` = `id`',
            [
                'id' => Uuid::randomBytes(),
                'mailTemplateTypeId' => $mailTemplateTypeId,
            ]
        );

        return $this->getMailTemplateId($mailTemplateTypeId);
    }

    /**
     * @param string $mailTemplateTypeId
     * @return string|bool
     */
    private function getMailTemplateId(string $mailTemplateTypeId)
    {
        return $this->db->fetchOne(
            'SELECT `id` FROM `mail_template` WHERE `mail_template_type_id` = :mailTemplateTypeId',
            [
                'mailTemplateTypeId' => $mailTemplateTypeId,
            ]
        );
    }

    /**
     * Ensures that translations (content and mail properties) for the given MailTemplate exist.
     *
     * @param string $mailTemplateId
     * @param MailTemplateTranslation $mailTemplateTranslation
     */
    public function ensureMailTemplateTranslation(
        string $mailTemplateId,
        MailTemplateTranslation $mailTemplateTranslation
    ): void {
        $this->db->executeStatement(
            'INSERT INTO `mail_template_translation` (
                `mail_template_id`,
                `language_id`,
                `sender_name`,
                `subject`,
                `description`,
                `content_html`,
                `content_plain`,
                `created_at`
            )
            SELECT
                :mailTemplateId,
                `language`.`id`,
                :senderName,
                :subject,
                :description,
                :content_html,
                :content_plain,
                NOW(3)
            FROM `language`
            INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id`
            WHERE `locale`.`code` = :localeCode
            ON DUPLICATE KEY UPDATE `mail_template_id` = `mail_template_id`',
            [
                'mailTemplateId' => $mailTemplateId,
                'senderName' => $mailTemplateTranslation->getSender(),
                'subject' => $mailTemplateTranslation->getSubject(),
                'description' => $mailTemplateTranslation->getDescription(),
                'content_html' => $mailTemplateTranslation->getContentHtml(),
                'content_plain' => $mailTemplateTranslation->getContentPlain(),
                'localeCode' => $mailTemplateTranslation->getLocaleCode(),
            ]
        );
    }

    public function ensureMailActionEvent(string $mailTemplateTypeId, string $mailTemplateId, string $eventName): void
    {
        $existingEventAction = $this->db->fetchAllAssociative(
            'SELECT * FROM `event_action`
            WHERE `event_name` = :eventName
            AND `action_name` = :actionName',
            [
                'eventName' => $eventName,
                'actionName' => MailTemplateActions::MAIL_TEMPLATE_MAIL_SEND_ACTION,
            ]
        );
        if ($existingEventAction) {
            return;
        }

        $this->db->executeStatement(
            'INSERT INTO `event_action` (
                `id`,
                `event_name`,
                `action_name`,
                `config`,
                `created_at`
            ) VALUES(
                :id,
                :eventName,
                :actionName,
                :config,
                NOW()
            )',
            [
                'id' => Uuid::randomBytes(),
                'eventName' => $eventName,
                'actionName' => MailTemplateActions::MAIL_TEMPLATE_MAIL_SEND_ACTION,
                'config' => json_encode([
                    'mail_template_type_id' => Uuid::fromBytesToHex($mailTemplateTypeId),
                    'mail_template_id' => Uuid::fromBytesToHex($mailTemplateId),
                ]),
            ]
        );
    }
}
