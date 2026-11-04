<?php
/**
 * Project: LMOnext
 * Filename: addon/mini/lmo-mininext.php
 * Fileversion: 1.1.1
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
 *   $mini_liga     = 3;          // Liga-ID, in der nach dem nächsten Spiel gesucht wird (Pflicht)
 *   $mini_a        = null;       // Team-ID von Team A; leer = Lieblingsmannschaft der Liga
 *   $mini_b        = null;       // Team-ID von Team B; leer = automatisch ermittelter nächster Gegner
 *   $mini_template = 'mininext'; // Template aus /addon/mini/templates/{name}.tpl.php
 *   include('/PfadZuLMOnext/addon/mini/lmo-mininext.php');
 *
 * Variante 2 – per IFrame/direkter URL (z.B. wenn kein PHP auf dem externen
 * Server verfügbar ist):
 *
 *   <iframe src="https://.../addon/mini/lmo-mininext.php?mini_liga=3"
 *           frameborder="0" width="220" height="420" scrolling="no"></iframe>
 *
 * Steuerparameter (GET hat immer Vorrang vor vorher gesetzten PHP-Variablen):
 *   mini_liga      Liga-ID, in der nach dem nächsten/letzten Spiel gesucht wird (Pflicht)
 *   mini_a         Team-ID von Team A. Leer = die in der Liga hinterlegte
 *                  Lieblingsmannschaft (Liga-Einstellungen → Grundwerte)
 *   mini_b         Team-ID von Team B (fester Gegner). Leer = wird automatisch aus
 *                  dem gefundenen nächsten/letzten Spiel von Team A ermittelt
 *   mini_template  Name der Template-Datei ohne Endung (Standard: "mininext"),
 *                  Datei muss unter /addon/mini/templates/{name}.tpl.php liegen
 */
declare(strict_types=1);

$miniIsDirectCall = defined('LMO_ADDON_STANDALONE_CALL');

require_once __DIR__ . '/../../frontend/bootstrap.php';

// Standalone-Addon: eigene Sprachdateien explizit laden.
if (function_exists('addonManager')) {
    \addonManager()->loadLanguages('mini-tabelle');
}

// Dieses Addon ist bewusst zum Einbetten via iframe auf fremden Websites
// gedacht (siehe Docblock oben) - die von frontend/bootstrap.php gesetzten
// Frame-Schutz-Header (X-Frame-Options/CSP frame-ancestors) werden hier
// deshalb wieder entfernt, sonst würde jede Einbettung blockiert.
if (!headers_sent()) {
    header_remove('X-Frame-Options');
    header_remove('Content-Security-Policy');
}

/**
 * Berechnet das URL-Präfix zum Projekt-Root, egal ob diese Datei direkt
 * aufgerufen ODER per include() aus einer beliebig platzierten Wrapper-Datei
 * eingebunden wird. Siehe ausführlichen Kommentar in lmo-minitab.php.
 */
function miniProjectRootUrlPrefix() : string
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
$m_liga     = isset($_GET['mini_liga']) ? (int)$_GET['mini_liga'] : (int)($mini_liga ?? 0);
$m_a        = isset($_GET['mini_a']) && $_GET['mini_a'] !== '' ? (int)$_GET['mini_a'] : (isset($mini_a) && $mini_a !== null && $mini_a !== '' ? (int)$mini_a : null);
$m_b        = isset($_GET['mini_b']) && $_GET['mini_b'] !== '' ? (int)$_GET['mini_b'] : (isset($mini_b) && $mini_b !== null && $mini_b !== '' ? (int)$mini_b : null);
$m_template = isset($_GET['mini_template']) ? (string)$_GET['mini_template'] : (string)($mini_template ?? 'mininext');
$m_template = str_replace('..', '', basename($m_template)); // Path-Traversal-Schutz

if ($miniIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\"><title>Mininext</title>"
        . "<style>html,body{margin:0;padding:0;background:transparent;}</style></head><body>\n";
}

/**
 * renderTeamLogoImg()/findTeamLogoPathFrontend() liefern Pfade relativ zum
 * Projekt-Root, korrekt für Seiten wie liga.php/home.php, die selbst dort
 * liegen. Dieses Addon liegt aber unter addon/mini/ (zwei Ebenen tiefer),
 * wodurch der Browser denselben relativen Pfad fälschlich relativ zu
 * addon/mini/ aufgelöst hätte. Eigene, lokal korrigierte Variante mit
 * "../../"-Präfix.
 */
function miniLogoImg(int $teamId) : string
{
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
    return '<img src="' . h(miniProjectRootUrlPrefix() . $path) . '" alt="" class="team-logo-inline">';
}

/**
 * Sucht für ein Team innerhalb einer Liga das nächste anstehende Spiel
 * (Anstoßzeit in der Zukunft) sowie – falls vorhanden – das unmittelbar
 * davor gespielte Spiel. Gibt es kein zukünftiges Spiel mehr (Saison
 * beendet), wird stattdessen das letzte Spiel als "aktuell" geliefert, ohne
 * separates "vorheriges Spiel" (identisch zum Verhalten des alten LMO).
 *
 * @return array{current:?array,previous:?array,isFuture:bool}
 */
function findMininextGames(int $ligaId, int $teamId) : array
{
    try {
        $s = getDB()->prepare(
            'SELECT p.heim_id, p.gast_id, p.h_tore, p.g_tore, p.status, p.notiz,
                    COALESCE(p.zeit, s.start) AS zeit
               FROM ' . tbl('liga_partien') . ' p
               JOIN ' . tbl('liga_spieltage') . ' s ON s.id = p.spieltag_id
              WHERE s.liga_id = ? AND (p.heim_id = ? OR p.gast_id = ?)
              ORDER BY COALESCE(p.zeit, s.start) ASC'
        );
        $s->execute([$ligaId, $teamId, $teamId]);
        $rows = $s->fetchAll();
    } catch (Throwable) {
        $rows = [];
    }

    $now      = time();
    $current  = null;
    $previous = null;
    $isFuture = false;
    foreach ($rows as $r) {
        $ts = $r['zeit'] !== null ? strtotime((string)$r['zeit']) : false;
        if ($ts !== false && $ts > $now) {
            $current  = $r;
            $isFuture = true;
            break;
        }
        $previous = $r; // letztes bisher durchlaufenes vergangenes Spiel merken
    }
    if ($current === null) {
        // Kein zukünftiges Spiel gefunden -> Saison beendet, letztes Spiel zeigen
        $current  = $previous;
        $previous = null;
        $isFuture = false;
    }

    return ['current' => $current, 'previous' => $previous, 'isFuture' => $isFuture];
}

/**
 * Sucht die (erste) Begegnung zweier fest vorgegebener Teams innerhalb einer
 * Liga (für den Fall, dass sowohl mini_a als auch mini_b angegeben wurden).
 */
function findMatchBetweenTeamsInLiga(int $ligaId, int $teamAId, int $teamBId) : ?array
{
    try {
        $s = getDB()->prepare(
            'SELECT p.heim_id, p.gast_id, p.h_tore, p.g_tore, p.status, p.notiz,
                    COALESCE(p.zeit, s.start) AS zeit
               FROM ' . tbl('liga_partien') . ' p
               JOIN ' . tbl('liga_spieltage') . ' s ON s.id = p.spieltag_id
              WHERE s.liga_id = ? AND ((p.heim_id = ? AND p.gast_id = ?) OR (p.heim_id = ? AND p.gast_id = ?))
              ORDER BY COALESCE(p.zeit, s.start) ASC
              LIMIT 1'
        );
        $s->execute([$ligaId, $teamAId, $teamBId, $teamBId, $teamAId]);
        $row = $s->fetch();
        return $row !== false ? $row : null;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Baut den HTML-Code des Mininext-Widgets.
 */
function renderMininext(int $ligaId, ?int $teamAId, ?int $teamBId, string $templateName) : string
{
    $opts = getLigaOptions($ligaId);
    if (empty($opts) && getLigaTeamsList($ligaId) === []) {
        return '<p style="font-family:sans-serif;color:#a33">Liga nicht gefunden (mini_liga=' . h($ligaId) . ')</p>';
    }

    try {
        $ligaDatumRaw = getDB()->prepare('SELECT datum FROM ' . tbl('liga') . ' WHERE id = ?');
        $ligaDatumRaw->execute([$ligaId]);
        $ligaDatumVal = $ligaDatumRaw->fetchColumn();
    } catch (Throwable) {
        $ligaDatumVal = false;
    }
    $ligaTs = $ligaDatumVal !== false ? strtotime((string)$ligaDatumVal) : false;
    $ligaDatumText = $ligaTs !== false ? date('d.m.Y', $ligaTs) : date('d.m.Y');

    if ($teamAId === null) {
        $teamAId = resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));
    }
    if ($teamAId === null) {
        return '<p style="font-family:sans-serif;color:#a33">Kein Team A angegeben und keine Lieblingsmannschaft für diese Liga hinterlegt</p>';
    }

    $teamsById = [];
    foreach (getLigaTeamsList($ligaId) as $t) {
        $teamsById[(int)$t['id']] = $t;
    }
    if (!isset($teamsById[$teamAId])) {
        return '<p style="font-family:sans-serif;color:#a33">Team A (ID ' . h($teamAId) . ') spielt nicht in dieser Liga</p>';
    }

    $previous = null;
    $isFuture = false;
    if ($teamBId !== null) {
        // Beide Teams fest vorgegeben -> genau diese Begegnung anzeigen
        $current = findMatchBetweenTeamsInLiga($ligaId, $teamAId, $teamBId);
        if ($current === null) {
            return '<p style="font-family:sans-serif;color:#a33">Keine Begegnung zwischen diesen beiden Teams in dieser Liga gefunden</p>';
        }
        $ts = $current['zeit'] !== null ? strtotime((string)$current['zeit']) : false;
        $isFuture = $ts !== false && $ts > time();
    } else {
        $found = findMininextGames($ligaId, $teamAId);
        if ($found['current'] === null) {
            return '<p style="font-family:sans-serif;color:#a33">Keine Spiele für dieses Team in dieser Liga gefunden</p>';
        }
        $current  = $found['current'];
        $previous = $found['previous'];
        $isFuture = $found['isFuture'];
        $teamBId  = (int)$current['heim_id'] === $teamAId ? (int)$current['gast_id'] : (int)$current['heim_id'];
    }

    if (!isset($teamsById[$teamBId])) {
        // Team B kommt evtl. nicht (mehr) in der aktuellen Teamliste der Liga vor
        // (z.B. abgemeldet) - Name trotzdem über teams_global auflösen.
        try {
            $s = getDB()->prepare('SELECT id, name, kurz, mittel FROM ' . tbl('teams_global') . ' WHERE id = ?');
            $s->execute([$teamBId]);
            $row = $s->fetch();
            if ($row !== false) {
                $teamsById[$teamBId] = $row;
            }
        } catch (Throwable) {
            // bleibt unaufgelöst -> Fallback "?" beim Rendern
        }
    }

    $nameOf = static fn(int $id, string $field) : string => isset($teamsById[$id]) ? (string)($teamsById[$id][$field] ?? '') : '?';

    // ── Template laden ───────────────────────────────────────────────────────
    $templatePath = __DIR__ . '/templates/' . $templateName . '.tpl.php';
    if (!is_file($templatePath)) {
        $templatePath = __DIR__ . '/templates/mininext.tpl.php';
    }
    $templateSrc = (string)file_get_contents($templatePath);

    $currentHeimId = (int)$current['heim_id'];
    $currentGastId = (int)$current['gast_id'];
    $gespielt      = $current['h_tore'] !== null && $current['g_tore'] !== null;

    $zeitTs = $current['zeit'] !== null ? strtotime((string)$current['zeit']) : false;
    $countdownText = '';
    if ($isFuture && $zeitTs !== false) {
        $diff    = $zeitTs - time();
        $days    = intdiv($diff, 86400);
        $hours   = intdiv($diff % 86400, 3600);
        $minutes = intdiv($diff % 3600, 60);
        $countdownText = tf('mini_next_countdown', ['d' => $days, 'h' => $hours, 'm' => $minutes]);
    }

    $outer = [
        '<!--gameTxt-->'    => h($isFuture ? tf('mini_next_upcoming') : tf('mini_next_season_over')),
        '<!--gameDate-->'   => $zeitTs !== false ? h(date('d.m.Y', $zeitTs)) : '',
        '<!--gameTime-->'   => $zeitTs !== false ? h(date('H:i', $zeitTs)) : '',
        '<!--countDown-->'  => h($countdownText),
        '<!--gameNote-->'   => h((string)($current['notiz'] ?? '')),
        '<!--Copyright-->'  => \LMOnext\Liga\LigaService::renderCopyrightNotice('mininext'),
        '<!--ligaDatum-->'  => h(tf('liga_stand_datum', ['datum' => $ligaDatumText])),
        '<!--homeName-->'        => h($nameOf($currentHeimId, 'name')),
        '<!--homeNameMiddle-->'  => h($nameOf($currentHeimId, 'mittel')),
        '<!--homeNameShort-->'   => h($nameOf($currentHeimId, 'kurz')),
        '<!--guestName-->'       => h($nameOf($currentGastId, 'name')),
        '<!--guestNameMiddle-->' => h($nameOf($currentGastId, 'mittel')),
        '<!--guestNameShort-->'  => h($nameOf($currentGastId, 'kurz')),
        '<!--homeTore-->'  => $gespielt ? h((string)$current['h_tore']) : '-',
        '<!--guestTore-->' => $gespielt ? h((string)$current['g_tore']) : '-',
        '<!--imgHomeBig-->'    => miniLogoImg($currentHeimId),
        '<!--imgHomeSmall-->'  => miniLogoImg($currentHeimId),
        '<!--imgGuestBig-->'   => miniLogoImg($currentGastId),
        '<!--imgGuestSmall-->' => miniLogoImg($currentGastId),
    ];

    // ── "Vorheriges Spiel"-Block (optional, siehe <!-- BEGIN/END previous --> im Template) ──
    if (preg_match('/<!-- BEGIN previous -->(.*?)<!-- END previous -->/s', $templateSrc, $mPrev)) {
        $prevBlockSrc = $mPrev[1];
        $prevBlockHtml = '';
        if ($previous !== null) {
            $pHeimId = (int)$previous['heim_id'];
            $pGastId = (int)$previous['gast_id'];
            $pTs     = $previous['zeit'] !== null ? strtotime((string)$previous['zeit']) : false;
            $prevRepl = [
                '<!--previous_gameTxt-->'  => h(tf('mini_next_previous')),
                '<!--previous_gameDate-->' => $pTs !== false ? h(date('d.m.Y', $pTs)) : '',
                '<!--previous_gameTime-->' => $pTs !== false ? h(date('H:i', $pTs)) : '',
                '<!--previous_homeName-->'       => h($nameOf($pHeimId, 'name')),
                '<!--previous_homeNameMiddle-->' => h($nameOf($pHeimId, 'mittel')),
                '<!--previous_homeNameShort-->'  => h($nameOf($pHeimId, 'kurz')),
                '<!--previous_guestName-->'       => h($nameOf($pGastId, 'name')),
                '<!--previous_guestNameMiddle-->' => h($nameOf($pGastId, 'mittel')),
                '<!--previous_guestNameShort-->'  => h($nameOf($pGastId, 'kurz')),
                '<!--previous_hTore-->' => $previous['h_tore'] !== null ? h((string)$previous['h_tore']) : '-',
                '<!--previous_gTore-->' => $previous['g_tore'] !== null ? h((string)$previous['g_tore']) : '-',
                '<!--previous_imgHomeSmall-->'  => miniLogoImg($pHeimId),
                '<!--previous_imgHomeBig-->'    => miniLogoImg($pHeimId),
                '<!--previous_imgGuestSmall-->' => miniLogoImg($pGastId),
                '<!--previous_imgGuestBig-->'   => miniLogoImg($pGastId),
            ];
            $prevBlockHtml = strtr($prevBlockSrc, $prevRepl);
        }
        $templateSrc = substr($templateSrc, 0, strpos($templateSrc, $mPrev[0]))
            . $prevBlockHtml
            . substr($templateSrc, strpos($templateSrc, $mPrev[0]) + strlen($mPrev[0]));
    }

    // ── Bilanz-/Verlaufsliste aller bisherigen Begegnungen (über ALLE Ligen
    // hinweg, ersetzt die alte Archivordner-Suche) ───────────────────────────
    $history = getHeadToHeadMatches($teamAId, $teamBId);
    $winCount = $drawCount = $lostCount = 0;
    $matchesRowsHtml = '';

    if (preg_match('/<!-- BEGIN matches -->(.*?)<!-- END matches -->/s', $templateSrc, $mRows)) {
        $rowTemplate = $mRows[1];
        foreach ($history as $m) {
            $mHeimId = (int)$m['heim_id'];
            $isHome  = $mHeimId === $teamAId;
            $aTore   = $isHome ? $m['h_tore'] : $m['g_tore'];
            $bTore   = $isHome ? $m['g_tore'] : $m['h_tore'];
            if ($aTore > $bTore) {
                $cls = 'win';
                $winCount++;
            } elseif ($aTore < $bTore) {
                $cls = 'lost';
                $lostCount++;
            } else {
                $cls = 'draw';
                $drawCount++;
            }
            $mTs = $m['zeit'] !== null ? strtotime((string)$m['zeit']) : false;
            $rowRepl = [
                '<!--class-->' => $cls,
                '<!--date-->'  => $mTs !== false ? h(date('d.m.Y', $mTs)) : '',
                '<!--time-->'  => $mTs !== false ? h(date('H:i', $mTs)) : '',
                '<!--hTore-->' => h((string)$aTore),
                '<!--gTore-->' => h((string)$bTore),
                '<!--where-->' => h($isHome ? tf('mini_next_home') : tf('mini_next_away')),
                '<!--matchingName-->' => '',
            ];
            $matchesRowsHtml .= strtr($rowTemplate, $rowRepl);
        }
        $templateSrc = substr($templateSrc, 0, strpos($templateSrc, $mRows[0]))
            . $matchesRowsHtml
            . substr($templateSrc, strpos($templateSrc, $mRows[0]) + strlen($mRows[0]));
    }

    $spAnzahl = count($history);
    $barWidth = 120;
    $outer['<!--matchesTxt-->'] = h(tf('mini_next_matches_heading'));
    $outer['<!--winCount-->']   = (string)$winCount;
    $outer['<!--drawCount-->']  = (string)$drawCount;
    $outer['<!--lostCount-->']  = (string)$lostCount;
    $outer['<!--matchCount-->'] = (string)$spAnzahl;
    $outer['<!--winTxt-->']     = h(tf('mini_next_win_short'));
    $outer['<!--drawTxt-->']    = h(tf('mini_next_draw_short'));
    $outer['<!--lostTxt-->']    = h(tf('mini_next_lost_short'));
    $outer['<!--winWidth-->']   = (string)(int)($barWidth * $winCount  / ($spAnzahl + .1));
    $outer['<!--drawWidth-->']  = (string)(int)($barWidth * $drawCount / ($spAnzahl + .1));
    $outer['<!--lostWidth-->']  = (string)(int)($barWidth * $lostCount / ($spAnzahl + .1));

    return strtr($templateSrc, $outer);
}

if ($m_liga <= 0) {
    echo '<p style="font-family:sans-serif;color:#a33">Fehlender oder ungültiger Parameter "mini_liga"</p>';
} else {
    echo renderMininext($m_liga, $m_a, $m_b, $m_template);
}

if ($miniIsDirectCall) {
    echo "\n</body></html>\n";
}
