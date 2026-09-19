<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/StatisticsTrait.php
 * Fileversion: 1.0.0
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
trait StatisticsTrait
{
    /**
     * Serien (Siege/Unentschieden/Niederlagen am Stück, Ungeschlagen/Sieglos-
     * Läufe) für ein Team, chronologisch über alle gespielten Partien.
     * "current" = Stand nach dem letzten Spiel, "best" = längste Serie der Saison
     * (inkl. Spieltag-Spanne).
     */
    public static function computeTeamStreaks(int $teamId, array $partienChrono) : array
    {
        $current = ['win' => 0, 'unbeaten' => 0, 'draw' => 0, 'winless' => 0, 'loss' => 0];
        $best     = [];
        foreach (array_keys($current) as $k) {
            $best[$k] = ['len' => 0, 'from' => null, 'to' => null];
        }
    
        foreach ($partienChrono as $p) {
            $hId = (int)($p['heim_id'] ?? 0);
            $gId = (int)($p['gast_id'] ?? 0);
            if ($p['h_tore'] === null || $p['g_tore'] === null || ($hId !== $teamId && $gId !== $teamId)) {
                continue;
            }
            $own = $hId === $teamId ? (int)$p['h_tore'] : (int)$p['g_tore'];
            $opp = $hId === $teamId ? (int)$p['g_tore'] : (int)$p['h_tore'];
            $res = $own > $opp ? 'W' : ($own < $opp ? 'L' : 'D');
            $nr  = (int)$p['_spieltag_nummer'];
    
            $current['win']      = $res === 'W' ? $current['win'] + 1 : 0;
            $current['unbeaten']  = $res !== 'L' ? $current['unbeaten'] + 1 : 0;
            $current['draw']     = $res === 'D' ? $current['draw'] + 1 : 0;
            $current['winless']   = $res !== 'W' ? $current['winless'] + 1 : 0;
            $current['loss']     = $res === 'L' ? $current['loss'] + 1 : 0;
    
            foreach ($current as $k => $len) {
                if ($len > $best[$k]['len']) {
                    $best[$k] = ['len' => $len, 'to' => $nr, 'from' => $nr - $len + 1];
                }
            }
        }
    
        return ['current' => $current, 'best' => $best];
    }
    /**
     * Findet je Serien-Kategorie (aktuell + Saison) das/die Team(s) mit der
     * längsten Serie, ligaweit (für den "Serien"-Block der Ligastatistik).
     */
    public static function computeAllTeamsStreakRecords(array $teams, array $partien) : array
    {
        $categories = ['win', 'unbeaten', 'draw', 'winless', 'loss'];
        $records = [];
        foreach ($categories as $cat) {
            $records[$cat] = [
                'aktuell' => ['len' => 0, 'teams' => []],
                'saison'  => ['len' => 0, 'teams' => [], 'from' => null, 'to' => null],
            ];
        }
    
        foreach ($teams as $t) {
            $tid       = (int)$t['id'];
            $ownMatches = array_values(array_filter($partien, static fn($p) => (int)($p['heim_id'] ?? 0) === $tid || (int)($p['gast_id'] ?? 0) === $tid));
            usort($ownMatches, static fn($a, $b) => (int)$a['_spieltag_nummer'] <=> (int)$b['_spieltag_nummer']);
            $streaks = self::computeTeamStreaks($tid, $ownMatches);
    
            foreach ($categories as $cat) {
                $curLen = $streaks['current'][$cat];
                if ($curLen > $records[$cat]['aktuell']['len']) {
                    $records[$cat]['aktuell'] = ['len' => $curLen, 'teams' => [$t['name']]];
                } elseif ($curLen > 0 && $curLen === $records[$cat]['aktuell']['len']) {
                    $records[$cat]['aktuell']['teams'][] = $t['name'];
                }
    
                $best = $streaks['best'][$cat];
                if ($best['len'] > $records[$cat]['saison']['len']) {
                    $records[$cat]['saison'] = ['len' => $best['len'], 'teams' => [$t['name']], 'from' => $best['from'], 'to' => $best['to']];
                } elseif ($best['len'] > 0 && $best['len'] === $records[$cat]['saison']['len']) {
                    $records[$cat]['saison']['teams'][] = $t['name'];
                }
            }
        }
    
        return $records;
    }
    /**
     * Höchste(r) Heimsieg(e), Auswärtssieg(e) und die meiste(n) Tore in einer
     * Partie – ligaweit, inkl. Gleichstände (mehrere Partien mit demselben Wert).
     */
    public static function findExtremeMatches(array $partien) : array
    {
        $maxHomeMargin = -1;
        $homeWins      = [];
        $maxAwayMargin = -1;
        $awayWins      = [];
        $maxGoals      = -1;
        $mostGoals     = [];
    
        foreach ($partien as $p) {
            if ($p['h_tore'] === null || $p['g_tore'] === null) {
                continue;
            }
            $h = (int)$p['h_tore'];
            $g = (int)$p['g_tore'];
    
            if ($h > $g) {
                $margin = $h - $g;
                if ($margin > $maxHomeMargin) {
                    $maxHomeMargin = $margin;
                    $homeWins = [$p];
                } elseif ($margin === $maxHomeMargin) {
                    $homeWins[] = $p;
                }
            } elseif ($g > $h) {
                $margin = $g - $h;
                if ($margin > $maxAwayMargin) {
                    $maxAwayMargin = $margin;
                    $awayWins = [$p];
                } elseif ($margin === $maxAwayMargin) {
                    $awayWins[] = $p;
                }
            }
    
            $total = $h + $g;
            if ($total > $maxGoals) {
                $maxGoals = $total;
                $mostGoals = [$p];
            } elseif ($total === $maxGoals) {
                $mostGoals[] = $p;
            }
        }
    
        return ['homeWins' => $homeWins, 'awayWins' => $awayWins, 'mostGoals' => $mostGoals];
    }
    /**
     * Alle Detail-Statistiken für ein einzelnes Team: Tabellenposition, Punkte,
     * Sp./Pkt.-Schnitt, Tore, Siege/Niederlagen (inkl. höchster Sieg/Niederlage),
     * aktuelle Serie in Textform, Restprogramm (kommende Partien) und der
     * durchschnittliche Punkteschnitt der noch verbleibenden Gegner (als
     * einfacher Näherungswert für die Restprogramm-Bewertung).
     */
    public static function computeTeamDetailStats(int $teamId, array $teams, array $partien, array $standing) : array
    {
        $position = null;
        $row      = null;
        foreach ($standing as $i => $r) {
            if ($r['id'] === $teamId) {
                $position = $i + 1;
                $row = $r;
                break;
            }
        }
        if ($row === null) {
            $row = ['name' => '', 'sp' => 0, 'pkt' => 0, 'tore_h' => 0, 'tore_g' => 0];
        }
    
        $ppgByTeam = [];
        foreach ($standing as $r) {
            $ppgByTeam[$r['id']] = $r['sp'] > 0 ? $r['pkt'] / $r['sp'] : 0.0;
        }
    
        $ownMatches = array_values(array_filter($partien, static fn($p) => (int)($p['heim_id'] ?? 0) === $teamId || (int)($p['gast_id'] ?? 0) === $teamId));
        usort($ownMatches, static fn($a, $b) => (int)$a['_spieltag_nummer'] <=> (int)$b['_spieltag_nummer']);
    
        $wins = 0;
        $losses = 0;
        $played = 0;
        $maxWinMargin = -1;
        $bestWin  = null;
        $maxLossMargin = -1;
        $bestLoss = null;
        $remaining = [];
        $remainingOppPpg = [];
    
        foreach ($ownMatches as $p) {
            $hId = (int)$p['heim_id'];
            $isHeim = $hId === $teamId;
            $oppId  = $isHeim ? (int)$p['gast_id'] : (int)$p['heim_id'];
            $oppName = $isHeim ? ($p['gast_name'] ?? self::partieTeamName($p, 'gast')) : ($p['heim_name'] ?? self::partieTeamName($p, 'heim'));
    
            if ($p['h_tore'] === null || $p['g_tore'] === null) {
                $remaining[] = ['opp' => $oppName, 'heim' => $isHeim, 'nr' => (int)$p['_spieltag_nummer']];
                $remainingOppPpg[] = $ppgByTeam[$oppId] ?? 0.0;
                continue;
            }
    
            $played++;
            $own = $isHeim ? (int)$p['h_tore'] : (int)$p['g_tore'];
            $opp = $isHeim ? (int)$p['g_tore'] : (int)$p['h_tore'];
    
            if ($own > $opp) {
                $wins++;
                $margin = $own - $opp;
                if ($margin > $maxWinMargin) {
                    $maxWinMargin = $margin;
                    $bestWin = ['own' => $own, 'opp' => $opp, 'oppName' => $oppName, 'heim' => $isHeim, 'nr' => (int)$p['_spieltag_nummer']];
                }
            } elseif ($own < $opp) {
                $losses++;
                $margin = $opp - $own;
                if ($margin > $maxLossMargin) {
                    $maxLossMargin = $margin;
                    $bestLoss = ['own' => $own, 'opp' => $opp, 'oppName' => $oppName, 'heim' => $isHeim, 'nr' => (int)$p['_spieltag_nummer']];
                }
            }
        }
    
        $streaks = self::computeTeamStreaks($teamId, $ownMatches);
        $cur     = $streaks['current'];
        $streakLines = [];
        if ($cur['win'] > 0) {
            $streakLines[] = tf('liga_stat_streak_wins', ['n' => $cur['win']]);
        } elseif ($cur['loss'] > 0) {
            $streakLines[] = tf('liga_stat_streak_losses', ['n' => $cur['loss']]);
        } elseif ($cur['draw'] > 0) {
            $streakLines[] = tf('liga_stat_streak_draws', ['n' => $cur['draw']]);
        }
        if ($cur['unbeaten'] > 1 && $cur['unbeaten'] !== $cur['win']) {
            $streakLines[] = tf('liga_stat_streak_unbeaten', ['n' => $cur['unbeaten']]);
        }
        if ($cur['winless'] > 1 && $cur['winless'] !== $cur['loss']) {
            $streakLines[] = tf('liga_stat_streak_winless', ['n' => $cur['winless']]);
        }
    
        return [
            'name'            => $row['name'],
            'position'        => $position,
            'pkt'             => $row['pkt'],
            'sp'              => $row['sp'],
            'ppg'             => $row['sp'] > 0 ? round($row['pkt'] / $row['sp'], 2) : 0.0,
            'toreH'           => $row['tore_h'],
            'toreG'           => $row['tore_g'],
            'goalsPerGame'    => $row['sp'] > 0 ? round($row['tore_h'] / $row['sp'], 2) . ':' . round($row['tore_g'] / $row['sp'], 2) : '-',
            'wins'            => $wins,
            'winPct'          => $played > 0 ? round($wins / $played * 100, 1) : 0.0,
            'bestWin'         => $bestWin,
            'losses'          => $losses,
            'lossPct'         => $played > 0 ? round($losses / $played * 100, 1) : 0.0,
            'bestLoss'        => $bestLoss,
            'streakLines'     => $streakLines,
            'remaining'       => $remaining,
            'remainingPpgAvg' => !empty($remainingOppPpg) ? array_sum($remainingOppPpg) / count($remainingOppPpg) : null,
            'ppg'             => $row['sp'] > 0 ? round($row['pkt'] / $row['sp'], 2) : 0.0,
        ];
    }
}
