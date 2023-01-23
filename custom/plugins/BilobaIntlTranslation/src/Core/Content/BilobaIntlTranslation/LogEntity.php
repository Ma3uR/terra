<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Core\Content\BilobaIntlTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageEntity;

class LogEntity extends Entity implements \JsonSerializable
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $initiator;
    
    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     *
     * @return self
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @param string $entityType
     *
     * @return self
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;

        return $this;
    }

    /**
     * @return Language
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Language $type
     *
     * @return self
     */
    public function setType(Language $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Language
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Language $status
     *
     * @return self
     */
    public function setStatus(Language $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    /**
     * @param string $initiator
     *
     * @return self
     */
    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     *
     * @return self
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }
}