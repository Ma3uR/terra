<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\InstallationLibrary\StateMachine;

use Doctrine\DBAL\Connection;
use Exception;
use Pickware\InstallationLibrary\IdLookUpService;
use Shopware\Core\Framework\Uuid\Uuid;

class StateMachineInstaller
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
     * Ensures that a given state machine exists.
     *
     * @param string $technicalName
     * @param array $translations technical name translations for the locale codes de-DE and en-GB. Eg. [
     *      'de-DE' => 'MeineEntity Status',
     *      'en-GB' => 'CustomEntity State',
     *  ]
     * @return string id of the state machine
     */
    public function upsertStateMachine(string $technicalName, array $translations): string
    {
        if (!array_key_exists('de-DE', $translations) || !array_key_exists('en-GB', $translations)) {
            throw new Exception('State machine translations must support locale codes \'de-DE\' and \'en-GB\'');
        }

        $this->db->executeStatement(
            'INSERT INTO `state_machine` (
                `id`,
                `technical_name`,
                `created_at`
            ) VALUES (
                :id,
                :technicalName,
                NOW()
            ) ON DUPLICATE KEY UPDATE `technical_name` = `technical_name`',
            [
                'id' => Uuid::randomBytes(),
                'technicalName' => $technicalName,
            ]
        );
        /** @var string $stateMachineId */
        $stateMachineId = $this->db->fetchOne(
            'SELECT `id` FROM `state_machine` WHERE `technical_name` = :technicalName',
            [
                'technicalName' => $technicalName,
            ]
        );

        foreach ($translations as $localeCode => $translatedName) {
            $languageId = $this->idLookUpService->lookUpLanguageIdForLocaleCode($localeCode);
            if (!$languageId) {
                continue;
            }

            $this->db->executeStatement(
                'INSERT INTO `state_machine_translation` (
                    `language_id`,
                    `state_machine_id`,
                    `name`,
                    `created_at`
                ) VALUES (
                    :languageId,
                    :stateMachineId,
                    :translatedName,
                    NOW(3)
                ) ON DUPLICATE KEY UPDATE `state_machine_id` = `state_machine_id`',
                [
                    'languageId' => $languageId,
                    'stateMachineId' => $stateMachineId,
                    'translatedName' => $translatedName,
                ]
            );
        }

        return $stateMachineId;
    }

    /**
     * Ensures that the given state machine state exists.
     *
     * @param string $stateMachineId
     * @param string $technicalName
     * @param array $translations technical name translations for the locale codes de-DE and en-GB. Eg. [
     *      'de-DE' => 'Konkreter Status',
     *      'en-GB' => 'Specific State',
     *  ]
     * @return string id of the state machine state
     */
    public function upsertStateMachineState(string $stateMachineId, string $technicalName, array $translations): string
    {
        if (!array_key_exists('de-DE', $translations) || !array_key_exists('en-GB', $translations)) {
            throw new Exception('State machine state translations must support locale codes \'de-DE\' and \'en-GB\'');
        }

        $this->db->executeStatement(
            'INSERT INTO `state_machine_state` (
                `id`,
                `technical_name`,
                `state_machine_id`,
                `created_at`
            ) VALUES (
                :id,
                :technicalName,
                :stateMachineId,
                NOW()
            ) ON DUPLICATE KEY UPDATE `technical_name` = `technical_name`',
            [
                'id' => Uuid::randomBytes(),
                'technicalName' => $technicalName,
                'stateMachineId' => $stateMachineId,
            ]
        );
        /** @var string $stateMachineStateId */
        $stateMachineStateId = $this->db->fetchOne(
            'SELECT `id`
             FROM `state_machine_state`
             WHERE `technical_name` = :technicalName
             AND `state_machine_id` = :stateMachineId',
            [
                'technicalName' => $technicalName,
                'stateMachineId' => $stateMachineId,
            ]
        );

        foreach ($translations as $localeCode => $translatedName) {
            $languageId = $this->idLookUpService->lookUpLanguageIdForLocaleCode($localeCode);
            if (!$languageId) {
                continue;
            }

            $this->db->executeStatement(
                'INSERT INTO `state_machine_state_translation` (
                    `language_id`,
                    `state_machine_state_id`,
                    `name`,
                    `created_at`
                ) VALUES (
                    :languageId,
                    :stateMachineStateId,
                    :translatedName,
                    NOW(3)
                ) ON DUPLICATE KEY UPDATE `state_machine_state_id` = `state_machine_state_id`',
                [
                    'languageId' => $languageId,
                    'stateMachineStateId' => $stateMachineStateId,
                    'translatedName' => $translatedName,
                ]
            );
        }

        return $stateMachineStateId;
    }

    public function upsertStateTransition(
        string $stateMachineTechnicalName,
        string $fromStateMachineStateTechnicalName,
        string $toStateMachineStateTechnicalName,
        string $actionName
    ): void {
        $this->db->executeStatement(
            'INSERT INTO `state_machine_transition` (
                `id`,
                `action_name`,
                `state_machine_id`,
                `from_state_id`,
                `to_state_id`,
                `created_at`
            )
            SELECT
                :id AS `id`,
                :actionName AS `action_name`,
                `state_machine`.`id` AS `state_machine_id`,
                `fromState`.`id` AS `from_state_id`,
                `toState`.`id` AS `to_state_id`,
                NOW(3) AS `created_at`
            FROM `state_machine`
            LEFT JOIN `state_machine_state` `fromState`
                ON `fromState`.`technical_name` = :fromStateMachineStateTechnicalName
                AND `fromState`.`state_machine_id` = `state_machine`.`id`
            LEFT JOIN `state_machine_state` `toState`
                ON `toState`.`technical_name` = :toStateMachineStateTechnicalName
                AND `toState`.`state_machine_id` = `state_machine`.`id`
            WHERE `state_machine`.`technical_name` = :stateMachineTechnicalName
            ON DUPLICATE KEY UPDATE `action_name` = `action_name`',
            [
                'id' => Uuid::randomBytes(),
                'stateMachineTechnicalName' => $stateMachineTechnicalName,
                'fromStateMachineStateTechnicalName' => $fromStateMachineStateTechnicalName,
                'toStateMachineStateTechnicalName' => $toStateMachineStateTechnicalName,
                'actionName' => $actionName,
            ]
        );
    }

    public function setInitialStateMachineState(string $stateMachineId, string $stateMachineStateId): void
    {
        $this->db->executeStatement(
            'UPDATE `state_machine` SET `initial_state_id` = :stateMachineStateId WHERE `id` = :stateMachineId',
            [
                'stateMachineId' => $stateMachineId,
                'stateMachineStateId' => $stateMachineStateId,
            ]
        );
    }

    /**
     * Ensures that state machine state transitions TO the given state machine state exist from ALL POSSIBLE state
     * machine states of this state machine. All transitions will have the same action name.
     *
     * @param string $stateMachineTechnicalName
     * @param string $toStateMachineStateTechnicalName
     * @param string $actionNameForAllTransitions
     */
    public function upsertStateTransitionsFromAllPossibleStates(
        string $stateMachineTechnicalName,
        string $toStateMachineStateTechnicalName,
        string $actionNameForAllTransitions
    ): void {
        $fromStateMachineStateTechnicalNames = $this->db->fetchFirstColumn(
            'SELECT `state_machine_state`.`technical_name`
             FROM `state_machine_state`
             LEFT JOIN `state_machine` ON `state_machine`.`id` = `state_machine_state`.`state_machine_id`
             WHERE `state_machine_state`.`technical_name` <> :toStateMachineStateTechnicalName
             AND `state_machine`.`technical_name` = :stateMachineTechnicalName',
            [
                'toStateMachineStateTechnicalName' => $toStateMachineStateTechnicalName,
                'stateMachineTechnicalName' => $stateMachineTechnicalName,
            ]
        );

        foreach ($fromStateMachineStateTechnicalNames as $fromStateMachineStateTechnicalName) {
            $this->upsertStateTransition(
                $stateMachineTechnicalName,
                $fromStateMachineStateTechnicalName,
                $toStateMachineStateTechnicalName,
                $actionNameForAllTransitions
            );
        }
    }
}
