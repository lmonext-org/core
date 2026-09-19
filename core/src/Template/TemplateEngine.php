<?php
declare(strict_types=1);

namespace LMOnext\Template;

final class TemplateEngine
{

/**
 * Project: LMOnext
 * Filename: template_engine.php
 * Fileversion: 2.8.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── Funktionsweise (angelehnt an den alten LMO) ───────────────────────────────
 * Ein Template ist ein Ordner unter /template/<name>/ mit:
 *   - template.json         Metadaten (Name, Beschreibung) – für die Admin-Auswahl
 *   - layout.tpl.php         HTML-Grundgerüst der ganzen Seite (<html>...<!--Hauptteil-->...)
 *   - <seite>.tpl.php        Seiteninhalt (z.B. home.tpl.php, liga.tpl.php) – wird in
 *   - partials/<name>.tpl.php  Wiederkehrende Bausteine (Tabellenzeile, Dropdown-Option, …)
 *
 * ALLE dieser Dateien enthalten AUSSCHLIESSLICH HTML/CSS und Platzhalter der Form
 * <!--Platzhalter--> – kein einziges <?php ?>-Tag. Die komplette Logik (Schleifen,
 * Bedingungen, DB-Zugriffe) steckt in frontend/bootstrap.php, frontend/data_home.php,
 * frontend/data_liga.php sowie den Root-Controllern (home.php, liga.php). Diese bauen
 * fertige HTML-Strings (auch aus mehreren self::renderPartial()-Aufrufen zusammengesetzt) und
 * übergeben sie als Platzhalter-Werte an self::renderTemplate().
 *
 * Ein neues Template erstellen:
 *   1. Ordner /template/<neuer-name>/ anlegen
 *   2. template.json mit {"name": "...", "description": "..."} anlegen
 *   3. layout.tpl.php, <seite>.tpl.php und partials/*.tpl.php nach Vorbild von
 *      template/default/ anlegen – reines Markup, gleiche Platzhalternamen
 *   4. Fertig – taucht automatisch in der Admin-Dropdown-Liste auf.
 */


private const TEMPLATE_DIR = __DIR__ . '/../../template';
private const TEMPLATE_SESSION_KEY = 'lmonext_template';
private const DEFAULT_TEMPLATE = 'default';

/**
 * Liefert alle gefundenen Templates: ['ordnername' => ['name'=>..,'description'=>..]]
 */
public static function getAvailableTemplates() : array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $result = [];
    foreach (glob(self::TEMPLATE_DIR . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $jsonFile = $dir . '/template.json';
        if (!is_file($jsonFile)) {
            continue;
        }
        $meta = json_decode((string)file_get_contents($jsonFile), true);
        if (!is_array($meta)) {
            continue;
        }
        $key = basename($dir);
        $result[$key] = [
            'name'        => $meta['name'] ?? $key,
            'description' => $meta['description'] ?? '',
        ];
    }
    if (empty($result)) {
        // Absoluter Fallback, falls kein Template gefunden wird (z.B. Ordner fehlt)
        $result[self::DEFAULT_TEMPLATE] = ['name' => 'Default', 'description' => ''];
    }
    return $cache = $result;
}

/**
 * Ermittelt das aktuell aktive Template für den Besucher.
 * Reihenfolge: expliziter Wechsel per ?template=xxx (nur wenn erlaubt) →
 * bereits gewähltes Template in der Session (nur wenn weiterhin erlaubt) →
 * Admin-Standard → absoluter Fallback.
 *
 * Kann bei ?template=xxx redirecten (muss also vor jeglichem Output aufgerufen werden).
 * Merkt sich das Ergebnis zusätzlich intern (siehe self::getActiveTemplateName()), damit
 * self::renderPartial() nicht bei jedem Aufruf den Namen mitgereicht bekommen muss.
 */
public static function resolveActiveTemplate(string $configuredDefault, bool $allowSwitch) : string
{
    $available = self::getAvailableTemplates();
    $result    = self::DEFAULT_TEMPLATE;

    if ($allowSwitch && isset($_GET['template']) && array_key_exists($_GET['template'], $available)) {
        $_SESSION[self::TEMPLATE_SESSION_KEY] = $_GET['template'];

        $qs = $_GET;
        unset($qs['template']);
        $self   = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
        $target = $self . (empty($qs) ? '' : ('?' . http_build_query($qs)));
        header('Location: ' . $target);
        exit;
    }

    if ($allowSwitch && isset($_SESSION[self::TEMPLATE_SESSION_KEY]) && array_key_exists($_SESSION[self::TEMPLATE_SESSION_KEY], $available)) {
        $result = $_SESSION[self::TEMPLATE_SESSION_KEY];
    } elseif (array_key_exists($configuredDefault, $available)) {
        $result = $configuredDefault;
    }

    self::setActiveTemplateName($result);
    return $result;
}

/** Merkt sich den Namen des aktiven Templates für self::renderPartial(). */
public static function setActiveTemplateName(string $name) : void
{
    $GLOBALS['__lmonext_active_template'] = $name;
}

/** Liefert den Namen des aktiven Templates (siehe self::setActiveTemplateName()). */
public static function getActiveTemplateName() : string
{
    return $GLOBALS['__lmonext_active_template'] ?? self::DEFAULT_TEMPLATE;
}

/**
 * Ersetzt Platzhalter der Form <!--Name--> im übergebenen HTML durch die
 * Werte aus $vars (Schlüssel = Platzhaltername ohne die Kommentar-Klammern).
 */
public static function substitutePlaceholders(string $html, array $vars) : string
{
    $search  = [];
    $replace = [];
    foreach ($vars as $key => $value) {
        $search[]  = '<!--' . $key . '-->';
        $replace[] = (string)$value;
    }
    return str_replace($search, $replace, $html);
}

/**
 * Lädt eine einzelne .tpl.php-Datei aus dem aktiven Template und ersetzt ihre
 * Platzhalter. Gibt einen leeren String zurück, falls die Datei nicht existiert.
 */
public static function loadTemplateFile(string $relativePath, array $vars) : string
{
    $dir  = self::TEMPLATE_DIR . '/' . self::getActiveTemplateName();
    $file = $dir . '/' . $relativePath;
    if (!is_file($file)) {
        $file = self::TEMPLATE_DIR . '/' . self::DEFAULT_TEMPLATE . '/' . $relativePath;
    }
    if (!is_file($file)) {
        return '';
    }
    return self::substitutePlaceholders((string)file_get_contents($file), $vars);
}

/**
 * Rendert einen wiederkehrenden Baustein (z.B. eine Tabellenzeile, einen
 * Dropdown-Eintrag, einen Archiv-Ordner) über template/<aktiv>/partials/<name>.tpl.php.
 * Wird typischerweise in einer Schleife im Grundgerüst (frontend/*.php) aufgerufen,
 * die die Ergebnis-Strings aneinanderhängt.
 */
public static function renderPartial(string $partialName, array $vars = []) : string
{
    return self::loadTemplateFile('partials/' . $partialName . '.tpl.php', $vars);
}

/**
 * Rendert eine ganze Seite: füllt zuerst template/<aktiv>/<page>.tpl.php mit den
 * übergebenen Platzhaltern, setzt das Ergebnis dann als "Hauptteil" in
 * template/<aktiv>/layout.tpl.php ein und gibt das fertige HTML aus.
 * "HtmlLang", "Sprachauswahl" und "Berechnungszeit" werden automatisch ergänzt
 * (wie im alten LMO zentral im Grundgerüst gesetzt) – Controller müssen sie
 * nicht selbst befüllen. "Berechnungszeit" nutzt PHPs eingebautes
 * REQUEST_TIME_FLOAT und wird bewusst als Letztes berechnet, damit die
 * Anzeige möglichst die tatsächliche Gesamtdauer widerspiegelt.
 */
/**
 * Baut das Dropdown, mit dem Besucher (falls in den Einstellungen erlaubt)
 * zwischen den vorhandenen Templates wechseln können – vom Aufbau her
 * bewusst analog zu renderLanguageSwitcher() (auto-submitting <form>, alle
 * übrigen GET-Parameter werden als Hidden-Felder mitgeführt). Gibt einen
 * leeren String zurück, wenn der Wechsel nicht erlaubt ist oder es ohnehin
 * nur ein einziges Template gibt (dann gäbe es nichts zur Auswahl).
 */
public static function renderTemplateSwitcher(bool $allowSwitch) : string
{
    $available = self::getAvailableTemplates();
    if (!$allowSwitch || count($available) < 2) {
        return '';
    }
    $current = self::getActiveTemplateName();
    $esc = static fn(string $v) : string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $html = '<form method="get" class="template-switch" onchange="this.submit()">';
    foreach ($_GET as $k => $v) {
        if ($k === 'template' || !is_scalar($v)) {
            continue;
        }
        $html .= '<input type="hidden" name="' . $esc((string)$k) . '" value="' . $esc((string)$v) . '">';
    }
    $html .= '<select name="template" aria-label="' . $esc(tf('template_switch_label')) . '">';
    foreach ($available as $key => $meta) {
        $sel = $key === $current ? ' selected' : '';
        $html .= '<option value="' . $esc($key) . '"' . $sel . '>' . $esc($meta['name']) . '</option>';
    }
    $html .= '</select></form>';
    return $html;
}

public static function renderTemplate(string $activeTemplate, string $page, array $vars = []) : void
{
    self::setActiveTemplateName($activeTemplate);
    $available     = self::getAvailableTemplates();
    $templateLabel = $available[$activeTemplate]['name'] ?? $activeTemplate;

    // Ist der Wechsel erlaubt (und gibt es überhaupt mehr als ein Template),
    // steht im Footer statt des reinen Namens das Auswahl-Dropdown – sonst
    // wie bisher nur Klartext "Template: {name}".
    $switcherHtml = self::renderTemplateSwitcher((bool)($GLOBALS['allowTemplateSwitch'] ?? false));
    $templateZeile = $switcherHtml !== ''
        ? h(tf('footer_template_prefix')) . ' ' . $switcherHtml
        : h(tf('footer_template', ['name' => $templateLabel]));

    $vars += [
        'HtmlLang'      => h(getCurrentLanguage('frontend')),
        'Sprachauswahl' => getAdminSetting('show_language_switcher', '1') === '1' ? renderLanguageSwitcher('frontend') : '',
        // im Besucherbereich (Footer) keine Versionsnummer mehr
        // zeigen, stattdessen nur noch "Supported by LMOnext" - nur "LMOnext"
        // selbst ist dabei der Link (zur Projekt-Website), nicht der ganze
        // Satz. {link} kommt bereits als fertiges <a>-Tag rein - transDomain()
        // (lang/i18n.php) escaped Platzhalterwerte nicht, das ist hier
        // gewollt, genau wie bei "name" in footer_template weiter unten.
        'PoweredBy'     => tf('footer_powered_by', [
            'link' => '<a href="https://www.liga-manager-online.org" target="_blank" rel="noopener">LMOnext</a>',
        ]),
        'CopyrightNotice' => \renderCopyrightNotice(),
        'TemplateZeile' => $templateZeile,
        'TippspielLink' => function_exists('tippRenderSiteLink') ? tippRenderSiteLink() : '',
    ];
    $pageHtml          = self::loadTemplateFile($page . '.tpl.php', $vars);
    $vars['Hauptteil'] = $pageHtml;

    $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    $vars['Berechnungszeit'] = h(tf('footer_render_time', [
        'sekunden' => number_format(microtime(true) - $startTime, 4, '.', ''),
    ]));

    echo self::loadTemplateFile('layout.tpl.php', $vars);
}

}
