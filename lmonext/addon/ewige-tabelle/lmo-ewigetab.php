<?php
/**
 * Project: LMOnext
 * Filename: addon/ewige/lmo-ewigetab.php
 * Fileversion: 1.3.2
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── Einbindung ────────────────────────────────────────────────────────────────
 *
 * Variante 1 (empfohlen) – per include() aus eigenem PHP-Code:
 *
 *   <?php
 *   $ewige_ligas    = '3,5,7';     // Liga-IDs (Pflicht), Komma oder Array
 *   $ewige_view     = 'eternal';  // 'eternal' (Summe) oder 'verlauf' (Matrix)
 *   $ewige_template = 'standard'; // optional, Standard je nach view
 *   $wertung        = 'pkt2';    // optional, Standard 'pkt' (historische Punkte)
 *   include('/PfadZuLMOnext/addon/ewige/lmo-ewigetab.php');
 *
 * Variante 2 – per IFrame/direkter URL:
 *
 *   <iframe src="https://.../addon-run.php?addon=ewige-tabelle&file=lmo-ewigetab.php&ewige_ligas=3,5,7&ewige_view=eternal"
 *           frameborder="0" width="860" height="600" scrolling="auto"></iframe>
 *
 * Steuerparameter (GET hat immer Vorrang vor vorher gesetzten PHP-Variablen):
 *   ewige_ligas    Liga-IDs, Komma-getrennt (z.B. "3,5,7") oder Array. Pflicht.
 *   ewige_view     'eternal' = ewige Tabelle (Summe), 'verlauf' = Matrix pro
 *                  Saison. Standard: 'eternal'.
 *   ewige_template Template-Name ohne Endung (Standard je nach view:
 *                  eternal -> 'standard', verlauf -> 'matrix'), Datei muss
 *                  unter /addon/ewige/templates/{name}.tpl.php liegen.
 *   wertung        Nur für ewige_view='eternal': 'pkt' (historische Original-
 *                  Punkte, Standard), 'pkt2' (immer 2-Punkte-System) oder
 *                  'pkt3' (immer 3-Punkte-System) - bestimmt Sortierung/Rang.
 */
declare(strict_types=1);

// Wird diese Datei über den zentralen Controller addon-run.php aufgerufen
// (der einzige noch erlaubte Aufrufweg, siehe addon/.htaccess) oder per
// include() aus einer anderen Datei eingebunden? Nur im ersten Fall wird
// ein vollständiges HTML-Grundgerüst drumherum gebaut.
$ewigeIsDirectCall = defined('LMO_ADDON_STANDALONE_CALL');

require_once __DIR__ . '/../../frontend/bootstrap.php';

// Standalone-Addon: eigene Sprachdateien explizit laden. Name muss dem
// manifest['name'] aus addon.json entsprechen ("ewige-tabelle"), nicht dem
// Ordnernamen "ewige" - siehe AddonManager::discover().
if (function_exists('addonManager')) {
    \addonManager()->loadLanguages('ewige-tabelle');
}

// Dieses Addon ist bewusst zum Einbetten via iframe auf fremden Websites
// gedacht (siehe Docblock oben) - die von frontend/bootstrap.php gesetzten
// Frame-Schutz-Header (X-Frame-Options/CSP frame-ancestors) werden hier
// deshalb wieder entfernt, sonst würde jede Einbettung blockiert.
if (!headers_sent()) {
    header_remove('X-Frame-Options');
    header_remove('Content-Security-Policy');
}
require_once __DIR__ . '/../../frontend/data_liga.php';
require_once __DIR__ . '/../../src/Liga/Eternal/EternalTableService.php';

use LMOnext\Liga\Eternal\EternalTableService;

$service = new EternalTableService();
$leagues = $service->allLeagues();
/**
 * Berechnet das URL-Präfix zum Projekt-Root, egal ob diese Datei direkt
 * aufgerufen ODER per include() aus einer beliebig platzierten Wrapper-Datei
 * eingebunden wird. Siehe miniProjectRootUrlPrefix() in lmo-minitab.php –
 * gleiche Logik, eigener Funktionsname, damit beide Addons parallel
 * eingebunden werden können, ohne sich gegenseitig zu überschreiben.
 */
function ewigeProjectRootUrlPrefix() : string
{
    static $prefix = null;
    if ($prefix !== null) {
        return $prefix;
    }

    if (defined('LMO_ADDON_WEB_BASE')) {
        $prefix = rtrim(dirname(rtrim(LMO_ADDON_WEB_BASE, '/'), 2), '/') . '/';
        return $prefix;
    }

    $projectRootDisk = rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/');
    $scriptFilename  = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName      = (string)($_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptFilename !== '' && $scriptName !== '' && str_ends_with($scriptFilename, $scriptName)) {
        $documentRootDisk = substr($scriptFilename, 0, -strlen($scriptName));
        $documentRootDisk = rtrim($documentRootDisk, '/');
        if ($documentRootDisk !== '' && str_starts_with($projectRootDisk, $documentRootDisk)) {
            $prefix = substr($projectRootDisk, strlen($documentRootDisk)) . '/';
            return $prefix;
        }
    }

    $isDirectCall = defined('LMO_ADDON_STANDALONE_CALL');
    $prefix = $isDirectCall ? '../../' : '';
    return $prefix;
}

// ── Parameter einlesen (GET überschreibt vorher per include() gesetzte
// PHP-Variablen, die wiederum Vorrang vor den Standardwerten haben) ──────────
$e_ligasRaw = $_REQUEST['ewige_ligas'] ?? ($ewige_ligas ?? '');
$e_ligasParts = is_array($e_ligasRaw) ? $e_ligasRaw : preg_split('/[,\s]+/', (string)$e_ligasRaw);
$e_ligas   = array_values(array_unique(array_filter(array_map('intval', $e_ligasParts), fn ($v) => $v > 0)));
$e_view    = ($_REQUEST['ewige_view'] ?? ($ewige_view ?? 'eternal')) === 'verlauf' ? 'verlauf' : 'eternal';
$e_template = str_replace('..', '', basename((string)($_REQUEST['ewige_template'] ?? ($ewige_template ?? ''))));
if ($e_template === '') {
    $e_template = $e_view === 'verlauf' ? 'matrix' : 'standard';
}

if ($ewigeIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\"><title>" . h(tf('ewige_titel')) . "</title>"
        . "<style>html,body{margin:0;padding:0;background:transparent;}</style></head><body>\n";
}

/**
 * Team-Logo mit korrektem URL-Präfix (wie miniLogoImg in lmo-minitab.php,
 * aber für die ewige Tabelle). findTeamLogoPathFrontend() liefert Pfade
 * relativ zum Projekt-Root, dieses Addon liegt aber unter addon/ewige/.
 */
function ewigeLogoImg(int $teamId) : string
{
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
    return '<img src="' . h(ewigeProjectRootUrlPrefix() . $path) . '" alt="" style="height:18px;width:auto;vertical-align:middle">';
}

/**
 * Schneidet einen "<!-- BEGIN {name} -->...<!-- END {name} -->"-Block aus
 * $src heraus. Liefert den Block-Inhalt und setzt $before/$after auf die
 * Teile vor bzw. nach dem Block (wie in lmo-minitab.php).
 */
function ewige_extract_block(string $src, string $name, ?string &$before, ?string &$after) : string
{
    $pat = '/<!-- BEGIN ' . preg_quote($name, '/') . ' -->(.*?)<!-- END ' . preg_quote($name, '/') . ' -->/s';
    if (!preg_match($pat, $src, $m)) {
        $before = $src;
        $after  = '';
        return '';
    }
    $pos    = strpos($src, $m[0]);
    $before = substr($src, 0, $pos);
    $after  = substr($src, $pos + strlen($m[0]));
    return $m[1];
}

/**
 * Baut den HTML-Code der ewigen Tabelle bzw. des Mehrjahres-Vergleichs.
 *
 * @param int[]  $ligaIds      Liga-IDs (Reihenfolge = Spaltenreihenfolge)
 * @param string $view         'eternal' oder 'verlauf'
 * @param string $templateName Template-Name ohne Endung
 */
function renderEwigeTabelle(array $ligaIds, string $view, string $templateName): string
{
    if (empty($ligaIds)) {
        return '<p style="font-family:sans-serif;color:#a33">' . h(tf('ewige_param_fehlt')) . '</p>';
    }

    $service = new EternalTableService();

    // Template laden
    $defaultName = $view === 'verlauf' ? 'matrix' : 'standard';

    $candidates = [
        __DIR__ . '/templates/' . $templateName . '.tpl.php',
        __DIR__ . '/templates/' . $defaultName . '.tpl.php',
        __DIR__ . '/templates/ewige_' . $templateName . '.tpl.php',
        __DIR__ . '/templates/ewige_' . $defaultName . '.tpl.php',
        __DIR__ . '/../mini/ewige_' . $defaultName . '.tpl.php',
    ];

    $templatePath = '';
    foreach ($candidates as $c) {
        if (is_file($c)) {
            $templatePath = $c;
            break;
        }
    }

    if ($templatePath === '') {
        return '<p style="font-family:sans-serif;color:#a33">' . h(tf('ewige_vorlage_nicht_gefunden')) . '<br>'
            . implode('<br>', array_map('h', $candidates))
            . '</p>';
    }

    $templateSrc = (string)file_get_contents($templatePath);

    if ($templateSrc === '') {
        return '<p style="font-family:sans-serif;color:#a33">' . h(tf('ewige_vorlage_leer')) . ' '
            . h($templatePath)
            . '</p>';
    }

    if ($view === 'verlauf') {
        $data = $service->seasonMatrix($ligaIds);
        return ewige_render_matrix($templateSrc, $data, $ligaIds);
    }

    // gewünschte Wertung - wie bei ewige_ligas/ewige_view/ewige_template hat ein
    // GET/POST-Parameter Vorrang vor einer vorher per include() gesetzten
    // PHP-Variablen $wertung (siehe Docblock oben); Standard ist die klassische
    // "Ewige Tabelle" nach historischen Original-Punkten der jeweiligen Saison
    $sort = $_REQUEST['wertung'] ?? ($wertung ?? 'pkt');

    // nur erlaubte Werte
    if (!in_array($sort, ['pkt', 'pkt2', 'pkt3'], true)) {
        $sort = 'pkt';
    }

    $rows = $service->eternalStandings($ligaIds);

    // gewünschte Tabelle sortieren
    usort($rows, static function (array $a, array $b) use ($sort): int {

        if ($a[$sort] !== $b[$sort]) {
            return $b[$sort] <=> $a[$sort];
        }

        $diffA = $a['tore_h'] - $a['tore_g'];
        $diffB = $b['tore_h'] - $b['tore_g'];

        if ($diffA !== $diffB) {
            return $diffB <=> $diffA;
        }

        if ($a['tore_h'] !== $b['tore_h']) {
            return $b['tore_h'] <=> $a['tore_h'];
        }

        return strcmp($a['name'], $b['name']);
    });

    // Rang nach neuer Sortierung vergeben
    foreach ($rows as $i => &$r) {
        $r['rang'] = $i + 1;
    }
    unset($r);

    return ewige_render_eternal($templateSrc, $rows, $ligaIds);
}

/** Ewige Tabelle (Summenansicht) über standard.tpl.php. */
function ewige_render_eternal(string $templateSrc, array $rows, array $ligaIds) : string
{
    $rowTemplate = ewige_extract_block($templateSrc, 'Inhalt', $before, $after);
    if ($rowTemplate === '') {
        return '<p style="font-family:sans-serif;color:#a33">' . h(tf('ewige_kein_inhalt_block')) . '</p>';
    }

    // Fußnoten-Nummern für Teams mit hinterlegtem(n) Strafgrund/Strafgründen
    // vergeben (Tabellenreihenfolge, wie bei der normalen Liga-Tabelle - siehe
    // assignStrafFootnotes() in src/Liga/StandingsTrait.php). Ein Grund allein
    // reicht, unabhängig davon, ob eine der vier Korrekturen von 0 abweicht.
    $footnoteNrs = [];
    $next = 1;
    foreach ($rows as $r) {
        if (!empty($r['strafgruende'])) {
            $footnoteNrs[(int)$r['id']] = $next++;
        }
    }

    $rowsHtml = '';
    foreach ($rows as $r) {
        $diff = (int)$r['tore_h'] - (int)$r['tore_g'];
        // Team-Verknüpfungen (Umbenennung/Fusion/Abspaltung, siehe
        // EternalTableService::resolveTeamLinkGroups() im Core): das
        // Kurz-/Langname-Feld liefert bewusst nur den aktuellen Namen,
        // ohne Zusatz - former_names enthält die Namen, unter denen
        // dieselbe (verknüpfte) Mannschaft in den ausgewählten Ligen
        // sonst noch spielte. Die "(ehem. ...)"-Darstellung baut dieses
        // Addon selbst, damit sie mit dem eigenen Sprachschlüssel
        // (ewige_ehemals) übersetzbar bleibt.
        $ewigeFormerSuffix = '';
        if (!empty($r['former_names'])) {
            $ewigeFormerSuffix = ' <span class="ewige-ehemals">('
                . h(tf('ewige_ehemals', ['names' => implode(', ', $r['former_names'])]))
                . ')</span>';
        }
        $rowsHtml .= strtr($rowTemplate, [
            '<!--Platz-->'         => (string)$r['rang'],
            '<!--Logo-->'          => ewigeLogoImg((int)$r['id']),
            '<!--Team-->'          => h($r['kurz'] !== '' ? $r['kurz'] : $r['name']) . $ewigeFormerSuffix,
            '<!--TeamLang-->'      => h($r['name']) . $ewigeFormerSuffix,
            // BUGFIX (gemeldet: Tabelle zeigte kaputtes HTML wie
            // '(ewige_ehemals)">Name' statt der Zeile): <!--TeamLang-->
            // wird im Standard-Template ZWEIMAL verwendet - einmal im
            // title-Attribut (Tooltip), einmal als sichtbarer Text. Der
            // HTML-Zusatz ($ewigeFormerSuffix, u.a. ein <span>-Tag mit
            // Anführungszeichen im class-Attribut) landete dadurch auch
            // IM title-Attribut und brach es vorzeitig ab, was den
            // Browser den Rest als rohen, kaputten Text anzeigen ließ.
            // Eigener Platzhalter für den Attribut-Kontext: reiner Text,
            // ohne jedes HTML.
            '<!--TeamLangTitel-->' => h($r['name']),
            '<!--StrafHinweis-->'  => ewigeStrafHinweis($r, $footnoteNrs[(int)$r['id']] ?? 0),
            '<!--Saisons-->'       => (string)$r['saisons'],
            '<!--Spiele-->'        => (string)$r['sp'],
            '<!--Siege-->'         => (string)$r['s'],
            '<!--Unentschieden-->' => (string)$r['u'],
            '<!--Niederlagen-->'   => (string)$r['n'],
            '<!--PlusTore-->'      => (string)$r['tore_h'],
            '<!--MinusTore-->'     => (string)$r['tore_g'],
            '<!--Tordifferenz-->'  => h(($diff > 0 ? '+' : '') . $diff),
            '<!--Punkte-->'       => (string)$r['pkt'],     // historisch
            '<!--Punkte2-->'      => (string)$r['pkt2'],    // immer 2 Punkte
            '<!--Minuspunkte2-->' => (string)$r['mpkt2'],
            '<!--Punkte3-->'      => (string)$r['pkt3'],    // immer 3 Punkte
            '<!--Minuspunkte3-->' => (string)$r['mpkt3'],
            '<!--Style-->'         => '',
            '<!--Class-->'         => '',
        ]);
    }

    // Spaltenüberschrift der Tore-Spalte: "Alternative für Tore" (nameTor,
    // z.B. "Holz" bei Kegeln) der ersten ausgewählten Liga, die einen Wert hat.
    $toreLabel = 'Tore';
    foreach ($ligaIds as $lid) {
        try {
            $o = getLigaOptions((int)$lid);
        } catch (\Throwable) {
            $o = [];
        }
        $n = trim((string)($o['nameTor'] ?? ''));
        if ($n !== '') {
            $toreLabel = $n;
            break;
        }
    }

    $outer = [
        '<!--ToreLabel-->' => h($toreLabel),
        '<!--Tabelle-->'  => h(tf('ewige_titel')),
        '<!--Fusszeile-->' => h(tf('ewige_fusszeile', ['teams' => count($rows), 'ligen' => count($ligaIds)])),
        '<!--Fussnoten-->' => ewigeStrafFootnotes($rows, $footnoteNrs),
        '<!--Copyright-->' => \LMOnext\Liga\LigaService::renderCopyrightNotice('ewige'),
    ];
    $before = strtr($before, $outer);
    $after  = strtr($after, $outer);

    return $before . $rowsHtml . $after;
}

/**
 * Straf-Hinweis für eine Zeile der Ewigen Tabelle (Beitrag: Torsten Hofmann,
 * hier an den Stil/die Sprachkeys der normalen Tabelle angepasst - siehe
 * renderStrafHinweis() in src/Liga/StandingsTrait.php). Zeigt bei
 * hinterlegtem(n) Grund eine anklickbare Fußnoten-Nummer, sonst (nur
 * Zahlenkorrektur ohne Begründung) nur ein ⚠-Symbol mit Tooltip.
 */
function ewigeStrafHinweis(array $r, int $fnNr = 0) : string
{
    $sp = (int)($r['strafpunkte'] ?? 0);
    $st = (int)($r['straftore'] ?? 0);
    $tk = (int)($r['torekorrektur'] ?? 0);
    $mk = (int)($r['minuspunktekorrektur'] ?? 0);
    $gruende = $r['strafgruende'] ?? [];
    if ($sp === 0 && $st === 0 && $tk === 0 && $mk === 0 && empty($gruende)) {
        return '';
    }
    $teile = [];
    if ($sp !== 0) { $teile[] = ($sp > 0 ? '+' : '') . $sp . ' ' . tf('liga_standings_col_pkt'); }
    if ($tk !== 0) { $teile[] = ($tk > 0 ? '+' : '') . $tk . ' ' . tf('liga_standings_straf_erzielt'); }
    if ($st !== 0) { $teile[] = ($st > 0 ? '+' : '') . $st . ' ' . tf('liga_standings_straf_gegentore'); }
    if ($mk !== 0) { $teile[] = ($mk > 0 ? '+' : '') . $mk . ' ' . tf('liga_standings_straf_minuspunkte'); }
    $tip = implode(', ', $teile);
    if (!empty($gruende)) {
        $tip .= ' (' . implode('; ', $gruende) . ')'; // NICHT hier schon h() aufrufen - passiert unten einmalig
    }
    if (!empty($gruende) && $fnNr > 0) {
        return ' <sup class="st-straf-hinweis" title="' . h($tip) . '" id="etstraf-ref-' . $fnNr . '">'
             . '<a href="#etstraf-' . $fnNr . '">(' . $fnNr . ')</a></sup>';
    }
    return ' <span class="st-straf-hinweis" title="' . h($tip) . '">⚠</span>';
}

/**
 * Fußnoten-Liste unter der Ewigen Tabelle, im Wikipedia-Stil - analog zu
 * renderStrafFootnotes() in src/Liga/StandingsTrait.php, aber mit den ggf.
 * mehreren Gründen über verschiedene Saisons hinweg (jeweils
 * "Liganame: Grund", siehe eternalStandings()).
 */
function ewigeStrafFootnotes(array $rows, array $footnoteNrs) : string
{
    if (empty($footnoteNrs)) {
        return '';
    }
    $byId = [];
    foreach ($rows as $r) {
        $byId[(int)$r['id']] = $r;
    }
    $items = '';
    foreach ($footnoteNrs as $teamId => $nr) {
        $gruende = $byId[$teamId]['strafgruende'] ?? [];
        $items .= '<p id="etstraf-' . $nr . '" class="st-footnote-item">'
                . '<a href="#etstraf-ref-' . $nr . '">(' . $nr . ')</a> ' . h(implode('; ', $gruende)) . '</p>';
    }
    return '<div class="st-footnotes">' . $items . '</div>';
}

/** Mehrjahres-Vergleich (Matrix) über matrix.tpl.php. */
function ewige_render_matrix(string $templateSrc, array $m, array $ligaIds) : string
{
    // Zeilen-Block (TeamZeile) herausschneiden; $before enthält den Kopf
    // mit dem Spalten-Block (Spalte).
    $rowTemplate = ewige_extract_block($templateSrc, 'TeamZeile', $before, $after);
    if ($rowTemplate === '') {
        return '<p style="font-family:sans-serif;color:#a33">' . h(tf('ewige_kein_teamzeile_block')) . '</p>';
    }
    $colTemplate = ewige_extract_block($before, 'Spalte', $colBefore, $colAfter);

    // Saison-Spalten (Kopf)
    $colsHtml = '';
    foreach ($m['seasons'] as $lid => $sname) {
        $colsHtml .= strtr($colTemplate, [
            '<!--SaisonName-->'  => h($sname),
            '<!--SaisonTitel-->' => h($sname),
        ]);
    }
    $before = $colBefore . $colsHtml . $colAfter;

    // Zellen-Block liegt innerhalb der Team-Zeile.
    $cellTemplate = ewige_extract_block($rowTemplate, 'Zelle', $rowBefore, $rowAfter);

    $rowsHtml = '';
    foreach ($m['teams'] as $tid => $tname) {
        $cellsHtml = '';
        foreach ($m['seasons'] as $lid => $_) {
            $cell = $m['matrix'][$tid][$lid] ?? null;
            if ($cell !== null) {
                $cellsHtml .= strtr($cellTemplate, [
                    '<!--ZelleKlasse-->'  => '',
                    '<!--ZelleInhalt-->'  => '<strong>#' . (int)$cell['rang'] . '</strong> · ' . (int)$cell['pkt'] . ' P.',
                ]);
            } else {
                $cellsHtml .= strtr($cellTemplate, [
                    '<!--ZelleKlasse-->'  => 'lmo-leer',
                    '<!--ZelleInhalt-->'  => '–',
                ]);
            }
        }
        $rowsHtml .= strtr($rowBefore . $cellsHtml . $rowAfter, [
            '<!--TeamName-->' => h($tname),
        ]);
    }

    $outer = [
        '<!--Tabelle-->'  => h(tf('ewige_matrix_titel')),
        '<!--Fusszeile-->' => tf('ewige_matrix_fusszeile'),
        '<!--Copyright-->' => \LMOnext\Liga\LigaService::renderCopyrightNotice('ewige'),
    ];
    $before = strtr($before, $outer);
    $after  = strtr($after, $outer);

    return $before . $rowsHtml . $after;
}

echo renderEwigeTabelle($e_ligas, $e_view, $e_template);

if ($ewigeIsDirectCall) {
    echo "\n</body></html>\n";
}
