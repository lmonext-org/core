<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/TeamRepositoryTrait.php
 * Fileversion: 1.1.0
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
trait TeamRepositoryTrait
{
    /**
     * Validiert, dass eine gespeicherte favTeam/selTeam-Team-ID tatsächlich
     * Teil dieser Liga ist - gibt sie unverändert zurück, wenn ja, sonst
     * null (z.B. wenn das Team zwischenzeitlich aus der Liga entfernt wurde).
     *
     * WICHTIGE HISTORIE (Bugfix, gemeldet nach einem Re-Import über das
     * url-import-Addon): favTeam/selTeam speicherten früher NICHT die
     * stabile teams_global.id, sondern nur die POSITION dieses Teams
     * innerhalb der nach Name sortierten Teamliste der Liga (1., 2., 3.
     * Team alphabetisch usw.) - diese Funktion löste die Position dann zur
     * Laufzeit wieder in eine ID auf. Das Problem: ändert sich die Menge
     * oder Schreibweise der Teams in der Liga (z.B. durch einen Re-Import,
     * der ein Team mit leicht anderem Namen neu anlegt oder ein Team
     * entfernt/hinzufügt), verschiebt sich die alphabetische Position ALLER
     * danach folgenden Teams - dieselbe gespeicherte Zahl zeigte danach auf
     * ein völlig anderes Team, ohne dass sich am eigentlichen
     * favTeam/selTeam-Wert etwas geändert hatte.
     *
     * Jetzt speichert admin/view_liga_settings.php direkt die echte,
     * unveränderliche teams_global.id (kein Positions-Index mehr) - diese
     * Funktion prüft nur noch, ob diese ID gültig (Teil der Liga) ist,
     * löst aber nichts mehr auf. Bereits gespeicherte, alte Positions-Werte
     * werden einmalig migriert, siehe migrateFavSelTeamToStableId().
     */
    public static function resolveTeamNumberToId(int $ligaId, int $number) : ?int
    {
        if ($number <= 0) {
            return null;
        }
        static $cache = [];
        if (!array_key_exists($ligaId, $cache)) {
            try {
                $s = getDB()->prepare(
                    'SELECT g.id
                       FROM ' . tbl('teams_global') . ' g
                       JOIN ' . tbl('liga_teams') . ' lt ON lt.team_id = g.id
                      WHERE lt.liga_id = ?'
                );
                $s->execute([$ligaId]);
                $cache[$ligaId] = array_map('intval', $s->fetchAll(\PDO::FETCH_COLUMN));
            } catch (\Throwable) {
                $cache[$ligaId] = [];
            }
        }
        return in_array($number, $cache[$ligaId], true) ? $number : null;
    }

    /**
     * Einmalige Migration pro Liga (siehe ausführlicher
     * Kommentar bei resolveTeamNumberToId()): wandelt einen alten,
     * positionsbasierten favTeam/selTeam-Wert in die stabile teams_global.id
     * um, die diese Position AKTUELL (zum Migrationszeitpunkt, mit der
     * historischen "sortiert nach Name"-Logik) referenziert - danach wird
     * dieser Wert nie wieder als Position interpretiert. Nimmt $opts per
     * Referenz entgegen und schreibt die migrierten Werte direkt hinein,
     * damit der Aufrufer sie sofort mit der neuen Semantik weiterverwenden
     * kann, ohne die Liga-Optionen erneut zu laden. Eigenes Flag pro Liga
     * ("FavSelTeamMigrated") statt eines globalen Schalters - eine neu
     * angelegte Liga speichert von Anfang an echte IDs und braucht nie eine
     * Migration.
     */
    public static function migrateFavSelTeamToStableId(int $ligaId, array &$opts) : void
    {
        if (($opts['FavSelTeamMigrated'] ?? '0') === '1') {
            return;
        }
        try {
            $db = getDB();
            // Historische Sortierung (Position = Reihenfolge in dieser
            // Liste), exakt wie admin/view_liga_settings.php sie vor dieser
            // Änderung zum Speichern verwendet hat - nur so lässt sich ein
            // alter Positions-Wert korrekt in die damals gemeinte ID
            // übersetzen.
            $s = $db->prepare(
                'SELECT g.id
                   FROM ' . tbl('teams_global') . ' g
                   JOIN ' . tbl('liga_teams') . ' lt ON lt.team_id = g.id
                  WHERE lt.liga_id = ?
                  ORDER BY g.name'
            );
            $s->execute([$ligaId]);
            $orderedIds = array_map('intval', $s->fetchAll(\PDO::FETCH_COLUMN));

            $stmt = $db->prepare(
                'INSERT INTO ' . tbl('liga_options') . ' (liga_id, option_key, option_value)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
            );
            foreach (['favTeam', 'selTeam'] as $key) {
                $oldPosition = (int)($opts[$key] ?? 0);
                if ($oldPosition > 0 && isset($orderedIds[$oldPosition - 1])) {
                    $newId = $orderedIds[$oldPosition - 1];
                    $stmt->execute([$ligaId, $key, (string)$newId]);
                    $opts[$key] = (string)$newId;
                }
            }
            $stmt->execute([$ligaId, 'FavSelTeamMigrated', '1']);
            $opts['FavSelTeamMigrated'] = '1';
        } catch (\Throwable) {
            // Bei einem Fehler (z.B. fehlende DB-Rechte) bleibt das Flag
            // unbesetzt - die Migration wird beim nächsten Aufruf erneut
            // versucht, statt eine kaputte/leere Konvertierung dauerhaft
            // als "erledigt" zu markieren.
        }
    }
    /**
     * Ermittelt die Team-ID des Dummy-Platzhalter-Teams ("___"), falls vorhanden.
     * Wird beim Umsortieren des Turnierbaums ausgeschlossen, damit nicht mehrere
     * unabhängige Platzhalter-Paarungen fälschlich als "dieselbe" Zuführung
     * erkannt werden (der Dummy-Team-Datensatz wird für alle Platzhalter geteilt).
     */
    public static function getDummyTeamId() : int
    {
        static $id = null;
        if ($id !== null) {
            return $id;
        }
        try {
            $s = getDB()->prepare('SELECT id FROM ' . tbl('teams_global') . ' WHERE name=? LIMIT 1');
            $s->execute(['___']);
            $v = $s->fetchColumn();
            return $id = ($v !== false ? (int)$v : 0);
        } catch (\Throwable) {
            return $id = 0;
        }
    }
    /**
     * Wird pro Seitenaufruf oft mehrfach für dieselbe Liga aufgerufen (Tabelle,
     * Kreuztabelle, Spielplan-Sidebar, PDF-Export, Mini-Addons usw.) - Speicher-
     * Cache pro Liga-ID, damit die (teils zweistufige, siehe Fallback unten)
     * Abfrage innerhalb eines Requests nur einmal läuft.
     */
    public static function getLigaTeamsList(int $ligaId) : array
    {
        static $cache = [];
        if (array_key_exists($ligaId, $cache)) {
            return $cache[$ligaId];
        }
        return $cache[$ligaId] = self::getLigaTeamsListUncached($ligaId);
    }
    public static function getLigaTeamsListUncached(int $ligaId) : array
    {
        try {
            $s = getDB()->prepare(
                'SELECT tg.id, tg.name, tg.kurz, tg.mittel
                   FROM ' . tbl('liga_teams') . ' lt
                   JOIN ' . tbl('teams_global') . ' tg ON tg.id = lt.team_id
                  WHERE lt.liga_id = ?
                  ORDER BY lt.id'
            );
            $s->execute([$ligaId]);
            $rows = $s->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable) {
            // fällt durch zum Fallback unten
        }
    
        // Fallback: liga_teams ist (noch) leer – z.B. bei älteren importierten
        // Ligen, wo diese Zuordnungstabelle nie befüllt wurde. Teams stattdessen
        // direkt aus den vorhandenen Partien ableiten (Dummy-Team "___" ausschließen).
        try {
            $dummyId = self::getDummyTeamId();
            $s = getDB()->prepare(
                'SELECT DISTINCT tg.id, tg.name, tg.kurz, tg.mittel
                   FROM ' . tbl('liga_partien') . ' p
                   JOIN ' . tbl('liga_spieltage') . ' st ON st.id = p.spieltag_id
                   JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.heim_id
                  WHERE st.liga_id = ? AND tg.id <> ?
                  UNION
                 SELECT DISTINCT tg.id, tg.name, tg.kurz, tg.mittel
                   FROM ' . tbl('liga_partien') . ' p
                   JOIN ' . tbl('liga_spieltage') . ' st ON st.id = p.spieltag_id
                   JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.gast_id
                  WHERE st.liga_id = ? AND tg.id <> ?
                  ORDER BY name'
            );
            $s->execute([$ligaId, $dummyId, $ligaId, $dummyId]);
            return $s->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }
    /**
     * Ermittelt für einen Spieltag alle Teams der Liga, die an diesem Spieltag
     * KEINE Partie haben ("Spielfrei"). Kommt typischerweise bei ungerader
     * Teamzahl vor, kann aber auch bei gerader Teamzahl auftreten (z.B. wenn ein
     * Team im Spielplan schlicht nicht eingeteilt wurde). Ermittlung durch
     * Abwesenheit, genau wie im alten LMO: es gibt keinen expliziten
     * "Spielfrei"-Eintrag im Datenmodell, das betroffene Team taucht einfach in
     * keiner Paarung des Spieltags auf.
     *
     * @return array<int,array> Liste der betroffenen Teams (id,name,kurz,mittel)
     */
    public static function findSpielfreiTeams(int $ligaId, array $partien) : array
    {
        $scheduledIds = [];
        foreach ($partien as $p) {
            if (self::partieIsEmptyPlaceholder($p)) {
                continue; // "kein Spielplan"-Platzhalterpaarung zählt nicht als Termin
            }
            if ((int)($p['heim_id'] ?? 0) > 0) {
                $scheduledIds[(int)$p['heim_id']] = true;
            }
            if ((int)($p['gast_id'] ?? 0) > 0) {
                $scheduledIds[(int)$p['gast_id']] = true;
            }
        }
    
        $spielfrei = [];
        foreach (self::getLigaTeamsList($ligaId) as $team) {
            $tid  = (int)$team['id'];
            $name = trim((string)($team['name'] ?? ''));
            if (!isset($scheduledIds[$tid]) && $name !== '' && $name !== '___') {
                $spielfrei[] = $team;
            }
        }
        return $spielfrei;
    }
}
