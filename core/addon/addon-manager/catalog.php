<?php
/**
 * Project: LMOnext
 * Filename: catalog.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Offizieller Addon-Katalog (Core-Team) ──────────────────────────────────
// Kuratierte, mit dem Core ausgelieferte Liste aller offiziellen LMOnext-
// Addons (Quelle: dieselbe Addon-Datenbank, die auch die Projekt-Website
// liga-manager-online.org nutzt, hier auf die für die Direktinstallation
// nötigen Felder reduziert). Wird ausschließlich für den "Entdecken"-Tab der
// Addon-Verwaltung genutzt (siehe view_addons.php), damit auf einer frischen
// Installation ohne Addons sofort eine Direktinstallation per Klick möglich
// ist - siehe CHANGELOG.
//
// Rein statische Daten, kein Datenbankzugriff, kein Netzwerkaufruf beim
// Anzeigen. Die eigentliche Installation läuft vollständig über den bereits
// vorhandenen "install_from_url"-Weg (AddonManager::installFromGithubUrl(),
// siehe handler_addons.php addon_action=install_from_url) - technisch exakt
// derselbe Weg wie eine manuell eingetippte GitHub-URL, inklusive aller
// Sicherheitsprüfungen (Zip-Validierung, PHP-Lint, Muster-Scan). Bei einem
// neuen offiziellen Addon oder einer neuen Beschreibung reicht ein normales
// Core-Update - kein separater Mechanismus nötig.
//
// addon-manager selbst taucht hier bewusst NICHT auf: es ist fester
// Bestandteil des Cores (siehe addon/addon-manager/addon.json) und wird nie
// separat installiert, nur mit dem Core selbst aktualisiert.
function getOfficialAddonCatalog() : array
{
    return [
        'viewer' => [
            'name'       => 'Spieltag-Viewer',
            'icon'       => '📅',
            'type'       => 'standalone',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-viewer',
            'short_desc' => 'Spielübersichten über mehrere Ligen hinweg in frei wählbarem Zeitraum — Standard- oder Kachel-Template.',
        ],
        'mini-tabelle' => [
            'name'       => 'Mini-Tabelle',
            'icon'       => '📐',
            'type'       => 'standalone',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-mini-tabelle',
            'short_desc' => 'Kompakte Mini-Tabelle zum Einbinden per Include oder IFrame.',
        ],
        'mini-next' => [
            'name'       => 'Mini-Next',
            'icon'       => '⏭️',
            'type'       => 'standalone',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-mini-tabelle',
            'short_desc' => 'Mini-Next-Ansicht (nächstes Spiel einer Mannschaft) zum Einbinden per Include oder IFrame.',
        ],
        'ewige' => [
            'name'       => 'Ewige Tabelle',
            'icon'       => '📈',
            'type'       => 'standalone',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-ewige',
            'short_desc' => 'Ewige Tabelle über mehrere Ligen hinweg — Summen- oder Verlaufsansicht.',
        ],
        'tabellenrechner' => [
            'name'       => 'Tabellenrechner',
            'icon'       => '🧮',
            'type'       => 'standalone',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-tabellenrechner',
            'short_desc' => 'Was-wäre-wenn-Rechner: Ergebnisse simulieren und Tabellenänderung live sehen.',
        ],
        'meister' => [
            'name'       => 'Liga-Klassen-Rekorde',
            'icon'       => '🏆',
            'type'       => 'standalone',
            'author'     => 'Dietmar Kersting',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-liga-klassen-rekorde',
            'short_desc' => 'Alle Meister über mehrere Ligen hinweg — mit Punkten, Toren und Bilanz. Plus aggregierte Statistik.',
        ],
        'relegation' => [
            'name'       => 'Relegation',
            'icon'       => '🔀',
            'type'       => 'standalone',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-relegation',
            'short_desc' => 'Relegationsspiele für Auf- und Abstieg zwischen Ligen — Hin- und Rückspiel.',
        ],
        'player' => [
            'name'       => 'Spielerstatistik',
            'icon'       => '📊',
            'type'       => 'both',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-player',
            'short_desc' => 'Detaillierte Spielerstatistiken mit Spaltenverwaltung, Foto-Upload und CSV-Import.',
        ],
        'tipp' => [
            'name'       => 'Tippspiel',
            'icon'       => '🎯',
            'type'       => 'both',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-tipp',
            'short_desc' => 'Tippspiel-System mit Anmeldung, Tippabgabe, Auswertung und Joker-Regeln.',
        ],
        'translator' => [
            'name'       => 'Translator',
            'icon'       => '🌐',
            'type'       => 'admin',
            'author'     => 'Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-translator',
            'short_desc' => 'Split-View Editor für alle Sprachdateien — mit optionaler DeepL Auto-Übersetzung.',
        ],
        'liga-notizen' => [
            'name'       => 'Liga-Notizen',
            'icon'       => '📝',
            'type'       => 'admin',
            'author'     => 'Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-liga-notizen',
            'short_desc' => 'Notizen pro Liga anlegen, bearbeiten und löschen. Beispiel-Addon für das Addon-System.',
        ],
        'team-notizen' => [
            'name'       => 'Team-Notizen',
            'icon'       => '📋',
            'type'       => 'both',
            'author'     => 'Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-team-notizen',
            'short_desc' => 'Stammdaten, Erfolge und Trainer-Historie pro Team pflegen und im Frontend anzeigen.',
        ],
        'ticker' => [
            'name'       => 'Newsticker',
            'icon'       => '📢',
            'type'       => 'both',
            'author'     => 'Dietmar Kersting, Torsten Hofmann',
            'homepage'   => 'https://github.com/henshingly/lmonext_addon-ticker',
            'short_desc' => 'Scrollendes Laufband oberhalb der Liga-Tabs — freier Text oder aktuelle Ergebnisse.',
        ],
    ];
}
