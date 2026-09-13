<?php
/**
 * Project: LMOnext
 * Filename: i18n.php
 * Fileversion: 1.2.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Diese Datei ist bewusst unabhängig von bootstrap.php/config.php gehalten,
 * damit sie sowohl von admin.php (via bootstrap.php) als auch direkt von
 * install.php eingebunden werden kann, bevor eine DB-Verbindung existiert.
 * Voraussetzung: session_start() wurde bereits aufgerufen.
 *
 * ── Domain-Konzept ────────────────────────────────────────────────────────────
 * Admin-Oberfläche und (künftiger) Besucherbereich haben getrennte Sprachdateien
 * und getrennte Spracheinstellungen, damit z.B. ein Admin die Verwaltung auf
 * Englisch nutzen kann, während die öffentliche Seite für Besucher unabhängig
 * davon auf Deutsch (oder je nach Besucher-Wahl) angezeigt wird:
 *
 *   lang/admin/de.php, lang/admin/en.php       → Adminbereich (admin.php, install.php)
 *   lang/frontend/de.php, lang/frontend/en.php → Besucherbereich (künftig)
 *
 * Beide Bereiche nutzen dieselbe Engine (diese Datei), aber jeweils eigene
 * Übersetzungsdateien, eigenen Session-Key und eigenen Laufzeit-Cache.
 * Für den Adminbereich: t($key, $vars).  Für den Besucherbereich (künftig): tf($key, $vars).
 */
declare(strict_types = 1);

// ── Verfügbare Sprachen (gemeinsam für alle Bereiche) ────────────────────────
// Weitere Sprachen: hier eintragen + passende lang/admin/<code>.php (und später
// lang/frontend/<code>.php) anlegen.
const AVAILABLE_LANGUAGES = [
    'de' => ['label' => 'Deutsch', 'flag' => '🇩🇪'],
    'en' => ['label' => 'English', 'flag' => '🇬🇧'],
];

const DEFAULT_LANGUAGE = 'de';

// Präfix für den domain-spezifischen Session-Key, z.B. "lmonext_lang_admin"
const LANG_SESSION_PREFIX = 'lmonext_lang_';

function langSessionKey(string $domain) : string
{
    return LANG_SESSION_PREFIX . $domain;
}

/**
 * Ermittelt die aktuell aktive Sprache für eine Domain ("admin" oder
 * "frontend") und persistiert einen expliziten Wechsel per ?lang=xx in der
 * Session. Muss aufgerufen werden BEVOR irgendein HTML-Output erzeugt wurde
 * (kann redirecten).
 *
 * @param string $domain              "admin" oder "frontend"
 * @param string $siteDefaultLanguage Optionale, konfigurierte Standardsprache
 *        (z.B. aus admin_settings). Greift nur, wenn noch keine
 *        Session-Sprache existiert (z.B. erster Besuch).
 */
function initLanguage(string $domain = 'admin', string $siteDefaultLanguage = '') : string
{
    $sessionKey = langSessionKey($domain);

    // 1) Expliziter Wechsel per GET-Parameter ?lang=xx
    if (isset($_GET['lang']) && array_key_exists($_GET['lang'], AVAILABLE_LANGUAGES)) {
        $_SESSION[$sessionKey] = $_GET['lang'];

        // Redirect ohne lang-Parameter, damit die URL sauber bleibt
        $qs = $_GET;
        unset($qs['lang']);
        $self   = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
        $target = $self . (empty($qs) ? '' : ('?' . http_build_query($qs)));
        header('Location: ' . $target);
        exit;
    }

    // 2) Bereits in der Session gemerkte Sprache (je Domain getrennt)
    if (isset($_SESSION[$sessionKey]) && array_key_exists($_SESSION[$sessionKey], AVAILABLE_LANGUAGES)) {
        return $_SESSION[$sessionKey];
    }

    // 3) Konfigurierte Standardsprache (z.B. Admin-Einstellungen)
    if ($siteDefaultLanguage !== '' && array_key_exists($siteDefaultLanguage, AVAILABLE_LANGUAGES)) {
        $_SESSION[$sessionKey] = $siteDefaultLanguage;
        return $siteDefaultLanguage;
    }

    // 4) Browsersprache (Accept-Language Header) als Fallback
    $browserLang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2));
    if (array_key_exists($browserLang, AVAILABLE_LANGUAGES)) {
        $_SESSION[$sessionKey] = $browserLang;
        return $browserLang;
    }

    // 5) Default
    $_SESSION[$sessionKey] = DEFAULT_LANGUAGE;
    return DEFAULT_LANGUAGE;
}

/**
 * @param string $domain "admin" (Standard) oder "frontend"
 */
function getCurrentLanguage(string $domain = 'admin', string $siteDefaultLanguage = '') : string
{
    static $langs = [];
    if (!isset($langs[$domain])) {
        $langs[$domain] = initLanguage($domain, $siteDefaultLanguage);
    }
    return $langs[$domain];
}

function loadTranslations(string $domain, string $lang) : array
{
    static $cache = [];
    $cacheKey = $domain . '/' . $lang;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    $file = __DIR__ . '/' . $domain . '/' . $lang . '.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/' . $domain . '/' . DEFAULT_LANGUAGE . '.php';
    }
    $data = is_file($file) ? require $file : [];
    $core = is_array($data) ? $data : [];

    // ── Addon-Übersetzungen einmischen ───────────────────────────────────────
    // AddonManager::loadLanguages() registriert die lang/{de,en}.php Dateien
    // aktivierter Addons über registerAddonTranslations() (siehe unten), da
    // Addons ihre Sprachdateien nicht im Core-Verzeichnis lang/{domain}/
    // ablegen. Core-Strings haben bei Namenskollisionen IMMER Vorrang
    // (ein Addon kann nur fehlende Keys ergänzen, nichts überschreiben).
    $addonEntries = $GLOBALS['__lmonextAddonTranslations'][$domain][$lang] ?? [];

    return $cache[$cacheKey] = (empty($addonEntries) ? $core : ($core + $addonEntries));
}

/**
 * Registriert die Übersetzungen eines Addons für eine Domain ("admin" oder
 * "frontend") und Sprache. Wird von AddonManager::loadLanguages() aufgerufen,
 * BEVOR der jeweilige t()/tf()-Aufruf im aktuellen Request stattfindet.
 * Muss daher vor dem ersten t()/tf()-Aufruf laufen (siehe bootAdmin()/
 * bootFrontend() in admin.php/frontend/bootstrap.php, die sehr früh im
 * Request greifen) – loadTranslations() cacht sonst ohne Addon-Strings.
 */
function registerAddonTranslations(string $domain, string $lang, array $entries) : void
{
    if (!isset($GLOBALS['__lmonextAddonTranslations'][$domain][$lang])) {
        $GLOBALS['__lmonextAddonTranslations'][$domain][$lang] = [];
    }
    foreach ($entries as $key => $value) {
        if (is_string($value)) {
            $GLOBALS['__lmonextAddonTranslations'][$domain][$lang][$key] = $value;
        }
    }
}

/**
 * Übersetzt einen Schlüssel in die aktuell aktive Sprache einer Domain.
 * Platzhalter im Format {name} werden über $vars ersetzt.
 * Fällt auf DEFAULT_LANGUAGE zurück, falls der Schlüssel fehlt,
 * und zuletzt auf den Schlüssel selbst.
 */
function transDomain(string $domain, string $key, array $vars = []) : string
{
    $strings = loadTranslations($domain, getCurrentLanguage($domain));
    $text    = $strings[$key] ?? (loadTranslations($domain, DEFAULT_LANGUAGE)[$key] ?? $key);
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', (string)$v, $text);
    }
    return $text;
}

/**
 * Übersetzungsfunktion für den Adminbereich (admin.php, install.php).
 * Unverändertes Verhalten/Signatur ggü. der Vorversion.
 */
function t(string $key, array $vars = []) : string
{
    return transDomain('admin', $key, $vars);
}

/**
 * Übersetzungsfunktion für den künftigen Besucherbereich ("frontend").
 * Nutzt lang/frontend/<sprache>.php – separat vom Adminbereich.
 */
function tf(string $key, array $vars = []) : string
{
    return transDomain('frontend', $key, $vars);
}

/**
 * Rendert das Sprachauswahl-Dropdown. Bewusst ohne Abhängigkeit von h(),
 * damit die Datei eigenständig funktioniert (z.B. in install.php).
 *
 * @param string $domain "admin" (Standard) oder "frontend"
 */
function renderLanguageSwitcher(string $domain = 'admin') : string
{
    $current = getCurrentLanguage($domain);
    $esc     = fn(string $v) : string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $label   = $domain === 'admin' ? t('lang_switch_label') : tf('lang_switch_label');

    $html = '<form method="get" class="lang-switch" onchange="this.submit()">';
    foreach ($_GET as $k => $v) {
        if ($k === 'lang' || !is_scalar($v)) {
            continue;
        }
        $html .= '<input type="hidden" name="' . $esc((string)$k) . '" value="' . $esc((string)$v) . '">';
    }
    $html .= '<select name="lang" aria-label="' . $esc($label) . '">';
    foreach (AVAILABLE_LANGUAGES as $code => $meta) {
        $sel  = $code === $current ? ' selected' : '';
        $html .= '<option value="' . $esc($code) . '"' . $sel . '>' . $esc($meta['flag']) . ' ' . $esc($meta['label']) . '</option>';
    }
    $html .= '</select></form>';
    return $html;
}
