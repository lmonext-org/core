<?php
/**
 * Project: LMOnext
 * Filename: addon-run.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * SICHERHEITSZWECK: Zentraler, einziger Einstiegspunkt für alle
 * "Standalone"-Addon-Skripte (z.B. das Mini-Tabellen-Addon, zum Einbetten
 * via iframe auf fremden Websites). Ersetzt den bisherigen direkten
 * URL-Aufruf einzelner Dateien unter addon/{name}/ - diese sind per
 * addon/.htaccess jetzt vollständig vor direkter PHP-Ausführung via
 * Web-Request geschützt.
 *
 * Hintergrund: Der Addon-Manager erlaubt das Hochladen von Addon-ZIPs
 * durch Administratoren. Ohne diesen zentralen Kontrollpunkt könnte eine
 * (versehentlich oder böswillig) hochgeladene PHP-Datei mit beliebigem
 * Dateinamen direkt per URL erreichbar und ausführbar sein, sobald sie
 * irgendwo unter addon/ liegt. Jede Anfrage muss jetzt stattdessen über
 * DIESEN Controller laufen, der den Zieldateinamen gegen eine explizite
 * Whitelist im jeweiligen addon.json (Feld "standalone_entrypoints")
 * prüft, BEVOR überhaupt ein Dateizugriff stattfindet - unbekannte
 * Dateien werden abgelehnt, egal wie sie heißen oder ob sie existieren.
 *
 * Aufruf-Muster (siehe jeweiliges Addon-CHANGELOG.md für den genauen
 * Parametersatz): addon-run.php?addon={name}&file={dateiname}&...
 */
declare(strict_types=1);

$addonName = $_GET['addon'] ?? '';
$fileName  = $_GET['file']  ?? '';

// ── Schritt 1: Strenge Formatprüfung, BEVOR irgendein Dateisystemzugriff
// stattfindet. Addon-Namen bestehen laut AddonManager-Konvention nur aus
// Kleinbuchstaben/Ziffern/Bindestrich/Unterstrich (siehe installFromZip()),
// Dateinamen nur aus den üblichen Zeichen + zwingend ".php"-Endung. ───────
if (!preg_match('/^[a-z0-9_-]+$/', $addonName) || !preg_match('/^[A-Za-z0-9_-]+\.php$/', $fileName)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Invalid request.');
}

$addonDir     = __DIR__ . '/addon/' . $addonName;
$manifestPath = $addonDir . '/addon.json';

if (!is_file($manifestPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Addon not found.');
}

$manifest = json_decode((string)file_get_contents($manifestPath), true);

// ── Schritt 2: Datei muss vom Addon SELBST explizit als erlaubter
// Standalone-Einstiegspunkt deklariert sein - alles andere wird abgelehnt,
// auch wenn die Datei physisch existiert und harmlos aussieht. ───────────
$allowed = is_array($manifest) ? ($manifest['standalone_entrypoints'] ?? []) : [];
if (!is_array($allowed) || !in_array($fileName, $allowed, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('This file is not a permitted standalone entry point of this add-on.');
}

$targetFile = $addonDir . '/' . $fileName;

// ── Schritt 3: realpath()-Absicherung gegen Pfad-Traversal. Selbst falls
// die Regex oben durch einen Encoding-Trick umgangen würde, muss der
// tatsächlich aufgelöste Pfad zwingend innerhalb des erwarteten
// Addon-Verzeichnisses liegen. ────────────────────────────────────────────
$realTarget = realpath($targetFile);
$realBase   = realpath($addonDir);
if ($realTarget === false || $realBase === false
    || !str_starts_with($realTarget, $realBase . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('File not found.');
}

// ── Schritt 4: Bootstrap (Config, DB, AddonManager inkl. bootFrontend()) -
// exakt dasselbe, was jedes Standalone-Addon-Skript ohnehin selbst beim
// direkten Aufruf lädt (require_once .../frontend/bootstrap.php). ────────
require_once __DIR__ . '/frontend/bootstrap.php';

// ── Schritt 5: Addon muss aktiviert sein. ─────────────────────────────────
if (!function_exists('addonManager') || !addonManager()->isEnabled($addonName)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Add-on not active.');
}

// Signalisiert dem Ziel-Skript, dass es als eigenständiger Einstiegspunkt
// aufgerufen wurde (analog zur früheren SCRIPT_NAME-Prüfung, die durch die
// Umleitung über diesen Controller nicht mehr funktionieren würde - siehe
// jeweiliges Addon-CHANGELOG.md).
define('LMO_ADDON_STANDALONE_CALL', true);

// Absoluter Web-Pfad (ab Domain-Wurzel) zum Ordner dieses Addons - ersetzt
// die früheren SCRIPT_NAME-basierten Tricks der einzelnen Addon-Skripte
// zur Berechnung relativer Asset-/Template-Pfade. Da JEDER Aufruf jetzt
// zwingend über diesen Controller läuft (der direkte Weg ist per
// addon/.htaccess gesperrt), ist dieser Pfad immer korrekt berechenbar -
// anders als bei einer reinen SCRIPT_NAME-Prüfung, die sich je nach
// tatsächlich angefordertem Skript änderte.
$lmoSelfDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/addon-run.php')), '/');
define('LMO_ADDON_WEB_BASE', $lmoSelfDir . '/addon/' . $addonName . '/');

require $realTarget;
