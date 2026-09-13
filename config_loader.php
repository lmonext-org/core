<?php
/**
 * Project: LMOnext
 * Filename: config_loader.php
 * Fileversion: 1.6.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

// ── Sicherheitsfix: PHP-Fehleranzeige explizit erzwingen ──────────────────────
// Bisher verließ sich die Anwendung komplett auf die php.ini-Konfiguration
// des jeweiligen Hostings. Steht dort display_errors=On (auf manchem Shared-
// Hosting Standard), würden PHP-Fehler/Warnungen/Notices mit internen Pfaden
// und Details direkt auf der Seite erscheinen - für jeden Besucher sichtbar.
// Hier wird die Anzeige unabhängig von der Server-Konfiguration abgeschaltet;
// Fehler werden stattdessen weiterhin protokolliert (log_errors), damit sie
// über die Server-Logs nachvollziehbar bleiben. Für lokale Fehlersuche kann
// direkt nach diesem require_once (in admin/bootstrap.php bzw.
// frontend/bootstrap.php) bei Bedarf ini_set('display_errors', '1') gesetzt
// werden.
if (!defined('LMO_DISPLAY_ERRORS_OVERRIDDEN')) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
    define('LMO_DISPLAY_ERRORS_OVERRIDDEN', true);
}

// ── Core-Version aus composer.json definieren ────────────────────────────────
// Wird vom AddonManager benutzt, um min_core_version aus addon.json gegen die
// tatsächlich laufende LMOnext-Version zu prüfen.
if (!defined('LMONEXT_VERSION')) {
    $_composer = json_decode((string)@file_get_contents(__DIR__ . '/composer.json'), true);
    define('LMONEXT_VERSION', $_composer['version'] ?? '1.0.0');
}

// ── Konfiguration laden ─────────────────────────────────────────────────────
// Nach vorne gezogen (vor den HTTPS-Erzwingung-Block weiter unten): die dort
// geprüfte Konstante LMO_FORCE_HTTPS kann optional in config.php (oder als
// LMO_FORCE_HTTPS in der .env) gesetzt werden - das setzt voraus, dass diese
// Datei bereits geladen ist, BEVOR die Weiterleitung entschieden wird. Alle
// anderen Blöcke dieser Datei bleiben unverändert in ihrer Reihenfolge.
$_lmoRoot           = __DIR__;
$_lmoVendorAutoload = $_lmoRoot . '/vendor/autoload.php';
$_lmoEnvFile        = $_lmoRoot . '/.env';
$_lmoConfigFile     = $_lmoRoot . '/config.php';

if (is_file($_lmoVendorAutoload) && is_file($_lmoEnvFile)) {
    // ── Composer/.env-Variante ──────────────────────────────────────────────
    require_once $_lmoVendorAutoload;
    \LMOnext\Core\Env::load($_lmoRoot);

    if (!defined('DB_HOST')) {
        define('DB_HOST', \LMOnext\Core\Env::get('DB_HOST', 'localhost') ?? 'localhost');
    }
    if (!defined('DB_PORT')) {
        define('DB_PORT', (int)(\LMOnext\Core\Env::get('DB_PORT', '3306') ?? '3306'));
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', \LMOnext\Core\Env::get('DB_NAME', '') ?? '');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', \LMOnext\Core\Env::get('DB_USER', '') ?? '');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', \LMOnext\Core\Env::get('DB_PASS', '') ?? '');
    }
    if (!defined('DB_CHARSET')) {
        define('DB_CHARSET', \LMOnext\Core\Env::get('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4');
    }
    if (!defined('DB_PREFIX')) {
        define('DB_PREFIX', \LMOnext\Core\Env::get('DB_PREFIX', 'lmonext_') ?? 'lmonext_');
    }
    // LMO_FORCE_HTTPS in der .env-Variante: config.php wird hier nicht
    // geladen, daher gibt es keinen define()-Weg - stattdessen per
    // Env::get() gelesen, akzeptiert "0"/"false"/"off" (case-insensitiv)
    // als Abschaltwert, alles andere (inkl. nicht gesetzt) bleibt aktiv.
    if (!defined('LMO_FORCE_HTTPS')) {
        $forceHttpsEnv = strtolower((string)(\LMOnext\Core\Env::get('LMO_FORCE_HTTPS', '1') ?? '1'));
        define('LMO_FORCE_HTTPS', !in_array($forceHttpsEnv, ['0', 'false', 'off', 'no'], true));
    }
} elseif (is_file($_lmoConfigFile)) {
    // ── Klassische config.php-Variante (Standard) ───────────────────────────
    require_once $_lmoConfigFile;
} else {
    http_response_code(503);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Nicht konfiguriert</title></head>'
      . '<body style="font-family:system-ui;text-align:center;padding:60px 20px;color:#333">'
      . '<h2>⚠️ Noch nicht installiert</h2>'
      . '<p>Weder <code>config.php</code> noch eine gültige <code>.env</code>-Konfiguration gefunden.</p>'
      . '<p>Bitte zuerst den <a href="install.php">Installer</a> ausführen.</p>'
      . '</body></html>');
}

unset($_lmoRoot, $_lmoVendorAutoload, $_lmoEnvFile, $_lmoConfigFile);

// Standardwert, falls weder config.php noch .env die Konstante gesetzt haben
// (der ganz normale Fall - HTTPS-Erzwingung ist standardmäßig AN).
if (!defined('LMO_FORCE_HTTPS')) {
    define('LMO_FORCE_HTTPS', true);
}

// ── Aktuelles Schema erkennen (zentrale Hilfsfunktion) ──────────────────────
// Wiederverwendet vom HTTPS-Erzwingung-Block direkt unten UND vom
// Info-Block unter Administrator → Einstellungen (siehe admin/view_settings.php),
// der Betreiber warnt, falls das Script tatsächlich ohne SSL läuft - auch
// wenn LMO_FORCE_HTTPS bewusst auf false gesetzt wurde, um genau das
// (temporär) zu erlauben. X-Forwarded-Proto wird zusätzlich zu HTTPS
// geprüft, da viele Hoster (auch Shared-Hosting) TLS auf einem
// vorgelagerten Proxy terminieren und intern nur noch per HTTP an PHP
// weiterreichen.
if (!function_exists('lmoIsHttps')) {
    function lmoIsHttps() : bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
}

// ── Sicherheitsfix: HTTP → HTTPS erzwingen ─────────────────────────────────────
// Bisher hing der "secure"-Cookie-Flag zwar korrekt vom erkannten Schema ab,
// es gab aber KEINE aktive Weiterleitung - wäre die Seite auch über
// ungesichertes HTTP erreichbar, könnten Session-Cookies z.B. in einem
// öffentlichen WLAN abgefangen werden. Nur im Web-Kontext aktiv (nicht bei
// CLI-Aufrufen, dort fehlt HTTP_HOST ohnehin), und nur wenn HTTPS nicht
// bereits aktiv ist.
//
// Abschaltbar über die Konstante LMO_FORCE_HTTPS (Standard: true) - nötig
// für Testinstallationen auf einem Host ohne SSL-Zertifikat (lokal, oder ein
// Hoster ohne kostenlose SSL-Option): OHNE diesen Schalter würde JEDER
// Request in einen 301-Redirect auf https:// laufen, der dort ins Leere
// läuft (kein Server/Zertifikat auf Port 443) - die Seite wäre dann
// komplett unerreichbar, nicht nur unverschlüsselt. Zum Abschalten in
// config.php ergänzen: define('LMO_FORCE_HTTPS', false); - oder in der .env:
// LMO_FORCE_HTTPS=0. WARNUNG: nur für Tests gedacht - im Echtbetrieb sollte
// SSL aktiviert und dieser Schutz eingeschaltet bleiben (siehe auch der
// entsprechende Hinweis unter Administrator → Einstellungen, sobald das
// Script tatsächlich ohne SSL läuft).
if (LMO_FORCE_HTTPS && PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST']) && !defined('LMO_HTTPS_CHECK_DONE')) {
    define('LMO_HTTPS_CHECK_DONE', true);
    if (!lmoIsHttps()) {
        $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $httpsUrl, true, 301);
        exit;
    }
}

// ── Datei-Log für PHP-Fehler/Warnungen ─────────────────────────────────────────
// Zentral hier (statt in admin/bootstrap.php ODER frontend/bootstrap.php),
// da diese Datei von BEIDEN Bereichen geladen wird - Fehler aus Admin und
// Frontend landen so in derselben Datei, ohne die bewusste Trennung der
// beiden Bootstrap-Dateien aufzuweichen (getrennte Sessions, eigene
// Sprach-/Template-Auflösung etc.). Wird von admin/bootstrap.php's
// set_exception_handler()/set_error_handler() sowie den Pendants in
// frontend/bootstrap.php aufgerufen. Anzeige unter Administrator → Log
// (siehe admin/view_users.php).
if (!function_exists('phpIssueLogFile')) {
    /**
     * Pfad zur Log-Datei für PHP-Fehler/Warnungen. Bewusst eine echte Datei
     * statt einer weiteren DB-Tabelle - falls ausgerechnet die
     * Datenbankverbindung selbst das Problem ist, bliebe ein DB-basiertes
     * Log sonst leer/unbrauchbar. Liegt im bereits vorhandenen,
     * beschreibbaren store/-Verzeichnis (siehe backupDir() in
     * admin/handler_backup.php).
     */
    function phpIssueLogFile() : string
    {
        $dir = __DIR__ . '/store';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/php_issues.log';
    }
}

const PHP_ISSUE_LOG_MAX_LINES = 500;

if (!function_exists('logPhpIssue')) {
    /**
     * Schreibt einen Fehler/eine Warnung in die eigene Log-Datei
     * (zusätzlich zum regulären PHP-error_log(), das je nach Hosting
     * schwer einsehbar ist). Rotiert die Datei automatisch, wenn sie zu
     * groß wird (behält die letzten PHP_ISSUE_LOG_MAX_LINES Zeilen), damit
     * sie nicht unbegrenzt wächst.
     */
    function logPhpIssue(string $level, string $message, string $file, int $line) : void
    {
        try {
            $logFile = phpIssueLogFile();
            $entry = sprintf(
                "[%s] [%s] %s in %s:%d\n",
                date('Y-m-d H:i:s'),
                $level,
                str_replace(["\r", "\n"], ' ', $message),
                $file,
                $line
            );
            @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

            // Rotation: nur gelegentlich prüfen (jede ~20. Schreibaktion,
            // per Zufall statt Zeilenzählung, um bei jedem Schreiben nicht
            // extra die ganze Datei einlesen zu müssen), damit die Datei
            // nicht unbegrenzt wächst.
            if (random_int(1, 20) === 1 && is_file($logFile) && filesize($logFile) > 512 * 1024) {
                $lines = @file($logFile, FILE_IGNORE_NEW_LINES);
                if (is_array($lines) && count($lines) > PHP_ISSUE_LOG_MAX_LINES) {
                    $kept = array_slice($lines, -PHP_ISSUE_LOG_MAX_LINES);
                    @file_put_contents($logFile, implode("\n", $kept) . "\n", LOCK_EX);
                }
            }
        } catch (Throwable) {}
    }
}

if (!function_exists('readPhpIssueLog')) {
    /**
     * Liest die letzten $limit Einträge aus der Fehler/Warnungen-Log-Datei,
     * neueste zuerst. Für die Anzeige unter Administrator → Log.
     *
     * @return string[]
     */
    function readPhpIssueLog(int $limit = 100) : array
    {
        $logFile = phpIssueLogFile();
        if (!is_file($logFile)) {
            return [];
        }
        $lines = @file($logFile, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return [];
        }
        return array_reverse(array_slice($lines, -$limit));
    }
}

// ── Copyright-Hinweis (zentral, überall verfügbar) ─────────────────────────
// Gibt "© LMOnext <composer-version> <Jahr>" als Link zum Spenden-Forum zurück.
// Die Version wird direkt aus composer.json gelesen (wie getAppVersion() in
// den Bootstrap-Dateien), da config_loader.php VOR den Bootstraps geladen wird.
// Wird im Footer, Admin-Sidebar und von LigaService::renderCopyrightNotice()
// (das noch einen <p>-Wrapper hinzufügt) verwendet (Beitrag: Torsten Hofmann).
if (!function_exists('renderCopyrightNotice')) {
    function renderCopyrightNotice(string $addon = '') : string
    {
        $year    = (string) date('Y');

        // Version aus composer.json lesen (gleiches Vorgehen wie getAppVersion())
        $version = '';
        $composer = __DIR__ . '/composer.json';
        if (is_file($composer)) {
            $data = @json_decode((string) file_get_contents($composer), true);
            if (is_array($data) && isset($data['version'])) {
                $version = (string) $data['version'];
            }
        }

        $dataAtt = $addon !== '' ? ' data-addon="' . htmlspecialchars($addon, ENT_QUOTES, 'UTF-8') . '"' : '';
        $url     = 'https://www.liga-manager-online.org/forum/app.php/donation';
        $label   = htmlspecialchars('LMOnext ' . $version . ' ' . $year, ENT_QUOTES, 'UTF-8');
        $link    = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
                 . 'target="_blank" rel="noopener" '
                 . 'style="color:inherit;text-decoration:underline dotted;opacity:.7">'
                 . $label . '</a>';
        return '<span class="lmo-copyright"' . $dataAtt . '>&copy;&nbsp;' . $link . '</span>';
    }
}
