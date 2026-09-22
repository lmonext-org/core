<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/BadmintonProfile.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Badminton: Spiele sind das Ergebnis (h_tore/g_tore = gewonnene Spiele,
 * z.B. 4:1). Die einzelnen Satz-Ergebnisse (21:15, 19:21, ...) werden in
 * extra_data als JSON gespeichert:
 *   {"games": [{"h":21,"g":15}, {"h":19,"g":21}, {"h":21,"g":18}, ...]}
 *
 * Punktevergabe (Badminton-Bundesliga, standardmaessig):
 *   Sieg = 2 Punkte, Niederlage = 0 Punkte.
 * Kein Unentschieden moeglich.
 * Satzverhaeltnis kann als Tiebreaker dienen (periodsAffectStandings = true).
 */
final class BadmintonProfile implements SportProfile
{
    public function getKey(): string         { return 'badminton'; }
    public function getLabel(): string       { return 'Badminton'; }
    public function getScoreLabel(): string  { return 'Spiele'; }
    public function supportsDraws(): bool    { return false; }
    public function periodsAffectStandings(): bool { return true; }

    public function getDefaultPointsConfig(): array
    {
        return [
            'PointsForWin'     => 2,
            'PointsForDraw'    => 0,
            'PointsForLost'    => 0,
        ];
    }

    public function getPeriodFields(): array
    {
        return ['games' => 'Satz'];
    }

    public function getDefaultPeriodCount(): int
    {
        return 5;
    }

    public function formatResult(array $match, bool $withPeriods = true): string
    {
        return $this->formatResultWithMode($match, $withPeriods, 'long');
    }

    /**
     * Darstellungsmodi:
     *   short  : 4:1
     *   medium : 4:1 / 110:95       (Spielgewinne / Gesamtpunkte)
     *   long   : 4:1 / 110:95 (21:15 19:21 21:18 21:17 21:14)
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

        // Gesamtpunkte aus Spielen berechnen
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
     * Parst extra_data und gibt die Spiele als Array zurueck.
     */
    private function parsePeriods(?string $extraData): array
    {
        if ($extraData === null) { return []; }
        $data = json_decode($extraData, true);
        if (!is_array($data)) { return []; }
        $games = $data['games'] ?? $data['game'] ?? null;
        return is_array($games) ? $games : [];
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
        $games = $data['games'] ?? $data['game'] ?? null;
        if (!is_array($games)) {
            return '';
        }
        $parts = [];
        foreach ($games as $game) {
            $parts[] = ($game['h'] ?? '?') . ':' . ($game['g'] ?? '?');
        }
        return implode(', ', $parts);
    }

    public function getResultFormFields(): array
    {
        return [
            [
                'key'       => 'games',
                'label'     => 'Satz',
                'type'      => 'dynamic-score-list',
                'min_items' => 3,
                'max_items' => 5,
                'required'  => false,
            ],
        ];
    }

    public function validateResult(array $data): array
    {
        $errors = [];
        if (isset($data['games']) && is_array($data['games'])) {
            $hWins = 0;
            $gWins = 0;
            foreach ($data['games'] as $game) {
                if (($game['h'] ?? 0) > ($game['g'] ?? 0)) { $hWins++; }
                elseif (($game['g'] ?? 0) > ($game['h'] ?? 0)) { $gWins++; }
            }
            if ((int)($data['h_tore'] ?? 0) !== $hWins) {
                $errors[] = 'Heim-Spielgewinne (' . $hWins . ') stimmen nicht mit h_tore (' . $data['h_tore'] . ') ueberein';
            }
            if ((int)($data['g_tore'] ?? 0) !== $gWins) {
                $errors[] = 'Gast-Spielgewinne (' . $gWins . ') stimmen nicht mit g_tore (' . $data['g_tore'] . ') ueberein';
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
        return [
            ['key' => 'sp',   'label' => 'Sp',     'class' => 'st-num'],
            ['key' => 's',    'label' => 'S',      'class' => 'st-num'],
            ['key' => 'n',    'label' => 'N',      'class' => 'st-num'],
            ['key' => 'tore', 'label' => 'Spiele', 'class' => 'st-num'],
            ['key' => 'diff', 'label' => 'Diff',   'class' => 'st-num'],
            ['key' => 'pkt',  'label' => 'Pkt',    'class' => 'st-pkt'],
        ];
    }

    /**
     * Tabellenspalten abhaengig vom Darstellungsmodus (analog Volleyball).
     */
    public function getStandingsColumnsForMode(string $mode = 'short'): array
    {
        if ($mode === 'medium') {
            return [
                ['key' => 'sp',   'label' => 'Sp',   'class' => 'st-num'],
                ['key' => 's',    'label' => 'S',    'class' => 'st-num'],
                ['key' => 'p3',   'label' => '2P',   'class' => 'st-num'],
                ['key' => 'p0',   'label' => '0P',   'class' => 'st-num'],
                ['key' => 'tore', 'label' => 'Spiele', 'class' => 'st-num'],
                ['key' => 'pkt',  'label' => 'Pkt',   'class' => 'st-pkt'],
            ];
        }
        if ($mode === 'long') {
            return [
                ['key' => 'sp',    'label' => 'Sp',    'class' => 'st-num'],
                ['key' => 's',     'label' => 'S',     'class' => 'st-num'],
                ['key' => 'n',     'label' => 'N',     'class' => 'st-num'],
                ['key' => 'bquot', 'label' => 'B-Quot', 'class' => 'st-num'],
                ['key' => 'bverh', 'label' => 'B-Verh',  'class' => 'st-num'],
                ['key' => 'squot', 'label' => 'P-Quot', 'class' => 'st-num'],
                ['key' => 'sverh', 'label' => 'P-Verh',  'class' => 'st-num'],
                ['key' => 'pkt',   'label' => 'Pkt',    'class' => 'st-pkt'],
            ];
        }
        // short (default)
        return [
            ['key' => 'sp',   'label' => 'Sp',     'class' => 'st-num'],
            ['key' => 's',    'label' => 'S',      'class' => 'st-num'],
            ['key' => 'tore', 'label' => 'Spiele', 'class' => 'st-num'],
            ['key' => 'pkt',  'label' => 'Pkt',    'class' => 'st-pkt'],
        ];
    }

    public function computeMatchPoints(int $hSets, int $gSets): array
    {
        if ($hSets > $gSets) {
            return [
                'home_pts' => 2, 'guest_pts' => 0,
                'home_win' => true, 'guest_win' => false,
            ];
        }
        return [
            'home_pts' => 0, 'guest_pts' => 2,
            'home_win' => false, 'guest_win' => true,
        ];
    }
}
