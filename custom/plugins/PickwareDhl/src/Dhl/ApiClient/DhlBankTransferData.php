<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\ApiClient;

use InvalidArgumentException;

class DhlBankTransferData
{
    public const CUSTOMER_REFERENCE_TEMPLATE_PLACEHOLDER_NAME = '{{ customerReference }}';

    /**
     * @var string
     */
    private $iban;

    /**
     * @var string
     */
    private $accountOwnerName;

    /**
     * @var string
     */
    private $bankName;

    /**
     * @var string
     */
    private $note1;

    /**
     * @var string
     */
    private $note2;

    /**
     * @var
     */
    private $bic;

    /**
     * @var string
     */
    private $accountReference;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->iban = strval($config['iban'] ?? '');
        $this->accountOwnerName = strval($config['accountOwnerName'] ?? '');
        $this->bankName = strval($config['bankName'] ?? '');
        $this->note1 = strval($config['note1'] ?? '');
        $this->note2 = strval($config['note2'] ?? '');
        $this->bic = strval($config['bic'] ?? '');
        $this->accountReference = strval($config['accountReference'] ?? '');

        $requiredFields = [
            'iban',
            'accountOwnerName',
            'bankName',
        ];
        foreach ($requiredFields as $requiredField) {
            if ($this->$requiredField === '') {
                throw new InvalidArgumentException(sprintf(
                    'Property %s of class %s must be a non empty string.',
                    $requiredField,
                    self::class
                ));
            }
        }
    }

    /**
     * @param string $customerReference
     * @return array
     */
    public function getAsArrayForShipmentDetails(string $customerReference): array
    {
        return [
            'accountOwner' => $this->accountOwnerName,
            'bankName' => $this->bankName,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'note1' => self::insertCustomerReference($this->note1, $customerReference),
            'note2' => self::insertCustomerReference($this->note2, $customerReference),
            'accountreference' => self::insertCustomerReference($this->accountReference, $customerReference),
        ];
    }

    /**
     * @param string $string
     * @param string $customerReference
     * @return mixed
     */
    private static function insertCustomerReference(string $string, string $customerReference)
    {
        return str_replace(self::CUSTOMER_REFERENCE_TEMPLATE_PLACEHOLDER_NAME, $customerReference, $string);
    }
}
