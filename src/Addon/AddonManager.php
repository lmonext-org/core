<?php
/**
 * Project: LMOnext
 * Filename: src/Addon/AddonManager.php
 * Fileversion: 1.5.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── Addon-Manager & Hook-System ──────────────────────────────────────────────
 *
 * Zentrale Komponente für das Addon- und Event-System von LMOnext.
 *
 * Aufgaben:
 *   - Discovery:  addon/{name}/addon.json scannen und Manifests parsen
 *   - Lifecycle:  enable / disable (Datenbank-gestützt über addon_registry)
 *   - Boot:       Admin- und Frontend-Handler/Views registrieren
 *   - Navigation: Nav-Items aus Addon-Manifests zusammenführen
 *   - Sprachen:   Addon-eigene lang/ Dateien mit Namespace laden
 *   - Templates:  Addon-Template-Verzeichnisse registrieren
 *   - Hooks:      Event-System für Addon-Interaktion mit dem Core
 *
 * Das System ist bewusst framework-frei (kein Composer, kein Laravel).
 * Addons werden per require_once geladen — wie bisher, nur dynamisch.
 */

declare(strict_types=1);

namespace LMOnext\Addon {

use PDO;
use RuntimeException;

/**
 * AddonManager: entdeckt, verwaltet, aktiviert und lädt Addons.
 */
class AddonManager
{
    /** @var string Pfad zum addon/ Verzeichnis (mit trailing slash) */
    private string $addonDir;

    /** @var PDO|null Datenbankverbindung für addon_registry */
    private ?PDO $db;

    /** @var string Tabellen-Präfix (z.B. 'lmo_') */
    private string $tablePrefix;

    /** @var array<string, array<string, mixed>> Alle entdeckten Addons: ['manifest' => [...], 'path' => '...', 'enabled' => bool] */
    private array $addons = [];

    /** @var array<string, array<int, array{handler: callable, priority: int}>> Hook-Registry: ['event' => [['handler'=>callable,'priority'=>int], ...]] */
    private array $hooks = [];

    /** @var bool Ob discover() bereits gelaufen ist */
    private bool $discovered = false;

    /** @var array<string, array<string, string>> Sprach-Registry nach Sprache ('de', 'en') und Keys */
    private array $langRegistry = [];

    /** @var array<int, string> Registrierte Template-Suchpfade */
    private array $templatePaths = [];

    /**
     * Konstruktor.
     *
     * @param string   $addonDir    Absoluter Pfad zum addon/ Verzeichnis
     * @param PDO|null $db          Datenbankverbindung (null = kein DB-Zugriff)
     * @param string   $tablePrefix Tabellen-Präfix für addon_registry
     */
    public function __construct(string $addonDir, ?PDO $db = null, string $tablePrefix = '')
    {
        $this->addonDir    = rtrim($addonDir, '/\\') . '/';
        $this->db          = $db;
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * Baut den vollständigen, mit Backticks gequoteten Tabellennamen für die
     * addon_registry. tablePrefix ist raw (z.B. "lmo_"), NICHT vorgequotet.
     */
    private function registryTable(): string
    {
        return '`' . $this->tablePrefix . 'addon_registry`';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Discovery & Lifecycle
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Scannt addon/{name}/addon.json und parst alle Manifests.
     * Idempotent (wird nur einmal ausgeführt, außer manuell erzwungen).
     */
    public function discover(bool $force = false): void
    {
        if ($this->discovered && !$force) {
            return;
        }

        $enabledNames = $this->loadEnabled();

        $this->addons = [];
        if (!is_dir($this->addonDir)) {
            $this->discovered = true;
            return;
        }

        $dirs = scandir($this->addonDir);
        if ($dirs === false) {
            $this->discovered = true;
            return;
        }

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $manifestPath = $this->addonDir . $dir . '/addon.json';
            if (!is_file($manifestPath)) {
                continue;
            }

            $raw = @file_get_contents($manifestPath);
            if ($raw === false) {
                continue;
            }

            $manifest = json_decode($raw, true);
            if (!is_array($manifest) || !isset($manifest['name']) || $manifest['name'] === '') {
                continue;
            }

            $name = (string)$manifest['name'];
            $this->addons[$name] = [
                'manifest' => $manifest,
                'path'     => $this->addonDir . $dir . '/',
                'enabled'  => in_array($name, $enabledNames, true),
            ];
        }

        $this->discovered = true;
    }

    /**
     * Liest die aktivierten Addons aus der DB-Tabelle addon_registry.
     *
     * @return array<string> Liste der aktivierten Addon-Namen
     */
    public function loadEnabled(): array
    {
        // addon-manager ist IMMER aktiviert (Core-Tool, kann nicht
        // deaktiviert werden) - unabhängig von der DB, damit der Addon-
        // Manager selbst niemals unerreichbar werden kann (z.B. bei
        // fehlender DB-Verbindung).
        if ($this->db === null) {
            return ['addon-manager'];
        }

        try {
            $this->ensureRegistryTable();
            $tableName = $this->registryTable();
            $stmt = $this->db->query("SELECT name FROM {$tableName} WHERE enabled = 1");
            if ($stmt === false) {
                return ['addon-manager'];
            }

            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $enabled = is_array($rows) ? array_map('strval', $rows) : [];

            // WICHTIG (Bugfix, Beitrag: Nutzerreport - "Addon aktivieren
            // deaktiviert alle anderen"): es gab früher hier einen
            // "leere Tabelle = frische Installation, alle Core-Addons
            // aktivieren"-Fallback (Konstante CORE_ADDONS, u.a. mit
            // veralteten/nicht mehr existierenden Addon-Namen wie
            // "translator"). Der Fallback konnte NICHT zwischen einer
            // echten Erstinstallation (Tabelle hat GAR KEINE Zeilen) und
            // dem Zustand "Tabelle hat Zeilen, aber zufällig gerade
            // KEINE mit enabled=1" unterscheiden - beide Fälle lösten
            // denselben Fallback aus. Solange z.B. tipp/relegation/
            // tabellenrechner NIE eine echte enabled=1-Zeile hatten,
            // erschienen sie NUR wegen dieses Fallbacks als aktiv. Sobald
            // ein ANDERES Addon zum ersten Mal eine echte enabled=1-Zeile
            // bekam (z.B. durch Aktivieren von ewige-tabelle), war
            // $enabled nicht mehr leer, der Fallback griff nicht mehr,
            // und alle Addons ohne echte Zeile erschienen plötzlich als
            // deaktiviert - und umgekehrt beim erneuten Deaktivieren.
            // Jetzt: JEDES Addon (außer addon-manager) startet konsistent
            // als INAKTIV, bis es explizit per enable() aktiviert wurde -
            // entspricht dem bereits für alle neueren Addons korrekt
            // beobachteten Verhalten (liga-klassen-rekorde, mini-tabelle,
            // spieltag-viewer starten ebenfalls inaktiv).
            if (!in_array('addon-manager', $enabled, true)) {
                $enabled[] = 'addon-manager';
            }

            return $enabled;
        } catch (\Throwable) {
            return ['addon-manager'];
        }
    }

    /**
     * Stellt sicher, dass die addon_registry Tabelle existiert.
     */
    public function ensureRegistryTable(): void
    {
        if ($this->db === null) {
            return;
        }

        $tableName = $this->registryTable();
        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            name         VARCHAR(64) NOT NULL UNIQUE,
            version      VARCHAR(32) NOT NULL DEFAULT '',
            enabled      TINYINT(1) NOT NULL DEFAULT 0,
            installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            settings     TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);

        // ── Nachträgliche Migration: UNIQUE-Constraint auf "name" sicherstellen
        // (Beitrag: Bugfix-Absicherung) ─────────────────────────────────────────
        // "CREATE TABLE IF NOT EXISTS" ändert an einer BEREITS bestehenden
        // Tabelle nichts mehr - falls diese Tabelle auf einer Installation vor
        // dem disable()-Bugfix (siehe dortiger Changelog-Eintrag) angelegt
        // wurde, könnte das UNIQUE-Constraint fehlen. Ohne es wäre "INSERT ...
        // ON DUPLICATE KEY UPDATE" (in enable()/disable() genutzt) wirkungslos
        // und würde bei jedem Aufruf eine ZUSÄTZLICHE Zeile statt eines Updates
        // erzeugen. Wird bei jedem Request geprüft (günstige SHOW-Abfrage),
        // aber nur bei Bedarf tatsächlich geändert.
        try {
            $idxCheck = $this->db->query(
                "SHOW INDEX FROM {$tableName} WHERE Column_name = 'name' AND Non_unique = 0"
            );
            $hasUnique = $idxCheck !== false && $idxCheck->fetch() !== false;
            if (!$hasUnique) {
                // Eventuelle Duplikate zuerst bereinigen (nur die neueste Zeile
                // pro Addon-Name behalten) - ein UNIQUE-Constraint lässt sich
                // sonst nicht anlegen, wenn bereits doppelte Werte vorhanden
                // sind (durch den früheren disable()-Bug bzw. durch enable()-
                // Aufrufe ohne wirksames ON DUPLICATE KEY UPDATE theoretisch
                // möglich).
                $this->db->exec(
                    "DELETE t1 FROM {$tableName} t1
                     INNER JOIN {$tableName} t2
                     ON t1.name = t2.name AND t1.id < t2.id"
                );
                $this->db->exec("ALTER TABLE {$tableName} ADD UNIQUE KEY uniq_name (name)");
            }
        } catch (\Throwable $e) {
            // Migration fehlgeschlagen (z.B. fehlende ALTER-Berechtigung) -
            // kein harter Abbruch, aber geloggt, damit es nicht stillschweigend
            // untergeht.
            error_log('[AddonManager] Registry-UNIQUE-Migration fehlgeschlagen: ' . $e->getMessage());
        }

        // addon_settings Tabelle für Core-Einstellungen (z.B. GitHub Token)
        $settingsTable = '`' . $this->tablePrefix . 'addon_settings`';
        $this->db->exec("CREATE TABLE IF NOT EXISTS {$settingsTable} (
            id    INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(64) NOT NULL UNIQUE,
            value TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Liest ein Core-Setting aus der addon_settings Tabelle.
     *
     * @param string $key    Setting-Key (z.B. 'github_token')
     * @param string $default Default-Wert wenn nicht gesetzt
     * @return string
     */
    public function getSetting(string $key, string $default = ''): string
    {
        if ($this->db === null) {
            return $default;
        }
        $this->ensureRegistryTable();
        $settingsTable = '`' . $this->tablePrefix . 'addon_settings`';
        $stmt = $this->db->prepare("SELECT value FROM {$settingsTable} WHERE `key` = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val === false || $val === null) ? $default : (string)$val;
    }

    /**
     * Speichert ein Core-Setting in der addon_settings Tabelle.
     *
     * @param string $key   Setting-Key
     * @param string $value Wert (leer = löschen)
     */
    public function setSetting(string $key, string $value): void
    {
        if ($this->db === null) {
            return;
        }
        $this->ensureRegistryTable();
        $settingsTable = '`' . $this->tablePrefix . 'addon_settings`';
        if ($value === '') {
            $stmt = $this->db->prepare("DELETE FROM {$settingsTable} WHERE `key` = ?");
            $stmt->execute([$key]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO {$settingsTable} (`key`, value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()"
            );
            $stmt->execute([$key, $value]);
        }
    }

    /**
     * Convenience: GitHub Token lesen.
     *
     * @return string Token oder leerstring
     */
    public function getGithubToken(): string
    {
        return $this->getSetting('github_token', '');
    }

    /**
     * Convenience: GitHub Token speichern (oder löschen bei leerstring).
     *
     * @param string $token
     */
    public function setGithubToken(string $token): void
    {
        $this->setSetting('github_token', $token);
    }

    /**
     * Aktiviert ein Addon (erstellt DB-Eintrag, registriert Hooks, lädt Sprachen & Templates).
     *
     * @param string $name Addon-Name
     * @throws RuntimeException Wenn das Addon nicht existiert
     */
    public function enable(string $name): void
    {
        $this->discover();
        if (!isset($this->addons[$name])) {
            throw new RuntimeException("Addon '{$name}' not found.");
        }

        if ($this->db !== null) {
            $this->ensureRegistryTable();
            $tableName = $this->registryTable();
            $version   = (string)($this->addons[$name]['manifest']['version'] ?? '1.0.0');

            $stmt = $this->db->prepare(
                "INSERT INTO {$tableName} (name, version, enabled)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE enabled = 1, version = VALUES(version), updated_at = NOW()"
            );
            $stmt->execute([$name, $version]);
        }

        $this->addons[$name]['enabled'] = true;

        $this->registerAddonHooks($this->addons[$name]);
        $this->loadLanguages($name);
        $this->loadTemplates($name);
    }

    /**
     * Deaktiviert ein Addon (setzt enabled=0 in der DB, behält Dateien).
     *
     * @param string $name Addon-Name
     */
    public function disable(string $name): void
    {
        $this->discover();
        if (!isset($this->addons[$name])) {
            return;
        }

        if ($this->db !== null) {
            $this->ensureRegistryTable();
            $tableName = $this->registryTable();
            $version   = (string)($this->addons[$name]['manifest']['version'] ?? '1.0.0');

            // WICHTIG (Bugfix): reines UPDATE betrifft 0 Zeilen, wenn für
            // dieses Addon noch NIE zuvor ein Registry-Eintrag angelegt
            // wurde (Standardzustand ohne Eintrag ist "aktiv", siehe
            // isEnabled()/discover()) - die Deaktivierung wäre dadurch
            // scheinbar erfolgreich (kein Fehler), hätte aber tatsächlich
            // gar keine Wirkung: der In-Memory-Status im aktuellen Request
            // zeigt "deaktiviert", der nächste Request liest aus der
            // (unveränderten) DB wieder "aktiv". Analog zu enable() daher
            // ebenfalls als INSERT ... ON DUPLICATE KEY UPDATE (Upsert)
            // statt eines reinen UPDATE.
            $stmt = $this->db->prepare(
                "INSERT INTO {$tableName} (name, version, enabled)
                 VALUES (?, ?, 0)
                 ON DUPLICATE KEY UPDATE enabled = 0, updated_at = NOW()"
            );
            $stmt->execute([$name, $version]);
        }

        $this->addons[$name]['enabled'] = false;
    }

    /**
     * Prüft, ob ein Addon aktiviert ist.
     *
     * @param string $name
     * @return bool
     */
    public function isEnabled(string $name): bool
    {
        $this->discover();
        return $this->addons[$name]['enabled'] ?? false;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Boot: Handler & Views
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Lädt alle Admin-Handler (require_once) aller aktivierten Addons.
     */
    public function bootAdmin(): void
    {
        // Handler-Dateien werden per require_once in DIESER Methode geladen.
        // require_once läuft im Scope der Methode — globale Variablen wie
        // $action müssen daher explizit hereingeholt werden, damit die
        // Handler (die im globalen Scope geschrieben sind) sie sehen.
        global $action, $addonManager;

        $this->discover();
        foreach ($this->addons as $name => $addon) {
            if (!$addon['enabled']) {
                continue;
            }

            $type = (string)($addon['manifest']['type'] ?? 'both');

            // ── Sprachen und Templates für ALLE Addons laden ───────────────
            // Auch standalone-Addons benötigen ihre Sprachdateien, da ihre
            // tf()-Aufrufe sonst leere Strings zurückgeben.
            $this->loadLanguages($name);
            $this->loadTemplates($name);

            if (!in_array($type, ['admin', 'both'], true)) {
                continue;
            }

            // WICHTIG: Sprachen/Hooks/Templates MÜSSEN vor den Handlern
            // registriert werden. Handler-Dateien enthalten Top-Level-Code,
            // der bei POST-Requests oft sofort t()/tf() aufruft und dann via
            // redirect() das Skript per exit() beendet — würde loadLanguages()
            // erst NACH den Handlern laufen, wären genau bei diesem ersten
            // Request (z.B. Addon aktivieren/deaktivieren) die eigenen
            // Übersetzungen des Addons noch nicht registriert und t() liefert
            // den rohen Key zurück statt des übersetzten Textes.
            $this->registerAddonHooks($addon);
            $this->loadLanguages($name);
            $this->loadTemplates($name);

            $handlers = $addon['manifest']['admin_handlers'] ?? [];
            if (is_array($handlers)) {
                foreach ($handlers as $file) {
                    $path = $addon['path'] . $file;
                    if (is_file($path)) {
                        require_once $path;
                    }
                }
            }
        }
    }

    /**
     * Lädt alle Frontend-Handler (require_once) aller aktivierten Addons.
     */
    public function bootFrontend(): void
    {
        $this->discover();
        foreach ($this->addons as $name => $addon) {
            if (!$addon['enabled']) {
                continue;
            }

            $type = (string)($addon['manifest']['type'] ?? 'both');

            // ── Sprachen und Templates für ALLE Addons laden ───────────────
            // Auch standalone-Addons benötigen ihre Sprachdateien, da ihre
            // tf()-Aufrufe sonst leere Strings zurückgeben.
            $this->loadLanguages($name);
            $this->loadTemplates($name);

            // standalone-Addons haben einen eigenen Einstiegspunkt und werden
            // NICHT auf jeder Seite geladen (nur 'frontend' und 'both').
            // Hooks und Handler werden nur für frontend/both geladen.
            if (!in_array($type, ['frontend', 'both'], true)) {
                continue;
            }

            // Reihenfolge wie in bootAdmin(): Sprachen/Hooks/Templates
            // VOR den Handlern registrieren (siehe Kommentar dort).
            $this->registerAddonHooks($addon);

            $handlers = $addon['manifest']['frontend_handlers'] ?? [];
            if (is_array($handlers)) {
                foreach ($handlers as $file) {
                    $path = $addon['path'] . $file;
                    if (is_file($path)) {
                        require_once $path;
                    }
                }
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Navigation & Routing / Views
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Liefert alle Sidebar-Navigations-Einträge aller aktivierten Addons.
     *
     * @return array<string, array{action: string, icon: string, label_key: string, position: int}>
     */
    public function getNavItems(): array
    {
        $this->discover();
        $items = [];

        foreach ($this->addons as $addon) {
            if (!$addon['enabled']) {
                continue;
            }

            $nav = $addon['manifest']['admin_nav'] ?? [];
            if (!is_array($nav)) {
                continue;
            }

            foreach ($nav as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $action = (string)($entry['action'] ?? '');
                if ($action === '') {
                    continue;
                }

                $items[$action] = [
                    'action'    => $action,
                    'icon'      => (string)($entry['icon'] ?? '🧩'),
                    'label_key' => (string)($entry['label_key'] ?? $action),
                    'position'  => (int)($entry['position'] ?? 99),
                ];
            }
        }

        uasort($items, static fn(array $a, array $b) => $a['position'] <=> $b['position']);

        return $items;
    }

    /**
     * Gibt den View-Dateipfad für eine Admin-Action zurück, oder null.
     * Unterstüzt sowohl [{action, file}] als auch {"action": "file"}.
     *
     * @param string $action Action-Name
     * @return string|null Absoluter Pfad zur View-Datei
     */
    public function getAdminView(string $action): ?string
    {
        $this->discover();

        foreach ($this->addons as $addon) {
            if (!$addon['enabled']) {
                continue;
            }

            $views = $addon['manifest']['admin_views'] ?? [];

            // List Format: [ {"action": "xxx", "file": "yyy"} ]
            if (is_array($views) && array_is_list($views)) {
                foreach ($views as $item) {
                    if (is_array($item) && ($item['action'] ?? '') === $action) {
                        $path = $addon['path'] . ($item['file'] ?? '');
                        return is_file($path) ? $path : null;
                    }
                }
            }
            // Map Format: { "action": "file.php" }
            elseif (is_array($views) && isset($views[$action])) {
                $path = $addon['path'] . $views[$action];
                return is_file($path) ? $path : null;
            }
        }

        return null;
    }

    /**
     * Gibt den View-Dateipfad für eine Frontend-Route zurück, oder null.
     * Unterstützt sowohl [{route, file}] als auch {"route": "file"}.
     *
     * @param string $route Route-Name
     * @return string|null Absoluter Pfad zur View-Datei
     */
    public function getFrontendView(string $route): ?string
    {
        $this->discover();

        foreach ($this->addons as $addon) {
            if (!$addon['enabled']) {
                continue;
            }

            $views = $addon['manifest']['frontend_views'] ?? [];

            // List Format: [ {"route": "xxx", "file": "yyy"} ]
            if (is_array($views) && array_is_list($views)) {
                foreach ($views as $item) {
                    if (is_array($item) && ($item['route'] ?? '') === $route) {
                        $path = $addon['path'] . ($item['file'] ?? '');
                        return is_file($path) ? $path : null;
                    }
                }
            }
            // Map Format: { "route": "file.php" }
            elseif (is_array($views) && isset($views[$route])) {
                $path = $addon['path'] . $views[$route];
                return is_file($path) ? $path : null;
            }
        }

        return null;
    }

    /**
     * Gibt alle Admin-Actions der aktivierten Addons zurück.
     *
     * @return array<string>
     */
    public function getAdminActions(): array
    {
        $this->discover();
        $actions = [];

        foreach ($this->addons as $addon) {
            if (!$addon['enabled']) {
                continue;
            }

            $manifestActions = $addon['manifest']['admin_actions'] ?? [];
            if (is_array($manifestActions)) {
                foreach ($manifestActions as $act) {
                    $actions[] = (string)$act;
                }
            }
        }

        return array_unique($actions);
    }

    /**
     * Gibt alle Frontend-Routen der aktivierten Addons zurück.
     *
     * @return array<string>
     */
    public function getFrontendRoutes(): array
    {
        $this->discover();
        $routes = [];

        foreach ($this->addons as $addon) {
            if (!$addon['enabled']) {
                continue;
            }

            $manifestRoutes = $addon['manifest']['frontend_routes'] ?? [];
            if (is_array($manifestRoutes)) {
                foreach ($manifestRoutes as $rt) {
                    $routes[] = (string)$rt;
                }
            }
        }

        return array_unique($routes);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Sprachen & Templates
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Lädt die Sprachdateien eines Addons (lang/de.php, lang/en.php)
     * und merged sie mit Namespace-Präfix in die translation registry.
     *
     * @param string $addonName
     */
    public function loadLanguages(string $addonName): void
    {
        $this->discover();
        if (!isset($this->addons[$addonName])) {
            return;
        }

        $addon   = $this->addons[$addonName];
        $relDir  = (string)($addon['manifest']['lang_dir'] ?? 'lang/');
        $langDir = $addon['path'] . rtrim($relDir, '/\\') . '/';

        foreach (['de', 'en'] as $lang) {
            $file = $langDir . $lang . '.php';
            if (!is_file($file)) {
                continue;
            }

            $entries = @include $file;
            if (!is_array($entries)) {
                continue;
            }

            if (!isset($this->langRegistry[$lang])) {
                $this->langRegistry[$lang] = [];
            }

            foreach ($entries as $key => $value) {
                if (!is_string($value)) {
                    continue;
                }

                // Addon-spezifischer Namespace
                $namespacedKey = $addonName . '.' . $key;
                $this->langRegistry[$lang][$namespacedKey] = $value;

                // Fallback / Direkt-Zugriff wenn Key noch nicht belegt
                if (!isset($this->langRegistry[$lang][$key])) {
                    $this->langRegistry[$lang][$key] = $value;
                }
            }

            // ── Bridge zum Core-i18n-System (lang/i18n.php) ───────────────────
            // Ein einzelnes lang/{de,en}.php pro Addon deckt i.d.R. sowohl
            // Admin- (t()) als auch Frontend-Strings (tf()) ab, da Addons
            // keine getrennten admin/frontend-Unterordner vorschreiben.
            // Wir registrieren die Einträge daher in BEIDEN Domains — Core-
            // Strings haben in registerAddonTranslations()/loadTranslations()
            // ohnehin Vorrang bei Namenskollisionen, ein Risiko besteht nicht.
            if (function_exists('registerAddonTranslations')) {
                registerAddonTranslations('admin', $lang, $entries);
                registerAddonTranslations('frontend', $lang, $entries);
            }
        }
    }

    /**
     * Liefert die globale Sprach-Registry für eine Sprache.
     *
     * @param string $lang Sprache ('de' oder 'en')
     * @return array<string, string>
     */
    public function getLangRegistry(string $lang = 'de'): array
    {
        return $this->langRegistry[$lang] ?? [];
    }

    /**
     * Registriert das Template-Verzeichnis eines Addons.
     *
     * @param string $addonName
     */
    public function loadTemplates(string $addonName): void
    {
        $this->discover();
        if (!isset($this->addons[$addonName])) {
            return;
        }

        $addon  = $this->addons[$addonName];
        $relDir = (string)($addon['manifest']['templates_dir'] ?? 'templates/');
        $tplDir = $addon['path'] . rtrim($relDir, '/\\') . '/';

        if (is_dir($tplDir) && !in_array($tplDir, $this->templatePaths, true)) {
            $this->templatePaths[] = $tplDir;
        }
    }

    /**
     * Gibt alle registrierten Template-Verzeichnisse zurück.
     *
     * @return array<int, string>
     */
    public function getTemplatePaths(): array
    {
        return $this->templatePaths;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Hook / Event System
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Registriert einen Event-Handler mit einer Priorität.
     *
     * @param string   $event    Event-Name (z.B. 'liga.saved', 'template.head')
     * @param callable $handler  Callable-Handler
     * @param int      $priority Priorität (Standard: 10, kleiner = früher)
     */
    public function registerHook(string $event, callable $handler, int $priority = 10): void
    {
        if (!isset($this->hooks[$event])) {
            $this->hooks[$event] = [];
        }

        $this->hooks[$event][] = [
            'handler'  => $handler,
            'priority' => $priority,
        ];

        usort($this->hooks[$event], static fn(array $a, array $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Führt alle für ein Event registrierten Handler nacheinander aus.
     *
     * @param string $event Event-Name
     * @param array  $data  Daten, die von Handlern verändert werden können
     * @return array Modifizierte Daten
     */
    public function doHook(string $event, array $data = []): array
    {
        if (!isset($this->hooks[$event])) {
            return $data;
        }

        foreach ($this->hooks[$event] as $hook) {
            $handler = $hook['handler'];
            $result  = $handler($data);
            if (is_array($result)) {
                $data = $result;
            }
        }

        return $data;
    }

    /**
     * Registriert Hooks aus dem Addon-Manifest.
     *
     * @param array $addon
     */
    private function registerAddonHooks(array $addon): void
    {
        $hooks = $addon['manifest']['hooks'] ?? [];
        if (!is_array($hooks)) {
            return;
        }

        foreach ($hooks as $hook) {
            if (!is_array($hook)) {
                continue;
            }

            $event    = (string)($hook['event'] ?? '');
            $handler  = $hook['handler'] ?? '';
            $priority = (int)($hook['priority'] ?? 10);

            if ($event === '' || $handler === '') {
                continue;
            }

            if (is_callable($handler)) {
                $this->registerHook($event, $handler, $priority);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Getters & Helpers
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Gibt alle entdeckten Addons zurück (enabled + disabled).
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllAddons(): array
    {
        $this->discover();
        return $this->addons;
    }

    /**
     * Gibt das Manifest eines Addons zurück, oder null.
     *
     * @param string $name
     * @return array|null
     */
    public function getAddonInfo(string $name): ?array
    {
        $this->discover();
        return $this->addons[$name]['manifest'] ?? null;
    }

    /**
     * Liefert die Liste der Datenbank-Tabellen, die ein Addon besitzt (für Cleanup).
     *
     * @param string $name
     * @return array<string>
     */
    public function getDbTables(string $name): array
    {
        $manifest = $this->getAddonInfo($name);
        $tables   = $manifest['db_tables'] ?? [];
        return is_array($tables) ? array_map('strval', $tables) : [];
    }

    /**
     * Löscht die eigenen Datenbank-Tabellen eines Addons unwiderruflich
     * (Beitrag: Nutzerwunsch, analog zu phpBB's "purge extension data").
     * Nur die im Manifest EXPLIZIT deklarierten Tabellen (Feld "db_tables")
     * werden angefasst - bewusst KEINE addon_settings-Einträge, da diese
     * Tabelle global (schlüsselbasiert, ohne Addon-Zuordnung) ist und ein
     * automatisches Löschen dort versehentlich fremde Einstellungen treffen
     * könnte.
     *
     * SICHERHEIT: der Aufrufer (siehe handler_addons.php) MUSS selbst
     * sicherstellen, dass das Addon aktuell DEAKTIVIERT ist, bevor diese
     * Methode aufgerufen wird - diese Methode selbst prüft das nicht
     * erneut, um sie unabhängig von der DB-Registry testbar zu halten.
     *
     * @param string $name Addon-Name
     * @return array{success:bool,dropped:array<string>,error:string}
     */
    public function purgeData(string $name): array
    {
        if ($this->db === null) {
            return ['success' => false, 'dropped' => [], 'error' => 'no_db'];
        }
        $tables = $this->getDbTables($name);
        if (empty($tables)) {
            return ['success' => false, 'dropped' => [], 'error' => 'no_tables_declared'];
        }

        $dropped = [];
        foreach ($tables as $rawTable) {
            // Tabellennamen strikt auf ein sicheres Zeichenmuster begrenzen,
            // BEVOR er in ein rohes SQL-Statement eingesetzt wird (DROP
            // TABLE unterstützt keine Prepared-Statement-Platzhalter für
            // Bezeichner) - verhindert SQL-Injection über ein manipuliertes
            // Manifest. db_tables enthält BASIS-Namen OHNE DB-Präfix (analog
            // zu allen tbl()-Aufrufen im übrigen Code) - der tatsächlich
            // konfigurierte Präfix (pro Installation unterschiedlich, nicht
            // zwingend "lmo_") wird hier zur Laufzeit über $this->tablePrefix
            // angewendet, exakt wie beim ursprünglichen CREATE TABLE.
            if (!preg_match('/^[A-Za-z0-9_]+$/', $rawTable)) {
                continue;
            }
            $fullTable = $this->tablePrefix . $rawTable;
            try {
                $this->db->exec('DROP TABLE IF EXISTS `' . $fullTable . '`');
                $dropped[] = $fullTable;
            } catch (\Throwable) {
                // Einzelne Tabelle konnte nicht gelöscht werden (z.B. fehlende
                // Berechtigung) - restliche Tabellen trotzdem weiter versuchen.
            }
        }

        return ['success' => !empty($dropped), 'dropped' => $dropped, 'error' => empty($dropped) ? 'drop_failed' : ''];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  GitHub Version Check
    //  Prüft Addon-Homepage (github.com/owner/repo) auf neuere Releases.
    //  Ergebnisse werden in einer Cache-Datei gespeichert (TTL 1 Stunde),
    //  damit GitHub nicht bei jedem Seitenaufruf bemüht wird.
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Prüft alle Addons mit GitHub-Homepage auf verfügbare Updates.
     * Nutzt einen Datei-Cache (addon_updates.json) mit 1-stündiger TTL.
     *
     * @param bool $force  Cache ignorieren und neu prüfen.
     * @return array<string,array{latest:string,update_available:bool,error:string}>
     *         Map: addon-name => ['latest' => version, 'update_available' => bool, 'error' => '']
     */
    /**
     * Zentraler HTTP-GET Helper für GitHub-API-Calls und Datei-Downloads.
     * Probiert file_get_contents (allow_url_fopen), fällt auf curl zurück.
     * Hängt automatisch den gespeicherten GitHub-Token an (falls vorhanden).
     *
     * @param string $url    Ziel-URL
     * @param bool   $binary true = Accept: application/octet-stream (Datei-Download),
     *                        false = Accept: application/vnd.github+json (API-Call)
     * @return array{body: string|false, http_code: int, error: string, method: string}
     */
    private function httpGet(string $url, bool $binary = false): array
    {
        $ghToken = $this->getGithubToken();
        $headers = [
            'User-Agent: LMOnext-AddonManager/' . LMONEXT_VERSION,
        ];
        if ($binary) {
            $headers[] = 'Accept: */*';
        } else {
            $headers[] = 'Accept: application/vnd.github+json';
        }
        if ($ghToken !== '' && strpos($url, 'api.github.com') !== false) {
            $headers[] = 'Authorization: Bearer ' . $ghToken;
        }

        // Manuelle Redirect-Verfolgung (bis zu 5 Hops). Wichtig weil
        // CURLOPT_FOLLOWLOCATION bei open_basedir deaktiviert ist und
        // file_get_contents follow_location auf manchen Hosts nicht greift.
        $maxRedirects = 5;
        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            $resp = $this->httpGetSingle($url, $headers, $binary);
            if ($resp['body'] !== false) {
                return $resp; // Erfolg (200)
            }
            // 301/302/303/307/308 → Location-Header folgen
            $code = $resp['http_code'];
            if (in_array($code, [301, 302, 303, 307, 308], true) && !empty($resp['location'])) {
                $url = $resp['location'];
                // Nach Redirect keine API-Header mehr mitschicken
                if (strpos($url, 'api.github.com') === false) {
                    $headers = array_filter($headers, fn($h) => stripos($h, 'Authorization') === false && stripos($h, 'Accept: application/vnd') === false);
                    $headers[] = 'Accept: */*';
                }
                continue;
            }
            // Kein Redirect, kein Erfolg → abbrechen
            return $resp;
        }
        return ['body' => false, 'http_code' => 0, 'error' => 'too_many_redirects', 'method' => ''];
    }

    /**
     * Ein einzelner HTTP-Request OHNE Redirect-Verfolgung.
     * Gibt zusätzlich den Location-Header zurück für manuelle Verfolgung.
     */
    private function httpGetSingle(string $url, array $headers, bool $binary): array
    {
        $body     = false;
        $httpCode = 0;
        $method   = '';
        $err      = '';
        $location = '';

        // Versuch 1: file_get_contents (allow_url_fopen)
        if (ini_get('allow_url_fopen')) {
            $opts = [
                'http' => [
                    'method'         => 'GET',
                    'header'         => $headers,
                    'timeout'        => $binary ? 30 : 10,
                    'follow_location'=> 0, // WICHTIG: keine auto-redirects, wir machen das manuell
                    'ignore_errors'  => true, // 4xx/5xx als Body zurückgeben statt false
                ],
            ];
            $context = stream_context_create($opts);
            $body    = @file_get_contents($url, false, $context);
            if (isset($http_response_header)) {
                foreach ((array)$http_response_header as $hLine) {
                    if (preg_match('#HTTP/\S+\s+(\d{3})#', $hLine, $hm)) {
                        $httpCode = (int)$hm[1];
                    }
                    if (stripos($hLine, 'Location:') === 0) {
                        $location = trim(substr($hLine, 9));
                    }
                }
                $method = 'file_get_contents';
                if ($httpCode === 200) {
                    return ['body' => $body, 'http_code' => 200, 'error' => '', 'method' => $method, 'location' => $location];
                }
                // Bei Redirect: body verwerfen, aber Location merken
                if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
                    $body = false;
                    return ['body' => false, 'http_code' => $httpCode, 'error' => '', 'method' => $method, 'location' => $location];
                }
                // Anderer Fehler-Code
                $body = false;
            }
        } else {
            $method = 'allow_url_fopen=Off';
        }

        // Versuch 2: curl (ohne FOLLOWLOCATION — manuell!)
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $binary ? 30 : 10,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => false, // MANUELL!
                CURLOPT_HEADER         => false,
            ]);
            $body     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err      = curl_error($ch);

            // Redirect-Target aus Header extrahieren
            if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
                // curl_getinfo hat kein Redirect-URL ohne FOLLOWLOCATION,
                // also müssen wir den Header selbst parsen.
                curl_setopt($ch, CURLOPT_HEADER, true);
                $resp = curl_exec($ch);
                curl_close($ch);
                if ($resp !== false && preg_match('#Location:\s*(\S+)#i', $resp, $lm)) {
                    $location = trim($lm[1]);
                }
                return ['body' => false, 'http_code' => $httpCode, 'error' => '', 'method' => 'curl', 'location' => $location];
            }

            curl_close($ch);
            $method = 'curl';
            if ($body !== false && $httpCode === 200) {
                return ['body' => $body, 'http_code' => 200, 'error' => '', 'method' => $method, 'location' => ''];
            }
            $body = false;
        } elseif ($method === '') {
            $method = 'none (allow_url_fopen=Off, curl missing)';
        }

        return ['body' => $body, 'http_code' => $httpCode, 'error' => $err, 'method' => $method, 'location' => $location];
    }

    public function checkGithubUpdates(bool $force = false): array
    {
        $this->discover();
        $cacheFile = sys_get_temp_dir() . '/lmonext_addon_updates_v2.json';
        $ttl       = 3600; // 1 Stunde

        // Cache laden (wenn nicht force)
        if (!$force && is_file($cacheFile)) {
            $raw = @file_get_contents($cacheFile);
            if ($raw !== false) {
                $cached = json_decode($raw, true);
                if (is_array($cached) && isset($cached['timestamp']) && (time() - $cached['timestamp']) < $ttl) {
                    return $cached['results'] ?? [];
                }
            }
        }

        $results = [];

        foreach ($this->addons as $name => $addon) {
            $home = $addon['manifest']['homepage'] ?? '';
            $ver  = $addon['manifest']['version'] ?? '0.0.0';
            $results[$name] = [
                'current'          => $ver,
                'latest'           => '',
                'update_available' => false,
                'error'            => '',
                'checked_at'       => date('c'),
            ];

            // Homepage muss eine GitHub-URL sein
            if ($home === '' || !preg_match('#github\.com/([^/]+)/([^/]+)/?#', $home, $m)) {
                $results[$name]['error'] = 'no_github';
                continue;
            }

            $owner = $m[1];
            $repo  = $m[2];
            $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";
            $results[$name]['api_url'] = $apiUrl;
            // Releases-Übersichtsseite (nicht die homepage-URL, die ggf. auf einen
            // Unterordner zeigt — /releases muss immer auf Repo-Root aufsetzen)
            $results[$name]['releases_url'] = "https://github.com/{$owner}/{$repo}/releases";

            // Zentraler HTTP-Helper (Token, file_get_contents+curl Fallback)
            $resp = $this->httpGet($apiUrl);
            $results[$name]['method'] = $resp['method'];

            if ($resp['body'] === false) {
                $httpCode = $resp['http_code'];
                if ($httpCode === 403) {
                    $results[$name]['error'] = 'rate_limited';
                } elseif ($httpCode === 404) {
                    $results[$name]['error'] = 'no_release';
                } elseif ($resp['method'] === '') {
                    $results[$name]['error'] = 'no_http_client';
                } else {
                    $results[$name]['error'] = 'fetch_failed';
                }
                $results[$name]['http_code']  = $httpCode ?: 0;
                $results[$name]['curl_error'] = $resp['error'];
                continue;
            }

            $data = json_decode($resp['body'], true);
            if (!is_array($data) || !isset($data['tag_name'])) {
                $results[$name]['error'] = 'no_release';
                continue;
            }
            // zipball_url merken — wird von installUpdate() für den Auto-Update
            // Download benötigt (spart einen erneuten API-Call).
            $results[$name]['zipball_url'] = $data['zipball_url'] ?? '';

            $tag = (string)$data['tag_name'];
            // Tag normalisieren: v1.2.3 → 1.2.3
            $latest = ltrim($tag, 'vV');

            $results[$name]['latest']           = $latest;
            $results[$name]['update_available'] = version_compare($latest, $ver, '>');
        }

        // Cache schreiben
        @file_put_contents($cacheFile, json_encode([
            'timestamp' => time(),
            'results'   => $results,
        ], JSON_PRETTY_PRINT));

        return $results;
    }

    /**
     * Prüft ein einzelnes Addon auf Update (ohne Cache).
     *
     * @param string $name
     * @return array{current:string, latest:string, update_available:bool, error:string}
     */
    public function checkSingleAddon(string $name): array
    {
        $all = $this->checkGithubUpdates(true);
        return $all[$name] ?? [
            'current'          => '0.0.0',
            'latest'           => '',
            'update_available' => false,
            'error'            => 'not_found',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Auto-Update — lädt das neueste GitHub-Release herunter, entpackt es und
    //  tauscht die Addon-Dateien aus. Legt vorher ein ZIP-Backup an.
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Lädt das neueste GitHub-Release für ein Addon herunter und ersetzt die
     * lokalen Dateien in addon/{name}/ damit. Der aktivierte/deaktivierte
     * Status bleibt unberührt (steht in addon_registry, nicht im Dateisystem).
     *
     * Ablauf:
     *  1. Neuestes Release per GitHub-API holen (tag_name + zipball_url)
     *  2. Prüfen ob wirklich neuer als die installierte Version
     *  3. ZIP herunterladen und in ein Temp-Verzeichnis entpacken
     *  4. addon.json im entpackten Root validieren (Name muss passen —
     *     Schutz gegen falsch konfigurierte homepage-URLs)
     *  5. Aktuelles addon/{name}/ als ZIP sichern (_addon_backups/)
     *  6. Alte Dateien löschen, neue Dateien einkopieren
     *  7. Caches invalidieren
     *
     * @param string $name Addon-Name (wie im Manifest)
     * @return array{success:bool, error?:string, from?:string, to?:string, backup?:string}
     */
    public function installUpdate(string $name): array
    {
        $this->discover();
        if (!isset($this->addons[$name])) {
            return ['success' => false, 'error' => 'not_found'];
        }

        $addon      = $this->addons[$name];
        $home       = $addon['manifest']['homepage'] ?? '';
        $currentVer = $addon['manifest']['version'] ?? '0.0.0';

        if ($home === '' || !preg_match('#github\.com/([^/]+)/([^/]+)/?#', $home, $m)) {
            return ['success' => false, 'error' => 'no_github'];
        }
        $owner = $m[1];
        $repo  = $m[2];

        // ── 1. Neuestes Release abfragen ─────────────────────────────────────
        $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";
        $resp   = $this->httpGet($apiUrl);
        if ($resp['body'] === false) {
            $httpCode = $resp['http_code'];
            if ($httpCode === 403) {
                return ['success' => false, 'error' => 'rate_limited'];
            }
            if ($httpCode === 404) {
                return ['success' => false, 'error' => 'no_release'];
            }
            return ['success' => false, 'error' => $resp['method'] === '' ? 'no_http_client' : 'fetch_failed'];
        }

        $data = json_decode($resp['body'], true);
        if (!is_array($data) || !isset($data['tag_name'], $data['zipball_url'])) {
            return ['success' => false, 'error' => 'no_release'];
        }

        $latestVer = ltrim((string)$data['tag_name'], 'vV');
        $tagName   = (string)$data['tag_name']; // z.B. "v1.0.2" — Tag mit Prefix

        // ── 2. Wirklich neuer? ────────────────────────────────────────────────
        if (version_compare($latestVer, $currentVer, '<=')) {
            return ['success' => false, 'error' => 'already_current', 'current' => $currentVer, 'latest' => $latestVer];
        }

        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'zip_extension_missing'];
        }

        // ── 3. ZIP herunterladen ──────────────────────────────────────────────
        // Statt der API-redirect zipball_url (api.github.com → 302 → codeload)
        // nutzen wir die direkte Archive-URL. Die funktioniert ohne Redirect
        // und ohne API-Authentifizierung (für öffentliche Repos).
        $zipUrl   = "https://github.com/{$owner}/{$repo}/archive/refs/tags/{$tagName}.zip";
        $zipResp  = $this->httpGet($zipUrl, true);
        $downloadUrl = $zipUrl;

        // Fallback: falls die Archive-URL 404 gibt (z.B. wenn der Tag Sonderzeichen
        // enthält), probieren wir die codeload-URL direkt.
        if ($zipResp['body'] === false && $zipResp['http_code'] === 404) {
            $zipUrl = "https://codeload.github.com/{$owner}/{$repo}/legacy.zipball/{$tagName}";
            $zipResp = $this->httpGet($zipUrl, true);
            $downloadUrl = $zipUrl;
        }

        if ($zipResp['body'] === false) {
            return ['success' => false, 'error' => 'download_failed', 'http_code' => $zipResp['http_code'], 'url' => $downloadUrl];
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'lmo_update_');
        file_put_contents($tmpZip, $zipResp['body']);

        $tmpExtract = sys_get_temp_dir() . '/lmo_update_extract_' . bin2hex(random_bytes(6));
        mkdir($tmpExtract, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => 'zip_open_failed'];
        }

        // ── Inhaltsprüfung 1: ZIP-Einträge selbst, VOR dem Entpacken (siehe
        // installFromZip() für den ausführlichen Hintergrund - dieselbe
        // Prüfung gilt hier genauso, da ein kompromittiertes GitHub-Repo
        // (gekaperter Account, unbeaufsichtigter Fork o.ä.) denselben
        // Angriffsweg wie ein bösartiger ZIP-Upload eröffnen würde). ──────────
        $zipError = $this->validateZipEntries($zip);
        if ($zipError !== null) {
            $zip->close();
            @unlink($tmpZip);
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => $zipError];
        }

        $zip->extractTo($tmpExtract);
        $zip->close();
        @unlink($tmpZip);

        // ── Inhaltsprüfung 2: entpackte Dateien, VOR dem Kopieren in den
        // öffentlich erreichbaren addon/-Ordner ──────────────────────────────
        [$scanError, $scanFile] = $this->scanExtractedFiles($tmpExtract);
        if ($scanError !== null) {
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => $scanError, 'file' => $scanFile];
        }

        // ── 4. addon.json im Release finden ───────────────────────────────────
        // GitHub-Archive haben ein Root-Verzeichnis (z.B. "owner-repo-abc1234/").
        // Die addon.json kann direkt darin liegen ODER in einem Unterordner,
        // z.B. "owner-repo-abc1234/addon/liga-notizen/addon.json".
        // Wir suchen rekursiv nach allen addon.json-Dateien und nehmen die,
        // deren "name" zum gesuchten Addon passt.
        $foundManifests = $this->findFiles($tmpExtract, 'addon.json');
        $sourceDir  = '';
        $newManifest = null;
        foreach ($foundManifests as $manifestPath) {
            $mf = json_decode((string)file_get_contents($manifestPath), true);
            if (is_array($mf) && ($mf['name'] ?? '') === $name) {
                $sourceDir   = dirname($manifestPath);
                $newManifest = $mf;
                break;
            }
        }
        if ($newManifest === null) {
            // Keine passende addon.json gefunden — Fehler mit Kontext
            $tree = $this->debugTree($tmpExtract, 3);
            $this->rrmdir($tmpExtract);
            return [
                'success' => false,
                'error'   => empty($foundManifests) ? 'no_manifest_in_release' : 'manifest_name_mismatch',
                'tree'    => $tree,
                'found'   => count($foundManifests),
            ];
        }

        // ── 5. Backup des aktuellen Addon-Verzeichnisses ──────────────────────
        $addonPath = rtrim($this->addonDir, '/\\') . '/' . $name;
        $backupDir = rtrim($this->addonDir, '/\\') . '/../_addon_backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
            // Web-Zugriff auf Backups verhindern (Apache) — best effort.
            @file_put_contents($backupDir . '/.htaccess', "Deny from all
Require all denied
");
        }
        $backupZipPath = $backupDir . '/' . $name . '_' . $currentVer . '_' . date('Ymd-His') . '.zip';
        $this->zipDirectory($addonPath, $backupZipPath);

        // ── 6. Alte Dateien raus, neue Dateien rein (ATOMAR, siehe
        // atomicReplaceAddonDir() für den Hintergrund - wichtig gerade bei
        // einem Selbst-Update von "addon-manager") ────────────────────────
        if (!$this->atomicReplaceAddonDir($addonPath, $sourceDir)) {
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => 'copy_failed'];
        }

        // ── 7. Aufräumen + Caches invalidieren ────────────────────────────────
        $this->rrmdir($tmpExtract);
        $this->discovered = false;
        $this->addons      = [];
        @unlink(sys_get_temp_dir() . '/lmonext_addon_updates_v2.json');

        return [
            'success' => true,
            'from'    => $currentVer,
            'to'      => $latestVer,
            'backup'  => $backupZipPath,
        ];
    }

    /**
     * Rekursiv ein Verzeichnis löschen.
     */
    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff((array)@scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Rekursiv Dateien von $src nach $dst kopieren (überschreibt Ziel).
     *
     * @return bool true, wenn ALLE Dateien erfolgreich kopiert wurden.
     */
    private function copyDirectory(string $src, string $dst): bool
    {
        if (!is_dir($dst) && !@mkdir($dst, 0755, true) && !is_dir($dst)) {
            return false;
        }
        $ok = true;
        $items = array_diff((array)@scandir($src), ['.', '..']);
        foreach ($items as $item) {
            $s = $src . '/' . $item;
            $d = $dst . '/' . $item;
            if (is_dir($s)) {
                $ok = $this->copyDirectory($s, $d) && $ok;
            } else {
                $ok = @copy($s, $d) && $ok;
            }
        }
        return $ok;
    }

    /**
     * Ersetzt ein Addon-Verzeichnis ATOMAR durch neue Dateien (Beitrag:
     * Bugfix - "Update von addon-manager meldet Erfolg, Version bleibt
     * unverändert"). Die vorherige Lösung (rrmdir() des Zielordners, dann
     * copyDirectory() der neuen Dateien hinein) hat ein grundsätzliches
     * Problem bei einem SELBST-Update des addon-manager-Addons: die gerade
     * ausführende Datei (handler_addons.php, die diesen Update-Vorgang
     * selbst durchführt) versucht sich dabei SELBST zu überschreiben.
     * Auf manchen Server-Konfigurationen ist eine gerade ausgeführte PHP-
     * Datei gesperrt - copy() schlägt für genau diese eine Datei (und
     * ggf. addon.json selbst) STILL fehl (kein Fehler, kein Abbruch, da
     * der Rückgabewert vorher nicht geprüft wurde), während alle anderen
     * Dateien erfolgreich aktualisiert werden - der Vorgang meldete
     * trotzdem "success", die installierte Version blieb aber unverändert.
     *
     * Fix: Standard-Muster für Selbst-Updates - neue Dateien zuerst in ein
     * FRISCHES, noch unbenutztes Verzeichnis kopieren (dort kollidiert
     * nichts mit offenen Datei-Handles), dann per rename() (auf den
     * meisten Dateisystemen eine atomare Operation, die nur den
     * Verzeichniseintrag ändert, nicht den Dateiinhalt selbst antastet -
     * funktioniert daher auch bei offen gehaltenen Dateien problemlos) das
     * alte Verzeichnis beiseiteschieben und das neue an dessen Stelle
     * setzen. Das alte Verzeichnis wird erst NACH dem Umbenennen entfernt.
     *
     * @return bool true bei vollständigem Erfolg
     */
    private function atomicReplaceAddonDir(string $addonPath, string $sourceDir): bool
    {
        $tmpNew = $addonPath . '_new_' . bin2hex(random_bytes(4));
        if (!$this->copyDirectory($sourceDir, $tmpNew)) {
            $this->rrmdir($tmpNew);
            return false;
        }

        if (is_dir($addonPath)) {
            $tmpOld = $addonPath . '_old_' . bin2hex(random_bytes(4));
            if (!@rename($addonPath, $tmpOld)) {
                // Altes Verzeichnis konnte nicht beiseitegeschoben werden -
                // kein Update, aufräumen und ehrlich Fehler melden statt
                // stillschweigend nichts zu tun.
                $this->rrmdir($tmpNew);
                return false;
            }
            if (!@rename($tmpNew, $addonPath)) {
                // Neues Verzeichnis konnte nicht an die Zielposition
                // verschoben werden - altes Verzeichnis zurückholen, damit
                // das Addon nicht in einem kaputten Zwischenzustand bleibt.
                @rename($tmpOld, $addonPath);
                $this->rrmdir($tmpNew);
                return false;
            }
            $this->rrmdir($tmpOld);
        } else {
            if (!@rename($tmpNew, $addonPath)) {
                $this->rrmdir($tmpNew);
                return false;
            }
        }

        return true;
    }

    /**
     * Ein Verzeichnis rekursiv als ZIP packen (für Backups vor dem Update).
     * Gibt still auf, wenn das Quellverzeichnis nicht existiert (z.B. bei
     * einem Addon, das noch nie installiert war).
     */
    private function zipDirectory(string $srcDir, string $zipPath): bool
    {
        if (!is_dir($srcDir)) {
            return false;
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        $srcDir = rtrim($srcDir, '/\\');
        $files  = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            $filePath     = $file->getRealPath();
            $relativePath = substr($filePath, strlen($srcDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
        $zip->close();
        return true;
    }

    /**
     * Rekursive Suche nach Dateien mit einem bestimmten Namen.
     * Gibt ein Array voller absoluter Pfade zurück.
     */
    private function findFiles(string $dir, string $filename): array
    {
        $results = [];
        if (!is_dir($dir)) {
            return $results;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $filename) {
                $results[] = $file->getRealPath();
            }
        }
        return $results;
    }

    /**
     * Installiert ein neues Addon aus einer hochgeladenen ZIP-Datei.
     *
     * Erwartet den Pfad zu einer ZIP-Datei, die entweder direkt eine addon.json
     * im Root enthält oder in einem Unterverzeichnis (wie bei GitHub-Archiven).
     * Das Addon wird nach addon/{name}/ entpackt, wobei {name} aus der
     * addon.json gelesen wird.
     *
     * Validiert:
     * - addon.json existiert und ist lesbar
     * - Name entspricht /^[a-z0-9_-]+$/
     * - min_core_version wird geprüft
     * - Wenn das Addon schon existiert, wird es vorher als ZIP gesichert
     *
     * @param string $zipPath Pfad zur hochgeladenen ZIP-Datei
     * @return array{success:bool, name?:string, version?:string, error?:string, backup?:string}
     */
    /**
     * Prüft die Einträge einer geöffneten ZIP-Datei GEGEN eine Whitelist,
     * BEVOR überhaupt etwas entpackt wird - Sicherheitsmaßnahme gegen über
     * den Addon-Manager hochgeladene bösartige Inhalte (Beitrag:
     * Sicherheitsüberarbeitung). Zwei unabhängige Prüfungen je Eintrag:
     *
     * 1) Zip-Slip-Schutz: ein Eintragsname wie "../../etc/passwd" oder ein
     *    absoluter Pfad könnte beim Entpacken Dateien AUSSERHALB des
     *    vorgesehenen Zielverzeichnisses schreiben. ZipArchive::extractTo()
     *    bietet in aktuellen PHP-Versionen zwar bereits einen gewissen
     *    eingebauten Schutz, diese explizite Prüfung verlässt sich aber
     *    nicht darauf.
     * 2) Dateiendungs-Whitelist: nur Dateitypen, die ein LMOnext-Addon
     *    plausibel benötigt (Code, Text, gängige Bildformate) - alles
     *    andere (z.B. .exe, .sh, .phar, doppelte Endungen wie .php.jpg)
     *    führt zur sofortigen Ablehnung der GESAMTEN ZIP.
     *
     * @return string|null Fehlercode oder null, wenn alles unauffällig ist.
     */
    private function validateZipEntries(\ZipArchive $zip): ?string
    {
        $allowedExt = ['php', 'json', 'md', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'webp', 'css', 'js'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            $normalized = str_replace('\\', '/', $name);

            // Zip-Slip: Pfad-Traversal, absoluter Pfad, NUL-Byte, Windows-
            // Laufwerksbuchstabe.
            if (str_contains($normalized, '../') || str_contains($normalized, "\0")
                || str_starts_with($normalized, '/') || preg_match('#^[A-Za-z]:#', $normalized)) {
                return 'zip_unsafe_path';
            }

            // Verzeichniseinträge (enden auf "/") haben keine Dateiendung
            // zu prüfen.
            if (str_ends_with($normalized, '/')) {
                continue;
            }

            $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                return 'zip_disallowed_filetype';
            }
        }
        return null;
    }

    /**
     * Prüft bereits ENTPACKTE Dateien auf offensichtliche Auffälligkeiten,
     * BEVOR sie in das öffentlich erreichbare addon/-Verzeichnis kopiert
     * werden (Beitrag: Sicherheitsüberarbeitung). Zwei Prüfungen je
     * .php-Datei:
     *
     * 1) Syntax-Check per "php -l" (Lint, KEINE Ausführung) - lehnt grob
     *    fehlerhafte/verschleierte Dateien ab, die keine gültige PHP-Datei
     *    sind (z.B. ein als .php getarntes Binary).
     * 2) Grobe Muster-Suche nach typischen Funktionsaufrufen, die in
     *    einem normalen LMOnext-Addon nichts verloren haben (Shell-
     *    Ausführung, dynamische Codeausführung, Ein-Zeilen-Obfuscation).
     *    Dies ist eine HEURISTIK, kein vollständiger Malware-Scanner -
     *    sie hebt die Hürde für unraffinierte automatisierte Angriffe
     *    deutlich an, garantiert aber keine Erkennung von gezielt
     *    verschleiertem Code.
     *
     * @return array{0:string|null,1:string} [Fehlercode oder null, betroffene relative Datei]
     */
    private function scanExtractedFiles(string $dir): array
    {
        // WICHTIG: negativer Lookbehind (?<!->)(?<!::) vor jedem Funktionsnamen
        // - verhindert Fehlalarme bei harmlosen METHODENAUFRUFEN gleichen
        // Namens (z.B. PDO::exec() für SQL-Statements, $db->exec(...) - eine
        // der in diesem Projekt allgegenwärtigsten Methoden, siehe
        // src/Addon/AddonManager.php selbst). Nur der GLOBALE Funktionsaufruf
        // (kein "->"/"::" davor) gilt als Treffer. Das Backtick-Muster für
        // PHP-Shell-Ausführung wurde ENTFERNT (siehe CHANGELOG.md): SQL mit
        // Backtick-quotierten Spaltennamen (z.B. "CREATE TABLE ... `id` INT
        // ...") ist der projektweite Standardstil (siehe tbl()-Helper) und
        // hätte praktisch JEDES SQL-schreibende Addon fälschlich blockiert -
        // die verbleibenden expliziten Funktionsmuster unten (shell_exec,
        // system, exec, ...) decken den eigentlichen Bedrohungsfall bereits ab.
        $dangerousPatterns = [
            '/(?<!->)(?<!::)\bsystem\s*\(/i', '/(?<!->)(?<!::)\bexec\s*\(/i', '/(?<!->)(?<!::)\bshell_exec\s*\(/i',
            '/(?<!->)(?<!::)\bpassthru\s*\(/i', '/(?<!->)(?<!::)\bproc_open\s*\(/i', '/(?<!->)(?<!::)\bpopen\s*\(/i',
            '/(?<!->)(?<!::)\bpcntl_exec\s*\(/i',
            '/(?<!->)(?<!::)\beval\s*\(/i', '/(?<!->)(?<!::)\bassert\s*\(\s*[\'"$]/i', '/(?<!->)(?<!::)\bcreate_function\s*\(/i',
            '/\bbase64_decode\s*\(\s*[\'"$][^)]*\)\s*\)?\s*;?\s*$/im', // Verdacht: base64_decode am Zeilenende ohne weitere Verarbeitung
            '/\$\{\s*[\'"]\w+[\'"]\s*\}\s*\(/', // ${'x'}(...) dynamische Funktionsaufruf-Verschleierung
        ];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $relPath = substr((string)$file->getPathname(), strlen($dir) + 1);

            // php -l-Syntaxcheck, ZWINGEND mit hartem Zeitlimit (siehe
            // lintPhpFileWithTimeout()): PHP_BINARY zeigt auf vielen
            // Shared-Hosting-Umgebungen mit PHP-FPM NICHT auf einen normal
            // per exec() aufrufbaren CLI-Interpreter, sondern auf den
            // FPM-Worker-Prozess - ein ungeschützter Aufruf kann dadurch
            // UNBEGRENZT haengen bleiben (beobachtet: sehr lange Ladezeit,
            // dann Timeout ohne jede Fehlermeldung, siehe CHANGELOG.md).
            // Kann das Ergebnis nicht innerhalb des Zeitlimits ermittelt
            // werden, wird NICHT blockiert (fail-open) - die übrigen
            // Prüfungen (Dateityp-Whitelist, Zip-Slip, Muster-Suche unten)
            // bleiben in jedem Fall wirksam.
            $lintResult = $this->lintPhpFileWithTimeout((string)$file->getPathname());
            if ($lintResult === false) {
                return ['php_lint_failed', $relPath];
            }

            // Kommentare vor der Muster-Suche entfernen (nicht String-
            // Literale - dort könnte verschleierter Code stecken, der
            // weiterhin erkannt werden soll). Verhindert Fehlalarme, wenn
            // ein Funktionsname wie "eval(" nur in einem KOMMENTAR auftaucht
            // (z.B. "// kein eval()! Sicherer Ersatz für ...") - beobachtet
            // beim player-Addon, das explizit dokumentiert, eval() NICHT zu
            // verwenden (siehe CHANGELOG.md).
            $content = (string)file_get_contents((string)$file->getPathname());
            $codeOnly = $this->stripPhpComments($content);
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $codeOnly)) {
                    return ['dangerous_pattern_found', $relPath];
                }
            }
        }
        return [null, ''];
    }

    /**
     * Entfernt // - und # -Zeilenkommentare sowie /* -Blockkommentare aus
     * PHP-Quelltext (grobe, zeichenweise Näherung - kein vollständiger
     * Tokenizer), OHNE String-Literale anzutasten. Wird von
     * scanExtractedFiles() genutzt, damit Erwähnungen sicherheitsrelevanter
     * Funktionsnamen INNERHALB von Kommentaren (z.B. erläuternde Docblocks)
     * keine Fehlalarme auslösen.
     */
    private function stripPhpComments(string $content): string
    {
        $out = '';
        $len = strlen($content);
        $state = 'code'; // code, sq_string, dq_string, line_comment, block_comment
        for ($i = 0; $i < $len; $i++) {
            $ch  = $content[$i];
            $nxt = $i + 1 < $len ? $content[$i + 1] : '';
            if ($state === 'code') {
                if ($ch === "'") {
                    $state = 'sq_string';
                    $out .= $ch;
                } elseif ($ch === '"') {
                    $state = 'dq_string';
                    $out .= $ch;
                } elseif ($ch === '/' && $nxt === '/') {
                    $state = 'line_comment';
                    $i++;
                } elseif ($ch === '#' && $nxt !== '[') { // #[Attribute] nicht als Kommentar behandeln
                    $state = 'line_comment';
                } elseif ($ch === '/' && $nxt === '*') {
                    $state = 'block_comment';
                    $i++;
                } else {
                    $out .= $ch;
                }
            } elseif ($state === 'sq_string') {
                $out .= $ch;
                if ($ch === '\\') { $out .= $nxt; $i++; }
                elseif ($ch === "'") { $state = 'code'; }
            } elseif ($state === 'dq_string') {
                $out .= $ch;
                if ($ch === '\\') { $out .= $nxt; $i++; }
                elseif ($ch === '"') { $state = 'code'; }
            } elseif ($state === 'line_comment') {
                if ($ch === "\n") { $state = 'code'; $out .= $ch; }
            } elseif ($state === 'block_comment') {
                if ($ch === '*' && $nxt === '/') { $state = 'code'; $i++; }
            }
        }
        return $out;
    }

    /**
     * Führt "php -l" (Syntax-Lint, KEINE Ausführung) auf eine einzelne Datei
     * aus, mit hartem Zeitlimit über proc_open()/proc_terminate() statt
     * eines ungeschützten exec()-Aufrufs (siehe scanExtractedFiles() für den
     * Hintergrund - ein simpler exec()-Aufruf kann auf manchen PHP-FPM-
     * Umgebungen unbegrenzt haengen, weil PHP_BINARY dort nicht auf einen
     * CLI-tauglichen Interpreter zeigt).
     *
     * @return bool|null true = gültige Syntax, false = ungültige Syntax
     *                    (Upload ablehnen), null = konnte nicht geprüft
     *                    werden (z.B. exec/proc_open gesperrt oder Timeout
     *                    erreicht) - wird NICHT als Ablehnungsgrund gewertet.
     */
    private function lintPhpFileWithTimeout(string $filePath, float $timeoutSeconds = 3.0)
    {
        static $usable = null;
        if ($usable === null) {
            $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
            $usable = function_exists('proc_open') && function_exists('proc_terminate')
                && !in_array('proc_open', $disabled, true);
        }
        if (!$usable) {
            return null;
        }

        $phpBinary = PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open([$phpBinary, '-l', $filePath], $descriptors, $pipes);
        if (!is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $start = microtime(true);
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(50000);
        } while ((microtime(true) - $start) < $timeoutSeconds);

        if ($status['running']) {
            // Zeitlimit erreicht: Prozess zwangsweise beenden, KEIN
            // Ablehnungsgrund - siehe Docblock oben.
            @proc_terminate($process, 9);
            fclose($pipes[1]);
            fclose($pipes[2]);
            @proc_close($process);
            return null;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        return $exitCode === 0;
    }

    public function installFromZip(string $zipPath): array
    {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'zip_extension_missing'];
        }
        if (!is_file($zipPath)) {
            return ['success' => false, 'error' => 'file_not_found'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'zip_open_failed'];
        }

        // ── Inhaltsprüfung 1: ZIP-Einträge selbst, VOR dem Entpacken ─────────
        $zipError = $this->validateZipEntries($zip);
        if ($zipError !== null) {
            $zip->close();
            return ['success' => false, 'error' => $zipError];
        }

        // Temporäres Entpack-Verzeichnis
        $tmpExtract = sys_get_temp_dir() . '/lmo_install_' . bin2hex(random_bytes(6));
        mkdir($tmpExtract, 0755, true);

        $zip->extractTo($tmpExtract);
        $zip->close();

        // ── Inhaltsprüfung 2: entpackte Dateien, VOR dem Kopieren in den
        // öffentlich erreichbaren addon/-Ordner ──────────────────────────────
        [$scanError, $scanFile] = $this->scanExtractedFiles($tmpExtract);
        if ($scanError !== null) {
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => $scanError, 'file' => $scanFile];
        }

        // ── addon.json suchen (rekursiv, wie bei installUpdate) ──────────────
        $manifests = $this->findFiles($tmpExtract, 'addon.json');
        if (empty($manifests)) {
            $tree = $this->debugTree($tmpExtract, 3);
            $this->rrmdir($tmpExtract);
            return [
                'success' => false,
                'error'   => 'no_manifest_in_zip',
                'tree'    => $tree,
                'found'   => 0,
            ];
        }

        // Erste gefundene addon.json verwenden
        $manifestPath  = $manifests[0];
        $sourceDir      = dirname($manifestPath);
        $manifestJson   = file_get_contents($manifestPath);
        $manifest       = json_decode($manifestJson, true);

        if (!is_array($manifest) || empty($manifest['name'])) {
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => 'invalid_manifest'];
        }

        $name    = $manifest['name'];
        $version = $manifest['version'] ?? '?';

        // Namensvalidierung
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => 'invalid_name', 'name' => $name];
        }

        // Min-Core-Version prüfen
        $minCore = $manifest['min_core_version'] ?? '';
        if ($minCore !== '' && defined('LMONEXT_VERSION') && version_compare(LMONEXT_VERSION, $minCore, '<')) {
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => 'core_version', 'need' => $minCore, 'have' => LMONEXT_VERSION];
        }

        // ── Backup falls Addon schon existiert ────────────────────────────────
        $addonPath  = rtrim($this->addonDir, '/\\') . '/' . $name;
        $backupPath = '';
        if (is_dir($addonPath)) {
            $backupDir = rtrim($this->addonDir, '/\\') . '/../_addon_backups';
            if (!is_dir($backupDir)) {
                @mkdir($backupDir, 0755, true);
                @file_put_contents($backupDir . '/.htaccess', "Deny from all\nRequire all denied\n");
            }
            $currentManifest = @json_decode(file_get_contents($addonPath . '/addon.json'), true);
            $currentVer      = $currentManifest['version'] ?? 'unknown';
            $backupPath      = $backupDir . '/' . $name . '_' . $currentVer . '_replace_' . date('Ymd-His') . '.zip';
            $this->zipDirectory($addonPath, $backupPath);
        }

        // ── Neue Dateien kopieren (ATOMAR, siehe atomicReplaceAddonDir() für
        // den Hintergrund - wichtig gerade bei einem Selbst-Update von
        // "addon-manager" über diesen ZIP-Upload-Weg) ──────────────────────
        if (!$this->atomicReplaceAddonDir($addonPath, $sourceDir)) {
            $this->rrmdir($tmpExtract);
            return ['success' => false, 'error' => 'copy_failed'];
        }

        // ── Aufräumen ────────────────────────────────────────────────────────────
        $this->rrmdir($tmpExtract);
        $this->discovered = false;
        $this->addons     = [];

        // ── Backups aufräumen (max. 5 pro Addon) ─────────────────────────────────
        $this->cleanupBackups($name);

        return [
            'success' => true,
            'name'    => $name,
            'version' => $version,
            'backup'  => $backupPath,
        ];
    }

    /**
     * Räumt alte Addon-Backups in _addon_backups/ auf.
     * Behält nur die letzten $max Backups pro Addon (basierend auf dem
     * Dateinamen-Prefix addonname_).
     *
     * @param string $addonName Name des Addons (optional — wenn leer, alle Addons)
     * @param int $max Maximale Anzahl Backups pro Addon (Default: 5)
     */
    public function cleanupBackups(string $addonName = '', int $max = 5): int
    {
        $backupDir = rtrim($this->addonDir, '/\\') . '/../_addon_backups';
        if (!is_dir($backupDir)) {
            return 0;
        }

        $pattern = $addonName !== '' ? $addonName . '_*.zip' : '*.zip';
        $files   = glob($backupDir . '/' . $pattern) ?: [];

        if (empty($files) || count($files) <= $max) {
            return 0;
        }

        // Nach Änderungsdatum sortieren (neueste zuerst)
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $deleted = 0;
        foreach (array_slice($files, $max) as $old) {
            @unlink($old);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Erzeugt einen vereinfachten Verzeichnisbaum für Debug-Zwecke.
     * Max depth begrenzt die Tiefe.
     */
    private function debugTree(string $dir, int $maxDepth): string
    {
        if (!is_dir($dir) || $maxDepth < 0) {
            return '';
        }
        $lines = [];
        $entries = array_diff((array)@scandir($dir), ['.', '..']);
        sort($entries);
        foreach ($entries as $entry) {
            $path    = $dir . '/' . $entry;
            $isDir   = is_dir($path);
            $lines[] = $entry . ($isDir ? '/' : '');
            if ($isDir && $maxDepth > 0) {
                $sub = $this->debugTree($path, $maxDepth - 1);
                if ($sub !== '') {
                    foreach (explode("\n", $sub) as $subLine) {
                        $lines[] = '  ' . $subLine;
                    }
                }
            }
        }
        return implode("\n", $lines);
    }
}

} // end namespace LMOnext\Addon

// ═══════════════════════════════════════════════════════════════════════════
//  Globale Hook & Manager API (Procedural Wrapper)
//  — im globalen Namespace, damit data_loader.php, home.php, liga.php etc.
//    addonManager(), registerHook() und doHook() ohne use-Statements nutzen
//    können.
// ═══════════════════════════════════════════════════════════════════════════

namespace {

if (!function_exists('addonManager')) {
    /**
     * Singleton / Hilfsfunktion fuer die globale AddonManager-Instanz.
     *
     * @param \LMOnext\Addon\AddonManager|null $manager
     * @return \LMOnext\Addon\AddonManager
     */
    function addonManager(?\LMOnext\Addon\AddonManager $manager = null): \LMOnext\Addon\AddonManager
    {
        if ($manager !== null) {
            $GLOBALS['__addonManager'] = $manager;
        }

        if (!isset($GLOBALS['__addonManager']) || !($GLOBALS['__addonManager'] instanceof \LMOnext\Addon\AddonManager)) {
            $addonDir = dirname(__DIR__, 2) . '/addon/';
            $GLOBALS['__addonManager'] = new \LMOnext\Addon\AddonManager($addonDir);
        }

        return $GLOBALS['__addonManager'];
    }
}

if (!function_exists('registerHook')) {
    /**
     * Registriert einen Handler fuer ein Event (Hook).
     *
     * @param string   $event    Event-Name ('liga.saved', 'team.saved', etc.)
     * @param callable $handler  Callback-Funktion
     * @param int      $priority Prioritaet (Standard: 10)
     */
    function registerHook(string $event, callable $handler, int $priority = 10): void
    {
        \addonManager()->registerHook($event, $handler, $priority);
    }
}

if (!function_exists('doHook')) {
    /**
     * Feuert ein Event ab und fuehrt alle registrierten Handler aus.
     *
     * @param string $event Event-Name
     * @param array  $data  Payload-Daten
     * @return array
     */
    function doHook(string $event, array $data = []): array
    {
        return \addonManager()->doHook($event, $data);
    }
}

} // end global namespace
