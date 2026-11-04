<?php
/**
 * Project: LMOnext
 * Filename: addon/mini/lmo-minitab.php
 * Fileversion: 1.3.1
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
 *   $mini_liga     = 3;        // Liga-ID (Pflicht)
 *   $mini_platz    = null;     // Zentrum des Ausschnitts; leer = Lieblingsmannschaft
 *   $mini_ueber    = 3;        // Plätze über dem Zentrum
 *   $mini_unter    = 2;        // Plätze unter dem Zentrum
 *   $mini_template = 'standard'; // Template aus /addon/mini/templates/{name}.tpl.php
 *   include('/PfadZuLMOnext/addon/mini/lmo-minitab.php');
 *
 * Variante 2 – per IFrame/direkter URL (z.B. wenn kein PHP auf dem externen
 * Server verfügbar ist):
 *
 *   <iframe src="https://.../addon/mini/lmo-minitab.php?mini_liga=3&mini_ueber=3&mini_unter=2"
 *           frameborder="0" width="220" height="260" scrolling="no"></iframe>
 *
 * Steuerparameter (GET hat immer Vorrang vor vorher gesetzten PHP-Variablen):
 *   mini_liga      Liga-ID (Pflicht)
 *   mini_platz     Tabellenplatz, um den herum der Ausschnitt zentriert wird.
 *                  Leer/0 = um die Lieblingsmannschaft der Liga zentrieren.
 *   mini_ueber     Anzahl Plätze oberhalb des Zentrums (Standard: 2)
 *   mini_unter     Anzahl Plätze unterhalb des Zentrums (Standard: 2)
 *   mini_template  Name der Template-Datei ohne Endung (Standard: "standard"),
 *                  Datei muss unter /addon/mini/templates/{name}.tpl.php liegen
 */
declare(strict_types=1);

// Wird diese Datei über den zentralen Controller addon-run.php aufgerufen
// (der einzige noch erlaubte Aufrufweg, siehe addon/.htaccess) oder per
// include() aus einer anderen Datei eingebunden? Nur im ersten Fall wird
// ein vollständiges HTML-Grundgerüst drumherum gebaut.
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
 * eingebunden wird. renderTeamLogoImg() & Co. liefern Pfade wie
 * "assets/img/teams/X.png", relativ zum Projekt-Root gedacht – bei direktem
 * Aufruf (Datei liegt unter addon/mini/, zwei Ebenen tiefer als der Root)
 * würde der Browser das sonst falsch auflösen, und ein reines "../../"
 * würde wiederum bei include() aus einer Wrapper-Datei fehlschlagen, die
 * selbst nicht im Projekt-Root liegt.
 *
 * Funktionsweise: __DIR__ dieser Datei ist IMMER "{ProjektRoot}/addon/mini"
 * (unabhängig vom Aufrufweg, da PHP __DIR__ den tatsächlichen Speicherort
 * der Datei liefert). $_SERVER['SCRIPT_FILENAME']/['SCRIPT_NAME'] verraten,
 * wie der ursprünglich aufgerufene Dateipfad auf eine URL abgebildet wird –
 * daraus lässt sich das Document-Root ableiten und der Projekt-Root-Pfad in
 * eine korrekte, absolute URL umrechnen.
 */
function miniProjectRootUrlPrefix() : string
{
    static $prefix = null;
    if ($prefix !== null) {
        return $prefix;
    }

    // Zuverlässigster Fall: Aufruf über den zentralen Controller
    // addon-run.php (der einzige noch erlaubte Aufrufweg, siehe
    // addon/.htaccess) - der Controller kennt den korrekten, absoluten
    // Web-Pfad zu diesem Addon-Ordner bereits genau, keine Rätselei über
    // SCRIPT_FILENAME/Document-Root mehr nötig.
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

    // Fallback, falls sich das Document-Root nicht zuverlässig ermitteln
    // lässt (untypisches Server-Setup): bei Direktaufruf ist der Pfad relativ
    // zu addon/mini/ bekannt (zwei Ebenen hoch), bei include() bleibt nur
    // die (unsichere) Annahme, dass die Wrapper-Datei im Projekt-Root liegt.
    $isDirectCall = defined('LMO_ADDON_STANDALONE_CALL');
    $prefix = $isDirectCall ? '../../' : '';
    return $prefix;
}

// ── Parameter einlesen (GET überschreibt vorher per include() gesetzte
// PHP-Variablen, die wiederum Vorrang vor den Standardwerten haben) ──────────
$m_liga     = isset($_GET['mini_liga']) ? (int)$_GET['mini_liga'] : (int)($mini_liga ?? 0);
$m_platz    = isset($_GET['mini_platz']) && $_GET['mini_platz'] !== ''
    ? (int)$_GET['mini_platz']
    : (isset($mini_platz) && $mini_platz !== null && $mini_platz !== '' ? (int)$mini_platz : null);
$m_ueber    = isset($_GET['mini_ueber']) ? max(0, (int)$_GET['mini_ueber']) : max(0, (int)($mini_ueber ?? 2));
$m_unter    = isset($_GET['mini_unter']) ? max(0, (int)$_GET['mini_unter']) : max(0, (int)($mini_unter ?? 2));
$m_template = isset($_GET['mini_template']) ? (string)$_GET['mini_template'] : (string)($mini_template ?? 'standard');
$m_template = str_replace('..', '', basename($m_template)); // Path-Traversal-Schutz

if ($miniIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\"><title>Minitabelle</title>"
        . "<style>html,body{margin:0;padding:0;background:transparent;}</style></head><body>\n";
}

/**
 * renderTeamLogoImg()/findTeamLogoPathFrontend() liefern Pfade wie
 * "assets/img/teams/X.png" – relativ zum Projekt-Root gedacht, korrekt für
 * Seiten wie liga.php/home.php, die selbst im Projekt-Root liegen. Dieses
 * Addon liegt aber unter addon/mini/ (zwei Ebenen tiefer), wodurch der
 * Browser denselben relativen Pfad fälschlich relativ zu addon/mini/
 * aufgelöst hätte. Eigene, lokal korrigierte Variante mit "../../"-Präfix.
 */
function miniLogoImg(int $teamId) : string
{
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
    return '<img src="' . h(miniProjectRootUrlPrefix() . $path) . '" alt="" class="team-logo-inline">';
}

/**
 * Baut den HTML-Code der Minitabelle. Eigene Funktion (statt alles inline),
 * damit sich der Rückgabewert bei Bedarf auch cachen/weiterverarbeiten ließe.
 */
function renderMinitabelle(int $ligaId, ?int $platz, int $ueber, int $unter, string $templateName) : string
{
    try {
        $liga = getDB()->prepare('SELECT id, name, datum FROM ' . tbl('liga') . ' WHERE id = ?');
        $liga->execute([$ligaId]);
        $ligaRow = $liga->fetch();
    } catch (Throwable) {
        $ligaRow = null;
    }
    if ($ligaRow === null) {
        return '<p style="font-family:sans-serif;color:#a33">Liga nicht gefunden (mini_liga=' . h($ligaId) . ')</p>';
    }

    $opts        = getLigaOptions($ligaId);
    $teamsList   = getLigaTeamsList($ligaId);
    $allSpieltage = getAllSpieltage($ligaId);
    $partien     = getAllLigaPartien($allSpieltage);
    $rows        = computeStandings($teamsList, $partien, $opts, $ligaId);
    $totalTeams  = count($rows);
    if ($totalTeams === 0) {
        return '<p style="font-family:sans-serif;color:#a33">Keine Teams in dieser Liga</p>';
    }

    $mittelById = [];
    foreach ($teamsList as $t) {
        $mittelById[(int)$t['id']] = $t['mittel'] ?? '';
    }
    $favTeamId = resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));

    // ── Zentrum des Ausschnitts bestimmen: expliziter Platz > Lieblingsteam > Platz 1 ──
    $viewPosition = 1;
    if ($platz !== null && $platz > 0 && $platz <= $totalTeams) {
        $viewPosition = $platz;
    } elseif ($favTeamId !== null) {
        foreach ($rows as $i => $r) {
            if ($r['id'] === $favTeamId) { $viewPosition = $i + 1; break; }
        }
    }

    // ── Anzeigefenster (gleiche Verschiebe-Logik wie im alten LMO: reicht das
    // Fenster über den Rand hinaus, wird es soweit möglich verschoben, statt
    // einfach abgeschnitten zu werden, damit man wenn möglich immer die volle
    // angeforderte Anzahl Zeilen sieht) ─────────────────────────────────────
    $begin = $viewPosition - $unter;
    $end   = $viewPosition + $ueber;
    if ($begin <= 0) {
        $begin = 1;
        $end   = min($totalTeams, $unter + 1 + $ueber);
    }
    if ($end > $totalTeams) {
        $end   = $totalTeams;
        $begin = max(1, $totalTeams - $unter - $ueber);
    }

    // ── Template laden ───────────────────────────────────────────────────────
    $templatePath = __DIR__ . '/templates/' . $templateName . '.tpl.php';
    if (!is_file($templatePath)) {
        $templatePath = __DIR__ . '/templates/standard.tpl.php';
    }
    $templateSrc = (string)file_get_contents($templatePath);

    // Wiederholten Zeilen-Block ("<!-- BEGIN Inhalt -->...<!-- END Inhalt -->",
    // Leerzeichen um "Inhalt" beachten, wie im alten LMO) aus dem Template
    // herausschneiden.
    if (!preg_match('/<!-- BEGIN Inhalt -->(.*?)<!-- END Inhalt -->/s', $templateSrc, $m)) {
        return '<p style="font-family:sans-serif;color:#a33">Ungültiges Template (kein Inhalt-Block gefunden)</p>';
    }
    $rowTemplate = $m[1];
    $before      = substr($templateSrc, 0, strpos($templateSrc, $m[0]));
    $after       = substr($templateSrc, strpos($templateSrc, $m[0]) + strlen($m[0]));

    $rowsHtml = '';
    foreach ($rows as $i => $r) {
        $platzNr = $i + 1;
        if ($platzNr < $begin || $platzNr > $end) {
            continue;
        }
        $diff = $r['tore_h'] - $r['tore_g'];
        $diffText = ($diff > 0 ? '+' : '') . $diff;
        $markerColor = computeStandingsMarkerColor($i, $totalTeams, $opts);
        $isFav = $favTeamId !== null && $r['id'] === $favTeamId;
        $style = $markerColor !== '' ? 'border-left:4px solid ' . $markerColor . ';' : '';
        if ($isFav) {
            $style .= 'font-weight:bold;';
        }

        $replacements = [
            '<!--Platz-->'        => (string)$platzNr,
            '<!--Logo-->'         => miniLogoImg((int)$r['id']),
            '<!--Team-->'         => h($r['kurz'] !== '' ? $r['kurz'] : $r['name']),
            '<!--TeamLang-->'     => h($r['name']),
            '<!--TeamMittel-->'   => h($mittelById[$r['id']] ?? ''),
            '<!--Spiele-->'       => (string)$r['sp'],
            '<!--Punkte-->'       => (string)$r['pkt'],
            '<!--PlusTore-->'     => (string)$r['tore_h'],
            '<!--MinusTore-->'    => (string)$r['tore_g'],
            '<!--Tordifferenz-->' => h($diffText),
            '<!--Siege-->'        => (string)$r['s'],
            '<!--Unentschieden-->'=> (string)$r['u'],
            '<!--Niederlagen-->'  => (string)$r['n'],
            '<!--Style-->'        => h($style),
            '<!--Class-->'        => $isFav ? 'mini-fav' : '',
        ];
        $rowsHtml .= strtr($rowTemplate, $replacements);
    }

    $outer = [
        '<!--Link-->'     => h(miniProjectRootUrlPrefix() . 'liga.php?id=' . $ligaId . '&view=tabelle'),
        '<!--Tabelle-->'  => h($ligaRow['name']),
        '<!--ligaDatum-->'=> h(tf('liga_stand_datum', ['datum' => ($ligaTs = strtotime((string)($ligaRow['datum'] ?? ''))) !== false ? date('d.m.Y', $ligaTs) : date('d.m.Y')])),
        '<!--Copyright-->' => \LMOnext\Liga\LigaService::renderCopyrightNotice('minitab'),
    ];
    $before = strtr($before, $outer);
    $after  = strtr($after, $outer);

    return $before . $rowsHtml . $after;
}

if ($m_liga <= 0) {
    echo '<p style="font-family:sans-serif;color:#a33">Fehlender oder ungültiger Parameter "mini_liga"</p>';
} else {
    echo renderMinitabelle($m_liga, $m_platz, $m_ueber, $m_unter, $m_template);
}

if ($miniIsDirectCall) {
    echo "\n</body></html>\n";
}
