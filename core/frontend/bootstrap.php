<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.13.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Wird von home.php (und künftigen Besucherseiten wie liga.php) eingebunden.
 * Bewusst unabhängig von admin/bootstrap.php: eigene Session (damit ein
 * Admin-Login nichts mit der Besucher-Session zu tun hat), eigene
 * Spracheinstellung (Domain "frontend" in lang/i18n.php) und eigenes Template.
 */
declare(strict_types = 1);

// ── Globale Fehlerbehandlung ──────────────────────────────────────────────────
// Jede nicht abgefangene Exception landet im Server-Error-Log (für Admins/
// Entwickler einsehbar), Besucher sehen nur eine schlichte, technikfreie
// Fehlermeldung statt Stacktrace/Dateipfaden - unabhängig davon, wie
// display_errors auf dem jeweiligen Hosting konfiguriert ist.
set_exception_handler(static function (Throwable $e) : void {
    error_log('LMOnext (frontend) uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (function_exists('logPhpIssue')) {
        logPhpIssue('FATAL', $e->getMessage(), $e->getFile(), $e->getLine());
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head>'
       . '<body style="font-family:system-ui;text-align:center;padding:60px 20px;color:#333">'
       . '<h2>⚠️ Ein unerwarteter Fehler ist aufgetreten</h2>'
       . '<p>Bitte versuche es später erneut.</p>'
       . '</body></html>';
});

// Nicht-fatale Warnungen/Notices/Deprecated-Meldungen fangen wie im
// Adminbereich (siehe admin/bootstrap.php) über einen eigenen
// set_error_handler() - gibt bewusst false zurück, damit PHPs reguläre
// Fehlerbehandlung zusätzlich weiterläuft.
set_error_handler(static function (int $errno, string $errstr, string $errfile = '', int $errline = 0) : bool {
    if (function_exists('logPhpIssue')) {
        $level = match ($errno) {
            E_WARNING, E_USER_WARNING       => 'WARNING',
            E_NOTICE, E_USER_NOTICE         => 'NOTICE',
            E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
            default                         => 'ERROR',
        };
        logPhpIssue($level, $errstr, $errfile, $errline);
    }
    return false;
}, E_WARNING | E_NOTICE | E_DEPRECATED | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED);

// ── Session ────────────────────────────────────────────────────────────────
// Cookie-Parameter schützen die Besucher-Session zusätzlich gegen
// clientseitigen Zugriff (HttpOnly) und Cross-Site-Requests (SameSite=Lax);
// Secure wird nur gesetzt, wenn die Seite tatsächlich über HTTPS läuft
// (sonst würde das Cookie auf einer reinen HTTP-Installation nie ankommen).
session_name('lmonext_frontend');
session_set_cookie_params([
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

// ── Security-Header ───────────────────────────────────────────────────────────
// Schützt die Hauptseiten (liga.php, home.php) vor Clickjacking über fremde
// iframes. Die eigens zum Einbetten gedachten Addons (viewer, tabellenrechner,
// relegation, ewige, mini) requiren zwar ebenfalls diese Datei, überschreiben
// die Header aber direkt danach wieder (siehe dortiger header_remove()-Aufruf),
// damit ihre iframe-Einbettung auf fremden Websites weiterhin funktioniert.
// CSP über frame-ancestors hinaus verschärft (Beitrag: Torsten Hofmann) -
// gleiche zusätzliche Direktiven wie im Adminbereich (siehe
// admin/bootstrap.php), frame-ancestors bleibt bewusst bei 'self' statt
// 'none' (unverändert gegenüber vorher - die Haupt-Besucherseiten dürfen
// weiterhin auf der eigenen Domain eingebettet werden, nur nicht extern).
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; "
         . "style-src 'self' 'unsafe-inline'; "
         . "script-src 'self' 'unsafe-inline'; "
         . "img-src 'self' data: blob:; "
         . "font-src 'self' data:; "
         . "object-src 'none'; "
         . "base-uri 'self'; "
         . "frame-ancestors 'self'");
}

// ── Session-Timeout für Tippspiel-Logins (Idle-Erkennung) ────────────────────
// Bisher lief eine einmal angemeldete Tipper-Session unbegrenzt weiter -
// selbst auf einem gemeinsam genutzten/öffentlichen Rechner blieb ein
// vergessenes Abmelden also dauerhaft aktiv. 60 Minuten statt der 30 Minuten
// im Adminbereich (Beitrag: Torsten Hofmann; Tipper sind zwischen zwei
// Aktionen typischerweise länger inaktiv als ein Admin bei der Datenpflege).
// Betrifft ausschließlich den Tippspiel-Login (tipp_user_id) - die übrige
// Besuchersitzung (Sprache, Theme etc.) bleibt unangetastet.
const FRONTEND_SESSION_IDLE_TIMEOUT = 3600; // 60 Minuten in Sekunden
if (isset($_SESSION['tipp_user_id'])) {
    $tippLast = (int)($_SESSION['tipp_last_activity'] ?? time());
    if (time() - $tippLast > FRONTEND_SESSION_IDLE_TIMEOUT) {
        unset($_SESSION['tipp_user_id'], $_SESSION['tipp_last_activity']);
    } else {
        $_SESSION['tipp_last_activity'] = time();
    }
}

// ── CSRF-Schutz (Frontend, z.B. Tippspiel-Formulare) ─────────────────────────
// Gleiches Vorgehen wie im Adminbereich (siehe admin/bootstrap.php): ein Token
// pro Session, zentral vor jedem POST geprüft, statt jedes Formular einzeln
// abzusichern.
if (!function_exists('csrfToken')) {
    function csrfToken() : string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrfField')) {
    function csrfField() : string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
    }
}
if (!function_exists('requireCsrf')) {
    function requireCsrf() : void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $sent  = $_POST['csrf_token'] ?? '';
        $known = $_SESSION['csrf_token'] ?? '';
        if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "403 Forbidden: Ungültiges oder fehlendes CSRF-Token. Bitte die Seite neu laden und erneut versuchen.";
            exit;
        }
    }
}
requireCsrf();

// ── Mehrsprachigkeit (Besucherbereich, unabhängig vom Adminbereich) ──────────
// Die eigentliche Sprachauflösung (inkl. der in den Admin-Einstellungen
// konfigurierten Standardsprache) passiert weiter unten, NACHDEM
// getAdminSetting() verfügbar ist (siehe dort) – hier nur die
// Funktionsdefinitionen laden, noch nichts auflösen.
require_once dirname(__DIR__) . '/lang/i18n.php';

// ── Konfiguration ─────────────────────────────────────────────────────────────
// Lädt entweder die Composer/.env-Variante oder die klassische config.php,
// je nachdem was install.php beim Installieren angelegt hat (siehe
// config_loader.php im Projekt-Root für Details zu beiden Varianten).
require_once dirname(__DIR__) . '/config_loader.php';

// ── PDO ──────────────────────────────────────────────────────────────────────
function getDB() : PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function tbl(string $base) : string
{
    return '`' . DB_PREFIX . $base . '`';
}

function h(mixed $v) : string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Liest die Versionsnummer aus der composer.json im Projekt-Root (Feld "version").
 * Liefert einen leeren String, falls die Datei fehlt oder ungültig ist.
 */
function getAppVersion() : string
{
    static $version = null;
    if ($version !== null) {
        return $version;
    }
    try {
        $path = dirname(__DIR__) . '/composer.json';
        $data = json_decode((string)file_get_contents($path), true);
        return $version = (string)($data['version'] ?? '');
    } catch (Throwable $e) {
        error_log('LMOnext getAppVersion(): ' . $e->getMessage());
        return $version = '';
    }
}

/**
 * Liest alle admin_settings-Zeilen einmal pro Request in einen statischen
 * Speicher-Cache (siehe getAdminSetting()) - vorher löste jeder einzelne
 * getAdminSetting()-Aufruf eine eigene SQL-Abfrage aus, obwohl derselbe
 * Aufruf (z.B. 'active_template', 'language', 'show_pdf_buttons' usw.)
 * innerhalb eines Seitenaufrufs oft mehrfach vorkommt.
 */
function getAdminSetting(string $key, string $default = '') : string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = getDB()->query('SELECT `key`, `value` FROM ' . tbl('admin_settings'))->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['key']] = (string)$row['value'];
            }
        } catch (Throwable $e) {
            error_log('LMOnext getAdminSetting(): ' . $e->getMessage());
            // $cache bleibt ein leeres Array -> jede einzelne Abfrage fällt
            // unten auf ihren jeweiligen $default zurück, kein Fataler Fehler
        }
    }
    return $cache[$key] ?? $default;
}

// ── Sprache jetzt WIRKLICH auflösen (siehe Kommentar weiter oben): erst jetzt
// ist getAdminSetting() verfügbar, um die in den Admin-Einstellungen
// konfigurierte Standardsprache zu berücksichtigen. Muss auch VOR
// resolveActiveTemplate() passieren, damit t()/tf() im weiteren Verlauf
// (inkl. der Datenfunktionen) korrekt auflösen.
getCurrentLanguage('frontend', getAdminSetting('language', DEFAULT_LANGUAGE));

// ── Wartungsmodus: Frontend sperren, wenn im Admin aktiviert (Beitrag:
// Torsten Hofmann) - der Adminbereich bleibt dabei immer erreichbar, nur die
// Besucherseiten (home.php, liga.php, Embed-Addons) werden blockiert und
// zeigen stattdessen eine gestaltete Wartungsseite (HTTP 503, damit
// Suchmaschinen/Monitoring den Zustand korrekt als "vorübergehend nicht
// verfügbar" statt als echten Fehler oder als neuen dauerhaften Seiteninhalt
// werten). Wird zentral hier geprüft (nicht einzeln in jeder Seite), da
// sowohl home.php/liga.php als auch alle Embed-Addons frontend/bootstrap.php
// laden.
if (getAdminSetting('maintenance_mode', '0') === '1') {
    $maintTitle   = tf('maintenance_title');
    $maintHeading = tf('maintenance_heading');
    $maintMsg     = tf('maintenance_message');
    $maintSub     = tf('maintenance_subtext');
    $maintContact = tf('maintenance_contact');
    $maintFooter  = tf('maintenance_footer');
    $maintVersion = getAppVersion();

    http_response_code(503);
    header('Retry-After: 3600');
    echo '<!DOCTYPE html>'
       . '<html lang="de"><head>'
       . '<meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex,nofollow">'
       . '<title>' . h($maintTitle) . '</title>'
       . '<style>'
       . '*{margin:0;padding:0;box-sizing:border-box}'
       . 'body{font-family:"Segoe UI",system-ui,-apple-system,sans-serif;'
       . 'min-height:100vh;display:flex;align-items:center;justify-content:center;'
       . 'background:linear-gradient(135deg,#153A8C 0%,#0d2862 100%);'
       . 'color:#1f2430;padding:20px}'
       . '.maint-card{background:#fff;border-radius:16px;'
       . 'box-shadow:0 20px 60px rgba(0,0,0,.3);'
       . 'max-width:520px;width:100%;padding:48px 40px;text-align:center}'
       . '.maint-icon{width:72px;height:72px;margin:0 auto 24px;'
       . 'border-radius:50%;background:#f0f2f5;display:flex;'
       . 'align-items:center;justify-content:center;font-size:32px}'
       . '.maint-h1{font-size:1.6rem;font-weight:700;color:#153A8C;'
       . 'margin-bottom:12px}'
       . '.maint-msg{font-size:1.02rem;color:#4b5261;margin-bottom:8px}'
       . '.maint-sub{font-size:.88rem;color:#9098a8;margin-bottom:24px}'
       . '.maint-contact{font-size:.84rem;color:#9098a8;'
       . 'padding-top:20px;border-top:1px solid #eef0f3}'
       . '.maint-foot{margin-top:24px;font-size:.72rem;color:#c0c5d0;'
       . 'letter-spacing:.02em}'
       . '</style></head><body>'
       . '<div class="maint-card">'
       . '<div class="maint-icon">&#9881;&#65039;</div>'
       . '<h1 class="maint-h1">' . h($maintHeading) . '</h1>'
       . '<p class="maint-msg">' . h($maintMsg) . '</p>'
       . '<p class="maint-sub">' . h($maintSub) . '</p>'
       . '<p class="maint-contact">' . h($maintContact) . '</p>'
       . '<div class="maint-foot">'
       . h($maintFooter)
       . ($maintVersion !== '' ? ' v' . h($maintVersion) : '')
       . '</div>'
       . '</div></body></html>';
    exit;
}

// ── Template-Engine + aktives Template ermitteln ─────────────────────────────
require_once __DIR__ . '/template_engine.php';

$activeTemplateDefault = getAdminSetting('active_template', DEFAULT_TEMPLATE);
$allowTemplateSwitch   = getAdminSetting('allow_template_switch', '0') === '1';
$activeTemplate        = resolveActiveTemplate($activeTemplateDefault, $allowTemplateSwitch);

// ── Sport-Profile (Beitrag: Torsten Hofmann) - müssen VOR data_liga.php
// geladen sein, da StandingsTrait/RenderViewsTrait (Teil der Kette dort)
// LMOnext\Sport\SportRegistry referenzieren ─────────────────────────────────
require_once dirname(__DIR__) . '/src/Sport/SportProfile.php';
require_once dirname(__DIR__) . '/src/Sport/SportRegistry.php';
require_once dirname(__DIR__) . '/src/Sport/FootballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/VolleyballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/IceHockeyProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BasketballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/HandballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BadmintonProfile.php';

// ── Datenfunktionen (Abfragen) ────────────────────────────────────────────────
require_once __DIR__ . '/data_home.php';
require_once __DIR__ . '/data_liga.php';

// ── AddonManager initialisieren (Frontend) ────────────────────────────────
// Laedt automatisch alle frontend_handlers der aktivierten Addons
// (z.B. spielerstat_lib.php, frontend_spielerstat.php, tipp_lib.php),
// ersetzt die frueheren festen require_once-Zeilen.
require_once dirname(__DIR__) . '/src/Addon/AddonManager.php';
$feAddonManager = new \LMOnext\Addon\AddonManager(dirname(__DIR__) . '/addon', getDB(), DB_PREFIX);
addonManager($feAddonManager);
$feAddonManager->bootFrontend();
// pdf_export.php wird bewusst NICHT hier eingebunden: die Datei ist recht groß
// (PHP muss sie sonst bei JEDEM Seitenaufruf parsen, auch auf home.php und den
// Mini-Addons, die sie nie brauchen) und wird ausschließlich von liga.php
// tatsächlich verwendet - dort wird sie direkt eingebunden.
