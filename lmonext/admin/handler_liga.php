<?php
/**
 * Project: LMOnext
 * Filename: handler_liga.php
 * Fileversion: 1.14.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Ergebnisse speichern ──────────────────────────────────────────────────────
if ($action === 'save_ergebnisse' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid  = (int)($_POST['liga_id']    ?? 0);
    $stNr = (int)($_POST['spieltag_nr'] ?? 0);
    try {
        $db     = getDB();
        $stmtE  = $db->prepare('UPDATE '.tbl('liga_partien').' SET h_tore=?, g_tore=?, zeit=?, status=?, bericht_url=?, gt_entscheidung=?, gt_grund=? WHERE id=?');
        foreach ($_POST as $key => $val) {
            if (preg_match('/^h_(\d+)$/', $key, $m)) {
                $pid     = (int)$m[1];
                $hv      = $_POST['h_'.$pid]    === '' ? null : (int)$_POST['h_'.$pid];
                $gv      = $_POST['g_'.$pid]    === '' ? null : (int)$_POST['g_'.$pid];
                $zeit    = trim($_POST['at_'.$pid] ?? '');
                // datetime-local liefert "YYYY-MM-DDTHH:MM" → in "YYYY-MM-DD HH:MM:SS" umwandeln
                $zeitDb  = $zeit !== '' ? str_replace('T', ' ', $zeit).':00' : null;
                $status  = (int)($_POST['status_'.$pid] ?? 0);
                if ($status < 0 || $status > 2) { $status = 0; }
                $bericht = trim($_POST['bericht_'.$pid] ?? '');
                $berichtDb = $bericht !== '' ? $bericht : null;
                // Grüne-Tisch-Entscheidung (Sportgericht, nach
                // DFB-Rechts- und Verfahrensordnung, siehe
                // StandingsTrait::gtCreditedScore()): 0 = keine, 1 = Heimteam
                // siegt, 2 = Gastteam siegt, 3 = beide Mannschaften verlieren.
                // BUGFIX (gefunden beim Ergänzen von gt_grund): die
                // Obergrenze hier stammte noch von vor der Einführung von
                // Option 3 ("beide verlieren") und hätte diese bislang
                // IMMER still auf 0 zurückgesetzt, nie tatsächlich
                // gespeichert - siehe CHANGELOG.md.
                $gt = (int)($_POST['gt_'.$pid] ?? 0);
                if ($gt < 0 || $gt > 3) { $gt = 0; }
                // Freier Zusatztext zur Grüne-Tisch-Entscheidung,
                // nur inhaltlich relevant wenn $gt > 0, aber
                // bewusst unabhängig davon gespeichert wie eingegeben (falls
                // ein zuvor gesetzter Grund stehen bleiben soll, wenn die
                // Entscheidung kurzzeitig auf "–" zurückgesetzt und wieder
                // gesetzt wird).
                $gtGrund = trim($_POST['gt_grund_'.$pid] ?? '');
                $gtGrundDb = $gtGrund !== '' ? $gtGrund : null;
                $stmtE->execute([$hv, $gv, $zeitDb, $status, $berichtDb, $gt, $gtGrundDb, $pid]);
            }
        }
        // "Letztes Speicherdatum" der Liga aktualisieren (siehe liga.datum) –
        // wird u.a. von den Mini-Addons (Minitabelle/Mininext) als
        // <!--ligaDatum--> angezeigt, analog zum Datei-Änderungsdatum im
        // alten LMO. Bewusst nur bei Ergebnis-Speicherungen berührt (nicht
        // bei jeder Einstellungsänderung), da das für die "wie aktuell ist
        // diese Tabelle"-Frage der Widgets am aussagekräftigsten ist.
        $db->prepare('UPDATE ' . tbl('liga') . ' SET datum=NOW() WHERE id=?')->execute([$lid]);
        flash(t('hl_flash_spieltag_saved', ['n' => $stNr]));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    // Weiter zum nächsten Spieltag?
    if (isset($_GET['next'])) {
        try {
            $db  = getDB();
            $sNx = $db->prepare('SELECT nummer FROM '.tbl('liga_spieltage').' WHERE liga_id=? AND nummer>? ORDER BY nummer LIMIT 1');
            $sNx->execute([$lid, $stNr]); $nextNr = $sNx->fetchColumn();
            if ($nextNr) { redirect('?action=spieltag&liga_id='.$lid.'&nr='.$nextNr); }
        } catch (Throwable) {}
    }
    redirect('?action=spieltag&liga_id='.$lid.'&nr='.$stNr);
}

// ── Team-Suche (AJAX) ─────────────────────────────────────────────────────────
if ($action === 'team_search' && isset($_GET['q'])) {
    requireLogin();
    header('Content-Type: application/json; charset=utf-8');
    $q = '%' . trim($_GET['q']) . '%';
    try {
        $s = getDB()->prepare(
            'SELECT id, name, mittel, kurz FROM '.tbl('teams_global').'
              WHERE name LIKE ? OR mittel LIKE ? OR kurz LIKE ?
              ORDER BY name LIMIT 20'
        );
        $s->execute([$q, $q, $q]);
        echo json_encode($s->fetchAll(), JSON_UNESCAPED_UNICODE);
    } catch (Throwable) { echo '[]'; }
    exit;
}

// ── Team-Nachschlagen per ID (AJAX) ────────────────────────────────────────────
// Für die direkte Team-ID-Eingabe im Liga-Detail-Team-Editor (Alternative zur
// Namenssuche, z.B. um eine bekannte Team-ID direkt zu verknüpfen).
if ($action === 'team_by_id' && isset($_GET['id'])) {
    requireLogin();
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)$_GET['id'];
    try {
        $s = getDB()->prepare('SELECT id, name, mittel, kurz FROM '.tbl('teams_global').' WHERE id=?');
        $s->execute([$id]);
        $team = $s->fetch();
        echo $team !== false ? json_encode($team, JSON_UNESCAPED_UNICODE) : 'null';
    } catch (Throwable) { echo 'null'; }
    exit;
}

// ── Team-Namen/-Kürzel/-Mittel bearbeiten / aus DB übernehmen ─────────────────
if ($action === 'save_team' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $tid      = (int)($_POST['team_id']    ?? 0);
    $lid      = (int)($_POST['liga_id']    ?? 0);
    $globalId = (int)($_POST['global_id']  ?? 0);
    $name     = trim($_POST['team_name']   ?? '');
    $mittel   = trim($_POST['team_mittel'] ?? '');
    $kurz     = trim($_POST['team_kurz']   ?? '');
    // Optionales Redirect-Ziel (z.B. zurück zum "Teams"-Tab der Liga-
    // Einstellungen statt zur Liga-Detailseite, siehe admin/view_liga_settings.php).
    // Nur eigene, relative "?action=..."-Ziele erlaubt (kein Open-Redirect).
    $redirectTarget = $_POST['redirect'] ?? '';
    $redirect = (is_string($redirectTarget) && str_starts_with($redirectTarget, '?action='))
        ? $redirectTarget
        : '?action=liga_detail&id=' . $lid;

    if ($tid <= 0 || $name === '') {
        flash(t('hl_flash_team_id_or_name_missing'), 'error');
        redirect($redirect);
    }
    try {
        $db = getDB();

        if ($globalId > 0 && $globalId !== $tid) {
            // ── Bestehendes Global-Team mit der Liga verknüpfen ───────────────
            // 1. Alte Verknüpfung entfernen
            $db->prepare('DELETE FROM '.tbl('liga_teams').' WHERE liga_id=? AND team_id=?')
               ->execute([$lid, $tid]);
            // 2. Neue Verknüpfung anlegen
            $db->prepare('INSERT IGNORE INTO '.tbl('liga_teams').' (liga_id, team_id) VALUES (?,?)')
               ->execute([$lid, $globalId]);
            // 3. Partien umschreiben
            foreach (['heim_id', 'gast_id'] as $col) {
                $db->prepare('UPDATE '.tbl('liga_partien').' p
                                JOIN '.tbl('liga_spieltage').' s ON s.id=p.spieltag_id
                               SET p.'.$col.'=? WHERE s.liga_id=? AND p.'.$col.'=?')
                   ->execute([$globalId, $lid, $tid]);
            }
            // 4. Platzhalter-Team löschen wenn nirgendwo mehr verwendet
            $cnt = $db->prepare('SELECT COUNT(*) FROM '.tbl('liga_teams').' WHERE team_id=?');
            $cnt->execute([$tid]);
            if ((int)$cnt->fetchColumn() === 0) {
                $db->prepare('DELETE FROM '.tbl('teams_global').' WHERE id=?')->execute([$tid]);
                doHook('team.deleted', ['team_id' => $tid]); // siehe Docblock bei delete_global_team weiter unten
            }
            flash(t('hl_flash_team_taken_from_db'));
        } else {
            // ── Normales Bearbeiten ───────────────────────────────────────────
            $db->prepare('UPDATE '.tbl('teams_global').' SET name=?, mittel=?, kurz=? WHERE id=?')
               ->execute([$name, $mittel, $kurz, $tid]);
            flash(t('hl_flash_team_updated'));
        }
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect($redirect);
}

// ── KO-Runden nachträglich anlegen (fehlende liga_spieltage-Einträge) ─────────
if ($action === 'fix_ko_rounds' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    if ($lid > 0) {
        try {
            $db = getDB();
            // Erwartete Rundenanzahl aus liga_options
            $sOpt = $db->prepare('SELECT option_value FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key="Rounds"');
            $sOpt->execute([$lid]); $totalRounds = (int)($sOpt->fetchColumn() ?: 0);
            // Vorhandene Runden
            $sEx = $db->prepare('SELECT nummer FROM '.tbl('liga_spieltage').' WHERE liga_id=? ORDER BY nummer');
            $sEx->execute([$lid]); $existing = array_column($sEx->fetchAll(), 'nummer');
            $existingSet = array_flip($existing);
            $added = 0;
            $stmtIns = $db->prepare('INSERT INTO '.tbl('liga_spieltage').' (liga_id, nummer, modus) VALUES (?,?,?)');
            for ($r = 1; $r <= $totalRounds; $r++) {
                if (!isset($existingSet[$r])) {
                    $stmtIns->execute([$lid, $r, KO_MODUS_DEFAULT]);
                    $added++;
                }
            }
            flash($added > 0 ? t('ko_flash_rounds_added', ['n' => $added]) : t('ko_flash_rounds_all_exist'));
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=liga_detail&id='.$lid);
}

// ── Spieltag-Startdatum bearbeiten ───────────────────────────────────────────
if ($action === 'save_spieltag_datum' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $stid  = (int)($_POST['spieltag_id'] ?? 0);
    $lid   = (int)($_POST['liga_id']     ?? 0);
    $datum = trim($_POST['start_datum']  ?? '');
    if ($stid > 0) {
        try {
            $dt = $datum !== '' ? $datum . ':00' : null;
            // Nur Datum-Teil übernehmen, Zeit auf 00:00:00
            if ($dt !== null) {
                $d = DateTime::createFromFormat('Y-m-d', substr($datum, 0, 10));
                $dt = $d ? $d->format('Y-m-d 00:00:00') : null;
            }
            getDB()->prepare('UPDATE '.tbl('liga_spieltage').' SET start=? WHERE id=?')
                   ->execute([$dt, $stid]);
            flash(t('hl_flash_spieltag_datum_saved'));
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=liga_detail&id='.$lid);
}

// ── Partie-Paarung (Heim/Gast) + Ergebnisse + Anstoßzeiten speichern ─────────
if ($action === 'save_partie_teams' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid  = (int)($_POST['liga_id']     ?? 0);
    $stNr = (int)($_POST['spieltag_nr'] ?? 0);
    try {
        $db   = getDB();
        // extra_data (Sätze bei Volleyball, siehe admin/view_spieltag.php)
        // ist eine on-demand-Spalte - hier defensiv geprüft wie an anderer
        // Stelle bereits etabliert, damit das Speichern auch auf einer noch
        // nicht migrierten Installation nicht mit "Unknown column" scheitert.
        $partienColsForSave = $db->query('SHOW COLUMNS FROM '.tbl('liga_partien'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('extra_data', $partienColsForSave, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `extra_data` JSON NULL DEFAULT NULL AFTER `g_tore`');
        }
        $stmtP = $db->prepare(
            'UPDATE '.tbl('liga_partien').' SET heim_id=?, gast_id=?, h_tore=?, g_tore=?, zeit=?, extra_data=?, status=?, bericht_url=?, gt_entscheidung=?, gt_grund=? WHERE id=?'
        );
        foreach ($_POST as $key => $val) {
            if (preg_match('/^heim_(\d+)$/', $key, $m)) {
                $pid  = (int)$m[1];
                $hid  = (int)($_POST['heim_'.$pid] ?? 0);
                $gid  = (int)($_POST['gast_'.$pid] ?? 0);
                if ($hid <= 0 || $gid <= 0 || $hid === $gid) { continue; }
                $hTore = isset($_POST['h_'.$pid]) && $_POST['h_'.$pid] !== '' ? (int)$_POST['h_'.$pid] : null;
                $gTore = isset($_POST['g_'.$pid]) && $_POST['g_'.$pid] !== '' ? (int)$_POST['g_'.$pid] : null;
                $zeit  = trim($_POST['at_'.$pid] ?? '');
                $zeitDb = $zeit !== '' ? str_replace('T', ' ', $zeit).':00' : null;

                // Sätze (Beitrag: Volleyball-Ergebniseingabe) - satz_h_<pid>[]
                // und satz_g_<pid>[] kommen paarweise aus dem Formular, leere
                // Paare (beide Felder leer) werden übersprungen, damit
                // ungenutzte Satz-Zeilen nicht als "0:0" gespeichert werden.
                $satzH = $_POST['satz_h_'.$pid] ?? [];
                $satzG = $_POST['satz_g_'.$pid] ?? [];
                $sets = [];
                if (is_array($satzH) && is_array($satzG)) {
                    foreach ($satzH as $i => $sh) {
                        $sg = $satzG[$i] ?? '';
                        if ($sh === '' && $sg === '') { continue; }
                        $sets[] = ['h' => (int)$sh, 'g' => (int)$sg];
                    }
                }
                $extraData = !empty($sets) ? json_encode(['sets' => $sets]) : null;

                // BUGFIX (gemeldet: Grüne-Tisch-Entscheidung wurde nach dem
                // Speichern immer wieder auf "–" zurückgesetzt, obwohl das
                // Ergebnis selbst korrekt gespeichert wurde): status_<pid>,
                // bericht_<pid> und gt_<pid> waren in diesem UPDATE komplett
                // vergessen worden - dieses Formular (view_spieltag.php,
                // reguläre Liga-Ansicht mit Team-Auswahl-Dropdowns) sendet an
                // die Aktion "save_partie_teams", NICHT an "save_ergebnisse"
                // (jener Handler existiert zwar noch im Code, wird aber von
                // KEINEM Formular mehr aufgerufen - toter Code, dort war die
                // gt_entscheidung-Verarbeitung bereits vorhanden, griff aber
                // nie). status und bericht_url fehlten hier schon vor der
                // Grüne-Tisch-Entscheidung und wurden bei der Gelegenheit
                // gleich mit ergänzt.
                $status = (int)($_POST['status_'.$pid] ?? 0);
                if ($status < 0 || $status > 2) { $status = 0; }
                $bericht = trim($_POST['bericht_'.$pid] ?? '');
                $berichtDb = $bericht !== '' ? $bericht : null;
                // BUGFIX (gefunden beim Ergänzen von gt_grund):
                // die Obergrenze hier stammte noch von vor der Einführung
                // von Option 3 ("beide Mannschaften verlieren", siehe
                // StandingsTrait.php) und hätte diese bislang IMMER still
                // auf 0 zurückgesetzt, nie tatsächlich gespeichert - dies
                // ist die tatsächlich vom Formular aufgerufene Stelle (siehe
                // Kommentar oben), der Bug betraf also die neue Option
                // wirklich, nicht nur den toten save_ergebnisse-Zweig.
                $gt = (int)($_POST['gt_'.$pid] ?? 0);
                if ($gt < 0 || $gt > 3) { $gt = 0; }
                // Freier Zusatztext zur Grüne-Tisch-Entscheidung - siehe
                // view_spieltag.php für das Formularfeld, das nur bei
                // ausgewählter Entscheidung eingeblendet wird.
                $gtGrund = trim($_POST['gt_grund_'.$pid] ?? '');
                $gtGrundDb = $gtGrund !== '' ? $gtGrund : null;

                $stmtP->execute([$hid, $gid, $hTore, $gTore, $zeitDb, $extraData, $status, $berichtDb, $gt, $gtGrundDb, $pid]);
            }
        }
        flash(t('hl_flash_spieltag_saved', ['n' => $stNr]));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=spieltag&liga_id='.$lid.'&nr='.$stNr);
}


// ── Liga löschen ─────────────────────────────────────────────────────────────
if ($action === 'delete_liga' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $id = (int)($_POST['liga_id'] ?? 0);
    if ($id > 0) {
        $db = getDB();
        try {
            // Namen vor dem Löschen merken, für einen aussagekräftigen Log-Eintrag.
            $sName = $db->prepare('SELECT name FROM '.tbl('liga').' WHERE id=?');
            $sName->execute([$id]);
            $ligaName = (string)($sName->fetchColumn() ?: ('ID '.$id));
            // Das Schema hat bewusst keine FOREIGN-KEY-Constraints (siehe
            // install.php) – ein reines "DELETE FROM liga" würde also
            // verwaiste Zeilen in liga_options/liga_teams/liga_team_values/
            // liga_spieltage/liga_partien zurücklassen. Deshalb hier explizit
            // in der richtigen Reihenfolge (Partien vor Spieltagen) mitlöschen.
            $db->beginTransaction();
            $db->prepare('DELETE FROM '.tbl('liga_partien').'
                WHERE spieltag_id IN (SELECT id FROM '.tbl('liga_spieltage').' WHERE liga_id=?)')
               ->execute([$id]);
            $db->prepare('DELETE FROM '.tbl('liga_spieltage').' WHERE liga_id=?')->execute([$id]);
            $db->prepare('DELETE FROM '.tbl('liga_team_values').' WHERE liga_id=?')->execute([$id]);
            $db->prepare('DELETE FROM '.tbl('liga_teams').' WHERE liga_id=?')->execute([$id]);
            $db->prepare('DELETE FROM '.tbl('liga_options').' WHERE liga_id=?')->execute([$id]);
            $db->prepare('DELETE FROM '.tbl('liga').' WHERE id=?')->execute([$id]);
            $db->commit();
            // Neuer Hook-Punkt (Beitrag: Integrationsprüfung eines
            // Drittanbieter-Addons, das sich auf dieses Event verlässt, ohne
            // dass es zuvor im Core existierte - siehe auch team.deleted,
            // dasselbe Muster). Erlaubt Addons, eigene, an die Liga gebundene
            // Daten zu bereinigen, statt verwaist in der Datenbank
            // zurückzubleiben. Bewusst ERST NACH dem commit() gefeuert (nicht
            // davor/innerhalb der Transaktion) - bei einem Rollback würde die
            // Liga ja gar nicht wirklich gelöscht, ein vorher gefeuerter Hook
            // hätte dann fälschlich schon Aufräumarbeiten eines Addons
            // ausgelöst.
            doHook('liga.deleted', ['liga_id' => $id, 'liga_name' => $ligaName]);
            logAdminAction('liga_deleted', $ligaName);
            flash(t('hl_flash_liga_deleted'));
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error');
        }
    }
    redirect(safeRedirectTarget($_POST['redirect'] ?? null, '?action=dashboard'));
}

// ── Globales Team bearbeiten ──────────────────────────────────────────────────
if ($action === 'save_global_team' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $tid    = (int)($_POST['team_id']    ?? 0);
    $name   = trim($_POST['team_name']   ?? '');
    $mittel = trim($_POST['team_mittel'] ?? '');
    $kurz   = trim($_POST['team_kurz']   ?? '');
    $url    = trim($_POST['team_url']    ?? '');
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url; // bequemer für den Admin, muss nicht jedes Mal "https://" mit eintippen
    }
    if ($tid > 0 && $name !== '') {
        try {
            ensureTeamUrlSchema();
            getDB()->prepare('UPDATE '.tbl('teams_global').' SET name=?, mittel=?, kurz=?, url=? WHERE id=?')
                   ->execute([$name, $mittel, $kurz, $url !== '' ? $url : null, $tid]);

            if (!empty($_FILES['team_logo']['name'])) {
                $logoResult = saveTeamLogoUpload($tid, $_FILES['team_logo']);
                if (!$logoResult['ok']) {
                    flash($logoResult['error'], 'error');
                    redirect('?action=teams');
                }
            }
            if (isset($_POST['remove_logo'])) {
                deleteTeamLogo($tid);
            }

            flash(t('hl_flash_team_updated'));
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=teams');
}

// ── Globales Team löschen ─────────────────────────────────────────────────────
if ($action === 'delete_global_team' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $tid = (int)($_POST['team_id'] ?? 0);
    if ($tid > 0) {
        try {
            $db = getDB();
            // Nur löschen wenn in keiner Liga mehr verwendet
            $cnt = $db->prepare('SELECT COUNT(*) FROM '.tbl('liga_teams').' WHERE team_id=?');
            $cnt->execute([$tid]);
            if ((int)$cnt->fetchColumn() === 0) {
                $db->prepare('DELETE FROM '.tbl('teams_global').' WHERE id=?')->execute([$tid]);
                // Neuer Hook-Punkt (Beitrag: Integrationsprüfung eines
                // Drittanbieter-Addons, das sich auf dieses Event verlässt,
                // ohne dass es zuvor im Core existierte) - erlaubt Addons,
                // eigene, an das Team gebundene Daten zu bereinigen (z.B.
                // Notizen, Zusatzfelder), statt verwaist in der Datenbank
                // zurückzubleiben. Analog zum bereits etablierten
                // Hook-Muster im Frontend (liga.saved etc.), hier erstmals
                // auch im Admin-Bereich genutzt - doHook() ist dort über
                // dieselbe global registrierte AddonManager-Instanz (siehe
                // admin.php) ebenso verfügbar.
                doHook('team.deleted', ['team_id' => $tid]);
                flash(t('hl_flash_team_deleted'));
            } else {
                flash(t('hl_flash_team_delete_blocked'), 'error');
            }
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=teams');
}

// ── Teams zusammenführen (Duplikate) ─────────────────────────────────────────
if ($action === 'merge_teams' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $keepId   = (int)($_POST['keep_id']   ?? 0);
    $deleteId = (int)($_POST['delete_id'] ?? 0);
    if ($keepId > 0 && $deleteId > 0 && $keepId !== $deleteId) {
        try {
            $db = getDB();
            $db->beginTransaction();
            // liga_teams: Verweise umbiegen (falls das Delete-Team in Ligen ist die Keep noch nicht hat)
            $db->prepare('UPDATE IGNORE '.tbl('liga_teams').' SET team_id=? WHERE team_id=?')
               ->execute([$keepId, $deleteId]);
            $db->prepare('DELETE FROM '.tbl('liga_teams').' WHERE team_id=?')
               ->execute([$deleteId]);
            // Partien umschreiben
            foreach (['heim_id','gast_id'] as $col) {
                $db->prepare('UPDATE '.tbl('liga_partien').' SET '.$col.'=? WHERE '.$col.'=?')
                   ->execute([$keepId, $deleteId]);
            }
            // Team-Values umschreiben
            $db->prepare('UPDATE IGNORE '.tbl('liga_team_values').' SET team_id=? WHERE team_id=?')
               ->execute([$keepId, $deleteId]);
            $db->prepare('DELETE FROM '.tbl('liga_team_values').' WHERE team_id=?')
               ->execute([$deleteId]);
            // Duplikat löschen
            $db->prepare('DELETE FROM '.tbl('teams_global').' WHERE id=?')
               ->execute([$deleteId]);
            $db->commit();
            // WICHTIG: Hook erst NACH dem commit() auslösen (nicht davor
            // oder innerhalb der Transaktion) - bei einem Rollback würde
            // das Team ja gar nicht wirklich gelöscht, ein vorher
            // gefeuerter Hook hätte dann fälschlich schon Aufräumarbeiten
            // eines Addons ausgelöst. Siehe auch delete_global_team weiter
            // unten für den vollständigen Hintergrund zu diesem Hook.
            doHook('team.deleted', ['team_id' => $deleteId]);
            flash(t('hl_flash_teams_merged'));
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
            flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error');
        }
    }
    redirect('?action=teams');
}

// ── Archiv-Ordner anlegen ─────────────────────────────────────────────────────
if ($action === 'save_archiv_folder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $id       = (int)($_POST['folder_id']    ?? 0);
    $parentId = (int)($_POST['parent_id']    ?? 0) ?: null;
    $name     = trim($_POST['folder_name']   ?? '');
    $beschr   = trim($_POST['folder_beschr'] ?? '');
    $sort     = (int)($_POST['folder_sort']  ?? 0);
    if ($name === '') { flash(t('hl_flash_name_required'), 'error'); redirect('?action=archiv'); }
    try {
        $db = getDB();
        if ($id > 0) {
            $db->prepare('UPDATE '.tbl('liga_archiv_folders').' SET parent_id=?,name=?,beschreibung=?,sort=? WHERE id=?')
               ->execute([$parentId, $name, $beschr, $sort, $id]);
            flash(t('hl_flash_folder_updated'));
        } else {
            $db->prepare('INSERT INTO '.tbl('liga_archiv_folders').' (parent_id,name,beschreibung,sort) VALUES (?,?,?,?)')
               ->execute([$parentId, $name, $beschr, $sort]);
            flash(t('hl_flash_folder_created'));
        }
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=archiv');
}

// ── Archiv-Ordner löschen ─────────────────────────────────────────────────────
if ($action === 'delete_archiv_folder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $id = (int)($_POST['folder_id'] ?? 0);
    if ($id > 0) {
        try {
            $db = getDB();
            // Ligen aus Ordner herauslösen
            $db->prepare('UPDATE '.tbl('liga').' SET archiv_folder_id=NULL WHERE archiv_folder_id=?')->execute([$id]);
            // Unterordner nach oben verschieben
            $parentId = $db->prepare('SELECT parent_id FROM '.tbl('liga_archiv_folders').' WHERE id=?');
            $parentId->execute([$id]); $pid = $parentId->fetchColumn() ?: null;
            $db->prepare('UPDATE '.tbl('liga_archiv_folders').' SET parent_id=? WHERE parent_id=?')->execute([$pid, $id]);
            $db->prepare('DELETE FROM '.tbl('liga_archiv_folders').' WHERE id=?')->execute([$id]);
            flash(t('hl_flash_folder_deleted'));
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=archiv');
}

// ── Liga ins Archiv verschieben / zurückholen ─────────────────────────────────
if ($action === 'move_liga_archiv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $ligaId   = (int)($_POST['liga_id']    ?? 0);
    $folderId = (int)($_POST['folder_id']  ?? 0) ?: null;
    if ($ligaId > 0) {
        try {
            getDB()->prepare('UPDATE '.tbl('liga').' SET archiv_folder_id=? WHERE id=?')
                   ->execute([$folderId, $ligaId]);
            flash($folderId ? t('hl_flash_liga_archived') : t('hl_flash_liga_unarchived'));
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect(safeRedirectTarget($_POST['redirect'] ?? null, '?action=archiv'));
}

// ── Mehrere Ligen ins Archiv verschieben ──────────────────────────────────────
if ($action === 'bulk_archiv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $folderId = (int)($_POST['folder_id'] ?? 0) ?: null;
    $ligaIds  = array_map('intval', (array)($_POST['liga_ids'] ?? []));
    $ligaIds  = array_filter($ligaIds, fn($id) => $id > 0);
    if (empty($ligaIds)) {
        flash(t('hl_flash_no_ligen_selected'), 'error');
        redirect('?action=dashboard');
    }
    try {
        $db   = getDB();
        $phs  = implode(',', array_fill(0, count($ligaIds), '?'));
        $db->prepare('UPDATE '.tbl('liga').' SET archiv_folder_id=? WHERE id IN ('.$phs.')')
           ->execute([$folderId, ...$ligaIds]);
        flash(count($ligaIds) === 1 ? t('hl_flash_bulk_archived_one') : t('hl_flash_bulk_archived_many', ['n' => count($ligaIds)]));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=dashboard');
}

// ── Team-Ligen AJAX ───────────────────────────────────────────────────────────
if ($action === 'team_ligen' && isset($_GET['team_id'])) {
    requireLogin();
    header('Content-Type: application/json; charset=utf-8');
    $tid = (int)$_GET['team_id'];
    try {
        $s = getDB()->prepare(
            'SELECT l.id, l.name, lo.option_value AS type
               FROM '.tbl('liga').' l
               JOIN '.tbl('liga_teams').' lt ON lt.liga_id = l.id
               LEFT JOIN '.tbl('liga_options').' lo ON lo.liga_id = l.id AND lo.option_key = "Type"
              WHERE lt.team_id = ?
              ORDER BY l.name'
        );
        $s->execute([$tid]);
        echo json_encode($s->fetchAll(), JSON_UNESCAPED_UNICODE);
    } catch (Throwable) { echo '[]'; }
    exit;
}

// ── Alle Teams für Merge-Suche (AJAX) ────────────────────────────────────────
if ($action === 'team_search_all') {
    requireLogin();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $s = getDB()->query(
            'SELECT id, name, mittel FROM '.tbl('teams_global').' ORDER BY name'
        );
        $teams = $s->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(array_values(array_map(fn($t) => [
            'id'     => (int)$t['id'],
            'name'   => $t['name'],
            'mittel' => $t['mittel'] ?? '',
        ], $teams)), JSON_UNESCAPED_UNICODE);
    } catch (Throwable) { echo '[]'; }
    exit;
}

// ── Team-Verknüpfungen (Umbenennung/Fusion/Abspaltung, siehe bootstrap.php) ──
if ($action === 'team_links_for') {
    requireLogin();
    header('Content-Type: application/json; charset=utf-8');
    $teamId = (int)($_GET['team_id'] ?? 0);
    $links = $teamId > 0 ? getTeamLinksForTeam($teamId) : [];
    echo json_encode(array_values(array_map(fn($l) => [
        'id'         => (int)$l['id'],
        'type'       => $l['type'],
        'note'       => $l['note'] ?? '',
        'other_id'   => (int)$l['other_id'],
        'other_name' => $l['other_name'],
    ], $links)), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'add_team_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $teamAId = (int)($_POST['team_a_id'] ?? 0);
    $teamBId = (int)($_POST['team_b_id'] ?? 0);
    $type    = trim($_POST['type'] ?? 'sonstige');
    $note    = trim($_POST['note'] ?? '');
    $newerChoice = $_POST['newer_team_choice'] ?? '';
    $newerTeamId = match ($newerChoice) {
        'a'     => $teamAId,
        'b'     => $teamBId,
        default => null,
    };
    if (addTeamLink($teamAId, $teamBId, $type, $note, $newerTeamId)) {
        flash(t('hl_flash_team_link_added'));
    } else {
        flash(t('hl_flash_team_link_failed'), 'error');
    }
    redirect('?action=teams');
}

if ($action === 'delete_team_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $linkId = (int)($_POST['link_id'] ?? 0);
    deleteTeamLink($linkId);
    flash(t('hl_flash_team_link_deleted'));
    redirect('?action=teams');
}

if ($action === 'set_team_link_direction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    header('Content-Type: application/json; charset=utf-8');
    $linkId = (int)($_POST['link_id'] ?? 0);
    $newerTeamId = isset($_POST['newer_team_id']) && $_POST['newer_team_id'] !== ''
        ? (int)$_POST['newer_team_id']
        : null;
    $ok = setTeamLinkDirection($linkId, $newerTeamId);
    echo json_encode(['ok' => $ok]);
    exit;
}
