## 3.2.1

* Adjust composer version constraints to improve compatibility with Shopware 6.4.0.0 and above.


## 3.2.0

* Removed deprecations for version 3.0.0.
* Add `Pickware\DalBundle\RetryableTransaction` to use retryable transactions.


## 3.1.0

* Add new classes `Pickware\DalBundleCriteriaJsonSerializer` and `Pickware\DalBundle\ExceptionHandling\InvalidOffsetQueryException`.


## 3.0.0

* Removed module `ShopwarePlugins\DalBundle\Caching` to be compatible to Shopware 6.4.0.0 and above.
* Removed `QueryBuilderFactory`. `Shopware\Core\Framework\DataAbstractionLayer\Dbal\CriteriaQueryBuilder` is used instead.
* Update dependencies to be compatible to Shopware 6.4.0.0.
* Renamed `ShopwarePlugins\DalBundle` to `Pickware\DalBundle`.


## 2.7.0

* Add new class `\Pickware\ShopwarePlugins\DalBundle\EntityCollectionExtension`.


## 2.6.0

* Add mew module `ShopwarePlugins\DalBundle\Caching` with classes `NonCachingAssociationValidator`, `NonCachingEntityReaderDecorator` and `NonCachingEntitySearcherDecorator`.


## 2.5.0

* Add new class `Pickware\ShopwarePlugins\DalBundle\ContextFactory`.


## 2.4.0

* Add new module `ShopwarePlugins\DalBundle\ExceptionHandling` with classes `UniqueIndexExceptionHandler`, `UniqueIndexExceptionMapping` and `UniqueIndexHttpException`.


## 2.3.0

* Add support for Composer 2.


## 2.2.0

* Add new class `ShopwarePlugins\DalBundle\Sql\SqlUuid`


## 2.1.1

* Add support for Shopware 6.3.1.


## 2.1.0

* Add new method `Pickware\ShopwarePlugins\DalBundle\EntityManager::transactional()`
* Add new method `Pickware\ShopwarePlugins\DalBundle\EntityManager::lockPessimistically()`
* Add new method `Pickware\ShopwarePlugins\DalBundle\EntityManager::createCriteriaFromArray()`
* Drop support for Shopware 6.2.
* Add support for Shopware 6.3.


## 2.0.1

* Fix `JsonSerializableObjectFieldSerializer` not working with API.


## 2.0.0

* Technical: Change signature of Pickware\ShopwarePlugins\DalBundle\DalBundle::registerMigrations in a backwards-incompatible manner.
* Drop support for Shopware 6.1
* Add support for Shopware 6.2


## 1.1.0

* Method `Pickware\ShopwarePlugins\DalBundle\EntityManager::getEntityDefinition` is now public.
