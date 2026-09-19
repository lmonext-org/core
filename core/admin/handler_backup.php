<?php
/**
 * Project: LMOnext
 * Filename: handler_backup.php
 * Fileversion: 1.5.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

/**
 * Eindeutiger Trenner zwischen einzelnen SQL-Anweisungen im Dump. Robuster
 * als ein naives Split am Semikolon (das bei Textfeldern mit ";" im Inhalt
 * brechen würde) – wir schreiben den Dump selbst und kontrollieren daher
 * exakt, wo dieser Marker auftaucht.
 */
const BACKUP_STMT_DELIM = "\n-- @@LMO_STMT@@\n";

/**
 * Absoluter Pfad zum Backup-Ordner (/store im Projekt-Root). Legt den Ordner
 * samt .htaccess-Schutz an, falls er (z.B. bei einer älteren Installation)
 * noch fehlt.
 */
function backupDir() : string
{
    $dir = dirname(__DIR__) . '/store';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        $htContent = "# Database backups (Maintenance > Backup) - complete access protection.\n"
            . "# These files must only be read/written via admin.php (logged-in admin),\n"
            . "# never directly via URL.\n\n"
            . "# Apache 2.2\nOrder Allow,Deny\nDeny from all\n\n"
            . "# Apache 2.4\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n\n"
            . "# Also disable directory listing in case the deny rules\n"
            . "# do not take effect for some reason (e.g., AllowOverride restriction)\n"
            . "Options -Indexes\n";
        @file_put_contents($htaccess, $htContent);
    }
    return $dir;
}

/**
 * Ob die bzip2-Extension verfügbar ist. Auf manchem Shared-Hosting fehlt sie
 * – die Option wird dann in der Backup-Ansicht ausgeblendet statt einen
 * Fataler Fehler zu riskieren.
 */
function backupBzip2Available() : bool
{
    return function_exists('bzcompress') && function_exists('bzdecompress');
}

/**
 * Erkennt Format + Zeitstempel aus einem Backup-Dateinamen
 * ("backup_2026-07-12_10-13-05.sql[.gz|.bz2]"). Gibt null zurück, wenn der
 * Dateiname nicht exakt diesem Muster entspricht (Whitelist – wichtig, damit
 * delete/restore niemals mit einem beliebigen, ggf. von außen manipulierten
 * Dateinamen auf das Dateisystem zugreifen).
 *
 * @return array{format:string,datetime:DateTime}|null
 */
function backupParseFilename(string $filename) : ?array
{
    if (!preg_match('/^backup_(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})\.sql(\.gz|\.bz2)?$/', $filename, $m)) {
        return null;
    }
    try {
        $dt = DateTime::createFromFormat('Y-m-d H-i-s', $m[1] . ' ' . $m[2]);
        if ($dt === false) {
            return null;
        }
    } catch (Throwable) {
        return null;
    }
    $format = match ($m[3] ?? '') {
        '.gz'  => 'gzip',
        '.bz2' => 'bzip2',
        default => 'text',
    };
    return ['format' => $format, 'datetime' => $dt];
}

/**
 * Liste aller vorhandenen Backups (neueste zuerst) für die Wiederherstellen-
 * Ansicht.
 *
 * @return array<int,array{filename:string,datetime:DateTime,format:string,size:int}>
 */
function backupList() : array
{
    $dir = backupDir();
    $out = [];
    foreach (glob($dir . '/backup_*.sql*') ?: [] as $path) {
        $filename = basename($path);
        $meta = backupParseFilename($filename);
        if ($meta === null) {
            continue;
        }
        $logosFilename = backupLogosZipFilenameFor($filename);
        $out[] = [
            'filename'   => $filename,
            'datetime'   => $meta['datetime'],
            'format'     => $meta['format'],
            'size'       => (int)(@filesize($path) ?: 0),
            'hasLogos'   => $logosFilename !== null,
            'logosSize'  => $logosFilename !== null ? (int)(@filesize($dir . '/' . $logosFilename) ?: 0) : 0,
        ];
    }
    usort($out, static fn(array $a, array $b) => $b['datetime'] <=> $a['datetime']);
    return $out;
}

/**
 * Löscht die ältesten Backups, bis die konfigurierte Maximalanzahl
 * (Einstellung "backup_max_count", 0 = unbegrenzt) eingehalten ist.
 */
function backupEnforceMaxCount() : void
{
    $max = (int)getAdminSetting('backup_max_count', '10');
    if ($max <= 0) {
        return; // 0/leer = unbegrenzt
    }
    $all = backupList(); // neueste zuerst
    if (count($all) <= $max) {
        return;
    }
    $dir = backupDir();
    foreach (array_slice($all, $max) as $old) {
        $oldLogos = backupLogosZipFilenameFor($old['filename']);
        if ($oldLogos !== null) {
            @unlink($dir . '/' . $oldLogos);
        }
        @unlink($dir . '/' . $old['filename']);
    }
}

/**
 * Alle Tabellennamen mit dem konfigurierten Präfix (für die Tabellen-
 * Auswahl in der Backup-Ansicht sowie als Default, wenn keine Auswahl
 * getroffen wurde).
 *
 * @return array<int,string> Tabellennamen OHNE Präfix (wie in der Auswahl-Liste angezeigt)
 */
function backupAllTableNames() : array
{
    try {
        $prefix = DB_PREFIX;
        $s = getDB()->query('SHOW TABLES LIKE ' . getDB()->quote($prefix . '%'));
        $names = [];
        foreach ($s->fetchAll(PDO::FETCH_NUM) as $row) {
            $full = (string)$row[0];
            if (str_starts_with($full, $prefix)) {
                $names[] = substr($full, strlen($prefix));
            }
        }
        sort($names);
        return $names;
    } catch (Throwable) {
        return [];
    }
}

/**
 * Escaped einen einzelnen Zellwert für eine INSERT-Anweisung.
 */
function backupQuoteValue(PDO $db, mixed $v) : string
{
    if ($v === null) {
        return 'NULL';
    }
    return $db->quote((string)$v);
}

/**
 * Baut den kompletten (unkomprimierten) SQL-Dump-Text für die übergebenen
 * Tabellen. $type: 'complete' (DROP+CREATE+INSERT) oder 'data' (nur
 * TRUNCATE+INSERT, Tabellen müssen beim Restore bereits existieren).
 *
 * @param array<int,string> $tables Tabellennamen OHNE Präfix
 */
function backupBuildDump(array $tables, string $type) : string
{
    $db = getDB();
    $sql  = "-- LMOnext Datenbank-Backup\n";
    $sql .= '-- Erstellt: ' . date('Y-m-d H:i:s') . "\n";
    $sql .= '-- Typ: ' . ($type === 'complete' ? 'Komplett' : 'Nur Daten') . "\n";
    $sql .= '-- Version: ' . getAppVersion() . "\n";
    $sql .= '-- Prefix: ' . DB_PREFIX . "\n";
    $sql .= "-- Zeichensatz: utf8mb4\n\n";
    $sql .= 'SET NAMES utf8mb4' . BACKUP_STMT_DELIM;
    $sql .= 'SET FOREIGN_KEY_CHECKS=0' . BACKUP_STMT_DELIM;

    foreach ($tables as $table) {
        $fullName = tbl($table); // bereits in Backticks, inkl. Präfix

        if ($type === 'complete') {
            $sql .= 'DROP TABLE IF EXISTS ' . $fullName . BACKUP_STMT_DELIM;
            $createRow = $db->query('SHOW CREATE TABLE ' . $fullName)->fetch(PDO::FETCH_NUM);
            if ($createRow !== false) {
                $sql .= $createRow[1] . BACKUP_STMT_DELIM;
            }
        } else {
            $sql .= 'TRUNCATE TABLE ' . $fullName . BACKUP_STMT_DELIM;
        }

        $stmt = $db->query('SELECT * FROM ' . $fullName);
        $batch = [];
        $batchSize = 0;
        $columns = null;
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if ($columns === null) {
                $columns = array_keys($row);
            }
            $values = array_map(static fn($v) => backupQuoteValue($db, $v), array_values($row));
            $batch[] = '(' . implode(',', $values) . ')';
            $batchSize++;
            if ($batchSize >= 200) {
                $sql .= backupInsertStatement($fullName, $columns, $batch) . BACKUP_STMT_DELIM;
                $batch = [];
                $batchSize = 0;
            }
        }
        if ($batch !== [] && $columns !== null) {
            $sql .= backupInsertStatement($fullName, $columns, $batch) . BACKUP_STMT_DELIM;
        }
    }

    $sql .= 'SET FOREIGN_KEY_CHECKS=1' . BACKUP_STMT_DELIM;

    return $sql;
}

function backupInsertStatement(string $fullName, array $columns, array $rowsSql) : string
{
    $cols = implode(',', array_map(static fn($c) => '`' . $c . '`', $columns));
    return 'INSERT INTO ' . $fullName . ' (' . $cols . ') VALUES ' . implode(',', $rowsSql);
}

/**
 * Erstellt ein neues Backup, schreibt es nach /store und wendet danach das
 * Aufräumen alter Backups an (backupEnforceMaxCount()).
 *
 * @param array<int,string> $tables Tabellennamen OHNE Präfix; leer = alle Tabellen
 * @return array{ok:bool,filename?:string,error?:string}
 */
function backupCreate(string $type, string $format, array $tables) : array
{
    if (!in_array($type, ['complete', 'data'], true)) {
        $type = 'complete';
    }
    if ($format === 'bzip2' && !backupBzip2Available()) {
        $format = 'gzip';
    }
    if (!in_array($format, ['gzip', 'bzip2', 'text'], true)) {
        $format = 'gzip';
    }

    $allTables = backupAllTableNames();
    $tables = array_values(array_intersect($tables, $allTables));
    if ($tables === []) {
        $tables = $allTables;
    }
    if ($tables === []) {
        return ['ok' => false, 'error' => t('wartung_error_no_tables')];
    }

    try {
        $dump = backupBuildDump($tables, $type);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => t('flash_error_prefix', ['msg' => $e->getMessage()])];
    }

    $ext = match ($format) {
        'gzip'  => '.sql.gz',
        'bzip2' => '.sql.bz2',
        default => '.sql',
    };
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'backup_' . $timestamp . $ext;
    $path = backupDir() . '/' . $filename;

    $bytes = match ($format) {
        'gzip'  => gzencode($dump, 6),
        'bzip2' => bzcompress($dump, 6),
        default => $dump,
    };
    if (!is_string($bytes)) {
        return ['ok' => false, 'error' => t('wartung_error_compress')];
    }

    if (@file_put_contents($path, $bytes) === false) {
        return ['ok' => false, 'error' => t('wartung_error_write', ['path' => 'store/' . $filename])];
    }

    // Team-Logos im selben Zug mitsichern (gleicher Zeitstempel im
    // Dateinamen, damit beide Dateien eindeutig zusammengehören). Rein
    // informativ, kein harter Fehler: fehlt ZipArchive oder gibt es noch
    // keine hochgeladenen Logos, bleibt logosFilename einfach null.
    $logosFilename = backupCreateLogosZip($timestamp);

    backupEnforceMaxCount();

    return ['ok' => true, 'filename' => $filename, 'logosFilename' => $logosFilename];
}

/**
 * Ob die ZipArchive-Erweiterung verfügbar ist. Fehlt sie (manches
 * Shared-Hosting), wird die Logo-Sicherung übersprungen statt einen
 * Fataler Fehler zu riskieren – die Datenbank-Sicherung selbst
 * funktioniert davon komplett unabhängig weiter.
 */
function backupZipAvailable() : bool
{
    return class_exists('ZipArchive');
}

/**
 * Erzeugt zu einem Datenbank-Backup ein begleitendes ZIP mit dem
 * Team-Logo-Ordner (assets/img/teams/) UND dem Spielerfoto-Ordner
 * (assets/img/player/, siehe addon/player/spielerstat_lib.php), jeweils in
 * einem eigenen Unterordner ("teams/" bzw. "player/") desselben ZIPs, im
 * selben /store-Verzeichnis, mit demselben Zeitstempel im Dateinamen
 * ("backup_{Zeitstempel}_logos.zip") wie der zugehörige SQL-Dump, damit alle
 * Dateien eindeutig zusammengehören und gemeinsam verwaltet
 * (angezeigt/gelöscht/wiederhergestellt) werden können.
 *
 * @return string|null Dateiname, oder null wenn ZipArchive fehlt oder beide
 *                      Ordner leer sind (kein Fehler – einfach nichts zu
 *                      sichern)
 */
function backupCreateLogosZip(string $timestamp) : ?string
{
    if (!backupZipAvailable()) {
        return null;
    }
    $logoDir  = teamLogoDir();
    $logoFiles = array_filter(glob($logoDir . '/*') ?: [], 'is_file');

    // Spielerfotos (siehe addon/player/spielerstat_lib.php) werden, falls
    // vorhanden, in dasselbe ZIP mit aufgenommen (eigenes Verzeichnis-Präfix
    // "player/"), damit ein Backup weiterhin nur eine begleitende Datei hat.
    $photoFiles = [];
    if (function_exists('spielerPhotoDir')) {
        $photoFiles = array_filter(glob(spielerPhotoDir() . '/*') ?: [], 'is_file');
    }

    if ($logoFiles === [] && $photoFiles === []) {
        return null;
    }

    $filename = 'backup_' . $timestamp . '_logos.zip';
    $path = backupDir() . '/' . $filename;

    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return null;
    }
    foreach ($logoFiles as $file) {
        $zip->addFile($file, 'teams/' . basename($file));
    }
    foreach ($photoFiles as $file) {
        $zip->addFile($file, 'player/' . basename($file));
    }
    $zip->close();

    return is_file($path) ? $filename : null;
}

/**
 * Ermittelt (falls vorhanden) den zu einer SQL-Backup-Datei gehörenden
 * Logo-ZIP-Dateinamen (gleicher Zeitstempel im Namen).
 */
function backupLogosZipFilenameFor(string $sqlFilename) : ?string
{
    $meta = backupParseFilename($sqlFilename);
    if ($meta === null) {
        return null;
    }
    $candidate = 'backup_' . $meta['datetime']->format('Y-m-d_H-i-s') . '_logos.zip';
    return is_file(backupDir() . '/' . $candidate) ? $candidate : null;
}

/**
 * Stellt den Team-Logo-Ordner UND den Spielerfoto-Ordner aus dem zu einem
 * SQL-Backup gehörenden ZIP wieder her (siehe backupCreateLogosZip()).
 * Entfernt vorher alle vorhandenen Dateien in beiden Ordnern (vollständiges
 * Ersetzen, analog zur Datenbank-Wiederherstellung), damit keine Altlasten
 * von inzwischen gelöschten Teams/Spielern zurückbleiben. Kein Fehler, wenn
 * es zu diesem Backup gar kein Logo-ZIP gibt (z.B. ein älteres Backup von
 * vor diesem Feature) – dann bleiben die aktuellen Ordner einfach
 * unangetastet.
 *
 * @return array{ok:bool,count?:int,error?:string}
 */
function backupRestoreLogosZip(string $sqlFilename) : array
{
    $logosFilename = backupLogosZipFilenameFor($sqlFilename);
    if ($logosFilename === null) {
        return ['ok' => true, 'count' => 0];
    }
    if (!backupZipAvailable()) {
        return ['ok' => false, 'error' => t('wartung_error_zip_missing')];
    }
    $path = backupDir() . '/' . $logosFilename;
    if (!is_file($path)) {
        return ['ok' => true, 'count' => 0];
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return ['ok' => false, 'error' => t('wartung_error_decompress')];
    }

    $logoDir = teamLogoDir();
    foreach (glob($logoDir . '/*') ?: [] as $existing) {
        if (is_file($existing)) {
            @unlink($existing);
        }
    }
    $photoDir = function_exists('spielerPhotoDir') ? spielerPhotoDir() : null;
    if ($photoDir !== null) {
        foreach (glob($photoDir . '/*') ?: [] as $existing) {
            if (is_file($existing)) {
                @unlink($existing);
            }
        }
    }

    $count = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);
        if ($entryName === false) {
            continue;
        }
        if (str_starts_with($entryName, 'teams/')) {
            $targetDir = $logoDir;
        } elseif (str_starts_with($entryName, 'player/') && $photoDir !== null) {
            $targetDir = $photoDir;
        } else {
            continue;
        }
        $baseName = basename($entryName);
        if ($baseName === '' || str_contains($baseName, '..')) {
            continue; // gegen Path-Traversal in manipulierten ZIP-Einträgen
        }
        $data = $zip->getFromIndex($i);
        if ($data !== false && @file_put_contents($targetDir . '/' . $baseName, $data) !== false) {
            $count++;
        }
    }
    $zip->close();

    return ['ok' => true, 'count' => $count];
}

/**
 * Löscht ein Backup anhand des Dateinamens (strikt gegen das Whitelist-Muster
 * aus backupParseFilename() geprüft).
 */
function backupDelete(string $filename) : bool
{
    if (backupParseFilename($filename) === null) {
        return false;
    }
    $logosFilename = backupLogosZipFilenameFor($filename);
    if ($logosFilename !== null) {
        @unlink(backupDir() . '/' . $logosFilename); // Logo-ZIP mit entsorgen, falls vorhanden
    }
    $path = backupDir() . '/' . $filename;
    return is_file($path) && @unlink($path);
}

/**
 * Liest + entpackt eine Backup-Datei und führt jede enthaltene SQL-
 * Anweisung aus. ACHTUNG: überschreibt vorhandene Daten (bei "Komplett"-
 * Backups werden die betroffenen Tabellen vorher gelöscht und neu angelegt).
 *
 * @return array{ok:bool,statements?:int,error?:string}
 */
function backupRestore(string $filename) : array
{
    $meta = backupParseFilename($filename);
    if ($meta === null) {
        return ['ok' => false, 'error' => t('wartung_error_invalid_file')];
    }
    $path = backupDir() . '/' . $filename;
    if (!is_file($path)) {
        return ['ok' => false, 'error' => t('wartung_error_file_missing')];
    }

    $raw = @file_get_contents($path);
    if ($raw === false) {
        return ['ok' => false, 'error' => t('wartung_error_file_missing')];
    }

    $sql = match ($meta['format']) {
        'gzip'  => @gzdecode($raw),
        'bzip2' => backupBzip2Available() ? bzdecompress($raw) : false,
        default => $raw,
    };
    if (!is_string($sql)) {
        return ['ok' => false, 'error' => t('wartung_error_decompress')];
    }

    // ── Portabilität zwischen Installationen mit unterschiedlichem
    // Tabellenprefix: Das Backup enthält den Prefix, mit dem es erstellt
    // wurde ("-- Prefix: xyz_"). Weicht der von der aktuell in config.php
    // konfigurierten DB_PREFIX ab, werden alle Tabellenbezeichner
    // (`{alterPrefix}...`) im Dump auf den aktuellen Prefix umgeschrieben,
    // bevor irgendetwas ausgeführt wird. Ältere Backups ohne diese
    // Metadatenzeile (vor diesem Feature erstellt) werden unverändert
    // ausgeführt wie bisher.
    $remappedPrefix = null;
    if (preg_match('/^-- Prefix: (\S+)$/m', $sql, $pm)) {
        $sourcePrefix = $pm[1];
        if ($sourcePrefix !== '' && $sourcePrefix !== DB_PREFIX) {
            $sql = str_replace('`' . $sourcePrefix, '`' . DB_PREFIX, $sql);
            $remappedPrefix = ['from' => $sourcePrefix, 'to' => DB_PREFIX];
        }
    }

    $statements = array_filter(array_map('trim', explode(BACKUP_STMT_DELIM, $sql)), static fn($s) => $s !== '');

    $db = getDB();
    $count = 0;
    try {
        foreach ($statements as $stmt) {
            $db->exec($stmt);
            $count++;
        }
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => t('flash_error_prefix', ['msg' => $e->getMessage()]), 'statements' => $count];
    }

    $logosResult = backupRestoreLogosZip($filename);

    return [
        'ok'             => true,
        'statements'     => $count,
        'remappedPrefix' => $remappedPrefix,
        'logosRestored'  => $logosResult['count'] ?? 0,
        'logosError'     => $logosResult['ok'] ? null : ($logosResult['error'] ?? null),
    ];
}

// ── AJAX/POST-Actions ─────────────────────────────────────────────────────────

if ($action === 'run_backup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $type   = (string)($_POST['backup_type'] ?? 'complete');
    $format = (string)($_POST['backup_format'] ?? 'gzip');
    $tables = array_map('strval', $_POST['tables'] ?? []);

    $result = backupCreate($type, $format, $tables);
    if ($result['ok']) {
        logAdminAction('backup_created', $result['filename'] ?? '');
        $msg = t('wartung_flash_backup_created', ['file' => $result['filename']]);
        if (!empty($result['logosFilename'])) {
            $msg .= ' ' . t('wartung_flash_logos_included');
        }
        flash($msg);
    } else {
        flash($result['error'] ?? t('wartung_error_generic'), 'error');
    }
    redirect('?action=wartung&tab=backup');
}

if ($action === 'restore_backup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $filename = (string)($_POST['filename'] ?? '');
    $result = backupRestore($filename);
    if ($result['ok']) {
        // Wiederherstellen überschreibt bestehende Daten - besonders
        // wichtig, dies im Log nachvollziehbar zu machen.
        logAdminAction('backup_restored', $filename);
        $msg = t('wartung_flash_restored', ['n' => $result['statements'] ?? 0]);
        if (!empty($result['remappedPrefix'])) {
            $msg .= ' ' . t('wartung_flash_prefix_remapped', $result['remappedPrefix']);
        }
        if (!empty($result['logosRestored'])) {
            $msg .= ' ' . t('wartung_flash_logos_restored', ['n' => $result['logosRestored']]);
        } elseif (!empty($result['logosError'])) {
            $msg .= ' ' . t('wartung_flash_logos_restore_failed', ['msg' => $result['logosError']]);
        }
        flash($msg);
    } else {
        flash($result['error'] ?? t('wartung_error_generic'), 'error');
    }
    redirect('?action=wartung&tab=restore');
}

if ($action === 'delete_backup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $filename = (string)($_POST['filename'] ?? '');
    if (backupDelete($filename)) {
        logAdminAction('backup_deleted', $filename);
        flash(t('wartung_flash_deleted'));
    } else {
        flash(t('wartung_error_generic'), 'error');
    }
    redirect('?action=wartung&tab=restore');
}

if ($action === 'save_backup_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    try {
        $max = (int)($_POST['backup_max_count'] ?? 10);
        if ($max < 0) { $max = 0; }
        $s = getDB()->prepare('INSERT INTO '.tbl('admin_settings').' (`key`,`value`) VALUES (?,?)
            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
        $s->execute(['backup_max_count', (string)$max]);
        backupEnforceMaxCount();
        flash(t('wartung_flash_settings_saved'));
    } catch (Throwable $e) {
        flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error');
    }
    redirect('?action=wartung&tab=backup');
}

// ── Wartungsmodus speichern (Beitrag: Torsten Hofmann) ───────────────────────
// Schaltet das Frontend komplett ab (siehe frontend/bootstrap.php), der
// Adminbereich bleibt davon unberührt. Wie alle anderen admin_settings-Werte
// per INSERT ... ON DUPLICATE KEY UPDATE gespeichert, kein eigenes Schema
// nötig (ensureAdminSettings() legt die Tabelle bereits an).
if ($action === 'save_maintenance_mode' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    try {
        $val = isset($_POST['maintenance_mode']) ? '1' : '0';
        $s = getDB()->prepare('INSERT INTO '.tbl('admin_settings').' (`key`,`value`) VALUES (?,?)
            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
        $s->execute(['maintenance_mode', $val]);
        logAdminAction('maintenance_mode_' . ($val === '1' ? 'enabled' : 'disabled'));
        flash($val === '1'
            ? t('wartung_flash_maintenance_on')
            : t('wartung_flash_maintenance_off'));
    } catch (Throwable $e) {
        flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error');
    }
    redirect('?action=wartung&tab=wartung');
}
