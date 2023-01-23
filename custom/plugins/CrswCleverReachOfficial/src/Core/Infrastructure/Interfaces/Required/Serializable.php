<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required;

/**
 * Interface Serializable
 *
 * @package Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required
 */
interface Serializable extends \Serializable
{
    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array);

    /**
     * Transforms entity to array.
     *
     * @return array
     */
   public function toArray();
}
