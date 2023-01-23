<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\Recipient;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\RecipientAttribute;

/**
 * Interface Attributes
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Interfaces
 */
interface Attributes
{

    const CLASS_NAME = __CLASS__;
    
    /**
     * Get attributes from integration with translated params in system language.
     *
     * It should set name, description, preview_value and default_value for each attribute available in system.
     *
     * @return RecipientAttribute[]
     *   List of available attributes in the system.
     */
    public function getAttributes();

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
    public function getRecipientAttributes(Recipient $recipient);
}
