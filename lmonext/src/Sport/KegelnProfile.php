<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/KegelnProfile.php
 * Fileversion: 1.0.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Kegeln (Beitrag: Nutzeranfrage): Ergebniseinheit "Holz" statt "Tore",
 * Unentschieden möglich (gleiche Gesamtholzzahl beider Teams), keine
 * Halbzeit/Perioden - strukturell identisch zu FootballProfile, nur mit
 * der sportart-eigenen Terminologie. Die Liga-eigene Einstellung
 * "Alternative für Tore"/"Alternative für Pkt." (nameTor/namePkt, siehe
 * admin/view_liga_settings.php) bleibt davon unberührt und kann weiterhin
 * zusätzlich pro Liga genutzt werden, falls ein anderer Begriff als "Holz"
 * gewünscht ist - getScoreLabel() hier ist nur die sportart-typische
 * Vorgabe, keine Pflichtvorgabe.
 */
final class KegelnProfile implements SportProfile
{
    public function getKey(): string         { return 'kegeln'; }
    public function getLabel(): string       { return tf('sport_name_kegeln'); }
    public function getScoreLabel(): string  { return tf('sport_score_wood'); }
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
            'PointsForWinPS'   => 2,
            'PointsForDrawPS'  => 1,
            'PointsForLostPS'  => 0,
        ];
    }

    public function getPeriodFields(): array
    {
        return [];
    }

    public function formatResult(array $match, bool $withPeriods = true): string
    {
        $h = $match['h_tore'] ?? null;
        $g = $match['g_tore'] ?? null;
        if ($h === null || $g === null) {
            return '- : -';
        }

        // Grüne-Tisch-Entscheidung: dasselbe Prinzip wie FootballProfile -
        // das ANGERECHNETE (gewertete) Ergebnis wird angezeigt statt des
        // real erzielten, siehe dortiger ausführlicher Kommentar.
        $gtEntscheidung = (int)($match['gt_entscheidung'] ?? 0);
        $istGtGewertet = $gtEntscheidung === 1 || $gtEntscheidung === 2 || $gtEntscheidung === 3;
        if ($istGtGewertet) {
            $credited = \LMOnext\Liga\LigaService::gtCreditedScore(
                $gtEntscheidung, (int)$h, (int)$g,
                (int)($match['_gt_tore_gespielt'] ?? 2),
                (int)($match['_gt_tore_nichtantritt'] ?? 2)
            );
            $result = (string)$credited['h_tore'] . ' : ' . (string)$credited['g_tore'];
        } else {
            $result = (string)$h . ' : ' . (string)$g;
        }

        $suffix = \LMOnext\Liga\LigaService::statusSuffix($match);

        return $result . $suffix;
    }

    public function formatPeriods(?string $extraData): string
    {
        return '';
    }

    public function getDefaultPeriodCount(): int
    {
        return 0;
    }

    public function getResultFormFields(): array
    {
        return [];
    }

    public function validateResult(array $data): array
    {
        return [];
    }

    public function getDisplayModes(): array { return []; }

    public function getStandingsColumnsForMode(string $mode = 'short'): array
    {
        return $this->getStandingsColumns();
    }

    public function getStandingsColumns(): array
    {
        return [
            ['key' => 'sp',   'label' => tf('sport_col_sp'),   'class' => 'st-num'],
            ['key' => 's',    'label' => tf('sport_col_s'),    'class' => 'st-num'],
            ['key' => 'u',    'label' => tf('sport_col_u'),    'class' => 'st-num'],
            ['key' => 'n',    'label' => tf('sport_col_n'),    'class' => 'st-num'],
            ['key' => 'tore', 'label' => tf('sport_score_wood'), 'class' => 'st-num'],
            ['key' => 'diff', 'label' => tf('sport_col_diff'), 'class' => 'st-num'],
            ['key' => 'pkt',  'label' => tf('sport_col_pkt'),  'class' => 'st-pkt'],
        ];
    }
}
