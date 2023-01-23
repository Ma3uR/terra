## 1.8.4

### de

**Fehlerbehebungen:**

* Das Plugin lässt sich jetzt wieder ohne Fehler installieren.

### en

**Bug fixes:**

* The plugin can now be installed again without errors.


## 1.8.3

### de

**Fehlerbehebungen:**

Das Plugin lässt sich nun wieder deinstallieren.

### en

**Bug fixes:**

The plugin can now be uninstalled again.


## 1.8.2

### de

**Fehlerbehebungen:**

* Das Plugin lässt sich nun wieder ohne Fehler aktualisieren.

### en

**Bug fixes:**

* The plugin can now be updated again without errors.


## 1.8.1

### de

**Fehlerbehebungen:**

* Der Link zur Versandverfolgung führt jetzt wieder auf die korrekte Seite bei DHL.

### en

**Bug fixes:**

* The tracking link now routes to the correct dhl page again.


## 1.8.0

### de

**Neue Funktionen und Verbesserungen:**

* Das Plugin unterstützt nun Shopware Version 6.4.0.
* Die Einstellungen für die automatische Gewichtsberechnung können nun pro Versandart festgelegt werden.

**Fehlerbehebungen:**

* Die Größe der angelegten Log-Dateien auf dem Server wurde reduziert.

**Anforderungen:**

* Das Plugin erfordert nun mindestens Shopware Version 6.4.0.

### en

**New features and improvements:**

* The plugin now supports Shopware version 6.4.0.
* The settings for automatic weight calculation can now be defined per shipping method.

**Bug fixes:**

* The size of the created log files on the server has been reduced.

**Requirements:**

* The plugin now requires at least Shopware version 6.4.0.


## 1.7.4

### de

**Fehlerbehebungen:**

* Beim automatischen Abruf der Vertragsdaten aus dem Geschäftskundenportal werden nun auch die Vertragsdaten für ein Warenpost-Produkt übernommen.
* Versandetiketten können nun auch für Bestellungen, die leere Positionen beinhalten, erstellt werden.
* Das Plugin lässt sich nun wieder unter Shopware 6.3.2.* installieren und aktualisieren.

### en

**Bug fixes:**

* When automatically retrieving contract data from the business customer portal, the contract data for a "Warenpost" product will now also be retrieved.
* Shipping labels can now be created for orders that contain custom order line items.
* The plugin can now be installed and updated again under Shopware 6.3.2.*.

## 1.7.3

### de

**Fehlerbehebungen:**

* Die Produkte für Österreich wurden entfernt, da DHL den Versand von Paketen aus Österreich nicht mehr anbietet.
* Manuelle Änderungen an Paketen beim Erstellen von Versandetiketten werden wieder korrekt übernommen.
* Die Auswahl im Feld "Wunschtag" wird wieder korrekt übernommen.

### en

**Bug fixes:**

* The products for Austria have been removed because DHL no longer offers shipping of packages from Austria.
* Manual changes to parcel items are correctly persisted when creating shipping labels.
* The selection in the "Preferred Day" input field is applied correctly again.


## 1.7.2

### de

**Fehlerbehebungen:**

* Für die Erzeugung eines Labels wird nun wieder das ausgewählte Produkt verwendet.
* Die Gewichte für die Positionen eines Paketes werden nun nicht mehr fälschlicherweise durchgestrichen.

### en

**Bug fixes:**

* The selected product will now be used again to create the label.
* The weights for the positions of a parcel are now no longer striked through incorrectly.


## 1.7.1

### de

**Fehlerbehebungen:**

* Das Plugin lässt sich nun wieder installieren, wenn das Plugin _Pickware ERP Starter_ bereits installiert ist.

### en

**Bug fixes:**

* The plugin can now be installed again if the plugin _Pickware ERP Starter_ is already installed.


## 1.7.0

### de

**Neue Funktionen und Verbesserungen:**

* Für internationale Sendungen lässt sich nun eine "Vorausverfügung" angeben, mit der konfiguriert werden kann, was mit
 der Sendung in dem Fall passiert, dass sie nicht zugestellt werden kann.

### en

**New features and improvements:**

* For international shipments, an "endorsement" can now be specified to configure what happens to the shipment in the
event that it cannot be delivered.


## 1.6.3

### de

**Fehlerbehebungen:**

* Es ist jetzt möglich, Versandetiketten bei Verwendung unterschiedlicher Zeitzonen zu erstellen.

### en

**Bug fixes:**

* It is now possible to create shipping labels when using different timezones.


## 1.6.2

### de

**Fehlerbehebungen:**

* Es ist jetzt möglich Versandetiketten für Bestellungen zu erzeugen, die "Custom Products" enthalten.

### en

**Bug fixes:**

* It is now possible to create shipping labels for orders that contain "Custom Products".

## 1.6.1

### de

**Fehlerbehebungen:**

* Die Liste der Versandetiketten wird korrekt in der Bestelldetailseite angezeigt, auch wenn für ein Versandetikett kein Dokument erzeugt werden konnte.

### en

**Bug fixes:**

* The list of shipping labels in the order detail page is correctly displayed even if there are shipping labels without documents.


## 1.6.0

### de

**Neue Funktionen und Verbesserungen:**

* **Beilegeretouren**: Es lässt sich jetzt zusammen mit einem Versandetiketten ein Retourenetikett erstellen, welches dem Paket beigelegt werden kann.
* Für Exportdokumente kann nun "Handelswaren" als Sendungstyp eingestellt werden.
* In der Storefront lässt sich nun eine Packstations-Adresse (mit Packstationsnummer und Postnummer) eingeben.
* In der Storefront lässt sich nun eine Postfilialen-Adresse (mit Filialnummer und Postnummer) eingeben.
* Es lässt sich nun eine Zollkennnummer in den Einstellungen hinterlegen, die in das entsprechende Feld auf einem CN23-Exportdokument abgedruckt wird.
  * Eine solche Zollkennnummer kann auch für den Empfänger angegeben werden.

**Fehlerbehebungen:**

* Die Zugangsdaten zum Geschäftskundenportal werden nicht mehr als fehlerhaft erkannt, wenn diese zu einem Systembenutzer gehören.

### en

**New features and improvements:**

* **Enclosed return labels**: It is now possible to create a return label together with a shipping label, which can be enclosed to the package.
* For export documents, "Sale of goods" can now be set as the shipment type.
* Supports easier submission of DHL Packstation information (Packstation Number and PostNumber) in the Storefront.
* Supports easier submission of Deutsche Post post office information (Post office number and PostNumber) in the Storefront.
* It is now possible to define a customs reference number in the settings, which will be printed in the corresponding field on a CN23 export document.
  * Such a customs reference number can also be specified for the recipient.

**Bug fixes:**

* The credentials for the business customer portal are no longer recognized as incorrect if they belong to a system user.


## 1.5.0

### de

**Neue Funktionen und Verbesserungen:**

* Das Plugin unterstützt nun Shopware Version 6.3.2.0.

### en

**New features and improvements:**

* The plugin now supports shopware version 6.3.2.0.


## 1.4.0

### de

**Neue Funktionen und Verbesserungen:**

* Das Plugin unterstützt nun Shopware Version 6.3.1.

**Fehlerbehebungen:**

* Beim Erstellen eines Versandetikettes kann das Land für Absender- und Empfängeradresse nun wieder geändert werden.
* Die Bestellung kann nun wieder gespeichert werden, nachdem ein Versandetikett erzeugt wurde.

**Anforderungen:**

* Das Plugin benötigt nun mindestens Shopware Version 6.3.1.0.

### en

**New features and improvements:**

* The plugin now supports shopware version 6.3.1.

**Bug fixes:**

* When creating a shipping label the country for sender and recipient address can now be changed again.
* The order can now be saved again after a shipping label has been created.

**Requirements:**

* The plugin now requires at least Shopware version 6.3.1.0.


## 1.3.0

### de

**Neue Funktionen und Verbesserungen:**

* Das Plugin kann jetzt optional unter Beibehaltung aller Daten deinstalliert werden.
* Das Plugin unterstützt nun Shopware Version 6.3.0 (ab Version 6.3.0.2).

**Fehlerbehebungen:**

* Der Adresszusatz wird nun auf das Versandetikett gedruckt.
* Die Versanddienstleisterkonfiguration zu einer neu angelegten Versandart wird jetzt gespeichert.
* Der Wert für das Feld "Telefonnummer" im Versandetiketten-Dialog wird nun wieder angezeigt.

**Anforderungen:**

* Das Plugin benötigt nun mindestens Shopware Version 6.3.0.2.

### en

**New features and improvements:**

* The plugin can now optionally be uninstalled while retaining all data.
* The plugin now supports shopware version 6.3.0 (from version 6.3.0.2).

**Bug fixes:**

* The additional address line is now printed on the shipping label.
* The shipping provider config for a newly created shipping method is now saved.
* The value for the "phone number" field in the shipping label dialog is now displayed again.

**Requirements:**

* The plugin now requires at least Shopware version 6.3.0.2.


## 1.2.0

### de

**Neue Funktionen und Verbesserungen:**

* Das Plugin unterstützt nun Shopware Version 6.2.3.

### en

**New features and improvements:**

* The plugin now supports Shopware version 6.2.3.


## 1.1.0

### de

**Neue Funktionen und Verbesserungen:**

* Es lassen sich nun Versandetiketten für das Produkt **DHL Warenpost** erstellen.

**Fehlerbehebungen:**

* Die Aktualisierung des Plugins ist nun wieder möglich, auch wenn das Plugin deaktiviert ist.

### Sonstiges:

* Die Produkte **DHL Paket Taggleich** und **DHL Paket Wunschzeit** wurden entfernt, das sie nicht mehr von DHL angeboten werden.

### en

**New features and improvements:**

* It is now possible to create shipping labels for the product **DHL Warenpost**.

**Bug fixes:**

* It is now possible to update the plugin again, even if the plugin is deactivated.

**Other:**

* The products **DHL Paket Taggleich** and **DHL Paket Wunschzeit** have been removed as they are no longer offered by DHL.


## 1.0.0

### de

**Neue Funktionen und Verbesserungen:**

* __Highlight:__ Es ist nun möglich für internationale Sendung automatisch Exportdokumente zu erstellen.
  * Die für den Export benötigten Zollinformationen lassen sich in den Produkten sowie unter _Einstellungen -> Plugins -> Versandetiketten allgemein_ hinterlegen.
* Falls eine Adresse unvollständig ist, wird im Dialog "Versandetikett erstellen" nun eine entsprechende Fehlermeldung angezeigt.
* Falls das Geburtsdatum für den Ident-Check nicht richtig eingegeben wurde, wird im Dialog "Versandetikett erstellen" nun eine entsprechende Fehlermeldung angezeigt.
* Das Plugin unterstützt nun Shopware Version 6.2.0.

**Fehlerbehebungen:**

* Vererbte Produktattribute, wie Gewicht und Beschreibung, werden bei der Versandetikettenerstellung jetzt korrekt aus Varianten übernommen.
* Mit den für den Test-Webservice hinterlegten Teilnahmenummern können ab sofort alle zur Verfügung stehenden Service-Optionen verwendet werden.
* Wenn ein in einer vorliegenden Kundenbestellung enthaltenes Produkt zwischenzeitlich gelöscht wurde, wird dieses bei der Versandetikettenerstellung weiterhin korrekt angezeigt und berücksichtigt.
* Das Fenster zur Versandetikettenerstellung öffnet sich nicht mehr ungewollt, nachdem ein Bestelldokument erstellt wurde.

**Anforderungen:**

* Im Anschluss an das Update, muss unter _Einstellungen -> System -> Caches & Indizes -> Löschen und Aufwärmen_ manuell der Cache geleert werden.
* Das Plugin benötigt nun mindestens Shopware Version 6.2.0.

### en

**New features and enhancements:**

* __Highlight:__ It is now possible to automatically create export documents for international shipments.
  * The required customs information can be stored in the products and under _Settings -> Plugins -> Shipping labels common_.
* If an address is incomplete, a corresponding error message is now displayed in the "Create shipping label" dialog.
* If the date of birth for the Ident-Check was not entered correctly, a corresponding error message is now displayed in the "Create shipping label" dialog.
* The plugin now supports Shopware version 6.2.0.

**Bug fixes:**

* Inherited product attributes, such as weight and description, are now considered correctly for variants when shipping labels are created.
* All available services can now be used with the participation numbers stored for the test web service.
* Even if a product contained in an existing customer order is deleted in the meantime, it is still displayed and taken into account correctly when creating shipping labels.
* The shipping label creation window no longer opens unintentionally after an order document has been created.

### Requirements:

* After the update, the cache has to be cleared manually in _Settings -> System -> Caches & Indexes -> Clear and warm up caches_.
* The plugin now requires at least Shopware version 6.2.0.


## 0.3.3

### de

* Behebt einen Fehler, der dazu führte, dass sich das Plugin auf System, die keine Datenbank-Trigger unterstützen, nicht installieren ließ.

### en

* Fixes a bug that caused the plugin to not install on systems that do not support database triggers.


## 0.3.2

### de

* Behebt eine Inkompatibilität zu MariaDB, welche dazu führte, dass sich das Plugin nicht installieren ließ.

### en

* Fixes an incompatibility to MariaDB which caused the plugin not to install.


## 0.3.1

### de

* Behebt einen Fehler, der nach einem Update auf Version 0.3.0 dazu führen konnte, dass keine Versandetiketten mehr erstellt werden konnten.

### en

* Fixes a bug that could cause shipping labels not to be created after an update to version 0.3.0.


## 0.3.0

### de

* Diese Version ist kompatibel mit Shopware 6.1.

### en

* This version is compatible with Shopware 6.1.


## 0.2.1

### de

* Behebt eine Inkompatibilität zu MariaDB, welche dazu führte, dass sich das Plugin nicht installieren ließ.
* Behebt einen Fehler, der verhinderte, dass sich Versandetiketten erzeugen ließen, wenn für einen Artikel Länge, Breite und Höhe hinterlegt waren.

### en

* Fixes an incompatibility to MariaDB which caused the plugin not to install.
* Fixes a bug that prevented shipping labels from being created when length, width and height were defined for a product.


## 0.2.0

### de

* Diese Version ist kompatibel mit Shopware 6.0.0 EA2.

### en

* This version is compatible with Shopware 6.0.0 EA2.


## 0.1.0

### en

**Initial release including these features:**
* Support for creating DHL shipping labels for all DHL products,
* Assignment of shipping costs to shipping products,
* Automatic fetching of contract data from the DHL BCP,
* Automatic splitting of shipments into multiple packages,
* Automatic calculation of package weight,
* Support for the services cash on delivery, bulky goods, IdentCheck, visual check of age, Courier, personal handover and additional insurance,
* Check for address codability, and
* Support for GDPR-conformant processing and transfer of customer data.

### de

**Initiales Release mit den folgenden Features:**
* Erzeugen von Versandetiketten für alle DHL-Produkte,
* Zuordnung von Versandkosten zu Versandprodukten,
* Automatischer Abruf der Vertragsdaten aus dem DHL-Geschäftskundenportal,
* Automatische Aufteilung in mehrere Pakete,
* Automatische Versandgewichtsberechnung,
* Unterstützung der Services Nachnahme, Sperrgut, IdentCheck, Alterssichtprüfung, Kurier, persönliche Übergabe und zusätzliche Versicherung,
* Prüfung der Leitcodierbarkeit,
* Unterstützung für die DSGVO-konforme Verarbeitung und Übertragung von Kundendaten.
