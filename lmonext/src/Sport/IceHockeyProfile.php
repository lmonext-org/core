<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/IceHockeyProfile.php
 * Fileversion: 1.1.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Eishockey: Tore als Ergebnis (h_tore/g_tore = Gesamttore),
 * Drittel-Ergebnisse in extra_data als JSON:
 *   {"periods": [{"h":1,"g":0}, {"h":2,"g":1}, {"h":1,"g":2}]}
 * status: 0 = regulär, 2 = n.V. (nach Verlängerung), 1 = i.E. (Penaltyschießen)
 *
 * Punktevergabe (DEL-Standard, konfigurierbar):
 *   Regulaerer Sieg (status=0):     3 Punkte
 *   Sieg nach Verlaengerung (status=2): 2 Punkte
 *   Niederlage n.V. (status=2):    1 Punkt
 *   Niederlage regulär:             0 Punkte
 * (Das bestehende status-System + PointsForWinET/LostET greift bereits!)
 */
final class IceHockeyProfile implements SportProfile
{
    public function getKey(): string         { return 'icehockey'; }
    public function getLabel(): string       { return tf('sport_name_icehockey'); }
    public function getScoreLabel(): string  { return tf('sport_score_goals'); }
    public function supportsDraws(): bool    { return false; } // nach regulärer Spielzeit nicht, n.V. entscheidet
    public function periodsAffectStandings(): bool { return false; }

    public function getDefaultPointsConfig(): array
    {
        return [
            'PointsForWin'     => 3,
            'PointsForDraw'    => 1,  // nach regulärer Zeit moeglich (wird selten genutzt)
            'PointsForLost'    => 0,
            'PointsForWinET'   => 2,  // Sieg n.V. / Penaltyschießen
            'PointsForDrawET'  => 1,
            'PointsForLostET'  => 1,  // Niederlage n.V. = 1 Punkt (3-2-1-0 System)
            'PointsForWinPS'   => 2,
            'PointsForDrawPS'  => 1,
            'PointsForLostPS'  => 1,
        ];
    }

    public function getPeriodFields(): array
    {
        return ['periods' => 'Drittel'];
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
            1 => ' ' . tf('liga_status_ie_icehockey'),
            2 => ' ' . tf('liga_status_nv_icehockey'),
            default => '',
        };

        if ($withPeriods) {
            $drittel = $this->formatPeriods($match['extra_data'] ?? null);
            if ($drittel !== '') {
                $result .= ' (' . $drittel . ')';
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
        // Kompatibilitaet: altes Key 'period' (vor v11f) → 'periods'
        $periods = $data['periods'] ?? $data['period'] ?? null;
        if (!is_array($periods)) {
            return '';
        }
        $parts = [];
        foreach ($periods as $period) {
            $parts[] = ($period['h'] ?? '?') . ':' . ($period['g'] ?? '?');
        }
        return implode(', ', $parts);
    }

    public function getDefaultPeriodCount(): int
    {
        return 3;
    }

    public function getResultFormFields(): array
    {
        return [
            [
                'key'       => 'periods',
                'label'     => tf('sport_form_periods'),
                'type'      => 'fixed-score-list',
                'count'     => 3,  // immer 3 Drittel
                'home_key'  => 'period_h',
                'guest_key' => 'period_g',
                'required'  => false,
            ],
        ];
    }

    public function validateResult(array $data): array
    {
        $errors = [];
        // Drittel-Eingaben optional, aber wenn angegeben: Summe muss passen
        if (isset($data['periods']) && is_array($data['periods'])) {
            $hSum = 0; $gSum = 0;
            foreach ($data['periods'] as $p) {
                $hSum += (int)($p['h'] ?? 0);
                $gSum += (int)($p['g'] ?? 0);
            }
            if ($hSum !== (int)($data['h_tore'] ?? 0)) {
                $errors[] = t('sport_err_ih_sum_home', ['sum' => (string)$hSum, 'total' => (string)$data['h_tore']]);
            }
            if ($gSum !== (int)($data['g_tore'] ?? 0)) {
                $errors[] = t('sport_err_ih_sum_guest', ['sum' => (string)$gSum, 'total' => (string)$data['g_tore']]);
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
        // Eishockey: S, U (n.V.), N, Tore, Diff, Pkt
        // U = Unentschieden nach regulärer Zeit (selten) - wird meist n.V. entschieden
        return [
            ['key' => 'sp',   'label' => tf('sport_col_sp'),   'class' => 'st-num'],
            ['key' => 's',    'label' => tf('sport_col_s'),    'class' => 'st-num'],
            ['key' => 'u',    'label' => tf('sport_col_u'),    'class' => 'st-num'],
            ['key' => 'n',    'label' => tf('sport_col_n'),    'class' => 'st-num'],
            ['key' => 'tore', 'label' => tf('sport_score_goals'), 'class' => 'st-num'],
            ['key' => 'diff', 'label' => tf('sport_col_diff'), 'class' => 'st-num'],
            ['key' => 'pkt',  'label' => tf('sport_col_pkt'),  'class' => 'st-pkt'],
        ];
    }
}
