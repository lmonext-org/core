# Changelog: viewer-Addon (LMOnext)

Dieses Addon war bis LMOnext 1.9.0-beta Teil des Core-Pakets. Mit der
Einführung des Addon-Manager-Frameworks (Beitrag Torsten Hofmann) wurde es
als eigenständiges, self-contained Paket extrahiert.

Die vollständige Entwicklungshistorie bis zur Extraktion steht im
CHANGELOG.md des LMOnext-Kernprojekts unter den Abschnitten
`addon/viewer/*`.

## Aktuelle Version: 3.0.1

- Als eigenständiges addon.json-Paket verpackt (Templates/Sprachdateien
  jetzt lokal im Addon statt zentral im Core), installierbar über
  Administrator → Addons.

## addon/viewer/lmo-viewer.php

- Changelog: 3.1.0 - Alle hartcodierten deutschen Texte durch tf()-Aufrufe ersetzt (Seitentitel, Hinweis "keine Spiele", Fehlermeldung fehlende Liga-ID, Wochentagskürzel - letztere über die bereits vorhandenen Core-Schlüssel liga_weekday_*). Wichtiger Bugfix dabei: der loadLanguages()-Aufruf nutzte fälschlich den Ordnernamen "viewer" statt des in addon.json deklarierten Manifest-Namens "spieltag-viewer" - dadurch griffen die Sprachdateien bislang nie, unabhängig davon, ob sie vorhanden waren.

## Sprachdateien (lang/de.php, lang/en.php)

- Neu hinzugefügt: enthalten jetzt die 4 vw_*-Schlüssel für Titel, Hinweistexte
  und Fehlermeldungen des Viewer-Addons.

## min_core_version-Korrektur

- min_core_version von "1.4.0" auf "1.9.0" korrigiert (Copy-Paste-Rest aus
  einer älteren internen Versionierung, faktisch wirkungslos, da niedriger
  als jede real existierende LMOnext-Version - AddonManager konnte damit nie
  eine zu alte Core-Version blockieren). "1.9.0" ist die tatsächliche
  LMOnext-Core-Version, ab der der Addon-Manager überhaupt existiert.

## Version 3.2.2 (Dokumentations-Bugfix)

- Bugfix (siehe ewige-tabelle CHANGELOG.md 1.3.2 fuer den vollstaendigen
  Hintergrund zur systematischen Pruefung): gleich drei Stellen im
  Datei-Kopf-Kommentar ("Per URL:" x2, "Per IFrame:" x1) zeigten einen
  direkten, durch addon/.htaccess gesperrten Pfad (.../lmo-viewer.php?...).
  Korrigiert auf addon-run.php?addon=spieltag-viewer&file=lmo-viewer.php&...
  - die Standalone-Erkennung selbst war bereits korrekt.

## Version 3.2.1

- 7 Wochentag-Sprachschlüssel ergänzt (liga_weekday_mo/di/mi/do/fr/sa/so, addon/viewer/lang/de.php + en.php) - bei systematischer Prüfung aller Addon-Sprachdateien festgestellt: diese Schlüssel werden nur von diesem Addon genutzt, standen aber noch in der Core-Sprachdatei (jetzt dort entfernt, siehe lang/frontend/de.php 1.50.0). Funktional keine Änderung - nur die korrekte Zuordnung.

## Version 3.2.0 (Sicherheitsüberarbeitung)

- lmo-viewer.php 3.2.0: Aufruf-Erkennung auf die neue Konstante
  LMO_ADDON_STANDALONE_CALL umgestellt (gesetzt vom neuen zentralen
  Controller /addon-run.php). Der direkte URL-Aufruf ist per
  addon/.htaccess jetzt komplett gesperrt - Einbettungen müssen ab sofort
  über /addon-run.php?addon=spieltag-viewer&file=lmo-viewer.php&...
  laufen, NICHT mehr über /addon/viewer/lmo-viewer.php.
- Neues Manifest-Feld "standalone_entrypoints": ["lmo-viewer.php"].
- Asset-Pfadauflösung (vwProjectRootUrlPrefix()) nutzt jetzt bevorzugt
  die vom Controller gelieferte LMO_ADDON_WEB_BASE.

**WICHTIG für bestehende Einbettungen:** URL wie oben anpassen, falls
bereits per iframe/URL extern eingebunden.
