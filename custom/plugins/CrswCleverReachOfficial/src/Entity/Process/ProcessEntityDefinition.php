<?php

namespace Crsw\CleverReachOfficial\Entity\Process;

use Crsw\CleverReachOfficial\Migration\Migration1568040585CreateProcessesTable;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class ProcessEntityDefinition
 *
 * @package Crsw\CleverReachOfficial\Entity\Process
 */
class ProcessEntityDefinition extends EntityDefinition
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return Migration1568040585CreateProcessesTable::PROCESSES_TABLE;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return ProcessEntity::class;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getCollectionClass(): string
    {
        return ProcessEntityCollection::class;
    }

    /**
     * @inheritDoc
     *
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new  PrimaryKey(), new Required()),
            new StringField('guid', 'guid'),
            new LongTextField('runner', 'runner'),
        ]);
    }

    /**
     * Do not add timestamps as default fields
     *
     * @return array
     */
    protected function defaultFields(): array
    {
        return [];
    }
}
