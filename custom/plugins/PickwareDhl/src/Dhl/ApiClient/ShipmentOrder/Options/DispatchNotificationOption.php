<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\ApiClient\ShipmentOrder\Options;

/**
 * Enables the dispatch notification.
 *
 * The delivery notification is sent to the shipment receiver once the merchant has shipped the shipment.
 *
 * Description by DHL support:
 * Versandbestätigung: Die Versandbestätigung wird nach erfolgtem Tagesschluss an dort hinterlegte
 * Mailadresse ($mail) versandt.
 */
class DispatchNotificationOption extends AbstractShipmentOrderOption
{
    /**
     * @var string
     */
    private $email;

    /**
     * @param string $email The email address DHL should send the notification to.
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function applyToShipmentOrderArray(array &$shipmentOrderArray): void
    {
        $shipmentOrderArray['Shipment']['ShipmentDetails']['Notification']['recipientEmailAddress'] = $this->email;
    }
}
