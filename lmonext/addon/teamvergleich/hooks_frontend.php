<?php
/**
 * Project: LMOnext
 * Filename: addon/teamvergleich/hooks_frontend.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Bindet die Teamvergleich-Logik (HeadToHead.php) über den generischen
 * Hook-Mechanismus des AddonManager an die vier Core-Anknüpfpunkte an
 * (siehe frontend/data_liga.php für die dortigen, kommentierten Wrapper-
 * Funktionen, die diese Hooks feuern). Wird von AddonManager::bootFrontend()
 * automatisch geladen, wenn das Addon aktiviert ist (siehe addon.json,
 * Feld "frontend_handlers").
 *
 * WICHTIG: die neue Einstellung "show_teamvergleich" (Administrator →
 * Einstellungen → Optionen → Anzeigen/Darstellung, nur sichtbar bei aktivem
 * Addon) wird bewusst HIER geprüft, nicht in HeadToHead.php selbst - die
 * Kernlogik-Klasse bleibt so eine reine, wiederverwendbare Datenverarbeitung
 * ohne Kenntnis von Ein-/Ausschalt-Einstellungen. Ist die Einstellung aus,
 * bleiben liga.compare_icon/liga.compare_modal_assets einfach unbehandelt
 * (Hook-Datenarray behält seinen leeren 'html'-Ausgangswert) - liga.h2h_matches/
 * liga.h2h_payload werden davon NICHT betroffen (der PDF-Export
 * (exportH2hPdf(), siehe pdf-export-Addon) braucht die Rohdaten weiterhin,
 * auch wenn das Icon/Modal für Besucher ausgeblendet ist - liga.php sichert
 * den h2h_pdf-Aufruf bereits separat über isEnabled('teamvergleich') ab,
 * nicht über show_teamvergleich - das ist eine bewusste Trennung: "Addon
 * aktiv" entscheidet über PDF-Erreichbarkeit, "show_teamvergleich" nur über
 * die Sichtbarkeit von Icon/Modal für normale Besucher).
 */
declare(strict_types = 1);

require_once __DIR__ . '/HeadToHead.php';

use LMOnext\Addon\Teamvergleich\HeadToHead;

// Eigene Sprachdateien laden (addon/teamvergleich/lang/de.php + en.php) -
// siehe dortiger Docblock: Schlüssel, die ausschließlich von diesem Addon
// (bzw. vom pdf-export-Addon nur innerhalb dessen H2H-PDF-Export) genutzt
// werden, wurden aus lang/frontend/*.php hierher verschoben. Diese Datei
// wird über frontend_handlers bei jedem Request geladen (siehe addon.json),
// daher genügt ein einmaliger Aufruf hier.
if (function_exists('addonManager')) {
    \addonManager()->loadLanguages('teamvergleich');
}

registerHook('liga.h2h_matches', static function (array $data) : array {
    $data['matches'] = HeadToHead::getHeadToHeadMatches((int)$data['idA'], (int)$data['idB']);
    return $data;
});

registerHook('liga.h2h_payload', static function (array $data) : array {
    $data['json'] = HeadToHead::buildHeadToHeadPayload(
        (int)$data['idA'],
        (int)$data['idB'],
        (string)$data['nameA'],
        (string)$data['nameB'],
        (bool)$data['showLogos']
    );
    return $data;
});

registerHook('liga.compare_icon', static function (array $data) : array {
    if (getAdminSetting('show_teamvergleich', '1') !== '1') {
        return $data;
    }
    $data['html'] = HeadToHead::renderH2hIcon(
        (int)$data['heim_id'],
        (int)$data['gast_id'],
        (string)$data['heim_name'],
        (string)$data['gast_name'],
        (bool)$data['show_logos']
    );
    return $data;
});

registerHook('liga.compare_modal_assets', static function (array $data) : array {
    if (getAdminSetting('show_teamvergleich', '1') !== '1') {
        return $data;
    }
    $data['html'] = HeadToHead::renderH2hModalAssets();
    return $data;
});
