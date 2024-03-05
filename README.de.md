[![English](data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=) English](https://github.com/printpagestopdf/api-for-cb-app)

# API Erweiterung zur Unterstützung von CB App (App für CommonsBooking)

[CB App](https://printpagestopdf.github.io/cb_app/) ist eine in Flutter geschriebene Multiplattform-App. Sie dient als alternatives Frontend für eine [CommonsBooking](https://commonsbooking.org/) website.Website. Wenn Sie mehr über die App erfahren möchten, besuchen Sie die [Website](https://printpagestopdf.github.io/cb_app/) oder die [GitHub page](https://github.com/printpagestopdf/cb_app).

[CB App](https://printpagestopdf.github.io/cb_app/) ist eine eigenständige Entwicklung unabhängig von [CommonsBooking](https://commonsbooking.org/),benötigt aber eine [CommonsBooking](https://wordpress.org/plugins/commonsbooking/) Installation als Voraussetzung.

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
