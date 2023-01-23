<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Entity;

/**
 * Class SpecialTagCollection
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Entity
 */
class SpecialTagCollection extends TagCollection
{
    /**
     * SpecialTagCollection constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\SpecialTag[] $tags List of special tags.
     */
    public function __construct(array $tags = array())
    {
        parent::__construct();

        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $this->addTag($tag);
            }
        }
    }

    /**
     * Adds tag to collection if it does not exist.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\SpecialTag|AbstractTag $tag Special tag.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Entity\TagCollection
     *   List of special tags.
     */
    public function addTag($tag)
    {
        if ($tag instanceof SpecialTag) {
            return parent::addTag($tag);
        }

        throw new \InvalidArgumentException('Special tag collection accepts only SpecialTag instances.');
    }
}
