<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/TeamFormattingTrait.php
 * Fileversion: 1.12.1
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
     * → 128 Turnier-Plätze in Runde 1, die überzähligen Plätze wurden als reine
     * Dummy-Begegnungen ohne jede Zuordnung angelegt). Ein Platzhalter mit
     * Label wie "Sieger Spiel 3" gilt NICHT als leer – der ist ein bedeutungsvoller
     * "noch offen"-Platzhalter, kein reiner Datenmüll.
     */
    /**
     * Prüft eine Seite ("heim"/"gast") auf "leer" - KEIN echtes Team, KEIN
     * informativer Freitext-Platzhalter. Arbeitet bewusst auf dem ROHEN
     * Label (nicht über partieTeamName(), das den Freilos-Marker für die
     * ANZEIGE bereits in den sichtbaren Text "Freilos" übersetzt) - sonst
     * würde eine Freilos-Seite nach der Übersetzung fälschlich als "nicht
     * leer" durchgehen, nur weil jetzt lesbarer Text dort steht.
     *
     * Als "leer" gilt: kein Team-Datensatz UND (Label ist leer ODER "___"
     * - alte LMO-Dummy-Teams, siehe getOrCreateDummyTeam() in
     * admin/handler_import_export.php - ODER der Freilos-Marker).
     */
    private static function partieSideIsEmptyRaw(array $partie, string $side) : bool
    {
        $idKey    = $side . '_id';
        $nameKey  = $side . '_name';
        $labelKey = $side . '_label';
        if ((int)($partie[$idKey] ?? 0) > 0 && !empty($partie[$nameKey])) {
            return false; // echtes Team
        }
        $label = trim((string)($partie[$labelKey] ?? ''));
        return $label === '' || $label === '___' || (defined('KO_FREILOS_MARKER') && $label === KO_FREILOS_MARKER);
    }

    public static function partieIsEmptyPlaceholder(array $partie) : bool
    {
        return self::partieSideIsEmptyRaw($partie, 'heim') && self::partieSideIsEmptyRaw($partie, 'gast');
    }

    /**
     * Freilos-Begegnung (Beitrag: Nutzeranfrage) - ein Team ohne echten
     * Gegner, z.B. wenn ein KO-Turnier größer gewählt wurde als die
     * tatsächliche Teilnehmerzahl (12 Teams in einem 16er-Turnier, die
     * vier übrigen Plätze bleiben leer). Anders als
     * partieIsEmptyPlaceholder() (verlangt BEIDE Seiten leer, z.B. für
     * eine komplett unbenutzte Turnier-Position) reicht hier bereits EINE
     * leere Seite - ein Team ganz ohne Gegnernamen oder -platzhalter ist
     * auf der Ergebnisliste nicht sinnvoll darstellbar. Eine Seite mit
     * einem Freitext-Platzhalter wie "Sieger Achtelfinale 1" gilt NICHT
     * als leer (dort steht ja etwas Informatives) - nur eine wirklich
     * leere Seite (kein Team, kein Label) löst das Ausblenden aus.
     */
    public static function partieHasEmptySide(array $partie) : bool
    {
        return self::partieSideIsEmptyRaw($partie, 'heim') || self::partieSideIsEmptyRaw($partie, 'gast');
    }
    public static function partieTeamName(array $partie, string $side) : string
    {
        $idKey    = $side . '_id';
        $nameKey  = $side . '_name';
        $labelKey = $side . '_label';
        if ((int)($partie[$idKey] ?? 0) > 0 && !empty($partie[$nameKey])) {
            return $partie[$nameKey];
        }
        $label = $partie[$labelKey] ?? '';
        // Beitrag: Nutzeranfrage - der Freilos-Marker (KO_FREILOS_MARKER,
        // siehe config_loader.php) ist ein interner technischer Wert und
        // darf NIE roh angezeigt werden - zentral hier übersetzt, damit
        // JEDE Ausgabestelle (Ergebnisse, Spielplan, Kreuztabelle, ...)
        // automatisch den lokalisierten Text "Freilos" zeigt, ohne dass
        // jede einzelne Stelle das selbst prüfen müsste.
        if (defined('KO_FREILOS_MARKER') && $label === KO_FREILOS_MARKER) {
            return function_exists('tf') ? tf('liga_freilos_label') : 'Freilos';
        }
        return $label;
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
     *
     * $title - z.B. der volle Teamname, wenn das Logo OHNE begleitenden Text
     * steht (z.B. die Kreuztabellen-Kopfzeile) und die Mannschaft sonst nur
     * durch das Bild erkennbar wäre. Füllt sowohl title (Tooltip beim Hovern)
     * als auch alt (Bildbeschreibung, z.B. für Screenreader) - leer (Standard)
     * bedeutet unverändertes bisheriges Verhalten (alt="").
     */
    public static function renderTeamLogoImg(int $teamId, bool $showLogos, string $title = '') : string
    {
        if (!$showLogos || $teamId <= 0) {
            return '';
        }
        $path = self::findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
        $titleAttr = $title !== '' ? ' title="' . h($title) . '"' : '';
        return '<img src="' . h($path) . '" alt="' . h($title) . '"' . $titleAttr . ' class="team-logo-inline">';
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
    public static function renderTeamLogoImgWrapped(int $teamId, bool $showLogos, string $title = '') : string
    {
        $img = self::renderTeamLogoImg($teamId, $showLogos, $title);
        return $img !== '' ? '<span class="st-team-logo-wrap">' . $img . '</span>' : '';
    }
    /**
     * Sollen Mannschaftslogos angezeigt werden?
     *
     * Bisher pro Liga/Pokal einzeln einzustellen (Liga-Option "ShowLogos"),
     * jetzt EINMAL global unter Einstellungen > Optionen > Anzeigen/Darstellung
     * (Admin-Einstellung "show_team_logos"). Rückwärtskompatibel: solange die
     * globale Einstellung noch nie gespeichert wurde, gilt weiterhin der alte
     * Wert der jeweiligen Liga ($opts['ShowLogos']) - dadurch ändert sich beim
     * Update nichts, bis der Admin die globale Einstellung zum ersten Mal setzt.
     */
    public static function showTeamLogos(array $opts) : bool
    {
        $global = function_exists('getAdminSetting') ? \getAdminSetting('show_team_logos', '') : '';
        if ($global !== '') {
            return $global === '1';
        }
        return ($opts['ShowLogos'] ?? '0') === '1';
    }

    /**
     * Nur http(s)-URLs werden als Link ausgegeben - verhindert, dass ein
     * gespeichertes "javascript:..."-Pseudo-Protokoll im Homepage-Feld
     * beim Klick im Frontend für jeden Besucher beliebigen JavaScript-Code
     * ausführen könnte (gleiche Absicherung wie an anderen Stellen im
     * Projekt, wo Nutzer-URLs ausgegeben werden).
     */
    private static function safeHomepageUrl(?string $url) : string
    {
        $url = trim((string)$url);
        return ($url !== '' && preg_match('#^https?://#i', $url)) ? $url : '';
    }

    /**
     * Link zum Spielbericht: bisher wurde jede URL ohne "http(s)://" pauschal
     * verworfen, auch ein relativer Pfad wie "berichte/spiel12.html" oder
     * "/berichte/spiel12.html" auf der eigenen Website. Erlaubt jetzt:
     *   - absolute http(s)-URLs (wie bisher),
     *   - relative URLs ohne Schema ("berichte/x.html", "/berichte/x.html",
     *     "../x.pdf", "//host/x", "?x=1", "#x").
     * Abgelehnt (leerer String) wird weiterhin jedes ANDERE Schema
     * (javascript:, data:, vbscript:, file:, mailto: ...) - das Hauptziel der
     * alten Prüfung war ja, dass ein gespeichertes "javascript:..." beim Klick
     * im Frontend keinen Code ausführen kann.
     *
     * Steuerzeichen (auch Tab/Zeilenumbruch) führen ebenfalls zur Ablehnung:
     * Browser entfernen sie beim Parsen einer URL stillschweigend, ein
     * "java<TAB>script:..." sähe sonst für diese Prüfung wie ein harmloser
     * relativer Pfad aus und würde im Browser trotzdem zu "javascript:...".
     */
    public static function safeReportUrl(?string $url) : string
    {
        $url = trim((string)$url);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return '';
        }
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $m)) {
            return in_array(strtolower($m[1]), ['http', 'https'], true) ? $url : '';
        }
        return $url; // kein Schema => relativ
    }

    /**
     * Wie partieTeamName(), aber als fertiges HTML-Snippet mit vorangestelltem
     * Logo (falls die Liga-Einstellung ShowLogos aktiv ist) – für alle
     * HTML-Ausgaben in der Besucheransicht. partieTeamName() selbst bleibt
     * unverändert (liefert reinen Text), da es auch für den PDF-Export
     * verwendet wird, wo kein HTML/Logo-Markup hinpasst.
     *
     * $linkHomepage (Bugfix: die Liga-Einstellung "Mannschafts-Homepages
     * verlinken"/urlH wurde bisher an keiner Stelle im Frontend
     * ausgewertet) - verlinkt den Teamnamen zur in teams_global.url
     * hinterlegten Homepage, wenn aktiv UND eine gültige http(s)-URL
     * hinterlegt ist.
     */
    /**
     * Logo-HTML für eine Begegnungsseite: bei aktivierten Logos UND einem
     * Freilos (Beitrag: Nutzeranfrage) wird assets/img/freilos.svg gezeigt
     * statt des normalen Teamwappens (das es ja mangels Team gar nicht
     * geben kann) - sonst das gewohnte renderTeamLogoImg().
     */
    private static function resolveSideLogoHtml(array $partie, string $side, int $teamId, bool $showLogos) : string
    {
        if (!$showLogos) {
            return '';
        }
        $labelKey = $side . '_label';
        $label = trim((string)($partie[$labelKey] ?? ''));
        if ($teamId <= 0 && defined('KO_FREILOS_MARKER') && $label === KO_FREILOS_MARKER) {
            $freilosLabel = function_exists('tf') ? tf('liga_freilos_label') : 'Freilos';
            return '<img src="assets/img/freilos.svg" alt="' . h($freilosLabel) . '" title="' . h($freilosLabel) . '" class="team-logo-inline">';
        }
        return self::renderTeamLogoImg($teamId, $showLogos);
    }

    public static function partieTeamNameWithLogo(array $partie, string $side, bool $showLogos, bool $linkHomepage = false, string $linkTarget = '_blank') : string
    {
        $teamId = (int)($partie[$side . '_id'] ?? 0);
        $name = h(self::partieTeamName($partie, $side));
        $url = $linkHomepage ? self::safeHomepageUrl($partie[$side . '_url'] ?? null) : '';
        if ($url !== '') {
            $name = '<a href="' . h($url) . '"' . self::linkTargetAttr($linkTarget) . '>' . $name . '</a>';
        }
        return self::resolveSideLogoHtml($partie, $side, $teamId, $showLogos) . $name;
    }
    /**
     * Wie partieTeamNameWithLogo(), aber umgekehrte Reihenfolge (Name zuerst,
     * dann Logo) – nur für die Heim-Spalte bei Ergebnissen/Spielplänen
     * regulärer (nicht-KO-)Ligen verwendet. Der KO-Turnierbaum behält bewusst
     * die normale Logo-zuerst-Reihenfolge (nicht Teil dieser Anforderung).
     */
    public static function partieTeamNameWithLogoReversed(array $partie, string $side, bool $showLogos, bool $linkHomepage = false, string $linkTarget = '_blank') : string
    {
        $teamId = (int)($partie[$side . '_id'] ?? 0);
        $name = h(self::partieTeamName($partie, $side));
        $url = $linkHomepage ? self::safeHomepageUrl($partie[$side . '_url'] ?? null) : '';
        if ($url !== '') {
            $name = '<a href="' . h($url) . '"' . self::linkTargetAttr($linkTarget) . '>' . $name . '</a>';
        }
        return $name . self::resolveSideLogoHtml($partie, $side, $teamId, $showLogos);
    }
    /**
     * Baut das target-/rel-Attribut-Fragment für einen Link, je nach
     * gewünschtem Linkziel ('_blank'/'_self', siehe oben). Zentral an
     * einer Stelle, damit beide Verlinkungs-Funktionen (Team-Homepage,
     * Spielbericht - siehe RenderViewsTrait::renderPartieRow()) dasselbe
     * Verhalten teilen.
     */
    public static function linkTargetAttr(string $linkTarget) : string
    {
        if ($linkTarget === '_self' || $linkTarget === '_top') {
            return $linkTarget === '_top' ? ' target="_top"' : '';
        }
        return ' target="_blank" rel="noopener"';
    }
    /**
     * Datum/Uhrzeit einer einzelnen Partie: eigene Zeit falls gesetzt, sonst der
     * Start des Spieltags als Fallback.
     */
    /**
     * Formatiert Datum/Uhrzeit einer Partie für die Anzeige (Ergebnisse,
     * Spielplan). $dateFormat ist ein PHP-date()-Formatstring - normalerweise
     * die Liga-Einstellung "Format der Anstoßtermine" (DatF), z.B. "d.m.Y"
     * (ohne Uhrzeit) oder "d.m.Y H:i" (mit Uhrzeit, der Standardwert).
     *
     * BUGFIX (gemeldet: "d.m.Y ohne Uhrzeit in den Einstellungen
     * eingestellt, Ausgabe trotzdem mit Uhrzeit"): diese Funktion hatte das
     * Format bisher fest auf "d.m.Y H:i" verdrahtet und die Liga-Einstellung
     * DatF nie gelesen - unabhängig davon, was der Admin dort konfiguriert
     * hatte. $dateFormat hat denselben Standardwert wie die Einstellung
     * selbst (siehe admin/view_liga_settings.php: $o('DatF', 'd.m.Y H:i')),
     * damit ein Aufruf ohne explizites Format sich unverändert wie bisher
     * verhält.
     */
    public static function partieZeitDisplay(array $partie, ?string $spieltagStart, string $dateFormat = 'd.m.Y H:i') : string
    {
        $raw = $partie['zeit'] ?? null;
        if (empty($raw)) {
            $raw = $spieltagStart;
        }
        if (empty($raw)) {
            return '–';
        }
        try {
            $dt = new \DateTime($raw);
            return self::localizeDateOutput($dt, $dt->format($dateFormat), $dateFormat);
        } catch (\Throwable) {
            return '–';
        }
    }

    /**
     * Ersetzt englische Wochentags-/Monatsnamen im bereits formatierten
     * Datums-String durch die Übersetzung in der aktuellen Seitensprache
     * PHP date()/DateTime::format() geben "l" (voller Wochentag),
     * "D" (kurzer Wochentag), "F" (voller Monat) und "M" (kurzer Monat)
     * IMMER auf Englisch aus, unabhängig von der gewählten Seitensprache.
     * date() ist nicht locale-abhängig, anders als das veraltete strftime()).
     *
     * Ersetzt gezielt den für DIESES Datum berechneten englischen Wert
     * (z.B. "Friday") durch die Übersetzung, statt den Format-String selbst
     * zu interpretieren - robust gegenüber beliebigen Kombinationen von
     * Formatzeichen und Literaltext. Bei Englisch als Seitensprache bleibt
     * die PHP-Ausgabe unverändert (keine Übersetzung nötig). Fehlt eine
     * Übersetzung (z.B. weitere Sprache ohne diese Schlüssel), bleibt die
     * jeweilige englische Ausgabe als Rückfall unangetastet.
     */
    private static function localizeDateOutput(\DateTime $dt, string $formatted, string $dateFormat) : string
    {
        if (!function_exists('getCurrentLanguage') || !function_exists('tf')) {
            return $formatted;
        }
        $lang = getCurrentLanguage('frontend');
        if ($lang === 'en') {
            return $formatted;
        }
        $weekdayNum = (int)$dt->format('N'); // 1=Montag..7=Sonntag
        $monthNum   = (int)$dt->format('n'); // 1..12
        $weekdayShortKeys = [1 => 'mo', 2 => 'di', 3 => 'mi', 4 => 'do', 5 => 'fr', 6 => 'sa', 7 => 'so'];

        $replace = static function (string $formatted, string $englishValue, string $translationKey) : string {
            if ($englishValue === '') { return $formatted; }
            $translated = tf($translationKey);
            return ($translated !== '' && $translated !== $translationKey)
                ? str_replace($englishValue, $translated, $formatted)
                : $formatted;
        };

        if (str_contains($dateFormat, 'l')) {
            $formatted = $replace($formatted, $dt->format('l'), 'liga_weekday_full_' . $weekdayNum);
        }
        if (str_contains($dateFormat, 'D')) {
            $formatted = $replace($formatted, $dt->format('D'), 'liga_weekday_' . $weekdayShortKeys[$weekdayNum]);
        }
        if (str_contains($dateFormat, 'F')) {
            $formatted = $replace($formatted, $dt->format('F'), 'liga_month_' . $monthNum);
        }
        if (str_contains($dateFormat, 'M')) {
            $formatted = $replace($formatted, $dt->format('M'), 'liga_month_short_' . $monthNum);
        }
        return $formatted;
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
     * Zusätzlich (unabhängig vom obigen status-Wert, siehe
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
    /**
     * Wandelt eine Zahl in eine hochgestellte, geklammerte Fußnoten-Markierung
     * um (z.B. 1 -> "⁽¹⁾"), als Ersatz für ein einzelnes "(*)" -
     * bei mehreren Grüne-Tisch-Entscheidungen an einem Spieltag lässt sich ein
     * Sternchen nicht den einzelnen Fußnoten zuordnen, eine Zahl schon.
     * Nutzt reine Unicode-Zeichen (kein HTML-Tag wie <sup>) statt echter
     * hochgestellter Formatierung, damit die Markierung unabhängig davon
     * korrekt erscheint, ob der Aufrufer das Ergebnis von statusSuffix()
     * selbst noch durch h() schickt oder nicht (uneinheitlich zwischen den
     * beiden Aufrufpfaden, siehe formatScore() vs. FootballProfile::
     * formatResult()) - ein HTML-Tag würde im einen Pfad escaped, im
     * anderen nicht.
     */
    public static function gtFootnoteMarker(int $n) : string
    {
        static $superDigits = ['0'=>'⁰','1'=>'¹','2'=>'²','3'=>'³','4'=>'⁴','5'=>'⁵','6'=>'⁶','7'=>'⁷','8'=>'⁸','9'=>'⁹'];
        $super = '';
        foreach (str_split((string)$n) as $ch) {
            $super .= $superDigits[$ch] ?? $ch;
        }
        return '⁽' . $super . '⁾';
    }

    public static function statusSuffix(array $partie) : string
    {
        $gtEntscheidung = (int)($partie['gt_entscheidung'] ?? 0);
        // "_gt_footnote_nr" wird vom Aufrufer (liga.php, siehe renderGtFootnotes())
        // je Spieltag durchnummeriert in $partie geschrieben, bevor diese
        // Funktion aufgerufen wird - fehlt diese Nummer (z.B. in Kontexten
        // ohne begleitende Fußnotenliste wie PDF-Export/Kreuztabelle/
        // Ligastatistik/Team-Spielplan), fällt die Anzeige auf das einfache
        // "(*)" zurück.
        $gtMarker = $gtEntscheidung > 0
            ? (isset($partie['_gt_footnote_nr']) ? self::gtFootnoteMarker((int)$partie['_gt_footnote_nr']) : tf('liga_status_gt'))
            : '';
        if ($partie['h_tore'] === null || $partie['g_tore'] === null) {
            return $gtEntscheidung > 0 ? ' ' . $gtMarker : '';
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
            $suffix .= ' ' . $gtMarker;
        }
        return $suffix;
    }
}
