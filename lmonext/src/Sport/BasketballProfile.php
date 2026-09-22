<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/BasketballProfile.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Basketball: Punkte als Ergebnis (h_tore/g_tore = Gesamtpunkte,
 * ACHTUNG: kann > 127 sein -> DB-Spalte muss SMALLINT statt TINYINT).
 * Viertel-Ergebnisse in extra_data:
 *   {"quarters": [{"h":20,"g":18}, {"h":22,"g":20}, {"h":18,"g":22}, {"h":25,"g":18}]}
 * Optional Verlaengerung:
 *   {"quarters": [...], "ot": [{"h":8,"g":6}]}
 * Kein Unentschieden moeglich.
 * 2-0 Punkte (Win/Loss).
 */
final class BasketballProfile implements SportProfile
{
    public function getKey(): string         { return 'basketball'; }
    public function getLabel(): string       { return 'Basketball'; }
    public function getScoreLabel(): string  { return 'Punkte'; }
    public function supportsDraws(): bool    { return false; }
    public function periodsAffectStandings(): bool { return false; }

    public function getDefaultPointsConfig(): array
    {
        return [
            'PointsForWin'  => 2,
            'PointsForDraw' => 1,  // wird nicht genutzt, aber sicherheitshalber
            'PointsForLost' => 0,
            'PointsForWinET'  => 2,
            'PointsForDrawET' => 1,
            'PointsForLostET' => 1, // OT-Verlierer bekommt 1 (optional)
        ];
    }

    public function getPeriodFields(): array
    {
        return ['quarters' => 'Viertel'];
    }

    public function formatResult(array $match, bool $withPeriods = true): string
    {
        $h = $match['h_tore'] ?? null;
        $g = $match['g_tore'] ?? null;
        if ($h === null || $g === null) {
            return '- : -';
        }
        $result = (string)$h . ' : ' . (string)$g;

        $status = (int)($match['status'] ?? 0);
        $suffix = match ($status) {
            1 => ' i.E.',
            2 => ' n.V.',
            default => '',
        };

        if ($withPeriods) {
            $viertel = $this->formatPeriods($match['extra_data'] ?? null);
            if ($viertel !== '') {
                $result .= ' (' . $viertel . ')';
            }
        }
        return $result . $suffix;
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
        // Kompatibilitaet: altes Key 'quarter' (vor v11f) → 'quarters'
        $quarters = $data['quarters'] ?? $data['quarter'] ?? null;
        if (!is_array($quarters)) {
            return '';
        }
        $parts = [];
        foreach ($quarters as $q) {
            $parts[] = ($q['h'] ?? '?') . ':' . ($q['g'] ?? '?');
        }
        $result = implode(', ', $parts);
        // Optionale Verlaengerung
        if (isset($data['ot']) && is_array($data['ot'])) {
            $otParts = [];
            foreach ($data['ot'] as $ot) {
                $otParts[] = ($ot['h'] ?? '?') . ':' . ($ot['g'] ?? '?');
            }
            $result .= ' | OT: ' . implode(', ', $otParts);
        }
        return $result;
    }

    public function getDefaultPeriodCount(): int
    {
        return 4;
    }

    public function getResultFormFields(): array
    {
        return [
            [
                'key'       => 'quarters',
                'label'     => 'Viertel',
                'type'      => 'fixed-score-list',
                'count'     => 4,
                'home_key'  => 'quarter_h',
                'guest_key' => 'quarter_g',
                'required'  => false,
            ],
            [
                'key'       => 'ot',
                'label'     => 'Verlängerung',
                'type'      => 'dynamic-score-list',
                'min_items' => 0,
                'max_items' => 5,  // theoretisch beliebig viele OT
                'required'  => false,
            ],
        ];
    }

    public function validateResult(array $data): array
    {
        $errors = [];
        if (isset($data['quarters']) && is_array($data['quarters'])) {
            $hSum = 0; $gSum = 0;
            foreach ($data['quarters'] as $q) {
                $hSum += (int)($q['h'] ?? 0);
                $gSum += (int)($q['g'] ?? 0);
            }
            // Viertelsumme + OT-Summe muss = Endstand sein
            if (isset($data['ot']) && is_array($data['ot'])) {
                foreach ($data['ot'] as $ot) {
                    $hSum += (int)($ot['h'] ?? 0);
                    $gSum += (int)($ot['g'] ?? 0);
                }
            }
            if ($hSum !== (int)($data['h_tore'] ?? 0)) {
                $errors[] = 'Summe der Viertel/OT-Punkte Heim (' . $hSum . ') ≠ Endstand (' . $data['h_tore'] . ')';
            }
            if ($gSum !== (int)($data['g_tore'] ?? 0)) {
                $errors[] = 'Summe der Viertel/OT-Punkte Gast (' . $gSum . ') ≠ Endstand (' . $data['g_tore'] . ')';
            }
        }
        return $errors;
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
            ['key' => 'n',    'label' => 'N',    'class' => 'st-num'],
            ['key' => 'tore', 'label' => 'P+/:P-', 'class' => 'st-num'],
            ['key' => 'diff', 'label' => 'Diff',   'class' => 'st-num'],
            ['key' => 'pkt',  'label' => 'Pkt',    'class' => 'st-pkt'],
        ];
    }
}
