<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Sync;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Serializer;

/**
 * Class RecipientStatusUpdateSyncTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Sync
 */
abstract class RecipientStatusUpdateSyncTask extends BaseSyncTask
{
    /**
     * Array of recipient emails that should be updated.
     *
     * @var array
     */
    public $recipientEmails;

    /**
     * RecipientStatusUpdateSyncTask constructor.
     *
     * @param array $recipientEmails Array of recipient emails that should be updated.
     */
    public function __construct(array $recipientEmails)
    {
        $this->recipientEmails = $recipientEmails;
    }

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        return new static($array['recipientEmails']);
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->recipientEmails);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->recipientEmails = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('recipientEmails' => $this->recipientEmails);
    }
}
