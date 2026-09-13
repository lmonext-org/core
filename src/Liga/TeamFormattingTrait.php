<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/TeamFormattingTrait.php
 * Fileversion: 1.2.0
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
trait TeamFormattingTrait
{
    /**
     * Reihenfolge der Logo-Formate für die Browser-Ansicht: SVG zuerst (beste
     * Qualität/Schärfe, Browser rendern SVG zuverlässig), dann Rasterformate.
     */
    public const TEAM_LOGO_EXT_LIST_BROWSER = ['svg', 'png', 'jpg', 'jpeg', 'gif'];

    /**
     * Reihenfolge der Logo-Formate für den PDF-Export: Rasterformate zuerst,
     * SVG als Fallback zuletzt - SVG-Rasterung für PDFs ist je nach Server
     * (verfügbares ImageMagick/rsvg-convert) unterschiedlich zuverlässig,
     * während JPG/PNG über GD auf praktisch jedem Server garantiert
     * funktionieren. Mehrere SVG-Logos wurden im PDF
     * falsch/gar nicht dargestellt).
     */
    public const TEAM_LOGO_EXT_LIST_PDF = ['jpg', 'jpeg', 'png', 'gif', 'svg'];

    /**
     * Legacy-Alias für Rückwärtskompatibilität - entspricht TEAM_LOGO_EXT_LIST_PDF
     * (die zuverlässigere Reihenfolge), falls irgendwo noch direkt referenziert.
     * @deprecated nutze TEAM_LOGO_EXT_LIST_BROWSER bzw. TEAM_LOGO_EXT_LIST_PDF
     */
    public const TEAM_LOGO_EXT_LIST = self::TEAM_LOGO_EXT_LIST_PDF;
    /**
     * Ob eine Partie eine reine Platzhalter-Leerbegegnung ist – weder Heim noch
     * Gast haben ein echtes Team ODER auch nur einen Anzeige-Namen (heim_label/
     * gast_label). Kommt bei KO-Turnieren vor, deren Teilnehmerzahl im alten LMO
     * auf die nächste Zweierpotenz aufgefüllt werden musste (z.B. 83 echte Teams
     * → 128 Bracket-Plätze in Runde 1, die überzähligen Plätze wurden als reine
     * Dummy-Begegnungen ohne jede Zuordnung angelegt). Ein Platzhalter mit
     * Label wie "Sieger Spiel 3" gilt NICHT als leer – der ist ein bedeutungsvoller
     * "noch offen"-Platzhalter, kein reiner Datenmüll.
     */
    public static function partieIsEmptyPlaceholder(array $partie) : bool
    {
        // Wichtig: heim_id/gast_id zeigen bei diesen Plätzen NICHT auf "nichts"
        // (id=0/null), sondern auf einen ECHTEN Team-Datensatz namens "___" (das
        // alte LMO legt dafür extra ein Dummy-Team in teams_global an, siehe
        // getOrCreateDummyTeam() in admin/handler_import_export.php). Eine reine
        // "hat die Partie überhaupt eine id?"-Prüfung erkennt das daher nicht –
        // es muss der aufgelöste Anzeigename selbst geprüft werden.
        $isDummy = static fn(string $n) : bool => trim($n) === '' || trim($n) === '___';
        return $isDummy(self::partieTeamName($partie, 'heim')) && $isDummy(self::partieTeamName($partie, 'gast'));
    }
    public static function partieTeamName(array $partie, string $side) : string
    {
        $idKey    = $side . '_id';
        $nameKey  = $side . '_name';
        $labelKey = $side . '_label';
        if ((int)($partie[$idKey] ?? 0) > 0 && !empty($partie[$nameKey])) {
            return $partie[$nameKey];
        }
        return $partie[$labelKey] ?? '';
    }
    /**
     * Sucht ein hochgeladenes Team-Logo (siehe Admin → Teams (global)). Gibt den
     * Web-Pfad relativ zum Projekt-Root zurück, oder null wenn keins hinterlegt
     * ist. Eigenständige, schlanke Kopie der gleichnamigen Logik aus
     * admin/bootstrap.php – das Frontend bindet die Admin-Bootstrap-Kette nicht
     * ein, daher hier separat statt geteilt.
     *
     * $forBrowser steuert die Suchreihenfolge: true (Standard)
     * = Browser-Ausgabe, SVG bevorzugt (TEAM_LOGO_EXT_LIST_BROWSER). false =
     * PDF-Export, Rasterformate bevorzugt (TEAM_LOGO_EXT_LIST_PDF, siehe dort
     * für die Begründung).
     */
    public static function findTeamLogoPathFrontend(int $teamId, bool $forBrowser = true) : ?string
    {
        // WICHTIG: diese Datei liegt unter src/Liga/, also ZWEI Ebenen unter
        // dem Projekt-Root - dirname(__DIR__, 2) ist hier nötig, nicht
        // dirname(__DIR__) (das ginge nur eine Ebene hoch, landet fälschlich
        // in src/assets/... statt im echten assets/-Ordner im Projekt-Root).
        $dir = dirname(__DIR__, 2) . '/assets/img/teams';
        $extList = $forBrowser ? self::TEAM_LOGO_EXT_LIST_BROWSER : self::TEAM_LOGO_EXT_LIST_PDF;
        foreach ($extList as $ext) {
            if (is_file($dir . '/' . $teamId . '.' . $ext)) {
                return 'assets/img/teams/' . $teamId . '.' . $ext;
            }
        }
        return null;
    }
    /**
     * Baut das kleine Logo-<img> (oder Platzhalter, falls kein Logo hinterlegt
     * ist) vor einem Teamnamen – nur wenn die Liga-Einstellung "Logo anzeigen"
     * (ShowLogos) aktiv ist, sonst leerer String. $teamId <= 0 (z.B. Freilos/
     * Label-only-Partien ohne echtes Team) liefert ebenfalls nichts.
     */
    public static function renderTeamLogoImg(int $teamId, bool $showLogos) : string
    {
        if (!$showLogos || $teamId <= 0) {
            return '';
        }
        $path = self::findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
        return '<img src="' . h($path) . '" alt="" class="team-logo-inline">';
    }
    /**
     * Wie renderTeamLogoImg(), aber in einen <span> mit fester Breite verpackt
     * (.st-team-logo-wrap) – für Tabellen, in denen die Teamnamen untereinander
     * bündig ausgerichtet sein sollen (z.B. die Liga-Tabelle). Ohne diesen
     * Wrapper würden unterschiedlich breite Logos die Teamnamen jeweils
     * unterschiedlich weit einrücken. Gibt bei ausgeschaltetem ShowLogos
     * weiterhin einfach '' zurück (kein leerer Wrapper, kein verschwendeter
     * Platz in Tabellen ohne Logos).
     */
    public static function renderTeamLogoImgWrapped(int $teamId, bool $showLogos) : string
    {
        $img = self::renderTeamLogoImg($teamId, $showLogos);
        return $img !== '' ? '<span class="st-team-logo-wrap">' . $img . '</span>' : '';
    }
    /**
     * Wie partieTeamName(), aber als fertiges HTML-Snippet mit vorangestelltem
     * Logo (falls die Liga-Einstellung ShowLogos aktiv ist) – für alle
     * HTML-Ausgaben in der Besucheransicht. partieTeamName() selbst bleibt
     * unverändert (liefert reinen Text), da es auch für den PDF-Export
     * verwendet wird, wo kein HTML/Logo-Markup hinpasst.
     */
    public static function partieTeamNameWithLogo(array $partie, string $side, bool $showLogos) : string
    {
        $teamId = (int)($partie[$side . '_id'] ?? 0);
        return self::renderTeamLogoImg($teamId, $showLogos) . h(self::partieTeamName($partie, $side));
    }
    /**
     * Wie partieTeamNameWithLogo(), aber umgekehrte Reihenfolge (Name zuerst,
     * dann Logo) – nur für die Heim-Spalte bei Ergebnissen/Spielplänen
     * regulärer (nicht-KO-)Ligen verwendet. Der KO-Turnierbaum behält bewusst
     * die normale Logo-zuerst-Reihenfolge (nicht Teil dieser Anforderung).
     */
    public static function partieTeamNameWithLogoReversed(array $partie, string $side, bool $showLogos) : string
    {
        $teamId = (int)($partie[$side . '_id'] ?? 0);
        return h(self::partieTeamName($partie, $side)) . self::renderTeamLogoImg($teamId, $showLogos);
    }
    /**
     * Datum/Uhrzeit einer einzelnen Partie: eigene Zeit falls gesetzt, sonst der
     * Start des Spieltags als Fallback.
     */
    public static function partieZeitDisplay(array $partie, ?string $spieltagStart) : string
    {
        $raw = $partie['zeit'] ?? null;
        if (empty($raw)) {
            $raw = $spieltagStart;
        }
        if (empty($raw)) {
            return '–';
        }
        try {
            return (new \DateTime($raw))->format('d.m.Y H:i');
        } catch (\Throwable) {
            return '–';
        }
    }
    /**
     * Datumsspanne eines Spieltags (frühestes – spätestes Datum unter den Partien,
     * ohne Uhrzeit). Gibt es nur ein Datum, wird es einmal statt als Spanne gezeigt.
     */
    public static function spieltagDateRange(array $partien, ?string $spieltagStart) : string
    {
        $dates = [];
        foreach ($partien as $p) {
            $raw = $p['zeit'] ?? $spieltagStart;
            if (!empty($raw)) {
                try {
                    $dates[] = (new \DateTime($raw))->format('Y-m-d');
                } catch (\Throwable) {
                }
            }
        }
        if (empty($dates)) {
            return '';
        }
        sort($dates);
        $first = $dates[0];
        $last  = end($dates);
        $fmt   = static fn(string $d) : string => (\DateTime::createFromFormat('Y-m-d', $d))->format('d.m.Y');
        return $first === $last ? $fmt($first) : $fmt($first) . ' - ' . $fmt($last);
    }
    /**
     * Zusatz für Ergebnisse nach Verlängerung/Elfmeterschießen ("n.V." bzw. "i.E."),
     * passend zum LMO-Mapping: 1 = i.E. (Elfmeterschießen), 2 = n.V. (Verlängerung).
     * Zusätzlich (auf Wunsch, unabhängig vom obigen status-Wert, siehe
     * ensureSpielstatusColumns()): "nicht gewertet" bei rückwirkend
     * annullierten Spielen (z.B. Lizenzentzug/Spielbetrieb-Einstellung eines
     * Vereins) - das Ergebnis bleibt sichtbar, zählt aber für keines der
     * beiden Teams in der Tabelle (siehe StandingsTrait::computeStandings()).
     * Sowie "Wertung" bei einer Grüne-Tisch-Entscheidung (Sportgericht, siehe
     * StandingsTrait::gtCreditedScore()) - bewusst VOR dem h_tore/g_tore-
     * null-Check geprüft, da eine solche Entscheidung auch ganz ohne real
     * eingetragenes Ergebnis greift (z.B. Nichtantritt).
     * Bestehendes Format (führendes Leerzeichen, kein Klammer-Wrapping) für
     * den bisherigen Einzelfall (nur i.E./n.V., nicht annulliert) bewusst
     * unverändert gelassen, um keine bestehende Anzeige zu verändern.
     * Leerer String bei normalem, gewertetem Spielausgang oder fehlendem
     * Ergebnis ohne jede Sonderwertung.
     */
    public static function statusSuffix(array $partie) : string
    {
        $gtEntscheidung = (int)($partie['gt_entscheidung'] ?? 0);
        if ($partie['h_tore'] === null || $partie['g_tore'] === null) {
            return $gtEntscheidung > 0 ? ' ' . tf('liga_status_gt') : '';
        }
        $suffix = match ((int)($partie['status'] ?? 0)) {
            1 => ' ' . tf('liga_status_ie'),
            2 => ' ' . tf('liga_status_nv'),
            default => '',
        };
        if ((int)($partie['nicht_gewertet'] ?? 0) === 1) {
            $suffix .= ' ' . tf('liga_status_ng');
        }
        if ($gtEntscheidung > 0) {
            $suffix .= ' ' . tf('liga_status_gt');
        }
        return $suffix;
    }
}
