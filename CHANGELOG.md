## NEXT PATCH RELEASE

### en

* Fixes a bug that prevented payments with digital wallets (Apple Pay, Google Pay, ...) if the country of the customer and the country of the configured Stripe account differ.
    * For this purpose, the country of the Stripe account holder is automatically loaded from the account. For this, the plugin configuration must be saved again.
* Fixes a bug that caused unsaved credit cards to continue to be displayed after a failed order completion.

### de

* Behebt einen Fehler, der dazu führte, dass Zahlungen mit Digital Wallets (Apple Pay, Google Pay, ...) nicht funktionierten, wenn sich das Land des Kunden und das des hinterlegten Stripe-Accounts unterscheiden.
    * Das Land des Stripe Accountinhabers wird hierfür automatisch aus dem Account geladen. Hierfür muss die Pluginkonfiguration neu gespeichert werden.
* Behebt einen Fehler, der dazu führte, dass nicht gespeicherte Kreditkarten nach einem fehlgeschlagenen Bestellabschluss weiterhin angezeigt wurden.


## 5.3.4

### en

* Improves the security of the plugin against XSS attacks.

### de

* Verbessert die Sicherheit des Plugins gegenüber XSS-Angriffen.


## 5.3.3

### en

* Improves the handling of declined Klarna payments by redirecting the customer back to the checkout.
* Fixes a bug that prevent payments via Apple Pay or Google Pay if an order item did not have a price.

### de

* Verbessert die Handhabung von abgelehnten Klarna-Zahlungen, sodass der Kunde in diesem Fall zurück zum Checkout gelangt.
* Behebt einen Fehler, der dazu führte, dass Artikel ohne Preis die Zahlung mittels Apple Pay oder Google Pay verhinderten.


## 5.3.2

### en

* Improves the compatibility with _Quotations powered by Pickware_.

### de

* Verbessert die Kompatibilität mit _Angebote powered by Pickware_.


## 5.3.1

### en

* Fixes a bug that prevented Klarna payments from working on Shopware version older than 5.5.0.

### de

* Behebt einen Fehler, der unter Shopware-Versionen älter als 5.5.0 dazu führte, dass Zahlungen mittels Klarna nicht funktionierten.


## 5.3.0

### en

* Adds support for Google Pay.
* Adds support for Klarna.

### de

* Fügt die Unterstützung für Google Pay hinzu.
* Fügt die Unterstützung für Klarna hinzu.


## 5.2.0

### en

* Adds support for "Mail Order / Telephone Order" (MOTO) transactions, which are excluded from SCA (must be enabled in the plugin configuration).

### de

* Fügt die Unterstützung für "Mail Order / Telephone Order" (MOTO) Transaktionen hinzu, welche von SCA ausgenommen sind (muss in der Plugin-Konfiguration aktiviert werden).


## 5.1.1

### en

* Fixes a bug that prevent the order number from being added to the description of credit card transactions.

### de

* Behebt einen Fehler, der dazu führte, dass der Beschreibung von Kreditkarten-Transaktionen nicht die Bestellnummer hinzugefügt wurde.


## 5.1.0

### en

* Enables compatibility with Shopware 5.6.

### de

* Stellt die Kompatibilität mit Shopware 5.6 her.


## 5.0.0

### en

**Note:** In order to update to this version you need to have at least version 3.0.0 of the plugin installed. Reinstallations are possible at any time.

* The plugin now complies with Strong Customer Authentication (SCA) which is part of the forthcoming PSD2, coming into force on 14 September 2019. This currently effects credit card payments only and changes if and when 3D Secure mechanisms are triggered, which is now solely controlled by Stripe's fraud detection and prevention as well as the credit card issuer. [More information about SCA and PSD2](https://stripe.com/gb/guides/strong-customer-authentication).
* Changes the 'save credit card' checkbox in the payment method selection from opt-out to opt-in.
* Fixes a bug that could prevent the credit card management from being visible in the account overview.

### de

**Hinweis:** Um auf diese Version aktualisieren zu können, muss mindestens Version 3.0.0 des Plugins installiert sein. Neuinstallationen sind jederzeit möglich.

* Das Plugin erfüllt nun die Anforderungen für Strong Customer Authentication (SCA) als Teil der bevorstehenden PSD2-Richtlinie, welche am 14. September 2019 in Kraft treten wird. Dies betrifft zur Zeit nur Kreditkartenzahlungen und steuert ob und wann 3D Secure-Mechanismen ausgelöst werden. Letzteres wird ab sofort nur noch von Stripes Betrugserkennung und -verhinderung sowie dem ausgebenden Institut der Kreditkarte gesteuert. [Weitere Informationen zu SCA und PSD2 (Englisch)](https://stripe.com/de/guides/strong-customer-authentication).
* Passt die Checkbox "Kreditkarte speichern" in der Auswahl der Zahlungsarten an, sodass diese nun standardmäßig nicht aktiviert ist.
* Behebt einen Fehler, der unter Umständen dazu führte, dass die Kreditkartenverwaltung nicht in der Kontoübersicht angezeigt wurde.


## 4.0.0

### en

**Note:** Starting with this release the plugin is no longer compatible to Shopware versions older than 5.2.0. Please install version 3.2.0 of this plugin on older Shopware versions.

* Improves the visualization and validation of entered IBANs for SEPA payments.
* Improves the reliability of Apple Pay payments.

### de

**Hinweis:** Beginnend mit diesem Release ist das Plugin nicht mehr zu Shopware-Versionen vor 5.2.0 kompatibel. Bitte installieren Sie unter älteren Shopware-Installationen Version 3.2.0 des Plugins.

* Verbessert die Darstellung und Validierung eingegebener IBANs für SEPA-Zahlungen.
* Verbessert die Zuverlässigkeit von Apple Pay-Zahlungen.


## 3.2.0

### en

* The menu item "Manage credit cards" in the customer account is no longer displayed, if the configuration option _Show "Show credit card"_ is disabled.
* Fixes an error that migth have caused the payment to be aborted, because the used basket amount was invalid.

### de

* Der Menüpunkt "Kreditkarten verwalten" im Kundenkonto wird nun nicht mehr angezeigt, wenn die Konfigurationsoption _"Kreditkarte speichern" anzeigen_ deaktiviert ist.
* Behebt einen Fehler, der unter Umständen dazu führte, dass Zahlungen abgebrochen wurden, weil der verwendete Betrag des Warenkorbs ungültig war.


## 3.1.3

### en

* Improves the Shopware 5.5 compatibility.
* Fixes a bug that might have prevented the input fields for credit cards payments from being displayed correctly.

### de

* Verbessert die Kompatibilität zu Shopware 5.5.
* Behebt einen Fehler, der unter Umständen dazu führen konnte, dass die Eingabefelder für Kreditkartenzahlungen nicht korrekt angezeigt wurden.


## 3.1.2

### en

* Improves the compatibility to custom themes.

### de

* Verbessert die Kompatibilität zu manuell angepassten Themes.


## 3.1.1

### en

* Improves the appearance of input fields for SEPA Direct Debit and credit card payments.
* Improves the compatibility to the plugin "DHL Wunschpaket".

### de

* Verbessert die Darstellung der Eingabefelder für Zahlungen mittels SEPA-Lastschrift und Kreditkarte.
* Verbessert die Kompatibilität zum Plugin "DHL Wunschpaket".


## 3.1.0

### en

* It is now possible to let Stripe automatically send receipt emails to the customer (must be enabled in the plugin config).
* Improves the compatibility of Apple Pay payments when using customized themes in the checkout.
* Fixes a UI glitch in the SEPA payment form.

### de

* Es ist nun möglich, dass Stripe automatisch Zahlungsbelege per E-Mail an den Kunden sendet (muss in der Pluginkonfiguration aktiviert werden).
* Verbessert die Zahlung mittels Apple Pay bei der Verwendung von angepassten Themes im Bestellabschluss.
* Behebt einen Darstellungsfehler im Formular von SEPA-Zahlungen.


## 3.0.3

### en

* Fixes a bug that prevented the checkout via Apple Pay, if the "terms and conditions" checkbox was hidden.
* Improves the validation of Apple Pay payments.

### de

* Behebt einen Fehler beim Bestellabschluss mit Apple Pay, der auftrat, falls die AGB-Checkbox nicht angezeigt wird.
* Verbessert die Validierung von Zahlungen mittels Apple Pay.


## 3.0.2

### en

* Fixes an error in the payment process when using SEPA.
* Fixes an error in the payment process when using Apple Pay.

### de

* Behebt einen Fehler im Bezahlvorgang bei der Verwendung von SEPA Lastschrift.
* Behebt einen Fehler im Bezahlvorgang bei der Verwendung von Apple Pay.


## 3.0.1

### en

* Fixes an error caused by obsolete plugin files.

### de

* Behebt einen Fehler, der durch obsolete Plugindateien verursacht wurde.


## 3.0.0

### en

**Note:** Starting with this release the plugin is no longer compatible to Shopware versions older than 5.0.4. Please install version 2.2.1 of this plugin on older Shopware versions.

* Fixes an error that occurred when trying to install/uninstall the plugin repeatedly.

### de

**Hinweis:** Beginnend mit diesem Release ist das Plugin nicht mehr zu Shopware-Versionen vor 5.0.4 kompatibel. Bitte installieren Sie unter älteren Shopware-Installationen Version 2.2.1 des Plugins.

* Behebt einen Fehler, der bei wiederholter Installation/Deinstallation auftrat.


## 2.2.1

### en

* Fixes a display error in the login when using Shopware 5.3.

### de

* Behebt einen Darstellungsfehler im Login unter Shopware 5.3.


## 2.2.0

### en

* Adds compatiblity with Shopware 5.3

### de

* Fügt die Kompatiblität zu Shopware 5.3 hinzu


## 2.1.4

### en

* Fixes an error that occurred during checkout in Shopware versions < 5.2.0

### de

* Behebt einen Fehler im Checkout unter Shopware Versionen < 5.2.0


## 2.1.3

### en

* Changes the creation of statement descriptors to prevent skipping order numbers
* Fixes an error that occurred when removing a credit card from a customer account

### de

* Passt die Erstellung von Verwendungszwecken an, sodass keine Bestellnummern mehr übersprungen werden
* Behebt einen Fehler beim Entfernen einer Kreditkarte aus dem Kundenkonto


## 2.1.2

### en

* Fixes an error that occurred during the plugin update

### de

* Behebt einen Fehler, der während des Plugin-Updates auftrat


## 2.1.1

### en

* Improves the internal handling of text snippets to make explicit escaping of single quotes in snippets obsolete

### de

* Verbessert die interne Handhabung von Textbausteinen, sodass einfachen Anführungszeichen in Textbausteinen kein Backslash mehr vorangestellt werden muss


## 2.1.0

### en

* Makes the title snippets of the CVC info popup localizable
* Adds respective logos for all payment methods provided by this plugin
* Adds a new config element for showing/hiding the payment provider logos in the payment form

### de

* Überarbeitet das CVC-Popup, sodass der Titel nun ebenfalls als Textbaustein hinterlegt werden kann
* Fügt die Logos aller Zahlungsarten hinzu, die von diesem Plugin bereitgestellt werden
* Fügt der Plugin-Konfiguration ein neues Feld "Logos der Zahlungsarten anzeigen" hinzu, mithilfe dessen die Logos der Zahlungsarten im Bezahlungs-Formular angezeigt oder ausgeblendet werden können


## 2.0.6

### en

* Adds a new config field for specifying a custom statement descriptor that is used e.g. for SOFORT payments
* Improves the internal handling of text snippets to fix some errors causing the credit card payment form to not load

### de

* Fügt der Plugin-Konfiguration ein neues Feld "Verwendungszweck" hinzu, mithilfe dessen der Verwendungszweck von z.B. SOFORT Zahlungen gesetzt werden kann
* Verbessert die interne Handhabung von Textbausteinen um einige Fehler zu beheben, welche dazu führten, dass das Formular für Kreditkartenzahlungen nicht geladen wurde


## 2.0.5

### en

* Fixes an error in the snippets, which could cause an error when loading the credit card payment form

### de

* Behebt einen Fehler in den Textbausteinen, der dazu führen konnte, dass die Form für Kreditkartenzahlungen nicht geladen wurde


## 2.0.4

### en

* Further improves the construction of statement descriptors

### de

* Verbessert die Erzeugung des Verwendungszwecks


## 2.0.3

### en

* Fixes an error in some payment methods that was caused by invalid characters in the statement descriptor

### de

* Behebt einen Fehler in manchen Zahlungsarten, der durch ungültige Zeichen im Verwendungszweck hervorgerufen wurde


## 2.0.2

### en

* Fixes an error that caused more than one order to use the same order number
* Adds aadditional safeguards to prevent duplication of order numbers

### de

* Behebt einen Fehler der dazu führte, dass Bestellnummern teilweise mehrfach verwendet wurden
* Fügt zusätzlich Schutzmaßnahmen ein, die eine Mehrfachverwendung von Bestellnummern verhindern


## 2.0.1

### en

* Fixes an error in the creation of "SOFORT Überweisung" payments

### de

* Behebt einen Fehler beim Erstellen von Zahlungen mittels "SOFORT Überweisung"


## 2.0.0

### en

**Note:** Please refer to the [plugin documentation](https://docs.google.com/document/d/1FfZU0AqEWtiXd7Ito6e7UiLzfpP5F_D8CT9gtogaZlk) before activating any of the new payment methods.

* Adds a new, disabled payment method "Stripe Kreditkarte (mit 3D-Secure)"
* Adds a new, disabled payment method "Stripe SEPA-Lastschrift"
* Adds a new, disabled payment method "Stripe SOFORT Überweisung"
* Adds a new, disabled payment method "Stripe Giropay"
* Adds a new, disabled payment method "Stripe Apple Pay"
* Adds a new, disabled payment method "Stripe Bancontact"
* Adds a new, disabled payment method "Stripe iDEAL"

### de

**Hinweis:** Bitte lesen Sie zunächste die [Plugin-Dokumentation](https://docs.google.com/document/d/1FfZU0AqEWtiXd7Ito6e7UiLzfpP5F_D8CT9gtogaZlk), bevor Sie eine der neuen Zahlungsarten aktivieren.

* Fügt eine neue, deaktivierte Zahlungsart "Stripe Kreditkarte (mit 3D-Secure)" hinzu
* Fügt eine neue, deaktivierte Zahlungsart "Stripe SEPA-Lastschrift" hinzu
* Fügt eine neue, deaktivierte Zahlungsart "Stripe SOFORT Überweisung" hinzu
* Fügt eine neue, deaktivierte Zahlungsart "Stripe Giropay" hinzu
* Fügt eine neue, deaktivierte Zahlungsart "Stripe Apple Pay" hinzu
* Fügt eine neue, deaktivierte Zahlungsart "Stripe Bancontact" hinzu
* Fügt eine neue, deaktivierte Zahlungsart "Stripe iDEAL" hinzu


## 1.1.1

### en

* From now on the theme files of this plugin are included when compiling them using the console command

### de

* Ab sofort werden die Theme-Dateien des Plugins auch beim Kompilieren über die Konsole berücksichtigt


## 1.1.0

### en

* From now on it is possible to configure different stripe accounts for different subshops
* It is now possible to hide the 'save credit card' checkbox
* The cleared date is now set correctly upon checkout
* Error messages shown during the payment process are now available as text snippets
* Improves the layout of the credit card management in the account settings on small displays

### de

* Ab sofort ist es möglich, verschiedene stripe-Accounts für verschiedene Subshops zu konfigurieren
* Es ist nun möglich, die Checkbox zum Speichern von Kreditkarten auszublenden
* Bei Bestellabschluss wird nun das Zahlungsdatum korrekt gesetzt
* Fehlermeldungen beim Bezahlvorgang sind ab sofort als Text-Schnipsel hinterlegt
* Verbessert die Darstellung der Kreditkartenverwaltung in den Benutzereinstellungen auf kleinen Displays


## 1.0.9

### en

* Fixes a UI bug in the credit card management in the account settings

### de

* Behebt eine Darstellungsfehler in der Kreditkartenverwaltung in den Benutzereinstellungen


## 1.0.8

### en

* Improves the compatibility with Shopware 5.2

### de

* Verbessert die Kompatibilität mit Shopware 5.2


## 1.0.7

### en

* Fixes a crash when saving a credit card

### de

* Behebt einen Fehler beim Speichern von Kreditkarten


## 1.0.6

### en

* Improves the PHP 7 compatibility in Shopware 5 (<= 5.0.3)

### de

* Verbessert die PHP 7 Kompatibilität unter Shopware 5 (<= 5.0.3)


## 1.0.5

### en

* You can now localize the "new card" selection text of the payment form

### de

* Der "Neue Karte" Auswahltext der Zahlungsform kann nun lokalisiert werden


## 1.0.4

### en

* Fixes a UI bug when using the *PayPal Plus* plugin

### de

* Behebt einen Darstellungsfehler bei der Verwendung des *PayPal Plus* Plugins


## 1.0.3

### en

* Fixes a broken text snippet, which triggered an error while loading the payment form

### de

* Repariert einen kaputten Text-Schnipsel, der einen Fehler beim Laden der Zahlungsform verursachte


## 1.0.2

### en

* Fixes a bug that caused some Shopware 5 shops to display a warning instead of the order summary after completing the checkout

### de

* Behebt einen Fehler, der in manchen Shopware 5 Installationen dazu führte, dass nach Abschluss der Bestellung statt der Zusammenfassung eine Warnmeldung angezeigt wurde


## 1.0.1

### en

* Fixes a bug in the checkout in case the stripe payment method was disabled
* Improves the PHP 7 compatibility

### de

* Behebt einen Fehler im Bestellabschluss in dem Fall, dass die stripe Zahlungsart deaktiviert war
* Verbessert die PHP 7 Kompatibilität


## 1.0.0

### en

This is the initial release of the official stripe plugin.

### de

Dies ist das erste Release des offiziellen stripe Plugins.
