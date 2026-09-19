<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/HandballProfile.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Handball: Tore als Ergebnis, Halbzeit nur Anzeige (wie Fussball),
 * 2-1-0 Punkte (Standard HBL), Unentschieden moeglich.
 * Halbzeit in extra_data: {"halftime": {"h":12, "g":10}}
 */
final class HandballProfile implements SportProfile
{
    public function getKey(): string         { return 'handball'; }
    public function getLabel(): string       { return 'Handball'; }
    public function getScoreLabel(): string  { return 'Tore'; }
    public function supportsDraws(): bool    { return true; }
    public function periodsAffectStandings(): bool { return false; }

    public function getDefaultPointsConfig(): array
    {
        return [
            'PointsForWin'     => 2,
            'PointsForDraw'    => 1,
            'PointsForLost'    => 0,
            'PointsForWinET'   => 2,
            'PointsForDrawET'  => 1,
            'PointsForLostET'  => 1,
        ];
    }

    public function getPeriodFields(): array
    {
        return ['halftime' => 'Halbzeit'];
    }

    public function formatResult(array $match, bool $withPeriods = true): string
    {
        $h = $match['h_tore'] ?? null;
        $g = $match['g_tore'] ?? null;
        if ($h === null || $g === null) {
            return '- : -';
        }
        $result = (string)$h . ' : ' . (string)$g;

        if ($withPeriods) {
            $hz = $this->formatPeriods($match['extra_data'] ?? null);
            if ($hz !== '') {
                $result .= ' (' . $hz . ')';
            }
        }
        return $result;
    }

    public function formatPeriods(?string $extraData): string
    {
        if ($extraData === null) {
            return '';
        }
        $data = json_decode($extraData, true);
        if (!is_array($data) || !isset($data['halftime'])) {
            return '';
        }
        return ($data['halftime']['h'] ?? '?') . ':' . ($data['halftime']['g'] ?? '?');
    }

    public function getDefaultPeriodCount(): int
    {
        return 1;
    }

    public function getResultFormFields(): array
    {
        return [
            [
                'key'       => 'halftime',
                'label'     => 'Halbzeit',
                'type'      => 'score-pair',
                'home_key'  => 'hz_h',
                'guest_key' => 'hz_g',
                'required'  => false,
            ],
        ];
    }

    public function validateResult(array $data): array
    {
        return []; // gleich wie Football, keine spezielle Validierung
    }

    public function getDisplayModes(): array { return []; }

    public function getStandingsColumnsForMode(string $mode = 'short'): array
    {
        return $this->getStandingsColumns();
    }

    public function getStandingsColumns(): array
    {
        return [
            ['key' => 'sp',   'label' => 'Sp',   'class' => 'st-num'],
            ['key' => 's',    'label' => 'S',    'class' => 'st-num'],
            ['key' => 'u',    'label' => 'U',    'class' => 'st-num'],
            ['key' => 'n',    'label' => 'N',    'class' => 'st-num'],
            ['key' => 'tore', 'label' => 'Tore', 'class' => 'st-num'],
            ['key' => 'diff', 'label' => 'Diff', 'class' => 'st-num'],
            ['key' => 'pkt',  'label' => 'Pkt',  'class' => 'st-pkt'],
        ];
    }
}
