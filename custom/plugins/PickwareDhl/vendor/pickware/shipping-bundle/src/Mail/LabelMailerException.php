<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Mail;

use Exception;

class LabelMailerException extends Exception
{
    public static function noMailTemplateConfiguredForCarrier(string $carrierTechnicalName): self
    {
        return new self(sprintf(
            'The return label mail template for carrier "%s" does not exist anymore. Try to reinstall the plugin.',
            $carrierTechnicalName
        ));
    }

    public static function failedToRenderMailTemplate(string $mailTemplateId, string $orderId): self
    {
        return new self(sprintf(
            'The mail template with ID "%s" failed to render for order with ID "%s".',
            $mailTemplateId,
            $orderId
        ));
    }

    public static function orderDeliveryNotFound(string $orderDeliveryId): self
    {
        return new self(sprintf('The order-delivery with ID "%s" was not found.', $orderDeliveryId));
    }

    public static function shipmentNotFound(string $shipmentId): self
    {
        return new self(
            sprintf('The shipment with ID %s was not found.', $shipmentId)
        );
    }

    public static function shipmentHasNoReturnLabelDocuments(string $shipmentId): self
    {
        return new self(
            sprintf('The shipment with ID %s has no return label documents.', $shipmentId)
        );
    }
}
