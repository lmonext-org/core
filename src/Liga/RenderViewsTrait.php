<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/RenderViewsTrait.php
 * Fileversion: 1.12.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types=1);

namespace LMOnext\Liga;

/**
 * Extracted from the legacy frontend/data_liga.php.
 * Behavior is intentionally preserved; public compatibility wrappers live in frontend/data_liga.php.
 */
trait RenderViewsTrait
{
    /**
     * Liefert das Sport-Profil für die angegebene Liga (Beitrag: Torsten
     * Hofmann, siehe src/Sport/). Fallback auf Fußball, falls $ligaId nicht
     * gesetzt ist (z.B. Ewige Tabelle, die mehrere Ligen mischt).
     */
    public static function sportProfile(?int $ligaId) : \LMOnext\Sport\SportProfile
    {
        if ($ligaId === null || $ligaId === 0) {
            return \LMOnext\Sport\SportRegistry::get('football');
        }
        return \LMOnext\Sport\SportRegistry::get(self::getLigaSportType($ligaId));
    }

    /**
     * Formatiert ein Spielergebnis über das Sport-Profil der Liga, statt
     * "h_tore : g_tore" + statusSuffix() hart einzucodieren - für Fußball
     * bleibt die Anzeige dadurch unverändert (FootballProfile bildet die
     * bisherige Logik 1:1 nach), andere Sportarten bekommen ihre eigene
     * Darstellung (z.B. "3 : 1 Sätze" bei Volleyball).
     */
    public static function formatScore(array $partie, ?int $ligaId = null, bool $withPeriods = false) : string
    {
        if ($partie['h_tore'] === null || $partie['g_tore'] === null) {
            return '- : -';
        }
        return h(self::sportProfile($ligaId)->formatResult($partie, $withPeriods));
    }

    /**
     * Rendert eine einzelne Ergebniszeile über das Partial "partie_row"
     * (template/<aktiv>/partials/partie_row.tpl.php). $spieltagStart dient als
     * Datums-Fallback, falls die einzelne Partie keine eigene Zeit hat.
     */
    public static function renderPartieRow(array $partie, ?string $spieltagStart = null, ?int $favTeamId = null, bool $showLogos = false, bool $reverseHeim = false) : string
    {
        $heimRaw  = self::partieTeamName($partie, 'heim');
        $gastRaw  = self::partieTeamName($partie, 'gast');
        $heim     = $reverseHeim
            ? self::partieTeamNameWithLogoReversed($partie, 'heim', $showLogos)
            : partieTeamNameWithLogo($partie, 'heim', $showLogos);
        $gast     = self::partieTeamNameWithLogo($partie, 'gast', $showLogos);
        $gespielt = $partie['h_tore'] !== null && $partie['g_tore'] !== null;
        // Sport-Profil-Anzeige (Beitrag: Torsten Hofmann) - _liga_id ist ein
        // optionales, vom Aufrufer injizierbares Feld (siehe getAllLigaPartien()
        // in SpieltagRepositoryTrait.php); fehlt es, wird auf 'football'
        // zurückgefallen (bisheriges Verhalten, 100% rückwärtskompatibel).
        $score    = self::formatScore($partie, $partie['_liga_id'] ?? null, true);
        $datum    = h(self::partieZeitDisplay($partie, $spieltagStart));
        $hId      = (int)($partie['heim_id'] ?? 0);
        $gId      = (int)($partie['gast_id'] ?? 0);
    
        return renderPartial('partie_row', [
            'Datum'              => $datum,
            'Heim'                => $heim,
            'Gast'                => $gast,
            'Ergebnis'            => $score,
            'ErgebnisOffenClass'  => $gespielt ? '' : ' ergebnis-offen',
            'HeimClass'           => ($favTeamId !== null && $hId === $favTeamId) ? ' schedule-own' : '',
            'GastClass'           => ($favTeamId !== null && $gId === $favTeamId) ? ' schedule-own' : '',
            // \renderH2hIcon() ist die GLOBALE Funktion aus frontend/data_liga.php
            // (führender Backslash = Root-Namespace, nicht self:: - seit der
            // Auslagerung des Teamvergleichs als eigenständiges Addon
            // "teamvergleich" existiert HeadToHeadTrait/self::renderH2hIcon()
            // in dieser Klasse nicht mehr, siehe CHANGELOG.md). Liefert '',
            // wenn das Addon nicht aktiv ist - kein Fehler.
            'CompareIcon'         => \renderH2hIcon($hId, $gId, $heimRaw, $gastRaw, $showLogos),
        ]);
    }
    /**
     * Baut die komplette Ergebnistabelle (Kopf + Zeilen) für eine Liste von
     * Partien über das Partial "results_table". Ist $favTeamId gesetzt, wird die
     * entsprechende Mannschaft in jeder Zeile fett hervorgehoben (Lieblingsmannschaft).
     * Jede Zeile bekommt zusätzlich ein Vergleichs-Icon (direkter Vergleich der
     * beiden Teams, siehe renderH2hIcon()/renderH2hModalAssets()).
     */
    public static function renderResultsTable(array $partien, ?string $spieltagStart, ?int $favTeamId = null, bool $showLogos = false, bool $reverseHeim = false) : string
    {
        $rows = '';
        foreach ($partien as $partie) {
            $rows .= self::renderPartieRow($partie, $spieltagStart, $favTeamId, $showLogos, $reverseHeim);
        }
        return renderPartial('results_table', [
            'ColDatum'    => h(tf('liga_col_datum')),
            'ColHeim'     => h(tf('liga_col_heim')),
            'ColGast'     => h(tf('liga_col_gast')),
            'ColErgebnis' => h(tf('liga_col_ergebnis')),
            'Zeilen'      => $rows,
        ]) . \renderH2hModalAssets();
    }
    /**
     * Baut den Statistik-Block (Überschrift + Zeile) für eine Liste von Partien
     * über das Partial "stats_block".
     */
    public static function renderStatsBlock(string $heading, array $partien) : string
    {
        $stats = self::computeSpieltagStats($partien);
        return renderPartial('stats_block', [
            'StatsHeading' => h(tf('liga_stats_heading', ['label' => $heading])),
            'StatsLine'    => h(tf('liga_stats_line', [
                'heim'     => $stats['schnittHeim'],
                'gast'     => $stats['schnittGast'],
                'tore'     => $stats['tore'],
                'proSpiel' => $stats['toreProSpiel'],
            ])),
        ]);
    }
    /**
     * Baut das komplette Spieltag/Runden-Auswahl-Dropdown über die Partials
     * "spieltag_option" (je Eintrag) und "spieltag_picker" (Rahmen). Liefert
     * einen leeren String, wenn nur eine Runde/ein Spieltag existiert.
     */
    /**
     * Baut das komplette Spieltag/Runden-Auswahl-Dropdown über die Partials
     * "spieltag_option" (je Eintrag) und "spieltag_picker" (Rahmen). Liefert
     * einen leeren String, wenn nur eine Runde/ein Spieltag existiert.
     * $targetView bestimmt, auf welchen Reiter die Auswahl navigiert (Standard
     * 'ergebnisse', bisheriges Verhalten) - z.B. 'tabelle' für den
     * "Tabelle nach Spieltag N"-Picker (siehe renderStandingsView()).
     */
    public static function renderSpieltagPicker(array $allSpieltage, int $ligaId, ?int $currentNr, bool $isKO, int $maxNr, string $targetView = 'ergebnisse') : string
    {
        if (count($allSpieltage) <= 1) {
            return '';
        }
        $optionsHtml = '';
        foreach ($allSpieltage as $st) {
            $optionsHtml .= renderPartial('spieltag_option', [
                'Nummer'       => (int)$st['nummer'],
                'SelectedAttr' => (int)$st['nummer'] === $currentNr ? ' selected' : '',
                'Label'        => h(self::roundDisplayName($st, $isKO, $maxNr)),
            ]);
        }
        return renderPartial('spieltag_picker', [
            'PickerLabel' => h($isKO ? tf('liga_label_pick_round') : tf('liga_label_pick_matchday')),
            'LigaId'      => $ligaId,
            'View'        => $targetView,
            'Optionen'    => $optionsHtml,
        ]);
    }
    /**
     * Baut die Reiter-Navigation (Kalender/Ergebnisse/Spielpläne/Info) über die
     * Partials "tab_item" (je Reiter) und "tabs_bar" (Rahmen). Reiter, die laut
     * $flags nicht aktiviert sind, werden weggelassen.
     *
     * @param array  $flags      Rückgabe von getLigaViewFlags()
     * @param int    $ligaId
     * @param string $currentView Schlüssel des aktuell aktiven Reiters
     */
    /**
     * Baut die Tab-Leiste (Ergebnisse/Tabelle/Spielpläne/...). $activeNr
     * (falls gesetzt) wird als "&nr=N" an die Links zu "ergebnisse" und
     * "tabelle" angehängt - nur an diese beiden, da nur sie ?nr= lesen und
     * es dort dieselbe Bedeutung hat (welcher Spieltag). So bleibt beim
     * Wechsel zwischen den beiden Reitern derselbe Spieltag erhalten statt
     * immer auf den letzten zurückzuspringen.
     */
    public static function renderTabsBar(array $flags, int $ligaId, string $currentView, ?int $activeNr = null) : string
    {
        $labels = [
            'kalender'      => tf('liga_tab_kalender'),
            'ergebnisse'    => tf('liga_tab_ergebnisse'),
            'tabelle'       => tf('liga_tab_tabelle'),
            'spielplaene'   => tf('liga_tab_spielplaene'),
            'kreuztabelle'  => tf('liga_tab_kreuztabelle'),
            'fieberkurve'   => tf('liga_tab_fieberkurve'),
            'ligastatistik' => tf('liga_tab_ligastatistik'),
            'spielerstatistik' => tf('liga_tab_spielerstatistik'),
            'info'          => tf('liga_tab_info'),
        ];
        $nrAwareTabs = ['ergebnisse', 'tabelle'];
        $tabsHtml = '';
        foreach ($labels as $key => $label) {
            if (empty($flags[$key])) {
                continue;
            }
            $tabsHtml .= renderPartial('tab_item', [
                'ActiveClass' => $key === $currentView ? ' tab-item-active' : '',
                'LigaId'      => $ligaId,
                'ViewKey'     => $key,
                'Label'       => h($label),
                'NrParam'     => ($activeNr !== null && in_array($key, $nrAwareTabs, true)) ? ('&amp;nr=' . $activeNr) : '',
            ]);
        }
        return renderPartial('tabs_bar', ['Tabs' => $tabsHtml]);
    }
    /**
     * Baut die Info-Ansicht: eine allgemeine "Über LMOnext"-Seite (Version,
     * Copyright, Kurzbeschreibung, Lizenz) – analog zur Info-Seite des alten
     * LMO, die ebenfalls keine ligaspezifischen Daten zeigt, sondern
     * Informationen über die Software selbst.
     */
    public static function renderInfoView() : string
    {
        $version = getAppVersion();
        return renderPartial('info_view', [
            'Title'     => h(tf('liga_info_title', ['version' => $version])),
            'LinkHomepage' => tf('liga_info_link_homepage'),
            'LinkForum'    => tf('liga_info_link_forum'),
            'Text1'     => h(tf('liga_info_text_1')),
            'Text2'     => h(tf('liga_info_text_2')),
            'License'   => h(tf('liga_info_license')),
            'Copyright' => h(tf('liga_info_copyright')),
        ]);
    }
    /**
     * Übersetzter Monatsname (1-12).
     */
    public static function monthName(int $month) : string
    {
        return tf('liga_month_' . max(1, min(12, $month)));
    }
    /**
     * Baut die Kalender-Ansicht für einen Monat: Wochentagskopf + Wochen mit
     * Tageszellen, jede Zelle zeigt die an diesem Tag stattfindenden Spieltage/
     * Runden als kleine anklickbare Badges (Sprung zur jeweiligen Ergebnisliste).
     * Berücksichtigt nur Spieltage mit gesetztem Startdatum.
     */
    public static function renderKalenderView(array $allSpieltage, int $ligaId, bool $isKO, int $maxNr, int $year, int $month) : string
    {
        // Einträge je Tag sammeln (nur Spieltage mit gesetztem Startdatum)
        $entriesByDay = [];
        foreach ($allSpieltage as $st) {
            if (empty($st['start'])) {
                continue;
            }
            try {
                $dt = new \DateTime($st['start']);
            } catch (\Throwable) {
                continue;
            }
            if ((int)$dt->format('Y') !== $year || (int)$dt->format('n') !== $month) {
                continue;
            }
            $entriesByDay[(int)$dt->format('j')][] = $st;
        }
    
        $firstOfMonth = \DateTime::createFromFormat('Y-n-j', $year . '-' . $month . '-1');
        $daysInMonth  = (int)$firstOfMonth->format('t');
        $startWeekday = (int)$firstOfMonth->format('N'); // 1 (Montag) .. 7 (Sonntag)
        $today        = new \DateTime('today');
    
        $weekdayLabels = ['liga_weekday_mo', 'liga_weekday_di', 'liga_weekday_mi', 'liga_weekday_do',
                           'liga_weekday_fr', 'liga_weekday_sa', 'liga_weekday_so'];
        $headerHtml = '';
        foreach ($weekdayLabels as $key) {
            $headerHtml .= '<th>' . h(tf($key)) . '</th>';
        }
    
        $weeksHtml = '';
        $dayNum    = 1 - ($startWeekday - 1);
        while ($dayNum <= $daysInMonth) {
            $daysHtml = '';
            for ($w = 0; $w < 7; $w++, $dayNum++) {
                if ($dayNum < 1 || $dayNum > $daysInMonth) {
                    $daysHtml .= renderPartial('kalender_day', ['DayClass' => ' cal-empty', 'DayNum' => '', 'Entries' => '']);
                    continue;
                }
                $cellDate  = \DateTime::createFromFormat('Y-n-j', $year . '-' . $month . '-' . $dayNum);
                $isToday   = $cellDate->format('Y-m-d') === $today->format('Y-m-d');
                $entries   = $entriesByDay[$dayNum] ?? [];
                $entriesHtml = '';
                foreach ($entries as $st) {
                    $entriesHtml .= renderPartial('kalender_entry', [
                        'LigaId' => $ligaId,
                        'Nummer' => (int)$st['nummer'],
                        'Label'  => h(self::roundDisplayName($st, $isKO, $maxNr)),
                    ]);
                }
                $daysHtml .= renderPartial('kalender_day', [
                    'DayClass' => $isToday ? ' cal-today' : '',
                    'DayNum'   => (string)$dayNum,
                    'Entries'  => $entriesHtml,
                ]);
            }
            $weeksHtml .= renderPartial('kalender_week', ['Tage' => $daysHtml]);
        }
    
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear  = $month === 1 ? $year - 1 : $year;
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear  = $month === 12 ? $year + 1 : $year;
    
        return renderPartial('kalender_view', [
            'LigaId'    => $ligaId,
            'MonthYear' => h(self::monthName($month)) . ' ' . $year,
            'PrevYear'  => $prevYear,
            'PrevMonth' => $prevMonth,
            'NextYear'  => $nextYear,
            'NextMonth' => $nextMonth,
            'TodayLabel'=> h(tf('liga_kalender_today')),
            'TodayYear' => (int)$today->format('Y'),
            'TodayMonth'=> (int)$today->format('n'),
            'Weekdays'  => $headerHtml,
            'Wochen'    => $weeksHtml,
        ]);
    }
    /**
     * Baut die Spielpläne-Ansicht als klassischen Turnierbaum (nur KO-Ligen):
     * eine Spalte je Runde, mit den Paarungen dieser Runde (aggregiertes
     * Ergebnis über alle Spiele einer Paarung, z.B. bei Hin+Rück). Die
     * Paarungen werden per reorderBracketPairings() so sortiert, dass sie sich
     * optisch korrekt zwischen den Runden ausrichten.
     */
    public static function renderBracketView(int $ligaId, array $allSpieltage, bool $isKO, int $maxNr) : string
    {
        $opts        = self::getLigaOptions($ligaId);
        $showKickoff = self::ligaFlagEnabled($opts, 'DatM', false);
        $dateFormat  = $opts['DatF'] ?? 'd.m.Y H:i';
        $showLogos   = ($opts['ShowLogos'] ?? '0') === '1';
    
        // Erst alle Runden mit ihren Paarungsgruppen + repräsentativen Team-IDs sammeln
        $rounds = [];
        foreach ($allSpieltage as $st) {
            $partien = self::getSpieltagPartien((int)$st['id']);
            $groups  = self::groupPartienByPairing($partien);
    
            $pairings = [];
            foreach ($groups as $group) {
                $pairings[] = [
                    'heim_id'       => (int)($group[0]['heim_id'] ?? 0),
                    'gast_id'       => (int)($group[0]['gast_id'] ?? 0),
                    'group'         => $group,
                    'spieltagStart' => $st['start'] ?? null,
                ];
            }
            $rounds[] = ['roundName' => h(self::roundDisplayName($st, $isKO, $maxNr)), 'pairings' => $pairings];
        }
    
        // Nur die Paarungslisten fürs Umsortieren extrahieren, roundName separat behalten
        $pairingLists = array_map(static fn($r) => $r['pairings'], $rounds);
        $orderedLists = self::reorderBracketPairings($pairingLists);
    
        $roundsHtml = '';
        foreach ($rounds as $i => $round) {
            $pairingsHtml = '';
            foreach ($orderedLists[$i] as $pairing) {
                $group = $pairing['group'] ?? null;
                if (empty($group)) {
                    continue;
                }
                // Reine Leer-Paarungen (beide Seiten Dummy-Team "___" bzw. ganz
                // ohne Zuordnung, siehe partieIsEmptyPlaceholder()) werden auch
                // im Turnierbaum nicht angezeigt – das Layout ist eine reine
                // Box-Liste pro Runde ohne feste Positionen/Verbindungslinien,
                // ein Weglassen verschiebt also nichts anderes.
                if (self::partieIsEmptyPlaceholder($group[0])) {
                    continue;
                }
                $heimRaw = self::partieTeamName($group[0], 'heim');
                $gastRaw = self::partieTeamName($group[0], 'gast');
                $heim    = self::partieTeamNameWithLogo($group[0], 'heim', $showLogos);
                $gast    = self::partieTeamNameWithLogo($group[0], 'gast', $showLogos);
    
                $hTotal     = 0;
                $gTotal     = 0;
                $allPlayed  = !empty($group);
                $statusVal  = 0;
                foreach ($group as $p) {
                    if ($p['h_tore'] === null || $p['g_tore'] === null) {
                        $allPlayed = false;
                    } else {
                        $hTotal += (int)$p['h_tore'];
                        $gTotal += (int)$p['g_tore'];
                    }
                    if ((int)($p['status'] ?? 0) !== 0) {
                        $statusVal = (int)$p['status'];
                    }
                }
                $suffix = $allPlayed ? self::statusSuffix(['h_tore' => $hTotal, 'g_tore' => $gTotal, 'status' => $statusVal]) : '';
                $score  = $allPlayed ? h((string)$hTotal) . ' : ' . h((string)$gTotal) . h($suffix) : '- : -';
    
                $kickoff = '';
                if ($showKickoff) {
                    $raw = $group[0]['zeit'] ?? $pairing['spieltagStart'] ?? null;
                    if (!empty($raw)) {
                        try {
                            $kickoff = (new \DateTime($raw))->format($dateFormat);
                        } catch (\Throwable) {
                            $kickoff = '';
                        }
                    }
                }
    
                $pairingsHtml .= renderPartial('bracket_pairing', [
                    'Heim'        => $heim,
                    'Gast'        => $gast,
                    'Score'       => $score,
                    'CompareIcon' => \renderH2hIcon($pairing['heim_id'], $pairing['gast_id'], $heimRaw, $gastRaw, $showLogos),
                    'Kickoff'     => h($kickoff),
                ]);
            }
    
            $roundsHtml .= renderPartial('bracket_round', [
                'RoundName' => $round['roundName'],
                'Pairings'  => $pairingsHtml,
            ]);
        }
    
        return renderPartial('bracket_view', ['Rounds' => $roundsHtml]) . \renderH2hModalAssets();
    }
    /**
     * Baut die Liga-Tabelle.
     * - $uptoSpieltag (falls gesetzt) zeigt den Tabellenstand NACH genau
     *   diesem Spieltag (nur Partien mit _spieltag_nummer <= $uptoSpieltag
     *   zählen) - analog zur "Tabelle nach Spieltag X"-Ansicht bei kicker.de,
     *   inkl. "vorheriger/nächster Spieltag"-Navigation ober- und unterhalb
     *   der Tabelle. null (Standard) zeigt den aktuellen/finalen Stand.
     * - $tableMode wählt zwischen Gesamt-/Heim-/Auswärts-/Hin-/Rückrunden-
     *   Tabelle (Beitrag: Torsten Hofmann). Wirkt NACH dem Spieltag-Filter,
     *   d.h. "Rückrunde bis Spieltag 20" filtert erst auf <=20, dann auf die
     *   zweite Saisonhälfte innerhalb dieser Auswahl.
     * Zusätzliche Spalten "Form" (letzte 5 Spiele) und "Trend"
     * (Platzierungsänderung zum vorherigen Spieltag innerhalb der jeweils
     * aktiven Auswahl), ebenfalls Beitrag von Torsten Hofmann.
     */
    public static function renderStandingsView(int $ligaId, array $allSpieltage, ?int $uptoSpieltag = null, string $tableMode = 'gesamt') : string
    {
        $opts      = self::getLigaOptions($ligaId);
        $teams     = self::getLigaTeamsList($ligaId);
        $partien   = self::getAllLigaPartien($allSpieltage, $ligaId);
        $maxNr     = self::getMaxSpieltagNummer($allSpieltage);

        $nr = $uptoSpieltag;
        if ($nr === null || $nr < 1) {
            $nr = $maxNr;
        }
        if ($nr > $maxNr) {
            $nr = $maxNr;
        }
        if ($maxNr > 0) {
            $partien = array_values(array_filter($partien, static fn(array $p) => (int)($p['_spieltag_nummer'] ?? 0) <= $nr));
        }

        // Tabellen-Modus: gesamt/heim/gast/hin/rueck (Beitrag: Torsten Hofmann).
        // Wirkt NACH dem Spieltag-Filter oben, siehe Docblock.
        $validModes = ['gesamt', 'heim', 'gast', 'hin', 'rueck'];
        $tableMode  = in_array($tableMode, $validModes, true) ? $tableMode : 'gesamt';

        $partienForMode = $partien;
        if ($tableMode === 'hin' || $tableMode === 'rueck') {
            $half = intdiv($maxNr, 2);
            $partienForMode = array_filter($partienForMode, static function (array $p) use ($tableMode, $half) : bool {
                $pn = (int)($p['_spieltag_nummer'] ?? 0);
                return $tableMode === 'hin' ? $pn <= $half : $pn > $half;
            });
        }
        $csMode = match ($tableMode) {
            'heim'  => 'home',
            'gast'  => 'away',
            default => 'overall',
        };

        $rows      = self::computeStandings($teams, $partienForMode, $opts, $ligaId, $csMode, $nr);

        // Dynamische Tabellenspalten (Beitrag: Torsten Hofmann, Vorbild
        // volleyball-bundesliga.de) - Sportarten mit eigenen Darstellungs-
        // modi (aktuell nur Volleyball: kurz/mittel/vollständig) bekommen
        // eine eigene Tabellendarstellung mit sportartspezifischen Spalten
        // (3:0/3:1/3:2-Siege, Ballquotient, ...) statt der normalen
        // Sp/S/U/N/Tore/Diff/Pkt-Tabelle. Fußball & alle anderen Sportarten
        // ohne eigene Modi (leeres getDisplayModes()) nutzen unverändert
        // die bestehende Logik unten.
        $sportProfile = self::sportProfile($ligaId);
        if (!empty($sportProfile->getDisplayModes())) {
            return self::renderDynamicStandingsTable($rows, $sportProfile, $ligaId, $opts, $nr, $maxNr, $tableMode);
        }

        $favTeamId = self::resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));
        $totalTeams = count($rows);
        $showLogos  = ($opts['ShowLogos'] ?? '0') === '1';
    
        $showMinuspunkte = ($opts['MinusPoints'] ?? '0') === '1';
        $footnoteNrs = self::assignStrafFootnotes($rows);

        $formByTeam  = self::computeLast5Form($partienForMode, $csMode);
        $trendByTeam = self::computePositionTrend($teams, $partienForMode, $opts, $ligaId, $csMode);

        $rowsHtml = '';
        foreach ($rows as $i => $r) {
            $diff = $r['tore_h'] - $r['tore_g'];
            $markerColor = self::computeStandingsMarkerColor($i, $totalTeams, $opts);
            $tid = (int)$r['id'];

            $trend = $trendByTeam[$tid] ?? ['direction' => 'same', 'delta' => 0];
            $trendHtml = match ($trend['direction']) {
                'up'    => '<span class="trend-arrow trend-up" title="+' . $trend['delta'] . '">&#9650;</span>',
                'down'  => '<span class="trend-arrow trend-down" title="' . $trend['delta'] . '">&#9660;</span>',
                default => '<span class="trend-arrow trend-same">&ndash;</span>',
            };

            $rowsHtml .= renderPartial('standings_row', [
                'Platz'    => (string)($i + 1),
                'Logo'     => self::renderTeamLogoImgWrapped($tid, $showLogos),
                'Team'     => h($r['name']),
                'TeamClass'=> ($favTeamId !== null && $r['id'] === $favTeamId) ? ' fav-team' : '',
                'StrafHinweis' => self::renderStrafHinweis($r, $footnoteNrs[$tid] ?? 0),
                'RowStyle' => $markerColor !== '' ? ' style="border-left-color:' . h($markerColor) . '"' : '',
                'Sp'       => (string)$r['sp'],
                'S'        => (string)$r['s'],
                'U'        => (string)$r['u'],
                'N'        => (string)$r['n'],
                'Tore'     => $r['tore_h'] . ':' . $r['tore_g'],
                'Diff'     => ($diff > 0 ? '+' : '') . $diff,
                'DiffClass'=> $diff > 0 ? ' diff-pos' : ($diff < 0 ? ' diff-neg' : ''),
                'Pkt'      => $showMinuspunkte ? ($r['pkt'] . ':' . $r['minuspunkte']) : (string)$r['pkt'],
                'Form'     => $formByTeam[$tid]['dots'] ?? '',
                'Trend'    => $trendHtml,
            ]);
        }

        $spieltagNav = self::renderStandingsSpieltagNav($ligaId, $nr, $maxNr, $tableMode);
        $modeNav     = self::renderStandingsModeNav($ligaId, $tableMode, $nr, $maxNr);

        return $modeNav . renderPartial('standings_view', [
            'ColPlatz'    => h(tf('liga_standings_col_platz')),
            'ColTeam'     => h(tf('liga_standings_col_team')),
            'ColSp'       => h(tf('liga_standings_col_sp')),
            'ColS'        => h(tf('liga_standings_col_s')),
            'ColU'        => h(tf('liga_standings_col_u')),
            'ColN'        => h(tf('liga_standings_col_n')),
            'ColTore'     => h(tf('liga_standings_col_tore')),
            'ColDiff'     => h(tf('liga_standings_col_diff')),
            'ColPkt'      => h(tf('liga_standings_col_pkt')),
            'ColForm'     => h(tf('liga_standings_col_form')),
            'ColTrend'    => h(tf('liga_standings_col_trend')),
            'Rows'        => $rowsHtml,
            'Fussnoten'   => self::renderStrafFootnotes($rows, $footnoteNrs),
            'SpieltagNavOben'  => $spieltagNav,
            'SpieltagNavUnten' => $spieltagNav,
        ]);
    }

    /**
     * Rendert die Gesamt/Heim/Auswärts/Hin-/Rückrunde-Umschalter als Link-
     * Leiste (Beitrag: Torsten Hofmann, hier um die Spieltag-Auswahl $nr
     * ergänzt, damit sie beim Moduswechsel erhalten bleibt).
     */
    /**
     * Tabelle mit sportartspezifischen, umschaltbaren Spalten (Beitrag:
     * Torsten Hofmann, Vorbild volleyball-bundesliga.de: "Tabellendarstellung:
     * kurz | mittel | vollständig"). Wird von renderStandingsView() nur für
     * Sportarten mit eigenen Darstellungsmodi (SportProfile::getDisplayModes())
     * aufgerufen - Fußball bleibt komplett unberührt. Baut die Tabelle als
     * eigenständiges HTML statt über renderPartial('standings_row', ...),
     * da die Spaltenzahl/-reihenfolge hier variabel ist. Nutzt dieselben
     * CSS-Klassen wie die normale Tabelle (card/table-scroll/standings-table/
     * st-*), damit sie optisch identisch aussieht.
     */
    private static function renderDynamicStandingsTable(array $rows, \LMOnext\Sport\SportProfile $sportProfile, int $ligaId, array $opts, int $nr, int $maxNr, string $tableMode) : string
    {
        $modes = $sportProfile->getDisplayModes(); // z.B. ['short','medium','long']
        $tmode = $_GET['tmode'] ?? $modes[0];
        if (!in_array($tmode, $modes, true)) {
            $tmode = $modes[0];
        }
        $columns = $sportProfile->getStandingsColumnsForMode($tmode);

        $favTeamId   = self::resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));
        $footnoteNrs = self::assignStrafFootnotes($rows);
        $totalTeams  = count($rows);

        $nrParam = ($nr > 0 && $nr < $maxNr) ? ('&nr=' . $nr) : '';

        // "Tabellendarstellung: kurz mittel vollständig" - Klartext-Links,
        // aktiver Modus fett, exakt wie im Vorbild.
        $modeLabels = ['short' => 'kurz', 'medium' => 'mittel', 'long' => 'vollständig'];
        $darstellungLinks = [];
        foreach ($modes as $m) {
            $label = h($modeLabels[$m] ?? $m);
            $url = '?id=' . $ligaId . '&view=tabelle&table=' . $tableMode . $nrParam . '&tmode=' . $m;
            $darstellungLinks[] = $m === $tmode
                ? '<strong>' . $label . '</strong>'
                : '<a href="' . h($url) . '">' . $label . '</a>';
        }
        $darstellungHtml = '<p class="standings-tmode-nav">' . h(tf('liga_standings_darstellung')) . ': '
                          . implode(' ', $darstellungLinks) . '</p>';

        $theadHtml = '<th class="st-platz">' . h(tf('liga_standings_col_platz')) . '</th><th class="st-team">' . h(tf('liga_standings_col_team')) . '</th>';
        foreach ($columns as $col) {
            if ($tmode === 'long' && !empty($col['diag'])) {
                $theadHtml .= '<th class="' . h($col['class']) . ' st-diag"><span>' . h($col['label']) . '</span></th>';
            } else {
                $theadHtml .= '<th class="' . h($col['class']) . '">' . h($col['label']) . '</th>';
            }
        }

        $rowsHtml = '';
        foreach ($rows as $i => $r) {
            $markerColor = self::computeStandingsMarkerColor($i, $totalTeams, $opts);
            $tid = (int)$r['id'];
            $rowStyle = $markerColor !== '' ? ' style="border-left-color:' . h($markerColor) . '"' : '';
            $teamClass = ($favTeamId !== null && $r['id'] === $favTeamId) ? ' fav-team' : '';

            $rowsHtml .= '<tr' . $rowStyle . '>'
                       . '<td class="st-platz">' . ($i + 1) . '</td>'
                       . '<td class="st-team' . $teamClass . '">' . h($r['name']) . self::renderStrafHinweis($r, $footnoteNrs[$tid] ?? 0) . '</td>';
            foreach ($columns as $col) {
                $rowsHtml .= '<td class="' . h($col['class']) . '">' . h(self::resolveStandingsCell($r, $col['key'])) . '</td>';
            }
            $rowsHtml .= '</tr>';
        }

        $spieltagNav = self::renderStandingsSpieltagNav($ligaId, $nr, $maxNr, $tableMode);
        $modeNav     = self::renderStandingsModeNav($ligaId, $tableMode, $nr, $maxNr);

        return $modeNav . '<div class="card">' . $spieltagNav
             . '<div class="table-scroll"><table class="standings-table"><thead><tr>' . $theadHtml . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table></div>'
             . self::renderStrafFootnotes($rows, $footnoteNrs)
             . $darstellungHtml
             . $spieltagNav . '</div>';
    }

    /**
     * Liefert den Anzeigewert einer dynamischen Tabellenspalte für eine
     * Tabellenzeile (siehe renderDynamicStandingsTable()). Volleyball-
     * spezifische Schlüssel (w30/w31/w32/... , bquot/bverh/squot/sverh,
     * p3/p2/p1/p0) werden aus den von computeStandings() gelieferten
     * Zusatzfeldern abgeleitet; unbekannte Schlüssel geben einen Leerstring
     * zurück statt eines Fehlers, damit ein künftiges Sport-Profil mit
     * anderen Spalten nicht crasht.
     *
     * Public (nicht private): wird auch von PdfExporter::exportTabellePdf()
     * wiederverwendet, damit der PDF-Export exakt dieselben Zellwerte wie
     * die HTML-Ansicht zeigt, statt die Auflösung ein zweites Mal zu
     * duplizieren (siehe dort für den Hintergrund: PDF zeigte bislang immer
     * die feste Fußball-Spaltenauswahl Sp/S/U/N/Tore/Diff/Pkt, unabhängig
     * von der tatsächlichen Sportart der Liga).
     */
    public static function resolveStandingsCell(array $r, string $key) : string
    {
        return match ($key) {
            'sp'    => (string)$r['sp'],
            's'     => (string)$r['s'],
            'u'     => (string)$r['u'],
            'n'     => (string)$r['n'],
            'tore'  => $r['tore_h'] . ':' . $r['tore_g'],
            'diff'  => (static function () use ($r) : string {
                $d = $r['tore_h'] - $r['tore_g'];
                return ($d > 0 ? '+' : '') . $d;
            })(),
            'pkt'   => (string)$r['pkt'],
            'w30'   => (string)($r['w30'] ?? 0),
            'w31'   => (string)($r['w31'] ?? 0),
            'w32'   => (string)($r['w32'] ?? 0),
            'l23'   => (string)($r['l23'] ?? 0),
            'l13'   => (string)($r['l13'] ?? 0),
            'l03'   => (string)($r['l03'] ?? 0),
            // "3-Punkte-Erfolge" (3:0+3:1), "2-Punkte-Erfolg" (3:2),
            // "1-Punkt-Niederlage" (2:3), "0-Punkte-Niederlagen" (1:3+0:3) -
            // Zusammenfassung der Satzergebnisse nach Punktwert.
            'p3'    => (string)(($r['w30'] ?? 0) + ($r['w31'] ?? 0)),
            'p2'    => (string)($r['w32'] ?? 0),
            'p1'    => (string)($r['l23'] ?? 0),
            'p0'    => (string)(($r['l13'] ?? 0) + ($r['l03'] ?? 0)),
            'bquot' => self::formatQuotient($r['balls_h'] ?? 0, $r['balls_g'] ?? 0),
            'bverh' => ($r['balls_h'] ?? 0) . ':' . ($r['balls_g'] ?? 0),
            'squot' => self::formatQuotient($r['tore_h'] ?? 0, $r['tore_g'] ?? 0),
            'sverh' => $r['tore_h'] . ':' . $r['tore_g'],
            default => '',
        };
    }

    /**
     * Quotient zweier Werte, deutsches Zahlenformat mit 3 Nachkommastellen
     * (Vorbild volleyball-bundesliga.de: "1,191"). 0:0 ergibt "0,000" statt
     * einer Division durch Null.
     */
    private static function formatQuotient(int $a, int $b) : string
    {
        if ($b === 0) {
            return $a === 0 ? '0,000' : '∞';
        }
        return number_format($a / $b, 3, ',', '');
    }

    private static function renderStandingsModeNav(int $ligaId, string $activeMode, int $nr, int $maxNr) : string
    {
        $modes = [
            'gesamt' => 'liga_standings_nav_gesamt',
            'heim'   => 'liga_standings_nav_heim',
            'gast'   => 'liga_standings_nav_gast',
            'hin'    => 'liga_standings_nav_hin',
            'rueck'  => 'liga_standings_nav_rueck',
        ];
        $nrParam = ($nr > 0 && $nr < $maxNr) ? ('&nr=' . $nr) : '';
        $html = '<div class="standings-nav">';
        foreach ($modes as $mode => $langKey) {
            $url = '?id=' . $ligaId . '&view=tabelle&table=' . $mode . $nrParam;
            $cls = $mode === $activeMode ? ' standings-nav-active' : '';
            $html .= '<a class="standings-nav-item' . $cls . '" href="' . h($url) . '">' . h(tf($langKey)) . '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * "← vorheriger Spieltag" / "nächster Spieltag →"-Navigation für die
     * Tabellen-nach-Spieltag-Ansicht, analog zu kicker.de. Am ersten
     * Spieltag fehlt der "vorheriger"-Link, am letzten der "nächster"-Link.
     */
    private static function renderStandingsSpieltagNav(int $ligaId, int $nr, int $maxNr, string $tableMode = 'gesamt') : string
    {
        if ($maxNr <= 1) {
            return '';
        }
        $modeParam = $tableMode !== 'gesamt' ? ('&table=' . $tableMode) : '';
        $prev = $nr > 1
            ? '<a class="st-spieltag-nav-prev" href="?id=' . $ligaId . '&view=tabelle&nr=' . ($nr - 1) . $modeParam . '">&larr; ' . h(tf('liga_standings_vorheriger_spieltag')) . '</a>'
            : '';
        $next = $nr < $maxNr
            ? '<a class="st-spieltag-nav-next" href="?id=' . $ligaId . '&view=tabelle&nr=' . ($nr + 1) . $modeParam . '">' . h(tf('liga_standings_naechster_spieltag')) . ' &rarr;</a>'
            : '';
        if ($prev === '' && $next === '') {
            return '';
        }
        return '<div class="st-spieltag-nav">' . $prev . $next . '</div>';
    }
    /**
     * Baut die Team-Spielplan-Ansicht für reguläre Ligen: Sidebar mit allen
     * Team-Kurznamen + (bei Auswahl) alle Partien dieses Teams über die ganze
     * Saison, chronologisch, mit fett hervorgehobenem eigenem Team.
     */
    public static function renderTeamScheduleView(int $ligaId, array $allSpieltage, ?int $selectedTeamId) : string
    {
        $teams     = self::getLigaTeamsList($ligaId);
        $showLogos = (self::getLigaOptions($ligaId)['ShowLogos'] ?? '0') === '1';
        // Team-Auswahl sportartabhängig (auf Wunsch: Dropdown nur bei
        // Volleyball, alle anderen Sportarten behalten die bisherige
        // Sidebar-Liste bei) - Vorbild für das Dropdown: Torsten Hofmanns
        // Vorschlag, hier an mein Kartenlayout angepasst statt 1:1 übernommen.
        $useDropdownPicker = self::getLigaSportType($ligaId) === 'volleyball';

        $sidebarHtml = '';
        if ($useDropdownPicker) {
            $sidebarHtml = '<select onchange="if(this.value)window.location.href=this.value" class="schedule-picker-select">'
                         . '<option value="">' . h(tf('liga_schedule_pick_team')) . '</option>';
            foreach ($teams as $t) {
                $tid   = (int)$t['id'];
                $url   = 'liga.php?id=' . $ligaId . '&view=spielplaene&team=' . $tid;
                $sel   = $tid === $selectedTeamId ? ' selected' : '';
                $label = h($t['mittel'] !== '' ? $t['mittel'] : $t['name']);
                $sidebarHtml .= '<option value="' . $url . '"' . $sel . '>' . $label . '</option>';
            }
            $sidebarHtml .= '</select>';
        } else {
            foreach ($teams as $t) {
                $sidebarHtml .= renderPartial('team_sidebar_item', [
                    'ActiveClass' => ((int)$t['id'] === $selectedTeamId) ? ' team-sidebar-active' : '',
                    'LigaId'      => $ligaId,
                    'TeamId'      => (int)$t['id'],
                    'Logo'        => self::renderTeamLogoImg((int)$t['id'], $showLogos),
                    'Kurz'        => h($t['mittel'] !== '' ? $t['mittel'] : $t['name']),
                ]);
            }
        }
    
        if ($selectedTeamId === null) {
            $contentHtml = '<p class="empty-msg">' . h(tf('liga_schedule_pick_team')) . '</p>';
        } else {
            $partien = self::getAllLigaPartien($allSpieltage, $ligaId);
            $rowsHtml = '';
            foreach ($partien as $p) {
                $hId = (int)($p['heim_id'] ?? 0);
                $gId = (int)($p['gast_id'] ?? 0);
                if ($hId !== $selectedTeamId && $gId !== $selectedTeamId) {
                    continue;
                }
                $gespielt = $p['h_tore'] !== null && $p['g_tore'] !== null;
                $score    = self::formatScore($p, $p['_liga_id'] ?? $ligaId, true);
                $heimRaw = self::partieTeamName($p, 'heim');
                $gastRaw = self::partieTeamName($p, 'gast');
                $rowsHtml .= renderPartial('team_schedule_row', [
                    'Nr'           => (string)$p['_spieltag_nummer'],
                    'Datum'        => h(self::partieZeitDisplay($p, null)),
                    'HeimClass'    => $hId === $selectedTeamId ? ' schedule-own' : '',
                    'GastClass'    => $gId === $selectedTeamId ? ' schedule-own' : '',
                    'Heim'         => self::partieTeamNameWithLogoReversed($p, 'heim', $showLogos),
                    'Gast'         => self::partieTeamNameWithLogo($p, 'gast', $showLogos),
                    'Ergebnis'     => $score,
                    'ErgebnisOffenClass' => $gespielt ? '' : ' ergebnis-offen',
                    'CompareIcon'  => \renderH2hIcon($hId, $gId, $heimRaw, $gastRaw, $showLogos),
                ]);
            }
            $contentHtml = renderPartial('team_schedule_table', ['Rows' => $rowsHtml]) . \renderH2hModalAssets();
        }

        if ($useDropdownPicker) {
            return '<div class="card schedule-picker-card">' . $sidebarHtml . '</div><div class="card">' . $contentHtml . '</div>';
        }
        return renderPartial('team_schedule_view', [
            'Sidebar' => $sidebarHtml,
            'Content' => $contentHtml,
        ]);
    }
    /**
     * Baut die Kreuztabelle: N×N-Gitter aller Teams (Heim = Zeilen, Gast =
     * Spalten), sortiert nach aktueller Tabellenposition. Jede Zelle zeigt das
     * Ergebnis der jeweiligen Heim-gegen-Gast-Begegnung (leer, falls noch nicht
     * gespielt; Diagonale immer leer).
     */
    public static function renderKreuztabelleView(int $ligaId, array $allSpieltage) : string
    {
        $opts      = self::getLigaOptions($ligaId);
        $teams     = self::getLigaTeamsList($ligaId);
        $partien   = self::getAllLigaPartien($allSpieltage, $ligaId);
        $standing  = self::computeStandings($teams, $partien, $opts, $ligaId);
        $favTeamId = self::resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));
        $showLogos = ($opts['ShowLogos'] ?? '0') === '1';
    
        $mittelById = [];
        foreach ($teams as $t) {
            $mittelById[(int)$t['id']] = $t['mittel'] ?? '';
        }
    
        // Lookup: "heimId_gastId" => letzte/aggregierte Begegnung dieser Richtung
        $lookup = [];
        foreach ($partien as $p) {
            $hId = (int)($p['heim_id'] ?? 0);
            $gId = (int)($p['gast_id'] ?? 0);
            if ($hId <= 0 || $gId <= 0) {
                continue;
            }
            $lookup[$hId . '_' . $gId] = $p;
        }
    
        $headerCells = '';
        foreach ($standing as $t) {
            // Bei aktivierter Logo-Einstellung steht in der Kopfzeile NUR das
            // Logo (kein Kürzel-Text mehr) – sonst wie bisher das Kürzel.
            $kurz = $t['kurz'] ?? '';
            $headerLabel = $showLogos
                ? self::renderTeamLogoImg((int)$t['id'], true)
                : h($kurz !== '' ? $kurz : $t['name']);
            $headerCells .= renderPartial('kreuz_header_cell', [
                'Label'       => $headerLabel,
                'HeaderClass' => ($favTeamId !== null && $t['id'] === $favTeamId) ? ' kz-fav' : '',
                'TeamId'      => (string)$t['id'],
            ]);
        }
    
        $bodyRows = '';
        foreach ($standing as $rowTeam) {
            $isFavRow  = $favTeamId !== null && $rowTeam['id'] === $favTeamId;
            $cellsHtml = '';
            foreach ($standing as $colTeam) {
                $isFavCol = $favTeamId !== null && $colTeam['id'] === $favTeamId;
                $favClass = ($isFavRow ? ' kz-fav-row' : '') . ($isFavCol ? ' kz-fav-col' : '');
                $cellVars = ['RowTeamId' => (string)$rowTeam['id'], 'ColTeamId' => (string)$colTeam['id']];
                if ($rowTeam['id'] === $colTeam['id']) {
                    $cellsHtml .= renderPartial('kreuz_cell', $cellVars + ['CellClass' => ' kz-diag' . $favClass, 'Content' => '']);
                    continue;
                }
                $p = $lookup[$rowTeam['id'] . '_' . $colTeam['id']] ?? null;
                if ($p === null || $p['h_tore'] === null || $p['g_tore'] === null) {
                    $cellsHtml .= renderPartial('kreuz_cell', $cellVars + ['CellClass' => $favClass, 'Content' => '']);
                } else {
                    $content = self::formatScore($p, $p['_liga_id'] ?? $ligaId, false);
                    $cellsHtml .= renderPartial('kreuz_cell', $cellVars + ['CellClass' => $favClass, 'Content' => $content]);
                }
            }
            $bodyRows .= renderPartial('kreuz_row', [
                'Label'          => $showLogos
                    ? self::renderTeamLogoImg((int)$rowTeam['id'], true) . h($mittelById[(int)$rowTeam['id']] !== '' ? $mittelById[(int)$rowTeam['id']] : $rowTeam['name'])
                    : h($rowTeam['name']),
                'RowLabelClass'  => $isFavRow ? ' kz-fav' : '',
                'TeamId'         => (string)$rowTeam['id'],
                'Cells'          => $cellsHtml,
            ]);
        }
    
        // Klick auf Spalten-Kopf oder Zeilen-Label hebt diese Mannschaft hervor
        // (ersetzt eine ggf. serverseitig vorgegebene favTeam-Hervorhebung). Ohne
        // hinterlegte Lieblingsmannschaft ist beim Aufruf noch nichts markiert.
        $script = '<script>(function(){'
            . 'var t=document.querySelectorAll(".kreuz-table th.kz-col,.kreuz-table th.kz-rowlabel");'
            . 'function clear(){document.querySelectorAll(".kreuz-table .kz-fav,.kreuz-table .kz-fav-row,.kreuz-table .kz-fav-col")'
            . '.forEach(function(el){el.classList.remove("kz-fav","kz-fav-row","kz-fav-col");});}'
            . 'function apply(id){'
            . 'document.querySelectorAll(\'.kreuz-table th[data-team="\'+id+\'"]\').forEach(function(el){el.classList.add("kz-fav");});'
            . 'document.querySelectorAll(\'.kreuz-table td[data-row="\'+id+\'"]\').forEach(function(el){el.classList.add("kz-fav-row");});'
            . 'document.querySelectorAll(\'.kreuz-table td[data-col="\'+id+\'"]\').forEach(function(el){el.classList.add("kz-fav-col");});'
            . '}'
            . 't.forEach(function(el){el.style.cursor="pointer";el.addEventListener("click",function(){'
            . 'var id=el.getAttribute("data-team");clear();apply(id);'
            . '});});'
            . '})();</script>';
    
        return renderPartial('kreuz_view', [
            'HeaderCells' => $headerCells,
            'BodyRows'    => $bodyRows,
        ]) . $script;
    }
    /**
     * Feste Farbpalette für die Fieberkurven-Linien (zyklisch, falls mehr Teams
     * als Farben vorhanden sind).
     */
    public static function fieberkurveColors() : array
    {
        return [
            '#2563eb', '#dc2626', '#16a34a', '#9333ea', '#ea580c', '#0891b2',
            '#ca8a04', '#db2777', '#4338ca', '#65a30d', '#7c2d12', '#0d9488',
            '#be123c', '#4d7c0f', '#6d28d9', '#b45309', '#0369a1', '#a21caf',
        ];
    }
    /**
     * Baut die Fieberkurve: Liniendiagramm der Tabellenposition jedes Teams über
     * die gespielten Spieltage hinweg (Position 1 oben, wächst nach unten – wie
     * ein "Fieberthermometer"). Reines SVG, keine externe Chart-Bibliothek.
     */
    public static function renderFieberkurveView(int $ligaId, array $allSpieltage) : string
    {
        $opts  = self::getLigaOptions($ligaId);
        $teams = self::getLigaTeamsList($ligaId);
        if (empty($teams)) {
            return renderPartial('fieberkurve_view', ['Content' => '<p class="empty-msg">' . h(tf('liga_fieberkurve_no_data')) . '</p>']);
        }
    
        usort($allSpieltage, static fn($a, $b) => (int)$a['nummer'] <=> (int)$b['nummer']);
    
        $positionsByTeam = [];
        $matchdays       = [];
        $cumulative      = [];
    
        foreach ($allSpieltage as $st) {
            $nr        = (int)$st['nummer'];
            $stPartien = self::getSpieltagPartien((int)$st['id']);
            $hasPlayed = false;
            foreach ($stPartien as $p) {
                if ($p['h_tore'] !== null && $p['g_tore'] !== null) {
                    $hasPlayed = true;
                    break;
                }
            }
            $cumulative = array_merge($cumulative, $stPartien);
            if (!$hasPlayed) {
                continue;
            }
            $standing    = self::computeStandings($teams, $cumulative, $opts, $ligaId);
            $matchdays[] = $nr;
            foreach ($standing as $i => $row) {
                $positionsByTeam[$row['id']]['name'] = $row['name'];
                $positionsByTeam[$row['id']]['pos'][$nr] = $i + 1;
            }
        }
    
        if (empty($matchdays)) {
            return renderPartial('fieberkurve_view', ['Content' => '<p class="empty-msg">' . h(tf('liga_fieberkurve_no_data')) . '</p>']);
        }
    
        $numTeams = count($teams);
        $minMd    = min($matchdays);
        $maxMd    = max($matchdays);
        $allMd    = range($minMd, $maxMd);
    
        $colors  = self::fieberkurveColors();
        $i       = 0;
        $datasets = [];
        foreach ($positionsByTeam as $teamId => $data) {
            $color = $colors[$i % count($colors)];
            $series = [];
            foreach ($allMd as $md) {
                $series[] = $data['pos'][$md] ?? null;
            }
            $datasets[] = [
                'label'           => $data['name'],
                'data'            => $series,
                'borderColor'     => $color,
                'backgroundColor' => $color,
                'spanGaps'        => true,
                'borderWidth'     => 2,
                'pointRadius'     => 0,
                'pointHoverRadius' => 4,
                'tension'         => 0.35,
                'hidden'          => $i >= 2,
            ];
            $i++;
        }
    
        $chartData = [
            'labels'   => $allMd,
            'datasets' => $datasets,
        ];
        $chartJson = str_replace('</script>', '<\/script>', json_encode($chartData, JSON_UNESCAPED_UNICODE));
    
        $canvasId = 'fk-canvas-' . $ligaId;
        $content  = '<script src="assets/vendor/chart.umd.min.js"></script>';
        $content .= '<div class="fk-chart-wrap"><canvas id="' . h($canvasId) . '"></canvas></div>';
        $content .= '<script>';
        $content .= '(function(){var ctx=document.getElementById(' . json_encode($canvasId) . ');';
        $content .= 'new Chart(ctx,{type:"line",data:' . $chartJson . ',options:{';
        $content .= 'responsive:true,maintainAspectRatio:false,interaction:{mode:"nearest",intersect:false},';
        $content .= 'scales:{y:{reverse:true,min:1,max:' . $numTeams . ',ticks:{stepSize:1}},x:{title:{display:true,text:' . json_encode(tf('liga_col_spieltag_short')) . '}}},';
        $content .= 'plugins:{legend:{position:"top",labels:{boxWidth:12,font:{size:11}}}}';
        $content .= '}});})();';
        $content .= '</script>';
    
        return renderPartial('fieberkurve_view', ['Content' => $content]);
    }
    /**
     * Baut eine einzelne Team-Statistik-Box (Position, Punkte, Siege/
     * Niederlagen inkl. Extremwerten, aktuelle Serie, Restprogramm).
     */
    public static function renderTeamStatBox(array $stat, int $teamId = 0, bool $showLogos = false) : string
    {
        $bw = $stat['bestWin'];
        $bestWinTxt = $bw
            ? h($stat['name']) . ' ' . h((string)$bw['own']) . ':' . h((string)$bw['opp']) . ' ' . h($bw['oppName']) . ' (' . ($bw['heim'] ? h(tf('liga_stat_home')) : h(tf('liga_stat_away'))) . ', ' . (int)$bw['nr'] . '. ' . h(tf('liga_col_spieltag_short')) . ')'
            : '–';
        $bl = $stat['bestLoss'];
        $bestLossTxt = $bl
            ? h($stat['name']) . ' ' . h((string)$bl['own']) . ':' . h((string)$bl['opp']) . ' ' . h($bl['oppName']) . ' (' . ($bl['heim'] ? h(tf('liga_stat_home')) : h(tf('liga_stat_away'))) . ', ' . (int)$bl['nr'] . '. ' . h(tf('liga_col_spieltag_short')) . ')'
            : '–';
    
        $remainingTxt = '–';
        if (!empty($stat['remaining'])) {
            $parts = [];
            foreach ($stat['remaining'] as $r) {
                $parts[] = h($r['opp']) . ' (' . ($r['heim'] ? h(tf('liga_stat_home_short')) : h(tf('liga_stat_away_short'))) . ')';
            }
            $remainingTxt = implode(', ', $parts);
        }
    
        $streakTxt = !empty($stat['streakLines']) ? implode('<br>', array_map('h', $stat['streakLines'])) : '–';
    
        $html  = '<div class="ligastat-box">';
        $html .= '<h3>' . self::renderTeamLogoImg($teamId, $showLogos) . h($stat['name']) . '</h3>';
        $html .= '<table class="ligastat-kv">';
        $html .= '<tr><td>' . h(tf('liga_stat_position')) . '</td><td>' . h((string)$stat['position']) . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_points')) . '</td><td>' . h((string)$stat['pkt']) . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_played')) . '</td><td>' . h((string)$stat['sp']) . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_ppg')) . '</td><td>' . h((string)$stat['ppg']) . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_goals')) . '</td><td>' . h((string)$stat['toreH']) . ':' . h((string)$stat['toreG']) . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_goals_per_game')) . '</td><td>' . h($stat['goalsPerGame']) . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_wins')) . '</td><td>' . h((string)$stat['wins']) . ' (' . h((string)$stat['winPct']) . '%)</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_best_win')) . '</td><td>' . $bestWinTxt . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_losses')) . '</td><td>' . h((string)$stat['losses']) . ' (' . h((string)$stat['lossPct']) . '%)</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_worst_loss')) . '</td><td>' . $bestLossTxt . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_current_streak')) . '</td><td>' . $streakTxt . '</td></tr>';
        $html .= '<tr><td>' . h(tf('liga_stat_remaining')) . '</td><td>' . $remainingTxt . '</td></tr>';
        $html .= '</table></div>';
    
        return $html;
    }
    /**
     * Baut den immer sichtbaren "Statistische Daten zur Liga"-Block: Spiele,
     * Tore, Extremwerte, Serien-Rekorde (ligaweit).
     */
    public static function renderOverallStatsBlock(array $teams, array $partien) : string
    {
        $totalGames = 0;
        $homeWins   = 0;
        $draws      = 0;
        $awayWins   = 0;
        $totalGoals = 0;
        $homeGoals  = 0;
        $awayGoals  = 0;
    
        foreach ($partien as $p) {
            if ($p['h_tore'] === null || $p['g_tore'] === null) {
                continue;
            }
            $h = (int)$p['h_tore'];
            $g = (int)$p['g_tore'];
            $totalGames++;
            $homeGoals  += $h;
            $awayGoals  += $g;
            $totalGoals += $h + $g;
            if ($h > $g) {
                $homeWins++;
            } elseif ($h < $g) {
                $awayWins++;
            } else {
                $draws++;
            }
        }
    
        $pct = static fn(int $n, int $total) : string => $total > 0 ? round($n / $total * 100) . '%' : '0%';
        $avg = static fn(int $n, int $total) : string => $total > 0 ? (string)round($n / $total, 2) : '0';
    
        $extremes = self::findExtremeMatches($partien);
        $matchLine = static function (array $p) : string {
            return h(self::partieTeamName($p, 'heim')) . ' - ' . h(self::partieTeamName($p, 'gast')) . '&nbsp;&nbsp;'
                 . self::formatScore($p, $p['_liga_id'] ?? null, false)
                 . ' (' . (int)$p['_spieltag_nummer'] . '.)';
        };
    
        $html  = '<div class="card"><h2>' . h(tf('liga_stat_overall_title')) . '</h2>';
        $html .= '<table class="ligastat-overall">';
        $html .= '<tr><th>' . h(tf('liga_stat_games')) . '</th><th>' . h(tf('liga_stat_home_wins')) . '</th><th>' . h(tf('liga_stat_draws')) . '</th><th>' . h(tf('liga_stat_away_wins')) . '</th></tr>';
        $html .= '<tr><td><strong>' . $totalGames . '</strong></td><td>' . $homeWins . ' (' . $pct($homeWins, $totalGames) . ')</td><td>' . $draws . ' (' . $pct($draws, $totalGames) . ')</td><td>' . $awayWins . ' (' . $pct($awayWins, $totalGames) . ')</td></tr>';
        $html .= '</table>';
    
        $html .= '<table class="ligastat-overall">';
        $html .= '<tr><th>' . h(tf('liga_stat_goals_total')) . '</th><th>' . h(tf('liga_stat_home_goals')) . '</th><th>' . h(tf('liga_stat_away_goals')) . '</th></tr>';
        $html .= '<tr><td><strong>' . $totalGoals . '</strong> (Ø ' . $avg($totalGoals, $totalGames) . ')</td><td>' . $homeGoals . ' (' . $pct($homeGoals, $totalGoals) . ', Ø ' . $avg($homeGoals, $totalGames) . ')</td><td>' . $awayGoals . ' (' . $pct($awayGoals, $totalGoals) . ', Ø ' . $avg($awayGoals, $totalGames) . ')</td></tr>';
        $html .= '</table>';
    
        if (!empty($extremes['homeWins'])) {
            $html .= '<p><strong>' . h(tf('liga_stat_highest_home_win')) . '</strong><br>' . implode('<br>', array_map($matchLine, $extremes['homeWins'])) . '</p>';
        }
        if (!empty($extremes['awayWins'])) {
            $html .= '<p><strong>' . h(tf('liga_stat_highest_away_win')) . '</strong><br>' . implode('<br>', array_map($matchLine, $extremes['awayWins'])) . '</p>';
        }
        if (!empty($extremes['mostGoals'])) {
            $html .= '<p><strong>' . h(tf('liga_stat_most_goals')) . '</strong><br>' . implode('<br>', array_map($matchLine, $extremes['mostGoals'])) . '</p>';
        }
    
        $records = self::computeAllTeamsStreakRecords($teams, $partien);
        $catLabels = [
            'win'      => 'liga_stat_streak_cat_won',
            'unbeaten' => 'liga_stat_streak_cat_unbeaten',
            'draw'     => 'liga_stat_streak_cat_draw',
            'winless'  => 'liga_stat_streak_cat_winless',
            'loss'     => 'liga_stat_streak_cat_lost',
        ];
        $html .= '<table class="ligastat-overall"><tr><th>' . h(tf('liga_stat_streaks_title')) . '</th><th>' . h(tf('liga_stat_streaks_current')) . '</th><th>' . h(tf('liga_stat_streaks_season')) . '</th></tr>';
        foreach ($catLabels as $cat => $labelKey) {
            $rec = $records[$cat];
            $aktuellTxt = $rec['aktuell']['len'] > 0 ? $rec['aktuell']['len'] . ' ' . h(implode(', ', $rec['aktuell']['teams'])) : '–';
            $saisonTxt  = $rec['saison']['len'] > 0
                ? $rec['saison']['len'] . ' ' . h(implode(', ', $rec['saison']['teams'])) . ' (' . $rec['saison']['from'] . '.-' . $rec['saison']['to'] . '.)'
                : '–';
            $html .= '<tr><td>' . h(tf($labelKey)) . '</td><td>' . $aktuellTxt . '</td><td>' . $saisonTxt . '</td></tr>';
        }
        $html .= '</table></div>';
    
        return $html;
    }
    /**
     * Baut die komplette Ligastatistik-Ansicht: Team-Auswahl (0/1/2 Teams),
     * Detail-Boxen (bei 2 Teams zusätzlich Chancen gegeneinander + einfache
     * Restprogramm-Bewertung), plus den immer sichtbaren Liga-weiten Block.
     */
    public static function renderLigastatistikView(int $ligaId, array $allSpieltage, ?int $team1Id, ?int $team2Id) : string
    {
        $opts     = self::getLigaOptions($ligaId);
        $teams    = self::getLigaTeamsList($ligaId);
        $partien  = self::getAllLigaPartien($allSpieltage, $ligaId);
        $standing = self::computeStandings($teams, $partien, $opts, $ligaId);
        $showLogos = ($opts['ShowLogos'] ?? '0') === '1';
    
        $pickerOptions = '<option value="0">– ' . h(tf('liga_stat_pick_team')) . ' –</option>';
        foreach ($teams as $t) {
            $pickerOptions .= '<option value="' . (int)$t['id'] . '">' . h($t['name']) . '</option>';
        }
    
        $picker  = '<div class="ligastat-picker">';
        $picker .= '<select id="team1-select" onchange="location.href=\'liga.php?id=' . $ligaId . '&view=ligastatistik&team1=\'+this.value+\'&team2=\'+document.getElementById(\'team2-select\').value">'
                 . str_replace('value="' . $team1Id . '"', 'value="' . $team1Id . '" selected', $pickerOptions) . '</select>';
        $picker .= '<select id="team2-select" onchange="location.href=\'liga.php?id=' . $ligaId . '&view=ligastatistik&team2=\'+this.value+\'&team1=\'+document.getElementById(\'team1-select\').value">'
                 . str_replace('value="' . $team2Id . '"', 'value="' . $team2Id . '" selected', $pickerOptions) . '</select>';
        $picker .= '</div>';
    
        $html = '<div class="card">' . $picker;
    
        if ($team1Id === null && $team2Id === null) {
            $html .= '<p class="empty-msg">' . h(tf('liga_stat_pick_team_msg')) . '</p>';
        } elseif ($team1Id !== null && $team2Id !== null) {
            $stat1 = self::computeTeamDetailStats($team1Id, $teams, $partien, $standing);
            $stat2 = self::computeTeamDetailStats($team2Id, $teams, $partien, $standing);
    
            $ppg1 = max(0.01, $stat1['ppg']);
            $ppg2 = max(0.01, $stat2['ppg']);
            $chance1 = round($ppg1 / ($ppg1 + $ppg2) * 100);
            $chance2 = 100 - $chance1;
    
            $html .= '<p class="ligastat-chances"><strong>' . h(tf('liga_stat_chances')) . ':</strong> '
                   . h($stat1['name']) . ' ' . $chance1 . '% – ' . $chance2 . '% ' . h($stat2['name']) . '</p>';
    
            $html .= '<div class="ligastat-compare">' . self::renderTeamStatBox($stat1, $team1Id, $showLogos) . self::renderTeamStatBox($stat2, $team2Id, $showLogos) . '</div>';
    
            if ($stat1['remainingPpgAvg'] !== null && $stat2['remainingPpgAvg'] !== null) {
                $r1 = round($stat1['remainingPpgAvg'], 2);
                $r2 = round($stat2['remainingPpgAvg'], 2);
                if (abs($r1 - $r2) < 0.05) {
                    $tendenz = h(tf('liga_stat_tendenz_equal'));
                } elseif ($r1 > $r2) {
                    $tendenz = h(tf('liga_stat_tendenz_harder', ['team' => $stat1['name']]));
                } else {
                    $tendenz = h(tf('liga_stat_tendenz_harder', ['team' => $stat2['name']]));
                }
                $html .= '<div class="card ligastat-remaining-eval"><h3>' . h(tf('liga_stat_remaining_eval_title')) . '</h3>';
                $html .= '<table class="ligastat-overall"><tr><th>' . h($stat1['name']) . '</th><th>' . h(tf('liga_stat_remaining_ppg')) . '</th><th>' . h($stat2['name']) . '</th></tr>';
                $html .= '<tr><td>' . $r1 . '</td><td>' . $tendenz . '</td><td>' . $r2 . '</td></tr></table></div>';
            }
        } else {
            $soloId = $team1Id ?? $team2Id;
            $stat    = self::computeTeamDetailStats($soloId, $teams, $partien, $standing);
            $html .= self::renderTeamStatBox($stat, $soloId, $showLogos);
        }
    
        $html .= '</div>';
        $html .= self::renderOverallStatsBlock($teams, $partien);
    
        return $html;
    }
    /**
     * Rendert die "Spielfrei: TEAMNAME"-Zeile unterhalb der Ergebnistabelle
     * eines Spieltags (siehe findSpielfreiTeams()). Liefert einen leeren String,
     * wenn kein Team spielfrei ist.
     */
    public static function renderSpielfreiNote(int $ligaId, array $partien) : string
    {
        $teams = self::findSpielfreiTeams($ligaId, $partien);
        if ($teams === []) {
            return '';
        }
        $names = implode(', ', array_map(static fn(array $t) : string => '<strong>' . h($t['name']) . '</strong>', $teams));
        return renderPartial('spielfrei_note', [
            'Label' => h(tf('liga_spielfrei_label')),
            'Teams' => $names,
        ]);
    }

    /**
     * Copyright-/Spenden-Hinweis für Addon-Ausgaben (Viewer, Tabellenrechner,
     * Relegation etc., siehe config_loader.php). $addon wird nur als
     * data-Attribut gesetzt, nicht sichtbar (Beitrag: Torsten Hofmann).
     *
     * @return string HTML <p> mit Copyright + Spenden-Link
     */
    public static function renderCopyrightNotice(string $addon = '') : string
    {
        // Delegiert an die zentrale Funktion in config_loader.php und packt
        // das Ergebnis in einen <p>-Block für die Addon-Ausgabe (Viewer,
        // Tabellenrechner, Relegation etc.).
        return '<p class="lmo-copyright" style="font-size:.68rem;color:#9098a8;'
             . 'text-align:right;margin:8px 0 0;padding:0;opacity:.65">'
             . \renderCopyrightNotice($addon) . '</p>';
    }
}
