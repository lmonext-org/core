<?php
/**
 * Project: LMOnext
 * Filename: addon/viewer/lmo-viewer.php
 * Fileversion: 3.2.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── zwei Varianten (getrennt aufrufbar) ──────────────────────────────────────
 *
 * Variante 1 – Zeitraum (Standard-Template):
 *
 *   $vw_ligen = '3,7,12';
 *   $vw_von   = '01.03.2026';   // optional, Standard: heute-7
 *   $vw_bis   = '31.03.2026';   // optional, Standard: heute+7
 *   include(__DIR__.'/addon/viewer/lmo-viewer.php');
 *
 *   Per IFrame (ueber addon-run.php, siehe Hinweis unten):
 *   .../addon-run.php?addon=spieltag-viewer&file=lmo-viewer.php&vw_ligen=3,7&vw_von=01.03.2026&vw_bis=31.03.2026
 *
 * Variante 2 – Tages-Kacheln (Template "kacheln"):
 *
 *   $vw_ligen    = '3,7,12';
 *   $vw_template = 'kacheln';
 *   include(__DIR__.'/addon/viewer/lmo-viewer.php');
 *
 *   Per IFrame (ueber den zentralen Standalone-Controller, siehe
 *   addon-run.php im Projekt-Root - direkte Aufrufe der .php-Datei sind
 *   aus Sicherheitsgruenden per addon/.htaccess gesperrt):
 *   <iframe src=".../addon-run.php?addon=spieltag-viewer&file=lmo-viewer.php&vw_ligen=3,7&vw_template=kacheln" ...></iframe>
 *
 *   Zusatz-Parameter für Variante 2:
 *   vw_start  Startdatum der Kachel-Woche (Standard: aktueller Sonntag)
 *   vw_tag    Anfangs ausgewählter Tag (Standard: heute)
 *
 * Steuerparameter:
 *   vw_ligen       Liga-IDs, Komma-getrennt oder Array. Pflicht.
 *   vw_template    "standard" (default) oder "kacheln"
 *   vw_von/vw_bis  Nur Variante 1: Zeitraum
 *   vw_start/vw_tag Nur Variante 2: Fenster-Start / ausgewählter Tag
 */
declare(strict_types=1);

use LMOnext\Liga\LigaService;

$vwIsDirectCall = defined('LMO_ADDON_STANDALONE_CALL');
require_once __DIR__ . '/../../frontend/bootstrap.php';

// Standalone-Addon: eigene Sprachdateien explizit laden. Wichtig: der Name
// muss dem manifest['name'] aus addon.json entsprechen ("spieltag-viewer"),
// NICHT dem Ordnernamen "viewer" - AddonManager führt Addons intern über
// den Manifest-Namen (siehe AddonManager::discover()).
if (function_exists('addonManager')) {
    \addonManager()->loadLanguages('spieltag-viewer');
}

// Dieses Addon ist bewusst zum Einbetten via iframe auf fremden Websites
// gedacht (siehe Docblock oben) - die von frontend/bootstrap.php gesetzten
// Frame-Schutz-Header (X-Frame-Options/CSP frame-ancestors) werden hier
// deshalb wieder entfernt, sonst würde jede Einbettung blockiert.
if (!headers_sent()) {
    header_remove('X-Frame-Options');
    header_remove('Content-Security-Policy');
}

// ════════════════════════════════════════════════════════════════════════════
//  Funktionen
// ════════════════════════════════════════════════════════════════════════════

function vwProjectRootUrlPrefix() : string
{
    static $prefix = null;
    if ($prefix !== null) return $prefix;
    if (defined('LMO_ADDON_WEB_BASE')) {
        $prefix = rtrim(dirname(rtrim(LMO_ADDON_WEB_BASE, '/'), 2), '/') . '/';
        return $prefix;
    }
    $projectRootDisk = rtrim(str_replace('\\','/', dirname(__DIR__, 2)), '/');
    $scriptFilename  = str_replace('\\','/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName      = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptFilename !== '' && $scriptName !== '' && str_ends_with($scriptFilename, $scriptName)) {
        $documentRootDisk = rtrim(substr($scriptFilename, 0, -strlen($scriptName)), '/');
        if ($documentRootDisk !== '' && str_starts_with($projectRootDisk, $documentRootDisk)) {
            $prefix = substr($projectRootDisk, strlen($documentRootDisk)) . '/';
            return $prefix;
        }
    }
    $prefix = defined('LMO_ADDON_STANDALONE_CALL') ? '../../' : '';
    return $prefix;
}

function vwLogoImg(int $teamId, bool $showLogos) : string
{
    if (!$showLogos || $teamId <= 0) return '';
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
    return '<img src="' . h(vwProjectRootUrlPrefix() . $path) . '" alt="" class="team-logo-inline">';
}

function vwLoadTemplate(string $templateName) : array
{
    $templatePath = __DIR__ . '/templates/' . $templateName . '.tpl.php';
    if (!is_file($templatePath)) {
        $templatePath = __DIR__ . '/templates/standard.tpl.php';
    }
    $src = (string)file_get_contents($templatePath);

    $ligaTpl = '';
    if (preg_match('/<!-- BEGIN LIGA -->(.*?)<!-- END LIGA -->/s', $src, $m)) {
        $ligaTpl = $m[1];
        $src = preg_replace('/<!-- BEGIN LIGA -->.*?<!-- END LIGA -->/s', '{LIGA_BLOCKS}', $src, 1);
    }
    $spielTpl = '';
    if (preg_match('/<!-- BEGIN SPIEL -->(.*?)<!-- END SPIEL -->/s', $ligaTpl, $m)) {
        $spielTpl = $m[1];
        $ligaTpl = preg_replace('/<!-- BEGIN SPIEL -->.*?<!-- END SPIEL -->/s', '{SPIEL_ROWS}', $ligaTpl, 1);
    }
    return ['skeleton' => $src, 'ligaTpl' => $ligaTpl, 'spielTpl' => $spielTpl];
}

function vwGetLigaName(int $ligaId) : string
{
    try {
        $stmt = getDB()->prepare('SELECT name FROM ' . tbl('liga') . ' WHERE id = ?');
        $stmt->execute([$ligaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? (string)$row['name'] : ('Liga ' . $ligaId);
    } catch (\Throwable) { return 'Liga ' . $ligaId; }
}

function vwCollectPartienInRange(int $ligaId, \DateTime $von, \DateTime $bis) : array
{
    $allSpieltage = LigaService::getAllSpieltage($ligaId);
    $opts         = LigaService::getLigaOptions($ligaId);
    $showLogos    = ($opts['ShowLogos'] ?? '0') === '1';

    $stStartByNr = [];
    foreach ($allSpieltage as $st) {
        $stStartByNr[(int)$st['nummer']] = $st['start'] ?? null;
    }
    $partien = LigaService::getAllLigaPartien($allSpieltage);

    $result = [];
    foreach ($partien as $p) {
        $rawZeit = $p['zeit'] ?? null;
        if (empty($rawZeit)) {
            $rawZeit = $stStartByNr[(int)($p['_spieltag_nummer'] ?? 0)] ?? null;
        }
        if (empty($rawZeit)) continue;
        try { $dt = new \DateTime($rawZeit); } catch (\Throwable) { continue; }
        if ($dt < $von || $dt > $bis) continue;
        $p['_datum_ts']      = $dt->getTimestamp();
        $p['_datum_display'] = $dt->format('d.m.Y');
        $p['_zeit_display']  = $dt->format('H:i');
        $p['_show_logos']    = $showLogos;
        $result[] = $p;
    }
    usort($result, fn($a,$b) => $a['_datum_ts'] <=> $b['_datum_ts']);
    return $result;
}

function vwRenderSpiel(array $p, string $spielTpl) : string
{
    $showLogos = (bool)($p['_show_logos'] ?? false);
    $heimId   = (int)($p['heim_id'] ?? 0);
    $gastId   = (int)($p['gast_id'] ?? 0);
    $heimName = LigaService::partieTeamName($p, 'heim');
    $gastName = LigaService::partieTeamName($p, 'gast');
    $hTore = $p['h_tore'];
    $gTore = $p['g_tore'];
    $isPlayed = $hTore !== null && $gTore !== null;
    $ergebnis = $isPlayed ? h((string)$hTore . ':' . (string)$gTore) : '&ndash;:&ndash;';
    $zusatz = LigaService::statusSuffix($p);
    return strtr($spielTpl, [
        '{DATUM}'      => h($p['_datum_display']),
        '{ZEIT}'       => h($p['_zeit_display']),
        '{HEIM_LOGO}'  => vwLogoImg($heimId, $showLogos),
        '{HEIM}'       => h($heimName),
        '{GAST_LOGO}'  => vwLogoImg($gastId, $showLogos),
        '{GAST}'       => h($gastName),
        '{ERGEBNIS}'   => $ergebnis,
        '{ZUSATZ}'     => h($zusatz),
        '{SPIELTAG}'   => h((string)($p['_spieltag_nummer'] ?? '')),
    ]);
}

/**
 * Rendert die Liga-Blöcke (Tabellen) für einen beliebigen Zeitraum.
 */
function vwRenderMatches(array $ligenIds, \DateTime $von, \DateTime $bis, string $templateName) : string
{
    $tpl = vwLoadTemplate($templateName);
    $html = '';
    foreach ($ligenIds as $ligaId) {
        $partien = vwCollectPartienInRange($ligaId, $von, $bis);
        $ligaName = vwGetLigaName($ligaId);
        $rows = '';
        foreach ($partien as $p) {
            $rows .= vwRenderSpiel($p, $tpl['spielTpl']);
        }
        if ($rows === '') {
            $rows = '<tr class="vw-empty"><td colspan="5" style="text-align:center;color:#9098a8;padding:10px;font-style:italic">'
                  . h($von->format('Y-m-d') === $bis->format('Y-m-d') ? tf('vw_keine_spiele_tag') : tf('vw_keine_spiele_zeitraum'))
                  . '</td></tr>';
        }
        $block = $tpl['ligaTpl'];
        $block = str_replace('{LIGA_NAME}',  h($ligaName), $block);
        $block = str_replace('{LIGA_ID}',    h((string)$ligaId), $block);
        $block = str_replace('{SPIEL_ROWS}',  $rows, $block);
        $html .= $block;
    }
    return $html;
}

/**
 * Variante 1 – Zeitraum-Ansicht.
 */
function vwRenderRangePage(array $ligenIds, \DateTime $von, \DateTime $bis, string $templateName) : string
{
    $tpl = vwLoadTemplate($templateName);
    $ligaBlocks = vwRenderMatches($ligenIds, $von, $bis, $templateName);
    $skeleton = $tpl['skeleton'];
    $skeleton = str_replace('{VON}',        h($von->format('d.m.Y')), $skeleton);
    $skeleton = str_replace('{BIS}',        h($bis->format('d.m.Y')), $skeleton);
    $skeleton = str_replace('{LIGA_BLOCKS}', $ligaBlocks, $skeleton);
    $skeleton = str_replace('{COPYRIGHT}',   LigaService::renderCopyrightNotice('viewer'), $skeleton);
    return $skeleton;
}

/**
 * Variante 2 – Kachel-Ansicht mit 7 vorgeladenen Tagen.
 */
function vwRenderTilesPage(array $ligenIds, \DateTime $windowStart, \DateTime $selectedDay, string $templateName) : string
{
    $tpl = vwLoadTemplate($templateName);

    // Kacheln
    $today = new \DateTime('today');
    $dayNames = [
        tf('liga_weekday_so'), tf('liga_weekday_mo'), tf('liga_weekday_di'),
        tf('liga_weekday_mi'), tf('liga_weekday_do'), tf('liga_weekday_fr'),
        tf('liga_weekday_sa'),
    ];
    $tilesHtml = '';
    for ($i = 0; $i < 7; $i++) {
        $d = (clone $windowStart)->modify("+$i days");
        $isToday = $d == $today;
        $isSelected = $d == $selectedDay;
        $cls = 'vw-tile' . ($isToday ? ' vw-tile-today' : '') . ($isSelected ? ' vw-tile-selected' : '');
        $tilesHtml .= '<button class="' . $cls . '" data-date="' . $d->format('Y-m-d') . '">'
            . '<span class="vw-tile-day">' . $dayNames[(int)$d->format('w')] . '</span>'
            . '<span class="vw-tile-num">' . $d->format('j') . '</span>'
            . '<span class="vw-tile-month">' . $d->format('n.') . '</span>'
            . '</button>';
    }

    // 7 Tage vorgeladen
    $dayBlocksHtml = '';
    for ($i = 0; $i < 7; $i++) {
        $d = (clone $windowStart)->modify("+$i days");
        $dayStart = (clone $d)->setTime(0, 0, 0);
        $dayEnd   = (clone $d)->setTime(23, 59, 59);
        $matches  = vwRenderMatches($ligenIds, $dayStart, $dayEnd, $templateName);
        $isSel    = $d == $selectedDay;
        $dayBlocksHtml .= '<div class="vw-day-content" data-date="' . $d->format('Y-m-d') . '"'
            . ($isSel ? '' : ' style="display:none"') . '>' . $matches . '</div>';
    }

    // Pfeil-URLs
    $addonUrl   = vwProjectRootUrlPrefix() . 'addon/viewer/lmo-viewer.php';
    $ligenParam = implode(',', $ligenIds);
    $selDate   = $selectedDay->format('Y-m-d');
    $leftDate  = (clone $windowStart)->modify('-1 day')->format('Y-m-d');
    $rightDate = (clone $windowStart)->modify('+1 day')->format('Y-m-d');
    $baseQ = 'vw_ligen=' . urlencode($ligenParam) . '&vw_template=kacheln';
    $leftUrl  = h($addonUrl . '?' . $baseQ . '&vw_start=' . $leftDate . '&vw_tag=' . $selDate);
    $rightUrl = h($addonUrl . '?' . $baseQ . '&vw_start=' . $rightDate . '&vw_tag=' . $selDate);

    $skeleton = $tpl['skeleton'];
    $skeleton = str_replace('{LIGA_BLOCKS}', '', $skeleton); // Extraktions-Rest entfernen
    $skeleton = str_replace('{TILES}',       $tilesHtml, $skeleton);
    $skeleton = str_replace('{DAY_BLOCKS}',  $dayBlocksHtml, $skeleton);
    $skeleton = str_replace('{LEFT_URL}',    $leftUrl, $skeleton);
    $skeleton = str_replace('{RIGHT_URL}',   $rightUrl, $skeleton);
    $skeleton = str_replace('{COPYRIGHT}',   LigaService::renderCopyrightNotice('viewer'), $skeleton);
    return $skeleton;
}

// ════════════════════════════════════════════════════════════════════════════
//  Parameter & Modus-Auswahl
// ════════════════════════════════════════════════════════════════════════════

$vwLigenRaw   = $_REQUEST['vw_ligen'] ?? ($vw_ligen ?? '');
$vwLigenParts = is_array($vwLigenRaw) ? $vwLigenRaw : preg_split('/[,\s]+/', (string)$vwLigenRaw);
$vwLigenIds   = array_values(array_unique(array_filter(array_map('intval', $vwLigenParts), fn($v) => $v > 0)));

$vwTemplate = isset($_REQUEST['vw_template']) ? (string)$_REQUEST['vw_template'] : (string)($vw_template ?? 'standard');
$vwTemplate = str_replace('..', '', basename($vwTemplate));

if ($vwIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
        . '<title>' . h(tf('vw_titel')) . '</title>'
        . '<style>html,body{margin:0;padding:8px;background:transparent;}</style>'
        . '</head><body>' . "\n";
}

if (count($vwLigenIds) === 0) {
    echo '<p style="font-family:sans-serif;color:#697182;padding:12px">'
        . h(tf('vw_bitte_liga_id')) . '</p>';
    if ($vwIsDirectCall) echo "\n</body></html>";
    return;
}

if ($vwTemplate === 'kacheln') {
    // ── Variante 2: Kacheln ──
    $today = new \DateTime('today');
    $vwStartRaw = (string)($_REQUEST['vw_start'] ?? ($vw_start ?? ''));
    if ($vwStartRaw !== '') {
        try { $windowStart = new \DateTime($vwStartRaw); } catch (\Throwable) { $windowStart = clone $today; }
    } else {
        $windowStart = clone $today;
        $windowStart->modify('-' . (int)$today->format('w') . ' days');
    }
    $windowStart->setTime(0, 0, 0);

    $vwTagRaw = (string)($_REQUEST['vw_tag'] ?? ($vw_tag ?? ''));
    if ($vwTagRaw !== '') {
        try { $selectedDay = new \DateTime($vwTagRaw); } catch (\Throwable) { $selectedDay = clone $today; }
    } else {
        $selectedDay = clone $today;
    }
    $selectedDay->setTime(0, 0, 0);

    echo vwRenderTilesPage($vwLigenIds, $windowStart, $selectedDay, $vwTemplate);

} else {
    // ── Variante 1: Zeitraum ──
    $vwVonRaw = (string)($_REQUEST['vw_von'] ?? ($vw_von ?? ''));
    $vwBisRaw = (string)($_REQUEST['vw_bis'] ?? ($vw_bis ?? ''));
    try { $vwVon = $vwVonRaw !== '' ? new \DateTime($vwVonRaw) : (new \DateTime())->modify('-7 days'); } catch (\Throwable) { $vwVon = (new \DateTime())->modify('-7 days'); }
    $vwVon->setTime(0, 0, 0);
    try { $vwBis = $vwBisRaw !== '' ? new \DateTime($vwBisRaw) : (new \DateTime())->modify('+7 days'); } catch (\Throwable) { $vwBis = (new \DateTime())->modify('+7 days'); }
    $vwBis->setTime(23, 59, 59);

    echo vwRenderRangePage($vwLigenIds, $vwVon, $vwBis, $vwTemplate);
}

if ($vwIsDirectCall) echo "\n</body></html>";
