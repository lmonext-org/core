<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/StandingsTrait.php
 * Fileversion: 1.13.5
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

use LMOnext\Sport\VolleyballProfile;

/**
 * Extracted from the legacy frontend/data_liga.php.
 * Behavior is intentionally preserved; public compatibility wrappers live in frontend/data_liga.php.
 */
trait StandingsTrait
{
    /**
     * Statistik für einen Spieltag: Schnitt Heim-/Gast-Tore, Gesamttore, Tore/Spiel
     * – nur aus tatsächlich gespielten Partien berechnet.
     */
    public static function computeSpieltagStats(array $partien) : array
    {
        $heimTore = 0;
        $gastTore = 0;
        $gespielt = 0;
        foreach ($partien as $p) {
            if ($p['h_tore'] !== null && $p['g_tore'] !== null) {
                $heimTore += (int)$p['h_tore'];
                $gastTore += (int)$p['g_tore'];
                $gespielt++;
            }
        }
        $gesamtTore = $heimTore + $gastTore;
        return [
            'schnittHeim'  => $gespielt > 0 ? round($heimTore / $gespielt, 2) : 0,
            'schnittGast'  => $gespielt > 0 ? round($gastTore / $gespielt, 2) : 0,
            'tore'         => $gesamtTore,
            'toreProSpiel' => $gespielt > 0 ? round($gesamtTore / $gespielt, 2) : 0,
        ];
    }
    /**
     * Berechnet die Tabelle: Sp/S/U/N/Tore/Diff/Pkt je Team, sortiert nach
     * Punkte → Tordifferenz → Tore (wie im Adminbereich). Startet mit allen
     * gemeldeten Teams (auch ohne gespielte Partie, dann mit lauter Nullen).
     */
    /**
     * Berechnet die "Wertung" (angerechnetes Ergebnis) für eine Grüne-Tisch-
     * Entscheidung (Sportgericht), nach DFB-Rechts- und Verfahrensordnung:
     *
     * Standardfall: die "unschuldige" (siegende) Mannschaft wird mit 3:0
     * gewertet, die "schuldige" (unterlegene) mit 0:3.
     *
     * Ausnahme: wurde das Spiel tatsächlich ausgetragen UND hat die
     * unschuldige Mannschaft real mit MEHR als 3 Toren Differenz gewonnen
     * (z.B. real 4:0 oder 5:1), bleibt ihr tatsächlich erzieltes Torergebnis
     * bestehen. Die schuldige Mannschaft bekommt in jedem Fall 0 Tore
     * gutgeschrieben - unabhängig davon, was real erzielt wurde.
     *
     * @param int      $entscheidung 1 = Heimteam siegt (Gastteam schuldig),
     *                                2 = Gastteam siegt (Heimteam schuldig)
     * @param int|null $hTore        real eingetragenes Heim-Ergebnis (falls vorhanden)
     * @param int|null $gTore        real eingetragenes Gast-Ergebnis (falls vorhanden)
     * @return array{h_tore:int,g_tore:int} angerechnetes (gewertetes) Ergebnis
     */
    /**
     * Berechnet die "Wertung" (angerechnetes Ergebnis) für eine Grüne-Tisch-
     * Entscheidung (Sportgericht), nach der DFB-Spielordnung - die zwischen
     * ZWEI Szenarien unterscheidet
     * (ersetzt die vorherige pauschale FIFA/UEFA-Regel "immer 3:0"):
     *
     * 1. Das Spiel HAT STATTGEFUNDEN (wurde abgebrochen oder nachträglich
     *    z.B. wegen eines nicht spielberechtigten Akteurs gewertet) -
     *    erkennbar daran, dass ein reales Ergebnis eingetragen wurde (auch
     *    ein Teilergebnis bei Abbruch zählt als "stattgefunden"):
     *    Standardwertung 2:0 für die siegende Mannschaft.
     * 2. Das Spiel hat NICHT STATTGEFUNDEN (Nichtantritt/kurzfristige
     *    Absage) - erkennbar daran, dass KEIN reales Ergebnis eingetragen
     *    ist: Standardwertung 3:0 (Maximalstrafe, da kein sportlicher
     *    Aufwand stattfand).
     *
     * In beiden Fällen gilt dieselbe Ausnahme: hat die unschuldige
     * Mannschaft real mit MEHR Toren Differenz gewonnen als die jeweilige
     * Standardwertung vorsieht (>2 bzw. real vorhanden), bleibt ihr
     * tatsächlich erzieltes Torergebnis bestehen. Die schuldige Mannschaft
     * bekommt in jedem Fall 0 Tore gutgeschrieben - unabhängig davon, was
     * real erzielt wurde.
     *
     * @param int      $entscheidung 1 = Heimteam siegt (Gastteam schuldig),
     *                                2 = Gastteam siegt (Heimteam schuldig)
     * @param int|null $hTore        real eingetragenes Heim-Ergebnis (falls vorhanden)
     * @param int|null $gTore        real eingetragenes Gast-Ergebnis (falls vorhanden)
     * @return array{h_tore:int,g_tore:int} angerechnetes (gewertetes) Ergebnis
     */
    /**
     * Berechnet die "Wertung" (angerechnetes Ergebnis) für eine Grüne-Tisch-
     * Entscheidung (Sportgericht). Die beiden Standardwerte (Tore für die
     * siegende Mannschaft je nach Szenario) sind PRO LIGA
     * einstellbar (siehe liga_options "GtToreGespielt"/"GtToreNichtantritt",
     * Zu den 21 DFB-Landesverbänden: die genaue
     * Ausgestaltung ist NICHT bundeseinheitlich - z.B. 2:0 bei den meisten
     * west-/norddeutschen Verbänden, 5:0 beim BFV/HFV/Badischen FV). Die
     * Parameter-Defaults (2/3) sind der bisherige DFB-Standard und greifen,
     * wenn ein Aufrufer keine Liga-spezifischen Werte übergibt.
     *
     * 1. Das Spiel HAT STATTGEFUNDEN (wurde abgebrochen oder nachträglich
     *    z.B. wegen eines nicht spielberechtigten Akteurs gewertet) -
     *    erkennbar daran, dass ein reales Ergebnis eingetragen wurde (auch
     *    ein Teilergebnis bei Abbruch zählt als "stattgefunden"):
     *    Standardwertung $toreGespielt:0 für die siegende Mannschaft.
     * 2. Das Spiel hat NICHT STATTGEFUNDEN (Nichtantritt/kurzfristige
     *    Absage) - erkennbar daran, dass KEIN reales Ergebnis eingetragen
     *    ist: Standardwertung $toreNichtantritt:0.
     *
     * In beiden Fällen gilt dieselbe Ausnahme: hat die unschuldige
     * Mannschaft real mit MEHR Toren Differenz gewonnen als die jeweilige
     * Standardwertung vorsieht, bleibt ihr tatsächlich erzieltes
     * Torergebnis bestehen. Die schuldige Mannschaft bekommt in jedem Fall
     * 0 Tore gutgeschrieben - unabhängig davon, was real erzielt wurde.
     *
     * @param int      $entscheidung 1 = Heimteam siegt (Gastteam schuldig),
     *                                2 = Gastteam siegt (Heimteam schuldig)
     * @param int      $entscheidung 1 = Heimteam siegt (Gastteam schuldig),
     *                                2 = Gastteam siegt (Heimteam schuldig),
     *                                3 = beide Mannschaften verlieren
     *                                (Praxisbeispiel DFB: kollektiver
     *                                Spielabbruch, gemeinsames Verlassen des
     *                                Spielfelds, nicht spielberechtigte
     *                                Akteure auf beiden Seiten, beidseitiger
     *                                Nichtantritt - siehe computeStandings(),
     *                                wo beide Teams UNABHÄNGIG voneinander
     *                                als Verlierer mit je 0 geschossenen/
     *                                GtToreBeideVerlieren kassierten Toren
     *                                gezählt werden, nicht über ein
     *                                symmetrisches h_tore/g_tore-Paar wie bei
     *                                1/2)
     * @param int|null $hTore        real eingetragenes Heim-Ergebnis (falls vorhanden)
     * @param int|null $gTore        real eingetragenes Gast-Ergebnis (falls vorhanden)
     * @param int      $toreGespielt      Standardtore für die siegende Mannschaft, wenn das Spiel stattfand (Liga-Einstellung, Default 2)
     * @param int      $toreNichtantritt  Standardtore für die siegende Mannschaft bei Nichtantritt (Liga-Einstellung, Default 3)
     * @return array{h_tore:int,g_tore:int} angerechnetes (gewertetes) Ergebnis
     *                                       zur ANZEIGE als ein einzelnes Paar
     *                                       - bei entscheidung=3 bewusst
     *                                       "0:0" (es gibt kein sinnvolles
     *                                       einzelnes Paar für zwei
     *                                       unabhängige 0:X-Niederlagen, die
     *                                       eigentliche Tordifferenz-Wertung
     *                                       läuft über computeStandings())
     */
    public static function gtCreditedScore(int $entscheidung, ?int $hTore, ?int $gTore, int $toreGespielt = 2, int $toreNichtantritt = 2) : array
    {
        $spielHatStattgefunden = $hTore !== null && $gTore !== null;
        $standardTore = $spielHatStattgefunden ? $toreGespielt : $toreNichtantritt;

        if ($entscheidung === 1) {
            // Heimteam ist unschuldig/siegt, Gastteam ist schuldig.
            $realWarSiegFuerUnschuldig = $spielHatStattgefunden && $hTore > $gTore;
            $unschuldigTore = ($realWarSiegFuerUnschuldig && $hTore > $standardTore) ? $hTore : $standardTore;
            return ['h_tore' => $unschuldigTore, 'g_tore' => 0];
        }
        if ($entscheidung === 2) {
            // Gastteam ist unschuldig/siegt, Heimteam ist schuldig.
            $realWarSiegFuerUnschuldig = $spielHatStattgefunden && $gTore > $hTore;
            $unschuldigTore = ($realWarSiegFuerUnschuldig && $gTore > $standardTore) ? $gTore : $standardTore;
            return ['h_tore' => 0, 'g_tore' => $unschuldigTore];
        }
        // entscheidung=3 (beide verlieren) UND jeder andere/unbekannte Wert:
        // "0:0" zur Anzeige - bei 3 bewusst (siehe Docblock oben), bei einem
        // wirklich unbekannten Wert weiterhin als defensiver Fallback.
        return ['h_tore' => 0, 'g_tore' => 0];
    }

    /**
     * Berechnet die Tabelle. $mode steuert, welche Seite pro Partie gezählt
     * wird - 'overall' (Standard, beide Seiten), 'home' (nur wenn das Team
     * Heimmannschaft war) oder 'away' (nur Auswärtsspiele) - für die
     * Heim-/Auswärts-Tabelle (Beitrag: Torsten Hofmann).
     */
    public static function computeStandings(array $teamsList, array $partien, array $ligaOptions, ?int $ligaId = null, string $mode = 'overall', ?int $currentSpieltag = null) : array
    {
        $ptW = (int)($ligaOptions['PointsForWin']  ?? 3);
        $ptD = (int)($ligaOptions['PointsForDraw'] ?? 1);
        $ptL = (int)($ligaOptions['PointsForLost'] ?? 0);
        // Eigene Punktwerte für "nach Verlängerung" (status=2, "n.V.") und "nach
        // Elfmeterschießen" (status=1, "i.E."), analog zum alten LMO. Fallen
        // mangels expliziter Einstellung auf die normalen Werte zurück – damit
        // ändert sich für alle Ligen, die diese neuen Felder noch nie gesetzt
        // haben, an der Punktevergabe nichts (volle Rückwärtskompatibilität).
        $ptWET = (int)($ligaOptions['PointsForWinET']  ?? $ptW);
        $ptDET = (int)($ligaOptions['PointsForDrawET'] ?? $ptD);
        $ptLET = (int)($ligaOptions['PointsForLostET'] ?? $ptL);
        $ptWPS = (int)($ligaOptions['PointsForWinPS']  ?? $ptW);
        $ptDPS = (int)($ligaOptions['PointsForDrawPS'] ?? $ptD);
        $ptLPS = (int)($ligaOptions['PointsForLostPS'] ?? $ptL);

        // Sport-Profil (Beitrag: Torsten Hofmann, siehe src/Sport/) - bei
        // Volleyball wird die Punktevergabe satzabhängig statt über die
        // normale Sieg/Unentschieden/Niederlage-Tabelle berechnet (3:0/3:1 =
        // 3 Punkte, 3:2 = 2 Punkte für den Sieger/1 für den Verlierer). Alle
        // anderen Sportarten (inkl. der Standardwert 'football' für jede
        // bestehende Liga) nutzen unverändert die bisherige Logik.
        $sportType = $ligaId !== null ? self::getLigaSportType($ligaId) : 'football';
        $isVolleyball = $sportType === 'volleyball';
    
        $rows = [];
        foreach ($teamsList as $t) {
            $rows[(int)$t['id']] = [
                'id' => (int)$t['id'], 'name' => $t['name'], 'kurz' => $t['kurz'] ?? '',
                'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0,
                'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0, 'minuspunkte' => 0,
                'strafpunkte' => 0, 'straftore' => 0, 'torekorrektur' => 0, 'minuspunktekorrektur' => 0, 'strafgrund' => '',
                // Volleyball-Detailstatistik (Beitrag: Torsten Hofmann) - bei
                // anderen Sportarten bleiben diese Felder einfach bei 0.
                'w30' => 0, 'w31' => 0, 'w32' => 0, 'l23' => 0, 'l13' => 0, 'l03' => 0,
                'balls_h' => 0, 'balls_g' => 0,
            ];
        }
    
        foreach ($partien as $p) {
            $gtEntscheidung = (int)($p['gt_entscheidung'] ?? 0);
            // Grüne-Tisch-Entscheidung (siehe gtCreditedScore() oben): ein so
            // gewertetes Spiel zählt AUCH OHNE real eingetragenes Ergebnis
            // (z.B. Nichtantritt) - der normale "kein Ergebnis => skip"-Check
            // gilt hier bewusst nicht.
            if ($gtEntscheidung === 0 && ($p['h_tore'] === null || $p['g_tore'] === null)) {
                continue;
            }
            // "nicht_gewertet" (rückwirkende Annullierung ALLER
            // Spiele eines Teams, z.B. bei Lizenzentzug/Spielbetrieb-
            // Einstellung, siehe ensureSpielstatusColumns() in
            // admin/bootstrap.php) - ein so markiertes Spiel zählt für KEINES
            // der beiden Teams (weder Sp/S/U/N noch Tore/Punkte), obwohl ein
            // Ergebnis eingetragen sein kann. ?? 0 deckt sowohl fehlendes
            // Array-Element (Spalte auf dieser Installation noch nicht
            // migriert) als auch NULL ab.
            if ((int)($p['nicht_gewertet'] ?? 0) === 1) {
                continue;
            }
            $hId = (int)($p['heim_id'] ?? 0);
            $gId = (int)($p['gast_id'] ?? 0);
            if ($hId <= 0 || $gId <= 0) {
                continue;
            }
            if (!isset($rows[$hId])) {
                $rows[$hId] = ['id' => $hId, 'name' => $p['heim_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0, 'minuspunkte' => 0, 'strafpunkte' => 0, 'straftore' => 0, 'torekorrektur' => 0, 'minuspunktekorrektur' => 0, 'strafgrund' => '', 'w30' => 0, 'w31' => 0, 'w32' => 0, 'l23' => 0, 'l13' => 0, 'l03' => 0, 'balls_h' => 0, 'balls_g' => 0];
            }
            if (!isset($rows[$gId])) {
                $rows[$gId] = ['id' => $gId, 'name' => $p['gast_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0, 'minuspunkte' => 0, 'strafpunkte' => 0, 'straftore' => 0, 'torekorrektur' => 0, 'minuspunktekorrektur' => 0, 'strafgrund' => '', 'w30' => 0, 'w31' => 0, 'w32' => 0, 'l23' => 0, 'l13' => 0, 'l03' => 0, 'balls_h' => 0, 'balls_g' => 0];
            }
    
            if ($gtEntscheidung === 3) {
                // Beide Mannschaften verlieren (- Praxisbeispiel
                // DFB: kollektiver Spielabbruch, gemeinsames Verlassen des
                // Spielfelds, nicht spielberechtigte Akteure auf beiden
                // Seiten, beidseitiger Nichtantritt - siehe
                // gtCreditedScore()-Docblock). Beide Teams zählen UNABHÄNGIG
                // voneinander als Niederlage mit je 0 geschossenen/
                // GtToreBeideVerlieren kassierten Toren - das lässt sich
                // NICHT über das normale, symmetrische h_tore/g_tore-Paar
                // abbilden (dort würde das eine Team zwangsläufig genau die
                // Tore "gewinnen", die das andere "verliert"), deshalb hier
                // ein eigener, vollständiger Verarbeitungspfad für diese
                // Partie statt der Logik weiter unten. minuspunkte
                // konsistent mit der normalen Niederlage-Semantik (dort
                // immer $curW, unabhängig vom tatsächlichen Gegner-Resultat).
                $strafTore = (int)($ligaOptions['GtToreBeideVerlieren'] ?? 2);
                [$curW3, , $curL3] = match ((int)($p['status'] ?? 0)) {
                    1       => [$ptWPS, $ptDPS, $ptLPS],
                    2       => [$ptWET, $ptDET, $ptLET],
                    default => [$ptW, $ptD, $ptL],
                };
                $trackHome = $mode !== 'away';
                $trackAway = $mode !== 'home';
                if ($trackHome) {
                    $rows[$hId]['sp']++;
                    $rows[$hId]['n']++;
                    $rows[$hId]['tore_g'] += $strafTore;
                    $rows[$hId]['pkt'] += $curL3;
                    $rows[$hId]['minuspunkte'] += $curW3;
                }
                if ($trackAway) {
                    $rows[$gId]['sp']++;
                    $rows[$gId]['n']++;
                    $rows[$gId]['tore_g'] += $strafTore;
                    $rows[$gId]['pkt'] += $curL3;
                    $rows[$gId]['minuspunkte'] += $curW3;
                }
                continue;
            }

            if ($gtEntscheidung === 1 || $gtEntscheidung === 2) {
                $credited = self::gtCreditedScore(
                    $gtEntscheidung,
                    $p['h_tore'] !== null ? (int)$p['h_tore'] : null,
                    $p['g_tore'] !== null ? (int)$p['g_tore'] : null,
                    (int)($ligaOptions['GtToreGespielt'] ?? 2),
                    (int)($ligaOptions['GtToreNichtantritt'] ?? 2)
                );
                $ht = $credited['h_tore'];
                $gt = $credited['g_tore'];
            } else {
                $ht = (int)$p['h_tore'];
                $gt = (int)$p['g_tore'];
            }

            // Heim-/Auswärts-Filter: 'home' zählt nur die Heimmannschaft dieser
            // Partie, 'away' nur die Gastmannschaft, 'overall' (Standard) beide.
            $trackHome = $mode !== 'away';
            $trackAway = $mode !== 'home';

            if ($trackHome) {
                $rows[$hId]['sp']++;
                $rows[$hId]['tore_h'] += $ht;
                $rows[$hId]['tore_g'] += $gt;
            }
            if ($trackAway) {
                $rows[$gId]['sp']++;
                $rows[$gId]['tore_h'] += $gt;
                $rows[$gId]['tore_g'] += $ht;
            }
    
            // status: 0 = regulär, 1 = i.E. (Elfmeterschießen), 2 = n.V. (nach
            // Verlängerung) – siehe statusSuffix(). Je nachdem gilt eine andere
            // Sieg/Unentschieden/Niederlage-Punktetabelle.
            [$curW, $curD, $curL] = match ((int)($p['status'] ?? 0)) {
                1       => [$ptWPS, $ptDPS, $ptLPS],
                2       => [$ptWET, $ptDET, $ptLET],
                default => [$ptW, $ptD, $ptL],
            };

            // KRITISCHER Bugfix (gemeldet, Praxisbeispiel: DDR-Fußball-
            // Oberliga 1962/63, wo Magdeburg zweimal per Grüner-Tisch-
            // Entscheidung mit 0:0 UND einem Sieg für den Gegner gewertet
            // wurde, weil kein sportärztlicher Nachweis erbracht wurde -
            // historisch korrekt nachstellbar, indem GtToreGespielt/
            // GtToreNichtantritt auf 0 gesetzt werden): weiter unten wird
            // Sieg/Unentschieden/Niederlage bisher AUSSCHLIESSLICH aus dem
            // Vergleich von $ht/$gt (den "gewerteten" Toren aus
            // gtCreditedScore()) abgeleitet. Werden die Vorgabe-Tore auf 0
            // gesetzt, liefert gtCreditedScore() für BEIDE Teams 0 zurück -
            // rechnerisch nicht mehr von einem echten Unentschieden zu
            // unterscheiden, obwohl gt_entscheidung einen klaren Sieger
            // festlegt. Der Sieger einer Grünen-Tisch-Entscheidung steht
            // aber per Definition fest, unabhängig vom (ggf. bewusst auf
            // 0:0 gesetzten) Ergebnis - deshalb hier direkt aus
            // $gtEntscheidung ermittelt, mit Vorrang vor jedem Toreverlgeich
            // weiter unten (auch bei Volleyball, siehe dort).
            $gtHeimGewinnt = match ($gtEntscheidung) {
                1       => true,
                2       => false,
                default => null, // keine GT-Entscheidung -> normaler Torevergleich
            };

            // Volleyball: satzabhängige Punkte (3:0/3:1 = 3, 3:2 = 2/1) +
            // Detail-Statistik (w30/w31/w32/l23/l13/l03, Ballpunkte aus
            // extra_data) statt der normalen Sieg/Unentschieden/Niederlage-
            // Punktetabelle (Beitrag: Torsten Hofmann). Bei einer Grünen-
            // Tisch-Entscheidung gibt es kein echtes Satzergebnis, das
            // computeMatchPoints() sinnvoll auswerten könnte - der Sieger
            // bekommt hier bewusst die vollen "3:0"-Punkte, der Verlierer 0
            // (siehe $gtHeimGewinnt oben).
            if ($isVolleyball) {
                $vbPts = $gtHeimGewinnt !== null
                    ? ['home_pts' => $gtHeimGewinnt ? 3 : 0, 'guest_pts' => $gtHeimGewinnt ? 0 : 3]
                    : (new VolleyballProfile())->computeMatchPoints($ht, $gt);
                $ballsH = 0; $ballsG = 0;
                $extraData = $p['extra_data'] ?? null;
                if ($extraData !== null) {
                    $decoded = json_decode($extraData, true);
                    if (is_array($decoded)) {
                        $sets = $decoded['sets'] ?? $decoded['set'] ?? null;
                        if (is_array($sets)) {
                            foreach ($sets as $set) {
                                $ballsH += (int)($set['h'] ?? 0);
                                $ballsG += (int)($set['g'] ?? 0);
                            }
                        }
                    }
                }
                // Bei einer Grünen-Tisch-Entscheidung entscheidet
                // $gtHeimGewinnt (siehe oben), sonst wie bisher der
                // Torevergleich. $catH/$catG (für w30/w31/w32/l03/l13/l23)
                // bleiben unverändert aus $ht/$gt - bei einer GT-Entscheidung
                // mit auf 0 gesetzten Vorgabe-Toren trifft dann bewusst keine
                // der "3:0"/"3:1"/"3:2"-Kategorien zu (es gab ja kein echtes
                // Satzergebnis), die Zähler bleiben in dem Fall einfach bei 0.
                if ($gtHeimGewinnt ?? ($ht > $gt)) {
                    $catH = "{$ht}:{$gt}"; // "3:0", "3:1" oder "3:2"
                    if ($trackHome) {
                        $rows[$hId]['s']++;
                        $rows[$hId]['pkt'] += $vbPts['home_pts'];
                        $rows[$hId]['minuspunkte'] += $vbPts['guest_pts'];
                        $rows[$hId]['balls_h'] += $ballsH;
                        $rows[$hId]['balls_g'] += $ballsG;
                        if ($catH === '3:0') { $rows[$hId]['w30']++; }
                        elseif ($catH === '3:1') { $rows[$hId]['w31']++; }
                        elseif ($catH === '3:2') { $rows[$hId]['w32']++; }
                    }
                    if ($trackAway) {
                        $rows[$gId]['n']++;
                        $rows[$gId]['pkt'] += $vbPts['guest_pts'];
                        $rows[$gId]['minuspunkte'] += $vbPts['home_pts'];
                        $rows[$gId]['balls_h'] += $ballsG;
                        $rows[$gId]['balls_g'] += $ballsH;
                        $catG = "{$gt}:{$ht}"; // "0:3", "1:3" oder "2:3"
                        if ($catG === '0:3') { $rows[$gId]['l03']++; }
                        elseif ($catG === '1:3') { $rows[$gId]['l13']++; }
                        elseif ($catG === '2:3') { $rows[$gId]['l23']++; }
                    }
                } else {
                    $catG = "{$gt}:{$ht}"; // "3:0", "3:1" oder "3:2"
                    if ($trackAway) {
                        $rows[$gId]['s']++;
                        $rows[$gId]['pkt'] += $vbPts['guest_pts'];
                        $rows[$gId]['minuspunkte'] += $vbPts['home_pts'];
                        $rows[$gId]['balls_h'] += $ballsG;
                        $rows[$gId]['balls_g'] += $ballsH;
                        if ($catG === '3:0') { $rows[$gId]['w30']++; }
                        elseif ($catG === '3:1') { $rows[$gId]['w31']++; }
                        elseif ($catG === '3:2') { $rows[$gId]['w32']++; }
                    }
                    if ($trackHome) {
                        $rows[$hId]['n']++;
                        $rows[$hId]['pkt'] += $vbPts['home_pts'];
                        $rows[$hId]['minuspunkte'] += $vbPts['guest_pts'];
                        $rows[$hId]['balls_h'] += $ballsH;
                        $rows[$hId]['balls_g'] += $ballsG;
                        $catH = "{$ht}:{$gt}"; // "0:3", "1:3" oder "2:3"
                        if ($catH === '0:3') { $rows[$hId]['l03']++; }
                        elseif ($catH === '1:3') { $rows[$hId]['l13']++; }
                        elseif ($catH === '2:3') { $rows[$hId]['l23']++; }
                    }
                }
            } elseif ($gtHeimGewinnt ?? ($ht > $gt)) {
                // Bei einer Grünen-Tisch-Entscheidung entscheidet
                // $gtHeimGewinnt (siehe Kommentar weiter oben), unabhängig
                // vom Torevergleich - sonst (kein gt_entscheidung) wie bisher
                // rein aus $ht/$gt.
                if ($trackHome) {
                    $rows[$hId]['s']++;
                    $rows[$hId]['pkt'] += $curW;
                    $rows[$hId]['minuspunkte'] += $curL;
                }
                if ($trackAway) {
                    $rows[$gId]['n']++;
                    $rows[$gId]['pkt'] += $curL;
                    $rows[$gId]['minuspunkte'] += $curW;
                }
            } elseif ($gtHeimGewinnt === false || ($gtHeimGewinnt === null && $ht < $gt)) {
                if ($trackAway) {
                    $rows[$gId]['s']++;
                    $rows[$gId]['pkt'] += $curW;
                    $rows[$gId]['minuspunkte'] += $curL;
                }
                if ($trackHome) {
                    $rows[$hId]['n']++;
                    $rows[$hId]['pkt'] += $curL;
                    $rows[$hId]['minuspunkte'] += $curW;
                }
            } else {
                // Unentschieden kann hier nur noch bei einer ECHTEN
                // Punktgleichheit OHNE Grüne-Tisch-Entscheidung ankommen -
                // $gtHeimGewinnt ist in diesem Zweig immer null.
                if ($trackHome) {
                    $rows[$hId]['u']++;
                    $rows[$hId]['pkt'] += $curD;
                    $rows[$hId]['minuspunkte'] += $curD;
                }
                if ($trackAway) {
                    $rows[$gId]['u']++;
                    $rows[$gId]['pkt'] += $curD;
                    $rows[$gId]['minuspunkte'] += $curD;
                }
            }
        }
    
        // Straftore/Strafpunkte bzw. Bonuspunkte/Bonustore anwenden (Admin →
        // Liga-Einstellungen → Strafen, siehe getLigaStrafpunkte()) - NACH der
        // regulären Punkteberechnung, damit sie unabhängig vom gewählten
        // Punktesystem (2er/3er, n.V./i.E.) als fester Zu-/Abschlag on top
        // wirken. Alle drei Werte sind VORZEICHENBEHAFTET: positiv = Bonus,
        // negativ = Strafe - z.B. um einem Team wegen Lizenzentzugs alle
        // Saisonwerte auf 0:0/0 zu korrigieren, unabhängig vom aktuellen
        // Spielstand. $ligaId ist optional (null), damit computeStandings()
        // für Kontexte ohne Liga-Bezug (z.B. reine Was-wäre-wenn-Berechnungen)
        // unverändert ohne DB-Zugriff nutzbar bleibt.
        if ($ligaId !== null) {
            $strafen = self::getLigaStrafpunkte($ligaId);
            foreach ($strafen as $teamId => $s) {
                if (!isset($rows[$teamId])) {
                    continue; // Team ist nicht (mehr) Teil dieser Liga
                }
                // "ab Spieltag"-Filter (Beitrag: Torsten Hofmann): die Strafe
                // greift erst ab dem eingestellten Spieltag (0 = ab
                // Saisonbeginn, bisheriges Verhalten). Nützlich z.B. bei einem
                // Lizenzentzug, der erst mitten in der Saison bekannt wird -
                // beim Blick auf einen älteren Spieltag (siehe "Tabelle nach
                // Spieltag N") soll die Korrektur dort noch nicht greifen.
                // $currentSpieltag ist null bei Aufrufen ohne Spieltag-Bezug
                // (z.B. Ewige Tabelle) - dort greift die Strafe immer.
                $abSt = (int)($s['ab_spieltag'] ?? 0);
                if ($currentSpieltag !== null && $abSt > 0 && $currentSpieltag < $abSt) {
                    continue; // Strafe greift an diesem Spieltag noch nicht
                }
                $rows[$teamId]['strafpunkte']    = $s['strafpunkte'];
                $rows[$teamId]['straftore']      = $s['straftore'];
                $rows[$teamId]['torekorrektur']  = $s['tore_korrektur'];
                $rows[$teamId]['minuspunktekorrektur'] = $s['minuspunkte_korrektur'];
                $rows[$teamId]['strafgrund']     = $s['grund'];
                $rows[$teamId]['pkt']           += $s['strafpunkte'];
                $rows[$teamId]['tore_g']        += $s['straftore'];
                $rows[$teamId]['tore_h']        += $s['tore_korrektur'];
                $rows[$teamId]['minuspunkte']   += $s['minuspunkte_korrektur'];
            }
        }

        $standings = array_values($rows);
        usort($standings, static function (array $a, array $b) : int {
            if ($a['pkt'] !== $b['pkt']) {
                return $b['pkt'] <=> $a['pkt'];
            }
            $diffA = $a['tore_h'] - $a['tore_g'];
            $diffB = $b['tore_h'] - $b['tore_g'];
            if ($diffA !== $diffB) {
                return $diffB <=> $diffA;
            }
            return $b['tore_h'] <=> $a['tore_h'];
        });
    
        return $standings;
    }

    /**
     * Form der letzten 5 Spiele je Team (Beitrag: Torsten Hofmann). Liefert
     * [teamId => ['w'=>int,'d'=>int,'l'=>int,'dots'=>HTML]]. $partien sollte
     * bereits auf den gewünschten Zeitpunkt gefiltert sein (z.B. nur bis zu
     * einem bestimmten Spieltag, siehe renderStandingsView()) - "die letzten
     * 5" bezieht sich dann korrekt auf die letzten 5 innerhalb dieser Auswahl.
     *
     * Jeder Punkt bekommt jetzt zusätzlich einen Mouseover-Tooltip mit den
     * Details der jeweiligen Begegnung (Gegner, Ergebnis, Spieltag, Datum).
     * $teamsById (id => Team-Zeile mit 'name') wird für die
     * Gegner-Namen gebraucht; ohne Angabe (Standard: leeres Array) werden die
     * Punkte wie bisher ohne Tooltip gerendert (abwärtskompatibel für evtl.
     * andere Aufrufer). Siehe computeNextMatchDot() für den analogen 6.
     * "nächste Begegnung"-Pfeil.
     */
    public static function computeLast5Form(
        array $partien,
        string $mode = 'overall',
        array $teamsById = [],
        string $dateFormat = 'd.m.Y H:i',
        bool $showLogos = false
    ) : array {
        $matchesByTeam = [];
        foreach ($partien as $p) {
            $nr = (int)($p['_spieltag_nummer'] ?? 0);
            if (!isset($p['heim_id'], $p['gast_id']) || $p['h_tore'] === null || $p['g_tore'] === null) {
                continue;
            }
            $ht = (int)$p['h_tore'];
            $gt = (int)$p['g_tore'];
            $hId = (int)$p['heim_id'];
            $gId = (int)$p['gast_id'];
            $zeit = $p['zeit'] ?? null;

            $trackHome = $mode !== 'away';
            $trackAway = $mode !== 'home';

            if ($ht > $gt) {
                if ($trackHome) $matchesByTeam[$hId][] = ['nr'=>$nr,'result'=>'w','opponent_id'=>$gId,'own'=>$ht,'opp'=>$gt,'home'=>true,'zeit'=>$zeit];
                if ($trackAway) $matchesByTeam[$gId][] = ['nr'=>$nr,'result'=>'l','opponent_id'=>$hId,'own'=>$gt,'opp'=>$ht,'home'=>false,'zeit'=>$zeit];
            } elseif ($ht < $gt) {
                if ($trackAway) $matchesByTeam[$gId][] = ['nr'=>$nr,'result'=>'w','opponent_id'=>$hId,'own'=>$gt,'opp'=>$ht,'home'=>false,'zeit'=>$zeit];
                if ($trackHome) $matchesByTeam[$hId][] = ['nr'=>$nr,'result'=>'l','opponent_id'=>$gId,'own'=>$ht,'opp'=>$gt,'home'=>true,'zeit'=>$zeit];
            } else {
                if ($trackHome) $matchesByTeam[$hId][] = ['nr'=>$nr,'result'=>'d','opponent_id'=>$gId,'own'=>$ht,'opp'=>$gt,'home'=>true,'zeit'=>$zeit];
                if ($trackAway) $matchesByTeam[$gId][] = ['nr'=>$nr,'result'=>'d','opponent_id'=>$hId,'own'=>$gt,'opp'=>$ht,'home'=>false,'zeit'=>$zeit];
            }
        }

        $formByTeam = [];
        foreach ($matchesByTeam as $teamId => $matches) {
            usort($matches, static fn($a, $b) => $a['nr'] <=> $b['nr']);
            $last5 = array_slice($matches, -5);

            $w = $d = $l = 0;
            $dots = '';
            foreach ($last5 as $m) {
                if ($m['result'] === 'w') { $w++; $cls = 'form-win'; }
                elseif ($m['result'] === 'd') { $d++; $cls = 'form-draw'; }
                else { $l++; $cls = 'form-loss'; }
                $dots .= $teamsById === []
                    ? '<span class="form-dot ' . $cls . '"></span>'
                    : self::renderFormDotWithTooltip($cls, '', $teamId, $m, $teamsById, $dateFormat, $showLogos);
            }
            $formByTeam[$teamId] = ['w' => $w, 'd' => $d, 'l' => $l, 'dots' => $dots];
        }
        return $formByTeam;
    }

    /**
     * Ermittelt je Team die nächste, noch nicht gespielte Begegnung, als 6.
     * Punkt/Pfeil neben den 5 Form-Punkten (analog zu
     * computeLast5Form()). WICHTIG: $allPartien muss UNGEFILTERT sein (auch
     * künftige Spieltage enthalten) - anders als bei computeLast5Form(), das
     * nur bereits gespielte Partien auswertet. Liefert [teamId => HTML] - fehlt ein Team hier (z.B. letzter Spieltag der
     * Saison bereits erreicht), hat es schlicht keine nächste Begegnung mehr -
     * dann wird für dieses Team logischerweise auch kein 6. Pfeil gerendert
     * (siehe Aufrufstelle: $nextDotByTeam[$tid] ?? ''), was automatisch auch
     * den Fall "Saison komplett zu Ende, alle Begegnungen eingetragen" richtig
     * abdeckt - ohne eine unbespielte Partie bleibt für kein Team mehr etwas
     * übrig, der Pfeil verschwindet also von ganz allein.
     *
     * WICHTIG: die "nächste Begegnung" ist bewusst NICHT als "erste Partie mit
     * Spieltag-Nummer > $afterNr" definiert (das war ein Bug: an einem
     * Spieltag, der sich über mehrere Tage erstreckt, hätte das bei einem
     * Team, das seine Partie dieses Spieltags noch nicht gespielt hat, genau
     * diese noch unbespielte Partie übersprungen und stattdessen gleich den
     * übernächsten Spieltag gezeigt). Stattdessen zählt einzig und allein der
     * Ergebnis-Status: die erste (nach Spieltag-Nummer sortierte) noch nicht
     * eingetragene Partie des Teams, unabhängig davon, ob ihre Nummer <= oder
     * > $afterNr ist.
     */
    public static function computeNextMatchDot(
        array $allPartien,
        array $teamsById,
        string $dateFormat = 'd.m.Y H:i',
        bool $showLogos = false,
        string $mode = 'overall'
    ) : array {
        $trackHome = $mode !== 'away';
        $trackAway = $mode !== 'home';

        $byTeam = [];
        foreach ($allPartien as $p) {
            if (!isset($p['heim_id'], $p['gast_id']) || $p['h_tore'] !== null || $p['g_tore'] !== null) {
                continue; // schon gespielt (oder kein echtes Team-Paar) -> keine "nächste Begegnung" mehr
            }
            $nr = (int)($p['_spieltag_nummer'] ?? 0);
            $hId = (int)$p['heim_id'];
            $gId = (int)$p['gast_id'];
            $zeit = $p['zeit'] ?? null;
            if ($trackHome) $byTeam[$hId][] = ['nr'=>$nr,'opponent_id'=>$gId,'home'=>true,'zeit'=>$zeit];
            if ($trackAway) $byTeam[$gId][] = ['nr'=>$nr,'opponent_id'=>$hId,'home'=>false,'zeit'=>$zeit];
        }

        $result = [];
        foreach ($byTeam as $teamId => $list) {
            usort($list, static fn($a, $b) => $a['nr'] <=> $b['nr']);
            $next = $list[0] ?? null;
            if ($next === null) {
                continue;
            }
            $result[$teamId] = self::renderFormDotWithTooltip('form-next', '&#8594;', $teamId, $next, $teamsById, $dateFormat, $showLogos, true);
        }
        return $result;
    }

    /**
     * Kurzform eines Teams für die Form-Tooltips (Vorbild:
     * Beispielscreenshot mit "SPA"/"GFÜ" statt voller Vereinsnamen). Fällt
     * auf den vollen Namen zurück, falls für das Team keine Kurzform
     * gepflegt ist (Feld "kurz" leer) - besser ein längerer, aber
     * vorhandener Name als eine leere Tooltip-Zeile.
     */
    private static function teamShortName(?array $team) : string
    {
        if ($team === null) {
            return '?';
        }
        $kurz = trim((string)($team['kurz'] ?? ''));
        return $kurz !== '' ? $kurz : (string)($team['name'] ?? '?');
    }

    /**
     * Ein einzelner Form-/Nächste-Begegnung-Punkt inkl. Mouseover-Tooltip mit
     * den Begegnungsdetails. Gemeinsam von computeLast5Form() und
     * computeNextMatchDot() genutzt. $glyph ist bei den 5 normalen
     * Form-Punkten leer (reine Farbfläche wie bisher), beim 6. Punkt der
     * Pfeil "→". $isNext unterscheidet nur die Beschriftung im Tooltip
     * ("nächste Begegnung" statt Ergebnis). Zeigt beide Mannschaften in
     * echter Heim-Gast-Reihenfolge (nicht "eigenes Team zuerst") - das
     * eigene Team (dessen Zeile gerade gehovert wird) ist fett hervorgehoben,
     * damit auf einen Blick klar ist, wer wer ist.
     */
    private static function renderFormDotWithTooltip(
        string $cls,
        string $glyph,
        int $ownId,
        array $m,
        array $teamsById,
        string $dateFormat,
        bool $showLogos,
        bool $isNext = false
    ) : string {
        $oppId  = (int)$m['opponent_id'];
        $isHome = (bool)$m['home'];
        $homeId = $isHome ? $ownId : $oppId;
        $awayId = $isHome ? $oppId : $ownId;

        $homeNameHtml = h(self::teamShortName($teamsById[$homeId] ?? null));
        $awayNameHtml = h(self::teamShortName($teamsById[$awayId] ?? null));
        $homeOwnCls   = $isHome ? ' ft-own' : '';
        $awayOwnCls   = $isHome ? '' : ' ft-own';
        $homeLogo = $showLogos ? self::renderTeamLogoImg($homeId, true) : '';
        $awayLogo = $showLogos ? self::renderTeamLogoImg($awayId, true) : '';
        $venue    = $isHome ? tf('liga_form_tooltip_home') : tf('liga_form_tooltip_away');

        $date = '';
        if (!empty($m['zeit'])) {
            try {
                $date = (new \DateTime($m['zeit']))->format($dateFormat);
            } catch (\Throwable) {
                $date = '';
            }
        }

        if ($isNext) {
            $scoreHtml = '<b class="ft-score ft-score-open">&ndash;&nbsp;:&nbsp;&ndash;</b>';
        } else {
            $homeGoals = $isHome ? (int)$m['own'] : (int)$m['opp'];
            $awayGoals = $isHome ? (int)$m['opp'] : (int)$m['own'];
            $scoreHtml = '<b class="ft-score">' . $homeGoals . '&nbsp;:&nbsp;' . $awayGoals . '</b>';
        }

        // Logo + Kurzname jeweils übereinander/zentriert je Team,
        // statt einer gemeinsamen "Heim – Gast"-Zeile unter dem Ergebnis.
        $homeSide = '<span class="ft-side' . $homeOwnCls . '">' . $homeLogo
                  . '<span class="ft-side-name">' . $homeNameHtml . '</span></span>';
        $awaySide = '<span class="ft-side' . $awayOwnCls . '">' . $awayLogo
                  . '<span class="ft-side-name">' . $awayNameHtml . '</span></span>';

        return '<span class="form-dot-wrap">'
             . '<span class="form-dot ' . $cls . '">' . $glyph . '</span>'
             . '<span class="form-tooltip">'
             . '<span class="ft-meta">' . h(tf('liga_form_tooltip_matchday', ['nr' => $m['nr']])) . ' &middot; ' . h($venue) . '</span>'
             . '<span class="ft-match">' . $homeSide . $scoreHtml . $awaySide . '</span>'
             . ($date !== '' ? '<span class="ft-date">' . h($date) . '</span>' : '')
             . '</span></span>';
    }

    /**
     * Positionsveränderung zum vorherigen Spieltag je Team (Beitrag: Torsten
     * Hofmann). $partien sollte bereits auf den gewünschten Zeitpunkt
     * gefiltert sein (siehe computeLast5Form()) - "vorheriger Spieltag"
     * bezieht sich dann korrekt auf den vorletzten innerhalb dieser Auswahl,
     * nicht immer auf den allerletzten der ganzen Saison.
     */
    public static function computePositionTrend(array $teams, array $partien, array $opts, ?int $ligaId = null, string $mode = 'overall') : array
    {
        $byNr = [];
        foreach ($partien as $p) {
            $nr = (int)($p['_spieltag_nummer'] ?? 0);
            if ($p['h_tore'] === null || $p['g_tore'] === null) continue;
            $byNr[$nr][] = $p;
        }
        $sortedNrs = array_keys($byNr);
        sort($sortedNrs);

        if (count($sortedNrs) < 1) {
            $trendByTeam = [];
            foreach ($teams as $t) {
                $trendByTeam[(int)$t['id']] = ['direction' => 'same', 'delta' => 0];
            }
            return $trendByTeam;
        }

        $latestNr = end($sortedNrs);

        $played = [];
        $previousPartien = [];
        foreach ($sortedNrs as $nr) {
            foreach ($byNr[$nr] as $p) {
                $played[] = $p;
                if ($nr < $latestNr) {
                    $previousPartien[] = $p;
                }
            }
        }

        $currentStandings = self::computeStandings($teams, $played, $opts, $ligaId, $mode, $latestNr);

        $trendByTeam = [];
        if (count($sortedNrs) < 2) {
            foreach ($currentStandings as $r) {
                $trendByTeam[(int)$r['id']] = ['direction' => 'same', 'delta' => 0];
            }
            return $trendByTeam;
        }

        $previousNr = $sortedNrs[count($sortedNrs) - 2];
        $previousStandings = self::computeStandings($teams, $previousPartien, $opts, $ligaId, $mode, $previousNr);

        $prevPositions = [];
        foreach ($previousStandings as $i => $r) {
            $prevPositions[(int)$r['id']] = $i + 1;
        }
        foreach ($currentStandings as $i => $r) {
            $currentPos = $i + 1;
            $prevPos = $prevPositions[(int)$r['id']] ?? $currentPos;
            $delta = $prevPos - $currentPos;
            $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'same');
            $trendByTeam[(int)$r['id']] = ['direction' => $direction, 'delta' => $delta];
        }
        return $trendByTeam;
    }

    /**
     * Legt die Strafpunkte-Tabelle bei Bedarf an (analog zum ensureTippSchema()-
     * Muster im Tippspiel-Addon) - so funktioniert die Funktion auch auf
     * bereits bestehenden Installationen, die vor Einführung dieses Features
     * angelegt wurden, ohne dass install.php erneut laufen müsste.
     */
    private static function ensureStrafpunkteSchema() : void
    {
        static $done = false; if ($done) return; $done = true;
        try {
            $db = getDB();
            $db->exec('CREATE TABLE IF NOT EXISTS ' . tbl('liga_strafpunkte') . ' (
                `id`          INT AUTO_INCREMENT PRIMARY KEY,
                `liga_id`     INT NOT NULL,
                `team_id`     INT NOT NULL,
                `strafpunkte` INT NOT NULL DEFAULT 0,
                `straftore`   INT NOT NULL DEFAULT 0,
                `grund`       VARCHAR(255) NULL DEFAULT NULL,
                `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `liga_team` (`liga_id`, `team_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            // Migration: Korrektur der "erzielten Tore" (tore_h) - ursprünglich
            // gab es nur "straftore" (wirkt auf die Gegentore/tore_g). Ergänzt
            // um die Bielefeld-Lizenzentzug-Situation abzudecken (Punkte UND
            // beide Tor-Werte auf 0 korrigieren zu können), siehe Changelog.
            // BUGFIX: "PDO::FETCH_COLUMN" (ohne führenden Backslash) wurde
            // innerhalb dieses Namespace (LMOnext\Liga) fälschlich als
            // LMOnext\Liga\PDO aufgelöst statt als globale PDO-Klasse - dadurch
            // schlug diese komplette Migration bisher IMMER mit einer von
            // catch(\Throwable) verschluckten Exception fehl. Nie aufgefallen,
            // weil admin/handler_settings.php dieselben Spalten über einen
            // eigenen, im globalen Namespace laufenden Migrationscode ohnehin
            // schon anlegte - erst mit der neuen "ab Spieltag"-Spalte (die
            // NUR über diesen Codepfad angelegt wird) wurde der Fehler sichtbar.
            $cols = $db->query('SHOW COLUMNS FROM ' . tbl('liga_strafpunkte'))->fetchAll(\PDO::FETCH_COLUMN);
            if (!in_array('tore_korrektur', $cols, true)) {
                $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `tore_korrektur` INT NOT NULL DEFAULT 0 AFTER `straftore`');
            }
            if (!in_array('minuspunkte_korrektur', $cols, true)) {
                $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `minuspunkte_korrektur` INT NOT NULL DEFAULT 0 AFTER `tore_korrektur`');
            }
            // Migration: "ab Spieltag" (Beitrag: Torsten Hofmann) - eine
            // Strafe/ein Bonus kann jetzt erst ab einem bestimmten Spieltag
            // greifen (0 = ab Saisonbeginn, bisheriges Verhalten), z.B. bei
            // einem Lizenzentzug, der erst mitten in der Saison bekannt wird.
            if (!in_array('ab_spieltag', $cols, true)) {
                $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `ab_spieltag` INT NOT NULL DEFAULT 0 AFTER `minuspunkte_korrektur`');
            }
        } catch (\Throwable) {
            // Wird bei jedem Aufruf erneut versucht (static $done bleibt auf
            // dieser Instanz zwar true, aber ein neuer Request versucht es
            // erneut) - getLigaStrafpunkte()/setLigaStrafpunkte() fangen einen
            // fehlenden Tabellenzugriff ohnehin selbst ab.
        }
    }

    /**
     * Liefert alle für eine Liga hinterlegten Korrekturen (Strafpunkte/
     * Bonuspunkte, Straftore/Bonustore bei den Gegentoren, sowie eine
     * Korrektur der erzielten Tore), indiziert nach Team-ID. Alle drei Werte
     * sind vorzeichenbehaftet - positiv = Bonus, negativ = Strafe/Abzug. Teams
     * ohne Eintrag fehlen im Ergebnis (kein Datensatz angelegt für 0/0/0 -
     * siehe setLigaStrafpunkte()).
     *
     * @return array<int,array{strafpunkte:int,straftore:int,tore_korrektur:int,minuspunkte_korrektur:int,ab_spieltag:int,grund:string}>
     */
    public static function getLigaStrafpunkte(int $ligaId) : array
    {
        self::ensureStrafpunkteSchema();
        try {
            $stmt = getDB()->prepare(
                'SELECT team_id, strafpunkte, straftore, tore_korrektur, minuspunkte_korrektur, ab_spieltag, grund FROM ' . tbl('liga_strafpunkte') . ' WHERE liga_id = ?'
            );
            $stmt->execute([$ligaId]);
            $result = [];
            foreach ($stmt->fetchAll() as $r) {
                $result[(int)$r['team_id']] = [
                    'strafpunkte'    => (int)$r['strafpunkte'],
                    'straftore'      => (int)$r['straftore'],
                    'tore_korrektur' => (int)($r['tore_korrektur'] ?? 0),
                    'minuspunkte_korrektur' => (int)($r['minuspunkte_korrektur'] ?? 0),
                    'ab_spieltag'    => (int)($r['ab_spieltag'] ?? 0),
                    'grund'          => (string)($r['grund'] ?? ''),
                ];
            }
            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Speichert die Korrektur-Einträge (Punkte/erzielte Tore/Gegentore, alle
     * vorzeichenbehaftet) für eine Liga (ein Formular-Submit mit einer Zeile
     * je Team, siehe Admin → Liga-Einstellungen → Strafen). Teams mit 0 in
     * allen drei Werten und leerem Grund werden aus der Tabelle entfernt
     * statt als Leerzeile gespeichert - hält die Tabelle sauber und "kein
     * Eintrag" bedeutet eindeutig "keine Korrektur".
     *
     * @param array<int,array{strafpunkte?:int|string,straftore?:int|string,tore_korrektur?:int|string,minuspunkte_korrektur?:int|string,ab_spieltag?:int|string,grund?:string}> $eintraege team_id => Werte
     */
    public static function setLigaStrafpunkte(int $ligaId, array $eintraege) : bool
    {
        self::ensureStrafpunkteSchema();
        try {
            $db = getDB();
            $upsert = $db->prepare(
                'INSERT INTO ' . tbl('liga_strafpunkte') . ' (liga_id, team_id, strafpunkte, straftore, tore_korrektur, minuspunkte_korrektur, ab_spieltag, grund)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE strafpunkte = VALUES(strafpunkte), straftore = VALUES(straftore),
                                         tore_korrektur = VALUES(tore_korrektur),
                                         minuspunkte_korrektur = VALUES(minuspunkte_korrektur),
                                         ab_spieltag = VALUES(ab_spieltag), grund = VALUES(grund)'
            );
            $delete = $db->prepare('DELETE FROM ' . tbl('liga_strafpunkte') . ' WHERE liga_id = ? AND team_id = ?');

            foreach ($eintraege as $teamId => $e) {
                $teamId = (int)$teamId;
                $sp     = (int)($e['strafpunkte'] ?? 0);
                $st     = (int)($e['straftore'] ?? 0);
                $tk     = (int)($e['tore_korrektur'] ?? 0);
                $mk     = (int)($e['minuspunkte_korrektur'] ?? 0);
                $abSt   = max(0, (int)($e['ab_spieltag'] ?? 0));
                $grund  = trim((string)($e['grund'] ?? ''));

                if ($sp === 0 && $st === 0 && $tk === 0 && $mk === 0 && $grund === '') {
                    $delete->execute([$ligaId, $teamId]);
                    continue;
                }
                $upsert->execute([$ligaId, $teamId, $sp, $st, $tk, $mk, $abSt, $grund !== '' ? $grund : null]);
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
    /**
     * Kleiner Hinweis-Marker (⚠ mit Tooltip) für die Tabellenzeile eines
     * Teams mit Strafpunkten/Straftoren - leerer String, wenn keine Strafe
     * vorliegt. Erwartet eine Zeile aus computeStandings() (mit den Feldern
     * strafpunkte/straftore/strafgrund).
     */
    /**
     * Kleine hochgestellte Fußnoten-Referenz "(N)" für die Tabellenzeile
     * eines Teams mit hinterlegtem Grund, analog zur Wikipedia-Tabellen-
     * Fußnote (siehe z.B. "Arminia Bielefeld (1)"). $footnoteNr ist die
     * fortlaufende Nummer dieses Teams unter allen Teams MIT Grund in dieser
     * Tabelle (1-basiert, siehe assignStrafFootnotes()) - leerer String, wenn
     * kein Grund hinterlegt ist. Erscheint UNABHÄNGIG davon, ob überhaupt eine
     * der vier Zahlenkorrekturen von 0 abweicht - ein Grund allein reicht für
     * die Fußnote. Behält den detaillierten Tooltip (genaue
     * Punkte-/Tore-Deltas, falls vorhanden) zusätzlich zur sichtbaren
     * Fußnoten-Nummer bei.
     */
    public static function renderStrafHinweis(array $row, int $footnoteNr = 0) : string
    {
        $sp = (int)($row['strafpunkte'] ?? 0);
        $st = (int)($row['straftore'] ?? 0);
        $tk = (int)($row['torekorrektur'] ?? 0);
        $mk = (int)($row['minuspunktekorrektur'] ?? 0);
        $grund = trim((string)($row['strafgrund'] ?? ''));
        if ($sp === 0 && $st === 0 && $tk === 0 && $mk === 0 && $grund === '') {
            return '';
        }
        $teile = [];
        if ($sp !== 0) {
            $teile[] = ($sp > 0 ? '+' : '') . $sp . ' ' . tf('liga_standings_col_pkt');
        }
        if ($tk !== 0) {
            $teile[] = ($tk > 0 ? '+' : '') . $tk . ' ' . tf('liga_standings_straf_erzielt');
        }
        if ($st !== 0) {
            $teile[] = ($st > 0 ? '+' : '') . $st . ' ' . tf('liga_standings_straf_gegentore');
        }
        if ($mk !== 0) {
            $teile[] = ($mk > 0 ? '+' : '') . $mk . ' ' . tf('liga_standings_straf_minuspunkte');
        }
        $tooltip = implode(', ', $teile);
        if ($grund !== '') {
            $tooltip = $tooltip !== '' ? ($tooltip . ' (' . $grund . ')') : $grund;
        }
        // Grund vorhanden -> sichtbare Fußnoten-Nummer wie bei Wikipedia,
        // sonst (nur Zahlenkorrektur ohne Begründung) weiterhin nur das
        // Warnsymbol mit Tooltip, da keine Fußnote zum Verlinken existiert.
        if ($grund !== '' && $footnoteNr > 0) {
            return ' <sup class="st-straf-hinweis" title="' . h($tooltip) . '" id="strafnote-ref-' . $footnoteNr . '">'
                 . '<a href="#strafnote-' . $footnoteNr . '">(' . $footnoteNr . ')</a></sup>';
        }
        return ' <span class="st-straf-hinweis" title="' . h($tooltip) . '">⚠</span>';
    }

    /**
     * Weist allen Tabellenzeilen MIT hinterlegtem Grund fortlaufende
     * Fußnoten-Nummern zu (1-basiert, in Tabellenreihenfolge - so wie bei
     * Wikipedia). Ein Grund allein reicht - unabhängig davon, ob eine der vier
     * Zahlenkorrekturen (Punkte/erzielte Tore/Gegentore/Minuspunkte)
     * tatsächlich von 0 abweicht.
     *
     * @param array<int,array> $rows Ergebnis von computeStandings(), bereits sortiert
     * @return array<int,int> team_id => Fußnoten-Nummer (nur Teams mit Grund)
     */
    public static function assignStrafFootnotes(array $rows) : array
    {
        $nrn = [];
        $next = 1;
        foreach ($rows as $r) {
            $grund = trim((string)($r['strafgrund'] ?? ''));
            if ($grund === '') {
                continue;
            }
            $nrn[(int)$r['id']] = $next++;
        }
        return $nrn;
    }

    /**
     * Baut die Fußnoten-Liste unter der Tabelle, im Wikipedia-Stil
     * "(1) Begründungstext". Leerer String, wenn keine Fußnoten vorliegen.
     *
     * @param array<int,array> $rows Ergebnis von computeStandings()
     * @param array<int,int>   $footnoteNrs von assignStrafFootnotes()
     */
    public static function renderStrafFootnotes(array $rows, array $footnoteNrs) : string
    {
        if (empty($footnoteNrs)) {
            return '';
        }
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = $r;
        }
        $items = '';
        foreach ($footnoteNrs as $teamId => $nr) {
            $grund = trim((string)($byId[$teamId]['strafgrund'] ?? ''));
            $items .= '<p id="strafnote-' . $nr . '" class="st-footnote-item">'
                    . '<a href="#strafnote-ref-' . $nr . '">(' . $nr . ')</a> ' . h($grund) . '</p>';
        }
        return '<div class="st-footnotes">' . $items . '</div>';
    }
    /**
     * Ermittelt die Randfarbe (Tabellenmarkierung, siehe Admin → Liga-
     * Einstellungen → Tabelle) für eine Tabellenzeile anhand ihres Rangs
     * (0-basiert). Von oben nach unten: Meister (nur Rang 1, falls aktiviert,
     * zählt zum CL-Kontingent dazu) → Champions League → CL-Qualifikation →
     * Euroleague. Von unten nach oben: feststehende Absteiger → Relegation.
     * Gibt einen Hex-Farbwert zurück, oder '' wenn dieser Rang keine
     * Markierung hat.
     */
    public static function computeStandingsMarkerColor(int $index, int $totalTeams, array $opts) : string
    {
        $champEnabled = ($opts['Champ'] ?? '0') !== '0';
        $cl = (int)($opts['CL'] ?? 0);
        $ck = (int)($opts['CK'] ?? 0);
        $uc = (int)($opts['UC'] ?? 0);
        $ar = (int)($opts['AR'] ?? 0);
        $ab = (int)($opts['AB'] ?? 0);
    
        $champColor = ($opts['ChampColor'] ?? '') !== '' ? $opts['ChampColor'] : '#22c55e';
        $clColor    = ($opts['CLColor']  ?? '') !== '' ? $opts['CLColor']  : '#3b82f6';
        $ckColor    = ($opts['CKColor']  ?? '') !== '' ? $opts['CKColor']  : '#0ea5e9';
        $ucColor    = ($opts['UCColor']  ?? '') !== '' ? $opts['UCColor']  : '#f59e0b';
        $arColor    = ($opts['ARColor']  ?? '') !== '' ? $opts['ARColor']  : '#f97316';
        $abColor    = ($opts['ABColor']  ?? '') !== '' ? $opts['ABColor']  : '#ef4444';
    
        // BUGFIX ("Champions-League-Qualifikanten" auf 1 gesetzt,
        // aber Tabellenzweiter bekam keine Markierung, obwohl Meister und
        // Absteiger korrekt markiert wurden): die CL/CK/UC-Zählung ging
        // bisher IMMER ab Platz 1 (index=0) los, unabhängig davon, ob
        // $champEnabled Platz 1 schon separat als Meister markiert. Bei
        // CL=0 deckte "index < CL+CK" also den Bereich [0, CK) ab - das ist
        // exakt Platz 1, der aber durch den früheren "index===0"-Zweig
        // bereits per return abgefangen wird und diesen Zweig nie erreicht.
        // Platz 2 (der eigentlich gemeinte "erste freie Platz nach dem
        // Meister") wurde dadurch nie erreicht. $champOffset verschiebt die
        // CL/CK/UC-Zählung um genau 1 Platz, wenn $champEnabled aktiv ist -
        // die Meister-Markierung "verbraucht" dann Platz 1, und CL/CK/UC
        // zählen ab Platz 2 weiter.
        $champOffset = $champEnabled ? 1 : 0;

        if ($champEnabled && $index === 0) {
            return $champColor;
        }
        if ($index < $champOffset + $cl) {
            return $clColor;
        }
        if ($index < $champOffset + $cl + $ck) {
            return $ckColor;
        }
        if ($index < $champOffset + $cl + $ck + $uc) {
            return $ucColor;
        }
    
        $fromBottom = $totalTeams - 1 - $index; // 0 = letzter Platz
        if ($fromBottom < $ab) {
            return $abColor;
        }
        if ($fromBottom < $ab + $ar) {
            return $arColor;
        }
    
        return '';
    }
}
