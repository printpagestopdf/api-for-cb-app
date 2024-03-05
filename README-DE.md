# API Erweiterung zur Unterstützung von CB App (App für CommonsBooking)

[CB App](https://printpagestopdf.github.io/cb_app/en/) ist eine in Flutter geschriebene Multiplattform-App. Sie dient als alternatives Frontend für eine [CommonsBooking](https://commonsbooking.org/) website.Website. Wenn Sie mehr über die App erfahren möchten, besuchen Sie die [Website](https://printpagestopdf.github.io/cb_app/en/) oder die [GitHub page](https://github.com/printpagestopdf/cb_app).

[CB App](https://printpagestopdf.github.io/cb_app/en/) ist eine eigenständige Entwicklung unabhängig von [CommonsBooking](https://commonsbooking.org/),benötigt aber eine [CommonsBooking](https://wordpress.org/plugins/commonsbooking/) Installation als Voraussetzung.

Dieses Plugin erweitert die Funktionalität der CommonsBooking API.  Wenn es auf der Website installiert ist, ist es möglich, sich über die CB App einzuloggen und Buchungen vorzunehmen.

Ohne dieses Plugin kann die CB App nur lesend auf die CommonsBooking Seite zugreifen (und das auch nur, wenn die Seite die CommonsAPI aktiviert hat).

Über das Erweiterungsplugin ist außerdem folgendes möglich:

- Auswahl zwischen verschiedenen konfigurierten Karten
- App Login auf bestimmte Rollen beschränken
- Bilder für die webbasierte App-Version bereitstellen (wenn CORS auf der Website aktiv ist)
- Buchungen für Benutzer einschränken (für CommonsBooking-Versionen, die dies nicht in Core anbieten)

#### **Installation**

- Stellen Sie sicher, dass das WP-Plugin CommonsBooking installiert ist
- Installieren Sie das Plugin entweder aus dem [Wordpress-Plugin-Verzeichnis](https://wordpress.org/plugins/api-for-cb-app/) oder die neueste Version von hier.
- Aktivieren Sie das Plugin
- Konfigurieren Sie ggf. Einstellungen (zu finden unter CommonsBooking Menüpunkt "App API Einstellungen")
