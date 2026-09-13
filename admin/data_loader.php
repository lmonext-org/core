<?php
/**
 * Project: LMOnext
 * Filename: data_loader.php
 * Fileversion: 1.8.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Daten laden ───────────────────────────────────────────────────────────────
$flash = getFlash();
// "reset_password" (Landingpage aus der Reset-E-Mail) muss wie "login" ohne
// bestehende Session erreichbar sein – genau dafür ist die Funktion ja da.
if ($action !== 'login' && $action !== 'reset_password') { requireLogin(); }

$ligen = []; $users = []; $ligaDetail = null;
$spieltagData = null; $tabelleData = null; $spielerstatData = null;

if (isLoggedIn()) {
    ensureArchivColumns();
    ensureSpielstatusColumns();
    ensureSportProfileColumns();
    ensureAdminSettings();
    ensureKoLabelColumns();
    // Zeitzone global setzen – gilt für alle date()-Aufrufe und den Import
    date_default_timezone_set(getAdminSetting('timezone', 'Europe/Berlin'));
    try {
        $db    = getDB();
        $ligen = $db->query('SELECT id,name,datum FROM '.tbl('liga').' WHERE archiv_folder_id IS NULL ORDER BY datum DESC')->fetchAll();
        if ($action === 'users') {
            ensurePasswordResetSchema(); // stellt sicher, dass admin_users.email existiert
            $users = $db->query('SELECT id,username,email,last_login FROM '.tbl('admin_users').' ORDER BY username')->fetchAll();
            if (($_GET['tab'] ?? 'userverwaltung') === 'log') {
                ensureAuditLogTable();
                $logPage    = max(1, (int)($_GET['logpage'] ?? 1));
                $logPerPage = 50;
                $logTotal   = (int)$db->query('SELECT COUNT(*) FROM '.tbl('admin_audit_log'))->fetchColumn();
                $sLog = $db->prepare('SELECT username,action,details,ip,created_at FROM '.tbl('admin_audit_log').'
                                       ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
                $sLog->bindValue(1, $logPerPage, PDO::PARAM_INT);
                $sLog->bindValue(2, ($logPage - 1) * $logPerPage, PDO::PARAM_INT);
                $sLog->execute();
                $auditLog = $sLog->fetchAll();
            }
        }
        if ($action === 'liga_detail' && isset($_GET['id'])) {
            $lid = (int)$_GET['id'];
            $s = $db->prepare('SELECT * FROM '.tbl('liga').' WHERE id=?');
            $s->execute([$lid]); $ligaDetail['liga'] = $s->fetch();
            $s2 = $db->prepare('SELECT g.id,g.name,g.kurz,g.mittel FROM '.tbl('teams_global').' g JOIN '.tbl('liga_teams').' lt ON lt.team_id=g.id WHERE lt.liga_id=? ORDER BY g.name');
            $s2->execute([$lid]); $ligaDetail['teams'] = $s2->fetchAll();
            $s3 = $db->prepare('SELECT s.id,s.nummer,s.start,COUNT(p.id) AS partie_count,SUM(CASE WHEN p.h_tore IS NOT NULL THEN 1 ELSE 0 END) AS gespielt FROM '.tbl('liga_spieltage').' s LEFT JOIN '.tbl('liga_partien').' p ON p.spieltag_id=s.id WHERE s.liga_id=? GROUP BY s.id,s.nummer,s.start ORDER BY s.nummer');
            $s3->execute([$lid]); $ligaDetail['spieltage'] = $s3->fetchAll();
            $s4 = $db->prepare('SELECT option_key,option_value FROM '.tbl('liga_options').' WHERE liga_id=?');
            $s4->execute([$lid]); $ligaDetail['options'] = array_column($s4->fetchAll(), null, 'option_key');
        }
        // Spielerstatistik-Verwaltung (spielerstat_lib.php wird bereits durch
        // AddonManager::bootAdmin() geladen, sofern das "player"-Addon aktiv ist)
        if ($action === 'spielerstatistik' && function_exists('addonManager') && addonManager()->isEnabled('player') && isset($_GET['liga_id'])) {
            $lid = (int)$_GET['liga_id'];
            $sLiga = $db->prepare('SELECT id,name FROM '.tbl('liga').' WHERE id=?');
            $sLiga->execute([$lid]);
            $sTeams = $db->prepare(
                'SELECT tg.name FROM '.tbl('liga_teams').' lt
                   JOIN '.tbl('teams_global').' tg ON tg.id = lt.team_id
                  WHERE lt.liga_id = ? ORDER BY tg.name'
            );
            $sTeams->execute([$lid]);
            $spielerstatData = [
                'liga_id' => $lid,
                'liga'    => $sLiga->fetch(),
                'spalten' => getSpielerstatSpalten($lid),
                'spieler' => getSpielerstatSpieler($lid),
                'config'  => getSpielerstatConfig($lid),
                'teams'   => array_column($sTeams->fetchAll(), 'name'),
            ];
        }
        // Spieltag-Ergebnisse
        if ($action === 'spieltag' && isset($_GET['liga_id'])) {
            $lid  = (int)$_GET['liga_id'];
            $stNr = (int)($_GET['nr'] ?? 1);
            $sLiga = $db->prepare('SELECT * FROM '.tbl('liga').' WHERE id=?');
            $sLiga->execute([$lid]); $spieltagData['liga'] = $sLiga->fetch();
            // Ticker-Optionen laden (liga-weit)
            $sTicker = $db->prepare('SELECT option_key,option_value FROM '.tbl('liga_options').'
                WHERE liga_id=? AND option_key IN ("ticker","tickertext")');
            $sTicker->execute([$lid]);
            $tickerOpts = array_column($sTicker->fetchAll(), 'option_value', 'option_key');
            $spieltagData['ticker']     = ($tickerOpts['ticker']     ?? '0') === '1';
            $spieltagData['tickertext'] = $tickerOpts['tickertext'] ?? '';
            $sST = $db->prepare('SELECT * FROM '.tbl('liga_spieltage').' WHERE liga_id=? AND nummer=?');
            $sST->execute([$lid, $stNr]); $spieltagData['spieltag'] = $sST->fetch();
            if ($spieltagData['spieltag']) {
                $sP = $db->prepare(
                    'SELECT p.*,
                            h.name AS heim_name, h.kurz AS heim_kurz,
                            g.name AS gast_name, g.kurz AS gast_kurz
                       FROM '.tbl('liga_partien').' p
                       LEFT JOIN '.tbl('teams_global').' h ON h.id=p.heim_id
                       LEFT JOIN '.tbl('teams_global').' g ON g.id=p.gast_id
                      WHERE p.spieltag_id=?
                      ORDER BY p.spiel_nr'
                );
                $sP->execute([$spieltagData['spieltag']['id']]);
                $rawPartien = $sP->fetchAll();
                $spieltagData['partien'] = $rawPartien;

                // Paarungen gruppieren: spiel_nr "P_S" → paarung P, spiel S
                // Für KO-View: gruppiert nach erstem Team-Paar (heim_id/gast_id der ersten Partie)
                $paarungen = [];
                foreach ($rawPartien as $p) {
                    $nr = $p['spiel_nr'];
                    if (str_contains($nr, '_')) {
                        [$pNr, $sNr] = explode('_', $nr, 2);
                        $paarungen[(int)$pNr][(int)$sNr] = $p;
                    } else {
                        // Altes Format (nur Zahl): als Paarung N, Spiel 1
                        $paarungen[(int)$nr][1] = $p;
                    }
                }
                ksort($paarungen);
                foreach ($paarungen as &$sp) { ksort($sp); }
                unset($sp);
                $spieltagData['paarungen'] = $paarungen;
            }
            // Alle Spieltage für Navigation
            $sAll = $db->prepare('SELECT nummer FROM '.tbl('liga_spieltage').' WHERE liga_id=? ORDER BY nummer');
            $sAll->execute([$lid]); $spieltagData['alle'] = array_column($sAll->fetchAll(), 'nummer');
            // Liga-Typ + KlFin + Gesamtrunden VOR prevWinners laden
            $sOpt = $db->prepare('SELECT option_value FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key="Type"');
            $sOpt->execute([$lid]); $spieltagData['liga_type'] = (int)($sOpt->fetchColumn() ?: 0);
            $sKlFin = $db->prepare('SELECT option_value FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key="KlFin"');
            $sKlFin->execute([$lid]); $spieltagData['kl_fin'] = ($sKlFin->fetchColumn() ?: '0') === '1';
            $sTot = $db->prepare('SELECT COUNT(*) FROM '.tbl('liga_spieltage').' WHERE liga_id=?');
            $sTot->execute([$lid]); $spieltagData['total_rounds'] = (int)$sTot->fetchColumn();
            // Alle Teams der Liga für Paarungs-Dropdowns
            $sT = $db->prepare('SELECT g.id,g.name FROM '.tbl('teams_global').' g JOIN '.tbl('liga_teams').' lt ON lt.team_id=g.id WHERE lt.liga_id=? ORDER BY g.name');
            $sT->execute([$lid]); $spieltagData['teams'] = $sT->fetchAll();

            // ── KO: Vorrundensieger für gefilterten Team-Dropdown ─────────────
            $spieltagData['prevWinners'] = null;
            if ($spieltagData['liga_type'] === 1 && $stNr > 1) {
                $sPrev = $db->prepare('SELECT id,modus FROM '.tbl('liga_spieltage').' WHERE liga_id=? AND nummer=?');
                $sPrev->execute([$lid, $stNr - 1]);
                $prevST = $sPrev->fetch();
                $prevStid = $prevST ? (int)$prevST['id'] : null;

                if ($prevStid) {
                    // Alle Partien der Vorrunde holen (inkl. noch nicht gespielter)
                    $sPP = $db->prepare(
                        'SELECT heim_id, gast_id, h_tore, g_tore, spiel_nr
                           FROM '.tbl('liga_partien').'
                          WHERE spieltag_id=?
                            AND heim_id IS NOT NULL AND gast_id IS NOT NULL
                            AND heim_id != gast_id
                          ORDER BY spiel_nr'
                    );
                    $sPP->execute([$prevStid]);
                    $prevPartien = $sPP->fetchAll();

                    // Dummy-Team ID ermitteln (soll nie als Sieger gelten)
                    $dummyId = 0;
                    try {
                        $sd = $db->prepare('SELECT id FROM '.tbl('teams_global').' WHERE name=?');
                        $sd->execute(['___']); $dummyId = (int)($sd->fetchColumn() ?: 0);
                    } catch (Throwable) {}

                    if (!empty($prevPartien)) {
                        // Partien nach Paarungsnummer gruppieren (spiel_nr = "P_S" → P ist Paarung)
                        $paarungen = [];
                        foreach ($prevPartien as $p) {
                            $nr   = $p['spiel_nr']; // z.B. "3_1" oder "3_2"
                            $pNr  = explode('_', $nr)[0] ?? $nr; // Paarungs-Nr
                            $paarungen[$pNr][] = $p;
                        }

                        $winnerIds = [];
                        $loserIds  = [];
                        foreach ($paarungen as $parts) {
                            // Prüfen ob alle Spiele dieser Paarung Ergebnisse haben
                            $allPlayed = true;
                            foreach ($parts as $p) {
                                $hT = $p['h_tore']; $gT = $p['g_tore'];
                                // Nicht gespielt: NULL oder -1 (LMO-Legacy)
                                if ($hT === null || $gT === null || (int)$hT === -1 || (int)$gT === -1) {
                                    $allPlayed = false; break;
                                }
                            }
                            if (!$allPlayed) { continue; } // Unvollständig → kein Filter

                            // Erste Partie: Team A = heim, Team B = gast
                            $teamA = (int)$parts[0]['heim_id'];
                            $teamB = (int)$parts[0]['gast_id'];

                            // Überspringen wenn Dummy-Team involviert
                            if ($teamA === $dummyId || $teamB === $dummyId) { continue; }

                            // Gesamttore aufsummieren (Heimrecht wechselt bei HR – korrekt durch DB-Werte)
                            // Bei Hin+Rückspiel: Tore für Team A aus beiden Partien addieren
                            $torA = 0; $torB = 0;
                            foreach ($parts as $p) {
                                $hId = (int)$p['heim_id'];
                                $gId = (int)$p['gast_id'];
                                $hT  = (int)$p['h_tore'];
                                $gT  = (int)$p['g_tore'];
                                if ($hId === $teamA) { $torA += $hT; $torB += $gT; }
                                else                 { $torA += $gT; $torB += $hT; }
                            }

                            if ($torA > $torB)      { $winnerIds[$teamA] = true; $loserIds[$teamB] = true; }
                            elseif ($torB > $torA)  { $winnerIds[$teamB] = true; $loserIds[$teamA] = true; }
                            else {
                                // Gleichstand → beide behalten (Elfmeter/Verlängerung noch offen)
                                $winnerIds[$teamA] = true;
                                $winnerIds[$teamB] = true;
                            }
                        }

                        // Ausnahme: Ist dies die letzte Runde UND gibt es ein "Spiel um
                        // Platz 3" (KlFin), werden dort die VERLIERER der Vorrunde
                        // (Halbfinale) gebraucht, nicht die Sieger – die stehen ja
                        // schon im Finale. Beide Runden (Finale + Spiel um Platz 3)
                        // teilen sich dieselbe Spieltag-Nummer, daher hier einfach
                        // Sieger UND Verlierer gemeinsam zur Auswahl anbieten.
                        $includeIds = $winnerIds;
                        if ($spieltagData['kl_fin'] && $stNr === $spieltagData['total_rounds']) {
                            $includeIds += $loserIds;
                        }

                        // Nur filtern wenn alle Paarungen vollständig ausgewertet werden konnten
                        if (!empty($includeIds)) {
                            $phs = implode(',', array_fill(0, count($includeIds), '?'));
                            $sW  = $db->prepare(
                                'SELECT id,name FROM '.tbl('teams_global').'
                                  WHERE id IN ('.$phs.') ORDER BY name'
                            );
                            $sW->execute(array_keys($includeIds));
                            $spieltagData['prevWinners'] = $sW->fetchAll();
                        }
                    }
                }
            }
            // (liga_type, kl_fin, total_rounds bereits oben geladen)
        }
        // Tabelle berechnen
        if ($action === 'tabelle' && isset($_GET['liga_id'])) {
            $lid = (int)$_GET['liga_id'];
            $sLiga = $db->prepare('SELECT * FROM '.tbl('liga').' WHERE id=?');
            $sLiga->execute([$lid]); $tabelleData['liga'] = $sLiga->fetch();
            $sOpt = $db->prepare('SELECT option_key,option_value FROM '.tbl('liga_options').' WHERE liga_id=?');
            $sOpt->execute([$lid]); $opts = array_column($sOpt->fetchAll(), 'option_value', 'option_key');
            $tabelleData['opts'] = $opts;
            $ptW = (int)($opts['PointsForWin']  ?? 3);
            $ptD = (int)($opts['PointsForDraw'] ?? 1);
            $ptL = (int)($opts['PointsForLost'] ?? 0);
            // Alle Teams der Liga
            $sT = $db->prepare('SELECT g.id,g.name,g.kurz FROM '.tbl('teams_global').' g JOIN '.tbl('liga_teams').' lt ON lt.team_id=g.id WHERE lt.liga_id=? ORDER BY g.name');
            $sT->execute([$lid]); $teams = $sT->fetchAll();
            $tabelle = [];
            foreach ($teams as $t) {
                $tabelle[$t['id']] = [
                    'id'=>$t['id'],'name'=>$t['name'],'kurz'=>$t['kurz'],
                    'sp'=>0,'g'=>0,'u'=>0,'v'=>0,'tore_h'=>0,'tore_g'=>0,'pkt'=>0
                ];
            }
            // Alle gespielten Partien
            $sP = $db->prepare(
                'SELECT p.heim_id,p.gast_id,p.h_tore,p.g_tore
                   FROM '.tbl('liga_partien').' p
                   JOIN '.tbl('liga_spieltage').' s ON s.id=p.spieltag_id
                  WHERE s.liga_id=? AND p.h_tore IS NOT NULL AND p.g_tore IS NOT NULL'
            );
            $sP->execute([$lid]);
            foreach ($sP->fetchAll() as $p) {
                $h = $p['heim_id']; $g = $p['gast_id'];
                $gh = (int)$p['h_tore']; $gg = (int)$p['g_tore'];
                if (!isset($tabelle[$h]) || !isset($tabelle[$g])) { continue; }
                $tabelle[$h]['sp']++; $tabelle[$g]['sp']++;
                $tabelle[$h]['tore_h'] += $gh; $tabelle[$h]['tore_g'] += $gg;
                $tabelle[$g]['tore_h'] += $gg; $tabelle[$g]['tore_g'] += $gh;
                if ($gh > $gg) {
                    $tabelle[$h]['g']++; $tabelle[$h]['pkt'] += $ptW;
                    $tabelle[$g]['v']++; $tabelle[$g]['pkt'] += $ptL;
                } elseif ($gh === $gg) {
                    $tabelle[$h]['u']++; $tabelle[$h]['pkt'] += $ptD;
                    $tabelle[$g]['u']++; $tabelle[$g]['pkt'] += $ptD;
                } else {
                    $tabelle[$g]['g']++; $tabelle[$g]['pkt'] += $ptW;
                    $tabelle[$h]['v']++; $tabelle[$h]['pkt'] += $ptL;
                }
            }
            // Sortierung: Punkte → Tordifferenz → Tore
            usort($tabelle, function($a, $b) {
                if ($b['pkt'] !== $a['pkt']) { return $b['pkt'] - $a['pkt']; }
                $da = $a['tore_h'] - $a['tore_g']; $db2 = $b['tore_h'] - $b['tore_g'];
                if ($db2 !== $da) { return $db2 - $da; }
                return $b['tore_h'] - $a['tore_h'];
            });
            $tabelleData['tabelle'] = $tabelle;
        }
    } catch (Throwable $e) { $flash = ['msg' => 'DB-Fehler: '.$e->getMessage(), 'type' => 'error']; }
}

$nav = [
    'dashboard'   => ['icon' => '⚽', 'label' => t('nav_dashboard')],
    'create_liga' => ['icon' => '➕', 'label' => t('nav_create_liga')],
    'import'      => ['icon' => '📥', 'label' => t('nav_import')],
    'archiv'      => ['icon' => '🗄️',  'label' => t('nav_archiv')],
    'teams'       => ['icon' => '👥', 'label' => t('nav_teams')],
    'users'       => ['icon' => '👤', 'label' => t('nav_users')],
    'wartung'     => ['icon' => '🛠️', 'label' => t('nav_wartung')],
    'settings'    => ['icon' => '⚙️', 'label' => t('nav_settings')],
];

// ── Addon-Navigation dynamisch ueber AddonManager mergen ─────────────────────
// Addons registrieren ihre Nav-Einträge ueber addon.json (admin_nav), z.B.
// "Tippspiel" (tipp-Addon) oder "Spielerstatistik" (player-Addon). Der
// AddonManager sammelt alle aktiven Einträge und sortiert nach 'position'.
if (function_exists('addonManager')) {
    foreach (addonManager()->getNavItems() as $navAction => $navItem) {
        if (!isset($nav[$navAction])) {
            $nav[$navAction] = [
                'icon'  => $navItem['icon'],
                'label' => t($navItem['label_key']),
            ];
        }
    }
}

$ligaSettingsData = null;
if ($action === 'liga_settings' && isLoggedIn()) {
    $lid = (int)($_GET['id'] ?? 0);
    if ($lid > 0) {
        try {
            $db = getDB();
            $sL = $db->prepare('SELECT id,name,sport_type FROM '.tbl('liga').' WHERE id=?');
            $sL->execute([$lid]); $ligaSettingsData['liga'] = $sL->fetch();
            $sO = $db->prepare('SELECT option_key,option_value FROM '.tbl('liga_options').' WHERE liga_id=?');
            $sO->execute([$lid]);
            $ligaSettingsData['opts'] = array_column($sO->fetchAll(), 'option_value', 'option_key');
            $sT = $db->prepare('SELECT g.id, g.name, g.kurz, g.mittel FROM '.tbl('teams_global').' g JOIN '.tbl('liga_teams').' lt ON lt.team_id=g.id WHERE lt.liga_id=? ORDER BY g.name');
            $sT->execute([$lid]);
            $ligaSettingsData['teams'] = $sT->fetchAll();
            $ligaSettingsData['lid']  = $lid;

            // Strafpunkte/Straftore je Team (siehe admin/handler_settings.php,
            // case 'strafen') - Tabelle existiert evtl. noch nicht auf älteren
            // Installationen, daher eigener try/catch statt den äußeren
            // abzubrechen. Migration für "tore_korrektur" (erzielte Tore) läuft
            // hier ZUSÄTZLICH zum Save-Handler, damit bereits gespeicherte
            // Strafpunkte/Straftore beim bloßen Ansehen des Tabs nicht
            // scheinbar verschwinden, bevor einmal gespeichert wurde.
            try {
                $strafCols = $db->query('SHOW COLUMNS FROM '.tbl('liga_strafpunkte'))->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('tore_korrektur', $strafCols, true)) {
                    $db->exec('ALTER TABLE '.tbl('liga_strafpunkte').' ADD COLUMN `tore_korrektur` INT NOT NULL DEFAULT 0 AFTER `straftore`');
                }
                if (!in_array('minuspunkte_korrektur', $strafCols, true)) {
                    $db->exec('ALTER TABLE '.tbl('liga_strafpunkte').' ADD COLUMN `minuspunkte_korrektur` INT NOT NULL DEFAULT 0 AFTER `tore_korrektur`');
                }
                // "ab Spieltag" (Beitrag: Torsten Hofmann) - siehe
                // src/Liga/StandingsTrait.php für die Anwendung beim Berechnen.
                if (!in_array('ab_spieltag', $strafCols, true)) {
                    $db->exec('ALTER TABLE '.tbl('liga_strafpunkte').' ADD COLUMN `ab_spieltag` INT NOT NULL DEFAULT 0 AFTER `minuspunkte_korrektur`');
                }
                $sS = $db->prepare('SELECT team_id, strafpunkte, straftore, tore_korrektur, minuspunkte_korrektur, ab_spieltag, grund FROM '.tbl('liga_strafpunkte').' WHERE liga_id=?');
                $sS->execute([$lid]);
                $ligaSettingsData['strafen'] = array_column($sS->fetchAll(), null, 'team_id');
            } catch (Throwable) {
                $ligaSettingsData['strafen'] = [];
            }
        } catch (Throwable) {}
    }
}

$teamsData = null;
if ($action === 'teams' && isLoggedIn()) {
    ensureTeamUrlSchema();
    try {
        $db = getDB();
        // Alle Teams mit Anzahl Ligen-Verwendungen
        $sT = $db->query(
            'SELECT g.id, g.name, g.mittel, g.kurz, g.url,
                    COUNT(DISTINCT lt.liga_id) AS liga_count
               FROM '.tbl('teams_global').' g
               LEFT JOIN '.tbl('liga_teams').' lt ON lt.team_id = g.id
              GROUP BY g.id
              ORDER BY g.name'
        );
        $teamsData['teams'] = $sT->fetchAll();

        // Anzahl Team-Verknüpfungen je Team (siehe admin/bootstrap.php,
        // team_links). Tabelle existiert evtl. noch nicht (Lazy-Erstellung
        // beim ersten Anlegen einer Verknüpfung) - dann bleibt die Zuordnung
        // einfach leer, kein Fehler.
        $linkCounts = [];
        try {
            $sLinks = $db->query(
                'SELECT team_a_id AS tid, COUNT(*) AS cnt FROM '.tbl('team_links').' GROUP BY team_a_id
                 UNION ALL
                 SELECT team_b_id AS tid, COUNT(*) AS cnt FROM '.tbl('team_links').' GROUP BY team_b_id'
            );
            foreach ($sLinks->fetchAll() as $row) {
                $tid = (int)$row['tid'];
                $linkCounts[$tid] = ($linkCounts[$tid] ?? 0) + (int)$row['cnt'];
            }
        } catch (Throwable) {
            // team_links existiert noch nicht - $linkCounts bleibt leer
        }
        foreach ($teamsData['teams'] as &$teamRow) {
            $teamRow['link_count'] = $linkCounts[(int)$teamRow['id']] ?? 0;
        }
        unset($teamRow);

        // Umlaut-Normalisierung für Duplikat-Vergleich
        $normalizeStr = function(string $s): string {
            // mbstring ist auf Shared-Hosting nicht garantiert (siehe Projektkonvention) –
            // ohne die Erweiterung reicht strtolower() + eine kleine Ersetzung der
            // deutschen Großbuchstaben-Umlaute, da die eigentliche Umlaut-Normalisierung
            // ohnehin gleich im Anschluss über str_replace() passiert.
            $s = function_exists('mb_strtolower')
                ? mb_strtolower(trim($s))
                : strtolower(strtr(trim($s), ['Ä' => 'ä', 'Ö' => 'ö', 'Ü' => 'ü']));
            $s = str_replace(['ä','ö','ü','ß','à','á','â','è','é','ê','ì','í','î','ò','ó','ô','ù','ú','û','ñ','ç'],
                             ['ae','oe','ue','ss','a','a','a','e','e','e','i','i','i','o','o','o','u','u','u','n','c'], $s);
            return $s;
        };

        // Duplikate erkennen: gleicher normalisierter Name oder Mittelname
        // Schritt 1: DB-seitige exakte Duplikate (case-insensitive)
        $dupNames  = [];
        $dupMittel = [];

        // Schritt 2: PHP-seitig Umlaut-Varianten gruppieren
        $normNameMap  = []; // normalisierter Name → [ids]
        $normMittelMap = []; // normalisierter Mittelname → [ids]

        foreach ($teamsData['teams'] as $t) {
            $normN = $normalizeStr($t['name']);
            $normNameMap[$normN][] = (int)$t['id'];

            if ($t['mittel'] !== '') {
                $normM = $normalizeStr($t['mittel']);
                $normMittelMap[$normM][] = (int)$t['id'];
            }
        }

        // Alle IDs die in einer Gruppe mit >1 Einträgen sind → Duplikat
        $dupIds = [];
        foreach ($normNameMap as $norm => $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) { $dupIds[$id] = 'name'; }
            }
        }
        foreach ($normMittelMap as $norm => $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    if (!isset($dupIds[$id])) { $dupIds[$id] = 'mittel'; }
                }
            }
        }

        $teamsData['dup_ids']   = $dupIds;      // id → 'name'|'mittel'
        // Rückwärtskompatibilität: dup_names/dup_mittel für view_teams.php
        $teamsData['dup_names']  = [];
        $teamsData['dup_mittel'] = [];
        foreach ($normNameMap as $norm => $ids) {
            if (count($ids) > 1) { $teamsData['dup_names'][$norm] = true; }
        }
        foreach ($normMittelMap as $norm => $ids) {
            if (count($ids) > 1) { $teamsData['dup_mittel'][$norm] = true; }
        }
    } catch (Throwable) { $teamsData = ['teams'=>[],'dup_names'=>[],'dup_mittel'=>[]]; }
}


$archivData = null;
if ($action === 'archiv' && isLoggedIn()) {
    try {
        $db = getDB();
        // Alle Ordner
        $folders = $db->query('SELECT * FROM '.tbl('liga_archiv_folders').' ORDER BY sort,name')->fetchAll();
        // Alle archivierten Ligen
        $archivLigen = $db->query(
            'SELECT l.*, lo.option_value AS liga_type
               FROM '.tbl('liga').' l
               LEFT JOIN '.tbl('liga_options').' lo ON lo.liga_id=l.id AND lo.option_key="Type"
              WHERE l.archiv_folder_id IS NOT NULL
              ORDER BY l.name'
        )->fetchAll();

        // Offene Partien pro Liga (nicht gespielt: h_tore NULL oder -1)
        $archivOffen = [];
        if (!empty($archivLigen)) {
            $ids = implode(',', array_column($archivLigen, 'id'));
            $sO  = $db->query(
                'SELECT s.liga_id, COUNT(*) AS offen
                   FROM '.tbl('liga_spieltage').' s
                   JOIN '.tbl('liga_partien').' p ON p.spieltag_id = s.id
                  WHERE s.liga_id IN ('.$ids.')
                    AND (p.h_tore IS NULL OR p.h_tore = -1)
                    AND p.heim_id != p.gast_id
                  GROUP BY s.liga_id'
            );
            foreach ($sO->fetchAll() as $r) { $archivOffen[(int)$r['liga_id']] = (int)$r['offen']; }
        }

        $archivData = [
            'folders'     => $folders,
            'archivLigen' => $archivLigen,
            'folderMap'   => array_column($folders, null, 'id'),
            'offen'       => $archivOffen,
        ];
    } catch (Throwable) { $archivData = ['folders'=>[],'archivLigen'=>[],'folderMap'=>[],'offen'=>[]]; }
}


$wizStep    = $_GET['step'] ?? '1';
$wizStepInt = ($wizStep === '1b') ? 1 : (int)$wizStep;
$wiz     = $_SESSION['wiz'] ?? null;

$pageTitle = match($action) {
    'dashboard'    => t('title_dashboard'),
    'create_liga'  => t('title_create_liga', ['step' => $wizStepInt]),
    'liga_detail'  => t('title_liga_detail'),
    'spieltag'     => t('title_spieltag'),
    'tabelle'      => t('title_tabelle'),
    'import'       => t('title_import'),
    'import_review'=> t('title_import_review'),
    'archiv'       => t('title_archiv'),
    'teams'        => t('nav_teams'),
    'tippspiel'    => t('nav_tippspiel'),
    'users'        => t('title_users'),
    'liga_settings'=> t('title_liga_settings'),
    'settings'     => t('title_settings'),
    'wartung'      => t('nav_wartung'),
    default        => t('title_admin_default'),
};

