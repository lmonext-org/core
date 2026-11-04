<?php
/**
 * Project: LMOnext
 * Filename: handler_import_merge.php
 * Fileversion: 1.3.2
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Import einer .l98-Datei in eine BEREITS VORHANDENE Liga (statt eine neue
 * Liga anzulegen, siehe handler_import_export.php):
 *   - vorhandene Begegnungen (gleicher Spieltag, gleiche Paarung) bekommen
 *     das Ergebnis aus der Datei - bereits eingetragene Ergebnisse werden
 *     standardmäßig NICHT überschrieben,
 *   - fehlende Spieltage/Begegnungen werden angelegt,
 *   - alles andere in der Ziel-Liga bleibt unverändert.
 * Ablauf: Upload -> Team-Zuordnung (+ Vorschau per Probelauf in einer
 * zurückgerollten Transaktion) -> Übernehmen.
 * Liga-Dateien (Type 0) gehen in Ligen, KO-Dateien (Type 1) in KO-Turniere:
 * dort werden Paarungen über die beteiligten Teams zugeordnet, Platzhalter
 * ("Sieger …", "___") durch die Teams der Datei ersetzt, siehe mergeKoRounds().
 */

/** Ist die Liga ein KO-Turnier (liga_options Type = 1)? */
function mergeIsKoLiga(int $ligaId) : bool
{
    try {
        $s = getDB()->prepare('SELECT option_value FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key=\'Type\'');
        $s->execute([$ligaId]);
        return (string)$s->fetchColumn() === '1';
    } catch (Throwable) {
        return false;
    }
}

/** Mögliche Ziel-Ligen und -Turniere (aktive zuerst, danach archivierte - die Übernahme ändert nur Ergebnisse, nicht den Archiv-Status). */
function mergeTargetLigen() : array
{
    try {
        $rows = getDB()->query('SELECT id,name,archiv_folder_id FROM '.tbl('liga').' ORDER BY (archiv_folder_id IS NOT NULL), name')->fetchAll();
    } catch (Throwable) {
        return [];
    }
    foreach ($rows as &$r) { $r['ko'] = mergeIsKoLiga((int)$r['id']); $r['archiv'] = $r['archiv_folder_id'] !== null; }
    unset($r);
    return $rows;
}

/**
 * Schlägt für jedes Team der Datei ein Ziel-Team vor.
 * Reihenfolge: exakter Name in der Ziel-Liga -> exakter Name global ->
 * ähnlicher Name in der Ziel-Liga -> ähnlicher Name global -> neu anlegen.
 *
 * @return array<int,array{name:string,suggest:int|string,options:array}>
 */
function mergeSuggestTeams(array $data, int $ligaId) : array
{
    $db = getDB();
    $sL = $db->prepare('SELECT g.id,g.name,g.kurz,g.mittel FROM '.tbl('teams_global').' g
                          JOIN '.tbl('liga_teams').' lt ON lt.team_id=g.id WHERE lt.liga_id=? ORDER BY g.name');
    $sL->execute([$ligaId]);
    $ligaTeams = $sL->fetchAll();
    // Auch Teams, die in den Partien der Liga vorkommen, aber (z.B. nach einem
    // früheren Import) nicht in liga_teams stehen, gehören zur Liga.
    $sP = $db->prepare('SELECT g.id,g.name,g.kurz,g.mittel FROM '.tbl('teams_global').' g WHERE g.id IN (
                          SELECT p.heim_id FROM '.tbl('liga_partien').' p JOIN '.tbl('liga_spieltage').' s ON s.id=p.spieltag_id WHERE s.liga_id=? AND p.heim_id IS NOT NULL
                          UNION
                          SELECT p.gast_id FROM '.tbl('liga_partien').' p JOIN '.tbl('liga_spieltage').' s ON s.id=p.spieltag_id WHERE s.liga_id=? AND p.gast_id IS NOT NULL
                        ) ORDER BY g.name');
    $sP->execute([$ligaId, $ligaId]);
    $known = [];
    foreach ($ligaTeams as $t) { $known[(int)$t['id']] = true; }
    foreach ($sP->fetchAll() as $t) {
        if (!isset($known[(int)$t['id']])) { $ligaTeams[] = $t; $known[(int)$t['id']] = true; }
    }
    usort($ligaTeams, static fn(array $a, array $b) => strcmp($a['name'], $b['name']));
    $global    = $db->query('SELECT id,name,kurz,mittel FROM '.tbl('teams_global'))->fetchAll();

    $ligaByName = []; $ligaIds = [];
    foreach ($ligaTeams as $t) { $ligaByName[$t['name']] = (int)$t['id']; $ligaIds[(int)$t['id']] = true; }
    $globalByName = [];
    foreach ($global as $t) { $globalByName[$t['name']] = (int)$t['id']; }
    $globalOthers = array_values(array_filter($global, static fn(array $t) => !isset($ligaIds[(int)$t['id']])));

    $out = [];
    foreach ($data['teams'] as $nr => $name) {
        $suggest = 'new';
        $extra   = []; // zusätzliche Kandidaten außerhalb der Liga
        if (isset($ligaByName[$name])) {
            $suggest = $ligaByName[$name];
        } elseif (isset($globalByName[$name])) {
            $suggest = $globalByName[$name];
            $extra[$suggest] = $name;
        } else {
            $fz = findFuzzyTeamMatches($name, $ligaTeams);
            if (!empty($fz)) {
                $suggest = (int)$fz[0]['id'];
            } else {
                $fg = findFuzzyTeamMatches($name, $globalOthers);
                if (!empty($fg)) {
                    $suggest = (int)$fg[0]['id'];
                    foreach (array_slice($fg, 0, 4) as $m) { $extra[(int)$m['id']] = $m['name']; }
                }
            }
        }
        $options = [];
        foreach ($ligaTeams as $t) {
            $options[] = ['id' => (int)$t['id'], 'name' => $t['name'], 'inLiga' => true];
        }
        foreach ($extra as $id => $n) {
            if (!isset($ligaIds[$id])) { $options[] = ['id' => (int)$id, 'name' => $n, 'inLiga' => false]; }
        }
        $out[$nr] = ['name' => $name, 'suggest' => $suggest, 'options' => $options];
    }
    return $out;
}

/**
 * KO-Variante der Übernahme (Ziel UND Datei sind KO-Turniere): Pro Runde werden
 * die Paarungen der Datei den vorhandenen Paarungen der Ziel-Runde über die
 * beteiligten Teams zugeordnet (Hin-/Rückrichtung egal). Ist in der Ziel-Paarung
 * eine Seite noch ein Platzhalter ("Sieger Achtelfinale 1", "___"), wird sie durch
 * das Team aus der Datei ersetzt. Ergebnisse werden wie bei Ligen übernommen
 * (vorhandene nur mit "überschreiben"), Heim/Gast der Einzelspiele richtet sich
 * nach dem Heimrecht-Schema der Ziel-Runde (koHeimatTeamA()). Nicht vorhandene
 * Paarungen werden angelegt.
 *
 * @param array<int,int|null> $teamId Datei-Team-Nr => Ziel-Team-ID (null = übersprungen)
 * @param array<int,string>   $names  Ziel-Team-ID => Name
 */
function mergeKoRounds(PDO $db, int $ligaId, array $data, array $teamId, array $names, bool $overwrite, array &$counts, array &$details, array &$undo) : void
{
    $sPm = $db->prepare('SELECT option_value FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key=\'playoffmode\'');
    $sPm->execute([$ligaId]);
    $playoffMode = (int)($sPm->fetchColumn() ?: 0);
    $sDum = $db->prepare('SELECT id FROM '.tbl('teams_global').' WHERE name=?');
    $sDum->execute(['___']);
    $dummyId = (int)$sDum->fetchColumn();

    $isFreilos = static fn(?string $label) : bool => $label !== null && $label === KO_FREILOS_MARKER;
    // Platzhalter-Seite: kein Team (NULL) oder Dummy-Team "___" - Freilos zählt nicht
    $isPh = static fn(?int $id, ?string $label) : bool => !$isFreilos($label) && ($id === null || $id === 0 || ($dummyId > 0 && $id === $dummyId));
    $sameTeam = static function (?int $idA, ?string $nameA, ?int $idB, ?string $nameB) : bool {
        if ($idA !== null && $idB !== null && $idA === $idB) { return true; }
        return $nameA !== null && $nameA !== '' && $nameB !== null && $nameB !== ''
            && teamNormalizeName($nameA) === teamNormalizeName($nameB);
    };

    $sST   = $db->prepare('SELECT id,modus FROM '.tbl('liga_spieltage').' WHERE liga_id=? AND nummer=?');
    $sSTi  = $db->prepare('INSERT INTO '.tbl('liga_spieltage').' (liga_id,nummer,start,modus) VALUES (?,?,?,?)');
    $sPs   = $db->prepare('SELECT p.id,p.heim_id,p.gast_id,p.heim_label,p.gast_label,p.h_tore,p.g_tore,p.zeit,p.spiel_nr,p.gt_entscheidung,th.name AS hn,tg.name AS gn
                             FROM '.tbl('liga_partien').' p
                        LEFT JOIN '.tbl('teams_global').' th ON th.id=p.heim_id
                        LEFT JOIN '.tbl('teams_global').' tg ON tg.id=p.gast_id
                            WHERE p.spieltag_id=? ORDER BY p.id');
    $sSnap = $db->prepare('SELECT id,h_tore,g_tore,zeit,notiz,status,bericht_url,extra_data,gt_entscheidung,heim_id,gast_id,heim_label,gast_label FROM '.tbl('liga_partien').' WHERE id=?');
    $sPi   = $db->prepare('INSERT INTO '.tbl('liga_partien').' (spieltag_id,heim_id,gast_id,heim_label,gast_label,h_tore,g_tore,zeit,notiz,status,bericht_url,spiel_nr,extra_data,gt_entscheidung) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $sPu   = $db->prepare('UPDATE '.tbl('liga_partien').' SET h_tore=?,g_tore=?,zeit=COALESCE(?,zeit),notiz=COALESCE(?,notiz),status=?,bericht_url=COALESCE(?,bericht_url),extra_data=COALESCE(?,extra_data),gt_entscheidung=? WHERE id=?');
    $sPz   = $db->prepare('UPDATE '.tbl('liga_partien').' SET zeit=? WHERE id=?');
    $sSide = $db->prepare('UPDATE '.tbl('liga_partien').' SET heim_id=?,heim_label=?,gast_id=?,gast_label=? WHERE id=?');
    $nameOf = static fn(?int $id) : string => $id !== null ? ($names[$id] ?? ('#'.$id)) : '?';

    foreach ($data['spieltage'] as $nr => $st) {
        if (empty($st['paarungen'])) { continue; }
        $fileModus = (int)($st['modus'] ?? KO_MODUS_DEFAULT);
        if (!isset(KO_MODUS[$fileModus])) { $fileModus = KO_MODUS_DEFAULT; }

        $sST->execute([$ligaId, $nr]);
        $stRow = $sST->fetch(PDO::FETCH_ASSOC);
        if (!$stRow) {
            $dt = null;
            if (!empty($st['datum'])) {
                $d = DateTime::createFromFormat('d.m.Y', $st['datum']) ?: DateTime::createFromFormat('d.m.y', $st['datum']);
                $dt = $d ? $d->format('Y-m-d 00:00:00') : null;
            }
            $sSTi->execute([$ligaId, $nr, $dt, $fileModus]);
            $stid = (int)$db->lastInsertId();
            $stModus = $fileModus;
            $roundNew = true;
            $counts['spieltageCreated']++;
            $undo['spieltage'][] = $stid;
            $details[] = ['nr' => $nr, 'text' => '', 'type' => 'spieltag'];
        } else {
            $stid = (int)$stRow['id'];
            $stModus = (int)$stRow['modus'];
            if (!isset(KO_MODUS[$stModus])) { $stModus = KO_MODUS_DEFAULT; }
            $roundNew = false;
        }

        // Vorhandene Paarungen der Ziel-Runde: [pNr => [sNr => Zeile]]
        $sPs->execute([$stid]);
        $groups = [];
        foreach ($sPs->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $parts = explode('_', (string)$row['spiel_nr']);
            $gNr = (int)$parts[0];
            $sNr = isset($parts[1]) ? (int)$parts[1] : 1;
            $groups[$gNr][$sNr] = $row;
        }
        $maxGroup = $groups ? max(array_keys($groups)) : 0;

        foreach ($st['paarungen'] as $pNr => $pa) {
            $fa = ((int)$pa['heim'] > 0) ? ($teamId[$pa['heim']] ?? null) : null;
            $fb = ((int)$pa['gast'] > 0) ? ($teamId[$pa['gast']] ?? null) : null;
            if (((int)$pa['heim'] > 0 && $fa === null) || ((int)$pa['gast'] > 0 && $fb === null)) {
                $counts['unmapped']++;
                continue;
            }
            if ($fa === null && $fb === null) { continue; } // noch keine Teams bekannt
            $faName = $fa !== null ? ($names[$fa] ?? '') : null;
            $fbName = $fb !== null ? ($names[$fb] ?? '') : null;

            // ── passende Ziel-Paarung suchen ────────────────────────────
            $score = static function (bool $ph, bool $fixed, ?int $slotId, ?string $slotName, ?int $tid, ?string $tName) use ($sameTeam) : int {
                if ($fixed) { return -1; }                       // Freilos: nie ändern
                if ($tid === null) { return $ph ? 1 : 0; }       // Datei kennt die Seite noch nicht
                if (!$ph && $sameTeam($slotId, $slotName, $tid, $tName)) { return 2; }
                if ($ph) { return 1; }                           // Platzhalter -> kann gefüllt werden
                return -1;                                       // anderes Team
            };
            $best = null; $bestScore = 0; $bestSwap = false;
            foreach ($groups as $gNr => $g) {
                $r1 = $g[1] ?? (reset($g) ?: null);
                if ($r1 === null) { continue; }
                $aId = $r1['heim_id'] !== null ? (int)$r1['heim_id'] : null;
                $bId = $r1['gast_id'] !== null ? (int)$r1['gast_id'] : null;
                $aPh = $isPh($aId, $r1['heim_label']);  $bPh = $isPh($bId, $r1['gast_label']);
                $aFx = $isFreilos($r1['heim_label']);   $bFx = $isFreilos($r1['gast_label']);
                foreach ([false, true] as $swap) {
                    $ta = $swap ? $fb : $fa; $tb = $swap ? $fa : $fb;
                    $tan = $swap ? $fbName : $faName; $tbn = $swap ? $faName : $fbName;
                    $sa = $score($aPh, $aFx, $aId, $r1['hn'], $ta, $tan);
                    $sb = $score($bPh, $bFx, $bId, $r1['gn'], $tb, $tbn);
                    if ($sa < 0 || $sb < 0) { continue; }
                    if ($sa !== 2 && $sb !== 2) { continue; }     // mindestens ein Team muss sicher passen
                    if ($sa + $sb > $bestScore) { $best = $gNr; $bestScore = $sa + $sb; $bestSwap = $swap; }
                }
            }

            $fileGames = $pa['spiele'];

            // ── keine passende Paarung: neu anlegen ────────────────────────
            if ($best === null) {
                $maxGroup++;
                $made = []; $resTxt = [];
                $modus = $roundNew ? $fileModus : $stModus;
                for ($s = 1; $s <= $modus; $s++) {
                    $fg = $fileGames[$s] ?? ['h_tore' => null, 'g_tore' => null, 'zeit' => null, 'notiz' => null, 'status' => 0, 'bericht' => null, 'sets' => [], 'gt_sieger' => ''];
                    $aHeim = koHeimatTeamA($s, $modus, $playoffMode);
                    // GA/GB der Datei = Tore Team A/B; Ziel-Heim = A wenn $aHeim
                    $swapGoals = !$aHeim;
                    $hT = $swapGoals ? $fg['g_tore'] : $fg['h_tore'];
                    $gT = $swapGoals ? $fg['h_tore'] : $fg['g_tore'];
                    $sets = $fg['sets'] ?? [];
                    if ($swapGoals && $sets) { $sets = array_map(static fn($x) => ['h' => $x['g'], 'g' => $x['h']], $sets); }
                    $gt = 0;
                    if (($fg['gt_sieger'] ?? '') === 'a') { $gt = $aHeim ? 1 : 2; }
                    elseif (($fg['gt_sieger'] ?? '') === 'b') { $gt = $aHeim ? 2 : 1; }
                    $aLbl = $fa === null ? '___' : null; $bLbl = $fb === null ? '___' : null;
                    $sPi->execute([
                        $stid,
                        $aHeim ? $fa : $fb, $aHeim ? $fb : $fa,
                        $aHeim ? $aLbl : $bLbl, $aHeim ? $bLbl : $aLbl,
                        $hT, $gT, $fg['zeit'] ?? null, ($fg['notiz'] ?? null) ?: null,
                        $fg['status'] ?? 0, $fg['bericht'] ?? null, $maxGroup.'_'.$s,
                        $sets ? json_encode(['sets' => $sets]) : null, $gt,
                    ]);
                    $undo['created'][] = (int)$db->lastInsertId();
                    $counts['created']++;
                    if ($fg['h_tore'] !== null && $fg['g_tore'] !== null) { $resTxt[] = $fg['h_tore'].':'.$fg['g_tore']; }
                }
                $details[] = ['nr' => $nr, 'text' => $nameOf($fa) . ' – ' . $nameOf($fb), 'type' => 'created', 'res' => implode(' / ', $resTxt)];
                continue;
            }

            // ── vorhandene Paarung: Platzhalter füllen, Ergebnisse eintragen ──
            $g = $groups[$best];
            $r1 = $g[1] ?? reset($g);
            $tA = $bestSwap ? $fb : $fa;   // Team der Datei für Ziel-Seite A
            $tB = $bestSwap ? $fa : $fb;   // ... für Ziel-Seite B
            $curA = $r1['heim_id'] !== null ? (int)$r1['heim_id'] : null; $lblA = $r1['heim_label'];
            $curB = $r1['gast_id'] !== null ? (int)$r1['gast_id'] : null; $lblB = $r1['gast_label'];
            $fillA = $isPh($curA, $lblA) && $tA !== null;
            $fillB = $isPh($curB, $lblB) && $tB !== null;
            if ($fillA || $fillB) {
                foreach ($g as $s => $row) {
                    $aHeim = koHeimatTeamA((int)$s, $stModus, $playoffMode);
                    $newA = $fillA ? [$tA, null] : null;
                    $newB = $fillB ? [$tB, null] : null;
                    $hId = $row['heim_id']; $hLb = $row['heim_label']; $gId = $row['gast_id']; $gLb = $row['gast_label'];
                    if ($aHeim) {
                        if ($newA) { [$hId, $hLb] = $newA; }
                        if ($newB) { [$gId, $gLb] = $newB; }
                    } else {
                        if ($newA) { [$gId, $gLb] = $newA; }
                        if ($newB) { [$hId, $hLb] = $newB; }
                    }
                    $sSnap->execute([(int)$row['id']]); $undo['updated'][] = $sSnap->fetch(PDO::FETCH_ASSOC);
                    $sSide->execute([$hId, $hLb, $gId, $gLb, (int)$row['id']]);
                    $groups[$best][$s]['heim_id'] = $hId; $groups[$best][$s]['heim_label'] = $hLb;
                    $groups[$best][$s]['gast_id'] = $gId; $groups[$best][$s]['gast_label'] = $gLb;
                }
                $counts['placeholdersFilled']++;
                $details[] = ['nr' => $nr, 'text' => $nameOf($tA ?? $curA) . ' – ' . $nameOf($tB ?? $curB), 'type' => 'resolved', 'res' => ''];
                $curA = $fillA ? $tA : $curA; $curB = $fillB ? $tB : $curB;
            }

            for ($s = 1; $s <= $fileModus; $s++) {
                $fg = $fileGames[$s] ?? null;
                if ($fg === null) { continue; }
                $aHeimT = koHeimatTeamA($s, $stModus, $playoffMode);
                // GA/GB der Datei = Tore Team A/B der Datei. Ziel-Seite A entspricht
                // Datei-A (direkt) bzw. Datei-B (vertauscht); Heim im Ziel-Spiel ist A,
                // wenn $aHeimT gilt.
                $swapGoals = ($bestSwap === $aHeimT);
                $hT = $swapGoals ? $fg['g_tore'] : $fg['h_tore'];
                $gT = $swapGoals ? $fg['h_tore'] : $fg['g_tore'];
                $sets = $fg['sets'] ?? [];
                if ($swapGoals && $sets) { $sets = array_map(static fn($x) => ['h' => $x['g'], 'g' => $x['h']], $sets); }
                $gtF = $fg['gt_sieger'] ?? '';
                $gt = 0;
                if ($gtF !== '') {
                    $winnerIsFileA = ($gtF === 'a');
                    $winnerIsTargetA = $bestSwap ? !$winnerIsFileA : $winnerIsFileA;
                    $gt = ($winnerIsTargetA === $aHeimT) ? 1 : 2;
                }
                $hasFileResult = ($hT !== null && $gT !== null) || $gt > 0;
                $fileRes = $fg['h_tore'] !== null && $fg['g_tore'] !== null ? ($fg['h_tore'] . ':' . $fg['g_tore']) : '';
                $lbl = $nameOf($curA) . ' – ' . $nameOf($curB) . ($fileModus > 1 ? ' (' . $s . ')' : '');

                $row = $groups[$best][$s] ?? null;
                if ($row === null) {
                    if (!$roundNew && $s > $stModus) { $counts['unmapped']++; continue; }
                    // Spiel fehlt in der Ziel-Paarung: mit den Seiten aus Spiel 1 anlegen
                    $hId = $aHeimT ? $curA : $curB; $hLb = $aHeimT ? $lblA : $lblB;
                    $gId = $aHeimT ? $curB : $curA; $gLb = $aHeimT ? $lblB : $lblA;
                    $sPi->execute([$stid, $hId, $gId, $hLb, $gLb, $hT, $gT, $fg['zeit'] ?? null, ($fg['notiz'] ?? null) ?: null,
                        $fg['status'] ?? 0, $fg['bericht'] ?? null, $best.'_'.$s, $sets ? json_encode(['sets' => $sets]) : null, $gt]);
                    $newId = (int)$db->lastInsertId();
                    $undo['created'][] = $newId;
                    $groups[$best][$s] = ['id' => $newId, 'heim_id' => $hId, 'gast_id' => $gId, 'heim_label' => $hLb, 'gast_label' => $gLb,
                                          'h_tore' => $hT, 'g_tore' => $gT, 'zeit' => $fg['zeit'] ?? null, 'gt_entscheidung' => $gt];
                    $counts['created']++;
                    $details[] = ['nr' => $nr, 'text' => $lbl, 'type' => 'created', 'res' => $fileRes];
                    continue;
                }

                $targetHasResult = ($row['h_tore'] !== null && $row['g_tore'] !== null) || (int)$row['gt_entscheidung'] > 0;
                $oldRes = $row['h_tore'] !== null ? ($row['h_tore'] . ':' . $row['g_tore']) : '';
                $newRes = $hT !== null && $gT !== null ? ($hT . ':' . $gT) : '';
                if (!$hasFileResult) {
                    if ($row['zeit'] === null && ($fg['zeit'] ?? null) !== null) {
                        $sSnap->execute([(int)$row['id']]); $undo['updated'][] = $sSnap->fetch(PDO::FETCH_ASSOC);
                        $sPz->execute([$fg['zeit'], (int)$row['id']]);
                        $groups[$best][$s]['zeit'] = $fg['zeit'];
                        $counts['datesFilled']++;
                    } else {
                        $counts['unchanged']++;
                    }
                    continue;
                }
                if ($targetHasResult && !$overwrite) {
                    if ($oldRes === $newRes && (int)$row['gt_entscheidung'] === $gt) {
                        $counts['unchanged']++;
                    } else {
                        $counts['skippedExisting']++;
                        $details[] = ['nr' => $nr, 'text' => $lbl, 'type' => 'skipped', 'res' => $newRes, 'old' => $oldRes];
                    }
                    continue;
                }
                $sSnap->execute([(int)$row['id']]); $undo['updated'][] = $sSnap->fetch(PDO::FETCH_ASSOC);
                $sPu->execute([$hT, $gT, $fg['zeit'] ?? null, ($fg['notiz'] ?? null) ?: null, $fg['status'] ?? 0, $fg['bericht'] ?? null,
                               $sets ? json_encode(['sets' => $sets]) : null, $gt, (int)$row['id']]);
                $groups[$best][$s]['h_tore'] = $hT; $groups[$best][$s]['g_tore'] = $gT; $groups[$best][$s]['gt_entscheidung'] = $gt;
                if ($targetHasResult) {
                    $counts['overwritten']++;
                    $details[] = ['nr' => $nr, 'text' => $lbl, 'type' => 'overwritten', 'res' => $newRes, 'old' => $oldRes];
                } else {
                    $counts['filled']++;
                    $details[] = ['nr' => $nr, 'text' => $lbl, 'type' => 'filled', 'res' => $newRes];
                }
            }
        }
    }
}

/**
 * Führt die Übernahme aus (oder simuliert sie: $dry = true rollt die
 * Transaktion am Ende zurück - die Zähler/Details sind trotzdem korrekt).
 *
 * @param array<int,int|string> $map  Datei-Team-Nr => Ziel-Team-ID | 'new' | 'skip'
 * @return array{ok:bool,error?:string,counts:array,details:array,teamsCreated:array}
 */
function mergeApplyL98(int $ligaId, array $data, array $map, bool $overwrite, bool $dry, bool $prune = false) : array
{
    $db = getDB();

    // On-Demand-Migration (wie importL98IntoDB) IMMER vor der Transaktion
    $cols = $db->query('SHOW COLUMNS FROM '.tbl('liga_partien'))->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('extra_data', $cols, true)) {
        $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `extra_data` JSON NULL DEFAULT NULL');
    }
    if (!in_array('gt_entscheidung', $cols, true)) {
        $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `gt_entscheidung` TINYINT NOT NULL DEFAULT 0');
    }

    $counts = [
        'filled' => 0, 'overwritten' => 0, 'skippedExisting' => 0, 'unchanged' => 0,
        'created' => 0, 'spieltageCreated' => 0, 'swapped' => 0, 'unmapped' => 0,
        'datesFilled' => 0, 'teamsAdded' => 0, 'pruned' => 0, 'placeholdersFilled' => 0,
    ];
    $details = [];
    $teamsCreated = [];
    $label = static fn(array $names, int $h, int $g) => ($names[$h] ?? ('#'.$h)) . ' – ' . ($names[$g] ?? ('#'.$g));

    try {
        $db->beginTransaction();

        // ── Teams auflösen ───────────────────────────────────────────────
        $teamId = [];
        $names  = []; // Ziel-Team-ID => Name (für die Anzeige)
        $sSel = $db->prepare('SELECT id,name FROM '.tbl('teams_global').' WHERE id=?');
        $sByName = $db->prepare('SELECT id FROM '.tbl('teams_global').' WHERE name=? LIMIT 1');
        $sIns = $db->prepare('INSERT INTO '.tbl('teams_global').' (name,kurz,mittel) VALUES (?,?,?)');
        $sLT  = $db->prepare('INSERT IGNORE INTO '.tbl('liga_teams').' (liga_id,team_id) VALUES (?,?)');
        foreach ($data['teams'] as $nr => $fileName) {
            $m = $map[$nr] ?? 'skip';
            if ($m === 'skip') { $teamId[$nr] = null; continue; }
            if ($m === 'new') {
                $sByName->execute([$fileName]);
                $tid = (int)$sByName->fetchColumn();
                if ($tid === 0) {
                    $sIns->execute([$fileName, $data['teamKurz'][$nr] ?? '', $data['teamMittel'][$nr] ?? '']);
                    $tid = (int)$db->lastInsertId();
                    $teamsCreated[] = $fileName;
                }
                $names[$tid] = $fileName;
            } else {
                $tid = (int)$m;
                $sSel->execute([$tid]);
                $row = $sSel->fetch();
                if (!$row) { $teamId[$nr] = null; continue; }
                $names[$tid] = $row['name'];
            }
            $teamId[$nr] = $tid;
            $sLT->execute([$ligaId, $tid]);
            $counts['teamsAdded'] += $sLT->rowCount();
        }

        // ── KO-Turnier (Ziel und Datei): eigene Zuordnung über Paarungen ─────
        if ((int)($data['type'] ?? 0) === 1) {
            $undoKo = ['ligaId' => $ligaId, 'created' => [], 'updated' => [], 'spieltage' => [], 'deleted' => []];
            mergeKoRounds($db, $ligaId, $data, $teamId, $names, $overwrite, $counts, $details, $undoKo);
            if ($dry) { $db->rollBack(); } else { $db->commit(); }
            return ['ok' => true, 'counts' => $counts, 'details' => $details, 'teamsCreated' => $teamsCreated, 'undo' => $undoKo];
        }

        // ── Spieltage und Partien ────────────────────────────────────────
        $sST   = $db->prepare('SELECT id FROM '.tbl('liga_spieltage').' WHERE liga_id=? AND nummer=?');
        $sSTi  = $db->prepare('INSERT INTO '.tbl('liga_spieltage').' (liga_id,nummer,start,modus) VALUES (?,?,?,0)');
        $sPs   = $db->prepare('SELECT p.id,p.heim_id,p.gast_id,p.h_tore,p.g_tore,p.zeit,p.spiel_nr,p.gt_entscheidung,th.name AS hn,tg.name AS gn
                                 FROM '.tbl('liga_partien').' p
                            LEFT JOIN '.tbl('teams_global').' th ON th.id=p.heim_id
                            LEFT JOIN '.tbl('teams_global').' tg ON tg.id=p.gast_id
                                WHERE p.spieltag_id=? ORDER BY (p.h_tore IS NULL), p.id');
        $sSnap = $db->prepare('SELECT id,h_tore,g_tore,zeit,notiz,status,bericht_url,extra_data,gt_entscheidung FROM '.tbl('liga_partien').' WHERE id=?');
        $undo  = ['ligaId' => $ligaId, 'created' => [], 'updated' => [], 'spieltage' => [], 'deleted' => []];
        $sameTeam = static function (?int $idA, ?string $nameA, int $idB, string $nameB) : bool {
            if ($idA !== null && $idA === $idB) { return true; }
            // gleicher (normalisierter) Name = dasselbe Team, auch bei doppelt angelegten Teams
            return $nameA !== null && $nameA !== '' && teamNormalizeName($nameA) === teamNormalizeName($nameB);
        };
        $sPi   = $db->prepare('INSERT INTO '.tbl('liga_partien').' (spieltag_id,heim_id,gast_id,h_tore,g_tore,zeit,notiz,status,bericht_url,spiel_nr,extra_data,gt_entscheidung) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $sPu   = $db->prepare('UPDATE '.tbl('liga_partien').' SET h_tore=?,g_tore=?,zeit=COALESCE(?,zeit),notiz=COALESCE(?,notiz),status=?,bericht_url=COALESCE(?,bericht_url),extra_data=COALESCE(?,extra_data),gt_entscheidung=? WHERE id=?');
        $sPz   = $db->prepare('UPDATE '.tbl('liga_partien').' SET zeit=? WHERE id=?');

        foreach ($data['spieltage'] as $nr => $st) {
            $sST->execute([$ligaId, $nr]);
            $stid = (int)$sST->fetchColumn();
            if ($stid === 0) {
                $dt = null;
                if (!empty($st['datum'])) {
                    $d = DateTime::createFromFormat('d.m.Y', $st['datum']) ?: DateTime::createFromFormat('d.m.y', $st['datum']);
                    $dt = $d ? $d->format('Y-m-d 00:00:00') : null;
                }
                $sSTi->execute([$ligaId, $nr, $dt]);
                $stid = (int)$db->lastInsertId();
                $counts['spieltageCreated']++;
                $undo['spieltage'][] = $stid;
                $details[] = ['nr' => $nr, 'text' => '', 'type' => 'spieltag'];
            }
            $sPs->execute([$stid]);
            $existing = $sPs->fetchAll();
            $maxNr = 0;
            foreach ($existing as $e) { $maxNr = max($maxNr, (int)$e['spiel_nr']); }
            $keep = [];            // IDs vorhandener/neuer Begegnungen, die der Datei entsprechen
            $unmappedHere = false; // nicht zuordenbare Teams -> in diesem Spieltag nichts entfernen

            foreach (($st['partien'] ?? []) as $p) {
                $h = $teamId[$p['heim']] ?? null;
                $g = $teamId[$p['gast']] ?? null;
                if (!$h || !$g) { $counts['unmapped']++; $unmappedHere = true; continue; }

                $hasFileResult = ($p['h_tore'] !== null && $p['g_tore'] !== null) || (int)($p['gt_entscheidung'] ?? 0) > 0;
                $fileRes = $p['h_tore'] !== null ? ($p['h_tore'] . ':' . $p['g_tore']) : '';

                $found = null; $reversed = null;
                $hName = (string)($names[$h] ?? '');
                $gName = (string)($names[$g] ?? '');
                foreach ($existing as $i => $e) {
                    $eh = $e['heim_id'] !== null ? (int)$e['heim_id'] : null;
                    $eg = $e['gast_id'] !== null ? (int)$e['gast_id'] : null;
                    if ($sameTeam($eh, $e['hn'] ?? null, $h, $hName) && $sameTeam($eg, $e['gn'] ?? null, $g, $gName)) { $found = $i; break; }
                    if ($sameTeam($eh, $e['hn'] ?? null, $g, $gName) && $sameTeam($eg, $e['gn'] ?? null, $h, $hName)) { $reversed = $i; }
                }

                if ($found === null) {
                    if ($reversed !== null) {
                        $keep[(int)$existing[$reversed]['id']] = true;
                        $counts['swapped']++;
                        $details[] = ['nr' => $nr, 'text' => $label($names, $h, $g), 'type' => 'swapped', 'res' => $fileRes];
                        continue;
                    }
                    // Diagnose: warum wurde nichts Passendes gefunden? Zeigt vorhandene
                    // Begegnungen des Spieltags, die mindestens ein gleiches Team enthalten.
                    $hint = [];
                    foreach ($existing as $e) {
                        $eh = $e['heim_id'] !== null ? (int)$e['heim_id'] : null;
                        $eg = $e['gast_id'] !== null ? (int)$e['gast_id'] : null;
                        $touch = $sameTeam($eh, $e['hn'] ?? null, $h, $hName) || $sameTeam($eg, $e['gn'] ?? null, $g, $gName)
                              || $sameTeam($eh, $e['hn'] ?? null, $g, $gName) || $sameTeam($eg, $e['gn'] ?? null, $h, $hName);
                        if ($touch) {
                            $hint[] = 'ID ' . $e['id'] . ': ' . ($e['hn'] ?? '?') . ' (' . ($eh ?? '-') . ') – ' . ($e['gn'] ?? '?') . ' (' . ($eg ?? '-') . ')';
                        }
                    }
                    $hint = 'Ziel: ' . $hName . ' (' . $h . ') – ' . $gName . ' (' . $g . '); im Spieltag vorhanden: '
                          . count($existing) . ($hint ? ' | ähnlich: ' . implode(' ; ', array_slice($hint, 0, 3)) : ' | keine ähnliche');
                    $maxNr++;
                    $sPi->execute([
                        $stid, $h, $g, $p['h_tore'], $p['g_tore'], $p['zeit'], ($p['notiz'] ?? null) ?: null,
                        $p['status'] ?? 0, $p['bericht'] ?? null, (string)$maxNr,
                        !empty($p['sets']) ? json_encode(['sets' => $p['sets']]) : null,
                        $p['gt_entscheidung'] ?? 0,
                    ]);
                    $newId = (int)$db->lastInsertId();
                    $undo['created'][] = $newId;
                    $keep[$newId] = true;
                    $existing[] = ['id' => $newId, 'heim_id' => $h, 'gast_id' => $g, 'hn' => $hName, 'gn' => $gName,
                                   'h_tore' => $p['h_tore'], 'g_tore' => $p['g_tore'], 'zeit' => $p['zeit'],
                                   'spiel_nr' => (string)$maxNr, 'gt_entscheidung' => $p['gt_entscheidung'] ?? 0];
                    $counts['created']++;
                    $details[] = ['nr' => $nr, 'text' => $label($names, $h, $g), 'type' => 'created', 'res' => $fileRes, 'hint' => $hint];
                    continue;
                }

                $e = $existing[$found];
                $keep[(int)$e['id']] = true;
                $targetHasResult = ($e['h_tore'] !== null && $e['g_tore'] !== null) || (int)$e['gt_entscheidung'] > 0;
                $oldRes = $e['h_tore'] !== null ? ($e['h_tore'] . ':' . $e['g_tore']) : '';

                if (!$hasFileResult) {
                    if ($e['zeit'] === null && $p['zeit'] !== null) {
                        $sSnap->execute([(int)$e['id']]); $undo['updated'][] = $sSnap->fetch(PDO::FETCH_ASSOC);
                        $sPz->execute([$p['zeit'], (int)$e['id']]);
                        $existing[$found]['zeit'] = $p['zeit'];
                        $counts['datesFilled']++;
                    } else {
                        $counts['unchanged']++;
                    }
                    continue;
                }
                if ($targetHasResult && !$overwrite) {
                    if ($oldRes === $fileRes && (int)$e['gt_entscheidung'] === (int)($p['gt_entscheidung'] ?? 0)) {
                        $counts['unchanged']++;
                    } else {
                        $counts['skippedExisting']++;
                        $details[] = ['nr' => $nr, 'text' => $label($names, $h, $g), 'type' => 'skipped', 'res' => $fileRes, 'old' => $oldRes];
                    }
                    continue;
                }
                $sSnap->execute([(int)$e['id']]); $undo['updated'][] = $sSnap->fetch(PDO::FETCH_ASSOC);
                $sPu->execute([
                    $p['h_tore'], $p['g_tore'], $p['zeit'], ($p['notiz'] ?? null) ?: null,
                    $p['status'] ?? 0, $p['bericht'] ?? null,
                    !empty($p['sets']) ? json_encode(['sets' => $p['sets']]) : null,
                    $p['gt_entscheidung'] ?? 0, (int)$e['id'],
                ]);
                $existing[$found]['h_tore'] = $p['h_tore'];
                $existing[$found]['g_tore'] = $p['g_tore'];
                $existing[$found]['gt_entscheidung'] = $p['gt_entscheidung'] ?? 0;
                if ($targetHasResult) {
                    $counts['overwritten']++;
                    $details[] = ['nr' => $nr, 'text' => $label($names, $h, $g), 'type' => 'overwritten', 'res' => $fileRes, 'old' => $oldRes];
                } else {
                    $counts['filled']++;
                    $details[] = ['nr' => $nr, 'text' => $label($names, $h, $g), 'type' => 'filled', 'res' => $fileRes];
                }
            }

            // ── Überzählige Begegnungen entfernen (nur ohne Ergebnis) ──
            if ($prune && !$unmappedHere) {
                $sFull = $db->prepare('SELECT * FROM '.tbl('liga_partien').' WHERE id=?');
                $sDel  = $db->prepare('DELETE FROM '.tbl('liga_partien').' WHERE id=?');
                foreach ($existing as $e) {
                    $eid = (int)$e['id'];
                    if (isset($keep[$eid])) { continue; }
                    $hasRes = ($e['h_tore'] !== null || $e['g_tore'] !== null) || (int)$e['gt_entscheidung'] > 0;
                    if ($hasRes) { continue; } // Ergebnisse werden nie automatisch gelöscht
                    $sFull->execute([$eid]);
                    $full = $sFull->fetch(PDO::FETCH_ASSOC);
                    if (!$full) { continue; }
                    $undo['deleted'][] = $full;
                    $sDel->execute([$eid]);
                    $counts['pruned']++;
                    $details[] = ['nr' => $nr, 'text' => ($e['hn'] ?? '?') . ' – ' . ($e['gn'] ?? '?'), 'type' => 'pruned', 'res' => ''];
                }
            }
        }

        if ($dry) { $db->rollBack(); } else { $db->commit(); }
        return ['ok' => true, 'counts' => $counts, 'details' => $details, 'teamsCreated' => $teamsCreated, 'undo' => $undo];
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        return ['ok' => false, 'error' => $e->getMessage(), 'counts' => $counts, 'details' => $details, 'teamsCreated' => $teamsCreated];
    }
}

// ── Upload: Datei einlesen und Zuordnungsseite vorbereiten ───────────────────
if ($action === 'import_merge' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $ligaId = (int)($_POST['target_liga'] ?? 0);
    $f = $_FILES['l98file'] ?? null;

    if ($ligaId <= 0) {
        flash(t('impm_err_target'), 'error');
        redirect('?action=import_merge');
    }
    $sL = getDB()->prepare('SELECT id,name FROM '.tbl('liga').' WHERE id=?');
    $sL->execute([$ligaId]);
    $ligaRow = $sL->fetch();
    if (!$ligaRow) {
        flash(t('impm_err_target'), 'error');
        redirect('?action=import_merge');
    }
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION)) !== 'l98') {
        flash(t('impm_err_file'), 'error');
        redirect('?action=import_merge&liga_id=' . $ligaId);
    }
    $content = file_get_contents($f['tmp_name']);
    if ($content === false) {
        flash(t('impm_err_file'), 'error');
        redirect('?action=import_merge&liga_id=' . $ligaId);
    }
    $parsed = parseL98($content);
    // Liga-Datei nur in Ligen, KO-Datei nur in KO-Turniere
    if (((int)$parsed['type'] === 1) !== mergeIsKoLiga($ligaId)) {
        flash(t((int)$parsed['type'] === 1 ? 'impm_err_ko' : 'impm_err_liga_in_ko'), 'error');
        redirect('?action=import_merge&liga_id=' . $ligaId);
    }
    if (empty($parsed['spieltage'])) {
        flash(t('impm_err_empty'), 'error');
        redirect('?action=import_merge&liga_id=' . $ligaId);
    }

    $_SESSION['import_merge'] = [
        'ligaId'    => $ligaId,
        'ligaName'  => $ligaRow['name'],
        'fileName'  => (string)$f['name'],
        'data'      => $parsed,
        'overwrite' => !empty($_POST['overwrite']),
        'prune'     => !empty($_POST['prune']),
        'map'       => null,
        'report'    => null,
    ];
    redirect('?action=import_merge_review');
}

// ── Letzte Übernahme rückgängig machen ──────────────────────────────────────
// Löscht die dabei neu angelegten Begegnungen/Spieltage und stellt die
// geänderten Begegnungen auf ihren vorherigen Stand zurück (nur solange die
// Sitzung läuft - die Daten dafür liegen in der Session).
if ($action === 'import_merge_undo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $u = $_SESSION['import_merge_undo'] ?? null;
    unset($_SESSION['import_merge_undo']);
    if (!is_array($u)) {
        flash(t('impm_undo_none'), 'error');
        redirect('?action=import_merge');
    }
    $db = getDB();
    try {
        $db->beginTransaction();
        $del = $db->prepare('DELETE FROM '.tbl('liga_partien').' WHERE id=?');
        foreach ($u['created'] as $id) { $del->execute([(int)$id]); }
        // Spalten je nach Snapshot (KO-Übernahmen sichern zusätzlich Teams/Labels)
        foreach ($u['updated'] as $r) {
            $cols = array_values(array_intersect(
                ['h_tore','g_tore','zeit','notiz','status','bericht_url','extra_data','gt_entscheidung','heim_id','gast_id','heim_label','gast_label'],
                array_keys($r)
            ));
            $vals = [];
            foreach ($cols as $c) { $vals[] = $r[$c]; }
            $vals[] = (int)$r['id'];
            $db->prepare('UPDATE '.tbl('liga_partien').' SET '.implode(',', array_map(static fn($c) => '`'.$c.'`=?', $cols)).' WHERE id=?')->execute($vals);
        }
        // entfernte Begegnungen wiederherstellen
        foreach (($u['deleted'] ?? []) as $r) {
            $cols = array_keys($r);
            $db->prepare('INSERT INTO '.tbl('liga_partien').' (`'.implode('`,`', $cols).'`) VALUES ('.implode(',', array_fill(0, count($cols), '?')).')')
               ->execute(array_values($r));
        }
        // neu angelegte Spieltage nur löschen, wenn sie jetzt leer sind
        $cnt = $db->prepare('SELECT COUNT(*) FROM '.tbl('liga_partien').' WHERE spieltag_id=?');
        $delSt = $db->prepare('DELETE FROM '.tbl('liga_spieltage').' WHERE id=?');
        foreach ($u['spieltage'] as $sid) {
            $cnt->execute([(int)$sid]);
            if ((int)$cnt->fetchColumn() === 0) { $delSt->execute([(int)$sid]); }
        }
        $db->commit();
        logAdminAction('import_merge_undo', (string)($u['ligaId'] ?? ''));
        flash(t('impm_undo_done', ['n' => count($u['created']), 'm' => count($u['updated']), 'r' => count($u['deleted'] ?? [])]));
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        flash(t('imp_error_prefix', ['msg' => $e->getMessage()]), 'error');
    }
    redirect('?action=import_merge');
}

// ── Abbrechen ───────────────────────────────────────────────────────────────
if ($action === 'import_merge_cancel') {
    requireLogin();
    unset($_SESSION['import_merge']);
    redirect('?action=import_merge');
}

// ── Vorschau (Probelauf) oder tatsächliche Übernahme ─────────────────────────
if ($action === 'import_merge_apply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $pending = $_SESSION['import_merge'] ?? null;
    if (!is_array($pending)) {
        flash(t('imp_review_expired'), 'error');
        redirect('?action=import_merge');
    }

    $map = [];
    foreach (($_POST['map'] ?? []) as $nr => $val) {
        $val = (string)$val;
        $map[(int)$nr] = ($val === 'new' || $val === 'skip') ? $val : (string)(int)$val;
    }
    $overwrite = !empty($_POST['overwrite']);
    $prune     = !empty($_POST['prune']);
    $_SESSION['import_merge']['prune']     = $prune;
    $_SESSION['import_merge']['map']       = $map;
    $_SESSION['import_merge']['overwrite'] = $overwrite;

    $dry = (($_POST['do'] ?? 'preview') !== 'apply');
    $result = mergeApplyL98((int)$pending['ligaId'], $pending['data'], $map, $overwrite, $dry, $prune);

    if (!$result['ok']) {
        flash(t('imp_error_prefix', ['msg' => $result['error'] ?? '']), 'error');
        redirect('?action=import_merge_review');
    }
    if ($dry) {
        $_SESSION['import_merge']['report'] = $result;
        redirect('?action=import_merge_review');
    }

    $_SESSION['import_merge_done'] = [
        'ligaId'   => (int)$pending['ligaId'],
        'ligaName' => $pending['ligaName'],
        'fileName' => $pending['fileName'],
        'result'   => $result,
    ];
    $_SESSION['import_merge_undo'] = $result['undo'] ?? null;
    unset($_SESSION['import_merge']);
    logAdminAction('import_merge', $pending['ligaName'] . ' <- ' . $pending['fileName']);
    redirect('?action=import_merge&done=1');
}
