# Changelog: ewige-Addon (LMOnext)

Dieses Addon war bis LMOnext 1.9.0-beta Teil des Core-Pakets. Mit der
Einführung des Addon-Manager-Frameworks (Beitrag Torsten Hofmann) wurde es
als eigenständiges, self-contained Paket extrahiert.

Die vollständige Entwicklungshistorie bis zur Extraktion steht im
CHANGELOG.md des LMOnext-Kernprojekts unter den Abschnitten
`addon/ewige/*`.

## 1.3.2

- Spaltenüberschrift "Tore" übernimmt jetzt die Einstellung "Alternative
  für Tore" (z.B. "Holz") der ersten ausgewählten Liga; Platzhalter
  <!--ToreLabel--> in templates/standard.tpl.php (lmo-ewigetab.php 1.3.2,
  standard.tpl.php 1.2.2). Eigene Templates mit festem "Tore" bleiben
  unverändert, bis dort der Platzhalter eingesetzt wird.
- Freilos-Teams werden ausgeblendet (Kennzeichen im Core, ab LMOnext 1.11.7).

## Aktuelle Version: 1.0.2

- Als eigenständiges addon.json-Paket verpackt (Templates/Sprachdateien
  jetzt lokal im Addon statt zentral im Core), installierbar über
  Administrator → Addons.

## Version 1.3.1 (KRITISCHER Bugfix)

- KRITISCHER Bugfix (gemeldet: Tabelle zeigte kaputtes HTML wie '(ewige_ehemals)">KFC Uerdingen 05' statt einer normalen Zeile): das Standard-Template verwendet den Platzhalter <!--TeamLang--> ZWEIMAL - einmal im title-Attribut (Tooltip), einmal als sichtbarer Text. Der neue HTML-Zusatz für ehemalige Namen (u.a. ein <span>-Tag mit Anführungszeichen im class-Attribut) landete dadurch auch IM title-Attribut und brach es vorzeitig ab, wodurch der Browser den Rest als rohen, kaputten Text anzeigte. Fix: standard.tpl.php 1.2.1 nutzt jetzt einen eigenen Platzhalter <!--TeamLangTitel--> für den Attribut-Kontext (reiner Text, ohne jedes HTML), lmo-ewigetab.php 1.3.1 befüllt ihn entsprechend separat.
- Hinweis: falls weiterhin der rohe Schlüssel "(ewige_ehemals)" statt eines übersetzten Textes erscheint, prüfe bitte, ob das komplette Addon-Paket (inkl. lang/de.php) neu hochgeladen wurde - der Schlüssel wurde erst mit Version 1.3.0 neu eingeführt.

## Version 1.3.0 (Neues Feature)

- Neues Feature (auf Wunsch): berücksichtigt jetzt bereits im Admin-Bereich hinterlegte Team-Verknüpfungen (Umbenennung/Fusion/Abspaltung, siehe Administrator → Teams (global) → Verknüpfungen). Verknüpfte Teams (z.B. "Bayer 05 Uerdingen" und "KFC Uerdingen 05") werden in der Ewigen Tabelle als EINE Zeile unter der kanonischen (aktuellen) Team-ID zusammengefasst statt getrennt gezählt. Der Teamname zeigt zusätzlich in Klammern die ehemaligen Namen an, unter denen dieselbe Mannschaft in den ausgewählten Ligen sonst noch spielte, z.B. "Bayer 05 Uerdingen (ehem. KFC Uerdingen 05)".
- Die eigentliche Verknüpfungsauflösung liegt im CORE (src/Liga/Eternal/EternalTableService.php 1.2.0, resolveTeamLinkGroups()) - bewusst unabhängig vom teamvergleich-Addon implementiert, da team_links eine reine Core-Tabelle ist. Auflösung nach demselben Prinzip wie beim Direktvergleich im teamvergleich-Addon: transitive Gruppierung über alle Verknüpfungen (auch Ketten wie A->B->C), dann die "wird abgelöst durch"-Kette (newer_team_id) auflösen; ist keine eindeutige Richtung hinterlegt, gilt das Team mit der höchsten ID der Gruppe als kanonisch (Fallback).
- Betrifft sowohl die Summenansicht (eternal) als auch den Mehrjahres-Vergleich (verlauf/Matrix) - in beiden wird eine verknüpfte Mannschaft als eine Zeile geführt.
- lmo-ewigetab.php 1.3.0: neue Darstellung der ehemaligen Namen, dezent gestylt (kleiner, grau, nicht fett, siehe .ewige-ehemals in standard.tpl.php 1.2.0) und mit white-space:normal versehen, damit lange Namenszusätze die Tabelle nicht unkontrolliert breit ziehen (die übrigen Zellen nutzen bewusst white-space:nowrap).
- Neuer Sprachschlüssel: ewige_ehemals.
- Gegen den Addon-Manager-Sicherheitsscanner getestet: keine Treffer.

## addon/ewige/lmo-ewigetab.php

- Changelog: 1.1.0 - Alle hartcodierten deutschen Texte durch tf()-Aufrufe ersetzt (Seiten-/Tabellentitel, Fehlermeldungen bei fehlendem Parameter/Template, Fußzeilen). Zusätzlich fehlte der loadLanguages()-Aufruf komplett - Sprachdateien wurden bislang nie geladen. Jetzt ergänzt mit korrektem Manifest-Namen "ewige-tabelle" (nicht Ordnername "ewige").

## Sprachdateien (lang/de.php, lang/en.php)

- Neu hinzugefügt: enthalten jetzt die 9 ewige_*-Schlüssel für Titel,
  Fehlermeldungen und Fußzeilen des Ewige-Tabelle-Addons.

## Version 1.2.0 (Sicherheitsüberarbeitung)

- HINWEIS: Version bewusst auf 1.2.0 gesprungen (nicht 1.1.1), da eine
  Version 1.1.1 mit anderen, extern angekündigten inhaltlichen Fixes
  (Sortierung, $wertung-Parameter, Docblock) noch nicht vorlag und keine
  Versionskollision riskiert werden sollte - siehe frühere Chat-Notiz.
  Falls diese Fixes nachgereicht werden, bitte in einer FOLGEVERSION
  (z.B. 1.2.1) einarbeiten lassen, nicht rückwirkend als 1.1.1.
- lmo-ewigetab.php 1.2.0: Aufruf-Erkennung auf die neue Konstante
  LMO_ADDON_STANDALONE_CALL umgestellt (gesetzt vom neuen zentralen
  Controller /addon-run.php). Der direkte URL-Aufruf ist per
  addon/.htaccess jetzt komplett gesperrt - Einbettungen müssen ab sofort
  über /addon-run.php?addon=ewige-tabelle&file=lmo-ewigetab.php&...
  laufen, NICHT mehr über /addon/ewige/lmo-ewigetab.php.
- Neues Manifest-Feld "standalone_entrypoints": ["lmo-ewigetab.php"].
- Asset-Pfadauflösung (ewigeProjectRootUrlPrefix()) nutzt jetzt bevorzugt
  die vom Controller gelieferte LMO_ADDON_WEB_BASE.

**WICHTIG für bestehende Einbettungen:** URL wie oben anpassen, falls
bereits per iframe/URL extern eingebunden.
