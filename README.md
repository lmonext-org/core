<p align="center">
  <img src="https://www.liga-manager-online.org/forum_files/logo.svg" alt="LMOnext" width="260">
</p>

<h1 align="center">LMOnext</h1>

<p align="center">
  Sportliga-Verwaltung für PHP 8.2 / MariaDB &nbsp;·&nbsp; Sports league management for PHP 8.2 / MariaDB
</p>

<p align="center">
  <a href="https://www.liga-manager-online.org/web/">Projektseite / Project page</a> ·
  <a href="https://www.liga-manager-online.org/forum/viewforum.php?f=16">Forum</a> ·
  <a href="./LICENSE">Lizenz / License: GPL-3.0-only</a>
</p>

<p align="center">
  <a href="https://github.com/lmonext-org/core/releases"><img src="https://img.shields.io/github/release/lmonext-org/core?include_prereleases=&sort=semver&color=blue" alt="GitHub release"></a>
  <a href="https://github.com/lmonext-org/core/commits/main/"><img src="https://img.shields.io/github/commits-since/lmonext-org/core/latest" alt="Commits since latest release"></a>
  <a href="https://github.com/lmonext-org/core/releases/latest"><img src="https://img.shields.io/github/downloads/lmonext-org/core/total.svg?style=flat-square" alt="Downloads"></a>
  <a href="https://github.com/lmonext-org/core/discussions"><img src="https://img.shields.io/github/discussions/lmonext-org/core" alt="Discussions"></a>
  <a href="https://github.com/lmonext-org/core/issues"><img src="https://img.shields.io/github/issues/lmonext-org/core" alt="Issues"></a>
  <a href="./CHANGELOG.md"><img src="https://img.shields.io/badge/CHANGELOG-blue" alt="Changelog"></a>
  <a href="https://www.liga-manager-online.org/forum/app.php/donation"><img src="https://img.shields.io/badge/-Buy%20me%20a%20coffee-brown.svg" alt="Donate"></a>
</p>

<p align="center">
  <a href="#deutsch">🇩🇪 Deutsch</a> &nbsp;|&nbsp; <a href="#english">🇬🇧 English</a>
</p>

---

<a id="deutsch"></a>
# 🇩🇪 Deutsch

## Über das Projekt

**LMOnext** ist eine komplette Neuentwicklung des klassischen *Liga Manager Online (LMO)* – von Grund auf neu geschrieben für **PHP 8.2** und **MariaDB**, statt (wie frühere Community-Patches) nur den alten PHP-4-Code kompatibel zu halten. Mit LMOnext betreibst du einen eigenen Ergebnisdienst für beliebig viele Sportligen direkt auf deinem eigenen Webserver: Tabellen, Spielpläne, Statistiken und mehr – die komplette Verwaltung läuft bei dir, nicht bei einem Drittanbieter. Du trägst nur die Ergebnisse ein, den Rest (Tabellenberechnung, Formkurve, Auf-/Abstiegszonen, Torschützenlisten …) übernimmt LMOnext automatisch.

Ein Nachfolgeprojekt für den PHP-4-Klassiker existiert parallel weiter (Wartung des Original-LMO), LMOnext ist die moderne Neuentwicklung daneben.

## Funktionen

**Liga-Verwaltung**
- Beliebig viele Ligen gleichzeitig, jede mit eigenem Spielplan
- Zwei Wettbewerbsformen: klassische **Liga** (Punktrunde) und **KO-Turnier** (Einzelspiel, Hin-/Rückspiel, Best of 3/5/7)
- Globaler Team-Pool: ein Team wird einmal angelegt und in beliebig vielen Ligen/Saisons wiederverwendet
- Team-Historie: Umbenennungen, Fusionen und Abspaltungen werden nachvollziehbar verknüpft
- Grüner-Tisch-Entscheidungen (nachträgliche Wertung/Nichtwertung von Spielen) inkl. Begründungstext
- Sport-Profile über den Fußball hinaus (z. B. Volleyball mit Satz-Ergebnissen)
- Archiv mit Ordnerstruktur für abgeschlossene Saisons/Ligen

**Tabellen & Statistiken**
- Gesamt-, Heim-, Gast-, Hin- und Rückrundentabelle
- Formkurve der letzten 5 Spiele je Team mit Mouseover-Tooltip (Gegner, Ergebnis, Datum) und Pfeil zur nächsten, noch nicht gespielten Begegnung
- Tendenz-Pfeile (Platzierungsentwicklung seit dem letzten Spieltag), Favoriten-Team-Hervorhebung, farbige Auf-/Abstiegs-/Relegationszonen
- Was-wäre-wenn-Tabellenrechner, ewige Tabelle, Rekorde und mehr – als optionale Addons (siehe unten)

**Mehrsprachigkeit & Darstellung**
- Adminbereich und Besucherbereich vollständig getrennt lokalisierbar, Kern liefert Deutsch/Englisch mit
- 5 mitgelieferte Frontend-Templates (Default, Colored, Dark, Light, Matchday) – wählbar, eigene Templates möglich
- Barrierearme, responsive Darstellung ohne schwere JavaScript-Frameworks

**Administration & Sicherheit**
- Installationsassistent mit Server-Check (PHP-Version, Erweiterungen, Schreibrechte), Datenbank-Rechte-Vorabprüfung und abschließender Konsistenzprüfung des angelegten Schemas
- Composer-Unterstützung optional – funktioniert auch ganz ohne Composer über eine klassische `config.php`
- CSRF-Schutz, Login-Rate-Limiting, Passwort-Reset per E-Mail, Administrator-Audit-Log
- Import/Export von `.l98`-Ligadateien (auch als ZIP-Stapelimport), Backup-Funktion

**Addon-System**
LMOnext bringt einen eingebauten Addon-Manager mit: offizielle Addons lassen sich im Adminbereich unter „Addons → Entdecken“ mit einem Klick direkt von GitHub installieren, Updates werden automatisch über die jeweiligen GitHub-Releases erkannt. Genauso lässt sich jedes inoffizielle Addon per ZIP-Upload oder beliebiger GitHub-URL installieren.

Offizielle Addons (Auswahl):

| Addon | Funktion |
|---|---|
| Spieltag-Viewer | Spielübersichten über mehrere Ligen hinweg |
| Mini-Tabelle / Mini-Next | Kompakte Tabelle bzw. Nächstes-Spiel-Widget zum Einbinden |
| Ewige Tabelle | Tabelle über mehrere Saisons hinweg |
| Tabellenrechner | Was-wäre-wenn-Ergebnisse simulieren |
| Liga-Klassen-Rekorde | Alle Meister und Rekorde über mehrere Ligen hinweg |
| Relegation | Relegationsspiele zwischen Ligen |
| Spielerstatistik | Spielerstatistiken mit Foto-Upload und CSV-Import |
| Tippspiel | Tippspiel mit Anmeldung, Auswertung und Joker-Regeln |
| Translator | Übersetzungs-Editor mit optionaler DeepL-Anbindung |
| Liga-/Team-Notizen | Freitext-Notizen und Team-Stammdaten |
| Newsticker | Laufband oberhalb der Liga-Tabs |

## Systemvoraussetzungen

- Webserver mit **PHP 8.2** oder neuer
- **MariaDB 10.2.7+** oder **MySQL 5.7.8+** (wegen des JSON-Spaltentyps)
- Übliche PHP-Erweiterungen: PDO/pdo_mysql, mbstring, GD oder Imagick, ZipArchive
- Composer ist **optional** – ohne Composer installiert LMOnext klassisch über eine `config.php`

## Installation

1. Repository/Release-ZIP auf den Webserver laden
2. `install.php` im Browser aufrufen – der Assistent prüft die Server-Umgebung, legt die Datenbank-Struktur an und führt eine abschließende Konsistenzprüfung durch
3. Zugangsdaten für Datenbank und Administrator-Account eingeben
4. Nach erfolgreicher Installation löscht sich `install.php` automatisch

## Mitmachen & Support

- **Support/Diskussion:** [Forum](https://www.liga-manager-online.org/forum/viewforum.php?f=16)
- **Quellcode/Issues/Releases:** [github.com/lmonext-org/core](https://github.com/lmonext-org/core)
- **Projektseite:** [liga-manager-online.org/web](https://www.liga-manager-online.org/lmonext/)
- Änderungen an jeder einzelnen Datei sind lückenlos im projektweiten `CHANGELOG.md` dokumentiert.

## Lizenz

LMOnext steht unter der **GPL-3.0-only**-Lizenz, siehe [LICENSE](./LICENSE).

## Mitwirkende

- **Dietmar Kersting** ([@henshingly](https://github.com/henshingly)) – Projektbetreuer
- **Torsten Hofmann** ([@webfalter](https://github.com/webfalter)) – Mitentwicklung (u. a. LMONext, Addon-Manager, mehrere offizielle Addons)

---

<a id="english"></a>
# 🇬🇧 English

## About the project

**LMOnext** is a complete rewrite of the classic *Liga Manager Online (LMO)* – built from the ground up for **PHP 8.2** and **MariaDB**, rather than just keeping the old PHP 4 codebase alive like earlier community patches did. With LMOnext you run your own results service for any number of sports leagues directly on your own web server: standings, schedules, statistics and more – all the league management happens on your server, not with a third party. All you do is enter the results; LMOnext takes care of the rest (standings calculation, form guide, promotion/relegation zones, top-scorer lists, …) automatically.

A successor project for the PHP 4 classic continues to exist in parallel (maintaining the original LMO); LMOnext is the modern rewrite alongside it.

## Features

**League management**
- Any number of leagues at once, each with its own schedule
- Two competition formats: classic **league** (round-robin) and **knockout tournament** (single match, home/away, best of 3/5/7)
- Global team pool: a team is created once and reused across any number of leagues/seasons
- Team history: renames, mergers and splits are tracked and linked
- "Green table" decisions (retroactively voiding/awarding matches) with a reason field
- Sport profiles beyond football (e.g. volleyball with set scores)
- Archive with a folder structure for finished seasons/leagues

**Standings & statistics**
- Overall, home, away, first-half and second-half standings
- Last-5-games form guide per team with a mouseover tooltip (opponent, score, date) and an arrow pointing to the next, not-yet-played match
- Trend arrows (ranking movement since the last matchday), favourite-team highlighting, coloured promotion/relegation/play-off zones
- What-if standings calculator, all-time table, records and more – as optional add-ons (see below)

**Localisation & presentation**
- Admin area and visitor-facing frontend are localised completely independently; the core ships German/English
- 5 bundled frontend templates (Default, Colored, Dark, Light, Matchday) – selectable, custom templates supported
- Accessible, responsive output without heavy JavaScript frameworks

**Administration & security**
- Installation wizard with a server check (PHP version, extensions, write permissions), a database-privilege pre-flight test, and a final consistency check of the created schema
- Composer support is optional – works fully without Composer via a classic `config.php`
- CSRF protection, login rate limiting, email-based password reset, administrator audit log
- Import/export of `.l98` league files (including ZIP batch import), backup function

**Add-on system**
LMOnext ships a built-in add-on manager: official add-ons can be installed with a single click directly from GitHub via "Add-ons → Discover" in the admin area, and updates are detected automatically from the respective GitHub releases. Any unofficial add-on can be installed the same way via a ZIP upload or any GitHub URL.

Official add-ons (selection):

| Add-on | What it does |
|---|---|
| Matchday Viewer | Match overviews spanning multiple leagues |
| Mini Table / Mini Next | Compact standings / "next match" widget for embedding |
| All-Time Table | Standings spanning multiple seasons |
| Standings Calculator | Simulate what-if results |
| League Records | All champions and records across multiple leagues |
| Play-offs | Promotion/relegation play-off matches between leagues |
| Player Statistics | Player stats with photo upload and CSV import |
| Prediction Game | Prediction game with sign-up, scoring and joker rules |
| Translator | Translation editor with optional DeepL integration |
| League/Team Notes | Free-text notes and team master data |
| News Ticker | Scrolling ticker above the league tabs |

## System requirements

- Web server with **PHP 8.2** or newer
- **MariaDB 10.2.7+** or **MySQL 5.7.8+** (required for the JSON column type)
- Common PHP extensions: PDO/pdo_mysql, mbstring, GD or Imagick, ZipArchive
- Composer is **optional** – without it, LMOnext installs the classic way via a `config.php`

## Installation

1. Upload the repository/release ZIP to your web server
2. Open `install.php` in your browser – the wizard checks the server environment, creates the database schema and runs a final consistency check
3. Enter your database credentials and administrator account
4. `install.php` deletes itself automatically once installation succeeds

## Contributing & support

- **Support/discussion:** [Forum](https://www.liga-manager-online.org/forum/viewforum.php?f=16)
- **Source code/issues/releases:** [github.com/lmonext-org/core](https://github.com/lmonext-org/core)
- **Project page:** [liga-manager-online.org/web](https://www.liga-manager-online.org/lmonext/)
- Changes to every single file are documented in full in the project-wide `CHANGELOG.md`.

## License

LMOnext is licensed under **GPL-3.0-only**, see [LICENSE](./LICENSE).

## Contributors

- **Dietmar Kersting** ([@henshingly](https://github.com/henshingly)) – project maintainer
- **Torsten Hofmann** ([@webfalter](https://github.com/webfalter)) – co-development (incl. the lmonext, add-on manager and several official add-ons)
