<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/Eternal/EternalTableService.php
 * Fileversion: 1.2.0
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Ewige Tabelle + Mehrjahres-Vergleich (Teamvergleich über mehrere Jahre).
 *
 * Grundlage sind die vorhandenen Liga-Daten (lmonext_liga_partien /
 * lmonext_teams_global). Die eigentliche Tabellenberechnung je Liga
 * läuft über LigaService::computeStandings(), damit Punktewerte
 * (3/1/0, n.V., i.E.) und Status genau wie im normalen Ligabetrieb
 * behandelt werden. Dieser Service summiert nur die bereits berechneten
 * Liga-Zeilen über mehrere Ligen hinweg auf bzw. stellt sie pro Saison
 * als Matrix bereit.
 *
 * Eingebaut als eigenständige PSR-4-Klasse – bestehende Dateien bleiben
 * unangetastet.
 */
declare(strict_types=1);

namespace LMOnext\Liga\Eternal;

use LMOnext\Liga\LigaService;

final class EternalTableService
{
    public const POINTS_REAL = 0;     // historische Saisonwertung
    public const POINTS_3    = 1;     // alles 3/1/0
    public const POINTS_2    = 2;     // alles 2/1/0
    /**
     * Alle regulären Ligen (keine KO-Turniere), alphabetisch – Auswahl
     * für das Formular. Eine Liga gilt als regulär, wenn in liga_options
     * kein Type=1 gesetzt ist (alte Ligen ohne Type-Eintrag sind Liga=0).
     */
    public function allLeagues(): array
    {
        $db = getDB();
        $sql = 'SELECT l.id, l.name
                  FROM ' . tbl('liga') . ' l
                 WHERE NOT EXISTS (
                       SELECT 1 FROM ' . tbl('liga_options') . ' o
                        WHERE o.liga_id = l.id
                          AND o.option_key = \'Type\'
                          AND o.option_value = \'1\'
                 )
                 ORDER BY l.name';
        try {
            $rows = $db->query($sql)->fetchAll();
        } catch (\Throwable) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (int)$r['id'], 'name' => $r['name']];
        }
        return $out;
    }

    /**
     * Teams einer Liga (id/name/kurz) – Startmenge für computeStandings,
     * damit auch Teams ohne gespielte Partie als Null-Zeile erscheinen.
     */
    private function leagueTeams(int $ligaId): array
    {
        $db = getDB();
        $sql = 'SELECT g.id, g.name, g.kurz
                  FROM ' . tbl('teams_global') . ' g
                  JOIN ' . tbl('liga_teams') . ' lt ON lt.team_id = g.id
                 WHERE lt.liga_id = ?
                 ORDER BY g.name';
        try {
            $s = $db->prepare($sql);
            $s->execute([$ligaId]);
            return $s->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Alle Partien einer Liga inkl. Teamnamen – Rohdaten für
     * computeStandings (heim_id/gast_id/h_tore/g_tore/status/heim_name/gast_name).
     */
    private function leagueMatches(int $ligaId): array
    {
        $db = getDB();
        $sql = 'SELECT p.heim_id, p.gast_id, p.h_tore, p.g_tore, p.status,
                       gh.name AS heim_name, gg.name AS gast_name
                  FROM ' . tbl('liga_partien') . ' p
                  JOIN ' . tbl('liga_spieltage') . ' s ON s.id = p.spieltag_id
             LEFT JOIN ' . tbl('teams_global') . ' gh ON gh.id = p.heim_id
             LEFT JOIN ' . tbl('teams_global') . ' gg ON gg.id = p.gast_id
                 WHERE s.liga_id = ?';
        try {
            $s = $db->prepare($sql);
            $s->execute([$ligaId]);
            return $s->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Berechnet die Liga-Tabelle für eine einzelne Liga (Saison) über
     * die vorhandene LigaService-Logik. Gibt die sortierten Zeilen mit
     * Rang (1-basiert) zurück.
     */
    public function leagueStandings(int $ligaId): array
    {
        $teams = $this->leagueTeams($ligaId);
        if (empty($teams)) {
            return [];
        }

        $matches = $this->leagueMatches($ligaId);
        $opts    = LigaService::getLigaOptions($ligaId);

        $rows = LigaService::computeStandings($teams, $matches, $opts, $ligaId);

        foreach ($rows as $i => &$r) {
            $r['rang'] = $i + 1;
            $r['diff'] = (int)$r['tore_h'] - (int)$r['tore_g'];
        }
        unset($r);

        return $rows;
    }
    /**
     * Löst Team-Verknüpfungen (Umbenennung/Fusion/Abspaltung, siehe
     * admin/bootstrap.php addTeamLink()/team_links) für eine Menge von
     * Team-IDs auf. Liefert für jede übergebene ID die "kanonische"
     * (aktuelle/heutige) Team-ID, unter der sie in der Ewigen Tabelle
     * zusammengefasst werden soll, sowie den Namen jeder kanonischen ID.
     *
     * Auflösung nach demselben Prinzip wie
     * HeadToHead::resolveLinkedTeamIds()/resolveCanonicalTeamId() im
     * teamvergleich-Addon (dort für den Direktvergleich zweier Teams),
     * hier unabhängig implementiert und für beliebig viele Teams
     * gleichzeitig - team_links ist eine reine Core-Tabelle, es besteht
     * bewusst keine Abhängigkeit zwischen den beiden Standalone-Addons:
     *   1. Transitive Gruppierung über alle team_links-Kanten (BFS) -
     *      auch Ketten wie A->B->C werden zu einer Gruppe zusammengefasst.
     *   2. Innerhalb jeder Gruppe die "wird abgelöst durch"-Kette
     *      (newer_team_id) auflösen. Führen alle Ketten eindeutig zum
     *      selben Ziel, ist DAS der kanonische Name.
     *   3. Ist keine (eindeutige) Richtung hinterlegt (newer_team_id
     *      NULL oder Kette uneindeutig/zyklisch), Fallback: das Team mit
     *      der höchsten Team-ID der Gruppe gilt als kanonisch - neu
     *      angelegte Teams haben in aller Regel die höhere ID, das trifft
     *      in der Praxis meist den aktuelleren Namen, ohne eine weitere
     *      Datenbankabfrage (z.B. nach dem jüngsten Spiel) zu benötigen.
     *
     * Nur für die übergebenen Team-IDs relevante Verknüpfungen werden
     * aufgelöst (nicht das gesamte System) - team_links selbst wird
     * einmalig komplett geladen (i.d.R. eine kleine, nur vom Admin
     * gepflegte Tabelle), die BFS-Traversierung bleibt aber auf die
     * übergebene Menge beschränkt.
     *
     * @param int[] $teamIds
     * @return array{canonical: array<int,int>, names: array<int,string>}
     */
    private function resolveTeamLinkGroups(array $teamIds): array
    {
        $canonical = [];
        $names     = [];
        if (empty($teamIds)) {
            return ['canonical' => $canonical, 'names' => $names];
        }

        try {
            $edges = getDB()->query(
                'SELECT team_a_id, team_b_id, newer_team_id FROM ' . tbl('team_links')
            )->fetchAll();
        } catch (\Throwable) {
            $edges = [];
        }

        // Adjazenzliste + "wird abgelöst durch"-Kette aus ALLEN Kanten
        // (nicht nur den für $teamIds relevanten) - eine Gruppe kann auch
        // Teams enthalten, die selbst nicht in $teamIds vorkommen (z.B.
        // ein Zwischenglied einer Umbenennungskette ohne eigene Saison in
        // den ausgewählten Ligen).
        $adj = [];
        $supersededBy = [];
        foreach ($edges as $e) {
            $a = (int)$e['team_a_id'];
            $b = (int)$e['team_b_id'];
            $adj[$a][] = $b;
            $adj[$b][] = $a;
            $newer = $e['newer_team_id'] !== null ? (int)$e['newer_team_id'] : null;
            if ($newer !== null) {
                $other = ($a === $newer) ? $b : $a;
                $supersededBy[$other] = $newer;
            }
        }

        $groupOf = []; // teamId => niedrigste ID der Gruppe (Gruppen-Schlüssel)
        foreach ($teamIds as $start) {
            $start = (int)$start;
            if (isset($groupOf[$start])) {
                continue; // schon einer Gruppe zugeordnet
            }
            // BFS über die vollständige Adjazenzliste (auch über Teams
            // hinweg, die nicht in $teamIds liegen).
            $visited = [$start => true];
            $queue   = [$start];
            while ($queue !== []) {
                $current = array_shift($queue);
                foreach ($adj[$current] ?? [] as $next) {
                    if (!isset($visited[$next])) {
                        $visited[$next] = true;
                        $queue[] = $next;
                    }
                }
            }
            $groupIds = array_keys($visited);

            // Kanonische ID der Gruppe bestimmen.
            $canonicalId = null;
            if (count($groupIds) > 1) {
                $terminals = [];
                foreach ($groupIds as $gid) {
                    $current = $gid;
                    $seen = [$current => true];
                    while (isset($supersededBy[$current])) {
                        $current = $supersededBy[$current];
                        if (isset($seen[$current])) { break; } // Zyklus-Schutz
                        $seen[$current] = true;
                    }
                    $terminals[$current] = true;
                }
                if (count($terminals) === 1) {
                    $canonicalId = array_key_first($terminals);
                } else {
                    $canonicalId = max($groupIds); // Fallback: höchste ID
                }
            } else {
                $canonicalId = $start; // keine Verknüpfung vorhanden
            }

            foreach ($groupIds as $gid) {
                $groupOf[$gid] = $canonicalId;
            }
        }

        foreach ($teamIds as $tid) {
            $tid = (int)$tid;
            $canonical[$tid] = $groupOf[$tid] ?? $tid;
        }

        // Namen der kanonischen IDs nachladen (nur die tatsächlich
        // benötigten - falls die kanonische ID selbst nicht in $teamIds
        // vorkam, z.B. weil sie in keiner der ausgewählten Ligen spielte).
        $canonicalIds = array_values(array_unique(array_values($canonical)));
        if (!empty($canonicalIds)) {
            try {
                $ph = implode(',', array_fill(0, count($canonicalIds), '?'));
                $s = getDB()->prepare(
                    'SELECT id, name FROM ' . tbl('teams_global') . ' WHERE id IN (' . $ph . ')'
                );
                $s->execute($canonicalIds);
                foreach ($s->fetchAll() as $row) {
                    $names[(int)$row['id']] = $row['name'];
                }
            } catch (\Throwable) {
                // $names bleibt für nicht geladene IDs leer - Aufrufer
                // fällt in diesem Fall auf den Namen aus den bereits
                // vorhandenen Saison-Zeilen zurück.
            }
        }

        return ['canonical' => $canonical, 'names' => $names];
    }

    /**
     * Ewige Tabelle: summiert Sp/S/U/N/Tore/Punkte je globalem Team
     * über alle ausgewählten Ligen. Sortierung wie bei computeStandings
     * (Punkte → Tordifferenz → geschossene Tore → Name).
     *
     * Team-Verknüpfungen (siehe resolveTeamLinkGroups()) werden dabei
     * automatisch berücksichtigt: verknüpfte Teams (Umbenennung, Fusion,
     * Abspaltung) werden unter der kanonischen (aktuellen) Team-ID
     * zusammengefasst, ihre unter anderem Namen gespielten Saisons zählen
     * mit. Das Feld 'former_names' enthält die Namen, unter denen dieselbe
     * kanonische Gruppe in den ausgewählten Ligen sonst noch aufgetreten
     * ist (leer, wenn keine Verknüpfung vorliegt) - die Darstellung
     * (z.B. "AktuellerName (ehem. AlterName)") obliegt bewusst dem
     * aufrufenden Addon, nicht diesem Core-Service, der keine eigenen
     * Sprachschlüssel besitzt.
     *
     * @param int[] $ligaIds
     */
    public function eternalStandings(array $ligaIds): array
    {
        // Erst alle Saison-Zeilen roh sammeln (wie bisher) - die
        // Verknüpfungsauflösung braucht die vollständige Menge der
        // tatsächlich vorkommenden Team-IDs, bevor sie einmalig läuft.
        $rawBySeasonTeam = [];
        $allTeamIds = [];
        foreach ($ligaIds as $lid) {
            foreach ($this->leagueStandings((int)$lid) as $r) {
                $id = (int)$r['id'];
                $rawBySeasonTeam[(int)$lid][$id] = $r;
                $allTeamIds[$id] = true;
            }
        }

        $linkInfo       = $this->resolveTeamLinkGroups(array_keys($allTeamIds));
        $canonicalOf    = $linkInfo['canonical'];
        $canonicalNames = $linkInfo['names'];

        $agg = [];
        $formerNamesOf = []; // canonicalId => [ehemaligerName => true]

        foreach ($rawBySeasonTeam as $lid => $teamsInSeason) {
            foreach ($teamsInSeason as $origId => $r) {

                $id = $canonicalOf[$origId] ?? $origId;

                if (!isset($agg[$id])) {
                    $agg[$id] = [
                        'id'      => $id,
                        'name'    => $canonicalNames[$id] ?? $r['name'],
                        'kurz'    => $r['kurz'] ?? '',
                        'saisons' => 0,
                        'sp'      => 0,
                        's'       => 0,
                        'u'       => 0,
                        'n'       => 0,
                        'tore_h'  => 0,
                        'tore_g'  => 0,
                        // drei Punktesysteme
                        'pkt'     => 0, // historisch
                        'pkt2'    => 0, // immer 2 Punkte
                        'pkt3'    => 0, // immer 3 Punkte
                        'mpkt2'    => 0,   // Minuspunkte (2-Punkte-System)
                        'mpkt3'    => 0,   // Minuspunkte (3-Punkte-System)
                        // Strafen über Saisons aufsummiert (Beitrag: Torsten
                        // Hofmann) - "pkt"/"tore_h"/"tore_g" oben enthalten die
                        // Korrekturen bereits (computeStandings() wendet sie VOR
                        // der Aggregation an), diese Felder dienen nur der
                        // Anzeige/Fußnote (siehe ewigeStrafHinweis() im
                        // Ewige-Tabelle-Addon)
                        'strafpunkte'     => 0,
                        'straftore'       => 0,
                        'torekorrektur'   => 0,
                        'minuspunktekorrektur' => 0,
                        'strafgruende'    => [],  // ['Liganame: Grund', ...]
                        'former_names'    => [],  // siehe Docblock oben
                    ];
                }

                if ($origId !== $id && $r['name'] !== $agg[$id]['name']) {
                    $formerNamesOf[$id][$r['name']] = true;
                }

                $agg[$id]['saisons']++;
                $agg[$id]['sp']     += (int)$r['sp'];
                $agg[$id]['s']      += (int)$r['s'];
                $agg[$id]['u']      += (int)$r['u'];
                $agg[$id]['n']      += (int)$r['n'];
                $agg[$id]['tore_h'] += (int)$r['tore_h'];
                $agg[$id]['tore_g'] += (int)$r['tore_g'];
                // Historische Punkte (wie berechnet)
                $agg[$id]['pkt'] += (int)$r['pkt'];
                // Immer 2 Punkte
                $agg[$id]['pkt2'] += $r['s'] * 2 + $r['u'];
                $agg[$id]['mpkt2'] += $r['n'] * 2 + $r['u'];
                // Immer 3 Punkte
                $agg[$id]['pkt3'] += $r['s'] * 3 + $r['u'];
                $agg[$id]['mpkt3'] += $r['n'] * 3 + $r['u'];

                // Strafen aufsummieren (Beitrag: Torsten Hofmann)
                $agg[$id]['strafpunkte']          += (int)($r['strafpunkte'] ?? 0);
                $agg[$id]['straftore']            += (int)($r['straftore'] ?? 0);
                $agg[$id]['torekorrektur']        += (int)($r['torekorrektur'] ?? 0);
                $agg[$id]['minuspunktekorrektur'] += (int)($r['minuspunktekorrektur'] ?? 0);
                $grund = trim((string)($r['strafgrund'] ?? ''));
                if ($grund !== '') {
                    $ligaName = LigaService::getLigaById((int)$lid)['name'] ?? ('Liga ' . $lid);
                    $agg[$id]['strafgruende'][] = $ligaName . ': ' . $grund;
                }
            }
        }

        foreach ($agg as $id => &$row) {
            if (!empty($formerNamesOf[$id])) {
                $row['former_names'] = array_keys($formerNamesOf[$id]);
            }
        }
        unset($row);

        $rows = array_values($agg);

        // Standardsortierung = historische Punkte
        usort($rows, static function (array $a, array $b): int {

            if ($a['pkt'] !== $b['pkt']) {
                return $b['pkt'] <=> $a['pkt'];
            }

            $da = $a['tore_h'] - $a['tore_g'];
            $db = $b['tore_h'] - $b['tore_g'];

            if ($da !== $db) {
                return $db <=> $da;
            }

            if ($a['tore_h'] !== $b['tore_h']) {
                return $b['tore_h'] <=> $a['tore_h'];
            }

            return strcmp($a['name'], $b['name']);
        });

        foreach ($rows as $i => &$r) {
            $r['rang'] = $i + 1;
            $r['diff'] = $r['tore_h'] - $r['tore_g'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Mehrjahres-Vergleich: pro Liga (Saison) Rang + Punkte je Team.
     * Liefert ein assoziatives Array
     *   ['seasons' => [ligaId => ligaName], 'teams' => [teamId => name], 'matrix' => [teamId => [ligaId => ['rang'=>..,'pkt'=>..]]]]
     *
     * Team-Verknüpfungen werden wie bei eternalStandings() berücksichtigt
     * (siehe resolveTeamLinkGroups()) - ein umbenanntes/fusioniertes Team
     * erscheint als EINE Zeile unter seiner kanonischen ID, mit den
     * Saison-Zellen aller verknüpften Namen darin.
     *
     * @param int[] $ligaIds  Reihenfolge = Spaltenreihenfolge (chronologisch)
     */
    public function seasonMatrix(array $ligaIds): array
    {
        $seasons = [];
        $rawByLiga = []; // ligaId => [teamId => Zeile]
        $allTeamIds = [];

        foreach ($ligaIds as $lid) {
            $lid = (int)$lid;
            $info = LigaService::getLigaById($lid);
            $seasons[$lid] = $info['name'] ?? ('Liga ' . $lid);
            foreach ($this->leagueStandings($lid) as $r) {
                $tid = (int)$r['id'];
                $rawByLiga[$lid][$tid] = $r;
                $allTeamIds[$tid] = true;
            }
        }

        $linkInfo       = $this->resolveTeamLinkGroups(array_keys($allTeamIds));
        $canonicalOf    = $linkInfo['canonical'];
        $canonicalNames = $linkInfo['names'];

        $teams  = [];
        $matrix = [];
        foreach ($rawByLiga as $lid => $teamsInSeason) {
            foreach ($teamsInSeason as $origId => $r) {
                $tid = $canonicalOf[$origId] ?? $origId;
                $teams[$tid] = $canonicalNames[$tid] ?? $r['name'];
                $matrix[$tid][$lid] = ['rang' => (int)$r['rang'], 'pkt' => (int)$r['pkt']];
            }
        }

        // Teams sortiert nach Name
        asort($teams);
        return ['seasons' => $seasons, 'teams' => $teams, 'matrix' => $matrix];
    }
}
