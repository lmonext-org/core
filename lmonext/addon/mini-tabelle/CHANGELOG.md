# Changelog: mini-Addon (LMOnext)

Dieses Addon war bis LMOnext 1.9.0-beta Teil des Core-Pakets. Mit der
Einführung des Addon-Manager-Frameworks (Beitrag Torsten Hofmann) wurde es
als eigenständiges, self-contained Paket extrahiert.

Die vollständige Entwicklungshistorie bis zur Extraktion steht im
CHANGELOG.md des LMOnext-Kernprojekts unter den Abschnitten
`addon/mini/*`.

## Aktuelle Version: 1.2.4

- Als eigenständiges addon.json-Paket verpackt (Templates/Sprachdateien
  jetzt lokal im Addon statt zentral im Core), installierbar über
  Administrator → Addons.

## min_core_version-Korrektur

- min_core_version von "1.4.0" auf "1.9.0" korrigiert (Copy-Paste-Rest aus
  einer älteren internen Versionierung, faktisch wirkungslos, da niedriger
  als jede real existierende LMOnext-Version - AddonManager konnte damit nie
  eine zu alte Core-Version blockieren). "1.9.0" ist die tatsächliche
  LMOnext-Core-Version, ab der der Addon-Manager überhaupt existiert.

## Version 1.3.2 (KRITISCHER Bugfix - Sprachdatei wurde nie geladen)

- KRITISCHER Bugfix (gemeldet: Sprachschlüssel "liga_stand_datum" erschien roh statt übersetzt): sowohl lmo-minitab.php (1.3.1) als auch lmo-mininext.php (1.1.1) riefen `addonManager()->loadLanguages('mini')` mit dem veralteten Addon-Namen auf, statt dem tatsächlichen Manifest-Namen "mini-tabelle" (siehe addon.json). Da loadLanguages() bei einem unbekannten Addon-Namen sofort und ohne Fehlermeldung zurückkehrt, wurde die eigene Sprachdatei dieses Addons dadurch NIE geladen - unabhängig davon, welche Sprache eingestellt war. Blieb lange unbemerkt, solange alle vom Addon genutzten Sprachschlüssel noch im Core lagen; erst sichtbar geworden, nachdem liga_stand_datum korrekt in die eigene Sprachdatei dieses Addons verschoben wurde. Betrifft ausschließlich dieses Addon - alle anderen Standalone-Addons (viewer, ewige, tabellenrechner, relegation, url-import) wurden zur Sicherheit ebenfalls geprüft und riefen loadLanguages() bereits korrekt mit ihrem tatsächlichen Manifest-Namen auf.

## Version 1.3.1

- Sprachschlüssel liga_stand_datum ergänzt (addon/mini/lang/de.php + en.php) - bei systematischer Prüfung aller Addon-Sprachdateien festgestellt: dieser Schlüssel wird nur von diesem Addon genutzt, stand aber noch in der Core-Sprachdatei (jetzt dort entfernt, siehe lang/frontend/de.php 1.50.0). Funktional keine Änderung (der Schlüssel war ja bereits vorhanden und lieferte einen korrekten Text) - nur die korrekte Zuordnung.

## Version 1.3.0 (Sicherheitsüberarbeitung)

- lmo-minitab.php 1.3.0, lmo-mininext.php 1.1.0: Aufruf-Erkennung von der
  fehleranfälligen SCRIPT_NAME-Prüfung auf die neue Konstante
  LMO_ADDON_STANDALONE_CALL umgestellt, die der neue zentrale Controller
  /addon-run.php setzt. Grund: der direkte URL-Aufruf einzelner Dateien
  unter addon/{name}/ ist jetzt per addon/.htaccess komplett gesperrt
  (Sicherheitsmaßnahme im LMOnext-Core, siehe dortiges CHANGELOG.md,
  addon-run.php 1.0.0) - alle Einbettungen (iframe/URL) müssen ab sofort
  über /addon-run.php?addon=mini&file=lmo-minitab.php&... (bzw.
  lmo-mininext.php) laufen, NICHT mehr über den alten direkten Pfad
  /addon/mini/lmo-minitab.php.
- Neues Manifest-Feld "standalone_entrypoints": ["lmo-minitab.php",
  "lmo-mininext.php"] - deklariert explizit, welche Dateien über den
  zentralen Controller aufrufbar sein dürfen.
- Asset-/Logo-Pfadauflösung (miniProjectRootUrlPrefix()) robuster gemacht:
  nutzt jetzt bevorzugt die vom Controller gelieferte LMO_ADDON_WEB_BASE
  statt der bisherigen SCRIPT_FILENAME/SCRIPT_NAME-Ratelogik.

**WICHTIG für bestehende Einbettungen:** Falls dieses Addon bereits per
iframe/URL auf einer externen Website eingebunden wurde, muss die
verwendete URL angepasst werden - siehe oben.
