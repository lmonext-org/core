<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/TournamentTrait.php
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
trait TournamentTrait
{
    /**
     * Rundenname für eine KO-Runde. Benannte Stufen gibt es erst ab den letzten
     * 16 Mannschaften: 16 Teams → Achtelfinale, 8 → Viertelfinale, 4 → Halbfinale,
     * letzte Runde → immer "Finale". Bei mehr als 16 Teams heißt es schlicht
     * "Runde {nummer}" nach der tatsächlichen, fortlaufenden Rundennummer.
     */
    public static function koRoundName(int $teamCount, bool $isFinalRound, int $roundNummer) : string
    {
        if ($isFinalRound) {
            return tf('liga_round_finale');
        }
        return match ($teamCount) {
            4  => tf('liga_round_halbfinale'),
            8  => tf('liga_round_viertelfinale'),
            16 => tf('liga_round_achtelfinale'),
            default => tf('liga_round_generic', ['n' => $roundNummer]),
        };
    }
    /** Anzeigename einer Runde/eines Spieltags (KO: nach Teamanzahl, Liga: "Spieltag N"). */
    public static function roundDisplayName(array $st, bool $isKO, int $maxNr) : string
    {
        if (!$isKO) {
            return tf('liga_label_matchday', ['n' => $st['nummer']]);
        }
        $isFinal   = (int)$st['nummer'] === $maxNr;
        $teamCount = (int)($st['pairing_count'] ?? 0) * 2;
        return self::koRoundName($teamCount, $isFinal, (int)$st['nummer']);
    }
    /**
     * Gruppiert Partien einer Runde nach Paarung (dem Teil von spiel_nr vor dem
     * "_", z.B. "1" bei "1_1"/"1_2"), sortiert nach Paarungsnummer. Für die
     * letzte KO-Runde ist Gruppe 1 das Finale, Gruppe 2 (falls vorhanden) das
     * Spiel um Platz 3.
     *
     * @return array<int, array> Liste von Partien-Gruppen.
     */
    public static function groupPartienByPairing(array $partien) : array
    {
        $groups = [];
        foreach ($partien as $p) {
            $prefix = explode('_', (string)$p['spiel_nr'])[0];
            $groups[$prefix][] = $p;
        }
        uksort($groups, static fn($a, $b) => (int)$a <=> (int)$b);
        return array_values($groups);
    }
    /**
     * Ordnet die Paarungen aller Runden so um, dass sie sich im Turnierbaum
     * korrekt untereinander ausrichten: für jede Runde werden die zwei Paarungen
     * der Vorrunde, deren Team (per Team-ID) in einer Paarung dieser Runde
     * weiterspielt, direkt nebeneinander positioniert – rückwärts von der
     * letzten Runde aus aufgebaut. Kann eine Zuordnung nicht per Team-ID
     * ermittelt werden (z.B. eine noch unbestimmte Paarung mit reinem
     * Platzhalter-Label ohne echte Team-ID), bleiben die betroffenen Paarungen
     * in ihrer ursprünglichen Reihenfolge.
     *
     * @param array $rounds Liste von Runden; jede Runde eine Liste von Paarungen
     *        mit mindestens den Schlüsseln 'heim_id' und 'gast_id'.
     * @return array Gleiche Struktur, aber je Runde neu sortiert.
     */
    public static function reorderBracketPairings(array $rounds) : array
    {
        $n = count($rounds);
        if ($n <= 1) {
            return $rounds;
        }
    
        $ordered = [];
        $ordered[$n - 1] = $rounds[$n - 1];
        $dummyId = self::getDummyTeamId();
    
        for ($r = $n - 2; $r >= 0; $r--) {
            $nextOrdered  = $ordered[$r + 1];
            $current      = $rounds[$r];
            $currentCount = count($current);
            $used         = array_fill(0, $currentCount, false);
    
            // Alle Team-Slots der Folgerunde einsammeln, aber höchstens so viele
            // verarbeiten, wie es Paarungen in der aktuellen Runde gibt. Ohne diese
            // Begrenzung würde z.B. bei einer letzten Runde mit Finale + Spiel um
            // Platz 3 jede Halbfinal-Paarung ZWEIMAL angefragt (einmal als Sieger-,
            // einmal als Verlierer-Quelle) und die zweite Anfrage liefe ins Leere,
            // weil die Paarung nach der ersten Zuordnung schon als "verwendet" gilt.
            $slots = [];
            foreach ($nextOrdered as $nextPairing) {
                $slots[] = (int)($nextPairing['heim_id'] ?? 0);
                $slots[] = (int)($nextPairing['gast_id'] ?? 0);
            }
            $slots = array_slice($slots, 0, $currentCount);
    
            $newOrder = [];
            foreach ($slots as $teamId) {
                $matchIdx = null;
                if ($teamId > 0 && $teamId !== $dummyId) {
                    foreach ($current as $idx => $pairing) {
                        if ($used[$idx]) {
                            continue;
                        }
                        if ((int)$pairing['heim_id'] === $teamId || (int)$pairing['gast_id'] === $teamId) {
                            $matchIdx = $idx;
                            break;
                        }
                    }
                }
                $newOrder[] = $matchIdx;
                if ($matchIdx !== null) {
                    $used[$matchIdx] = true;
                }
            }
    
            // Nicht per Team-ID zuordenbare Slots (null) mit den übrig gebliebenen
            // Paarungen in ihrer ursprünglichen Reihenfolge auffüllen.
            $leftover = [];
            foreach ($current as $idx => $pairing) {
                if (!$used[$idx]) {
                    $leftover[] = $idx;
                }
            }
            foreach ($newOrder as $i => $idx) {
                if ($idx === null) {
                    $newOrder[$i] = array_shift($leftover);
                }
            }
    
            $ordered[$r] = array_map(static fn($idx) => $current[$idx], $newOrder);
        }
    
        ksort($ordered);
        return $ordered;
    }
}
