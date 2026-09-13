<?php
/**
 * Project: LMOnext
 * Filename: handler_settings.php
 * Fileversion: 1.6.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Liga-Einstellungen speichern ─────────────────────────────────────────────
if ($action === 'save_liga_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    $tab = $_POST['tab'] ?? 'grundwerte';
    if ($lid <= 0) { flash(t('ls_flash_invalid_id'), 'error'); redirect('?action=liga_settings&id='.$lid.'&tab='.$tab); }
    try {
        $db   = getDB();
        $stmt = $db->prepare('INSERT INTO '.tbl('liga_options').' (liga_id,option_key,option_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value)');
        $save = function(string $key, string $val) use ($stmt, $lid) { $stmt->execute([$lid, $key, $val]); };

        switch ($tab) {
            case 'grundwerte':
                $save('Name',         trim($_POST['liga_name']  ?? ''));
                $save('namePkt',      trim($_POST['namePkt']    ?? 'Punkte'));
                $save('nameTor',      trim($_POST['nameTor']    ?? 'Tore'));
                $save('goalfaktor',   trim($_POST['goalfaktor'] ?? '1'));
                $save('pointsfaktor', trim($_POST['pointsfaktor'] ?? '1'));
                $save('favTeam',      trim($_POST['favTeam']    ?? '0'));
                $save('selTeam',      trim($_POST['selTeam']    ?? '0'));
                // Liga-Namen auch in liga-Tabelle aktualisieren
                $name = trim($_POST['liga_name'] ?? '');
                if ($name !== '') {
                    $db->prepare('UPDATE '.tbl('liga').' SET name=? WHERE id=?')->execute([$name, $lid]);
                }
                // Sportart speichern (Beitrag: Torsten Hofmann, in liga-Tabelle,
                // nicht liga_options - siehe getLigaSportType() in LigaRepositoryTrait.php)
                $sportType = trim($_POST['sport_type'] ?? 'football');
                if (!in_array($sportType, ['football', 'volleyball', 'icehockey', 'basketball', 'handball', 'badminton'], true)) {
                    $sportType = 'football';
                }
                $db->prepare('UPDATE '.tbl('liga').' SET sport_type=? WHERE id=?')->execute([$sportType, $lid]);
                // Standard-Punktekonfiguration aus dem Sport-Profil übernehmen,
                // aber nur, wenn noch keine Werte gesetzt sind (nicht überschreiben!).
                // Eigenständige Abfrage statt LigaService::getLigaOptions() - der
                // Adminbereich bindet die Liga-Trait-Kette bewusst nicht ein
                // (siehe admin/bootstrap.php, "Tipps nachtragen"-Feature).
                $existingKeys = $db->prepare('SELECT option_key FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key IN ("PointsForWin","PointsForDraw")');
                $existingKeys->execute([$lid]);
                if (count($existingKeys->fetchAll()) === 0) {
                    $profile = \LMOnext\Sport\SportRegistry::get($sportType);
                    foreach ($profile->getDefaultPointsConfig() as $k => $v) {
                        $save($k, (string)$v);
                    }
                }
                break;

            case 'anzeige':
                $save('DatS',       isset($_POST['DatS'])       ? '1' : '0');
                $save('DatM',       isset($_POST['DatM'])       ? '1' : '0');
                $save('DatC',       isset($_POST['DatC'])       ? '1' : '0');
                $save('Kalender',   isset($_POST['Kalender'])   ? '1' : '0');
                $save('DatF',       trim($_POST['DatF']         ?? 'd.m.Y H:i'));
                $save('Actual',     trim($_POST['Actual']       ?? '1'));
                $save('Ergebnis',   isset($_POST['Ergebnis'])   ? '1' : '0');
                $save('ShowSpielfrei', isset($_POST['ShowSpielfrei']) ? '1' : '0');
                $save('Plan',       isset($_POST['Plan'])       ? '1' : '0');
                $save('Tabelle',    isset($_POST['Tabelle'])    ? '1' : '0');
                $save('ShowLogos',  isset($_POST['ShowLogos'])  ? '1' : '0');
                $save('Kreuz',      isset($_POST['Kreuz'])      ? '1' : '0');
                // "stats" (Spielerstatistik-Anzeige) nur speichern, wenn das
                // player-Addon installiert ist - die Checkbox ist sonst in
                // view_liga_settings.php ausgeblendet (siehe dortiger
                // Changelog-Eintrag) und würde hier sonst bei JEDEM Speichern
                // dieses Tabs unbemerkt auf '0' zurückgesetzt, selbst wenn
                // ein per .l98-Import mitgebrachter Wert '1' erhalten bleiben
                // sollte (z.B. weil das Addon später nachinstalliert wird).
                if (function_exists('addonManager') && addonManager()->isEnabled('player')) {
                    $save('stats', isset($_POST['stats']) ? '1' : '0');
                }
                $save('Ligastats',  isset($_POST['Ligastats'])  ? '1' : '0');
                $save('kurve1',     isset($_POST['kurve1'])     ? '1' : '0');
                $save('kurve2',     isset($_POST['kurve2'])     ? '1' : '0');
                $save('ticker',     isset($_POST['ticker'])     ? '1' : '0');
                $save('tickertext', trim($_POST['tickertext']   ?? ''));
                $save('urlT',       isset($_POST['urlT'])       ? '1' : '0');
                $save('urlB',       isset($_POST['urlB'])       ? '1' : '0');
                // KO-spezifisch
                $save('KlFin',      isset($_POST['KlFin'])      ? '1' : '0');
                $save('playdown',   isset($_POST['playdown'])   ? '1' : '0');
                $save('playoffmode', trim($_POST['playoffmode'] ?? '0'));
                break;

            case 'spielsystem':
                $save('MinusPoints',  isset($_POST['MinusPoints'])  ? '1' : '0');
                $save('OnRun',        isset($_POST['OnRun'])        ? '1' : '0');
                $save('HideDraw',     isset($_POST['HideDraw'])     ? '1' : '0');
                $save('Direct',       isset($_POST['Direct'])       ? '1' : '0');
                $save('Spez',         isset($_POST['Spez'])         ? '1' : '0');
                $save('enableGameSort',isset($_POST['enableGameSort'])? '1' : '0');
                $save('PointsForWin',  trim($_POST['PointsForWin']  ?? '3'));
                $save('PointsForDraw', trim($_POST['PointsForDraw'] ?? '1'));
                $save('PointsForLost', trim($_POST['PointsForLost'] ?? '0'));
                // Eigene Punktwerte "nach Verlängerung" (ET) und "nach
                // Elfmeterschießen" (PS), analog zum alten LMO. Frühere
                // Versionen missbrauchten hierfür versehentlich goalfaktor/
                // pointsfaktor – dieselben Schlüssel, die der Grundwerte-Tab
                // für die Dezimalstellen-Anzeige nutzt, wodurch sich beide
                // Tabs beim Speichern gegenseitig überschrieben haben.
                $save('PointsForWinET',  trim($_POST['PointsForWinET']  ?? $_POST['PointsForWin']  ?? '3'));
                $save('PointsForDrawET', trim($_POST['PointsForDrawET'] ?? $_POST['PointsForDraw'] ?? '1'));
                $save('PointsForLostET', trim($_POST['PointsForLostET'] ?? $_POST['PointsForLost'] ?? '0'));
                $save('PointsForWinPS',  trim($_POST['PointsForWinPS']  ?? $_POST['PointsForWin']  ?? '3'));
                $save('PointsForDrawPS', trim($_POST['PointsForDrawPS'] ?? $_POST['PointsForDraw'] ?? '1'));
                $save('PointsForLostPS', trim($_POST['PointsForLostPS'] ?? $_POST['PointsForLost'] ?? '0'));
                $save('Kegel',         isset($_POST['Kegel'])       ? '1' : '0');
                $save('HandS',         isset($_POST['HandS'])       ? '1' : '0');
                break;

            case 'tabelle':
                $save('tableHinRueck',  isset($_POST['tableHinRueck'])  ? '1' : '0');
                $save('tableHeimAusw',  isset($_POST['tableHeimAusw'])  ? '1' : '0');
                // Bugfix: Checkbox im Formular heißt "Champ_enabled" (nicht "Champ") –
                // $_POST['Champ'] existierte nie, wodurch dieser Haken bislang immer
                // stillschweigend als "0" gespeichert wurde, unabhängig von der Auswahl.
                $save('Champ',    isset($_POST['Champ_enabled']) ? '1' : '0');
                $save('CL',       trim($_POST['CL']       ?? '0'));
                $save('CK',       trim($_POST['CK']       ?? '0'));
                $save('UC',       trim($_POST['UC']       ?? '0'));
                $save('AR',       trim($_POST['AR']       ?? '0'));
                $save('AB',       trim($_POST['AB']       ?? '0'));
                // Randfarben der Tabellenmarkierungen (siehe computeStandingsMarkerColor()
                // in frontend/data_liga.php). Nur speichern, wenn es ein gültiger
                // #rrggbb-Hexwert ist (so wie ihn <input type="color"> liefert).
                foreach (['Champ', 'CL', 'CK', 'UC', 'AR', 'AB'] as $mk) {
                    $colVal = trim($_POST[$mk . 'Color'] ?? '');
                    if (preg_match('/^#[0-9a-fA-F]{6}$/', $colVal)) {
                        $save($mk . 'Color', $colVal);
                    }
                }
                break;

            case 'ticker':
                $save('ticker',     $_POST['ticker'] === '1' ? '1' : '0');
                $save('tickertext', trim($_POST['tickertext'] ?? ''));
                break;

            case 'spieltage':
                $save('Rounds',  trim($_POST['Rounds']  ?? '0'));
                $save('Matches', trim($_POST['Matches'] ?? '0'));
                break;

            case 'strafen':
                // Eigener Zweig, NICHT über den liga_options-$save()-Helper -
                // Strafpunkte/Straftore/Tore-Korrektur leben pro Liga+Team in
                // einer eigenen Tabelle (liga_strafpunkte), damit eine
                // Korrektur gezielt nur in genau der Liga wirkt, in der sie
                // eingetragen wurde - nicht global für das Team über alle
                // Ligen/Saisons hinweg. Alle drei Werte sind vorzeichenbehaftet
                // (positiv = Bonus, negativ = Strafe/Abzug).
                //
                // Bewusst eigenständige SQL statt eines Aufrufs von
                // setLigaStrafpunkte() aus frontend/data_liga.php: der
                // Adminbereich bindet frontend/data_liga.php nirgends ein
                // (baut $opts z.B. selbst per Direkt-SQL, siehe
                // admin/data_loader.php) - dieselbe Eigenständigkeit wird hier
                // fortgeführt, siehe auch StandingsTrait::ensureStrafpunkteSchema()
                // für die frontend-seitige Variante derselben Tabelle.
                $db->exec('CREATE TABLE IF NOT EXISTS ' . tbl('liga_strafpunkte') . ' (
                    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
                    `liga_id`               INT NOT NULL,
                    `team_id`               INT NOT NULL,
                    `strafpunkte`           INT NOT NULL DEFAULT 0,
                    `straftore`             INT NOT NULL DEFAULT 0,
                    `tore_korrektur`        INT NOT NULL DEFAULT 0,
                    `minuspunkte_korrektur` INT NOT NULL DEFAULT 0,
                    `ab_spieltag`           INT NOT NULL DEFAULT 0,
                    `grund`                 VARCHAR(255) NULL DEFAULT NULL,
                    `updated_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `liga_team` (`liga_id`, `team_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                // Migration für Installationen, die diese Tabelle schon vor der
                // "erzielte Tore"/"Minuspunkte"/"ab Spieltag"-Erweiterung angelegt hatten
                $strafCols = $db->query('SHOW COLUMNS FROM ' . tbl('liga_strafpunkte'))->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('tore_korrektur', $strafCols, true)) {
                    $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `tore_korrektur` INT NOT NULL DEFAULT 0 AFTER `straftore`');
                }
                if (!in_array('minuspunkte_korrektur', $strafCols, true)) {
                    $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `minuspunkte_korrektur` INT NOT NULL DEFAULT 0 AFTER `tore_korrektur`');
                }
                // "ab Spieltag" (Beitrag: Torsten Hofmann) - die Strafe/der
                // Bonus greift erst ab dem eingestellten Spieltag (0 = ab
                // Saisonbeginn), siehe StandingsTrait::computeStandings().
                if (!in_array('ab_spieltag', $strafCols, true)) {
                    $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `ab_spieltag` INT NOT NULL DEFAULT 0 AFTER `minuspunkte_korrektur`');
                }

                $strafUpsert = $db->prepare(
                    'INSERT INTO ' . tbl('liga_strafpunkte') . ' (liga_id, team_id, strafpunkte, straftore, tore_korrektur, minuspunkte_korrektur, ab_spieltag, grund)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE strafpunkte = VALUES(strafpunkte), straftore = VALUES(straftore),
                                             tore_korrektur = VALUES(tore_korrektur),
                                             minuspunkte_korrektur = VALUES(minuspunkte_korrektur),
                                             ab_spieltag = VALUES(ab_spieltag), grund = VALUES(grund)'
                );
                $strafDelete = $db->prepare('DELETE FROM ' . tbl('liga_strafpunkte') . ' WHERE liga_id = ? AND team_id = ?');

                // Vorzeichen-Dropdown (_dir: '1'/'-1') + Betrag (_wert: immer >=0)
                // zu einem vorzeichenbehafteten Wert zusammenführen - siehe
                // admin/view_liga_settings.php ($strafField()) für den Grund
                // (mobile Zifferntastaturen zeigen oft kein Minuszeichen).
                $combine = static function (string $name, $i) : int {
                    $dir = ((int)($_POST[$name . '_dir'][$i] ?? 1)) < 0 ? -1 : 1;
                    $mag = abs((int)($_POST[$name . '_wert'][$i] ?? 0));
                    return $dir * $mag;
                };

                foreach ($_POST['strafe_team_id'] ?? [] as $i => $teamId) {
                    $teamId = (int)$teamId;
                    if ($teamId <= 0) { continue; }
                    $sp    = $combine('strafe_punkte', $i);
                    $st    = $combine('strafe_tore', $i);
                    $tk    = $combine('strafe_erzielt', $i);
                    $mk    = $combine('strafe_minus', $i);
                    $abSt  = max(0, (int)($_POST['strafe_ab_spieltag'][$i] ?? 0));
                    $grund = trim((string)($_POST['strafe_grund'][$i] ?? ''));
                    if ($sp === 0 && $st === 0 && $tk === 0 && $mk === 0 && $grund === '') {
                        $strafDelete->execute([$lid, $teamId]);
                        continue;
                    }
                    $strafUpsert->execute([$lid, $teamId, $sp, $st, $tk, $mk, $abSt, $grund !== '' ? $grund : null]);
                }
                break;
        }
        flash(t('flash_settings_saved'));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    $redirect = $_POST['redirect'] ?? ('?action=liga_settings&id='.$lid.'&tab='.$tab);
    redirect($redirect);
}

