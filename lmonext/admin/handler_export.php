<?php
/**
 * Project: LMOnext
 * Filename: handler_export.php
 * Fileversion: 1.2.4
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Export: .l98 generieren und direkt senden ─────────────────────────────────
if ($action === 'export' && isset($_GET['liga_id'])) {
    requireLogin();
    $lid = (int)$_GET['liga_id'];
    $db  = getDB();

    $liga = $db->prepare('SELECT * FROM '.tbl('liga').' WHERE id=?');
    $liga->execute([$lid]); $liga = $liga->fetch();
    if (!$liga) { flash(t('exp_flash_liga_not_found'), 'error'); redirect('?action=dashboard'); }

    // Options
    $sOpt = $db->prepare('SELECT option_key,option_value FROM '.tbl('liga_options').' WHERE liga_id=?');
    $sOpt->execute([$lid]); $opts = array_column($sOpt->fetchAll(), 'option_value', 'option_key');

    // Teams
    $sT = $db->prepare(
        'SELECT g.id,g.name,g.kurz,g.mittel FROM '.tbl('teams_global').' g
          JOIN '.tbl('liga_teams').' lt ON lt.team_id=g.id
         WHERE lt.liga_id=? ORDER BY g.id'
    );
    $sT->execute([$lid]); $teams = $sT->fetchAll();
    $teamIdx = []; // db_id → 1-basierter l98-Index
    foreach ($teams as $i => $t) { $teamIdx[$t['id']] = $i + 1; }

    // Spieltage + Partien
    $sST = $db->prepare('SELECT * FROM '.tbl('liga_spieltage').' WHERE liga_id=? ORDER BY nummer');
    $sST->execute([$lid]); $spieltage = $sST->fetchAll();

    $sP = $db->prepare('SELECT * FROM '.tbl('liga_partien').' WHERE spieltag_id=? ORDER BY spiel_nr');

    // Team-Values
    $sTV = $db->prepare('SELECT team_id,key_name,key_value FROM '.tbl('liga_team_values').' WHERE liga_id=?');
    $sTV->execute([$lid]); $tvRaw = $sTV->fetchAll();
    $teamValues = [];
    foreach ($tvRaw as $tv) { $teamValues[$tv['team_id']][$tv['key_name']] = $tv['key_value']; }

    // .l98 zusammenbauen
    $nTeams   = count($teams);
    $nRounds  = count($spieltage);
    $nMatches = $nRounds > 0 ? (int)$db->prepare('SELECT COUNT(*) FROM '.tbl('liga_partien').' WHERE spieltag_id=?')->execute([$spieltage[0]['id']]) : 0;
    // Korrekte Matches-Anzahl
    if ($nRounds > 0) {
        $sM = $db->prepare('SELECT COUNT(*) FROM '.tbl('liga_partien').' WHERE spieltag_id=?');
        $sM->execute([$spieltage[0]['id']]); $nMatches = (int)$sM->fetchColumn();
    }

    $lines   = [];
    $lines[] = '[Options]';
    $lines[] = 'Title=Liga Manager Online 4';
    $lines[] = 'Name='.$liga['name'];
    $lines[] = 'Type='.($opts['Type'] ?? '0');
    $lines[] = 'Teams='.$nTeams;
    // Standard-Options
    $stdKeys = ['goalfaktor','pointsfaktor','enableGameSort','Rounds','Matches','Actual',
        'Kegel','HandS','PointsForWin','PointsForDraw','PointsForLost','Spez','HideDraw',
        'OnRun','MinusPoints','Direct','Champ','CL','CK','UC','AR','AB','namePkt','nameTor',
        'tableHinRueck','tableHeimAusw','DatC','DatS','DatM','DatF','urlT','urlB','stats',
        'Plan','Ergebnis','mittore','favTeam','selTeam','ticker','icon','Graph','Kreuz',
        'Tabelle','Ligastats','kurve1','kurve2','KlFin','playdown','playoffmode'];
    // Rounds/Matches aus echten Daten
    $opts['Rounds']  = (string)$nRounds;
    $opts['Matches'] = (string)$nMatches;
    foreach ($stdKeys as $k) {
        if (isset($opts[$k]) && $k !== 'Name' && $k !== 'Type' && $k !== 'Teams') {
            $lines[] = $k.'='.$opts[$k];
        }
    }
    $lines[] = '';

    // Teams-Sektionen
    $lines[] = '[Teams]';
    foreach ($teams as $i => $t) { $lines[] = ($i+1).'='.$t['name']; }
    $lines[] = '';
    $lines[] = '[Teamm]';
    foreach ($teams as $i => $t) { $lines[] = ($i+1).'='.$t['mittel']; }
    $lines[] = '';
    $lines[] = '[Teamk]';
    foreach ($teams as $i => $t) { $lines[] = ($i+1).'='.$t['kurz']; }
    $lines[] = '';

    // Team-Value-Sektionen
    foreach ($teams as $i => $t) {
        $tv = $teamValues[$t['id']] ?? [];
        if (!empty($tv)) {
            $lines[] = '[Team'.($i+1).']';
            foreach ($tv as $k => $v) { $lines[] = $k.'='.$v; }
            $lines[] = '';
        }
    }

    // Tickertext aus liga_options (zeilenweise als N0, N1, ...)
    $tickerLines = isset($opts['tickertext']) && $opts['tickertext'] !== ''
        ? explode("\n", $opts['tickertext']) : [];
    $lines[] = '[News]';
    $lines[] = 'NC='.count($tickerLines);
    foreach ($tickerLines as $i => $line) { $lines[] = 'N'.$i.'='.$line; }
    $lines[] = '';

    $isKOExport = ((int)($opts['Type'] ?? 0)) === 1;

    // Runden
    foreach ($spieltage as $st) {
        $rNr        = (int)$st['nummer'];
        $roundModus = (int)($st['modus'] ?? ($isKOExport ? KO_MODUS_DEFAULT : 0));
        $lines[]    = '[Round'.$rNr.']';
        if ($st['start']) {
            $d = new DateTime($st['start']);
            $lines[] = 'D1='.$d->format('d.m.Y');
        }

        $sP->execute([$st['id']]); $partien = $sP->fetchAll();

        if ($isKOExport) {
            // ── KO-Format: TAp/TBp/GApS/GBpS ────────────────────────────────
            $lines[] = 'MO='.$roundModus;

            // Partien nach Paarung gruppieren (spiel_nr = "P_S")
            $paarMap = [];
            foreach ($partien as $p) {
                $nr = $p['spiel_nr'];
                if (str_contains($nr, '_')) {
                    [$pNr, $sNr] = explode('_', $nr, 2);
                } else {
                    $pNr = $nr; $sNr = '1';
                }
                $paarMap[(int)$pNr][(int)$sNr] = $p;
            }
            ksort($paarMap);

            foreach ($paarMap as $pNr => $spiele) {
                ksort($spiele);
                // Erstes Spiel enthält Original-Heim/Gast der Paarung
                $erstSpiel = reset($spiele);
                $hIdx = $teamIdx[$erstSpiel['heim_id']] ?? 0;
                $gIdx = $teamIdx[$erstSpiel['gast_id']] ?? 0;
                // Bei Modus 2: Spiel 1 = Hinspiel (original), Spiel 2 = Rückspiel (getauscht)
                // → für TAp/TBp das Original-Heim/Gast wiederherstellen
                if ($roundModus === 2 && isset($spiele[2])) {
                    // Spiel 1 hat Original-Heim als heim_id
                    $hIdx = $teamIdx[$spiele[1]['heim_id']] ?? $hIdx;
                    $gIdx = $teamIdx[$spiele[1]['gast_id']] ?? $gIdx;
                } elseif ($roundModus > 2 && isset($spiele[1])) {
                    $hIdx = $teamIdx[$spiele[1]['heim_id']] ?? $hIdx;
                    $gIdx = $teamIdx[$spiele[1]['gast_id']] ?? $gIdx;
                }
                if (!$hIdx || !$gIdx) { continue; }

                $lines[] = 'TA'.$pNr.'='.$hIdx;
                $lines[] = 'TB'.$pNr.'='.$gIdx;

                foreach ($spiele as $sNr => $sp) {
                    $key = $pNr.$sNr;
                    // Tore: -1 wenn nicht gespielt
                    $ga = $sp['h_tore'] !== null ? $sp['h_tore'] : -1;
                    $gb = $sp['g_tore'] !== null ? $sp['g_tore'] : -1;
                    $lines[] = 'GA'.$key.'='.$ga;
                    $lines[] = 'GB'.$key.'='.$gb;
                    $lines[] = 'SP'.$key.'='.((int)($sp['status'] ?? 0));
                    $lines[] = 'NT'.$key.'='.($sp['notiz'] ?? '');
                    $lines[] = 'BE'.$key.'='.($sp['bericht_url'] ?? '');
                    if ($sp['zeit']) {
                        $dt = new DateTime($sp['zeit']);
                        $lines[] = 'AT'.$key.'='.$dt->getTimestamp();
                    }
                }
            }
        } else {
            // ── Liga-Format: klassisch ────────────────────────────────────────
            if ($roundModus > 0) { $lines[] = 'SM='.$roundModus; }
            $pIdx = 1;
            foreach ($partien as $p) {
                $hIdx = $teamIdx[$p['heim_id']] ?? 0;
                $gIdx = $teamIdx[$p['gast_id']] ?? 0;
                if (!$hIdx || !$gIdx) { continue; }
                $lines[] = 'TA'.$pIdx.'='.$hIdx;
                $lines[] = 'TB'.$pIdx.'='.$gIdx;
                if ($p['h_tore'] !== null) { $lines[] = 'GA'.$pIdx.'='.$p['h_tore']; }
                if ($p['g_tore'] !== null) { $lines[] = 'GB'.$pIdx.'='.$p['g_tore']; }
                if ($p['zeit']) {
                    $dt = new DateTime($p['zeit']);
                    $lines[] = 'AT'.$pIdx.'='.$dt->getTimestamp();
                }
                $lines[] = 'NT'.$pIdx.'='.($p['notiz'] ?? '');
                $lines[] = 'SP'.$pIdx.'='.((int)($p['status'] ?? 0));
                $lines[] = 'BE'.$pIdx.'='.($p['bericht_url'] ?? '');
                $pIdx++;
            }
        }
        $lines[] = '';
    }

    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $liga['name']).'.l98';
    $content  = implode("\r\n", $lines);
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.strlen($content));
    echo $content;
    exit;
}

