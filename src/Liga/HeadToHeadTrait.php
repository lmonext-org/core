<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/HeadToHeadTrait.php
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
trait HeadToHeadTrait
{
    /**
     * Löst ein Team transitiv zu seiner vollständigen "verknüpften Gruppe" auf
     * (siehe admin/bootstrap.php: team_links, Umbenennung/Fusion/Abspaltung).
     * Bei Ketten (A↔B, B↔C) gehören beim Vergleich automatisch alle drei
     * zusammen. Liest die Tabelle read-only – falls sie (noch) nicht existiert
     * (Team-Verknüpfung wurde bisher nie genutzt), liefert die Funktion einfach
     * nur das Team selbst zurück, kein Fehler.
     *
     * @return array<int,int> Team-IDs inkl. des übergebenen Teams selbst
     */
    public static function resolveLinkedTeamIds(int $teamId) : array
    {
        try {
            $edges = getDB()->query('SELECT team_a_id, team_b_id FROM ' . tbl('team_links'))->fetchAll();
        } catch (\Throwable) {
            return [$teamId];
        }
    
        $adj = [];
        foreach ($edges as $e) {
            $a = (int)$e['team_a_id'];
            $b = (int)$e['team_b_id'];
            $adj[$a][] = $b;
            $adj[$b][] = $a;
        }
    
        $visited = [$teamId => true];
        $queue = [$teamId];
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($adj[$current] ?? [] as $next) {
                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $queue[] = $next;
                }
            }
        }
        return array_keys($visited);
    }
    /**
     * Ermittelt den fest hinterlegten "heutigen" Namen einer verknüpften Gruppe
     * (siehe team_links.newer_team_id, admin/bootstrap.php addTeamLink()/
     * setTeamLinkDirection()) – unabhängig davon, von welchem Spiel/welcher Liga
     * aus der Vergleich gerade geöffnet wurde. Löst dazu die "wird abgelöst
     * durch"-Kette über evtl. mehrere Verknüpfungen hinweg auf (z.B. A wurde zu B
     * umbenannt, B später zu C: A und B zeigen dann beide auf C).
     *
     * Liefert null, wenn für die Gruppe keine (eindeutige) Richtung hinterlegt
     * ist – dann fällt getHeadToHeadMatches() auf das alte, kontextabhängige
     * Verhalten zurück (Anker = das im aktuellen Vergleich angeklickte Team).
     */
    public static function resolveCanonicalTeamId(array $groupIds) : ?int
    {
        if (count($groupIds) < 2) {
            return null;
        }
        try {
            $ph = implode(',', array_fill(0, count($groupIds), '?'));
            $s = getDB()->prepare(
                'SELECT team_a_id, team_b_id, newer_team_id FROM ' . tbl('team_links') . '
                  WHERE newer_team_id IS NOT NULL
                    AND (team_a_id IN (' . $ph . ') OR team_b_id IN (' . $ph . '))'
            );
            $s->execute([...$groupIds, ...$groupIds]);
            $edges = $s->fetchAll();
        } catch (\Throwable) {
            return null;
        }
        if ($edges === []) {
            return null; // keine Richtung für diese Gruppe hinterlegt
        }
    
        // "wird abgelöst durch"-Kette: jede Kante mit gesetztem newer_team_id
        // sagt "die andere Seite wird abgelöst durch newer_team_id"
        $supersededBy = [];
        foreach ($edges as $e) {
            $newer = (int)$e['newer_team_id'];
            $other = ((int)$e['team_a_id'] === $newer) ? (int)$e['team_b_id'] : (int)$e['team_a_id'];
            $supersededBy[$other] = $newer;
        }
    
        // Von jedem Team der Gruppe aus die Kette bis zum Ende verfolgen. Führen
        // alle Ketten zum selben Ziel, ist das der eindeutige kanonische Name.
        // Laufen sie auseinander (z.B. bei einer Abspaltung mit widersprüchlichen
        // Angaben) oder gibt es gar keine ableitbare Endstation, bleibt es
        // uneindeutig -> null (alter Anker-Fallback greift dann).
        $terminals = [];
        foreach ($groupIds as $start) {
            $current = $start;
            $seen = [$current => true];
            while (isset($supersededBy[$current])) {
                $current = $supersededBy[$current];
                if (isset($seen[$current])) { break; } // Zyklus-Schutz, sollte nicht vorkommen
                $seen[$current] = true;
            }
            $terminals[$current] = true;
        }
    
        return count($terminals) === 1 ? array_key_first($terminals) : null;
    }
    public static function getHeadToHeadMatches(int $idA, int $idB) : array
    {
        static $cache = [];
        $key = min($idA, $idB) . '_' . max($idA, $idB);
        if (isset($cache[$key])) {
            return $cache[$key];
        }
    
        // Beide Teams zu ihrer vollständigen verknüpften Gruppe auflösen (z.B.
        // ein umbenannter/fusionierter/abgespaltener Verein), damit Spiele unter
        // früheren Namen im Vergleich mit auftauchen. $idA/$idB bleiben die
        // "heutigen" Anker-IDs für die "(heute TEAM_HEUTE)"-Kennzeichnung unten.
        $groupA = self::resolveLinkedTeamIds($idA);
        $groupB = self::resolveLinkedTeamIds($idB);
    
        try {
            $phA = implode(',', array_fill(0, count($groupA), '?'));
            $phB = implode(',', array_fill(0, count($groupB), '?'));
            $s = getDB()->prepare(
                'SELECT p.heim_id, p.gast_id, p.h_tore, p.g_tore, p.status,
                        COALESCE(p.zeit, s.start) AS zeit,
                        s.id AS spieltag_db_id, s.nummer AS spieltag_nummer, s.liga_id AS liga_id, l.name AS liga_name
                   FROM ' . tbl('liga_partien') . ' p
                   JOIN ' . tbl('liga_spieltage') . ' s ON s.id = p.spieltag_id
                   JOIN ' . tbl('liga') . ' l ON l.id = s.liga_id
                  WHERE ((p.heim_id IN (' . $phA . ') AND p.gast_id IN (' . $phB . '))
                      OR (p.heim_id IN (' . $phB . ') AND p.gast_id IN (' . $phA . ')))
                    AND p.h_tore IS NOT NULL AND p.g_tore IS NOT NULL
                  ORDER BY COALESCE(p.zeit, s.start) DESC'
            );
            $s->execute([...$groupA, ...$groupB, ...$groupB, ...$groupA]);
            $rows = $s->fetchAll();
        } catch (\Throwable) {
            $rows = [];
        }
    
        // Namen für ALLE Teams in beiden Gruppen (nicht nur $idA/$idB), damit
        // auch Spiele unter früheren Namen den richtigen (historischen) Namen
        // zeigen. $anchorOf ordnet jede Team-ID dem "heutigen" Namen zu, damit
        // sich pro Zeile ermitteln lässt, ob eine "(heute TEAM_HEUTE)"-
        // Kennzeichnung nötig ist. Bevorzugt wird die FEST hinterlegte Richtung
        // (team_links.newer_team_id, siehe resolveCanonicalTeamId()) verwendet,
        // damit der "heutige" Name unabhängig vom Aufrufkontext immer derselbe
        // ist – nur wenn für eine Gruppe keine (eindeutige) Richtung hinterlegt
        // ist, fällt es auf das alte Verhalten zurück (Anker = das im aktuellen
        // Vergleich angeklickte Team, $idA/$idB).
        $allIds = array_values(array_unique([...$groupA, ...$groupB]));
        $names = [];
        $anchorOf = [];
        $canonicalA = self::resolveCanonicalTeamId($groupA) ?? $idA;
        $canonicalB = self::resolveCanonicalTeamId($groupB) ?? $idB;
        foreach ($groupA as $tid) { $anchorOf[$tid] = $canonicalA; }
        foreach ($groupB as $tid) { $anchorOf[$tid] = $canonicalB; }
        try {
            $ph3 = implode(',', array_fill(0, count($allIds), '?'));
            $s2 = getDB()->prepare('SELECT id, name FROM ' . tbl('teams_global') . ' WHERE id IN (' . $ph3 . ')');
            $s2->execute($allIds);
            foreach ($s2->fetchAll() as $r) {
                $names[(int)$r['id']] = $r['name'];
            }
        } catch (\Throwable) {
            // $names bleibt leer; Fallback "?" beim Aufbau unten
        }
    
        $displayName = static function (int $teamId) use ($names, $anchorOf) : array {
            $name = $names[$teamId] ?? '?';
            $anchor = $anchorOf[$teamId] ?? $teamId;
            $today = ($anchor !== $teamId && isset($names[$anchor])) ? $names[$anchor] : null;
            return ['name' => $name, 'today' => $today];
        };
    
        $matches = [];
        foreach ($rows as $r) {
            $hId = (int)$r['heim_id'];
            $gId = (int)$r['gast_id'];
            $heimInfo = $displayName($hId);
            $gastInfo = $displayName($gId);
            $matches[] = [
                'heim_id'         => $hId,
                'gast_id'         => $gId,
                'heim_name'       => $heimInfo['name'],
                'heim_today'      => $heimInfo['today'],
                'gast_name'       => $gastInfo['name'],
                'gast_today'      => $gastInfo['today'],
                'h_tore'          => (int)$r['h_tore'],
                'g_tore'          => (int)$r['g_tore'],
                'status'          => (int)($r['status'] ?? 0),
                'zeit'            => $r['zeit'],
                'spieltag'        => (int)$r['spieltag_nummer'],
                'spieltag_db_id'  => (int)$r['spieltag_db_id'],
                'liga_id'         => (int)$r['liga_id'],
                'liga_name'       => $r['liga_name'],
            ];
        }
    
        // ── Rundenname pro Begegnung ermitteln ────────────────────────────────────
        // Bei KO-Ligen soll die passende Turnierrunde stehen (z.B. "Achtelfinale",
        // "Halbfinale", "Finale") statt "N. Spieltag" – dafür braucht es je Liga
        // den Typ (KO?) + die Gesamtrundenzahl, und je betroffenem Spieltag die
        // Anzahl Paarungen (bestimmt z.B. Achtel- vs. Viertelfinale). Bei
        // regulären Ligen werden Lang- UND Kurzform mitgegeben, damit die
        // Anzeige responsiv (Web/Mobil) umschalten kann (siehe h2h-Match-Meta im
        // <script>-Block weiter unten).
        $ligaIds = array_values(array_unique(array_column($matches, 'liga_id')));
        $ligaMeta = [];
        if (!empty($ligaIds)) {
            $ph = implode(',', array_fill(0, count($ligaIds), '?'));
            try {
                $db = getDB();
                $sType = $db->prepare('SELECT liga_id, option_value FROM ' . tbl('liga_options') . ' WHERE liga_id IN (' . $ph . ') AND option_key=\'Type\'');
                $sType->execute($ligaIds);
                foreach ($sType->fetchAll() as $r) {
                    $ligaMeta[(int)$r['liga_id']]['isKO'] = ((string)$r['option_value'] === '1');
                }
                $sMax = $db->prepare('SELECT liga_id, COUNT(*) AS c FROM ' . tbl('liga_spieltage') . ' WHERE liga_id IN (' . $ph . ') GROUP BY liga_id');
                $sMax->execute($ligaIds);
                foreach ($sMax->fetchAll() as $r) {
                    $ligaMeta[(int)$r['liga_id']]['maxNr'] = (int)$r['c'];
                }
            } catch (\Throwable) {
                // $ligaMeta bleibt (teilweise) leer -> Fallback unten greift
            }
        }
    
        $pairingCounts = [];
        $spieltagIds = array_values(array_unique(array_column($matches, 'spieltag_db_id')));
        if (!empty($spieltagIds)) {
            try {
                $ph2 = implode(',', array_fill(0, count($spieltagIds), '?'));
                $sPair = getDB()->prepare('SELECT spieltag_id, spiel_nr FROM ' . tbl('liga_partien') . ' WHERE spieltag_id IN (' . $ph2 . ')');
                $sPair->execute($spieltagIds);
                $seen = [];
                foreach ($sPair->fetchAll() as $r) {
                    $prefix = explode('_', (string)$r['spiel_nr'])[0];
                    $seen[(int)$r['spieltag_id']][$prefix] = true;
                }
                foreach ($seen as $stid => $prefixes) {
                    $pairingCounts[$stid] = count($prefixes);
                }
            } catch (\Throwable) {
                // $pairingCounts bleibt leer -> koRoundName() faellt auf "Runde N" zurueck
            }
        }
    
        foreach ($matches as &$m) {
            $isKO = $ligaMeta[$m['liga_id']]['isKO'] ?? false;
            if ($isKO) {
                $maxNr = $ligaMeta[$m['liga_id']]['maxNr'] ?? $m['spieltag'];
                $pairingCount = $pairingCounts[$m['spieltag_db_id']] ?? 0;
                $label = self::roundDisplayName(['nummer' => $m['spieltag'], 'pairing_count' => $pairingCount], true, $maxNr);
                $m['runde_label']       = $label;
                $m['runde_label_short'] = $label; // KO-Rundennamen brauchen keine eigene Kurzform
            } else {
                $m['runde_label']       = $m['spieltag'] . '. ' . tf('liga_col_spieltag_long');
                $m['runde_label_short'] = $m['spieltag'] . '. ' . tf('liga_col_spieltag_short');
            }
        }
        unset($m);
    
        return $cache[$key] = $matches;
    }
    /**
     * Baut das JSON-Payload (für das data-h2h-Attribut des Vergleichs-Icons) aus
     * Sicht von $idA (links, i.d.R. das Heimteam der aufrufenden Zeile) gegen
     * $idB (rechts).
     */
    public static function buildHeadToHeadPayload(int $idA, int $idB, string $nameA, string $nameB, bool $showLogos = false) : string
    {
        $matches = self::getHeadToHeadMatches($idA, $idB);
        $winsA = 0;
        $winsB = 0;
        $draws = 0;
        foreach ($matches as $m) {
            if ($m['h_tore'] === $m['g_tore']) {
                $draws++;
            } elseif (($m['heim_id'] === $idA && $m['h_tore'] > $m['g_tore'])
                || ($m['gast_id'] === $idA && $m['g_tore'] > $m['h_tore'])) {
                $winsA++;
            } else {
                $winsB++;
            }
        }
    
        $payload = [
            'teamAId'   => $idA,
            'teamBId'   => $idB,
            'teamAName' => $nameA,
            'teamBName' => $nameB,
            'teamALogo' => $showLogos ? (self::findTeamLogoPathFrontend($idA) ?? 'assets/img/nopic-team.svg') : null,
            'teamBLogo' => $showLogos ? (self::findTeamLogoPathFrontend($idB) ?? 'assets/img/nopic-team.svg') : null,
            'winsA'     => $winsA,
            'draws'     => $draws,
            'winsB'     => $winsB,
            'matches'   => array_map(static function (array $m) : array {
                $datum = '–';
                if (!empty($m['zeit'])) {
                    try {
                        $datum = (new \DateTime($m['zeit']))->format('d.m.Y');
                    } catch (\Throwable) {
                        // $datum bleibt '–'
                    }
                }
                return [
                    'datum'          => $datum,
                    'spieltag'       => $m['spieltag'],
                    'rundeLabel'     => $m['runde_label'],
                    'rundeLabelKurz' => $m['runde_label_short'],
                    'ligaId'         => $m['liga_id'],
                    'liga'           => $m['liga_name'],
                    'heim'           => $m['heim_name'],
                    'heimToday'      => $m['heim_today'],
                    'gast'           => $m['gast_name'],
                    'gastToday'      => $m['gast_today'],
                    'hTore'          => $m['h_tore'],
                    'gTore'          => $m['g_tore'],
                    'suffix'         => self::statusSuffix($m),
                ];
            }, $matches),
        ];
    
        return str_replace('</script>', '<\/script>', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
    /**
     * Baut das anklickbare Vergleichs-Icon für eine Ergebnis-/Spielplanzeile.
     * Liefert einen leeren String, wenn eine der beiden Team-IDs kein echtes Team
     * ist (z.B. KO-Platzhalter "___" ohne heim_id/gast_id).
     */
    public static function renderH2hIcon(int $heimId, int $gastId, string $heimName, string $gastName, bool $showLogos = false) : string
    {
        if ($heimId <= 0 || $gastId <= 0) {
            return '';
        }
        $payload = self::buildHeadToHeadPayload($heimId, $gastId, $heimName, $gastName, $showLogos);
    
        return '<button type="button" class="h2h-icon" title="' . h(tf('liga_h2h_icon_title')) . '" data-h2h="' . h($payload) . '">'
            . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<line x1="2" y1="7" x2="11" y2="7"/><polyline points="7,2 13,7 7,12"/>'
            . '<line x1="22" y1="17" x2="13" y2="17"/><polyline points="17,12 11,17 17,22"/>'
            . '</svg></button>';
    }
    /**
     * Emittiert das Vergleichs-Modal-Grundgerüst (Overlay-<div> + CSS-freies,
     * reines JS zum Öffnen/Befüllen/Schließen) genau einmal pro Request – auch
     * wenn mehrere Ergebnistabellen (z.B. gruppierte KO-Runden) auf einer Seite
     * gerendert werden. CSS dafür liegt in layout.tpl.php (.h2h-*).
     */
    public static function renderH2hModalAssets() : string
    {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;
        // Zusätzliche Prüfung auf das pdf-export-Addon (Beitrag: Auslagerung
        // als eigenständiges Addon, siehe CHANGELOG.md und die identische
        // Ergänzung in frontend/data_liga_pretraits.php - beide Implementierungen
        // enthalten dieselbe H2H-Modal-Logik, siehe dortiger Kommentar zur
        // Code-Duplikation).
        $showPdfButtons = function_exists('addonManager') && addonManager()->isEnabled('pdf-export')
            && getAdminSetting('show_pdf_buttons', '1') === '1';
    
        $html  = '<div class="h2h-overlay" id="h2h-overlay" hidden>';
        $html .= '<div class="h2h-modal" role="dialog" aria-modal="true">';
        $html .= '<button type="button" class="h2h-close" id="h2h-close" aria-label="' . h(tf('liga_h2h_close')) . '">&times;</button>';
        $html .= '<h3 class="h2h-title" id="h2h-title"></h3>';
        $html .= '<div class="h2h-record" id="h2h-record"></div>';
        $html .= '<div class="h2h-list" id="h2h-list"></div>';
        if ($showPdfButtons) {
            $html .= '<div class="pdf-export-row"><a class="btn-pdf-export" id="h2h-pdf-link" href="#" title="' . h(tf('liga_pdf_export_button')) . '">'
                . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . '<rect x="7" y="3" width="13" height="16" rx="2"/><path d="M4 7v13a2 2 0 0 0 2 2h11"/>'
                . '</svg>'
                . 'PDF</a></div>';
        }
        $html .= '</div></div>';
    
        $html .= '<script>(function(){'
            . 'var overlay=document.getElementById("h2h-overlay");'
            . 'var titleEl=document.getElementById("h2h-title");'
            . 'var recordEl=document.getElementById("h2h-record");'
            . 'var listEl=document.getElementById("h2h-list");'
            . 'var pdfLinkEl=document.getElementById("h2h-pdf-link");'
            . 'var drawLabel=' . json_encode(tf('liga_h2h_draw'), JSON_UNESCAPED_UNICODE) . ';'
            . 'var todayLabel=' . json_encode(tf('h2h_today_prefix'), JSON_UNESCAPED_UNICODE) . ';'
            . 'var winsLabelTpl=' . json_encode(tf('liga_h2h_wins'), JSON_UNESCAPED_UNICODE) . ';'
            . 'var noMatchesLabel=' . json_encode(tf('liga_h2h_no_matches'), JSON_UNESCAPED_UNICODE) . ';'
            . 'var titleTpl=' . json_encode(tf('liga_h2h_modal_title'), JSON_UNESCAPED_UNICODE) . ';'
            . 'function esc(s){var d=document.createElement("div");d.textContent=s;return d.innerHTML;}'
            . 'function winsLabel(team){return winsLabelTpl.replace("{team}",esc(team));}'
            . 'function open(data){'
            . 'var teamALabel=esc(data.teamAName)+(data.teamALogo?\'<img src="\'+esc(data.teamALogo)+\'" alt="" class="team-logo-inline">\':\'\');'
            . 'var teamBLabel=(data.teamBLogo?\'<img src="\'+esc(data.teamBLogo)+\'" alt="" class="team-logo-inline">\':\'\')+esc(data.teamBName);'
            . 'titleEl.innerHTML=titleTpl.replace("{heim}",teamALabel).replace("{gast}",teamBLabel);'
            . 'if(pdfLinkEl){pdfLinkEl.href="liga.php?h2h_pdf=1&a="+data.teamAId+"&b="+data.teamBId+(data.teamALogo?"&logos=1":"");}'
            . 'recordEl.innerHTML=\'<span class="h2h-chip h2h-chip-a"><span class="h2h-chip-label">\'+winsLabel(data.teamAName)+\'</span><span class="h2h-chip-num">\'+data.winsA+\'</span></span>\''
            . '+\'<span class="h2h-chip h2h-chip-draw">\'+data.draws+\' \'+esc(drawLabel)+\'</span>\''
            . '+\'<span class="h2h-chip h2h-chip-b"><span class="h2h-chip-label">\'+winsLabel(data.teamBName)+\'</span><span class="h2h-chip-num">\'+data.winsB+\'</span></span>\';'
            . 'if(!data.matches.length){listEl.innerHTML=\'<p class="h2h-empty">\'+esc(noMatchesLabel)+\'</p>\';}'
            . 'else{listEl.innerHTML=data.matches.map(function(m){'
            . 'var heimWon=m.hTore>m.gTore,gastWon=m.gTore>m.hTore;'
            . 'var heimCls=heimWon?" h2h-winner":"",gastCls=gastWon?" h2h-winner":"";'
            . 'return \'<div class="h2h-match-row">\'' 
            . '+\'<a class="h2h-match-meta" href="liga.php?id=\'+m.ligaId+\'&view=ergebnisse&nr=\'+m.spieltag+\'">\'+esc(m.datum)+\' &middot; \'+esc(m.liga)+\', \'' 
            . '+\'<span class="h2h-rd-long">\'+esc(m.rundeLabel)+\'</span><span class="h2h-rd-short">\'+esc(m.rundeLabelKurz)+\'</span></a>\'' 
            . '+\'<div class="h2h-match-teams">\'' 
            . '+\'<span class="h2h-match-team\'+heimCls+\'">\'+esc(m.heim)+(m.heimToday?\'<span class="h2h-match-today">(\'+todayLabel+\' \'+esc(m.heimToday)+\')</span>\':\'\')+\'</span>\'' 
            . '+\'<span class="h2h-match-score">\'+m.hTore+\':\'+m.gTore+esc(m.suffix)+\'</span>\'' 
            . '+\'<span class="h2h-match-team\'+gastCls+\'">\'+esc(m.gast)+(m.gastToday?\'<span class="h2h-match-today">(\'+todayLabel+\' \'+esc(m.gastToday)+\')</span>\':\'\')+\'</span>\'' 
            . '+\'</div></div>\';'
            . '}).join("");}'
            . 'overlay.hidden=false;'
            . '}'
            . 'function close(){overlay.hidden=true;}'
            . 'document.addEventListener("click",function(e){'
            . 'var icon=e.target.closest(".h2h-icon");'
            . 'if(icon){open(JSON.parse(icon.getAttribute("data-h2h")));return;}'
            . 'if(e.target===overlay||e.target.closest("#h2h-close")){close();}'
            . '});'
            . 'document.addEventListener("keydown",function(e){if(e.key==="Escape"){close();}});'
            . '})();</script>';
    
        return $html;
    }
}
