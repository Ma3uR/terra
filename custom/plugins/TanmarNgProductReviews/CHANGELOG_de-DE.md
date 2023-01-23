# 1.0.0
- erste version für shopware 6

# 1.0.1
- Es wurde ein Fehler behoben, durch den die Bewertung ohne Sterne einen Fehler auslöste

# 1.0.2
- Es wurde ein Fehler in der Konfiguration behoben

# 1.0.3
- Es wurde ein Fehler behoben wo Altkunden keine Emails bekommen

# 1.0.4
- Es wurde ein weiterer Fehler behoben wo Altkunden keine Emails bekommen

# 1.0.5
- Verbesserte Fehlerbehandlung

# 1.0.6
- Konfiguration "Überschrift ist ein Pflichtfeld" hinzugefügt
- Bugfix Bestellnummer mit Präfix oder Suffix

# 1.0.7
- Bugfix E-Mail Vorlagen

# 1.0.8
- Bugfix Controller Template Menüleiste wurde nicht ausgespielt

# 1.0.9
- Es wurde ein Fehler behoben, durch den bereits bewertete Produkte nicht sichtbar waren

# 1.0.10
- Shopware 6.4 Kompatibilität

# 1.1.0
- Button zum testen von Mails in der Konfiguration hinzugefügt
- Option um Blindkopien von Bewertungseinladungen zu bekommen hinzugefügt
- Option um Bestellungen vor einem bestimmten Datum zu ignorieren hinzugefügt
- Mail Benachrichtigung wenn ein Kunde seine ganze Bestellung bewertet hat

# 1.1.1
- Bereits bewertete Produkte werden auf der Plugin Bewertungsseite zuletzt angezeigt

# 1.2.0
- Option Gutschein Versand ergänzt
- Button zum testen von Gutschein-Versand-Mails in der Konfiguration hinzugefügt
- Achtung: Wenn {{salesChannel.name}} in Ihren Mail Vorlagen nicht mehr wirkt, dann ändern in: {{salesChannel.translated.name}}
- Achtung: Wenn die Gutschein Mails nicht verschickt werden, überprüfen Sie bitte die Mail Templates und löschen Sie folgende Variable, falls vorhanden: {{ order.orderCustomer.salutation.letterName }}