<?php
/**
 * Project: LMOnext
 * Filename: frontend/data_liga.php
 * Fileversion: 3.1.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Kompatibilitätsschicht für die frühere monolithische frontend/data_liga.php.
 * Die eigentliche Implementierung liegt jetzt unter src/Liga/ (siehe dort für
 * Details zu einzelnen Funktionen). Diese Datei lädt die Trait-Dateien direkt
 * per require_once (kein Composer nötig) und stellt alle bisherigen globalen
 * Funktionsnamen unverändert als dünne Delegations-Wrapper bereit.
 */
declare(strict_types = 1);

// ── Trait-Dateien + Fassaden-Klasse laden (kein Composer/PSR-4 nötig) ────────
require_once __DIR__ . '/../src/Liga/LigaRepositoryTrait.php';
require_once __DIR__ . '/../src/Liga/SpieltagRepositoryTrait.php';
require_once __DIR__ . '/../src/Liga/TeamRepositoryTrait.php';
require_once __DIR__ . '/../src/Liga/TeamFormattingTrait.php';
// require_once auf HeadToHeadTrait.php ENTFERNT (Beitrag: Auslagerung als
// eigenständiges Addon "teamvergleich", siehe CHANGELOG.md) - die Datei
// existiert im Core nicht mehr, liegt jetzt unter
// addon/teamvergleich/HeadToHead.php und wird nur geladen, wenn das Addon
// aktiv ist (siehe dortige addon.json, Feld "frontend_handlers").
require_once __DIR__ . '/../src/Liga/TournamentTrait.php';
require_once __DIR__ . '/../src/Liga/StandingsTrait.php';
require_once __DIR__ . '/../src/Liga/StatisticsTrait.php';
require_once __DIR__ . '/../src/Liga/RenderViewsTrait.php';
require_once __DIR__ . '/../src/Liga/LigaService.php';

if (!defined('TEAM_LOGO_EXT_LIST')) {
    define('TEAM_LOGO_EXT_LIST', ['svg', 'jpg', 'jpeg', 'png', 'gif']);
}

function getLigaById(int $id) : ?array
{
    return \LMOnext\Liga\LigaService::getLigaById($id);
}

function getLigaType(int $ligaId) : int
{
    return \LMOnext\Liga\LigaService::getLigaType($ligaId);
}

function getLigaTeamCount(int $ligaId) : int
{
    return \LMOnext\Liga\LigaService::getLigaTeamCount($ligaId);
}

function getLigaOptions(int $ligaId) : array
{
    return \LMOnext\Liga\LigaService::getLigaOptions($ligaId);
}

function resolveTeamNumberToId(int $ligaId, int $number) : ?int
{
    return \LMOnext\Liga\LigaService::resolveTeamNumberToId($ligaId, $number);
}

function ligaFlagEnabled(array $opts, string $key, bool $default = true) : bool
{
    return \LMOnext\Liga\LigaService::ligaFlagEnabled($opts, $key, $default);
}

function getLigaViewFlags(array $opts) : array
{
    return \LMOnext\Liga\LigaService::getLigaViewFlags($opts);
}

function getAllSpieltage(int $ligaId) : array
{
    return \LMOnext\Liga\LigaService::getAllSpieltage($ligaId);
}

function getMaxSpieltagNummer(array $allSpieltage) : int
{
    return \LMOnext\Liga\LigaService::getMaxSpieltagNummer($allSpieltage);
}

function getLatestSpieltagWithResults(array $allSpieltage) : ?array
{
    return \LMOnext\Liga\LigaService::getLatestSpieltagWithResults($allSpieltage);
}

function getSpieltagByNummer(array $allSpieltage, int $nummer) : ?array
{
    return \LMOnext\Liga\LigaService::getSpieltagByNummer($allSpieltage, $nummer);
}

function getSpieltagPartien(int $spieltagId) : array
{
    return \LMOnext\Liga\LigaService::getSpieltagPartien($spieltagId);
}

// ── Teamvergleich (H2H) — als eigenständiges Addon "teamvergleich"
// ausgegliedert (Beitrag: Nutzerwunsch, siehe Machbarkeitsstudie im Chat-
// Verlauf sowie CHANGELOG.md). Diese vier Wrapper-Funktionen bleiben im Core
// (viele Aufrufer: RenderViewsTrait.php, PdfExporter, data_liga_pretraits.php)
// delegieren aber jetzt über einen Hook statt direkt an LigaService, das die
// zugehörige Trait nicht mehr enthält. Ist das Addon nicht aktiv/installiert,
// liefert doHook() einfach die unveränderten Ausgangsdaten zurück (leeres
// Array/leerer String) - kein Fehler, der Teamvergleich fällt einfach weg.
// resolveLinkedTeamIds()/resolveCanonicalTeamId() brauchen KEINE globalen
// Wrapper mehr, da sie nur intern von der jetzt ausgelagerten
// getHeadToHeadMatches()-Logik selbst genutzt wurden, kein externer Aufrufer.
function getHeadToHeadMatches(int $idA, int $idB) : array
{
    $result = doHook('liga.h2h_matches', ['idA' => $idA, 'idB' => $idB, 'matches' => []]);
    return is_array($result['matches'] ?? null) ? $result['matches'] : [];
}

function buildHeadToHeadPayload(int $idA, int $idB, string $nameA, string $nameB, bool $showLogos = false) : string
{
    $result = doHook('liga.h2h_payload', [
        'idA' => $idA, 'idB' => $idB, 'nameA' => $nameA, 'nameB' => $nameB,
        'showLogos' => $showLogos, 'json' => '{}',
    ]);
    return (string)($result['json'] ?? '{}');
}

function renderH2hIcon(int $heimId, int $gastId, string $heimName, string $gastName, bool $showLogos = false) : string
{
    $result = doHook('liga.compare_icon', [
        'heim_id' => $heimId, 'gast_id' => $gastId, 'heim_name' => $heimName,
        'gast_name' => $gastName, 'show_logos' => $showLogos, 'html' => '',
    ]);
    return (string)($result['html'] ?? '');
}

function renderH2hModalAssets() : string
{
    $result = doHook('liga.compare_modal_assets', ['html' => '']);
    return (string)($result['html'] ?? '');
}

function statusSuffix(array $partie) : string
{
    return \LMOnext\Liga\LigaService::statusSuffix($partie);
}

function findSpielfreiTeams(int $ligaId, array $partien) : array
{
    return \LMOnext\Liga\LigaService::findSpielfreiTeams($ligaId, $partien);
}

function renderSpielfreiNote(int $ligaId, array $partien) : string
{
    return \LMOnext\Liga\LigaService::renderSpielfreiNote($ligaId, $partien);
}

function partieIsEmptyPlaceholder(array $partie) : bool
{
    return \LMOnext\Liga\LigaService::partieIsEmptyPlaceholder($partie);
}

function partieTeamName(array $partie, string $side) : string
{
    return \LMOnext\Liga\LigaService::partieTeamName($partie, $side);
}

function findTeamLogoPathFrontend(int $teamId, bool $forBrowser = true) : ?string
{
    return \LMOnext\Liga\LigaService::findTeamLogoPathFrontend($teamId, $forBrowser);
}

function renderTeamLogoImg(int $teamId, bool $showLogos) : string
{
    return \LMOnext\Liga\LigaService::renderTeamLogoImg($teamId, $showLogos);
}

function renderTeamLogoImgWrapped(int $teamId, bool $showLogos) : string
{
    return \LMOnext\Liga\LigaService::renderTeamLogoImgWrapped($teamId, $showLogos);
}

function partieTeamNameWithLogo(array $partie, string $side, bool $showLogos) : string
{
    return \LMOnext\Liga\LigaService::partieTeamNameWithLogo($partie, $side, $showLogos);
}

function partieTeamNameWithLogoReversed(array $partie, string $side, bool $showLogos) : string
{
    return \LMOnext\Liga\LigaService::partieTeamNameWithLogoReversed($partie, $side, $showLogos);
}

function partieZeitDisplay(array $partie, ?string $spieltagStart) : string
{
    return \LMOnext\Liga\LigaService::partieZeitDisplay($partie, $spieltagStart);
}

function spieltagDateRange(array $partien, ?string $spieltagStart) : string
{
    return \LMOnext\Liga\LigaService::spieltagDateRange($partien, $spieltagStart);
}

function computeSpieltagStats(array $partien) : array
{
    return \LMOnext\Liga\LigaService::computeSpieltagStats($partien);
}

function koRoundName(int $teamCount, bool $isFinalRound, int $roundNummer) : string
{
    return \LMOnext\Liga\LigaService::koRoundName($teamCount, $isFinalRound, $roundNummer);
}

function roundDisplayName(array $st, bool $isKO, int $maxNr) : string
{
    return \LMOnext\Liga\LigaService::roundDisplayName($st, $isKO, $maxNr);
}

function groupPartienByPairing(array $partien) : array
{
    return \LMOnext\Liga\LigaService::groupPartienByPairing($partien);
}

function renderPartieRow(array $partie, ?string $spieltagStart = null, ?int $favTeamId = null, bool $showLogos = false, bool $reverseHeim = false) : string
{
    return \LMOnext\Liga\LigaService::renderPartieRow($partie, $spieltagStart, $favTeamId, $showLogos, $reverseHeim);
}

function renderResultsTable(array $partien, ?string $spieltagStart, ?int $favTeamId = null, bool $showLogos = false, bool $reverseHeim = false) : string
{
    return \LMOnext\Liga\LigaService::renderResultsTable($partien, $spieltagStart, $favTeamId, $showLogos, $reverseHeim);
}

function renderStatsBlock(string $heading, array $partien) : string
{
    return \LMOnext\Liga\LigaService::renderStatsBlock($heading, $partien);
}

function renderSpieltagPicker(array $allSpieltage, int $ligaId, ?int $currentNr, bool $isKO, int $maxNr, string $targetView = 'ergebnisse') : string
{
    return \LMOnext\Liga\LigaService::renderSpieltagPicker($allSpieltage, $ligaId, $currentNr, $isKO, $maxNr, $targetView);
}

function renderTabsBar(array $flags, int $ligaId, string $currentView, ?int $activeNr = null) : string
{
    return \LMOnext\Liga\LigaService::renderTabsBar($flags, $ligaId, $currentView, $activeNr);
}

function renderInfoView() : string
{
    return \LMOnext\Liga\LigaService::renderInfoView();
}

function monthName(int $month) : string
{
    return \LMOnext\Liga\LigaService::monthName($month);
}

function renderKalenderView(array $allSpieltage, int $ligaId, bool $isKO, int $maxNr, int $year, int $month) : string
{
    return \LMOnext\Liga\LigaService::renderKalenderView($allSpieltage, $ligaId, $isKO, $maxNr, $year, $month);
}

function getDummyTeamId() : int
{
    return \LMOnext\Liga\LigaService::getDummyTeamId();
}

function reorderBracketPairings(array $rounds) : array
{
    return \LMOnext\Liga\LigaService::reorderBracketPairings($rounds);
}

function renderBracketView(int $ligaId, array $allSpieltage, bool $isKO, int $maxNr) : string
{
    return \LMOnext\Liga\LigaService::renderBracketView($ligaId, $allSpieltage, $isKO, $maxNr);
}

function getLigaTeamsList(int $ligaId) : array
{
    return \LMOnext\Liga\LigaService::getLigaTeamsList($ligaId);
}

function getLigaTeamsListUncached(int $ligaId) : array
{
    return \LMOnext\Liga\LigaService::getLigaTeamsListUncached($ligaId);
}

function getAllLigaPartien(array $allSpieltage, ?int $ligaId = null) : array
{
    return \LMOnext\Liga\LigaService::getAllLigaPartien($allSpieltage, $ligaId);
}

function computeStandings(array $teamsList, array $partien, array $ligaOptions, ?int $ligaId = null) : array
{
    return \LMOnext\Liga\LigaService::computeStandings($teamsList, $partien, $ligaOptions, $ligaId);
}

function getLigaStrafpunkte(int $ligaId) : array
{
    return \LMOnext\Liga\LigaService::getLigaStrafpunkte($ligaId);
}

function setLigaStrafpunkte(int $ligaId, array $eintraege) : bool
{
    return \LMOnext\Liga\LigaService::setLigaStrafpunkte($ligaId, $eintraege);
}

function computeStandingsMarkerColor(int $index, int $totalTeams, array $opts) : string
{
    return \LMOnext\Liga\LigaService::computeStandingsMarkerColor($index, $totalTeams, $opts);
}

function renderStandingsView(int $ligaId, array $allSpieltage, ?int $uptoSpieltag = null, string $tableMode = 'gesamt') : string
{
    return \LMOnext\Liga\LigaService::renderStandingsView($ligaId, $allSpieltage, $uptoSpieltag, $tableMode);
}

function renderTeamScheduleView(int $ligaId, array $allSpieltage, ?int $selectedTeamId) : string
{
    return \LMOnext\Liga\LigaService::renderTeamScheduleView($ligaId, $allSpieltage, $selectedTeamId);
}

function renderKreuztabelleView(int $ligaId, array $allSpieltage) : string
{
    return \LMOnext\Liga\LigaService::renderKreuztabelleView($ligaId, $allSpieltage);
}

function fieberkurveColors() : array
{
    return \LMOnext\Liga\LigaService::fieberkurveColors();
}

function renderFieberkurveView(int $ligaId, array $allSpieltage) : string
{
    return \LMOnext\Liga\LigaService::renderFieberkurveView($ligaId, $allSpieltage);
}

function computeTeamStreaks(int $teamId, array $partienChrono) : array
{
    return \LMOnext\Liga\LigaService::computeTeamStreaks($teamId, $partienChrono);
}

function computeAllTeamsStreakRecords(array $teams, array $partien) : array
{
    return \LMOnext\Liga\LigaService::computeAllTeamsStreakRecords($teams, $partien);
}

function findExtremeMatches(array $partien) : array
{
    return \LMOnext\Liga\LigaService::findExtremeMatches($partien);
}

function computeTeamDetailStats(int $teamId, array $teams, array $partien, array $standing) : array
{
    return \LMOnext\Liga\LigaService::computeTeamDetailStats($teamId, $teams, $partien, $standing);
}

function renderTeamStatBox(array $stat, int $teamId = 0, bool $showLogos = false) : string
{
    return \LMOnext\Liga\LigaService::renderTeamStatBox($stat, $teamId, $showLogos);
}

function renderOverallStatsBlock(array $teams, array $partien) : string
{
    return \LMOnext\Liga\LigaService::renderOverallStatsBlock($teams, $partien);
}

function renderLigastatistikView(int $ligaId, array $allSpieltage, ?int $team1Id, ?int $team2Id) : string
{
    return \LMOnext\Liga\LigaService::renderLigastatistikView($ligaId, $allSpieltage, $team1Id, $team2Id);
}

/**
 * Baut den "← Zur Übersicht"-Link als vollständigen HTML-Block (nicht nur
 * den Text), damit er sich über die globale Einstellung "Übersicht-Link
 * anzeigen?" (Admin → Einstellungen → Besucherbereich) komplett ausblenden
 * lässt - nicht nur der Text wäre sonst leer, der Link selbst (mit href)
 * bliebe trotzdem anklickbar sichtbar. Default "an" (kein stiller
 * Verhaltenswechsel für bestehende Installationen ohne explizite Wahl).
 *
 * Ursprünglich nur in liga.php definiert (Fileversion vor 3.10.1), hierher
 * verschoben, damit auch home.php (Tippspiel-View, siehe
 * addon/tipp/view_tippspiel_frontend.php) denselben Link nutzen kann - eine
 * einfache eigenständige Hilfsfunktion, bewusst NICHT über die
 * LigaService-Fassade delegiert (kein Liga-spezifischer Zustand nötig).
 */
function renderBackLinkBlock() : string
{
    if (getAdminSetting('show_back_link', '1') !== '1') {
        return '';
    }
    return '<a class="back-link" href="home.php">' . h(tf('liga_back_link')) . '</a>';
}
