<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/SportProfile.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Ein Sport-Profil definiert, wie eine bestimmte Sportart funktioniert:
 * - Wie Ergebnisse eingegeben werden (nur Tore? Sätze? Drittel?)
 * - Wie die Tabelle berechnet wird (3-1-0? 3-0/2-1? 2-0?)
 * - Wie Ergebnisse angezeigt werden ("2:1 (1:0)" vs "3:1 Sätze" vs "5:3 n.3.D.")
 * - Ob es Unentschieden geben kann
 * - Ob Nebenwerte (Halbzeit/Sätze/Drittel) nur Anzeige oder auch Tabelle sind
 *
 * Jede Sportart implementiert dieses Interface. Die zentralen Stellen
 * (computeStandings, RenderViewsTrait, PdfExporter, Admin-Ergebnisformular)
 * rufen das Profil ueber SportRegistry::get($sportType) ab, statt die Logik
 * fest fuer Fussball einzubauen.
 *
 * Alle Profile sind zustaendslos (kein Konstruktor mit Ligadaten) - sie kennen
 * nur die *Regeln* ihrer Sportart. Die eigentlichen Ligadaten (Teams, Partien,
 * Optionen) werden als Parameter uebergeben.
 */
interface SportProfile
{
    /** Eindeutiger Schluessel ('football', 'volleyball', 'icehockey'). */
    public function getKey(): string;

    /** Anzeigename fuer Admin-Dropdowns ("Fussball", "Volleyball"). */
    public function getLabel(): string;

    /** Primaere Ergebniseinheit ("Tore", "Punkte", "Saetze"). */
    public function getScoreLabel(): string;

    /** Gibt es Unentschieden? Fussball: true, Basketball/Volleyball: false. */
    public function supportsDraws(): bool;

    /**
     * Beeinflussen Nebenwerte (Halbzeit/Saetze/Drittel) die Tabelle,
     * oder sind sie reine Anzeige fuer Ergebnisse + Spielplan?
     */
    public function periodsAffectStandings(): bool;

    /** Standard-Punktekonfiguration fuer diese Sportart. */
    public function getDefaultPointsConfig(): array;

    /**
     * Welche Perioden/Abschnitte gibt es?
     * ['halftime' => 'Halbzeit']  - Fussball
     * ['set' => 'Satz']           - Volleyball
     * ['period' => 'Drittel']     - Eishockey
     * ['quarter' => 'Viertel']   - Basketball
     * Leeres Array = nur Endstand.
     */
    public function getPeriodFields(): array;

    /**
     * Formatiert ein Ergebnis fuer die Anzeige (Ergebnisse + Spielplan).
     * $match: h_tore, g_tore, status, extra_data (JSON-String)
     * $withPeriods: true = mit Nebenwerten, false = nur Endstand.
     */
    public function formatResult(array $match, bool $withPeriods = true): string;

    /** Formatiert nur die Nebenwerte als String ('' wenn keine). */
    public function formatPeriods(?string $extraData): string;

    /** Felddefinitionen fuer das Admin-Ergebnisformular (zusaetzlich zu h_tore/g_tore). */
    /**
     * Standard-Anzahl der Perioden-Eingabefelder fuer neue Spiele.
     * 1 = einzelner Wert (Halbzeit), >1 = Liste (Saetze/Drittel/Viertel).
     */
    public function getDefaultPeriodCount(): int;

    public function getResultFormFields(): array;

    /** Validiert die eingegebenen Ergebnisdaten (leeres Array = gueltig). */
    public function validateResult(array $data): array;

    /**
     * Verfuegbare Darstellungsmodi fuer Ergebnisse ('short','medium','long').
     * Leeres Array = keine Auswahl, nur Standardanzeige.
     */
    public function getDisplayModes(): array;

    /**
     * Spalten fuer die Tabelle abhaengig vom Darstellungsmodus
     * ('short','medium','long'). Default: das normale getStandingsColumns().
     */
    public function getStandingsColumnsForMode(string $mode = 'short'): array;

    /** Spaltenueberschriften fuer die Tabelle (sportartspezifische Zwischenspalten). */
    public function getStandingsColumns(): array;
}
