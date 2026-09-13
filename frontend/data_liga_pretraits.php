<?php
/**
 * Project: LMOnext
 * Filename: data_liga.php
 * Fileversion: 2.23.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Alle Abfragen und die dazugehörige Aufbereitung für die Liga-Detailseite an
 * einem Ort. Templates selbst enthalten kein PHP – sie bekommen fertige
 * HTML-Fragmente als Platzhalterwerte (siehe frontend/template_engine.php).
 */
declare(strict_types = 1);

/**
 * Liga-Grunddaten (Name, Datum, …) oder null, falls nicht gefunden.
 */
function getLigaById(int $id) : ?array
{
    try {
        $s = getDB()->prepare('SELECT * FROM ' . tbl('liga') . ' WHERE id=?');
        $s->execute([$id]);
        $row = $s->fetch();
        return $row !== false ? $row : null;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Liga-Typ: 0 = Liga, 1 = KO-Turnier.
 */
function getLigaType(int $ligaId) : int
{
    try {
        $s = getDB()->prepare('SELECT option_value FROM ' . tbl('liga_options') . ' WHERE liga_id=? AND option_key="Type"');
        $s->execute([$ligaId]);
        $v = $s->fetchColumn();
        return $v !== false ? (int)$v : 0;
    } catch (Throwable) {
        return 0;
    }
}

/**
 * Anzahl der Teams, die dieser Liga zugeordnet sind (für die Info-Ansicht).
 */
function getLigaTeamCount(int $ligaId) : int
{
    try {
        $s = getDB()->prepare('SELECT COUNT(*) FROM ' . tbl('liga_teams') . ' WHERE liga_id=?');
        $s->execute([$ligaId]);
        return (int)$s->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

/**
 * Alle liga_options einer Liga als Schlüssel→Wert-Array.
 */
/**
 * Liest die Liga-Optionen (siehe liga_options-Tabelle). Wird pro Seitenaufruf
 * oft für dieselbe Liga mehrfach aufgerufen (Tabelle, Ergebnisse, PDF-Export
 * usw. greifen alle unabhängig voneinander darauf zu) - daher Speicher-Cache
 * pro Liga-ID, damit dieselbe Liga innerhalb eines Requests nur einmal
 * abgefragt wird.
 */
function getLigaOptions(int $ligaId) : array
{
    static $cache = [];
    if (array_key_exists($ligaId, $cache)) {
        return $cache[$ligaId];
    }
    try {
        $s = getDB()->prepare('SELECT option_key, option_value FROM ' . tbl('liga_options') . ' WHERE liga_id=?');
        $s->execute([$ligaId]);
        $out = [];
        foreach ($s->fetchAll() as $row) {
            $out[$row['option_key']] = $row['option_value'];
        }
        return $cache[$ligaId] = $out;
    } catch (Throwable) {
        return $cache[$ligaId] = [];
    }
}

/**
 * Löst die in den Liga-Einstellungen für favTeam/selTeam gespeicherte
 * Team-"Nummer" (1-basierte Position in der alphabetisch nach Name sortierten
 * Team-Liste, so wie sie auch im Adminbereich für diese Dropdowns aufgebaut
 * wird) in die tatsächliche team_id auf. 0 bzw. eine nicht mehr vorhandene
 * Position ergeben null.
 */
/**
 * "Team-Nummer" (Position in der nach Name sortierten Teamliste dieser Liga)
 * zu einer Team-ID auflösen - z.B. für favTeam/selTeam. Cacht die sortierte
 * ID-Liste pro Liga, da diese Funktion innerhalb eines Requests oft mehrfach
 * für dieselbe Liga aufgerufen wird (favTeam, selTeam usw.).
 */
function resolveTeamNumberToId(int $ligaId, int $number) : ?int
{
    if ($number <= 0) {
        return null;
    }
    static $cache = [];
    if (!array_key_exists($ligaId, $cache)) {
        try {
            $s = getDB()->prepare(
                'SELECT g.id
                   FROM ' . tbl('teams_global') . ' g
                   JOIN ' . tbl('liga_teams') . ' lt ON lt.team_id = g.id
                  WHERE lt.liga_id = ?
                  ORDER BY g.name'
            );
            $s->execute([$ligaId]);
            $cache[$ligaId] = $s->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable) {
            $cache[$ligaId] = [];
        }
    }
    $ids = $cache[$ligaId];
    return isset($ids[$number - 1]) ? (int)$ids[$number - 1] : null;
}

/**
 * Prüft ein Ein/Aus-Flag aus liga_options. Fehlt der Schlüssel ganz (z.B. bei
 * einer neu angelegten Liga, für die noch nie die Einstellungen gespeichert
 * wurden), gilt $default – für Kalender/Ergebnisse/Spielpläne ist das "an",
 * damit neue Ligen nicht versehentlich unsichtbar für Besucher sind.
 */
function ligaFlagEnabled(array $opts, string $key, bool $default = true) : bool
{
    return ($opts[$key] ?? ($default ? '1' : '0')) === '1';
}

/**
 * Welche Besucher-Reiter für diese Liga sichtbar sein sollen, basierend auf
 * den Liga-Einstellungen. "Info" ist immer an.
 */
function getLigaViewFlags(array $opts) : array
{
    return [
        'kalender'      => ligaFlagEnabled($opts, 'Kalender', true),
        'ergebnisse'    => ligaFlagEnabled($opts, 'Ergebnis', true),
        'tabelle'       => ligaFlagEnabled($opts, 'Tabelle', true),
        'spielplaene'   => ligaFlagEnabled($opts, 'Plan', true),
        'kreuztabelle'  => ligaFlagEnabled($opts, 'Kreuz', true),
        'fieberkurve'   => ligaFlagEnabled($opts, 'kurve1', true),
        'ligastatistik' => ligaFlagEnabled($opts, 'Ligastats', true),
        'spielerstatistik' => ligaFlagEnabled($opts, 'stats', false),
        'info'          => true,
    ];
}

/**
 * Alle Spieltage/Runden einer Liga mit Basis-Statistik (gespielt/partie_count/
 * pairing_count), aufsteigend nach Nummer. Grundlage für Auswahl/Navigation
 * und die KO-Rundennamen.
 */
function getAllSpieltage(int $ligaId) : array
{
    try {
        $s = getDB()->prepare(
            'SELECT s.id, s.nummer, s.start,
                    SUM(CASE WHEN p.h_tore IS NOT NULL THEN 1 ELSE 0 END) AS gespielt,
                    COUNT(p.id) AS partie_count,
                    COUNT(DISTINCT SUBSTRING_INDEX(p.spiel_nr, "_", 1)) AS pairing_count
               FROM ' . tbl('liga_spieltage') . ' s
               LEFT JOIN ' . tbl('liga_partien') . ' p ON p.spieltag_id = s.id
              WHERE s.liga_id = ?
              GROUP BY s.id, s.nummer, s.start
              ORDER BY s.nummer'
        );
        $s->execute([$ligaId]);
        return $s->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/** Höchste Spieltag-/Rundennummer einer Liga (für die "ist das die letzte Runde?"-Prüfung). */
function getMaxSpieltagNummer(array $allSpieltage) : int
{
    $max = 0;
    foreach ($allSpieltage as $st) {
        $max = max($max, (int)$st['nummer']);
    }
    return $max;
}

/**
 * Ermittelt den letzten Spieltag/die letzte Runde mit mindestens einem
 * eingetragenen Ergebnis. Gibt es noch keine Ergebnisse, wird stattdessen
 * der erste Spieltag zurückgegeben. Liefert null, wenn die Liga überhaupt
 * keine Spieltage hat.
 */
function getLatestSpieltagWithResults(array $allSpieltage) : ?array
{
    if (empty($allSpieltage)) {
        return null;
    }
    $latest = null;
    foreach ($allSpieltage as $row) {
        if ((int)$row['gespielt'] > 0) {
            $latest = $row;
        }
    }
    return $latest ?? $allSpieltage[0];
}

/**
 * Sucht einen bestimmten Spieltag/eine Runde per Nummer innerhalb einer
 * bereits geladenen Spieltag-Liste (siehe getAllSpieltage()).
 */
function getSpieltagByNummer(array $allSpieltage, int $nummer) : ?array
{
    foreach ($allSpieltage as $row) {
        if ((int)$row['nummer'] === $nummer) {
            return $row;
        }
    }
    return null;
}

/**
 * Partien (Spiele) eines Spieltags, mit aufgelösten Teamnamen (Platzhalter-
 * Teams im KO-Modus nutzen heim_label/gast_label statt einer echten
 * Team-Zuordnung).
 */
function getSpieltagPartien(int $spieltagId) : array
{
    // "status" ist eine on-demand-Spalte (wird erst per ensureSpielstatusColumns()
    // im Adminbereich angelegt) – hier defensiv prüfen, damit die Abfrage auch auf
    // einer ganz frischen Installation nicht scheitert.
    static $hasStatusColumn = null;
    if ($hasStatusColumn === null) {
        try {
            getDB()->query('SELECT status FROM ' . tbl('liga_partien') . ' LIMIT 0');
            $hasStatusColumn = true;
        } catch (Throwable) {
            $hasStatusColumn = false;
        }
    }
    $statusSelect = $hasStatusColumn ? ', p.status' : '';

    try {
        $s = getDB()->prepare(
            'SELECT p.id, p.heim_id, p.gast_id, p.heim_label, p.gast_label,
                    p.h_tore, p.g_tore, p.zeit, p.spiel_nr' . $statusSelect . ',
                    th.name AS heim_name, tg.name AS gast_name
               FROM ' . tbl('liga_partien') . ' p
               LEFT JOIN ' . tbl('teams_global') . ' th ON th.id = p.heim_id
               LEFT JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.gast_id
              WHERE p.spieltag_id = ?
              ORDER BY CAST(SUBSTRING_INDEX(p.spiel_nr, "_", 1) AS UNSIGNED),
                       CAST(SUBSTRING_INDEX(p.spiel_nr, "_", -1) AS UNSIGNED)'
        );
        $s->execute([$spieltagId]);
        $rows = $s->fetchAll();
        if (!$hasStatusColumn) {
            foreach ($rows as &$row) {
                $row['status'] = 0;
            }
            unset($row);
        }
        return $rows;
    } catch (Throwable) {
        return [];
    }
}

/**
 * Teamvergleich (H2H) - als eigenständiges Addon "teamvergleich" ausgegliedert
 * (Beitrag: Nutzerwunsch, siehe Machbarkeitsstudie im Chat-Verlauf sowie
 * CHANGELOG.md). Die frühere vollständige Eigenimplementierung dieser Datei
 * (resolveLinkedTeamIds, resolveCanonicalTeamId, getHeadToHeadMatches,
 * buildHeadToHeadPayload, renderH2hIcon, renderH2hModalAssets - eine reine
 * Code-Duplikation der gleichnamigen Funktionen in frontend/data_liga.php)
 * wurde ENTFERNT. Beide Dateien delegieren jetzt identisch über denselben
 * Hook an das (optionale) teamvergleich-Addon - siehe frontend/data_liga.php
 * für die vollständigen, kommentierten Wrapper-Implementierungen. Ist das
 * Addon nicht aktiv, liefern diese Funktionen einfach leere Werte (kein
 * Direkter Vergleich sichtbar), kein Fehler.
 */
function getHeadToHeadMatches(int $idA, int $idB) : array
{
    $result = doHook('liga.h2h_matches', ['idA' => $idA, 'idB' => $idB, 'matches' => []]);
    return is_array($result['matches'] ?? null) ? $result['matches'] : [];
}

function buildHeadToHeadPayload(int $idA, int $idB, string $nameA, string $nameB, bool $showLogos = false) : string
{
    $result = doHook('liga.h2h_payload', [
        'idA' => $idA, 'idB' => $idB, 'nameA' => $nameA, 'nameB' => $nameB,
        'showLogos' => $showLogos, 'json' => '{}',
    ]);
    return (string)($result['json'] ?? '{}');
}

function renderH2hIcon(int $heimId, int $gastId, string $heimName, string $gastName, bool $showLogos = false) : string
{
    $result = doHook('liga.compare_icon', [
        'heim_id' => $heimId, 'gast_id' => $gastId, 'heim_name' => $heimName,
        'gast_name' => $gastName, 'show_logos' => $showLogos, 'html' => '',
    ]);
    return (string)($result['html'] ?? '');
}

function renderH2hModalAssets() : string
{
    $result = doHook('liga.compare_modal_assets', ['html' => '']);
    return (string)($result['html'] ?? '');
}

/**
 * Zusatz für Ergebnisse nach Verlängerung/Elfmeterschießen ("n.V." bzw. "i.E."),
 * passend zum LMO-Mapping: 1 = i.E. (Elfmeterschießen), 2 = n.V. (Verlängerung).
 * Leerer String bei normalem Spielausgang (Status 0) oder fehlendem Ergebnis.
 */
function statusSuffix(array $partie) : string
{
    if ($partie['h_tore'] === null || $partie['g_tore'] === null) {
        return '';
    }
    return match ((int)($partie['status'] ?? 0)) {
        1 => ' ' . tf('liga_status_ie'),
        2 => ' ' . tf('liga_status_nv'),
        default => '',
    };
}

/**
 * Ermittelt für einen Spieltag alle Teams der Liga, die an diesem Spieltag
 * KEINE Partie haben ("Spielfrei"). Kommt typischerweise bei ungerader
 * Teamzahl vor, kann aber auch bei gerader Teamzahl auftreten (z.B. wenn ein
 * Team im Spielplan schlicht nicht eingeteilt wurde). Ermittlung durch
 * Abwesenheit, genau wie im alten LMO: es gibt keinen expliziten
 * "Spielfrei"-Eintrag im Datenmodell, das betroffene Team taucht einfach in
 * keiner Paarung des Spieltags auf.
 *
 * @return array<int,array> Liste der betroffenen Teams (id,name,kurz,mittel)
 */
function findSpielfreiTeams(int $ligaId, array $partien) : array
{
    $scheduledIds = [];
    foreach ($partien as $p) {
        if (partieIsEmptyPlaceholder($p)) {
            continue; // "kein Spielplan"-Platzhalterpaarung zählt nicht als Termin
        }
        if ((int)($p['heim_id'] ?? 0) > 0) {
            $scheduledIds[(int)$p['heim_id']] = true;
        }
        if ((int)($p['gast_id'] ?? 0) > 0) {
            $scheduledIds[(int)$p['gast_id']] = true;
        }
    }

    $spielfrei = [];
    foreach (getLigaTeamsList($ligaId) as $team) {
        $tid  = (int)$team['id'];
        $name = trim((string)($team['name'] ?? ''));
        if (!isset($scheduledIds[$tid]) && $name !== '' && $name !== '___') {
            $spielfrei[] = $team;
        }
    }
    return $spielfrei;
}

/**
 * Rendert die "Spielfrei: TEAMNAME"-Zeile unterhalb der Ergebnistabelle
 * eines Spieltags (siehe findSpielfreiTeams()). Liefert einen leeren String,
 * wenn kein Team spielfrei ist.
 */
function renderSpielfreiNote(int $ligaId, array $partien) : string
{
    $teams = findSpielfreiTeams($ligaId, $partien);
    if ($teams === []) {
        return '';
    }
    $names = implode(', ', array_map(static fn(array $t) : string => '<strong>' . h($t['name']) . '</strong>', $teams));
    return renderPartial('spielfrei_note', [
        'Label' => h(tf('liga_spielfrei_label')),
        'Teams' => $names,
    ]);
}

/**
 * Anzeigename eines Teams für eine Partie-Zeile (echtes Team oder Platzhalter-Label).
 */
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
function partieIsEmptyPlaceholder(array $partie) : bool
{
    // Wichtig: heim_id/gast_id zeigen bei diesen Plätzen NICHT auf "nichts"
    // (id=0/null), sondern auf einen ECHTEN Team-Datensatz namens "___" (das
    // alte LMO legt dafür extra ein Dummy-Team in teams_global an, siehe
    // getOrCreateDummyTeam() in admin/handler_import_export.php). Eine reine
    // "hat die Partie überhaupt eine id?"-Prüfung erkennt das daher nicht –
    // es muss der aufgelöste Anzeigename selbst geprüft werden.
    $isDummy = static fn(string $n) : bool => trim($n) === '' || trim($n) === '___';
    return $isDummy(partieTeamName($partie, 'heim')) && $isDummy(partieTeamName($partie, 'gast'));
}

function partieTeamName(array $partie, string $side) : string
{
    $idKey    = $side . '_id';
    $nameKey  = $side . '_name';
    $labelKey = $side . '_label';
    if ((int)($partie[$idKey] ?? 0) > 0 && !empty($partie[$nameKey])) {
        return $partie[$nameKey];
    }
    return $partie[$labelKey] ?? '';
}

const TEAM_LOGO_EXT_LIST = ['svg', 'jpg', 'jpeg', 'png', 'gif'];

/**
 * Sucht ein hochgeladenes Team-Logo (siehe Admin → Teams (global)). Gibt den
 * Web-Pfad relativ zum Projekt-Root zurück, oder null wenn keins hinterlegt
 * ist. Eigenständige, schlanke Kopie der gleichnamigen Logik aus
 * admin/bootstrap.php – das Frontend bindet die Admin-Bootstrap-Kette nicht
 * ein, daher hier separat statt geteilt.
 */
function findTeamLogoPathFrontend(int $teamId) : ?string
{
    $dir = dirname(__DIR__) . '/assets/img/teams';
    foreach (TEAM_LOGO_EXT_LIST as $ext) {
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
function renderTeamLogoImg(int $teamId, bool $showLogos) : string
{
    if (!$showLogos || $teamId <= 0) {
        return '';
    }
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
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
function renderTeamLogoImgWrapped(int $teamId, bool $showLogos) : string
{
    $img = renderTeamLogoImg($teamId, $showLogos);
    return $img !== '' ? '<span class="st-team-logo-wrap">' . $img . '</span>' : '';
}

/**
 * Wie partieTeamName(), aber als fertiges HTML-Snippet mit vorangestelltem
 * Logo (falls die Liga-Einstellung ShowLogos aktiv ist) – für alle
 * HTML-Ausgaben in der Besucheransicht. partieTeamName() selbst bleibt
 * unverändert (liefert reinen Text), da es auch für den PDF-Export
 * verwendet wird, wo kein HTML/Logo-Markup hinpasst.
 */
function partieTeamNameWithLogo(array $partie, string $side, bool $showLogos) : string
{
    $teamId = (int)($partie[$side . '_id'] ?? 0);
    return renderTeamLogoImg($teamId, $showLogos) . h(partieTeamName($partie, $side));
}

/**
 * Wie partieTeamNameWithLogo(), aber umgekehrte Reihenfolge (Name zuerst,
 * dann Logo) – nur für die Heim-Spalte bei Ergebnissen/Spielplänen
 * regulärer (nicht-KO-)Ligen verwendet. Der KO-Turnierbaum behält bewusst
 * die normale Logo-zuerst-Reihenfolge (nicht Teil dieser Anforderung).
 */
function partieTeamNameWithLogoReversed(array $partie, string $side, bool $showLogos) : string
{
    $teamId = (int)($partie[$side . '_id'] ?? 0);
    return h(partieTeamName($partie, $side)) . renderTeamLogoImg($teamId, $showLogos);
}

/**
 * Datum/Uhrzeit einer einzelnen Partie: eigene Zeit falls gesetzt, sonst der
 * Start des Spieltags als Fallback.
 */
function partieZeitDisplay(array $partie, ?string $spieltagStart) : string
{
    $raw = $partie['zeit'] ?? null;
    if (empty($raw)) {
        $raw = $spieltagStart;
    }
    if (empty($raw)) {
        return '–';
    }
    try {
        return (new DateTime($raw))->format('d.m.Y H:i');
    } catch (Throwable) {
        return '–';
    }
}

/**
 * Datumsspanne eines Spieltags (frühestes – spätestes Datum unter den Partien,
 * ohne Uhrzeit). Gibt es nur ein Datum, wird es einmal statt als Spanne gezeigt.
 */
function spieltagDateRange(array $partien, ?string $spieltagStart) : string
{
    $dates = [];
    foreach ($partien as $p) {
        $raw = $p['zeit'] ?? $spieltagStart;
        if (!empty($raw)) {
            try {
                $dates[] = (new DateTime($raw))->format('Y-m-d');
            } catch (Throwable) {
            }
        }
    }
    if (empty($dates)) {
        return '';
    }
    sort($dates);
    $first = $dates[0];
    $last  = end($dates);
    $fmt   = static fn(string $d) : string => (DateTime::createFromFormat('Y-m-d', $d))->format('d.m.Y');
    return $first === $last ? $fmt($first) : $fmt($first) . ' - ' . $fmt($last);
}

/**
 * Statistik für einen Spieltag: Schnitt Heim-/Gast-Tore, Gesamttore, Tore/Spiel
 * – nur aus tatsächlich gespielten Partien berechnet.
 */
function computeSpieltagStats(array $partien) : array
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
 * Rundenname für eine KO-Runde. Benannte Stufen gibt es erst ab den letzten
 * 16 Mannschaften: 16 Teams → Achtelfinale, 8 → Viertelfinale, 4 → Halbfinale,
 * letzte Runde → immer "Finale". Bei mehr als 16 Teams heißt es schlicht
 * "Runde {nummer}" nach der tatsächlichen, fortlaufenden Rundennummer.
 */
function koRoundName(int $teamCount, bool $isFinalRound, int $roundNummer) : string
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
function roundDisplayName(array $st, bool $isKO, int $maxNr) : string
{
    if (!$isKO) {
        return tf('liga_label_matchday', ['n' => $st['nummer']]);
    }
    $isFinal   = (int)$st['nummer'] === $maxNr;
    $teamCount = (int)($st['pairing_count'] ?? 0) * 2;
    return koRoundName($teamCount, $isFinal, (int)$st['nummer']);
}

/**
 * Gruppiert Partien einer Runde nach Paarung (dem Teil von spiel_nr vor dem
 * "_", z.B. "1" bei "1_1"/"1_2"), sortiert nach Paarungsnummer. Für die
 * letzte KO-Runde ist Gruppe 1 das Finale, Gruppe 2 (falls vorhanden) das
 * Spiel um Platz 3.
 *
 * @return array<int, array> Liste von Partien-Gruppen.
 */
function groupPartienByPairing(array $partien) : array
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
 * Rendert eine einzelne Ergebniszeile über das Partial "partie_row"
 * (template/<aktiv>/partials/partie_row.tpl.php). $spieltagStart dient als
 * Datums-Fallback, falls die einzelne Partie keine eigene Zeit hat.
 */
function renderPartieRow(array $partie, ?string $spieltagStart = null, ?int $favTeamId = null, bool $showLogos = false, bool $reverseHeim = false) : string
{
    $heimRaw  = partieTeamName($partie, 'heim');
    $gastRaw  = partieTeamName($partie, 'gast');
    $heim     = $reverseHeim
        ? partieTeamNameWithLogoReversed($partie, 'heim', $showLogos)
        : partieTeamNameWithLogo($partie, 'heim', $showLogos);
    $gast     = partieTeamNameWithLogo($partie, 'gast', $showLogos);
    $gespielt = $partie['h_tore'] !== null && $partie['g_tore'] !== null;
    $score    = $gespielt ? h((string)$partie['h_tore']) . ' : ' . h((string)$partie['g_tore']) . h(statusSuffix($partie)) : '- : -';
    $datum    = h(partieZeitDisplay($partie, $spieltagStart));
    $hId      = (int)($partie['heim_id'] ?? 0);
    $gId      = (int)($partie['gast_id'] ?? 0);

    return renderPartial('partie_row', [
        'Datum'              => $datum,
        'Heim'                => $heim,
        'Gast'                => $gast,
        'Ergebnis'            => $score,
        'ErgebnisOffenClass'  => $gespielt ? '' : ' ergebnis-offen',
        'HeimClass'           => ($favTeamId !== null && $hId === $favTeamId) ? ' schedule-own' : '',
        'GastClass'           => ($favTeamId !== null && $gId === $favTeamId) ? ' schedule-own' : '',
        'CompareIcon'         => renderH2hIcon($hId, $gId, $heimRaw, $gastRaw, $showLogos),
    ]);
}

/**
 * Baut die komplette Ergebnistabelle (Kopf + Zeilen) für eine Liste von
 * Partien über das Partial "results_table". Ist $favTeamId gesetzt, wird die
 * entsprechende Mannschaft in jeder Zeile fett hervorgehoben (Lieblingsmannschaft).
 * Jede Zeile bekommt zusätzlich ein Vergleichs-Icon (direkter Vergleich der
 * beiden Teams, siehe renderH2hIcon()/renderH2hModalAssets()).
 */
function renderResultsTable(array $partien, ?string $spieltagStart, ?int $favTeamId = null, bool $showLogos = false, bool $reverseHeim = false) : string
{
    $rows = '';
    foreach ($partien as $partie) {
        $rows .= renderPartieRow($partie, $spieltagStart, $favTeamId, $showLogos, $reverseHeim);
    }
    return renderPartial('results_table', [
        'ColDatum'    => h(tf('liga_col_datum')),
        'ColHeim'     => h(tf('liga_col_heim')),
        'ColGast'     => h(tf('liga_col_gast')),
        'ColErgebnis' => h(tf('liga_col_ergebnis')),
        'Zeilen'      => $rows,
    ]) . renderH2hModalAssets();
}


/**
 * Baut den Statistik-Block (Überschrift + Zeile) für eine Liste von Partien
 * über das Partial "stats_block".
 */
function renderStatsBlock(string $heading, array $partien) : string
{
    $stats = computeSpieltagStats($partien);
    return renderPartial('stats_block', [
        'StatsHeading' => h(tf('liga_stats_heading', ['label' => $heading])),
        'StatsLine'    => h(tf('liga_stats_line', [
            'heim'     => $stats['schnittHeim'],
            'gast'     => $stats['schnittGast'],
            'tore'     => $stats['tore'],
            'proSpiel' => $stats['toreProSpiel'],
        ])),
    ]);
}

/**
 * Baut das komplette Spieltag/Runden-Auswahl-Dropdown über die Partials
 * "spieltag_option" (je Eintrag) und "spieltag_picker" (Rahmen). Liefert
 * einen leeren String, wenn nur eine Runde/ein Spieltag existiert.
 */
function renderSpieltagPicker(array $allSpieltage, int $ligaId, ?int $currentNr, bool $isKO, int $maxNr) : string
{
    if (count($allSpieltage) <= 1) {
        return '';
    }
    $optionsHtml = '';
    foreach ($allSpieltage as $st) {
        $optionsHtml .= renderPartial('spieltag_option', [
            'Nummer'       => (int)$st['nummer'],
            'SelectedAttr' => (int)$st['nummer'] === $currentNr ? ' selected' : '',
            'Label'        => h(roundDisplayName($st, $isKO, $maxNr)),
        ]);
    }
    return renderPartial('spieltag_picker', [
        'PickerLabel' => h($isKO ? tf('liga_label_pick_round') : tf('liga_label_pick_matchday')),
        'LigaId'      => $ligaId,
        'Optionen'    => $optionsHtml,
    ]);
}

/**
 * Baut die Reiter-Navigation (Kalender/Ergebnisse/Spielpläne/Info) über die
 * Partials "tab_item" (je Reiter) und "tabs_bar" (Rahmen). Reiter, die laut
 * $flags nicht aktiviert sind, werden weggelassen.
 *
 * @param array  $flags      Rückgabe von getLigaViewFlags()
 * @param int    $ligaId
 * @param string $currentView Schlüssel des aktuell aktiven Reiters
 */
function renderTabsBar(array $flags, int $ligaId, string $currentView) : string
{
    $labels = [
        'kalender'      => tf('liga_tab_kalender'),
        'ergebnisse'    => tf('liga_tab_ergebnisse'),
        'tabelle'       => tf('liga_tab_tabelle'),
        'spielplaene'   => tf('liga_tab_spielplaene'),
        'kreuztabelle'  => tf('liga_tab_kreuztabelle'),
        'fieberkurve'   => tf('liga_tab_fieberkurve'),
        'ligastatistik' => tf('liga_tab_ligastatistik'),
        'spielerstatistik' => tf('liga_tab_spielerstatistik'),
        'info'          => tf('liga_tab_info'),
    ];
    $tabsHtml = '';
    foreach ($labels as $key => $label) {
        if (empty($flags[$key])) {
            continue;
        }
        $tabsHtml .= renderPartial('tab_item', [
            'ActiveClass' => $key === $currentView ? ' tab-item-active' : '',
            'LigaId'      => $ligaId,
            'ViewKey'     => $key,
            'Label'       => h($label),
        ]);
    }
    return renderPartial('tabs_bar', ['Tabs' => $tabsHtml]);
}

/**
 * Baut die Info-Ansicht: eine allgemeine "Über LMOnext"-Seite (Version,
 * Copyright, Kurzbeschreibung, Lizenz) – analog zur Info-Seite des alten
 * LMO, die ebenfalls keine ligaspezifischen Daten zeigt, sondern
 * Informationen über die Software selbst.
 */
function renderInfoView() : string
{
    $version = getAppVersion();
    return renderPartial('info_view', [
        'Title'     => h(tf('liga_info_title', ['version' => $version])),
        'LinkHomepage' => tf('liga_info_link_homepage'),
        'LinkForum'    => tf('liga_info_link_forum'),
        'Text1'     => h(tf('liga_info_text_1')),
        'Text2'     => h(tf('liga_info_text_2')),
        'License'   => h(tf('liga_info_license')),
        'Copyright' => h(tf('liga_info_copyright')),
    ]);
}

/**
 * Übersetzter Monatsname (1-12).
 */
function monthName(int $month) : string
{
    return tf('liga_month_' . max(1, min(12, $month)));
}

/**
 * Baut die Kalender-Ansicht für einen Monat: Wochentagskopf + Wochen mit
 * Tageszellen, jede Zelle zeigt die an diesem Tag stattfindenden Spieltage/
 * Runden als kleine anklickbare Badges (Sprung zur jeweiligen Ergebnisliste).
 * Berücksichtigt nur Spieltage mit gesetztem Startdatum.
 */
function renderKalenderView(array $allSpieltage, int $ligaId, bool $isKO, int $maxNr, int $year, int $month) : string
{
    // Einträge je Tag sammeln (nur Spieltage mit gesetztem Startdatum)
    $entriesByDay = [];
    foreach ($allSpieltage as $st) {
        if (empty($st['start'])) {
            continue;
        }
        try {
            $dt = new DateTime($st['start']);
        } catch (Throwable) {
            continue;
        }
        if ((int)$dt->format('Y') !== $year || (int)$dt->format('n') !== $month) {
            continue;
        }
        $entriesByDay[(int)$dt->format('j')][] = $st;
    }

    $firstOfMonth = DateTime::createFromFormat('Y-n-j', $year . '-' . $month . '-1');
    $daysInMonth  = (int)$firstOfMonth->format('t');
    $startWeekday = (int)$firstOfMonth->format('N'); // 1 (Montag) .. 7 (Sonntag)
    $today        = new DateTime('today');

    $weekdayLabels = ['liga_weekday_mo', 'liga_weekday_di', 'liga_weekday_mi', 'liga_weekday_do',
                       'liga_weekday_fr', 'liga_weekday_sa', 'liga_weekday_so'];
    $headerHtml = '';
    foreach ($weekdayLabels as $key) {
        $headerHtml .= '<th>' . h(tf($key)) . '</th>';
    }

    $weeksHtml = '';
    $dayNum    = 1 - ($startWeekday - 1);
    while ($dayNum <= $daysInMonth) {
        $daysHtml = '';
        for ($w = 0; $w < 7; $w++, $dayNum++) {
            if ($dayNum < 1 || $dayNum > $daysInMonth) {
                $daysHtml .= renderPartial('kalender_day', ['DayClass' => ' cal-empty', 'DayNum' => '', 'Entries' => '']);
                continue;
            }
            $cellDate  = DateTime::createFromFormat('Y-n-j', $year . '-' . $month . '-' . $dayNum);
            $isToday   = $cellDate->format('Y-m-d') === $today->format('Y-m-d');
            $entries   = $entriesByDay[$dayNum] ?? [];
            $entriesHtml = '';
            foreach ($entries as $st) {
                $entriesHtml .= renderPartial('kalender_entry', [
                    'LigaId' => $ligaId,
                    'Nummer' => (int)$st['nummer'],
                    'Label'  => h(roundDisplayName($st, $isKO, $maxNr)),
                ]);
            }
            $daysHtml .= renderPartial('kalender_day', [
                'DayClass' => $isToday ? ' cal-today' : '',
                'DayNum'   => (string)$dayNum,
                'Entries'  => $entriesHtml,
            ]);
        }
        $weeksHtml .= renderPartial('kalender_week', ['Tage' => $daysHtml]);
    }

    $prevMonth = $month === 1 ? 12 : $month - 1;
    $prevYear  = $month === 1 ? $year - 1 : $year;
    $nextMonth = $month === 12 ? 1 : $month + 1;
    $nextYear  = $month === 12 ? $year + 1 : $year;

    return renderPartial('kalender_view', [
        'LigaId'    => $ligaId,
        'MonthYear' => h(monthName($month)) . ' ' . $year,
        'PrevYear'  => $prevYear,
        'PrevMonth' => $prevMonth,
        'NextYear'  => $nextYear,
        'NextMonth' => $nextMonth,
        'TodayLabel'=> h(tf('liga_kalender_today')),
        'TodayYear' => (int)$today->format('Y'),
        'TodayMonth'=> (int)$today->format('n'),
        'Weekdays'  => $headerHtml,
        'Wochen'    => $weeksHtml,
    ]);
}

/**
 * Ermittelt die Team-ID des Dummy-Platzhalter-Teams ("___"), falls vorhanden.
 * Wird beim Umsortieren des Turnierbaums ausgeschlossen, damit nicht mehrere
 * unabhängige Platzhalter-Paarungen fälschlich als "dieselbe" Zuführung
 * erkannt werden (der Dummy-Team-Datensatz wird für alle Platzhalter geteilt).
 */
function getDummyTeamId() : int
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    try {
        $s = getDB()->prepare('SELECT id FROM ' . tbl('teams_global') . ' WHERE name=? LIMIT 1');
        $s->execute(['___']);
        $v = $s->fetchColumn();
        return $id = ($v !== false ? (int)$v : 0);
    } catch (Throwable) {
        return $id = 0;
    }
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
function reorderBracketPairings(array $rounds) : array
{
    $n = count($rounds);
    if ($n <= 1) {
        return $rounds;
    }

    $ordered = [];
    $ordered[$n - 1] = $rounds[$n - 1];
    $dummyId = getDummyTeamId();

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

/**
 * Baut die Spielpläne-Ansicht als klassischen Turnierbaum (nur KO-Ligen):
 * eine Spalte je Runde, mit den Paarungen dieser Runde (aggregiertes
 * Ergebnis über alle Spiele einer Paarung, z.B. bei Hin+Rück). Die
 * Paarungen werden per reorderBracketPairings() so sortiert, dass sie sich
 * optisch korrekt zwischen den Runden ausrichten.
 */
function renderBracketView(int $ligaId, array $allSpieltage, bool $isKO, int $maxNr) : string
{
    $opts        = getLigaOptions($ligaId);
    $showKickoff = ligaFlagEnabled($opts, 'DatM', false);
    $dateFormat  = $opts['DatF'] ?? 'd.m.Y H:i';
    $showLogos   = ($opts['ShowLogos'] ?? '0') === '1';

    // Erst alle Runden mit ihren Paarungsgruppen + repräsentativen Team-IDs sammeln
    $rounds = [];
    foreach ($allSpieltage as $st) {
        $partien = getSpieltagPartien((int)$st['id']);
        $groups  = groupPartienByPairing($partien);

        $pairings = [];
        foreach ($groups as $group) {
            $pairings[] = [
                'heim_id'       => (int)($group[0]['heim_id'] ?? 0),
                'gast_id'       => (int)($group[0]['gast_id'] ?? 0),
                'group'         => $group,
                'spieltagStart' => $st['start'] ?? null,
            ];
        }
        $rounds[] = ['roundName' => h(roundDisplayName($st, $isKO, $maxNr)), 'pairings' => $pairings];
    }

    // Nur die Paarungslisten fürs Umsortieren extrahieren, roundName separat behalten
    $pairingLists = array_map(static fn($r) => $r['pairings'], $rounds);
    $orderedLists = reorderBracketPairings($pairingLists);

    $roundsHtml = '';
    foreach ($rounds as $i => $round) {
        $pairingsHtml = '';
        foreach ($orderedLists[$i] as $pairing) {
            $group = $pairing['group'] ?? null;
            if (empty($group)) {
                continue;
            }
            // Reine Leer-Paarungen (beide Seiten Dummy-Team "___" bzw. ganz
            // ohne Zuordnung, siehe partieIsEmptyPlaceholder()) werden auch
            // im Turnierbaum nicht angezeigt – das Layout ist eine reine
            // Box-Liste pro Runde ohne feste Positionen/Verbindungslinien,
            // ein Weglassen verschiebt also nichts anderes.
            if (partieIsEmptyPlaceholder($group[0])) {
                continue;
            }
            $heimRaw = partieTeamName($group[0], 'heim');
            $gastRaw = partieTeamName($group[0], 'gast');
            $heim    = partieTeamNameWithLogo($group[0], 'heim', $showLogos);
            $gast    = partieTeamNameWithLogo($group[0], 'gast', $showLogos);

            $hTotal     = 0;
            $gTotal     = 0;
            $allPlayed  = !empty($group);
            $statusVal  = 0;
            foreach ($group as $p) {
                if ($p['h_tore'] === null || $p['g_tore'] === null) {
                    $allPlayed = false;
                } else {
                    $hTotal += (int)$p['h_tore'];
                    $gTotal += (int)$p['g_tore'];
                }
                if ((int)($p['status'] ?? 0) !== 0) {
                    $statusVal = (int)$p['status'];
                }
            }
            $suffix = $allPlayed ? statusSuffix(['h_tore' => $hTotal, 'g_tore' => $gTotal, 'status' => $statusVal]) : '';
            $score  = $allPlayed ? h((string)$hTotal) . ' : ' . h((string)$gTotal) . h($suffix) : '- : -';

            $kickoff = '';
            if ($showKickoff) {
                $raw = $group[0]['zeit'] ?? $pairing['spieltagStart'] ?? null;
                if (!empty($raw)) {
                    try {
                        $kickoff = (new DateTime($raw))->format($dateFormat);
                    } catch (Throwable) {
                        $kickoff = '';
                    }
                }
            }

            $pairingsHtml .= renderPartial('bracket_pairing', [
                'Heim'        => $heim,
                'Gast'        => $gast,
                'Score'       => $score,
                'CompareIcon' => renderH2hIcon($pairing['heim_id'], $pairing['gast_id'], $heimRaw, $gastRaw, $showLogos),
                'Kickoff'     => h($kickoff),
            ]);
        }

        $roundsHtml .= renderPartial('bracket_round', [
            'RoundName' => $round['roundName'],
            'Pairings'  => $pairingsHtml,
        ]);
    }

    return renderPartial('bracket_view', ['Rounds' => $roundsHtml]) . renderH2hModalAssets();
}

/**
 * Alle Teams, die dieser Liga zugeordnet sind (für die Tabelle – auch Teams
 * ohne bisher gespielte Partie sollen mit 0 erscheinen).
 */
/**
 * Wird pro Seitenaufruf oft mehrfach für dieselbe Liga aufgerufen (Tabelle,
 * Kreuztabelle, Spielplan-Sidebar, PDF-Export, Mini-Addons usw.) - Speicher-
 * Cache pro Liga-ID, damit die (teils zweistufige, siehe Fallback unten)
 * Abfrage innerhalb eines Requests nur einmal läuft.
 */
function getLigaTeamsList(int $ligaId) : array
{
    static $cache = [];
    if (array_key_exists($ligaId, $cache)) {
        return $cache[$ligaId];
    }
    return $cache[$ligaId] = getLigaTeamsListUncached($ligaId);
}

function getLigaTeamsListUncached(int $ligaId) : array
{
    try {
        $s = getDB()->prepare(
            'SELECT tg.id, tg.name, tg.kurz, tg.mittel
               FROM ' . tbl('liga_teams') . ' lt
               JOIN ' . tbl('teams_global') . ' tg ON tg.id = lt.team_id
              WHERE lt.liga_id = ?
              ORDER BY lt.id'
        );
        $s->execute([$ligaId]);
        $rows = $s->fetchAll();
        if (!empty($rows)) {
            return $rows;
        }
    } catch (Throwable) {
        // fällt durch zum Fallback unten
    }

    // Fallback: liga_teams ist (noch) leer – z.B. bei älteren importierten
    // Ligen, wo diese Zuordnungstabelle nie befüllt wurde. Teams stattdessen
    // direkt aus den vorhandenen Partien ableiten (Dummy-Team "___" ausschließen).
    try {
        $dummyId = getDummyTeamId();
        $s = getDB()->prepare(
            'SELECT DISTINCT tg.id, tg.name, tg.kurz, tg.mittel
               FROM ' . tbl('liga_partien') . ' p
               JOIN ' . tbl('liga_spieltage') . ' st ON st.id = p.spieltag_id
               JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.heim_id
              WHERE st.liga_id = ? AND tg.id <> ?
              UNION
             SELECT DISTINCT tg.id, tg.name, tg.kurz, tg.mittel
               FROM ' . tbl('liga_partien') . ' p
               JOIN ' . tbl('liga_spieltage') . ' st ON st.id = p.spieltag_id
               JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.gast_id
              WHERE st.liga_id = ? AND tg.id <> ?
              ORDER BY name'
        );
        $s->execute([$ligaId, $dummyId, $ligaId, $dummyId]);
        return $s->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Alle Partien einer Liga über alle Spieltage hinweg (für Tabelle, Spielpläne,
 * Kreuztabelle, Fieberkurven, Ligastatistik).
 */
function getAllLigaPartien(array $allSpieltage) : array
{
    $all = [];
    foreach ($allSpieltage as $st) {
        foreach (getSpieltagPartien((int)$st['id']) as $p) {
            $p['_spieltag_nummer'] = (int)$st['nummer'];
            $all[] = $p;
        }
    }
    return $all;
}

/**
 * Berechnet die Tabelle: Sp/S/U/N/Tore/Diff/Pkt je Team, sortiert nach
 * Punkte → Tordifferenz → Tore (wie im Adminbereich). Startet mit allen
 * gemeldeten Teams (auch ohne gespielte Partie, dann mit lauter Nullen).
 */
function computeStandings(array $teamsList, array $partien, array $ligaOptions) : array
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

    $rows = [];
    foreach ($teamsList as $t) {
        $rows[(int)$t['id']] = [
            'id' => (int)$t['id'], 'name' => $t['name'], 'kurz' => $t['kurz'] ?? '',
            'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0,
            'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0,
        ];
    }

    foreach ($partien as $p) {
        if ($p['h_tore'] === null || $p['g_tore'] === null) {
            continue;
        }
        $hId = (int)($p['heim_id'] ?? 0);
        $gId = (int)($p['gast_id'] ?? 0);
        if ($hId <= 0 || $gId <= 0) {
            continue;
        }
        if (!isset($rows[$hId])) {
            $rows[$hId] = ['id' => $hId, 'name' => $p['heim_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0];
        }
        if (!isset($rows[$gId])) {
            $rows[$gId] = ['id' => $gId, 'name' => $p['gast_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0];
        }

        $ht = (int)$p['h_tore'];
        $gt = (int)$p['g_tore'];

        $rows[$hId]['sp']++;
        $rows[$gId]['sp']++;
        $rows[$hId]['tore_h'] += $ht;
        $rows[$hId]['tore_g'] += $gt;
        $rows[$gId]['tore_h'] += $gt;
        $rows[$gId]['tore_g'] += $ht;

        // status: 0 = regulär, 1 = i.E. (Elfmeterschießen), 2 = n.V. (nach
        // Verlängerung) – siehe statusSuffix(). Je nachdem gilt eine andere
        // Sieg/Unentschieden/Niederlage-Punktetabelle.
        [$curW, $curD, $curL] = match ((int)($p['status'] ?? 0)) {
            1       => [$ptWPS, $ptDPS, $ptLPS],
            2       => [$ptWET, $ptDET, $ptLET],
            default => [$ptW, $ptD, $ptL],
        };

        if ($ht > $gt) {
            $rows[$hId]['s']++;
            $rows[$hId]['pkt'] += $curW;
            $rows[$gId]['n']++;
            $rows[$gId]['pkt'] += $curL;
        } elseif ($ht < $gt) {
            $rows[$gId]['s']++;
            $rows[$gId]['pkt'] += $curW;
            $rows[$hId]['n']++;
            $rows[$hId]['pkt'] += $curL;
        } else {
            $rows[$hId]['u']++;
            $rows[$hId]['pkt'] += $curD;
            $rows[$gId]['u']++;
            $rows[$gId]['pkt'] += $curD;
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
 * Baut die Tabellen-Ansicht: Wertungshinweis (Sieg/Unentschieden-Punkte,
 * Sortierregel) + die eigentliche Tabelle.
 */
/**
 * Ermittelt die Randfarbe (Tabellenmarkierung, siehe Admin → Liga-
 * Einstellungen → Tabelle) für eine Tabellenzeile anhand ihres Rangs
 * (0-basiert). Von oben nach unten: Meister (nur Rang 1, falls aktiviert,
 * zählt zum CL-Kontingent dazu) → Champions League → CL-Qualifikation →
 * Euroleague. Von unten nach oben: feststehende Absteiger → Relegation.
 * Gibt einen Hex-Farbwert zurück, oder '' wenn dieser Rang keine
 * Markierung hat.
 */
function computeStandingsMarkerColor(int $index, int $totalTeams, array $opts) : string
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

    if ($champEnabled && $index === 0) {
        return $champColor;
    }
    if ($index < $cl) {
        return $clColor;
    }
    if ($index < $cl + $ck) {
        return $ckColor;
    }
    if ($index < $cl + $ck + $uc) {
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

function renderStandingsView(int $ligaId, array $allSpieltage) : string
{
    $opts      = getLigaOptions($ligaId);
    $teams     = getLigaTeamsList($ligaId);
    $partien   = getAllLigaPartien($allSpieltage);
    $rows      = computeStandings($teams, $partien, $opts);
    $favTeamId = resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));
    $totalTeams = count($rows);
    $showLogos  = ($opts['ShowLogos'] ?? '0') === '1';

    $rowsHtml = '';
    foreach ($rows as $i => $r) {
        $diff = $r['tore_h'] - $r['tore_g'];
        $markerColor = computeStandingsMarkerColor($i, $totalTeams, $opts);
        $rowsHtml .= renderPartial('standings_row', [
            'Platz'    => (string)($i + 1),
            'Logo'     => renderTeamLogoImgWrapped((int)$r['id'], $showLogos),
            'Team'     => h($r['name']),
            'TeamClass'=> ($favTeamId !== null && $r['id'] === $favTeamId) ? ' fav-team' : '',
            'RowStyle' => $markerColor !== '' ? ' style="border-left-color:' . h($markerColor) . '"' : '',
            'Sp'       => (string)$r['sp'],
            'S'        => (string)$r['s'],
            'U'        => (string)$r['u'],
            'N'        => (string)$r['n'],
            'Tore'     => $r['tore_h'] . ':' . $r['tore_g'],
            'Diff'     => ($diff > 0 ? '+' : '') . $diff,
            'DiffClass'=> $diff > 0 ? ' diff-pos' : ($diff < 0 ? ' diff-neg' : ''),
            'Pkt'      => (string)$r['pkt'],
        ]);
    }

    return renderPartial('standings_view', [
        'ColPlatz'    => h(tf('liga_standings_col_platz')),
        'ColTeam'     => h(tf('liga_standings_col_team')),
        'ColSp'       => h(tf('liga_standings_col_sp')),
        'ColS'        => h(tf('liga_standings_col_s')),
        'ColU'        => h(tf('liga_standings_col_u')),
        'ColN'        => h(tf('liga_standings_col_n')),
        'ColTore'     => h(tf('liga_standings_col_tore')),
        'ColDiff'     => h(tf('liga_standings_col_diff')),
        'ColPkt'      => h(tf('liga_standings_col_pkt')),
        'Rows'        => $rowsHtml,
    ]);
}

/**
 * Baut die Team-Spielplan-Ansicht für reguläre Ligen: Sidebar mit allen
 * Team-Kurznamen + (bei Auswahl) alle Partien dieses Teams über die ganze
 * Saison, chronologisch, mit fett hervorgehobenem eigenem Team.
 */
function renderTeamScheduleView(int $ligaId, array $allSpieltage, ?int $selectedTeamId) : string
{
    $teams     = getLigaTeamsList($ligaId);
    $showLogos = (getLigaOptions($ligaId)['ShowLogos'] ?? '0') === '1';

    $sidebarHtml = '';
    foreach ($teams as $t) {
        $sidebarHtml .= renderPartial('team_sidebar_item', [
            'ActiveClass' => ((int)$t['id'] === $selectedTeamId) ? ' team-sidebar-active' : '',
            'LigaId'      => $ligaId,
            'TeamId'      => (int)$t['id'],
            'Logo'        => renderTeamLogoImg((int)$t['id'], $showLogos),
            'Kurz'        => h($t['mittel'] !== '' ? $t['mittel'] : $t['name']),
        ]);
    }

    if ($selectedTeamId === null) {
        $contentHtml = '<p class="empty-msg">' . h(tf('liga_schedule_pick_team')) . '</p>';
    } else {
        $partien = getAllLigaPartien($allSpieltage);
        $rowsHtml = '';
        foreach ($partien as $p) {
            $hId = (int)($p['heim_id'] ?? 0);
            $gId = (int)($p['gast_id'] ?? 0);
            if ($hId !== $selectedTeamId && $gId !== $selectedTeamId) {
                continue;
            }
            $gespielt = $p['h_tore'] !== null && $p['g_tore'] !== null;
            $score    = $gespielt
                ? h((string)$p['h_tore']) . ' : ' . h((string)$p['g_tore']) . h(statusSuffix($p))
                : '- : -';
            $heimRaw = partieTeamName($p, 'heim');
            $gastRaw = partieTeamName($p, 'gast');
            $rowsHtml .= renderPartial('team_schedule_row', [
                'Nr'           => (string)$p['_spieltag_nummer'],
                'Datum'        => h(partieZeitDisplay($p, null)),
                'HeimClass'    => $hId === $selectedTeamId ? ' schedule-own' : '',
                'GastClass'    => $gId === $selectedTeamId ? ' schedule-own' : '',
                'Heim'         => partieTeamNameWithLogoReversed($p, 'heim', $showLogos),
                'Gast'         => partieTeamNameWithLogo($p, 'gast', $showLogos),
                'Ergebnis'     => $score,
                'ErgebnisOffenClass' => $gespielt ? '' : ' ergebnis-offen',
                'CompareIcon'  => renderH2hIcon($hId, $gId, $heimRaw, $gastRaw, $showLogos),
            ]);
        }
        $contentHtml = renderPartial('team_schedule_table', ['Rows' => $rowsHtml]) . renderH2hModalAssets();
    }

    return renderPartial('team_schedule_view', [
        'Sidebar' => $sidebarHtml,
        'Content' => $contentHtml,
    ]);
}

/**
 * Baut die Kreuztabelle: N×N-Gitter aller Teams (Heim = Zeilen, Gast =
 * Spalten), sortiert nach aktueller Tabellenposition. Jede Zelle zeigt das
 * Ergebnis der jeweiligen Heim-gegen-Gast-Begegnung (leer, falls noch nicht
 * gespielt; Diagonale immer leer).
 */
function renderKreuztabelleView(int $ligaId, array $allSpieltage) : string
{
    $opts      = getLigaOptions($ligaId);
    $teams     = getLigaTeamsList($ligaId);
    $partien   = getAllLigaPartien($allSpieltage);
    $standing  = computeStandings($teams, $partien, $opts);
    $favTeamId = resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));
    $showLogos = ($opts['ShowLogos'] ?? '0') === '1';

    $mittelById = [];
    foreach ($teams as $t) {
        $mittelById[(int)$t['id']] = $t['mittel'] ?? '';
    }

    // Lookup: "heimId_gastId" => letzte/aggregierte Begegnung dieser Richtung
    $lookup = [];
    foreach ($partien as $p) {
        $hId = (int)($p['heim_id'] ?? 0);
        $gId = (int)($p['gast_id'] ?? 0);
        if ($hId <= 0 || $gId <= 0) {
            continue;
        }
        $lookup[$hId . '_' . $gId] = $p;
    }

    $headerCells = '';
    foreach ($standing as $t) {
        // Bei aktivierter Logo-Einstellung steht in der Kopfzeile NUR das
        // Logo (kein Kürzel-Text mehr) – sonst wie bisher das Kürzel.
        $kurz = $t['kurz'] ?? '';
        $headerLabel = $showLogos
            ? renderTeamLogoImg((int)$t['id'], true)
            : h($kurz !== '' ? $kurz : $t['name']);
        $headerCells .= renderPartial('kreuz_header_cell', [
            'Label'       => $headerLabel,
            'HeaderClass' => ($favTeamId !== null && $t['id'] === $favTeamId) ? ' kz-fav' : '',
            'TeamId'      => (string)$t['id'],
        ]);
    }

    $bodyRows = '';
    foreach ($standing as $rowTeam) {
        $isFavRow  = $favTeamId !== null && $rowTeam['id'] === $favTeamId;
        $cellsHtml = '';
        foreach ($standing as $colTeam) {
            $isFavCol = $favTeamId !== null && $colTeam['id'] === $favTeamId;
            $favClass = ($isFavRow ? ' kz-fav-row' : '') . ($isFavCol ? ' kz-fav-col' : '');
            $cellVars = ['RowTeamId' => (string)$rowTeam['id'], 'ColTeamId' => (string)$colTeam['id']];
            if ($rowTeam['id'] === $colTeam['id']) {
                $cellsHtml .= renderPartial('kreuz_cell', $cellVars + ['CellClass' => ' kz-diag' . $favClass, 'Content' => '']);
                continue;
            }
            $p = $lookup[$rowTeam['id'] . '_' . $colTeam['id']] ?? null;
            if ($p === null || $p['h_tore'] === null || $p['g_tore'] === null) {
                $cellsHtml .= renderPartial('kreuz_cell', $cellVars + ['CellClass' => $favClass, 'Content' => '']);
            } else {
                $content = h((string)$p['h_tore']) . ':' . h((string)$p['g_tore']) . h(statusSuffix($p));
                $cellsHtml .= renderPartial('kreuz_cell', $cellVars + ['CellClass' => $favClass, 'Content' => $content]);
            }
        }
        $bodyRows .= renderPartial('kreuz_row', [
            'Label'          => $showLogos
                ? renderTeamLogoImg((int)$rowTeam['id'], true) . h($mittelById[(int)$rowTeam['id']] !== '' ? $mittelById[(int)$rowTeam['id']] : $rowTeam['name'])
                : h($rowTeam['name']),
            'RowLabelClass'  => $isFavRow ? ' kz-fav' : '',
            'TeamId'         => (string)$rowTeam['id'],
            'Cells'          => $cellsHtml,
        ]);
    }

    // Klick auf Spalten-Kopf oder Zeilen-Label hebt diese Mannschaft hervor
    // (ersetzt eine ggf. serverseitig vorgegebene favTeam-Hervorhebung). Ohne
    // hinterlegte Lieblingsmannschaft ist beim Aufruf noch nichts markiert.
    $script = '<script>(function(){'
        . 'var t=document.querySelectorAll(".kreuz-table th.kz-col,.kreuz-table th.kz-rowlabel");'
        . 'function clear(){document.querySelectorAll(".kreuz-table .kz-fav,.kreuz-table .kz-fav-row,.kreuz-table .kz-fav-col")'
        . '.forEach(function(el){el.classList.remove("kz-fav","kz-fav-row","kz-fav-col");});}'
        . 'function apply(id){'
        . 'document.querySelectorAll(\'.kreuz-table th[data-team="\'+id+\'"]\').forEach(function(el){el.classList.add("kz-fav");});'
        . 'document.querySelectorAll(\'.kreuz-table td[data-row="\'+id+\'"]\').forEach(function(el){el.classList.add("kz-fav-row");});'
        . 'document.querySelectorAll(\'.kreuz-table td[data-col="\'+id+\'"]\').forEach(function(el){el.classList.add("kz-fav-col");});'
        . '}'
        . 't.forEach(function(el){el.style.cursor="pointer";el.addEventListener("click",function(){'
        . 'var id=el.getAttribute("data-team");clear();apply(id);'
        . '});});'
        . '})();</script>';

    return renderPartial('kreuz_view', [
        'HeaderCells' => $headerCells,
        'BodyRows'    => $bodyRows,
    ]) . $script;
}

/**
 * Feste Farbpalette für die Fieberkurven-Linien (zyklisch, falls mehr Teams
 * als Farben vorhanden sind).
 */
function fieberkurveColors() : array
{
    return [
        '#2563eb', '#dc2626', '#16a34a', '#9333ea', '#ea580c', '#0891b2',
        '#ca8a04', '#db2777', '#4338ca', '#65a30d', '#7c2d12', '#0d9488',
        '#be123c', '#4d7c0f', '#6d28d9', '#b45309', '#0369a1', '#a21caf',
    ];
}

/**
 * Baut die Fieberkurve: Liniendiagramm der Tabellenposition jedes Teams über
 * die gespielten Spieltage hinweg (Position 1 oben, wächst nach unten – wie
 * ein "Fieberthermometer"). Reines SVG, keine externe Chart-Bibliothek.
 */
function renderFieberkurveView(int $ligaId, array $allSpieltage) : string
{
    $opts  = getLigaOptions($ligaId);
    $teams = getLigaTeamsList($ligaId);
    if (empty($teams)) {
        return renderPartial('fieberkurve_view', ['Content' => '<p class="empty-msg">' . h(tf('liga_fieberkurve_no_data')) . '</p>']);
    }

    usort($allSpieltage, static fn($a, $b) => (int)$a['nummer'] <=> (int)$b['nummer']);

    $positionsByTeam = [];
    $matchdays       = [];
    $cumulative      = [];

    foreach ($allSpieltage as $st) {
        $nr        = (int)$st['nummer'];
        $stPartien = getSpieltagPartien((int)$st['id']);
        $hasPlayed = false;
        foreach ($stPartien as $p) {
            if ($p['h_tore'] !== null && $p['g_tore'] !== null) {
                $hasPlayed = true;
                break;
            }
        }
        $cumulative = array_merge($cumulative, $stPartien);
        if (!$hasPlayed) {
            continue;
        }
        $standing    = computeStandings($teams, $cumulative, $opts);
        $matchdays[] = $nr;
        foreach ($standing as $i => $row) {
            $positionsByTeam[$row['id']]['name'] = $row['name'];
            $positionsByTeam[$row['id']]['pos'][$nr] = $i + 1;
        }
    }

    if (empty($matchdays)) {
        return renderPartial('fieberkurve_view', ['Content' => '<p class="empty-msg">' . h(tf('liga_fieberkurve_no_data')) . '</p>']);
    }

    $numTeams = count($teams);
    $minMd    = min($matchdays);
    $maxMd    = max($matchdays);
    $allMd    = range($minMd, $maxMd);

    $colors  = fieberkurveColors();
    $i       = 0;
    $datasets = [];
    foreach ($positionsByTeam as $teamId => $data) {
        $color = $colors[$i % count($colors)];
        $series = [];
        foreach ($allMd as $md) {
            $series[] = $data['pos'][$md] ?? null;
        }
        $datasets[] = [
            'label'           => $data['name'],
            'data'            => $series,
            'borderColor'     => $color,
            'backgroundColor' => $color,
            'spanGaps'        => true,
            'borderWidth'     => 2,
            'pointRadius'     => 0,
            'pointHoverRadius' => 4,
            'tension'         => 0.35,
            'hidden'          => $i >= 2,
        ];
        $i++;
    }

    $chartData = [
        'labels'   => $allMd,
        'datasets' => $datasets,
    ];
    $chartJson = str_replace('</script>', '<\/script>', json_encode($chartData, JSON_UNESCAPED_UNICODE));

    $canvasId = 'fk-canvas-' . $ligaId;
    $content  = '<script src="assets/vendor/chart.umd.min.js"></script>';
    $content .= '<div class="fk-chart-wrap"><canvas id="' . h($canvasId) . '"></canvas></div>';
    $content .= '<script>';
    $content .= '(function(){var ctx=document.getElementById(' . json_encode($canvasId) . ');';
    $content .= 'new Chart(ctx,{type:"line",data:' . $chartJson . ',options:{';
    $content .= 'responsive:true,maintainAspectRatio:false,interaction:{mode:"nearest",intersect:false},';
    $content .= 'scales:{y:{reverse:true,min:1,max:' . $numTeams . ',ticks:{stepSize:1}},x:{title:{display:true,text:' . json_encode(tf('liga_col_spieltag_short')) . '}}},';
    $content .= 'plugins:{legend:{position:"top",labels:{boxWidth:12,font:{size:11}}}}';
    $content .= '}});})();';
    $content .= '</script>';

    return renderPartial('fieberkurve_view', ['Content' => $content]);
}

/**
 * Serien (Siege/Unentschieden/Niederlagen am Stück, Ungeschlagen/Sieglos-
 * Läufe) für ein Team, chronologisch über alle gespielten Partien.
 * "current" = Stand nach dem letzten Spiel, "best" = längste Serie der Saison
 * (inkl. Spieltag-Spanne).
 */
function computeTeamStreaks(int $teamId, array $partienChrono) : array
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
function computeAllTeamsStreakRecords(array $teams, array $partien) : array
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
        $streaks = computeTeamStreaks($tid, $ownMatches);

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
function findExtremeMatches(array $partien) : array
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
function computeTeamDetailStats(int $teamId, array $teams, array $partien, array $standing) : array
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
        $oppName = $isHeim ? ($p['gast_name'] ?? partieTeamName($p, 'gast')) : ($p['heim_name'] ?? partieTeamName($p, 'heim'));

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

    $streaks = computeTeamStreaks($teamId, $ownMatches);
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

/**
 * Baut eine einzelne Team-Statistik-Box (Position, Punkte, Siege/
 * Niederlagen inkl. Extremwerten, aktuelle Serie, Restprogramm).
 */
function renderTeamStatBox(array $stat, int $teamId = 0, bool $showLogos = false) : string
{
    $bw = $stat['bestWin'];
    $bestWinTxt = $bw
        ? h($stat['name']) . ' ' . h((string)$bw['own']) . ':' . h((string)$bw['opp']) . ' ' . h($bw['oppName']) . ' (' . ($bw['heim'] ? h(tf('liga_stat_home')) : h(tf('liga_stat_away'))) . ', ' . (int)$bw['nr'] . '. ' . h(tf('liga_col_spieltag_short')) . ')'
        : '–';
    $bl = $stat['bestLoss'];
    $bestLossTxt = $bl
        ? h($stat['name']) . ' ' . h((string)$bl['own']) . ':' . h((string)$bl['opp']) . ' ' . h($bl['oppName']) . ' (' . ($bl['heim'] ? h(tf('liga_stat_home')) : h(tf('liga_stat_away'))) . ', ' . (int)$bl['nr'] . '. ' . h(tf('liga_col_spieltag_short')) . ')'
        : '–';

    $remainingTxt = '–';
    if (!empty($stat['remaining'])) {
        $parts = [];
        foreach ($stat['remaining'] as $r) {
            $parts[] = h($r['opp']) . ' (' . ($r['heim'] ? h(tf('liga_stat_home_short')) : h(tf('liga_stat_away_short'))) . ')';
        }
        $remainingTxt = implode(', ', $parts);
    }

    $streakTxt = !empty($stat['streakLines']) ? implode('<br>', array_map('h', $stat['streakLines'])) : '–';

    $html  = '<div class="ligastat-box">';
    $html .= '<h3>' . renderTeamLogoImg($teamId, $showLogos) . h($stat['name']) . '</h3>';
    $html .= '<table class="ligastat-kv">';
    $html .= '<tr><td>' . h(tf('liga_stat_position')) . '</td><td>' . h((string)$stat['position']) . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_points')) . '</td><td>' . h((string)$stat['pkt']) . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_played')) . '</td><td>' . h((string)$stat['sp']) . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_ppg')) . '</td><td>' . h((string)$stat['ppg']) . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_goals')) . '</td><td>' . h((string)$stat['toreH']) . ':' . h((string)$stat['toreG']) . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_goals_per_game')) . '</td><td>' . h($stat['goalsPerGame']) . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_wins')) . '</td><td>' . h((string)$stat['wins']) . ' (' . h((string)$stat['winPct']) . '%)</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_best_win')) . '</td><td>' . $bestWinTxt . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_losses')) . '</td><td>' . h((string)$stat['losses']) . ' (' . h((string)$stat['lossPct']) . '%)</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_worst_loss')) . '</td><td>' . $bestLossTxt . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_current_streak')) . '</td><td>' . $streakTxt . '</td></tr>';
    $html .= '<tr><td>' . h(tf('liga_stat_remaining')) . '</td><td>' . $remainingTxt . '</td></tr>';
    $html .= '</table></div>';

    return $html;
}

/**
 * Baut den immer sichtbaren "Statistische Daten zur Liga"-Block: Spiele,
 * Tore, Extremwerte, Serien-Rekorde (ligaweit).
 */
function renderOverallStatsBlock(array $teams, array $partien) : string
{
    $totalGames = 0;
    $homeWins   = 0;
    $draws      = 0;
    $awayWins   = 0;
    $totalGoals = 0;
    $homeGoals  = 0;
    $awayGoals  = 0;

    foreach ($partien as $p) {
        if ($p['h_tore'] === null || $p['g_tore'] === null) {
            continue;
        }
        $h = (int)$p['h_tore'];
        $g = (int)$p['g_tore'];
        $totalGames++;
        $homeGoals  += $h;
        $awayGoals  += $g;
        $totalGoals += $h + $g;
        if ($h > $g) {
            $homeWins++;
        } elseif ($h < $g) {
            $awayWins++;
        } else {
            $draws++;
        }
    }

    $pct = static fn(int $n, int $total) : string => $total > 0 ? round($n / $total * 100) . '%' : '0%';
    $avg = static fn(int $n, int $total) : string => $total > 0 ? (string)round($n / $total, 2) : '0';

    $extremes = findExtremeMatches($partien);
    $matchLine = static function (array $p) : string {
        return h(partieTeamName($p, 'heim')) . ' - ' . h(partieTeamName($p, 'gast')) . '&nbsp;&nbsp;'
             . h((string)$p['h_tore']) . ':' . h((string)$p['g_tore'])
             . ' (' . (int)$p['_spieltag_nummer'] . '.)';
    };

    $html  = '<div class="card"><h2>' . h(tf('liga_stat_overall_title')) . '</h2>';
    $html .= '<table class="ligastat-overall">';
    $html .= '<tr><th>' . h(tf('liga_stat_games')) . '</th><th>' . h(tf('liga_stat_home_wins')) . '</th><th>' . h(tf('liga_stat_draws')) . '</th><th>' . h(tf('liga_stat_away_wins')) . '</th></tr>';
    $html .= '<tr><td><strong>' . $totalGames . '</strong></td><td>' . $homeWins . ' (' . $pct($homeWins, $totalGames) . ')</td><td>' . $draws . ' (' . $pct($draws, $totalGames) . ')</td><td>' . $awayWins . ' (' . $pct($awayWins, $totalGames) . ')</td></tr>';
    $html .= '</table>';

    $html .= '<table class="ligastat-overall">';
    $html .= '<tr><th>' . h(tf('liga_stat_goals_total')) . '</th><th>' . h(tf('liga_stat_home_goals')) . '</th><th>' . h(tf('liga_stat_away_goals')) . '</th></tr>';
    $html .= '<tr><td><strong>' . $totalGoals . '</strong> (Ø ' . $avg($totalGoals, $totalGames) . ')</td><td>' . $homeGoals . ' (' . $pct($homeGoals, $totalGoals) . ', Ø ' . $avg($homeGoals, $totalGames) . ')</td><td>' . $awayGoals . ' (' . $pct($awayGoals, $totalGoals) . ', Ø ' . $avg($awayGoals, $totalGames) . ')</td></tr>';
    $html .= '</table>';

    if (!empty($extremes['homeWins'])) {
        $html .= '<p><strong>' . h(tf('liga_stat_highest_home_win')) . '</strong><br>' . implode('<br>', array_map($matchLine, $extremes['homeWins'])) . '</p>';
    }
    if (!empty($extremes['awayWins'])) {
        $html .= '<p><strong>' . h(tf('liga_stat_highest_away_win')) . '</strong><br>' . implode('<br>', array_map($matchLine, $extremes['awayWins'])) . '</p>';
    }
    if (!empty($extremes['mostGoals'])) {
        $html .= '<p><strong>' . h(tf('liga_stat_most_goals')) . '</strong><br>' . implode('<br>', array_map($matchLine, $extremes['mostGoals'])) . '</p>';
    }

    $records = computeAllTeamsStreakRecords($teams, $partien);
    $catLabels = [
        'win'      => 'liga_stat_streak_cat_won',
        'unbeaten' => 'liga_stat_streak_cat_unbeaten',
        'draw'     => 'liga_stat_streak_cat_draw',
        'winless'  => 'liga_stat_streak_cat_winless',
        'loss'     => 'liga_stat_streak_cat_lost',
    ];
    $html .= '<table class="ligastat-overall"><tr><th>' . h(tf('liga_stat_streaks_title')) . '</th><th>' . h(tf('liga_stat_streaks_current')) . '</th><th>' . h(tf('liga_stat_streaks_season')) . '</th></tr>';
    foreach ($catLabels as $cat => $labelKey) {
        $rec = $records[$cat];
        $aktuellTxt = $rec['aktuell']['len'] > 0 ? $rec['aktuell']['len'] . ' ' . h(implode(', ', $rec['aktuell']['teams'])) : '–';
        $saisonTxt  = $rec['saison']['len'] > 0
            ? $rec['saison']['len'] . ' ' . h(implode(', ', $rec['saison']['teams'])) . ' (' . $rec['saison']['from'] . '.-' . $rec['saison']['to'] . '.)'
            : '–';
        $html .= '<tr><td>' . h(tf($labelKey)) . '</td><td>' . $aktuellTxt . '</td><td>' . $saisonTxt . '</td></tr>';
    }
    $html .= '</table></div>';

    return $html;
}

/**
 * Baut die komplette Ligastatistik-Ansicht: Team-Auswahl (0/1/2 Teams),
 * Detail-Boxen (bei 2 Teams zusätzlich Chancen gegeneinander + einfache
 * Restprogramm-Bewertung), plus den immer sichtbaren Liga-weiten Block.
 */
function renderLigastatistikView(int $ligaId, array $allSpieltage, ?int $team1Id, ?int $team2Id) : string
{
    $opts     = getLigaOptions($ligaId);
    $teams    = getLigaTeamsList($ligaId);
    $partien  = getAllLigaPartien($allSpieltage);
    $standing = computeStandings($teams, $partien, $opts);
    $showLogos = ($opts['ShowLogos'] ?? '0') === '1';

    $pickerOptions = '<option value="0">– ' . h(tf('liga_stat_pick_team')) . ' –</option>';
    foreach ($teams as $t) {
        $pickerOptions .= '<option value="' . (int)$t['id'] . '">' . h($t['name']) . '</option>';
    }

    $picker  = '<div class="ligastat-picker">';
    $picker .= '<select id="team1-select" onchange="location.href=\'liga.php?id=' . $ligaId . '&view=ligastatistik&team1=\'+this.value+\'&team2=\'+document.getElementById(\'team2-select\').value">'
             . str_replace('value="' . $team1Id . '"', 'value="' . $team1Id . '" selected', $pickerOptions) . '</select>';
    $picker .= '<select id="team2-select" onchange="location.href=\'liga.php?id=' . $ligaId . '&view=ligastatistik&team2=\'+this.value+\'&team1=\'+document.getElementById(\'team1-select\').value">'
             . str_replace('value="' . $team2Id . '"', 'value="' . $team2Id . '" selected', $pickerOptions) . '</select>';
    $picker .= '</div>';

    $html = '<div class="card">' . $picker;

    if ($team1Id === null && $team2Id === null) {
        $html .= '<p class="empty-msg">' . h(tf('liga_stat_pick_team_msg')) . '</p>';
    } elseif ($team1Id !== null && $team2Id !== null) {
        $stat1 = computeTeamDetailStats($team1Id, $teams, $partien, $standing);
        $stat2 = computeTeamDetailStats($team2Id, $teams, $partien, $standing);

        $ppg1 = max(0.01, $stat1['ppg']);
        $ppg2 = max(0.01, $stat2['ppg']);
        $chance1 = round($ppg1 / ($ppg1 + $ppg2) * 100);
        $chance2 = 100 - $chance1;

        $html .= '<p class="ligastat-chances"><strong>' . h(tf('liga_stat_chances')) . ':</strong> '
               . h($stat1['name']) . ' ' . $chance1 . '% – ' . $chance2 . '% ' . h($stat2['name']) . '</p>';

        $html .= '<div class="ligastat-compare">' . renderTeamStatBox($stat1, $team1Id, $showLogos) . renderTeamStatBox($stat2, $team2Id, $showLogos) . '</div>';

        if ($stat1['remainingPpgAvg'] !== null && $stat2['remainingPpgAvg'] !== null) {
            $r1 = round($stat1['remainingPpgAvg'], 2);
            $r2 = round($stat2['remainingPpgAvg'], 2);
            if (abs($r1 - $r2) < 0.05) {
                $tendenz = h(tf('liga_stat_tendenz_equal'));
            } elseif ($r1 > $r2) {
                $tendenz = h(tf('liga_stat_tendenz_harder', ['team' => $stat1['name']]));
            } else {
                $tendenz = h(tf('liga_stat_tendenz_harder', ['team' => $stat2['name']]));
            }
            $html .= '<div class="card ligastat-remaining-eval"><h3>' . h(tf('liga_stat_remaining_eval_title')) . '</h3>';
            $html .= '<table class="ligastat-overall"><tr><th>' . h($stat1['name']) . '</th><th>' . h(tf('liga_stat_remaining_ppg')) . '</th><th>' . h($stat2['name']) . '</th></tr>';
            $html .= '<tr><td>' . $r1 . '</td><td>' . $tendenz . '</td><td>' . $r2 . '</td></tr></table></div>';
        }
    } else {
        $soloId = $team1Id ?? $team2Id;
        $stat    = computeTeamDetailStats($soloId, $teams, $partien, $standing);
        $html .= renderTeamStatBox($stat, $soloId, $showLogos);
    }

    $html .= '</div>';
    $html .= renderOverallStatsBlock($teams, $partien);

    return $html;
}
