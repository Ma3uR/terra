<?php declare(strict_types=1);

namespace Biloba\IntlTranslation\Core\Content\BilobaIntlTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ConfigEntity $entity)
 * @method void              set(string $key, ConfigEntity $entity)
 * @method ConfigEntity[]    getIterator()
 * @method ConfigEntity[]    getElements()
 * @method ConfigEntity|null get(string $key)
 * @method ConfigEntity|null first()
 * @method ConfigEntity|null last()
 */
class LogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return LogEntity::class;
    }
}