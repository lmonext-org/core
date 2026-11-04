<?php
/**
 * Project: LMOnext
 * Filename: handler_import_merge.php
 * Fileversion: 1.2.0
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
 * Nur für Liga-Format-Dateien (Type 0), nicht für KO-Turniere.
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

/** Mögliche Ziel-Ligen (nicht archiviert, keine KO-Turniere). */
function mergeTargetLigen() : array
{
    try {
        $rows = getDB()->query('SELECT id,name FROM '.tbl('liga').' WHERE archiv_folder_id IS NULL ORDER BY name')->fetchAll();
    } catch (Throwable) {
        return [];
    }
    return array_values(array_filter($rows, static fn(array $r) => !mergeIsKoLiga((int)$r['id'])));
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
        'datesFilled' => 0, 'teamsAdded' => 0, 'pruned' => 0,
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

    if ($ligaId <= 0 || mergeIsKoLiga($ligaId)) {
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
    if ((int)$parsed['type'] === 1) {
        flash(t('impm_err_ko'), 'error');
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
        $upd = $db->prepare('UPDATE '.tbl('liga_partien').' SET h_tore=?,g_tore=?,zeit=?,notiz=?,status=?,bericht_url=?,extra_data=?,gt_entscheidung=? WHERE id=?');
        foreach ($u['updated'] as $r) {
            $upd->execute([$r['h_tore'], $r['g_tore'], $r['zeit'], $r['notiz'], $r['status'], $r['bericht_url'], $r['extra_data'], $r['gt_entscheidung'], (int)$r['id']]);
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
