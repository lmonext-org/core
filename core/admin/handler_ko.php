<?php
/**
 * Project: LMOnext
 * Filename: handler_ko.php
 * Fileversion: 1.3.2
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── KO-Team-Picker: echtes Team oder Platzhalter-Label ───────────────────────
function renderKoTeamPicker(int $pIdx, string $slot, int $teamId, string $label, array $allTeams): string {
    $isPlaceholder = ($teamId === 0);
    $selStyle = 'flex:1;min-width:140px;background:var(--bg);border:1px solid var(--border);'
              . 'color:var(--text);border-radius:var(--radius);padding:5px 8px;font-size:.85rem';
    $inpStyle = 'flex:1;min-width:140px;background:var(--bg);border:1px solid var(--yellow);'
              . 'color:var(--text);border-radius:var(--radius);padding:5px 8px;font-size:.85rem';
    $toggleStyle = 'background:none;border:1px solid var(--border);color:var(--muted);'
                 . 'border-radius:var(--radius);padding:3px 7px;font-size:.75rem;cursor:pointer;'
                 . 'white-space:nowrap;flex-shrink:0';

    $sel  = '<select name="'.$slot.'_'.$pIdx.'" id="sel-'.$slot.'-'.$pIdx.'"'
           . ($isPlaceholder ? ' style="'.$selStyle.';display:none"' : ' style="'.$selStyle.'"').'>';
    $sel .= '<option value="0">'.h(t('sp_option_placeholder')).'</option>';
    foreach ($allTeams as $t) {
        $sel .= '<option value="'.(int)$t['id'].'"'.((int)$t['id']===$teamId?' selected':'').'>'.h($t['name']).'</option>';
    }
    $sel .= '</select>';

    $inp = '<input type="text" name="'.$slot.'_label_'.$pIdx.'" id="lbl-'.$slot.'-'.$pIdx.'"'
         . ' value="'.h($label).'" placeholder="'.h(t('sp_placeholder_example_winner')).'"'
         . ($isPlaceholder ? ' style="'.$inpStyle.'"' : ' style="'.$inpStyle.';display:none"').'>';

    $btn = '<button type="button" title="'.h(t('sp_tooltip_toggle_team_placeholder')).'" style="'.$toggleStyle.'"'
         . ' onclick="koTogglePicker(\''.h($slot).'\','.$pIdx.')">'
         . ($isPlaceholder ? h(t('sp_btn_team_short')) : h(t('sp_btn_placeholder_short'))).'</button>';

    $hid = $isPlaceholder
        ? '<input type="hidden" name="'.$slot.'_'.$pIdx.'" value="0">'
        : '';

    return $sel.$inp.$btn;
}

/**
 * Gibt zurück ob Team A (true) oder Team B (false) in Spiel $s Heimrecht hat.
 *
 * Playoff-Modi (LMO-Quelltext):
 *   0 = 1-1-1-...      A,B,A,B,A,B,A  (alternierend, A beginnt)
 *   1 = 2-2-1           A,A,B,B,A      (Best-of-5)
 *   2 = 2-2-1-1-1       A,A,B,B,A,B,A  (Best-of-7)
 *   3 = 2-3-2           A,A,B,B,B,A,A  (Best-of-7)
 *
 * Bei Modus 2 (Hin+Rück) und Modus 1 (Einzelspiel) wird playoffmode ignoriert.
 */
function koHeimatTeamA(int $spielNr, int $rundeModus, int $playoffMode): bool {
    // Einzelspiel oder Hin-Rückspiel: festes Schema
    if ($rundeModus <= 2) {
        return $spielNr === 1; // Spiel 1 immer A:Heim, Spiel 2 B:Heim (Rückspiel)
    }

    // Best-of: Heimrechtschema je nach playoffMode
    // $spielNr ist 1-basiert
    switch ($playoffMode) {
        case 1: // 2-2-1 (Best-of-5: A,A,B,B,A)
            return in_array($spielNr, [1, 2, 5], true);

        case 2: // 2-2-1-1-1 (Best-of-7: A,A,B,B,A,B,A)
            return in_array($spielNr, [1, 2, 5, 7], true);

        case 3: // 2-3-2 (Best-of-7: A,A,B,B,B,A,A)
            return in_array($spielNr, [1, 2, 6, 7], true);

        default: // 0 = 1-1-1-... (alternierend, A beginnt)
            return $spielNr % 2 === 1;
    }
}


$action = $_GET['action'] ?? 'dashboard';

// ── KO-Runde: Partien komplett neu setzen (löschen + einfügen) ───────────────
if ($action === 'save_ko_runde' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid  = (int)($_POST['liga_id']     ?? 0);
    $stNr = (int)($_POST['spieltag_nr'] ?? 0);
    $stid = (int)($_POST['spieltag_id'] ?? 0);
    if ($stid <= 0 || $lid <= 0) {
        flash(t('ko_flash_invalid_round_id'), 'error');
        redirect('?action=spieltag&liga_id='.$lid.'&nr='.$stNr);
    }
    try {
        $db  = getDB();
        $mod = (int)($_POST['runde_modus'] ?? KO_MODUS_DEFAULT);
        if (!isset(KO_MODUS[$mod])) { $mod = KO_MODUS_DEFAULT; }

        // Playoff-Modus aus liga_options lesen
        $sOpt = $db->prepare('SELECT option_value FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key="playoffmode"');
        $sOpt->execute([$lid]);
        $playoffMode = (int)($sOpt->fetchColumn() ?: 0);

        $db->beginTransaction();

        $db->prepare('UPDATE '.tbl('liga_spieltage').' SET modus=? WHERE id=?')->execute([$mod, $stid]);
        $db->prepare('DELETE FROM '.tbl('liga_partien').' WHERE spieltag_id=?')->execute([$stid]);

        $stmt = $db->prepare(
            'INSERT INTO '.tbl('liga_partien').'
             (spieltag_id, heim_id, gast_id, heim_label, gast_label, h_tore, g_tore, zeit, notiz, status, bericht_url, spiel_nr)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );

        $paarungCount  = (int)($_POST['pair_count'] ?? 0);
        $insertedTotal = 0;

        for ($i = 0; $i < $paarungCount; $i++) {
            $hid    = (int)($_POST['heim_'.$i] ?? 0);
            $gid    = (int)($_POST['gast_'.$i] ?? 0);
            $hLabel = trim($_POST['heim_label_'.$i] ?? '');
            $gLabel = trim($_POST['gast_label_'.$i] ?? '');

            $hValid = $hid > 0 || $hLabel !== '';
            $gValid = $gid > 0 || $gLabel !== '';
            if (!$hValid || !$gValid) { continue; }
            if ($hid > 0 && $gid > 0 && $hid === $gid) { continue; }

            $hIdDb    = $hid > 0 ? $hid : null;
            $gIdDb    = $gid > 0 ? $gid : null;
            $hLabelDb = $hid > 0 ? null : ($hLabel !== '' ? $hLabel : null);
            $gLabelDb = $gid > 0 ? null : ($gLabel !== '' ? $gLabel : null);

            $paarungNr = $i + 1;

            for ($s = 1; $s <= $mod; $s++) {
                // Heimrecht nach playoffMode bestimmen
                $aHatHeim = koHeimatTeamA($s, $mod, $playoffMode);

                if ($aHatHeim) {
                    $spielHId = $hIdDb;    $spielGId = $gIdDb;
                    $spielHL  = $hLabelDb; $spielGL  = $gLabelDb;
                } else {
                    $spielHId = $gIdDb;    $spielGId = $hIdDb;
                    $spielHL  = $gLabelDb; $spielGL  = $hLabelDb;
                }

                $hTore  = isset($_POST['h_'.$i.'_'.$s]) && $_POST['h_'.$i.'_'.$s] !== ''
                    ? (int)$_POST['h_'.$i.'_'.$s] : null;
                $gTore  = isset($_POST['g_'.$i.'_'.$s]) && $_POST['g_'.$i.'_'.$s] !== ''
                    ? (int)$_POST['g_'.$i.'_'.$s] : null;
                $zeit   = trim($_POST['at_'.$i.'_'.$s] ?? '');
                $zeitDb = $zeit !== '' ? str_replace('T', ' ', $zeit).':00' : null;

                $status  = (int)($_POST['status_'.$i.'_'.$s] ?? 0);
                if ($status < 0 || $status > 2) { $status = 0; }
                $bericht = trim($_POST['bericht_'.$i.'_'.$s] ?? '');
                $berichtDb = $bericht !== '' ? $bericht : null;
                $notiz   = trim($_POST['notiz_'.$i.'_'.$s] ?? '');
                $notizDb = $notiz !== '' ? $notiz : null;

                $stmt->execute([$stid, $spielHId, $spielGId, $spielHL, $spielGL,
                                $hTore, $gTore, $zeitDb, $notizDb, $status, $berichtDb, $paarungNr.'_'.$s]);
                $insertedTotal++;
            }
        }

        $db->commit();
        flash(t('ko_flash_round_saved', ['nr' => $stNr, 'pairs' => $paarungCount, 'matches' => $insertedTotal]));
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
        flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error');
    }
    redirect('?action=spieltag&liga_id='.$lid.'&nr='.$stNr);
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

