<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/LigaRepositoryTrait.php
 * Fileversion: 1.2.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types=1);

namespace LMOnext\Liga;

/**
 * Extracted from the legacy frontend/data_liga.php.
 * Behavior is intentionally preserved; public compatibility wrappers live in frontend/data_liga.php.
 */
trait LigaRepositoryTrait
{
    /**
     * Liga-Grunddaten (Name, Datum, …) oder null, falls nicht gefunden.
     */
    public static function getLigaById(int $id) : ?array
    {
        try {
            $s = getDB()->prepare('SELECT * FROM ' . tbl('liga') . ' WHERE id=?');
            $s->execute([$id]);
            $row = $s->fetch();
            return $row !== false ? $row : null;
        } catch (\Throwable) {
            return null;
        }
    }
    /**
     * Sportart der Liga ('football', 'volleyball', 'icehockey', 'basketball',
     * 'handball', 'badminton' - siehe src/Sport/SportRegistry.php, Beitrag:
     * Torsten Hofmann). Fallback auf 'football' für volle Rückwärtskompatibilität
     * (bestehende Ligen haben sport_type='football' als DB-Standardwert).
     */
    public static function getLigaSportType(int $ligaId) : string
    {
        try {
            $s = getDB()->prepare('SELECT sport_type FROM ' . tbl('liga') . ' WHERE id=?');
            $s->execute([$ligaId]);
            $v = $s->fetchColumn();
            return $v !== false && $v !== '' ? (string)$v : 'football';
        } catch (\Throwable) {
            return 'football';
        }
    }
    /**
     * Liga-Typ: 0 = Liga, 1 = KO-Turnier.
     */
    public static function getLigaType(int $ligaId) : int
    {
        try {
            $s = getDB()->prepare('SELECT option_value FROM ' . tbl('liga_options') . ' WHERE liga_id=? AND option_key="Type"');
            $s->execute([$ligaId]);
            $v = $s->fetchColumn();
            return $v !== false ? (int)$v : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
    /**
     * Anzahl der Teams, die dieser Liga zugeordnet sind (für die Info-Ansicht).
     */
    public static function getLigaTeamCount(int $ligaId) : int
    {
        try {
            $s = getDB()->prepare('SELECT COUNT(*) FROM ' . tbl('liga_teams') . ' WHERE liga_id=?');
            $s->execute([$ligaId]);
            return (int)$s->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }
    /**
     * Liest die Liga-Optionen (siehe liga_options-Tabelle). Wird pro Seitenaufruf
     * oft für dieselbe Liga mehrfach aufgerufen (Tabelle, Ergebnisse, PDF-Export
     * usw. greifen alle unabhängig voneinander darauf zu) - daher Speicher-Cache
     * pro Liga-ID, damit dieselbe Liga innerhalb eines Requests nur einmal
     * abgefragt wird.
     */
    public static function getLigaOptions(int $ligaId) : array
    {
        static $cache = [];
        if (array_key_exists($ligaId, $cache)) {
            return $cache[$ligaId];
        }
        try {
            $s = getDB()->prepare('SELECT option_key, option_value FROM ' . tbl('liga_options') . ' WHERE liga_id=?');
            $s->execute([$ligaId]);
            $out = [];
            foreach ($s->fetchAll() as $row) {
                $out[$row['option_key']] = $row['option_value'];
            }
            // Einmalige favTeam/selTeam-Migration (auf Wunsch, siehe
            // ausführlicher Kommentar bei TeamRepositoryTrait::
            // resolveTeamNumberToId()) - läuft VOR dem Cachen dieser
            // Rückgabe, damit der gecachte Wert bereits die migrierten,
            // stabilen Team-IDs enthält statt der alten Positions-Werte.
            self::migrateFavSelTeamToStableId($ligaId, $out);
            return $cache[$ligaId] = $out;
        } catch (\Throwable) {
            return $cache[$ligaId] = [];
        }
    }
    /**
     * Prüft ein Ein/Aus-Flag aus liga_options. Fehlt der Schlüssel ganz (z.B. bei
     * einer neu angelegten Liga, für die noch nie die Einstellungen gespeichert
     * wurden), gilt $default – für Kalender/Ergebnisse/Spielpläne ist das "an",
     * damit neue Ligen nicht versehentlich unsichtbar für Besucher sind.
     */
    public static function ligaFlagEnabled(array $opts, string $key, bool $default = true) : bool
    {
        return ($opts[$key] ?? ($default ? '1' : '0')) === '1';
    }
    /**
     * Welche Besucher-Reiter für diese Liga sichtbar sein sollen, basierend auf
     * den Liga-Einstellungen. "Info" ist immer an.
     */
    public static function getLigaViewFlags(array $opts) : array
    {
        return [
            'kalender'      => self::ligaFlagEnabled($opts, 'Kalender', true),
            'ergebnisse'    => self::ligaFlagEnabled($opts, 'Ergebnis', true),
            'tabelle'       => self::ligaFlagEnabled($opts, 'Tabelle', true),
            'spielplaene'   => self::ligaFlagEnabled($opts, 'Plan', true),
            'kreuztabelle'  => self::ligaFlagEnabled($opts, 'Kreuz', true),
            'fieberkurve'   => self::ligaFlagEnabled($opts, 'kurve1', true),
            'ligastatistik' => self::ligaFlagEnabled($opts, 'Ligastats', true),
            'spielerstatistik' => self::ligaFlagEnabled($opts, 'stats', false),
            'info'          => true,
        ];
    }
}
