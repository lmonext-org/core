<?php
/**
 * Project: LMOnext
 * Filename: handler_team_logos.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Massen-Upload von Team-Logos mit automatischem Namensabgleich:
 * Dateien (mehrere Einzeldateien, eine ZIP-Datei oder ein per FTP befüllter
 * Eingangsordner store/logo_inbox/) werden anhand ihres Dateinamens den
 * Teams zugeordnet (wie im alten LMO: Dateiname = Teamname, meist ohne
 * Leer- und Sonderzeichen). Ablauf: Auswahl -> Zuordnung prüfen/korrigieren
 * -> Übernehmen. Gespeichert wird über saveTeamLogoFromPath() - dieselben
 * Prüfungen wie beim Einzel-Upload (Format, Inhalt, Mindesthöhe, SVG-
 * Bereinigung).
 */

const TLB_MAX_FILE_BYTES  = 3 * 1024 * 1024;   // pro Datei
const TLB_MAX_FILES       = 1000;              // pro Durchlauf
const TLB_MAX_ZIP_BYTES   = 150 * 1024 * 1024; // entpackte Gesamtgröße
const TLB_EXT             = ['svg', 'png', 'jpg', 'jpeg', 'gif'];

function tlbBaseDir() : string
{
    return dirname(__DIR__) . '/store';
}

function tlbInboxDir() : string
{
    $d = tlbBaseDir() . '/logo_inbox';
    if (!is_dir($d)) { @mkdir($d, 0755, true); }
    return $d;
}

function tlbStagingRoot() : string
{
    $d = tlbBaseDir() . '/logo_import';
    if (!is_dir($d)) { @mkdir($d, 0755, true); }
    return $d;
}

/** Löscht ein Zwischenverzeichnis samt Inhalt (nur unterhalb von store/logo_import). */
function tlbRemoveStaging(string $dir) : void
{
    $root = realpath(tlbStagingRoot());
    $real = realpath($dir);
    if ($root === false || $real === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) { return; }
    foreach (glob($real . '/*') ?: [] as $f) { if (is_file($f)) { @unlink($f); } }
    @rmdir($real);
}

/** Alte Zwischenverzeichnisse (> 1 Tag) aufräumen. */
function tlbCleanupOldStaging() : void
{
    foreach (glob(tlbStagingRoot() . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        if (filemtime($d) < time() - 86400) { tlbRemoveStaging($d); }
    }
}

/** Dateiname -> Vergleichsschlüssel: ASCII-gefaltet, nur a-z0-9. */
function tlbKey(string $s, bool $umlautSimple = false) : string
{
    if ($umlautSimple) {
        $s = strtr($s, ['ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'Ä' => 'a', 'Ö' => 'o', 'Ü' => 'u', 'ß' => 'ss']);
    }
    $s = teamNormalizeName($s);
    return (string)preg_replace('/[^a-z0-9]+/', '', $s);
}

/**
 * Ordnet Dateinamen den Teams zu.
 *
 * @param array<int,string> $origNames Originaldateinamen (mit Endung)
 * @return array<int,array{team_id:?int,how:string,cands:array<int,int>}> je Index
 */
function tlbMatch(array $origNames) : array
{
    $teams = getDB()->query('SELECT id,name,kurz,mittel FROM ' . tbl('teams_global'))->fetchAll(PDO::FETCH_ASSOC);
    $index = []; // key => [teamId => tier]  (tier 1 = Name, 2 = Mittel/Kurz)
    $keysByTeam = [];
    foreach ($teams as $t) {
        $id = (int)$t['id'];
        foreach ([[1, 'name'], [2, 'mittel'], [2, 'kurz']] as [$tier, $col]) {
            $v = trim((string)($t[$col] ?? ''));
            if ($v === '') { continue; }
            foreach ([false, true] as $simple) {
                $k = tlbKey($v, $simple);
                if ($k === '' || ($tier === 2 && strlen($k) < 3)) { continue; }
                if (!isset($index[$k][$id]) || $index[$k][$id] > $tier) { $index[$k][$id] = $tier; }
                $keysByTeam[$id][$k] = true;
            }
        }
    }

    $out = [];
    foreach ($origNames as $i => $orig) {
        $fk = tlbKey((string)pathinfo($orig, PATHINFO_FILENAME));
        $fk2 = tlbKey((string)pathinfo($orig, PATHINFO_FILENAME), true);
        $res = ['team_id' => null, 'how' => 'none', 'cands' => []];
        if ($fk === '') { $out[$i] = $res; continue; }

        // 1) exakte Treffer
        $hits = [];
        foreach (array_unique([$fk, $fk2]) as $k) {
            foreach (($index[$k] ?? []) as $id => $tier) {
                if (!isset($hits[$id]) || $hits[$id] > $tier) { $hits[$id] = $tier; }
            }
        }
        if ($hits) {
            $best = min($hits);
            $top = array_keys(array_filter($hits, static fn($t) => $t === $best));
            if (count($top) === 1) {
                $out[$i] = ['team_id' => (int)$top[0], 'how' => 'exact', 'cands' => [(int)$top[0]]];
            } else {
                $out[$i] = ['team_id' => null, 'how' => 'ambiguous', 'cands' => array_map('intval', array_slice($top, 0, 6))];
            }
            continue;
        }

        // 2) ähnliche Namen (Enthalten-sein bzw. Levenshtein-Ähnlichkeit)
        $scores = [];
        $lenF = strlen($fk);
        foreach ($keysByTeam as $id => $keys) {
            $bestScore = 0.0;
            foreach ($keys as $k => $_) {
                $lenK = strlen($k);
                if (min($lenF, $lenK) >= 5 && (str_contains($k, $fk) || str_contains($fk, $k))) {
                    $score = 0.88 + 0.1 * (min($lenF, $lenK) / max($lenF, $lenK));
                } elseif (abs($lenF - $lenK) <= 6 && $lenF <= 200 && $lenK <= 200) {
                    $score = 1 - levenshtein($fk, $k) / max($lenF, $lenK);
                } else {
                    continue;
                }
                if ($score > $bestScore) { $bestScore = $score; }
            }
            if ($bestScore >= 0.78) { $scores[$id] = $bestScore; }
        }
        if ($scores) {
            arsort($scores);
            $ids = array_keys($scores);
            $vals = array_values($scores);
            $res['cands'] = array_map('intval', array_slice($ids, 0, 5));
            $clear = $vals[0] >= 0.85 && (count($vals) === 1 || $vals[0] - $vals[1] >= 0.05);
            if ($clear) { $res['team_id'] = (int)$ids[0]; $res['how'] = 'fuzzy'; }
            else { $res['how'] = 'ambiguous'; }
        }
        $out[$i] = $res;
    }
    return $out;
}

/**
 * Liest die Dateiliste eines Staging-Verzeichnisses (Eingangsordner oder
 * Zwischenverzeichnis) - nur erlaubte Endungen, keine Unterordner.
 *
 * @return array<int,array{orig:string,stored:string,size:int}>
 */
function tlbListDir(string $dir, bool $isInbox) : array
{
    $files = [];
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..' || $f[0] === '.' || !is_file($dir . '/' . $f)) { continue; }
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, TLB_EXT, true)) { continue; }
        $size = (int)filesize($dir . '/' . $f);
        if ($size <= 0 || $size > TLB_MAX_FILE_BYTES) { continue; }
        $orig = $f;
        if (!$isInbox) {
            // im Zwischenverzeichnis: "<nr>__<Originalname>" -> Originalname
            $orig = preg_replace('/^\d{4}__/', '', $f) ?? $f;
        }
        $files[] = ['orig' => $orig, 'stored' => $f, 'size' => $size];
        if (count($files) >= TLB_MAX_FILES) { break; }
    }
    usort($files, static fn($a, $b) => strcasecmp($a['orig'], $b['orig']));
    return $files;
}

/** Speichert einen Originalnamen sicher im Zwischenverzeichnis ("<nr>__<name>"). */
function tlbStoredName(int $nr, string $orig) : string
{
    $safe = preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', basename(str_replace('\\', '/', $orig))) ?? 'file';
    $safe = ltrim($safe, '.');
    return sprintf('%04d__', $nr) . $safe;
}

// ── Schritt 1: Dateien entgegennehmen und Zuordnung vorbereiten ──────────────
if ($action === 'team_logos_upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    tlbCleanupOldStaging();
    $source = (string)($_POST['source'] ?? 'files');
    $session = null;

    if ($source === 'inbox') {
        $dir = tlbInboxDir();
        $list = tlbListDir($dir, true);
        if (!$list) {
            flash(t('tlb_err_inbox_empty'), 'error');
            redirect('?action=team_logos');
        }
        $session = ['dir' => $dir, 'inbox' => true, 'files' => $list];
    } else {
        $token = bin2hex(random_bytes(12));
        $dir = tlbStagingRoot() . '/' . $token;
        @mkdir($dir, 0755, true);
        $n = 0;
        $skipped = 0;

        // a) mehrere Einzeldateien
        $fl = $_FILES['logos'] ?? null;
        if ($fl && is_array($fl['name'] ?? null)) {
            foreach ($fl['name'] as $k => $name) {
                if (($fl['error'][$k] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { continue; }
                $ext = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
                $tmp = (string)$fl['tmp_name'][$k];
                if (!in_array($ext, TLB_EXT, true) || !is_uploaded_file($tmp)
                    || (int)$fl['size'][$k] > TLB_MAX_FILE_BYTES || $n >= TLB_MAX_FILES) { $skipped++; continue; }
                if (move_uploaded_file($tmp, $dir . '/' . tlbStoredName(++$n, (string)$name))) { /* ok */ } else { $n--; $skipped++; }
            }
        }

        // b) ZIP-Datei
        $zf = $_FILES['zipfile'] ?? null;
        if ($zf && ($zf['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            if (!class_exists('ZipArchive')) {
                tlbRemoveStaging($dir);
                flash(t('tlb_err_nozip'), 'error');
                redirect('?action=team_logos');
            }
            $zip = new ZipArchive();
            if ($zip->open((string)$zf['tmp_name']) === true) {
                $total = 0;
                for ($zi = 0; $zi < $zip->numFiles && $n < TLB_MAX_FILES; $zi++) {
                    $st = $zip->statIndex($zi);
                    if (!$st) { continue; }
                    $ename = (string)$st['name'];
                    $base  = basename(str_replace('\\', '/', $ename));
                    if (str_ends_with($ename, '/') || $base === '' || $base[0] === '.' || str_contains($ename, '__MACOSX/')) { continue; }
                    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
                    if (!in_array($ext, TLB_EXT, true) || (int)$st['size'] > TLB_MAX_FILE_BYTES || (int)$st['size'] <= 0) { $skipped++; continue; }
                    $total += (int)$st['size'];
                    if ($total > TLB_MAX_ZIP_BYTES) { break; }
                    $data = $zip->getFromIndex($zi);
                    if ($data === false) { $skipped++; continue; }
                    // ZIPs von Windows liefern Namen teils in CP437/CP850 - bei ungültigem UTF-8 umwandeln
                    if (!preg_match('//u', $base)) {
                        $conv = @iconv('CP850', 'UTF-8//IGNORE', $base);
                        if ($conv !== false && $conv !== '') { $base = $conv; }
                    }
                    if (file_put_contents($dir . '/' . tlbStoredName(++$n, $base), $data) === false) { $n--; $skipped++; }
                }
                $zip->close();
            } else {
                $skipped++;
            }
        }

        $list = tlbListDir($dir, false);
        if (!$list) {
            tlbRemoveStaging($dir);
            flash(t('tlb_err_nothing'), 'error');
            redirect('?action=team_logos');
        }
        $session = ['dir' => $dir, 'inbox' => false, 'files' => $list, 'skipped' => $skipped];
    }

    $names = array_map(static fn($f) => $f['orig'], $session['files']);
    $session['match'] = tlbMatch($names);
    $_SESSION['team_logos'] = $session;
    redirect('?action=team_logos_review');
}

// ── Abbrechen ────────────────────────────────────────────────────────────────
if ($action === 'team_logos_cancel') {
    requireLogin();
    $s = $_SESSION['team_logos'] ?? null;
    if (is_array($s) && empty($s['inbox'])) { tlbRemoveStaging((string)$s['dir']); }
    unset($_SESSION['team_logos']);
    redirect('?action=team_logos');
}

// ── Schritt 2: Zuordnung übernehmen ──────────────────────────────────────────
if ($action === 'team_logos_apply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $s = $_SESSION['team_logos'] ?? null;
    if (!is_array($s) || !is_dir((string)$s['dir'])) {
        flash(t('tlb_err_expired'), 'error');
        redirect('?action=team_logos');
    }
    $dir = (string)$s['dir'];
    $overwrite  = !empty($_POST['overwrite']);
    $deleteDone = !empty($_POST['delete_done']);

    // erlaubte Dateien = tatsächlicher Verzeichnisinhalt (nicht dem POST vertrauen)
    $valid = [];
    foreach (tlbListDir($dir, !empty($s['inbox'])) as $f) { $valid[$f['stored']] = $f; }
    $teamIds = array_map('intval', getDB()->query('SELECT id FROM ' . tbl('teams_global'))->fetchAll(PDO::FETCH_COLUMN));
    $teamSet = array_flip($teamIds);

    $results = [];
    $used = [];
    $okCount = 0;
    foreach ((array)($_POST['file'] ?? []) as $i => $stored) {
        $stored = (string)$stored;
        if (!isset($valid[$stored])) { continue; }
        $f = $valid[$stored];
        $row = ['file' => $f['orig'], 'team' => '', 'status' => '', 'msg' => ''];
        if (empty($_POST['use'][$i])) { $row['status'] = 'skip'; $results[] = $row; continue; }
        $raw = trim((string)($_POST['team'][$i] ?? ''));
        $tid = 0;
        if (preg_match('/\[(\d+)\]\s*$/', $raw, $m)) { $tid = (int)$m[1]; }
        elseif (ctype_digit($raw)) { $tid = (int)$raw; }
        if ($tid === 0 || !isset($teamSet[$tid])) { $row['status'] = 'noteam'; $results[] = $row; continue; }
        $row['team'] = $raw;
        if (isset($used[$tid])) { $row['status'] = 'dup'; $results[] = $row; continue; }
        if (!$overwrite && findTeamLogoPath($tid) !== null) { $row['status'] = 'haslogo'; $results[] = $row; continue; }
        $r = saveTeamLogoFromPath($tid, $dir . '/' . $stored, $f['orig'], false);
        if ($r['ok']) {
            $used[$tid] = true; $okCount++;
            $row['status'] = 'ok';
            if ($deleteDone && !empty($s['inbox'])) { @unlink($dir . '/' . $stored); }
        } else {
            $row['status'] = 'error'; $row['msg'] = (string)$r['error'];
        }
        $results[] = $row;
    }

    if (empty($s['inbox'])) { tlbRemoveStaging($dir); }
    unset($_SESSION['team_logos']);
    $_SESSION['team_logos_done'] = ['results' => $results, 'ok' => $okCount];
    logAdminAction('team_logos_bulk', (string)$okCount);
    redirect('?action=team_logos&done=1');
}
