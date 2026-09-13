<?php
/**
 * Project: LMOnext
 * Filename: handler_addons.php
 * Fileversion: 1.3.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Addon-Verwaltung: POST-Handler (Enable/Disable) ──────────────────────────
// Wird von admin.php geladen, verarbeitet POST-Requests für
// ?action=addons mit addon_action=enable|disable.

use LMOnext\Addon\AddonManager;

if ($action !== 'addons' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$addonAction = $_POST['addon_action'] ?? '';
$addonName  = $_POST['addon_name'] ?? '';

// ── Validierung ──────────────────────────────────────────────────────────────
if (!in_array($addonAction, ['enable', 'disable', 'check_updates', 'save_token', 'delete_token', 'install_update', 'install_zip', 'purge_data'], true)) {
    flash(t('addons_err_invalid_action'), 'error');
    redirect('?action=addons');
}

// ── Demo-Modus: Installation neuer/fremder Addon-Codes komplett sperren ──────
// (Beitrag: Sicherheitsüberarbeitung). Auf Installationen, bei denen die
// Konstante DEMO_MODE in config.php auf true gesetzt ist (z.B. eine öffentlich
// zugängliche Vorstellungs-/Demo-Instanz), werden sowohl der ZIP-Upload als
// auch der Ein-Klick-Update-Weg über GitHub blockiert - beides würde sonst
// fremden Code in den Webroot schreiben. Aktivieren/Deaktivieren bereits
// installierter Addons bleibt uneingeschränkt möglich, damit die Demo weiterhin
// vollständig vorführbar ist. EINE Codebasis für beide Einsatzzwecke: der
// Schalter lebt in config.php (nicht Teil der verteilten ZIP), nicht im Code.
if (in_array($addonAction, ['install_zip', 'install_update', 'purge_data'], true)
    && defined('DEMO_MODE') && DEMO_MODE === true) {
    flash(t('addons_demo_mode_blocked'), 'error');
    redirect('?action=addons&tab=settings');
}

// save_token / delete_token brauchen keinen addon_name
if ($addonAction === 'save_token') {
    $token = trim($_POST['github_token'] ?? '');
    if ($token !== '' && !preg_match('/^[a-zA-Z0-9_]{16,}$/', $token)) {
        flash(t('addons_token_invalid'), 'error');
        redirect('?action=addons&tab=settings');
    }
    $addonManager->setGithubToken($token);
    // Cache löschen damit der nächste Check den Token nutzt
    @unlink(sys_get_temp_dir() . '/lmonext_addon_updates_v2.json');
    flash(t('addons_token_saved'), 'success');
    redirect('?action=addons&tab=settings');
}

if ($addonAction === 'delete_token') {
    $addonManager->setGithubToken('');
    @unlink(sys_get_temp_dir() . '/lmonext_addon_updates_v2.json');
    flash(t('addons_token_deleted'), 'success');
    redirect('?action=addons&tab=settings');
}

// ── ZIP-Upload: Neues Addon installieren ───────────────────────────────────
// Nimmt eine hochgeladene ZIP-Datei entgegen, sucht darin nach addon.json
// und installiert das Addon nach addon/{name}/. Falls das Addon schon
// existiert, wird ein Backup im _addon_backups/ Ordner angelegt.
if ($addonAction === 'install_zip') {
    if (!isset($_FILES['addon_zip']) || $_FILES['addon_zip']['error'] !== UPLOAD_ERR_OK) {
        $uploadErr = $_FILES['addon_zip']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'Datei zu groß (Server-Limit)',
            UPLOAD_ERR_FORM_SIZE => 'Datei zu groß (Form-Limit)',
            UPLOAD_ERR_PARTIAL   => 'Upload unvollständig',
            UPLOAD_ERR_NO_FILE   => 'Keine Datei ausgewählt',
            UPLOAD_ERR_NO_TMP_DIR => 'Kein Temp-Verzeichnis',
            UPLOAD_ERR_CANT_WRITE => 'Temp-Datei nicht schreibbar',
        ];
        $msg = $errMap[$uploadErr] ?? 'Unbekannter Upload-Fehler (' . $uploadErr . ')';
        flash(t('addons_install_upload_error', ['error' => $msg]), 'error');
        redirect('?action=addons&tab=settings');
    }

    $uploadedFile = $_FILES['addon_zip']['tmp_name'];
    $uploadedName = $_FILES['addon_zip']['name'];

    // Nur ZIP-Dateien erlauben
    $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));
    if ($ext !== 'zip') {
        flash(t('addons_install_not_zip'), 'error');
        redirect('?action=addons&tab=settings');
    }

    // Größenlimit: 7 MB (Beitrag: Sicherheitsüberarbeitung, war vorher 50 MB -
    // deutlich zu großzügig für ein reines PHP-/Sprach-/Template-Paket ohne
    // große Mediendateien; kleineres Limit reduziert die mögliche Nutzlast
    // eines missbräuchlichen Uploads, ersetzt aber KEINE Inhaltsprüfung).
    $maxSize = 7 * 1024 * 1024;
    if ($_FILES['addon_zip']['size'] > $maxSize) {
        flash(t('addons_install_too_big', ['max' => '7 MB']), 'error');
        redirect('?action=addons&tab=settings');
    }

    try {
        $result = $addonManager->installFromZip($uploadedFile);
    } catch (Throwable $e) {
        flash(t('addons_install_err_generic', ['error' => $e->getMessage()]), 'error');
        redirect('?action=addons&tab=settings');
    }

    if (!empty($result['success'])) {
        $msg = t('addons_install_success', [
            'name'    => $result['name'] ?? '?',
            'version' => $result['version'] ?? '?',
        ]);
        if (!empty($result['backup'])) {
            $msg .= ' ' . t('addons_install_backup_created');
        }
        flash($msg, 'success');
    } else {
        $errCode = $result['error'] ?? 'unknown';
        $errMsgKeys = [
            'zip_extension_missing'   => 'addons_update_err_zip_missing',
            'file_not_found'          => 'addons_install_err_no_file',
            'zip_open_failed'         => 'addons_update_err_zip_failed',
            'no_manifest_in_zip'      => 'addons_update_err_no_manifest',
            'invalid_manifest'        => 'addons_install_err_invalid_manifest',
            'invalid_name'            => 'addons_install_err_invalid_name',
            'core_version'            => 'addons_err_core_version',
            'zip_unsafe_path'         => 'addons_install_err_unsafe_path',
            'zip_disallowed_filetype' => 'addons_install_err_disallowed_filetype',
            'php_lint_failed'         => 'addons_install_err_lint_failed',
            'dangerous_pattern_found' => 'addons_install_err_dangerous_pattern',
        ];
        $msgKey = $errMsgKeys[$errCode] ?? '';
        if ($msgKey !== '') {
            $params = ['name' => $result['name'] ?? '', 'error' => $errCode,
                       'need' => $result['need'] ?? '', 'have' => $result['have'] ?? '',
                       'file' => $result['file'] ?? ''];
            flash(t($msgKey, $params), 'error');
        } else {
            flash(t('addons_install_err_generic', ['error' => $errCode]), 'error');
        }
        if ($errCode === 'no_manifest_in_zip') {
            $tree  = $result['tree'] ?? '(keine Debug-Daten)';
            $found = $result['found'] ?? 0;
            flash('Debug: ' . $found . ' addon.json gefunden — Inhalt des ZIP:\n' . h($tree), 'error');
        }
    }
    redirect('?action=addons&tab=settings');
}

// check_updates braucht keinen addon_name — vor der Namens-Validierung abfangen
if ($addonAction === 'check_updates') {
    $results = $addonManager->checkGithubUpdates(true);
    $updatesAvailable = 0;
    $connErrors = 0;    // fetch_failed, rate_limited, no_http_client = GitHub nicht erreichbar
    $notFound = 0;      // no_release = Repo existiert nicht (404) — KEINE Verbindungsstörung
    $checked = 0;
    foreach ($results as $info) {
        $err = $info['error'] ?? '';
        if ($err === 'no_github') {
            continue; // überspringen — kein GitHub-Repo konfiguriert
        }
        $checked++;
        if (!empty($info['update_available'])) {
            $updatesAvailable++;
        } elseif ($err === 'no_release') {
            $notFound++; // Repo/Release existiert nicht — ist OK, kein Verbindungsfehler
        } elseif ($err !== '') {
            $connErrors++; // echter Verbindungsfehler
        }
    }

    if ($updatesAvailable > 0) {
        flash(t('addons_updates_available', ['count' => $updatesAvailable]), 'info');
    } elseif ($connErrors > 0 && ($checked - $notFound) === $connErrors) {
        // Alle echten Checks fehlgeschlagen (404er nicht mitgezählt)
        flash(t('addons_updates_all_failed'), 'error');
    } elseif ($connErrors > 0) {
        flash(t('addons_updates_some_failed', ['errors' => $connErrors, 'checked' => $checked - $notFound]), 'warning');
    } else {
        // Keine Updates, keine Verbindungsfehler — 404er sind einfach "kein Release"
        flash(t('addons_no_updates'), 'success');
    }
    redirect('?action=addons');
}

if ($addonName === '' || !preg_match('/^[a-z0-9_-]+$/', $addonName)) {
    flash(t('addons_err_invalid_name'), 'error');
    redirect('?action=addons');
}

// ── Addon existiert? ─────────────────────────────────────────────────────────
$allAddons = $addonManager->getAllAddons();
$found = null;
foreach ($allAddons as $a) {
    if (($a['manifest']['name'] ?? '') === $addonName) {
        $found = $a;
        break;
    }
}
if ($found === null) {
    flash(t('addons_err_not_found'), 'error');
    redirect('?action=addons');
}

// ── Enable ──────────────────────────────────────────────────────────────────
if ($addonAction === 'enable') {
    // Abhängigkeiten prüfen
    $deps = $found['manifest']['dependencies'] ?? [];
    $missing = [];
    foreach ($deps as $dep) {
        if (!$addonManager->isEnabled($dep)) {
            $missing[] = $dep;
        }
    }
    if (!empty($missing)) {
        flash(t('addons_err_dep_missing', ['deps' => implode(', ', $missing)]), 'error');
        redirect('?action=addons');
    }

    // Min-Core-Version prüfen
    $minCore = $found['manifest']['min_core_version'] ?? '';
    if ($minCore !== '' && version_compare(LMONEXT_VERSION, $minCore, '<')) {
        flash(t('addons_err_core_version', ['need' => $minCore, 'have' => LMONEXT_VERSION]), 'error');
        redirect('?action=addons');
    }

    try {
        $addonManager->enable($addonName);
        flash(t('addons_flash_enabled', ['name' => $addonName]), 'success');
    } catch (Throwable $e) {
        flash(t('addons_flash_enable_error', ['name' => $addonName, 'error' => $e->getMessage()]), 'error');
    }
    redirect('?action=addons');
}

// ── Disable ──────────────────────────────────────────────────────────────────
if ($addonAction === 'disable') {
    // Prüfen, ob andere aktivierte Addons von diesem abhängen
    foreach ($allAddons as $a) {
        if (!$a['enabled']) continue;
        $deps = $a['manifest']['dependencies'] ?? [];
        if (in_array($addonName, $deps, true)) {
            flash(t('addons_err_depended_on', ['addon' => $addonName, 'by' => $a['manifest']['name']]), 'error');
            redirect('?action=addons');
        }
    }

    try {
        $addonManager->disable($addonName);
        flash(t('addons_flash_disabled', ['name' => $addonName]), 'success');
    } catch (Throwable $e) {
        flash(t('addons_flash_disable_error', ['name' => $addonName, 'error' => $e->getMessage()]), 'error');
    }
    redirect('?action=addons');
}

// ── Eigene Daten eines deaktivierten Addons löschen ("Purge", Beitrag:
// Nutzerwunsch, analog zu phpBB's "purge extension data") ────────────────────
// Löscht unwiderruflich die in addon.json (Feld "db_tables") deklarierten
// Datenbank-Tabellen. NUR für bereits DEAKTIVIERTE Addons erlaubt (doppelte
// serverseitige Prüfung: Registry-Status + $found['enabled'] direkt aus dem
// zuvor neu ermittelten Addon-Stand) - verhindert einen versehentlichen
// Datenverlust bei einem noch aktiv genutzten Addon, selbst wenn die
// UI-Sperre (Button nur bei deaktivierten Addons sichtbar) umgangen würde.
if ($addonAction === 'purge_data') {
    if ($addonManager->isEnabled($addonName)) {
        flash(t('addons_purge_err_still_enabled', ['name' => $addonName]), 'error');
        redirect('?action=addons');
    }

    $tables = $addonManager->getDbTables($addonName);
    if (empty($tables)) {
        flash(t('addons_purge_err_no_tables', ['name' => $addonName]), 'error');
        redirect('?action=addons');
    }

    $result = $addonManager->purgeData($addonName);
    if ($result['success']) {
        flash(t('addons_purge_flash_success', ['name' => $addonName, 'n' => count($result['dropped']), 'tables' => implode(', ', $result['dropped'])]), 'success');
    } else {
        flash(t('addons_purge_flash_error', ['name' => $addonName]), 'error');
    }
    redirect('?action=addons');
}


// ── Auto-Update installieren ─────────────────────────────────────────────────
// Lädt das neueste GitHub-Release herunter, entpackt es und ersetzt die
// Addon-Dateien. Aktiviert/Deaktiviert-Status bleibt unverändert.
if ($addonAction === 'install_update') {
    try {
        $result = $addonManager->installUpdate($addonName);
    } catch (Throwable $e) {
        flash(t('addons_update_err_generic', ['error' => $e->getMessage()]), 'error');
        redirect('?action=addons');
    }

    if (!empty($result['success'])) {
        flash(t('addons_update_installed', [
            'name' => $addonName,
            'from' => $result['from'] ?? '?',
            'to'   => $result['to'] ?? '?',
        ]), 'success');
        // Alte Backups aufräumen (max. 5 pro Addon)
        $addonManager->cleanupBackups($addonName);
    } else {
        $errCode = $result['error'] ?? 'unknown';
        $errMsgKeys = [
            'not_found'                => 'addons_err_not_found',
            'no_github'                => 'addons_update_err_no_github',
            'rate_limited'              => 'addons_update_err_rate_limited',
            'no_release'                => 'addons_update_err_no_release',
            'fetch_failed'              => 'addons_update_err_fetch_failed',
            'no_http_client'            => 'addons_update_err_fetch_failed',
            'already_current'          => 'addons_update_err_already_current',
            'zip_extension_missing'    => 'addons_update_err_zip_missing',
            'download_failed'          => 'addons_update_err_download_failed',
            'zip_open_failed'          => 'addons_update_err_zip_failed',
            'unexpected_zip_structure' => 'addons_update_err_zip_structure',
            'no_manifest_in_release'   => 'addons_update_err_no_manifest',
            'manifest_name_mismatch'   => 'addons_update_err_name_mismatch',
            'zip_unsafe_path'          => 'addons_install_err_unsafe_path',
            'zip_disallowed_filetype'  => 'addons_install_err_disallowed_filetype',
            'php_lint_failed'          => 'addons_install_err_lint_failed',
            'dangerous_pattern_found'  => 'addons_install_err_dangerous_pattern',
        ];
        $msgKey = $errMsgKeys[$errCode] ?? '';
        if ($msgKey !== '') {
            flash(t($msgKey, ['name' => $addonName, 'error' => $errCode, 'file' => $result['file'] ?? '']), 'error');
        } else {
            flash(t('addons_update_err_generic', ['error' => $errCode]), 'error');
        }
        // Bei download_failed: Details (HTTP-Code, URL) ins Flash
        if ($errCode === 'download_failed') {
            $httpCode = $result['http_code'] ?? 0;
            $url      = $result['url'] ?? '';
            flash('Debug: HTTP ' . $httpCode . ' — ' . h($url), 'error');
        }
        // Bei no_manifest_in_release / manifest_name_mismatch: Verzeichnisbaum
        // zeigen damit man sieht was im ZIP wirklich drin ist.
        if (in_array($errCode, ['no_manifest_in_release', 'manifest_name_mismatch'], true)) {
            $tree  = $result['tree'] ?? '(keine Debug-Daten)';
            $found = $result['found'] ?? 0;
            flash('Debug: ' . $found . ' addon.json gefunden — Inhalt des ZIP:\n' . h($tree), 'error');
        }
    }
    redirect('?action=addons');
}
