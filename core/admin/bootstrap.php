<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.28.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Globale Fehlerbehandlung ──────────────────────────────────────────────────
// Jede nicht abgefangene Exception landet im Server-Error-Log, im Adminbereich
// selbst (angemeldete Nutzer) wird die eigentliche Meldung trotzdem angezeigt,
// da das für die Fehlersuche during der Entwicklung/Wartung hilfreich ist -
// nur der Stacktrace/Dateipfad bleibt dem Besucherbereich vorbehalten (siehe
// frontend/bootstrap.php), hier reicht die Kurzfassung.
set_exception_handler(static function (Throwable $e) : void {
    error_log('LMOnext (admin) uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (function_exists('logPhpIssue')) {
        logPhpIssue('FATAL', $e->getMessage(), $e->getFile(), $e->getLine());
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    // Link zum Aktivitätsprotokoll nur anzeigen, wenn schon eine
    // eingeloggte Admin-Session besteht - anonyme Besucher (z.B. falls der
    // Fehler ganz früh, noch vor dem Login, auftritt) bekommen keinen
    // Hinweis auf den Adminbereich zu sehen.
    $logLinkHtml = '';
    if (!empty($_SESSION['admin_logged_in'])) {
        $logLinkHtml = '<p style="margin-top:16px"><a href="?action=users&tab=log" '
                     . 'style="color:#2563eb;text-decoration:underline">Zum Aktivitätsprotokoll (Administrator → Log)</a></p>';
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head>'
       . '<body style="font-family:system-ui;text-align:center;padding:60px 20px;color:#333">'
       . '<h2>⚠️ Ein unerwarteter Fehler ist aufgetreten</h2>'
       . '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</p>'
       . '<p style="color:#888;font-size:.85rem">Details wurden ins Server-Log geschrieben.</p>'
       . $logLinkHtml
       . '</body></html>';
});

// Nicht-fatale Warnungen/Notices/Deprecated-Meldungen fangen diesen
// Handler NICHT über set_exception_handler() ab (der reagiert nur auf
// tatsächlich geworfene/uncaught Throwables) - dafür ein eigener
// set_error_handler(). Gibt bewusst false zurück, damit PHPs reguläre
// Fehlerbehandlung (log_errors etc., siehe config_loader.php) zusätzlich
// weiterläuft, statt sie zu ersetzen.
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

// ── Konfiguration ─────────────────────────────────────────────────────────────
// Lädt entweder die Composer/.env-Variante oder die klassische config.php,
// je nachdem was install.php beim Installieren angelegt hat (siehe
// config_loader.php im Projekt-Root für Details zu beiden Varianten).
require_once dirname(__DIR__) . '/config_loader.php';

defined('DB_PORT')      || define('DB_PORT',      3306);
defined('DB_CHARSET')   || define('DB_CHARSET',   'utf8mb4');
defined('DB_PREFIX')    || define('DB_PREFIX',    '');
defined('ADMIN_TITLE')  || define('ADMIN_TITLE',  'LMOnext Admin');
defined('SESSION_NAME') || define('SESSION_NAME', 'lmonext_admin');

session_name(SESSION_NAME);
session_set_cookie_params([
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

// ── Security-Header ───────────────────────────────────────────────────────────
// Der Adminbereich hat KEINEN legitimen Grund, jemals in ein iframe (auch nicht
// auf der eigenen Domain) eingebettet zu werden - im Unterschied zu den
// Embed-Addons (viewer, tabellenrechner, relegation, ewige, mini), die genau
// dafür gebaut sind und ihre eigenen, permissiveren Header setzen (siehe
// dortiger Aufruf von header_remove() nach dem Laden von frontend/bootstrap.php).
// CSP über frame-ancestors hinaus verschärft (Beitrag: Torsten Hofmann) -
// script-src/object-src/base-uri schränken ein, WOHER Skripte/Objekte/das
// <base>-Tag stammen dürfen, als zusätzliche Verteidigungsebene falls
// irgendwo doch reflektierter/gespeicherter Inhalt in die Ausgabe gerät (die
// eigentliche Absicherung bleibt konsequentes h()-Escaping, siehe unten -
// die CSP ist Verteidigung in der Tiefe, kein Ersatz dafür). 'unsafe-inline'
// bleibt für style-src/script-src nötig, da Admin-Views inline-CSS/JS
// verwenden (kein Build-Schritt für externe Bundles in diesem Projekt).
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; "
         . "style-src 'self' 'unsafe-inline'; "
         . "script-src 'self' 'unsafe-inline'; "
         . "img-src 'self' data: blob:; "
         . "font-src 'self' data:; "
         . "object-src 'none'; "
         . "base-uri 'self'; "
         . "frame-ancestors 'none'");
}

// ── Mehrsprachigkeit ─────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/lang/i18n.php';

// ── Sport-Profile (Beitrag: Torsten Hofmann) - bewusst NUR die Sport-Klassen,
// NICHT die Liga-Trait-Kette (LigaService, StandingsTrait, ...): Torstens
// Version band diese komplett in admin/bootstrap.php ein, was der bewussten
// Trennung von Admin- und Frontend-Bootstrap widerspricht (getDB()/tbl() sind
// hier komplett eigenständig definiert, siehe "Tipps nachtragen"-Feature).
// Die Sport-Profile selbst sind zustandslos (kein DB-Zugriff im Konstruktor,
// siehe src/Sport/INTEGRATION.md) und daher gefahrlos einbindbar.
require_once dirname(__DIR__) . '/src/Sport/SportProfile.php';
require_once dirname(__DIR__) . '/src/Sport/SportRegistry.php';
require_once dirname(__DIR__) . '/src/Sport/FootballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/VolleyballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/IceHockeyProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BasketballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/HandballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BadmintonProfile.php';

ensureAdminSettings(); // legt admin_settings ggf. an (Funktionsdefinition weiter unten, aber dank Hoisting hier schon nutzbar)
getCurrentLanguage('admin', getAdminSetting('language', DEFAULT_LANGUAGE)); // ermittelt/persistiert Sprache; kann bei ?lang=xx redirecten

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

function isLoggedIn() : bool
{
    return !empty($_SESSION['admin_logged_in']);
}

// Minuten ohne Aktivität, nach denen eine Admin-Session automatisch abläuft.
const SESSION_IDLE_TIMEOUT_MIN = 30;

/**
 * Prüft bei jedem Aufruf von requireLogin(), ob die Session wegen
 * Inaktivität abgelaufen ist (kein automatisches Logout vorher - eine
 * einmal gestartete Session lief bisher beliebig lange). Bei Überschreitung
 * wird die Session verworfen und zum Login umgeleitet; ansonsten wird der
 * Aktivitäts-Zeitstempel aktualisiert.
 */
function checkSessionIdleTimeout() : void
{
    if (empty($_SESSION['admin_logged_in'])) {
        return;
    }
    $now  = time();
    $last = (int)($_SESSION['last_activity'] ?? $now);
    if ($now - $last > SESSION_IDLE_TIMEOUT_MIN * 60) {
        $_SESSION = [];
        session_destroy();
        session_start();
        // Neue Session-ID nach dem Neustart erzwingen (Beitrag: Torsten
        // Hofmann) - ohne dies würde PHP die vom Client weiterhin gesendete
        // (alte) Session-ID einfach für die neue, leere Session
        // wiederverwenden. Verhindert Session-Fixation: ein Angreifer, der
        // vor dem Timeout eine Session-ID "vorgegeben" hat, bekommt nach dem
        // automatischen Reset keine gültige ID mehr zugewiesen.
        session_regenerate_id(true);
        flash(t('flash_session_expired'), 'error');
        header('Location: ?action=login');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function requireLogin() : void
{
    checkSessionIdleTimeout();
    if (!isLoggedIn()) {
        header('Location: ?action=login');
        exit;
    }
}

// ── CSRF-Schutz ───────────────────────────────────────────────────────────────
// Ein einziger Token pro Session (nicht pro Formular), damit mehrere gleichzeitig
// geöffnete Tabs/Formulare nicht gegenseitig ihre Tokens invalidieren. Wird auch
// für anonyme Sessions ausgestellt (schützt zusätzlich das Login-Formular selbst
// vor Login-CSRF, bei dem ein Opfer unbemerkt in einen fremden Account eingeloggt
// wird).
function csrfToken() : string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verstecktes Formularfeld mit dem aktuellen CSRF-Token, für jedes POST-Formular. */
function csrfField() : string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

/**
 * Prüft bei jedem POST-Request das mitgesendete csrf_token gegen die Session
 * (zeitkonstanter Vergleich über hash_equals(), um Timing-Angriffe zu
 * vermeiden). Wird zentral in admin.php vor allen POST-Handlern aufgerufen,
 * damit keiner der 30+ POST-Aktionen einzeln abgesichert werden muss und
 * keine versehentlich vergessen wird. Bei fehlendem/falschem Token: Abbruch
 * mit 403 statt stillschweigendem Ausführen der Aktion.
 */
function requireCsrf() : void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $sent = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "403 Forbidden: Ungültiges oder fehlendes CSRF-Token. Bitte die Seite neu laden und erneut versuchen.";
        exit;
    }
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
    } catch (Throwable) {
        return $version = '';
    }
}

/**
 * Absolute Basis-URL des Projekt-Roots (ohne abschließenden Slash), aus dem
 * aktuellen Request abgeleitet – z.B. "https://www.example.org/lmo". Wird für
 * den Link im "Passwort vergessen"-Mail gebraucht, da config.php keine
 * eigene Site-URL-Einstellung hat.
 */
function getSiteBaseUrl() : string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin.php'));
    $dir    = rtrim($dir, '/');
    return $scheme . '://' . $host . $dir;
}

/**
 * Verschickt die "Passwort vergessen"-E-Mail über die eingebaute mail()-
 * Funktion (bewusst ohne externe Mail-Bibliothek, wie beim Rest des
 * Projekts). Gibt zurück, ob mail() den Versand an den lokalen Mailserver
 * angenommen hat (keine Garantie für tatsächliche Zustellung).
 */
function sendPasswordResetEmail(string $toEmail, string $resetLink) : bool
{
    $siteTitle = defined('ADMIN_TITLE') ? ADMIN_TITLE : 'LMOnext';
    $subject   = t('mail_reset_subject', ['site' => $siteTitle]);
    $body      = t('mail_reset_body', ['link' => $resetLink, 'hours' => 4]);

    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host      = preg_replace('/:\d+$/', '', $host); // Port entfernen, falls vorhanden
    $fromAddr  = 'no-reply@' . $host;

    $headers   = "From: {$siteTitle} <{$fromAddr}>\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($toEmail, $subject, $body, $headers);
}

function flash(string $msg, string $type = 'success') : void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() : ? array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url) : never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Validiert eine aus dem Request kommende Redirect-Zielangabe (z.B.
 * $_POST['redirect'], wie sie einige Formulare mitschicken, um nach dem
 * Speichern zur ursprünglichen Seite zurückzukehren). Erlaubt sind NUR
 * interne, mit "?" beginnende Query-Strings (das durchgängige Muster in
 * diesem Projekt, z.B. "?action=archiv") - alles andere (absolute URLs,
 * protokollrelative "//evil.com", "javascript:"-Pseudo-URLs etc.) fällt auf
 * $fallback zurück. Schützt vor Open-Redirect-Phishing, bei dem ein Angreifer
 * eine eigene Ziel-URL in einen sonst legitimen Link/Formular einschleust.
 */
function safeRedirectTarget(mixed $target, string $fallback) : string
{
    if (!is_string($target) || $target === '') {
        return $fallback;
    }
    // Muss mit genau einem "?" beginnen (kein "//", kein "http", kein ":") -
    // alles andere ist potenziell ein externes Ziel.
    if ($target[0] !== '?' || str_starts_with($target, '?/')) {
        return $fallback;
    }
    return $target;
}

// ── Timestamp → DATETIME (unterstützt negative Werte, d.h. vor 1970) ─────────
/**
 * Konvertiert einen Unix-Timestamp (auch negativ) sicher in einen
 * MySQL-DATETIME-String. LMO speichert Timestamps in Lokalzeit → UTC-Offset
 * wird NICHT angewendet; der Wert wird direkt als UTC-äquivalent behandelt,
 * da der LMO selbst keine Zeitzonenkorrektur vornimmt.
 */
function ensureAdminSettings() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('admin_settings').' (
            `key`   VARCHAR(64)   NOT NULL PRIMARY KEY,
            `value` VARCHAR(255)  NOT NULL DEFAULT \'\'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        // Startwerte, die eine Installation von Anfang an sinnvoll erwartbar
        // machen sollen (bei frischen Installationen bereits durch install.php
        // selbst gesetzt, siehe dort - hier zusätzlich für Bestandsinstallationen,
        // die diese install.php-Version nie durchlaufen haben)
        $db->exec('INSERT IGNORE INTO '.tbl('admin_settings') . ' (`key`, `value`) VALUES (\'timezone\', \'Europe/Berlin\')');
        $db->exec('INSERT IGNORE INTO '.tbl('admin_settings') . ' (`key`, `value`) VALUES (\'show_back_link\', \'1\')');
    } catch (Throwable) {}
}

function getAdminSetting(string $key, string $default = '') : string
{
    try {
        $s = getDB()->prepare('SELECT `value` FROM '.tbl('admin_settings') . ' WHERE `key`=?');
        $s->execute([$key]);
        $v = $s->fetchColumn();
        return $v !== false ? (string)$v : $default;
    } catch (Throwable) { return $default; }
}

function getAdminTimezone(): DateTimeZone
{
    static $tz = null;
    if ($tz !== null) return $tz;
    try {
        $tzName = getAdminSetting('timezone', 'Europe/Berlin');
        $tz = new DateTimeZone($tzName);
    } catch (Throwable) {
        $tz = new DateTimeZone('Europe/Berlin');
    }
    return $tz;
}

function tsToDatetime(string|int $ts) : ? string
{
    $ts = (int)$ts;
    if ($ts === 0) {
        return null;
    }
    try {
        $dt = new DateTime('@' . $ts); // UTC-Basis
        $dt->setTimezone(getAdminTimezone()); // In konfigurierte Zeitzone konvertieren
        return $dt->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return null;
    }
}

function generateRoundRobin(array $teamIds) : array
{
    $n = count($teamIds);
    if ($n % 2 !== 0) {
        $teamIds[] = -1;
        $n++;
    }  // -1 = Dummy (spielfrei); 0 ist ein gueltiger Team-Index!

    $rounds = [];
    $fixed  = $teamIds[0];
    $rotate = array_slice($teamIds, 1);
    $half   = $n / 2;

    for ($r = 0; $r < $n - 1; $r++) {
        $ring  = array_merge([$fixed], $rotate);
        $pairs = [];
        for ($i = 0; $i < $half; $i++) {
            $h = $ring[$i];
            $g = $ring[$n - 1 - $i];
            if ($h !== -1 && $g !== -1) {
                $pairs[] = [$h, $g];
            }
        }
        $rounds[$r + 1] = $pairs;
        $last = array_pop($rotate);
        array_unshift($rotate, $last);
    }

    // Rueckrunde: Heimrecht tauschen
    $hinRunden = count($rounds);
    $hinKeys   = array_keys($rounds);
    foreach ($hinKeys as $nr) {
        $rueck = [];
        foreach ($rounds[$nr] as [$h, $g]) { $rueck[] = [$g, $h]; }
        $rounds[$nr + $hinRunden] = $rueck;
    }

    return $rounds;
}

/**
 * Liefert das vorgefertigte DFB-League-Key-Spielplanmuster für die
 * angegebene Teamzahl, falls eines hinterlegt ist (siehe
 * admin/league-key_data.php) – sonst null. Anders als die per Zufall
 * erzeugte generateRoundRobin()-Reihenfolge folgt der League Key einer
 * traditionellen, festen Paarungslogik, wie sie deutsche Fußballverbände
 * für Ligen üblicher Größe verwenden.
 */
function getLeagueKeyPattern(int $teamCount) : ?array
{
    return LEAGUEKEYS_PATTERNS[$teamCount] ?? null;
}

/**
 * Baut den Spielplan für eine reguläre Liga gemäß gewähltem Modus:
 * 'leaguekey' (falls für die Teamzahl vorhanden, sonst automatisch
 * Rückfall auf 'random'), 'random' (bisheriges Verhalten, generateRoundRobin()
 * mit fortlaufender Team-Reihenfolge) oder 'none' (kein Spielplan).
 *
 * @return array<int,array<int,array{0:int,1:int}>>
 */
function buildScheduleForMode(int $teamCount, string $mode) : array
{
    if ($mode === 'none') {
        // Trotz "kein Spielplan" soll die Liga bereits die richtige Anzahl
        // Spieltage/Begegnungen bekommen (wie ein normaler Rundenplan für
        // diese Teamzahl) – nur eben mit einem Leerteam ("___", siehe
        // getOrCreateDummyTeam()) statt echter Paarungen. So kann der Admin
        // jede Begegnung anschließend einzeln manuell im Spieltag-Editor
        // eintragen, ohne vorher Spieltage/Partien-Zeilen selbst anlegen zu
        // müssen. -1 ist der von createLigaInDB() bereits unterstützte
        // Platzhalter-Wert für "Leerteam".
        $shape = generateRoundRobin(range(0, $teamCount - 1));
        $blank = [];
        foreach ($shape as $nr => $pairs) {
            $blank[$nr] = array_fill(0, count($pairs), [-1, -1]);
        }
        return $blank;
    }
    if ($mode === 'leaguekey') {
        $pattern = getLeagueKeyPattern($teamCount);
        if ($pattern !== null) {
            return $pattern;
        }
        // Kein Muster für diese Teamzahl hinterlegt -> auf Zufall zurückfallen
    }
    return generateRoundRobin(range(0, $teamCount - 1));
}

// ── Routing ── (existing code continues below)
function ensureArchivColumns() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('liga_archiv_folders').' (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `parent_id`    INT          NULL DEFAULT NULL,
            `name`         VARCHAR(120) NOT NULL DEFAULT \'\',
            `beschreibung` VARCHAR(255) NOT NULL DEFAULT \'\',
            `sort`         SMALLINT     NOT NULL DEFAULT 0,
            KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('liga'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('archiv_folder_id', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga').' ADD COLUMN `archiv_folder_id` INT NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

function ensureLastLoginColumn() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('admin_users'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('last_login', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('admin_users').' ADD COLUMN `last_login` DATETIME NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

/**
 * Legt die admin_users.email-Spalte + die admin_password_resets-Tabelle an,
 * falls sie fehlen ("Passwort vergessen"-Funktion). Frische Installationen
 * bekommen beides schon direkt über install.php – dieselbe Migration hier
 * sorgt dafür, dass es auch für bereits bestehende Installationen ohne
 * erneuten Install-Lauf funktioniert (gleiches Muster wie
 * ensureLastLoginColumn()).
 */
function ensurePasswordResetSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('admin_users'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('email', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('admin_users').' ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL');
        }
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('admin_password_resets').' (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`    INT          NOT NULL,
            `token`      VARCHAR(64)  NOT NULL,
            `expires_at` DATETIME     NOT NULL,
            `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `token` (`token`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (Throwable) {}
}

// ── Login-Rate-Limiting ───────────────────────────────────────────────────────
const LOGIN_MAX_ATTEMPTS  = 5;   // erlaubte Fehlversuche im Zeitfenster
const LOGIN_LOCKOUT_MIN   = 15;  // Sperrzeit in Minuten nach Erreichen des Limits

/**
 * Legt die login_attempts-Tabelle an, falls sie fehlt (frische
 * Installationen bekommen sie schon über install.php).
 */
/**
 * Prüft die für den laufenden Betrieb relevanten PHP-Erweiterungen. Eigen-
 * ständig statt install.php's checkEnvironment() zu nutzen, da sich
 * install.php nach erfolgreicher Installation selbst löscht (siehe dortige
 * selfDestructAndRedirect()) und danach nicht mehr verfügbar wäre. Für die
 * Info-Seite unter Einstellungen (siehe admin/view_settings.php).
 *
 * @return array<int,array{label:string,ok:bool,required:bool,info:string}>
 */
// ── Audit-Log für Admin-Aktionen ──────────────────────────────────────────────
/**
 * Legt die admin_audit_log-Tabelle an, falls sie fehlt (frische
 * Installationen bekommen sie schon über install.php).
 */
function ensureAuditLogTable() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        getDB()->exec('CREATE TABLE IF NOT EXISTS '.tbl('admin_audit_log').' (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `username`   VARCHAR(80)   NOT NULL,
            `action`     VARCHAR(60)   NOT NULL,
            `details`    VARCHAR(500)  NULL DEFAULT NULL,
            `ip`         VARCHAR(45)   NOT NULL,
            `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `created_at` (`created_at`),
            KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (Throwable) {}
}

/**
 * Protokolliert eine sicherheitsrelevante Admin-Aktion (Login, Liga
 * löschen, Backup wiederherstellen, Benutzer anlegen/löschen, Einstellungen
 * ändern, Import etc.). $action ist eine kurze, feste Kennung (z.B.
 * "liga_deleted"), $details ein optionaler Klartext-Zusatz (z.B. der
 * Liganame) - beide werden bewusst NICHT über t() übersetzt, damit das Log
 * unabhängig von der aktuell eingestellten Sprache konsistent lesbar bleibt.
 * Wird unter Administrator → Log angezeigt (siehe admin/view_users.php).
 */
function logAdminAction(string $action, string $details = '') : void
{
    ensureAuditLogTable();
    try {
        getDB()->prepare('INSERT INTO '.tbl('admin_audit_log').' (username, action, details, ip) VALUES (?, ?, ?, ?)')
               ->execute([$_SESSION['admin_user'] ?? '(unbekannt)', $action, $details !== '' ? $details : null, loginClientIp()]);
    } catch (Throwable) {}
}

/**
 * Prüft die für den laufenden Betrieb relevanten PHP-Erweiterungen. Eigen-
 * ständig statt install.php's checkEnvironment() zu nutzen, da sich
 * install.php nach erfolgreicher Installation selbst löscht (siehe dortige
 * selfDestructAndRedirect()) und danach nicht mehr verfügbar wäre. Für die
 * Info-Seite unter Einstellungen (siehe admin/view_settings.php).
 *
 * @return array<int,array{label:string,ok:bool,required:bool,info:string}>
 */
function checkRuntimeExtensions() : array
{
    $checks = [];
    $checks[] = ['label' => 'PDO', 'ok' => extension_loaded('pdo'), 'required' => true,
                 'info' => extension_loaded('pdo') ? t('install_available') : t('install_missing_ini')];
    $checks[] = ['label' => 'PDO MySQL', 'ok' => extension_loaded('pdo_mysql'), 'required' => true,
                 'info' => extension_loaded('pdo_mysql') ? t('install_available') : t('install_missing_pdo_mysql')];
    $checks[] = ['label' => 'mbstring', 'ok' => extension_loaded('mbstring'), 'required' => false,
                 'info' => extension_loaded('mbstring') ? t('install_available') : t('install_recommended_missing')];
    $checks[] = ['label' => 'GD', 'ok' => extension_loaded('gd'), 'required' => false,
                 'info' => extension_loaded('gd') ? t('install_available') : t('install_recommended_missing')];
    // DOM/libxml wird seit der SVG-Sanitisierung beim Team-Logo-Upload
    // benötigt (siehe sanitizeSvgContent() oben) - ohne diese Erweiterung
    // werden SVG-Uploads komplett abgelehnt statt eine ungeprüfte Datei zu
    // akzeptieren.
    $checks[] = ['label' => 'DOM/libxml', 'ok' => class_exists('DOMDocument'), 'required' => false,
                 'info' => class_exists('DOMDocument') ? t('install_available') : t('install_recommended_missing')];
    $hasImagick  = class_exists('Imagick');
    $hasRsvgTool = function_exists('shell_exec') && trim((string)@shell_exec('command -v rsvg-convert 2>/dev/null')) !== '';
    $svgRasterInfo = ($hasImagick || $hasRsvgTool)
        ? t('install_available') . ' (' . implode(' + ', array_filter([$hasImagick ? 'Imagick' : null, $hasRsvgTool ? 'rsvg-convert' : null])) . ')'
        : t('install_recommended_missing');
    $checks[] = ['label' => 'Imagick / rsvg-convert', 'ok' => ($hasImagick || $hasRsvgTool), 'required' => false, 'info' => $svgRasterInfo];
    $checks[] = ['label' => 'ZipArchive', 'ok' => class_exists('ZipArchive'), 'required' => false,
                 'info' => class_exists('ZipArchive') ? t('install_available') : t('install_recommended_missing')];
    $checks[] = ['label' => 'bzip2', 'ok' => function_exists('bzcompress'), 'required' => false,
                 'info' => function_exists('bzcompress') ? t('install_available') : t('install_recommended_missing')];
    return $checks;
}

/**
 * Ermittelt die Besucher-IP für das Rate-Limiting. X-Forwarded-For wird nur
 * verwendet, wenn REMOTE_ADDR nicht gesetzt ist (CLI/Tests) - im Normalfall
 * zählt ausschließlich REMOTE_ADDR, da X-Forwarded-For vom Client frei
 * mitgeschickt und damit gefälscht werden kann (ein Angreifer könnte sich
 * sonst durch wechselnde Fake-Header selbst von der Sperre befreien).
 */
function loginClientIp() : string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Legt die login_attempts-Tabelle an, falls sie fehlt (frische
 * Installationen bekommen sie schon über install.php).
 */
function ensureLoginAttemptsTable() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        getDB()->exec('CREATE TABLE IF NOT EXISTS '.tbl('login_attempts').' (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `username`     VARCHAR(80)  NOT NULL,
            `ip`           VARCHAR(45)  NOT NULL,
            `attempted_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `username` (`username`),
            KEY `ip` (`ip`),
            KEY `attempted_at` (`attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (Throwable) {}
}

/**
 * Prüft, ob für den angegebenen Benutzernamen ODER die aktuelle IP aktuell
 * eine Sperre wegen zu vieler Fehlversuche besteht. Beide werden geprüft,
 * damit weder "viele Versuche auf einen Benutzernamen" noch "viele Versuche
 * von einer IP über verschiedene Benutzernamen" das Limit umgehen.
 *
 * @return int Verbleibende Sperrzeit in Sekunden, 0 = keine Sperre aktiv.
 */
function loginRateLimitSecondsLeft(string $username) : int
{
    ensureLoginAttemptsTable();
    try {
        $db = getDB();
        $since = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MIN * 60);
        $s = $db->prepare(
            'SELECT COUNT(*) AS cnt, MAX(attempted_at) AS last_at FROM '.tbl('login_attempts').'
             WHERE attempted_at > ? AND (username = ? OR ip = ?)'
        );
        $s->execute([$since, $username, loginClientIp()]);
        $row = $s->fetch();
        if ($row === false || (int)$row['cnt'] < LOGIN_MAX_ATTEMPTS) {
            return 0;
        }
        $lastAt = strtotime((string)$row['last_at']);
        $unlockAt = $lastAt + LOGIN_LOCKOUT_MIN * 60;
        return max(0, $unlockAt - time());
    } catch (Throwable) {
        return 0; // im Zweifel nicht aussperren, falls die Tabelle mal nicht erreichbar ist
    }
}

/** Trägt einen fehlgeschlagenen Login-Versuch ein. */
function recordFailedLoginAttempt(string $username) : void
{
    ensureLoginAttemptsTable();
    try {
        getDB()->prepare('INSERT INTO '.tbl('login_attempts').' (username, ip) VALUES (?, ?)')
               ->execute([$username, loginClientIp()]);
    } catch (Throwable) {}
}

/** Setzt den Fehlversuch-Zähler nach erfolgreichem Login zurück. */
function clearLoginAttempts(string $username) : void
{
    ensureLoginAttemptsTable();
    try {
        getDB()->prepare('DELETE FROM '.tbl('login_attempts').' WHERE username = ? OR ip = ?')
               ->execute([$username, loginClientIp()]);
    } catch (Throwable) {}
}

/**
 * Team-Verknüpfungen: nicht-destruktive Verbindung zwischen zwei
 * eigenständigen Team-Datensätzen (im Unterschied zu mergeTeams(), das einen
 * Datensatz permanent löscht). Deckt drei Fälle ab, ohne sie technisch
 * unterscheiden zu müssen (siehe resolveLinkedTeamIds() in data_liga.php für
 * die transitive Auflösung):
 *   - Umbenennung: A↔B (eine Verknüpfung)
 *   - Fusion: A↔C, B↔C (zwei Verknüpfungen zum neuen Verein)
 *   - Abspaltung: A↔B, A↔C (zwei Verknüpfungen vom ursprünglichen Verein)
 * Wird u.a. vom Teamvergleich (H2H) genutzt, um Spiele unter allen
 * verknüpften (historischen) Namen mit anzuzeigen.
 */
function ensureTeamLinksSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('team_links').' (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `team_a_id`      INT NOT NULL,
            `team_b_id`      INT NOT NULL,
            `type`           ENUM(\'umbenennung\',\'fusion\',\'abspaltung\',\'sonstige\') NOT NULL DEFAULT \'umbenennung\',
            `note`           VARCHAR(255) NULL DEFAULT NULL,
            `newer_team_id`  INT NULL DEFAULT NULL,
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `team_a_id` (`team_a_id`),
            KEY `team_b_id` (`team_b_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        // Migration für bereits bestehende Installationen (Tabelle existierte
        // schon vor Einführung von newer_team_id, siehe Changelog 1.9.0)
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('team_links'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('newer_team_id', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('team_links').' ADD COLUMN `newer_team_id` INT NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

/**
 * Alle direkten Verknüpfungen eines Teams (für die Anzeige/Verwaltung in
 * "Teams (global)"), inkl. Name des jeweils anderen Teams. Liefert NUR die
 * direkten Verknüpfungen (nicht transitiv aufgelöst) – für die
 * Vergleichsauflösung siehe resolveLinkedTeamIds() in data_liga.php.
 */
function getTeamLinksForTeam(int $teamId) : array
{
    ensureTeamLinksSchema();
    try {
        $s = getDB()->prepare(
            'SELECT tl.id, tl.type, tl.note, tl.newer_team_id,
                    CASE WHEN tl.team_a_id = ? THEN tl.team_b_id ELSE tl.team_a_id END AS other_id,
                    tg.name AS other_name
               FROM '.tbl('team_links').' tl
               JOIN '.tbl('teams_global').' tg
                 ON tg.id = CASE WHEN tl.team_a_id = ? THEN tl.team_b_id ELSE tl.team_a_id END
              WHERE tl.team_a_id = ? OR tl.team_b_id = ?
              ORDER BY tg.name'
        );
        $s->execute([$teamId, $teamId, $teamId, $teamId]);
        return $s->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Legt eine neue Team-Verknüpfung an. Verhindert Duplikate (in beide
 * Richtungen geprüft) und Selbstverknüpfung. $newerTeamId (optional) legt
 * fest, welches der beiden Teams der heutige/aktuelle Name ist – wichtig für
 * die "(heute TEAM_HEUTE)"-Kennzeichnung im Teamvergleich, die sonst vom
 * zufällig angeklickten Team abhinge statt von einer festen Richtung
 * (z.B. bei einer Umbenennung: der alte Name soll IMMER als "heute
 * NEUER_NAME" gekennzeichnet werden, unabhängig davon, von welchem Spiel aus
 * man den Vergleich öffnet). Muss einer der beiden Team-IDs entsprechen
 * oder null sein (= Richtung unbekannt/nicht festgelegt).
 */
function addTeamLink(int $teamAId, int $teamBId, string $type, string $note, ?int $newerTeamId = null) : bool
{
    if ($teamAId <= 0 || $teamBId <= 0 || $teamAId === $teamBId) {
        return false;
    }
    if (!in_array($type, ['umbenennung', 'fusion', 'abspaltung', 'sonstige'], true)) {
        $type = 'sonstige';
    }
    if ($newerTeamId !== null && $newerTeamId !== $teamAId && $newerTeamId !== $teamBId) {
        $newerTeamId = null;
    }
    ensureTeamLinksSchema();
    try {
        $db = getDB();
        $exists = $db->prepare(
            'SELECT id FROM '.tbl('team_links').'
              WHERE (team_a_id = ? AND team_b_id = ?) OR (team_a_id = ? AND team_b_id = ?)'
        );
        $exists->execute([$teamAId, $teamBId, $teamBId, $teamAId]);
        if ($exists->fetch() !== false) {
            return false; // schon verknüpft
        }
        $db->prepare('INSERT INTO '.tbl('team_links').' (team_a_id,team_b_id,type,note,newer_team_id) VALUES (?,?,?,?,?)')
           ->execute([$teamAId, $teamBId, $type, $note !== '' ? $note : null, $newerTeamId]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Ändert nachträglich, welches Team einer bestehenden Verknüpfung der
 * heutige/aktuelle Name ist (siehe addTeamLink()). $newerTeamId muss einem
 * der beiden verknüpften Teams entsprechen, oder null (= zurücksetzen auf
 * "unbekannt").
 */
function setTeamLinkDirection(int $linkId, ?int $newerTeamId) : bool
{
    ensureTeamLinksSchema();
    try {
        $db = getDB();
        $s = $db->prepare('SELECT team_a_id, team_b_id FROM '.tbl('team_links').' WHERE id = ?');
        $s->execute([$linkId]);
        $row = $s->fetch();
        if ($row === false) {
            return false;
        }
        if ($newerTeamId !== null && $newerTeamId !== (int)$row['team_a_id'] && $newerTeamId !== (int)$row['team_b_id']) {
            return false;
        }
        $db->prepare('UPDATE '.tbl('team_links').' SET newer_team_id = ? WHERE id = ?')->execute([$newerTeamId, $linkId]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/** Löscht eine Team-Verknüpfung anhand ihrer ID. */
function deleteTeamLink(int $linkId) : bool
{
    ensureTeamLinksSchema();
    try {
        getDB()->prepare('DELETE FROM '.tbl('team_links').' WHERE id = ?')->execute([$linkId]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Stellt sicher, dass teams_global.url existiert (Website-/Vereinslink,
 * siehe "Teams (global)" → Logo & Link). Das Team-Logo selbst braucht keine
 * eigene Spalte – es liegt einfach unter assets/img/teams/{id}.{ext} und
 * wird beim Anzeigen per Dateisystem-Check gefunden (siehe
 * findTeamLogoPath() in handler_liga.php).
 */
function ensureTeamUrlSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('teams_global'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('url', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('teams_global').' ADD COLUMN `url` VARCHAR(500) NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

// Reihenfolge = Priorität bei mehreren vorhandenen Dateien für dieselbe
// Team-ID (z.B. team.svg UND team.png) - SVG zuerst (beste Qualität), dann
// Rasterformate. Diese Konstante wird nur für die Admin-Vorschau (Browser-
// Ausgabe) und zum Löschen genutzt, NIE für den PDF-Export - der hat seine
// eigene, unabhängige Priorität (Rasterformate zuerst), siehe
// TeamFormattingTrait::TEAM_LOGO_EXT_LIST_PDF für die ausführliche
// Begründung. Admin-Vorschau soll dasselbe zeigen wie die echte Website.
const TEAM_LOGO_ALLOWED_EXT  = ['svg', 'png', 'jpg', 'jpeg', 'gif'];
const TEAM_LOGO_MIN_HEIGHT_PX = 50;

/** Absoluter Dateisystempfad zum Team-Logo-Verzeichnis (wird bei Bedarf angelegt). */
function teamLogoDir() : string
{
    $dir = dirname(__DIR__) . '/assets/img/teams';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Sucht ein hochgeladenes Logo für die angegebene Team-ID (Dateiname
 * "{id}.{ext}", siehe TEAM_LOGO_ALLOWED_EXT). Gibt den Web-Pfad relativ zum
 * Projekt-Root zurück (z.B. "assets/img/teams/42.png"), oder null wenn kein
 * Logo hinterlegt ist.
 */
function findTeamLogoPath(int $teamId) : ?string
{
    $dir = teamLogoDir();
    foreach (TEAM_LOGO_ALLOWED_EXT as $ext) {
        if (is_file($dir . '/' . $teamId . '.' . $ext)) {
            return 'assets/img/teams/' . $teamId . '.' . $ext;
        }
    }
    return null;
}

/** Entfernt ein evtl. vorhandenes Logo (alle möglichen Endungen) für die Team-ID. */
function deleteTeamLogo(int $teamId) : void
{
    $dir = teamLogoDir();
    foreach (TEAM_LOGO_ALLOWED_EXT as $ext) {
        $path = $dir . '/' . $teamId . '.' . $ext;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * Prüft und speichert ein hochgeladenes Team-Logo ($_FILES-Eintrag). Erlaubt
 * sind SVG, JPEG/JPG, PNG und GIF, mit einer Mindesthöhe von
 * TEAM_LOGO_MIN_HEIGHT_PX Pixeln. Bei SVG lässt sich die Höhe nicht immer
 * zuverlässig aus der Datei auslesen (Vektorgrafik, oft ohne feste
 * Pixelmaße) – dort wird nur geprüft, wenn width/height/viewBox tatsächlich
 * vorhanden sind und explizit zu klein wären; ansonsten wird SVG nicht
 * pauschal abgelehnt. Ein evtl. vorhandenes altes Logo (auch mit anderer
 * Dateiendung) wird vorher entfernt, damit nicht mehrere Logo-Dateien für
 * dieselbe Team-ID gleichzeitig existieren.
 *
 * @return array{ok:bool, error:?string}
 */
/**
 * Entfernt aktive Inhalte aus einem hochgeladenen SVG (Sicherheitsfix,
 * Punkt 3 aus dem Security-Audit): <script>-Elemente, alle "on*"-Event-
 * Handler-Attribute (onload, onclick, onerror, ...), "javascript:"-URIs in
 * beliebigen Attributen (href, xlink:href, ...) sowie <foreignObject>
 * (kann beliebiges eingebettetes HTML enthalten). Nutzt DOMDocument statt
 * reiner Regex-Ersetzung für robustes, korrektes Parsen; externe
 * Entities/DTDs werden dabei nicht aufgelöst (schützt zusätzlich vor XXE).
 * Bei ungültigem/nicht parsbarem XML wird null zurückgegeben - der Aufrufer
 * lehnt die Datei dann komplett ab, statt eine evtl. noch gefährliche Datei
 * unverändert durchzulassen.
 */
function sanitizeSvgContent(string $content) : ?string
{
    // DOMDocument ist auf praktisch jedem PHP-Hosting vorhanden (ext-dom),
    // aber defensiv geprüft: fehlt die Klasse ausnahmsweise, wird die Datei
    // abgelehnt statt sie unbereinigt zu akzeptieren.
    if (!class_exists('DOMDocument')) {
        return null;
    }
    $prevEntityLoader = null;
    if (function_exists('libxml_disable_entity_loader')) {
        // In PHP 8 ist externes Entity-Laden ohnehin standardmäßig aus,
        // die Funktion selbst ist ab 8.0 deprecated - defensiv trotzdem
        // aufrufen, falls auf einer älteren PHP-Version ausgeführt.
        $prevEntityLoader = @libxml_disable_entity_loader(true);
    }
    $prevUseErrors = libxml_use_internal_errors(true);

    $dom = new \DOMDocument();
    $ok = $dom->loadXML($content, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prevUseErrors);
    if ($prevEntityLoader !== null) {
        @libxml_disable_entity_loader($prevEntityLoader);
    }
    if (!$ok) {
        return null;
    }

    $xpath = new \DOMXPath($dom);

    // 1. Alle <script>-Elemente komplett entfernen (unabhängig vom Namespace).
    foreach (iterator_to_array($xpath->query('//*[local-name()="script"]')) as $node) {
        $node->parentNode?->removeChild($node);
    }

    // 2. <foreignObject> entfernen - kann beliebiges eingebettetes HTML
    // (inkl. <script>) enthalten, das von SVG-Parsern oft anders behandelt wird.
    foreach (iterator_to_array($xpath->query('//*[local-name()="foreignObject"]')) as $node) {
        $node->parentNode?->removeChild($node);
    }

    // 3. Auf allen verbleibenden Elementen: "on*"-Attribute entfernen und
    // "javascript:"-URIs in href/xlink:href/... neutralisieren.
    foreach (iterator_to_array($xpath->query('//*')) as $el) {
        if (!($el instanceof \DOMElement)) {
            continue;
        }
        $toRemove = [];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            if (str_starts_with($name, 'on')) {
                $toRemove[] = $attr->nodeName;
                continue;
            }
            $value = trim($attr->nodeValue ?? '');
            // Führende Steuerzeichen/Whitespace-Tricks (z.B. "java\tscript:")
            // vor dem Vergleich entfernen.
            $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', $value) ?? $value);
            if (str_starts_with($normalized, 'javascript:') || str_starts_with($normalized, 'data:text/html')) {
                $toRemove[] = $attr->nodeName;
            }
        }
        foreach ($toRemove as $attrName) {
            $el->removeAttribute($attrName);
        }
    }

    $result = $dom->saveXML();
    return $result !== false ? $result : null;
}

function saveTeamLogoUpload(int $teamId, array $file) : array
{

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'error' => null]; // nichts hochgeladen -> kein Fehler, einfach nichts tun
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => t('teams_logo_err_upload')];
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') { $ext = 'jpg'; }
    if (!in_array($ext, ['svg', 'jpg', 'png', 'gif'], true)) {
        return ['ok' => false, 'error' => t('teams_logo_err_format')];
    }

    $tmpPath = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => t('teams_logo_err_upload')];
    }

    $sanitizedSvgContent = null;
    if ($ext === 'svg') {
        $content = (string)file_get_contents($tmpPath);
        // Grobe Inhaltsprüfung statt reiner Endungs-Prüfung: muss ein
        // <svg>-Wurzelelement enthalten (schützt vor umbenannten Dateien, die
        // gar kein SVG sind).
        if (stripos($content, '<svg') === false) {
            return ['ok' => false, 'error' => t('teams_logo_err_invalid')];
        }
        // Höhe nur prüfen, wenn sie sich eindeutig aus width/height/viewBox
        // ergibt – SVGs ohne feste Pixelmaße werden nicht abgelehnt.
        if (preg_match('/height=["\']?([\d.]+)/i', $content, $hm)) {
            if ((float)$hm[1] < TEAM_LOGO_MIN_HEIGHT_PX) {
                return ['ok' => false, 'error' => t('teams_logo_err_too_small', ['min' => TEAM_LOGO_MIN_HEIGHT_PX])];
            }
        }
        // Sicherheitsfix (Security-Audit Punkt 3): Scripts, Event-Handler und
        // javascript:-URIs entfernen, bevor die Datei überhaupt gespeichert
        // wird. Ist die Datei danach nicht mehr sauber als XML parsbar, wird
        // sie komplett abgelehnt statt eine ggf. noch gefährliche Version zu
        // akzeptieren.
        $sanitizedSvgContent = sanitizeSvgContent($content);
        if ($sanitizedSvgContent === null) {
            return ['ok' => false, 'error' => t('teams_logo_err_invalid')];
        }
    } else {
        $info = @getimagesize($tmpPath);
        if ($info === false) {
            return ['ok' => false, 'error' => t('teams_logo_err_invalid')];
        }
        $detectedExt = image_type_to_extension((int)$info[2], false);
        $detectedExt = $detectedExt === 'jpeg' ? 'jpg' : $detectedExt;
        if ($detectedExt !== $ext) {
            return ['ok' => false, 'error' => t('teams_logo_err_invalid')];
        }
        if ((int)$info[1] < TEAM_LOGO_MIN_HEIGHT_PX) {
            return ['ok' => false, 'error' => t('teams_logo_err_too_small', ['min' => TEAM_LOGO_MIN_HEIGHT_PX])];
        }
    }

    deleteTeamLogo($teamId); // altes Logo (ggf. andere Endung) zuerst entfernen
    $destPath = teamLogoDir() . '/' . $teamId . '.' . $ext;
    if ($sanitizedSvgContent !== null) {
        // Bereinigten Inhalt schreiben statt move_uploaded_file() - sonst
        // würden trotz Sanitizing die ORIGINALEN (ungefilterten) Upload-Bytes
        // gespeichert werden.
        if (@file_put_contents($destPath, $sanitizedSvgContent) === false) {
            return ['ok' => false, 'error' => t('teams_logo_err_upload')];
        }
    } elseif (!move_uploaded_file($tmpPath, $destPath)) {
        return ['ok' => false, 'error' => t('teams_logo_err_upload')];
    }
    @chmod($destPath, 0644);
    return ['ok' => true, 'error' => null];
}

function ensureSpielstatusColumns() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('liga_partien'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('status', $cols, true)) {
            // 0 = normal, 1 = nach Elfmeterschießen (i.E.), 2 = nach Verlängerung (n.V.) — LMO-Original-Mapping
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `status` TINYINT NOT NULL DEFAULT 0');
        }
        if (!in_array('bericht_url', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `bericht_url` VARCHAR(500) NULL DEFAULT NULL');
        }
        // "nicht_gewertet" (Vereins-Spielbetrieb-Einstellung mit
        // rückwirkender Annullierung ALLER Spiele, z.B. Lizenzentzug) - bewusst
        // eine EIGENE, von "status" (i.E./n.V.) UNABHÄNGIGE Spalte statt einen
        // dritten status-Wert einzuführen: ein Elfmeterschießen-Spiel, das
        // nachträglich annulliert wird, soll diese Information nicht verlieren,
        // falls die Annullierung später wieder aufgehoben wird. Wirkt in
        // computeStandings() (StandingsTrait.php) - ein so markiertes Spiel
        // zählt für KEINES der beiden Teams (weder Sp/S/U/N noch Tore/Punkte),
        // bleibt aber im Spielplan mit Ergebnis + Hinweis "nicht gewertet"
        // sichtbar (siehe statusSuffix()).
        if (!in_array('nicht_gewertet', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `nicht_gewertet` TINYINT(1) NOT NULL DEFAULT 0');
        }
        // "gt_entscheidung" (Grüne-Tisch-Entscheidung / Sportgericht-
        // Wertung nach DFB-Rechts- und Verfahrensordnung) - 0 = keine
        // Entscheidung, 1 = Heimteam gewinnt am grünen Tisch, 2 = Gastteam
        // gewinnt am grünen Tisch. Wirkt in computeStandings() über
        // LigaService::gtCreditedScore() - Standardwertung 3:0 (bzw. 0:3) für
        // die unschuldige/schuldige Mannschaft, außer ein real gespieltes
        // Ergebnis war für die unschuldige Mannschaft noch höher (siehe
        // gtCreditedScore() für die vollständige Regel). Bewusst eine eigene
        // Spalte statt einer Kombination aus status/nicht_gewertet, da hier -
        // anders als bei beiden anderen - ein Team trotzdem Punkte/Tore
        // gutgeschrieben bekommt.
        if (!in_array('gt_entscheidung', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `gt_entscheidung` TINYINT NOT NULL DEFAULT 0');
        }
        // "gt_grund": freier Zusatztext zur Grüne-Tisch-
        // Entscheidung (z.B. "gravierender Regelverstoß beider Teams",
        // "kein sportärztlicher Nachweis erbracht") - wird im Ergebniseditor
        // nur eingeblendet, wenn eine Entscheidung ausgewählt ist (siehe
        // view_spieltag.php), und zusätzlich zum bestehenden Fußnotentext in
        // renderGtFootnotes() angezeigt (siehe RenderViewsTrait.php). Bewusst
        // TEXT statt VARCHAR, da keine sinnvolle Obergrenze für einen
        // Begründungstext existiert.
        if (!in_array('gt_grund', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `gt_grund` TEXT NULL');
        }
    } catch (Throwable) {}
}

/**
 * Admin-eigenständige Kopie von TeamRepositoryTrait::migrateFavSelTeamToStableId()
 * (siehe dort für den vollständigen Hintergrund zum behobenen favTeam/selTeam-
 * Bug). KRITISCHER Bugfix (gemeldet: Liga-Einstellungen komplett kaputt -
 * Teams-Tab leer, alle Tab-Navigations-Links ohne "id="): admin/data_loader.php
 * rief ursprünglich \LMOnext\Liga\LigaService::migrateFavSelTeamToStableId()
 * auf - diese Klasse (und ihre Trait-Kette) wird aber AUSSCHLIESSLICH von
 * frontend/data_liga.php geladen, NICHT von admin/bootstrap.php (bewusste
 * Trennung von Admin-/Frontend-Bootstrap, siehe Kommentar bei den Sport-
 * Profil-Requires weiter oben - getDB()/tbl() sind hier eigenständig
 * definiert). Der Aufruf einer nicht geladenen Klasse wirft einen
 * \Error("Class ... not found"), der vom äußeren catch(Throwable){} in
 * admin/data_loader.php lautlos gefangen wurde, OHNE dass der Rest des
 * try-Blocks (inkl. $ligaSettingsData['teams']/['lid']) noch ausgeführt
 * wurde - dadurch blieb $lid in view_liga_settings.php leer (jede Tab-
 * Navigation verlor die Liga-ID) und die Teams-Liste komplett leer, für
 * JEDE Liga, deren Migrations-Flag noch nicht gesetzt war. Eigenständige
 * Kopie statt die Trait-Kette im Admin-Bereich einzubinden, konsistent mit
 * dem bereits etablierten Muster dieses Projekts (z.B. team_matching.php
 * im url-import-Addon: "eigene, umbenannte Kopie statt Wiederverwendung").
 */
function adminMigrateFavSelTeamToStableId(int $ligaId, array &$opts) : void
{
    if (($opts['FavSelTeamMigrated'] ?? '0') === '1') {
        return;
    }
    try {
        $db = getDB();
        $s = $db->prepare(
            'SELECT g.id
               FROM ' . tbl('teams_global') . ' g
               JOIN ' . tbl('liga_teams') . ' lt ON lt.team_id = g.id
              WHERE lt.liga_id = ?
              ORDER BY g.name'
        );
        $s->execute([$ligaId]);
        $orderedIds = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));

        $stmt = $db->prepare(
            'INSERT INTO ' . tbl('liga_options') . ' (liga_id, option_key, option_value)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
        );
        foreach (['favTeam', 'selTeam'] as $key) {
            $oldPosition = (int)($opts[$key] ?? 0);
            if ($oldPosition > 0 && isset($orderedIds[$oldPosition - 1])) {
                $newId = $orderedIds[$oldPosition - 1];
                $stmt->execute([$ligaId, $key, (string)$newId]);
                $opts[$key] = (string)$newId;
            }
        }
        $stmt->execute([$ligaId, 'FavSelTeamMigrated', '1']);
        $opts['FavSelTeamMigrated'] = '1';
    } catch (Throwable) {
        // Bei einem Fehler bleibt das Flag unbesetzt - die Migration wird
        // beim nächsten Aufruf erneut versucht.
    }
}

/**
 * On-demand-Migration für die Sport-Profile-Erweiterung (Beitrag: Torsten
 * Hofmann) - sport_type auf liga, extra_data (Sätze/Drittel/Viertel) auf
 * liga_partien. Bugfix: fehlte hier ursprünglich komplett - führte auf jeder
 * Installation, die install.php seit der Erweiterung noch nicht erneut
 * ausgeführt hatte, zu "Unknown column 'sport_type'" beim .l98-Import UND
 * beim Öffnen der Liga-Einstellungen (admin/data_loader.php liest
 * sport_type direkt, ohne eigene Absicherung). Analog zu
 * ensureSpielstatusColumns() bewusst zentral hier statt einzeln in jedem
 * Aufrufer geprüft.
 */
function ensureSportProfileColumns() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $ligaCols = $db->query('SHOW COLUMNS FROM '.tbl('liga'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('sport_type', $ligaCols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga')." ADD COLUMN `sport_type` VARCHAR(20) NOT NULL DEFAULT 'football' AFTER `archiv_folder_id`");
        }
        $partienCols = $db->query('SHOW COLUMNS FROM '.tbl('liga_partien'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('extra_data', $partienCols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `extra_data` JSON NULL DEFAULT NULL AFTER `g_tore`');
        }
    } catch (Throwable) {}
}

function ensureKoLabelColumns() : void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $db  = getDB();
        $tbl = DB_PREFIX . 'liga_partien';
        $cols = $db->query("SHOW COLUMNS FROM `{$tbl}`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('heim_label', $cols, true)) {
            $db->exec("ALTER TABLE `{$tbl}` ADD COLUMN `heim_label` VARCHAR(120) NULL DEFAULT NULL AFTER `heim_id`");
        }
        if (!in_array('gast_label', $cols, true)) {
            $db->exec("ALTER TABLE `{$tbl}` ADD COLUMN `gast_label` VARCHAR(120) NULL DEFAULT NULL AFTER `gast_id`");
        }
        // heim_id / gast_id dürfen jetzt NULL sein (Platzhalter)
        $db->exec("ALTER TABLE `{$tbl}` MODIFY COLUMN `heim_id` INT NULL, MODIFY COLUMN `gast_id` INT NULL");
    } catch (Throwable) { /* nicht-kritisch – läuft schon */ }
}


// Wert wird in liga_spieltage.modus gespeichert.
// 0 = normale Liga-Runde (kein KO)
// KO-Modi:
const KO_MODUS = [
    1 => 'Einzelspiel',
    2 => 'Hin- und Rückspiel',
    3 => 'Best of 3',
    5 => 'Best of 5',
    7 => 'Best of 7',
];
// Standard-KO-Modus bei Neu-Erstellung
const KO_MODUS_DEFAULT = 1;

// Übersetzte Anzeige-Bezeichnung für einen KO-Modus-Wert (KO_MODUS-Keys bleiben
// stabile interne Werte, nur das Label wird über t() lokalisiert).
function koModusLabel(int $val) : string
{
    return match ($val) {
        1 => t('ko_mode_1'),
        2 => t('ko_mode_2'),
        3 => t('ko_mode_3'),
        5 => t('ko_mode_5'),
        7 => t('ko_mode_7'),
        default => (string)$val,
    };
}

/**
 * Ermittelt den Spieltag/die Runde, die beim Öffnen einer Liga
 * ("?action=liga_detail&id=…") direkt angezeigt werden soll, statt immer
 * erst über die Spieltage-Übersicht zu gehen:
 *   1. der zuletzt angesehene Spieltag dieser Liga (liga_options
 *      "LastSpieltagNr", siehe rememberLigaLastSpieltag()), sofern er noch
 *      existiert
 *   2. sonst der erste Spieltag mit fehlenden Ergebnissen (aufsteigend
 *      nach Nummer)
 *   3. sonst (alles gespielt, oder noch keine Partien angelegt) der
 *      letzte Spieltag
 * Gibt null zurück, wenn die Liga noch gar keine Spieltage hat.
 */
function resolveLigaEntrySpieltagNr(PDO $db, int $lid) : ?int
{
    $s = $db->prepare(
        'SELECT s.nummer,
                COUNT(p.id) AS partie_count,
                SUM(CASE WHEN p.h_tore IS NOT NULL THEN 1 ELSE 0 END) AS gespielt
           FROM ' . tbl('liga_spieltage') . ' s
           LEFT JOIN ' . tbl('liga_partien') . ' p ON p.spieltag_id = s.id
          WHERE s.liga_id = ?
          GROUP BY s.id, s.nummer
          ORDER BY s.nummer'
    );
    $s->execute([$lid]);
    $rows = $s->fetchAll();
    if (!$rows) {
        return null;
    }
    $nummern = array_map(static fn($r) => (int)$r['nummer'], $rows);

    // 1) zuletzt angesehener Spieltag, sofern noch vorhanden
    $sLast = $db->prepare(
        'SELECT option_value FROM ' . tbl('liga_options') . '
          WHERE liga_id = ? AND option_key = "LastSpieltagNr"'
    );
    $sLast->execute([$lid]);
    $last = $sLast->fetchColumn();
    if ($last !== false && in_array((int)$last, $nummern, true)) {
        return (int)$last;
    }

    // 2) erster Spieltag mit fehlenden Ergebnissen
    foreach ($rows as $r) {
        $tc = (int)$r['partie_count'];
        $g  = (int)$r['gespielt'];
        if ($tc > 0 && $g < $tc) {
            return (int)$r['nummer'];
        }
    }

    // 3) alles gespielt (oder keine Partien vorhanden) → letzter Spieltag
    return end($nummern);
}

/**
 * Merkt sich den zuletzt in der Ergebniseingabe angesehenen Spieltag einer
 * Liga (liga_options "LastSpieltagNr"), damit resolveLigaEntrySpieltagNr()
 * beim nächsten Öffnen der Liga wieder dorthin zurückführt. Wird bei jedem
 * erfolgreichen Aufruf von "?action=spieltag" aufgerufen.
 */
function rememberLigaLastSpieltag(PDO $db, int $lid, int $nr) : void
{
    try {
        $stmt = $db->prepare(
            'INSERT INTO ' . tbl('liga_options') . ' (liga_id, option_key, option_value)
             VALUES (?, "LastSpieltagNr", ?) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
        );
        $stmt->execute([$lid, (string)$nr]);
    } catch (Throwable) {
        // Nicht kritisch - im schlimmsten Fall greift beim nächsten Öffnen
        // einfach die "erster Spieltag mit fehlenden Ergebnissen"-Regel.
    }
}

