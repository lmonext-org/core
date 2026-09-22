<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/VolleyballProfile.php
 * Fileversion: 1.2.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Volleyball: Saetze sind das Ergebnis (h_tore/g_tore = gewonnene Saetze,
 * z.B. 3:1). Die einzelnen Satzpunkte (25:23, 22:25, ...) werden in
 * extra_data als JSON gespeichert:
 *   {"sets": [{"h":25,"g":23}, {"h":22,"g":25}, {"h":25,"g":20}, {"h":25,"g":18}]}
 *
 * Punktevergabe (standardmaessig):
 *   3:0 oder 3:1  ->  3 Punkte Sieger, 0 Punkte Verlierer
 *   3:2           ->  2 Punkte Sieger, 1 Punkt Verlierer
 *   (umgekehrt entsprechend)
 * Kein Unentschieden moeglich.
 * Satzverhaeltnis kann als Tiebreaker dienen (periodsAffectStandings = true).
 */
final class VolleyballProfile implements SportProfile
{
    public function getKey(): string         { return 'volleyball'; }
    public function getLabel(): string       { return 'Volleyball'; }
    public function getScoreLabel(): string  { return 'Sätze'; }
    public function supportsDraws(): bool    { return false; }
    public function periodsAffectStandings(): bool { return true; }

    public function getDefaultPointsConfig(): array
    {
        return [
            // Wir nutzen die bestehenden Felder:
            // 3:0/3:1 Sieger = 3, 3:2 Sieger = 2
            // Dafuer muessen wir auf das bestehende 3-1-0 System zurueckgreifen,
            // aber die Punktverteilung ist satzabhaengig.
            // Loesung: computeStandings bekommt eine sport-spezifische Berechnung
            // (siehe VolleyballProfile::computeStandingsRow). Die Punkte hier
            // sind nur der Fallback, wenn keine Sport-Logik greift.
            'PointsForWin'     => 3,
            'PointsForDraw'    => 0,  // gibt es nicht
            'PointsForLost'    => 0,
        ];
    }

    public function getPeriodFields(): array
    {
        return ['sets' => 'Satz'];
    }

    public function formatResult(array $match, bool $withPeriods = true): string
    {
        return $this->formatResultWithMode($match, $withPeriods, 'long');
    }

    /**
     * Darstellungsmodi:
     *   short  : 3:0
     *   medium : 3:0 / 83:72       (Satzgewinne / Gesamtpunkte)
     *   long   : 3:0 / 83:72 (25:18 33:31 25:23)
     */
    public function formatResultWithMode(array $match, bool $withPeriods, string $mode = 'long'): string
    {
        $h = $match['h_tore'] ?? null;
        $g = $match['g_tore'] ?? null;
        if ($h === null || $g === null) {
            return '- : -';
        }
        $result = (string)$h . ' : ' . (string)$g;

        if ($mode === 'short') {
            return $result;
        }

        // Gesamtpunkte aus Saetzen berechnen
        $periods = $this->parsePeriods($match['extra_data'] ?? null);
        if (!empty($periods)) {
            $th = 0; $tg = 0;
            foreach ($periods as $p) {
                $th += (int)($p['h'] ?? 0);
                $tg += (int)($p['g'] ?? 0);
            }
            $result .= ' / ' . $th . ':' . $tg;

            if ($mode === 'long' && $withPeriods) {
                $parts = [];
                foreach ($periods as $p) {
                    $parts[] = ($p['h'] ?? '?') . ':' . ($p['g'] ?? '?');
                }
                $result .= ' (' . implode(' ', $parts) . ')';
            }
        }
        return $result;
    }

    /**
     * Parst extra_data und gibt die Saetze als Array zurueck.
     */
    private function parsePeriods(?string $extraData): array
    {
        if ($extraData === null) { return []; }
        $data = json_decode($extraData, true);
        if (!is_array($data)) { return []; }
        $sets = $data['sets'] ?? $data['set'] ?? null;
        return is_array($sets) ? $sets : [];
    }

    public function formatPeriods(?string $extraData): string
    {
        if ($extraData === null) {
            return '';
        }
        $data = json_decode($extraData, true);
        if (!is_array($data)) {
            return '';
        }
        // Kompatibilitaet: altes Key 'set' (vor v11f) → 'sets'
        $sets = $data['sets'] ?? $data['set'] ?? null;
        if (!is_array($sets)) {
            return '';
        }
        $parts = [];
        foreach ($sets as $set) {
            $parts[] = ($set['h'] ?? '?') . ':' . ($set['g'] ?? '?');
        }
        return implode(', ', $parts);
    }

    public function getDefaultPeriodCount(): int
    {
        return 4;
    }

    public function getResultFormFields(): array
    {
        // Dynamische Anzahl von Saetzen (meist 3-5)
        return [
            [
                'key'       => 'sets',
                'label'     => 'Sätze',
                'type'      => 'dynamic-score-list',  // Admin rendert +/- Button
                'min_items' => 3,
                'max_items' => 5,
                'required'  => false,
            ],
        ];
    }

    public function validateResult(array $data): array
    {
        $errors = [];
        // h_tore / g_tore muessen die Satzgewinne widerspiegeln
        if (isset($data['sets']) && is_array($data['sets'])) {
            $hWins = 0;
            $gWins = 0;
            foreach ($data['sets'] as $set) {
                if (($set['h'] ?? 0) > ($set['g'] ?? 0)) { $hWins++; }
                elseif (($set['g'] ?? 0) > ($set['h'] ?? 0)) { $gWins++; }
            }
            if ((int)($data['h_tore'] ?? 0) !== $hWins) {
                $errors[] = 'Heim-Satzgewinne (' . $hWins . ') stimmen nicht mit h_tore (' . $data['h_tore'] . ') überein';
            }
            if ((int)($data['g_tore'] ?? 0) !== $gWins) {
                $errors[] = 'Gast-Satzgewinne (' . $gWins . ') stimmen nicht mit g_tore (' . $data['g_tore'] . ') überein';
            }
        }
        return $errors;
    }

    public function getDisplayModes(): array
    {
        return ['short', 'medium', 'long'];
    }

    public function getStandingsColumns(): array
    {
        // Volleyball zeigt Saetze statt Tore
        return [
            ['key' => 'sp',   'label' => 'Sp',   'class' => 'st-num'],
            ['key' => 's',    'label' => 'S',    'class' => 'st-num'],
            ['key' => 'n',    'label' => 'N',    'class' => 'st-num'],
            ['key' => 'tore', 'label' => 'Sätze', 'class' => 'st-num'],
            ['key' => 'diff', 'label' => 'Diff',  'class' => 'st-num'],
            ['key' => 'pkt',  'label' => 'Pkt',   'class' => 'st-pkt'],
        ];
    }

    /**
     * Tabellenspalten abhaengig vom Darstellungsmodus.
     *
     * Kurz:  Sp | S | Sätze | Pkt
     * Mittel: Sp | S | 3P | 2P | 1P | 0P | Sätze | Pkt
     * Lang: Sp | S | N | 3:0 | 3:1 | 3:2 | 2:3 | 1:3 | 0:3 | B-Quot | B-Verh | S-Quot | S-Verh | Pkt
     *
     * Die Beschriftungen kommen über tf() aus lang/frontend/*.php (nicht
     * hartkodiert Deutsch) - die reinen Ergebnis-Notationen (3:0, 2:3, ...)
     * brauchen keine Übersetzung, da Ziffern/Doppelpunkte sprachneutral sind.
     *
     * 'diag' => true markiert Spalten, deren Kopfzeile in der Lang-Ansicht
     * diagonal gestellt wird (siehe RenderViewsTrait::renderDynamicStandingsTable()
     * und st-diag-CSS in den Templates). Nur für die langen Wortbezeichner
     * (Spiele/Siege/Niederlagen/Ballquotient/Ballverhältnis/Satzquotient/
     * Satzverhältnis) gesetzt - NICHT für die kurzen Ergebnis-Spalten (3:0
     * usw.) oder "Punkte". Vorbild volleyball-bundesliga.de: dort bleiben
     * genau diese kurzen Spalten horizontal, weil sie ohnehin in ihre
     * schmale (an den kurzen Datenwert angepasste) Spalte passen - nur die
     * langen Wörter würden ohne Drehung über die eigene schmale Spalte
     * hinausragen. Das vermeidet die Text-Überlappung, die bei einer
     * vorherigen Umsetzung (alle Spalten diagonal, inkl. der kurzen)
     * aufgetreten war, von vornherein statt sie nachträglich zu kompensieren.
     */
    public function getStandingsColumnsForMode(string $mode = 'short'): array
    {
        if ($mode === 'medium') {
            return [
                ['key' => 'sp',   'label' => tf('sport_vb_col_sp'),   'class' => 'st-num'],
                ['key' => 's',    'label' => tf('sport_vb_col_s'),    'class' => 'st-num'],
                ['key' => 'p3',   'label' => tf('sport_vb_col_3p'),   'class' => 'st-num'],
                ['key' => 'p2',   'label' => tf('sport_vb_col_2p'),   'class' => 'st-num'],
                ['key' => 'p1',   'label' => tf('sport_vb_col_1p'),   'class' => 'st-num'],
                ['key' => 'p0',   'label' => tf('sport_vb_col_0p'),   'class' => 'st-num'],
                ['key' => 'tore', 'label' => tf('sport_vb_col_saetze'), 'class' => 'st-num'],
                ['key' => 'pkt',  'label' => tf('sport_vb_col_pkt'),  'class' => 'st-pkt'],
            ];
        }
        if ($mode === 'long') {
            return [
                ['key' => 'sp',    'label' => tf('sport_vb_col_sp'),    'class' => 'st-num', 'diag' => true],
                ['key' => 's',     'label' => tf('sport_vb_col_s'),     'class' => 'st-num', 'diag' => true],
                ['key' => 'n',     'label' => tf('sport_vb_col_n'),     'class' => 'st-num', 'diag' => true],
                ['key' => 'w30',   'label' => '3:0',   'class' => 'st-num'],
                ['key' => 'w31',   'label' => '3:1',   'class' => 'st-num'],
                ['key' => 'w32',   'label' => '3:2',   'class' => 'st-num'],
                ['key' => 'l23',   'label' => '2:3',   'class' => 'st-num'],
                ['key' => 'l13',   'label' => '1:3',   'class' => 'st-num'],
                ['key' => 'l03',   'label' => '0:3',   'class' => 'st-num'],
                ['key' => 'bquot', 'label' => tf('sport_vb_col_bquot'), 'class' => 'st-num', 'diag' => true],
                ['key' => 'bverh', 'label' => tf('sport_vb_col_bverh'), 'class' => 'st-num', 'diag' => true],
                ['key' => 'squot', 'label' => tf('sport_vb_col_squot'), 'class' => 'st-num', 'diag' => true],
                ['key' => 'sverh', 'label' => tf('sport_vb_col_sverh'), 'class' => 'st-num', 'diag' => true],
                ['key' => 'pkt',   'label' => tf('sport_vb_col_pkt'),   'class' => 'st-pkt'],
            ];
        }
        // short (default)
        return [
            ['key' => 'sp',   'label' => tf('sport_vb_col_sp'),    'class' => 'st-num'],
            ['key' => 's',    'label' => tf('sport_vb_col_s'),     'class' => 'st-num'],
            ['key' => 'tore', 'label' => tf('sport_vb_col_saetze'), 'class' => 'st-num'],
            ['key' => 'pkt',  'label' => tf('sport_vb_col_pkt'),   'class' => 'st-pkt'],
        ];
    }

    /**
     * Sport-spezifische Tabellenberechnung fuer Volleyball.
     * Wird statt der generischen computeStandings-Logik aufgerufen, wenn
     * die Liga sport_type='volleyball' hat.
     *
     * 3:0 / 3:1  ->  3 Punkte Sieger, 0 Verlierer
     * 3:2        ->  2 Punkte Sieger, 1 Verlierer
     */
    public function computeMatchPoints(int $hSets, int $gSets): array
    {
        if ($hSets > $gSets) {
            $diff = $hSets - $gSets;
            return [
                'home_pts' => $diff >= 2 ? 3 : 2,  // 3:0/3:1 = 3, 3:2 = 2
                'guest_pts' => $diff >= 2 ? 0 : 1,  // 0:3/1:3 = 0, 2:3 = 1
                'home_win' => true, 'guest_win' => false,
            ];
        }
        return [
            'home_pts' => $hSets === $gSets - 1 ? 1 : 0,
            'guest_pts' => $gSets - $hSets >= 2 ? 3 : 2,
            'home_win' => false, 'guest_win' => true,
        ];
    }
}
