## 2.0.4

* Adjust composer version constraints to improve compatibility with Shopware 6.4.0.0 and above.


## 2.0.3

* Removed deprecations for version 2.0.0.


## 2.0.2

* Remove unnecessary dependency.


## 2.0.1

* Fix a problem that lead to duplicate migration execution.


## 2.0.0

* Update dependencies to be compatible to Shopware 6.4.0.0.
* Rename `Pickware\ShopwarePlugins\DocumentBundle` to `Pickware\DocumentBundle`


## 1.5.0

* Add new class `Pickware\ShopwarePlugins\DocumentBundle\Installation\DocumentUninstaller`
* Fix migration execution order.


## 1.4.0

* The path of the documents in the private file system is now stored explicitly in the entity.
* The file size of a document is now saved in the entity.
* A file name can now be saved for a document.
* The performance of downloading a document has been improved by streaming it directly from the file system.
* The method `DocumentContentsService::persistDocumentContents` has been deprecated. Use the method `DocumentContentsService::saveStringAsDocument` instead.
* The method `DocumentContentsService::readDocumentContents` has been deprecated. Use the private file system of the document bundle directly instead.
* When a document entity gets removed the corresponding file is now removed from the file system.


## 1.3.1

* Increase minimum required version of dependency `pickware/shopware-plugins-document-bundle`.


## 1.3.0

* Add support for Composer 2.
* Add method `DocumentContentsService::saveStringAsDocument`.


## 1.2.1

* Add support for Shopware 6.3.1.


## 1.2.0

* Drop support for Shopware 6.2.
* Add support for Shopware 6.3.


## 1.1.0

* Added support for Shopware 6.2.3.


## 1.0.0

* Initial release
