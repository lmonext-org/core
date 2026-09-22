<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/SpieltagRepositoryTrait.php
 * Fileversion: 1.7.0
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
trait SpieltagRepositoryTrait
{
    /**
     * Alle Spieltage/Runden einer Liga mit Basis-Statistik (gespielt/partie_count/
     * pairing_count), aufsteigend nach Nummer. Grundlage für Auswahl/Navigation
     * und die KO-Rundennamen.
     */
    public static function getAllSpieltage(int $ligaId) : array
    {
        try {
            $s = getDB()->prepare(
                'SELECT s.id, s.nummer, s.start,
                        SUM(CASE WHEN p.h_tore IS NOT NULL THEN 1 ELSE 0 END) AS gespielt,
                        COUNT(p.id) AS partie_count,
                        COUNT(DISTINCT SUBSTRING_INDEX(p.spiel_nr, "_", 1)) AS pairing_count
                   FROM ' . tbl('liga_spieltage') . ' s
                   LEFT JOIN ' . tbl('liga_partien') . ' p ON p.spieltag_id = s.id
                  WHERE s.liga_id = ?
                  GROUP BY s.id, s.nummer, s.start
                  ORDER BY s.nummer'
            );
            $s->execute([$ligaId]);
            return $s->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }
    /** Höchste Spieltag-/Rundennummer einer Liga (für die "ist das die letzte Runde?"-Prüfung). */
    public static function getMaxSpieltagNummer(array $allSpieltage) : int
    {
        $max = 0;
        foreach ($allSpieltage as $st) {
            $max = max($max, (int)$st['nummer']);
        }
        return $max;
    }
    /**
     * Ermittelt den letzten Spieltag/die letzte Runde mit mindestens einem
     * eingetragenen Ergebnis. Gibt es noch keine Ergebnisse, wird stattdessen
     * der erste Spieltag zurückgegeben. Liefert null, wenn die Liga überhaupt
     * keine Spieltage hat.
     */
    public static function getLatestSpieltagWithResults(array $allSpieltage) : ?array
    {
        if (empty($allSpieltage)) {
            return null;
        }
        $latest = null;
        foreach ($allSpieltage as $row) {
            if ((int)$row['gespielt'] > 0) {
                $latest = $row;
            }
        }
        return $latest ?? $allSpieltage[0];
    }
    /**
     * Sucht einen bestimmten Spieltag/eine Runde per Nummer innerhalb einer
     * bereits geladenen Spieltag-Liste (siehe getAllSpieltage()).
     */
    public static function getSpieltagByNummer(array $allSpieltage, int $nummer) : ?array
    {
        foreach ($allSpieltage as $row) {
            if ((int)$row['nummer'] === $nummer) {
                return $row;
            }
        }
        return null;
    }
    /**
     * Partien (Spiele) eines Spieltags, mit aufgelösten Teamnamen (Platzhalter-
     * Teams im KO-Modus nutzen heim_label/gast_label statt einer echten
     * Team-Zuordnung).
     */
    public static function getSpieltagPartien(int $spieltagId) : array
    {
        // "status" ist eine on-demand-Spalte (wird erst per ensureSpielstatusColumns()
        // im Adminbereich angelegt) – hier defensiv prüfen, damit die Abfrage auch auf
        // einer ganz frischen Installation nicht scheitert.
        static $hasStatusColumn = null;
        if ($hasStatusColumn === null) {
            try {
                getDB()->query('SELECT status FROM ' . tbl('liga_partien') . ' LIMIT 0');
                $hasStatusColumn = true;
            } catch (\Throwable) {
                $hasStatusColumn = false;
            }
        }
        $statusSelect = $hasStatusColumn ? ', p.status' : '';

        // "extra_data" (Sätze/Drittel/Viertel für Nicht-Fußball-Sportarten,
        // Beitrag: Torsten Hofmann) - dieselbe defensive Prüfung wie oben bei
        // status, für Installationen vor der Sport-Profile-Migration.
        static $hasExtraDataColumn = null;
        if ($hasExtraDataColumn === null) {
            try {
                getDB()->query('SELECT extra_data FROM ' . tbl('liga_partien') . ' LIMIT 0');
                $hasExtraDataColumn = true;
            } catch (\Throwable) {
                $hasExtraDataColumn = false;
            }
        }
        $extraDataSelect = $hasExtraDataColumn ? ', p.extra_data' : '';

        // "nicht_gewertet" - dieselbe defensive Prüfung wie oben bei status/
        // extra_data (siehe ensureSpielstatusColumns() in admin/bootstrap.php).
        static $hasNichtGewertetColumn = null;
        if ($hasNichtGewertetColumn === null) {
            try {
                getDB()->query('SELECT nicht_gewertet FROM ' . tbl('liga_partien') . ' LIMIT 0');
                $hasNichtGewertetColumn = true;
            } catch (\Throwable) {
                $hasNichtGewertetColumn = false;
            }
        }
        $nichtGewertetSelect = $hasNichtGewertetColumn ? ', p.nicht_gewertet' : '';

        // "gt_entscheidung" - dieselbe defensive Prüfung wie oben.
        static $hasGtColumn = null;
        if ($hasGtColumn === null) {
            try {
                getDB()->query('SELECT gt_entscheidung FROM ' . tbl('liga_partien') . ' LIMIT 0');
                $hasGtColumn = true;
            } catch (\Throwable) {
                $hasGtColumn = false;
            }
        }
        $gtSelect = $hasGtColumn ? ', p.gt_entscheidung' : '';

        // "gt_grund" (freier Zusatztext zur Grüne-Tisch-
        // Entscheidung) - dieselbe defensive Prüfung wie oben.
        static $hasGtGrundColumn = null;
        if ($hasGtGrundColumn === null) {
            try {
                getDB()->query('SELECT gt_grund FROM ' . tbl('liga_partien') . ' LIMIT 0');
                $hasGtGrundColumn = true;
            } catch (\Throwable) {
                $hasGtGrundColumn = false;
            }
        }
        $gtGrundSelect = $hasGtGrundColumn ? ', p.gt_grund' : '';
    
        try {
            $s = getDB()->prepare(
                'SELECT p.id, p.heim_id, p.gast_id, p.heim_label, p.gast_label,
                        p.h_tore, p.g_tore, p.zeit, p.spiel_nr' . $statusSelect . $extraDataSelect . $nichtGewertetSelect . $gtSelect . $gtGrundSelect . ',
                        th.name AS heim_name, tg.name AS gast_name,
                        th.kurz AS heim_kurz, tg.kurz AS gast_kurz
                   FROM ' . tbl('liga_partien') . ' p
                   LEFT JOIN ' . tbl('teams_global') . ' th ON th.id = p.heim_id
                   LEFT JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.gast_id
                  WHERE p.spieltag_id = ?
                  ORDER BY p.zeit IS NULL, p.zeit ASC,
                           CAST(SUBSTRING_INDEX(p.spiel_nr, "_", 1) AS UNSIGNED),
                           CAST(SUBSTRING_INDEX(p.spiel_nr, "_", -1) AS UNSIGNED)'
            );
            $s->execute([$spieltagId]);
            $rows = $s->fetchAll();
            if (!$hasStatusColumn) {
                foreach ($rows as &$row) {
                    $row['status'] = 0;
                }
                unset($row);
            }
            if (!$hasNichtGewertetColumn) {
                foreach ($rows as &$row) {
                    $row['nicht_gewertet'] = 0;
                }
                unset($row);
            }
            if (!$hasGtColumn) {
                foreach ($rows as &$row) {
                    $row['gt_entscheidung'] = 0;
                }
                unset($row);
            }
            if (!$hasGtGrundColumn) {
                foreach ($rows as &$row) {
                    $row['gt_grund'] = null;
                }
                unset($row);
            }
            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }
    /**
     * Alle Partien einer Liga über alle Spieltage hinweg (für Tabelle, Spielpläne,
     * Kreuztabelle, Fieberkurven, Ligastatistik).
     */
    public static function getAllLigaPartien(array $allSpieltage, ?int $ligaId = null) : array
    {
        $all = [];
        foreach ($allSpieltage as $st) {
            foreach (self::getSpieltagPartien((int)$st['id']) as $p) {
                $p['_spieltag_nummer'] = (int)$st['nummer'];
                // Für die sport-profil-abhängige Anzeige (Beitrag: Torsten
                // Hofmann, siehe RenderViewsTrait::formatScore()) - optional,
                // bestehende Aufrufer ohne $ligaId bleiben unverändert.
                if ($ligaId !== null) {
                    $p['_liga_id'] = $ligaId;
                }
                $all[] = $p;
            }
        }
        return $all;
    }
}
