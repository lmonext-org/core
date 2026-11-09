<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/FootballProfile.php
 * Fileversion: 1.5.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Fussball: Tore als Ergebnis, Halbzeit nur Anzeige (nicht Tabelle),
 * 3-1-0 Punkte (konfigurierbar), Unentschieden moeglich.
 * Halbzeit-Ergebnis wird in extra_data als JSON gespeichert:
 *   {"halftime": {"h": 1, "g": 0}}
 */
final class FootballProfile implements SportProfile
{
    public function getKey(): string         { return 'football'; }
    public function getLabel(): string       { return tf('sport_name_football'); }
    public function getScoreLabel(): string  { return tf('sport_score_goals'); }
    public function supportsDraws(): bool    { return true; }
    public function periodsAffectStandings(): bool { return false; }

    public function getDefaultPointsConfig(): array
    {
        return [
            'PointsForWin'     => 3,
            'PointsForDraw'    => 1,
            'PointsForLost'    => 0,
            'PointsForWinET'   => 3,
            'PointsForDrawET'  => 1,
            'PointsForLostET'  => 1, // n.V. - Verlierer bekommt 1 Punkt (3-1-0 mit OT-Losspunkt)
            'PointsForWinPS'   => 3,
            'PointsForDrawPS'  => 1,
            'PointsForLostPS'  => 0,
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

        // Grüne-Tisch-Entscheidung: das ANGERECHNETE (gewertete)
        // Ergebnis wird angezeigt statt des real erzielten, mit "(*)" als
        // dezentem Hinweis (siehe liga_status_gt) statt eines ausgeschriebenen
        // "Wertung"-Texts - die vollständige Erklärung mit dem realen Ergebnis
        // steht stattdessen als Fußnote unterhalb der Spieltagsansicht (siehe
        // renderGtFootnotes()). Halbzeitanzeige bewusst unterdrückt, wenn eine
        // Entscheidung greift - sie bezieht sich auf das reale, nicht das
        // gewertete Spiel und wäre neben dem gewerteten Ergebnis irreführend.
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

        // Status-Suffix (n.V./i.E./nicht gewertet/Wertung) - ruft die
        // zentrale TeamFormattingTrait::statusSuffix() auf statt die Logik
        // hier ein drittes Mal zu duplizieren (Bugfix: diese Stelle war beim
        // Ergänzen von "nicht gewertet" zunächst übersehen worden, da sie
        // ihre eigene, lokale Kopie der status-Logik hatte statt die
        // zentrale Funktion zu nutzen - dadurch fehlte hier sowohl der
        // "nicht gewertet"- als auch der "Wertung"-Hinweis).
        $suffix = \LMOnext\Liga\LigaService::statusSuffix($match);

        if ($withPeriods && !$istGtGewertet) {
            $hz = $this->formatPeriods($match['extra_data'] ?? null);
            if ($hz !== '') {
                $result .= ' (' . $hz . ')';
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
        if (!is_array($data) || !isset($data['halftime'])) {
            return '';
        }
        $h = $data['halftime']['h'] ?? '?';
        $g = $data['halftime']['g'] ?? '?';
        return (string)$h . ':' . (string)$g;
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
                'label'     => tf('sport_form_halftime'),
                'type'      => 'score-pair',
                'home_key'  => 'hz_h',
                'guest_key' => 'hz_g',
                'required'  => false,
            ],
        ];
    }

    public function validateResult(array $data): array
    {
        $errors = [];
        // Halbzeit optional, aber wenn angegeben: gueltige Zahlen
        if (isset($data['hz_h']) && $data['hz_h'] !== '') {
            if (!is_numeric($data['hz_h']) || (int)$data['hz_h'] < 0) {
                $errors[] = t('sport_err_ht_home');
            }
        }
        if (isset($data['hz_g']) && $data['hz_g'] !== '') {
            if (!is_numeric($data['hz_g']) || (int)$data['hz_g'] < 0) {
                $errors[] = t('sport_err_ht_guest');
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
            ['key' => 'sp',   'label' => tf('sport_col_sp'),  'class' => 'st-num'],
            ['key' => 's',    'label' => tf('sport_col_s'),   'class' => 'st-num'],
            ['key' => 'u',    'label' => tf('sport_col_u'),   'class' => 'st-num'],
            ['key' => 'n',    'label' => tf('sport_col_n'),   'class' => 'st-num'],
            ['key' => 'tore', 'label' => tf('sport_score_goals'), 'class' => 'st-num'],
            ['key' => 'diff', 'label' => tf('sport_col_diff'), 'class' => 'st-num'],
            ['key' => 'pkt',  'label' => tf('sport_col_pkt'), 'class' => 'st-pkt'],
        ];
    }
}
