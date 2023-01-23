<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\InstallationLibrary\NumberRange;

use Doctrine\DBAL\Connection;
use Exception;
use Pickware\InstallationLibrary\IdLookUpService;
use Shopware\Core\Framework\Uuid\Uuid;

class NumberRangeInstaller
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
     * @param string $technicalName Matches number_range_type.technical_name
     * @param string $pattern Matches number_range.pattern (e.g. '{n}')
     * @param int $start Matches number_range.start (e.g. 1000)
     * @param array $translations Number range and number range type name for the locale codes de-DE and en-GB. Eg. [
     *   'de-DE' => 'Lieferanten',
     *   'en-GB' => 'Suppliers',
     *]
     */
    public function ensureNumberRangeExists(
        string $technicalName,
        string $pattern,
        int $start,
        array $translations
    ): void {
        if (!array_key_exists('de-DE', $translations) || !array_key_exists('en-GB', $translations)) {
            throw new Exception('Number range translations must support locale codes \'de-DE\' and \'en-GB\'');
        }

        $this->db->executeStatement(
            'INSERT INTO `number_range_type` (
                `id`,
                `technical_name`,
                `global`,
                `created_at`
            ) VALUES (
                :id,
                :technicalName,
                1,
                NOW()
            ) ON DUPLICATE KEY UPDATE `id` = `id`',
            [
                'id' => Uuid::randomBytes(),
                'technicalName' => $technicalName,
            ]
        );
        /** @var string $numberRangeTypeId */
        $numberRangeTypeId = $this->db->fetchOne(
            'SELECT `id` FROM `number_range_type` WHERE `technical_name` = :technicalName',
            [
                'technicalName' => $technicalName,
            ]
        );

        // Since number ranges are not unique per number range type, check for existing number ranges beforehand
        /** @var string $numberRangeId */
        $numberRangeId = $this->db->fetchOne(
            'SELECT `id` FROM `number_range` WHERE `type_id` = :numberRangeTypeId LIMIT 1',
            [
                'numberRangeTypeId' => $numberRangeTypeId,
            ]
        );
        if (!$numberRangeId) {
            $numberRangeId = Uuid::randomBytes();
            $this->db->executeStatement(
                'INSERT INTO `number_range` (
                    `id`,
                    `type_id`,
                    `global`,
                    `pattern`,
                    `start`,
                    `created_at`
                ) VALUES (
                    :id,
                    :numberRangeTypeId,
                    1,
                    :pattern,
                    :start,
                    NOW()
                )',
                [
                    'id' => $numberRangeId,
                    'numberRangeTypeId' => $numberRangeTypeId,
                    'pattern' => $pattern,
                    'start' => $start,
                ]
            );
        }

        foreach ($translations as $localeCode => $translatedName) {
            $languageId = $this->idLookUpService->lookUpLanguageIdForLocaleCode($localeCode);
            if (!$languageId) {
                continue;
            }

            $this->db->executeStatement(
                'INSERT INTO `number_range_type_translation` (
                    `number_range_type_id`,
                    `language_id`,
                    `type_name`,
                    `created_at`
                ) VALUES (
                    :numberRangeTypeId,
                    :languageId,
                    :translatedName,
                    NOW(3)
                ) ON DUPLICATE KEY UPDATE `number_range_type_id` = `number_range_type_id`',
                [
                    'numberRangeTypeId' => $numberRangeTypeId,
                    'languageId' => $languageId,
                    'translatedName' => $translatedName,
                ]
            );
            $this->db->executeStatement(
                'INSERT INTO `number_range_translation` (
                    `number_range_id`,
                    `language_id`,
                    `name`,
                    `created_at`
                ) VALUES (
                    :numberRangeId,
                    :languageId,
                    :translatedName,
                    NOW(3)
                ) ON DUPLICATE KEY UPDATE `number_range_id` = `number_range_id`',
                [
                    'numberRangeId' => $numberRangeId,
                    'languageId' => $languageId,
                    'translatedName' => $translatedName,
                ]
            );
        }
    }
}
