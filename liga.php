<?php
/**
 * Project: LMOnext
 * Filename: liga.php
 * Fileversion: 3.14.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

require_once __DIR__ . '/frontend/bootstrap.php';
// GEZIELTES, bedingtes Laden statt eines unbedingten require_once (Beitrag:
// Auslagerung als eigenständiges Addon "pdf-export", siehe CHANGELOG.md).
// WICHTIG: bewusst NICHT über AddonManager::bootFrontend()/frontend_handlers
// im addon.json geladen - das würde die Datei bei JEDEM Frontend-Request
// laden (auch home.php, Mini-Addons usw.), nicht nur hier in liga.php, wo sie
// tatsächlich gebraucht wird (siehe bereits bestehender Kommentar dazu in
// frontend/bootstrap.php, der genau das für die alte, feste Einbindung
// vermeiden wollte - dieselbe Überlegung gilt jetzt für das Addon).
// Ist das Addon nicht installiert/aktiv, existieren die exportXxxPdf()-
// Funktionen schlicht nicht - deshalb prüfen ALLE Aufrufstellen unten
// zusätzlich zu $showPdfButtons/isEnabled('pdf-export'), niemals blind.
if (function_exists('addonManager') && addonManager()->isEnabled('pdf-export')) {
    require_once __DIR__ . '/addon/pdf-export/pdf_export.php';
}

// renderBackLinkBlock() ist jetzt in frontend/data_liga.php definiert (wird
// über bootstrap.php geladen) - dadurch kann auch home.php (Tippspiel-View)
// dieselbe Funktion nutzen, siehe dortiger Changelog

// ── PDF-Export des Team-Vergleichs (Direkter Vergleich) ──────────────────────
// Teamübergreifend, nicht an eine bestimmte Liga gebunden – deshalb hier vor
// der normalen id/view-Auflösung abgefangen. Zusätzliche Prüfung auf das
// pdf-export-Addon (Beitrag: Auslagerung als eigenständiges Addon) - ohne
// aktives Addon existiert exportH2hPdf() schlicht nicht, ein Aufruf würde
// sonst mit "Call to undefined function" fatal abbrechen. ZUSÄTZLICH auf das
// teamvergleich-Addon geprüft (Beitrag: Auslagerung als eigenständiges Addon,
// siehe CHANGELOG.md) - ohne aktives teamvergleich-Addon liefert
// getHeadToHeadMatches() zwar dank Hook-Fallback kein Fatal, aber ein
// inhaltsleeres PDF ohne echte Daten wäre trotzdem verwirrend, deshalb hier
// schon abgefangen.
if (isset($_GET['h2h_pdf']) && function_exists('addonManager') && addonManager()->isEnabled('pdf-export')
    && addonManager()->isEnabled('teamvergleich')
    && getAdminSetting('show_pdf_buttons', '1') === '1') {
    $teamAId = (int)($_GET['a'] ?? 0);
    $teamBId = (int)($_GET['b'] ?? 0);
    if ($teamAId > 0 && $teamBId > 0) {
        exportH2hPdf($teamAId, $teamBId, ($_GET['logos'] ?? '0') === '1');
    }
    exit;
}

$ligaId = (int)($_GET['id'] ?? 0);
$liga   = $ligaId > 0 ? getLigaById($ligaId) : null;

if ($liga === null) {
    renderTemplate($activeTemplate, 'liga_not_found', [
        'Titel'             => h(tf('liga_not_found')),
        'ZurueckLink'       => h(tf('liga_back_link')),
        'ZurueckLinkBlock'  => renderBackLinkBlock(),
        'NichtGefundenText' => h(tf('liga_not_found')),
    ]);
    exit;
}

$isKO         = getLigaType($ligaId) === 1;
$opts         = getLigaOptions($ligaId);
$flags        = getLigaViewFlags($opts);
$showLogos    = ($opts['ShowLogos'] ?? '0') === '1';
// Default '1' (anzeigen): die Spielfrei-Anzeige wurde bereits ohne diese
// Einstellung ausgeliefert, daher soll sich für bestehende Ligen ohne
// explizite Wahl nichts ändern (kein stiller Verhaltenswechsel).
$showSpielfrei = ($opts['ShowSpielfrei'] ?? '1') === '1';
// Globale Einstellung (Admin → Einstellungen → Besucherbereich), gilt für
// alle Liga-Typen und alle PDF-Exporte gleichermaßen. Blockiert bei
// Deaktivierung nicht nur den Button, sondern auch den direkten Aufruf über
// ?pdf=1 (sonst wäre die Datei trotz ausgeblendetem Button weiter abrufbar).
// Zusätzliche Prüfung auf das pdf-export-Addon (Beitrag: Auslagerung als
// eigenständiges Addon, siehe CHANGELOG.md) - ohne aktives Addon existieren
// die exportXxxPdf()-Funktionen schlicht nicht, ALLE unten von dieser
// Variable abhängigen Aufrufstellen sind dadurch automatisch mit-geschützt.
$showPdfButtons = function_exists('addonManager') && addonManager()->isEnabled('pdf-export')
    && getAdminSetting('show_pdf_buttons', '1') === '1';
// Tabelle und Kreuztabelle ergeben bei KO-Turnieren (Ausscheidungsmodus) keinen
// Sinn – nur für reguläre (Round-Robin-)Ligen anzeigen.
if ($isKO) {
    $flags['tabelle']       = false;
    $flags['kreuztabelle']  = false;
    $flags['fieberkurve']   = false;
    $flags['ligastatistik'] = false;
}

$allSpieltage = getAllSpieltage($ligaId);
$maxNr        = getMaxSpieltagNummer($allSpieltage);
$favTeamId    = resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));

// ── Aktuellen Reiter bestimmen (?view=…, sonst ersten aktivierten Reiter) ────
$viewOrder   = ['ergebnisse', 'kalender', 'tabelle', 'spielplaene', 'kreuztabelle', 'fieberkurve', 'ligastatistik', 'info'];
// Addon-Views dynamisch hinzufuegen (z.B. 'spielerstatistik' vom Player-Addon)
$hookOrder = doHook('liga.view_order', ['order' => $viewOrder]);
if (isset($hookOrder['order']) && is_array($hookOrder['order'])) {
    foreach ($hookOrder['order'] as $v) {
        if (!in_array($v, $viewOrder, true)) { $viewOrder[] = $v; }
    }
}
$currentView = $_GET['view'] ?? 'ergebnisse';
if (empty($flags[$currentView])) {
    $currentView = 'ergebnisse';
    foreach ($viewOrder as $candidate) {
        if (!empty($flags[$candidate])) {
            $currentView = $candidate;
            break;
        }
    }
}

// Für die Tab-Leiste: welcher Spieltag ist gerade aktiv (nur bei
// Ergebnisse/Tabelle relevant, siehe renderTabsBar() weiter unten) - damit
// ein Wechsel zwischen den beiden Reitern denselben Spieltag beibehält statt
// immer auf den letzten Spieltag zurückzuspringen.
$activeNr = null;

switch ($currentView) {

    case 'kalender':
        $year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        $viewInhalt = renderKalenderView($allSpieltage, $ligaId, $isKO, $maxNr, $year, $month);
        break;

    case 'tabelle':
        $tabelleNr = isset($_GET['nr']) ? (int)$_GET['nr'] : null;
        $activeNr  = ($tabelleNr !== null && $tabelleNr >= 1 && $tabelleNr <= $maxNr) ? $tabelleNr : $maxNr;
        $tableMode = $_GET['table'] ?? 'gesamt';
        // $tmode (kurz/mittel/vollständig, nur bei Sportarten mit eigenen
        // Darstellungsmodi wie Volleyball relevant) 1:1 aus der URL an den
        // PDF-Export durchreichen und im PDF-Button-Link mitführen, damit
        // das heruntergeladene PDF exakt die Spaltenauswahl zeigt, die der
        // Besucher gerade vor sich hat - siehe PdfExporter::exportTabellePdf().
        $tmode = $_GET['tmode'] ?? '';
        if (isset($_GET['pdf']) && $showPdfButtons) {
            exportTabellePdf($liga['name'], $ligaId, $allSpieltage, $showLogos, $tableMode, $tmode);
            exit;
        }
        $tabellePicker = renderSpieltagPicker($allSpieltage, $ligaId, $activeNr, $isKO, $maxNr, 'tabelle');
        $viewInhalt = $tabellePicker . renderStandingsView($ligaId, $allSpieltage, $tabelleNr, $tableMode);
        if ($showPdfButtons) {
            $pdfTableParam = $tableMode !== 'gesamt' ? ('&table=' . $tableMode) : '';
            $pdfTmodeParam = $tmode !== '' ? ('&tmode=' . rawurlencode($tmode)) : '';
            $viewInhalt .= '<div class="pdf-export-row"><a class="btn-pdf-export" href="?id=' . $ligaId . '&view=tabelle&pdf=1' . $pdfTableParam . $pdfTmodeParam . '" title="' . h(tf('liga_pdf_export_button')) . '">'
                . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . '<rect x="7" y="3" width="13" height="16" rx="2"/><path d="M4 7v13a2 2 0 0 0 2 2h11"/>'
                . '</svg>'
                . 'PDF</a></div>';
        }
        break;

    case 'spielplaene':
        if ($isKO) {
            $viewInhalt = renderBracketView($ligaId, $allSpieltage, $isKO, $maxNr);
        } else {
            $selectedTeamId = isset($_GET['team'])
                ? (int)$_GET['team']
                : resolveTeamNumberToId($ligaId, (int)($opts['selTeam'] ?? 0));
            if ($selectedTeamId !== null && isset($_GET['pdf']) && $showPdfButtons) {
                exportSpielplanPdf($liga['name'], $ligaId, $allSpieltage, $selectedTeamId, $showLogos);
                exit;
            }
            $viewInhalt = renderTeamScheduleView($ligaId, $allSpieltage, $selectedTeamId);
            if ($selectedTeamId !== null && $showPdfButtons) {
                $pdfUrl = '?id=' . $ligaId . '&view=spielplaene&team=' . $selectedTeamId . '&pdf=1';
                $viewInhalt .= '<div class="pdf-export-row"><a class="btn-pdf-export" href="' . h($pdfUrl) . '" title="' . h(tf('liga_pdf_export_button')) . '">'
                    . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<rect x="7" y="3" width="13" height="16" rx="2"/><path d="M4 7v13a2 2 0 0 0 2 2h11"/>'
                    . '</svg>'
                    . 'PDF</a></div>';
            }
        }
        break;

    case 'kreuztabelle':
        $viewInhalt = renderKreuztabelleView($ligaId, $allSpieltage);
        break;

    case 'fieberkurve':
        $viewInhalt = renderFieberkurveView($ligaId, $allSpieltage);
        break;

    case 'ligastatistik':
        $team1 = isset($_GET['team1']) && (int)$_GET['team1'] > 0 ? (int)$_GET['team1'] : null;
        $team2 = isset($_GET['team2']) && (int)$_GET['team2'] > 0 ? (int)$_GET['team2'] : null;
        $viewInhalt = renderLigastatistikView($ligaId, $allSpieltage, $team1, $team2);
        break;

    case 'info':
        $viewInhalt = renderInfoView();
        break;

    default:
        // ── Addon-Views ueber Hook (z.B. spielerstatistik) ──────────────
        $hookResult = doHook('liga.view_render', [
            'view'    => $currentView,
            'liga_id' => $ligaId,
            'inhalt'  => '',
        ]);
        if (!empty($hookResult['inhalt'])) {
            $viewInhalt = $hookResult['inhalt'];
            break;
        }
        // Fallthrough zu ergebnisse wenn kein Addon gematched hat

    case 'ergebnisse':
        // ── Aktuell gewählten Spieltag/Runde ermitteln (?nr=N, sonst letzter mit Ergebnis) ──
        $requestedNr = isset($_GET['nr']) ? (int)$_GET['nr'] : null;
        $spieltag    = $requestedNr !== null ? getSpieltagByNummer($allSpieltage, $requestedNr) : null;
        if ($spieltag === null) {
            $spieltag = getLatestSpieltagWithResults($allSpieltage);
        }

        $currentNr   = $spieltag['nummer'] ?? null;
        $activeNr    = $currentNr !== null ? (int)$currentNr : null;
        $currentName = $spieltag !== null ? roundDisplayName($spieltag, $isKO, $maxNr) : '';
        $partien     = $spieltag !== null ? getSpieltagPartien((int)$spieltag['id']) : [];
        // _liga_id für die sport-profil-abhängige Ergebnis-Anzeige (Beitrag:
        // Torsten Hofmann, siehe RenderViewsTrait::formatScore()) - getSpieltagPartien()
        // kennt die Liga selbst nicht (nur den Spieltag), daher hier ergänzt.
        foreach ($partien as &$_p) { $_p['_liga_id'] = $ligaId; }
        unset($_p);
        // Reine Leer-Begegnungen (kein Team, kein Label auf beiden Seiten – z.B.
        // Freilos-Auffüllplätze bei KO-Turnieren) werden nicht angezeigt, siehe
        // partieIsEmptyPlaceholder().
        $partien     = array_values(array_filter($partien, static fn(array $p) => !partieIsEmptyPlaceholder($p)));
        $dateRange   = $spieltag !== null ? spieltagDateRange($partien, $spieltag['start'] ?? null) : '';

        // ── PDF-Export (reguläre Ligen: "Spieltag N", KO-Turniere: Rundenname
        // wie "Achtelfinale"/"Runde 1"; bei Finale+Spiel um Platz 3 zwei
        // getrennte Abschnitte mit jeweils eigenem Datum statt einem
        // gemeinsamen Datumsbereich über beide Begegnungen hinweg) ────────────
        if ($spieltag !== null && isset($_GET['pdf']) && $showPdfButtons) {
            if ($isKO && (int)$currentNr === $maxNr && count(groupPartienByPairing($partien)) > 1) {
                $pdfGroups = groupPartienByPairing($partien);
                $pdfGroupHeadings = [tf('liga_round_finale'), tf('liga_heading_platz3')];
                $sectionSpecs = [];
                foreach ($pdfGroups as $i => $groupPartien) {
                    $heading = $pdfGroupHeadings[$i] ?? tf('liga_round_finale');
                    $groupDateRange = spieltagDateRange($groupPartien, $spieltag['start'] ?? null);
                    $sectionSpecs[] = [
                        'label'         => $heading . ($groupDateRange !== '' ? ' · ' . $groupDateRange : ''),
                        'partien'       => $groupPartien,
                        'spieltagStart' => $spieltag['start'] ?? null,
                        'ligaId'        => $ligaId,
                    ];
                }
            } else {
                $pdfRoundLabel = $isKO ? $currentName : tf('liga_pdf_title_matchday', ['n' => $currentNr]);
                $sectionSpecs = [[
                    'label'         => $pdfRoundLabel . ($dateRange !== '' ? ' · ' . $dateRange : ''),
                    'partien'       => $partien,
                    'spieltagStart' => $spieltag['start'] ?? null,
                    'spielfrei'     => $showSpielfrei ? findSpielfreiTeams($ligaId, $partien) : [],
                    'ligaId'        => $ligaId,
                ]];
            }
            exportErgebnissePdf($liga['name'], $sectionSpecs, $showLogos);
            exit;
        }

        $subtitle = '';
        if ($spieltag !== null) {
            $subtitleText = $isKO
                ? tf('liga_subtitle_round', ['name' => $currentName])
                : tf('liga_subtitle_matchday', ['n' => $currentNr]);
            $subtitle = '<h2 class="liga-subtitle">' . h($subtitleText) . '</h2>';
        }

        $pdfUrl = '?id=' . $ligaId . '&view=ergebnisse&nr=' . (int)$currentNr . '&pdf=1';
        $pdfButtonHtml = !$showPdfButtons ? '' : '<div class="pdf-export-row"><a class="btn-pdf-export" href="' . h($pdfUrl) . '" title="' . h(tf('liga_pdf_export_button')) . '">'
            . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<rect x="7" y="3" width="13" height="16" rx="2"/><path d="M4 7v13a2 2 0 0 0 2 2h11"/>'
            . '</svg>'
            . 'PDF</a></div>';

        if ($spieltag === null) {
            $ergebnisInhalt = '<p class="empty-msg">' . h(tf('liga_no_results_yet')) . '</p>';
        } elseif ($isKO && (int)$currentNr === $maxNr && count(groupPartienByPairing($partien)) > 1) {
            $ergebnisInhalt = '';
            $groups         = groupPartienByPairing($partien);
            $groupHeadings  = [tf('liga_round_finale'), tf('liga_heading_platz3')];
            foreach ($groups as $i => $groupPartien) {
                $heading = $groupHeadings[$i] ?? tf('liga_round_finale');
                $groupDateRange = spieltagDateRange($groupPartien, $spieltag['start'] ?? null);
                $headingWithRange = $heading . ($groupDateRange !== '' ? ' ' . $groupDateRange : '');
                $ergebnisInhalt .= '<h3 class="spieltag-heading">' . h($headingWithRange) . '</h3>';
                $ergebnisInhalt .= renderResultsTable($groupPartien, $spieltag['start'] ?? null, $favTeamId, $showLogos, true);
                $ergebnisInhalt .= renderStatsBlock($heading, $groupPartien);
            }
            $ergebnisInhalt .= $pdfButtonHtml;
        } else {
            $headingText = $isKO
                ? $currentName . ($dateRange !== '' ? ' ' . $dateRange : '')
                : tf('liga_heading_matchday_range', ['n' => $currentNr, 'range' => $dateRange]);
            $ergebnisInhalt  = '<h3 class="spieltag-heading">' . h($headingText) . '</h3>';
            $ergebnisInhalt .= renderResultsTable($partien, $spieltag['start'] ?? null, $favTeamId, $showLogos, true);
            $ergebnisInhalt .= $showSpielfrei ? renderSpielfreiNote($ligaId, $partien) : '';
            $ergebnisInhalt .= renderStatsBlock($currentName, $partien);
            $ergebnisInhalt .= $pdfButtonHtml;
        }

        $picker = renderSpieltagPicker($allSpieltage, $ligaId, $currentNr !== null ? (int)$currentNr : null, $isKO, $maxNr);

        $viewInhalt = $subtitle . $picker . $ergebnisInhalt;
        break;
}

$tabsBar = renderTabsBar($flags, $ligaId, $currentView, $activeNr);

renderTemplate($activeTemplate, 'liga', [
    'Titel'        => h($liga['name']),
    'ZurueckLink'  => h(tf('liga_back_link')),
    'ZurueckLinkBlock' => renderBackLinkBlock(),
    'LigaName'     => h($liga['name']),
    'TypChipClass' => $isKO ? 'chip-yellow' : 'chip-blue',
    'TypLabel'     => $isKO ? h(tf('home_type_ko')) : h(tf('home_type_liga')),
    'TabsBar'      => $tabsBar,
    'ViewInhalt'   => $viewInhalt,
]);
