<?php
/**
 * Project: LMOnext
 * Filename: lang/admin/addons_de.php
 * Sprachvariablen für die Addon-Verwaltung
 *
 * @license   GPL-3.0-only
 */

return [
    // ── Navigation ────────────────────────────────────────────────────────────
    'nav_addons'               => 'Addons',

    // ── Stat-Karten ───────────────────────────────────────────────────────────
    'addons_stat_total'        => 'Addons gesamt',
    'addons_stat_enabled'      => 'Aktiviert',
    'addons_stat_disabled'     => 'Deaktiviert',

    // ── Tabs ───────────────────────────────────────────────────────────────────
    'addons_tab_all'           => 'Alle',
    'addons_tab_admin'        => 'Admin',
    'addons_tab_frontend'     => 'Frontend',
    'addons_tab_both'         => 'Admin + Frontend',
    'addons_tab_standalone'   => 'Standalone',
    'addons_tab_settings'   => 'Einstellungen',
    'addons_tab_catalog'    => 'Entdecken',

    // ── Tabelle ────────────────────────────────────────────────────────────────
    'addons_col_addon'         => 'Addon',
    'addons_col_type'          => 'Typ',
    'addons_col_version'       => 'Version',
    'addons_col_description'   => 'Beschreibung',
    'addons_col_status'        => 'Status',
    'addons_col_actions'       => 'Aktion',

    // ── Status ──────────────────────────────────────────────────────────────────
    'addons_status_active'     => 'Aktiv',
    'addons_status_inactive'   => 'Inaktiv',

    // ── Buttons ────────────────────────────────────────────────────────────────
    'addons_btn_enable'        => 'Aktivieren',
    'addons_btn_disable'       => 'Deaktivieren',

    // ── Bestätigungen ──────────────────────────────────────────────────────────
    'addons_confirm_disable'   => 'Addon "{name}" wirklich deaktivieren? Frontend- und Admin-Funktionen werden dann nicht mehr verfügbar sein.',

    // ── Flash-Meldungen ─────────────────────────────────────────────────────────
    'addons_flash_enabled'     => 'Addon "{name}" wurde aktiviert.',
    'addons_flash_disabled'    => 'Addon "{name}" wurde deaktiviert.',
    'addons_flash_enable_error'=> 'Addon "{name}" konnte nicht aktiviert werden: {error}',
    'addons_flash_disable_error'=> 'Addon "{name}" konnte nicht deaktiviert werden: {error}',

    // ── Fehler ──────────────────────────────────────────────────────────────────
    'addons_err_invalid_action'=> 'Ungültige Aktion.',
    'addons_err_invalid_name'  => 'Ungültiger Addon-Name.',
    'addons_err_not_found'    => 'Addon nicht gefunden.',
    'addons_err_dep_missing'   => 'Abhängigkeiten fehlen: {deps}',
    'addons_err_core_version'  => 'Core-Version zu niedrig. Benötigt: {need}, vorhanden: {have}',
    'addons_install_err_unsafe_path'         => 'Sicherheitsprüfung fehlgeschlagen: die ZIP enthält einen unzulässigen Dateipfad (Pfad-Traversal). Installation abgelehnt.',
    'addons_install_err_disallowed_filetype' => 'Sicherheitsprüfung fehlgeschlagen: die ZIP enthält einen nicht erlaubten Dateityp. Erlaubt sind nur: php, json, md, txt, png, jpg, jpeg, gif, svg, ico, webp, css, js.',
    'addons_install_err_lint_failed'         => 'Sicherheitsprüfung fehlgeschlagen: die Datei "{file}" enthält keinen gültigen PHP-Code. Installation abgelehnt.',
    'addons_install_err_dangerous_pattern'   => 'Sicherheitsprüfung fehlgeschlagen: die Datei "{file}" enthält einen verdächtigen Funktionsaufruf (z.B. Shell-Ausführung oder dynamische Codeausführung). Installation abgelehnt. Falls dies ein Fehlalarm bei einem legitimen Addon ist, wende dich an den Addon-Autor.',
    'addons_install_err_copy_failed'         => 'Die neuen Dateien konnten nicht vollständig eingespielt werden (z.B. weil eine Datei gerade in Benutzung war). Es wurde nichts geändert - bitte erneut versuchen.',
    'addons_url_install_invalid'  => 'Ungültige URL. Bitte einen direkten Link zu einem öffentlichen GitHub-Repository angeben, z.B. https://github.com/owner/repo',
    'addons_url_install_no_release' => 'Kein Release gefunden. Das Repository muss mindestens ein veröffentlichtes GitHub-Release (kein reiner Branch/Commit) mit einer gültigen addon.json enthalten.',
    'addons_url_install_title' => 'Addon von GitHub-URL installieren',
    'addons_url_install_desc'  => 'Direktinstallation eines Addons aus einem öffentlichen GitHub-Repository, ohne die ZIP-Datei erst manuell herunterzuladen und wieder hochzuladen. Es wird das neueste veröffentlichte GitHub-Release verwendet - genau wie beim ZIP-Upload durchläuft der Download dieselben Sicherheitsprüfungen.',
    'addons_url_install_label' => 'GitHub-Repository-URL',
    'addons_url_install_btn'   => 'Installieren',
    'addons_catalog_desc'      => 'Alle offiziellen LMOnext-Addons auf einen Blick. Installation direkt vom jeweiligen GitHub-Repository (neuestes veröffentlichtes Release) - mit denselben Sicherheitsprüfungen wie beim manuellen ZIP-Upload. Bereits installierte Addons sind entsprechend markiert; ihre Verwaltung (Aktivieren/Updates) läuft weiterhin über die anderen Tabs.',
    'addons_catalog_btn_install' => 'Installieren',
    'addons_catalog_unofficial_hint' => 'Kein offizielles Addon dabei, das du suchst? Unter „⚙ Einstellungen" lässt sich jedes Addon auch per ZIP-Datei oder direkt über eine beliebige GitHub-Repository-URL installieren - offiziell oder inoffiziell.',
    'addons_demo_mode_blocked' => 'Diese Aktion ist auf dieser Demo-Instanz deaktiviert. Aktivieren/Deaktivieren bereits installierter Addons funktioniert weiterhin normal.',
    'addons_demo_mode_notice'  => 'Auf dieser Demo-Instanz ist die Installation/Aktualisierung von Addon-Code deaktiviert. Aktivieren/Deaktivieren bereits installierter Addons ist weiterhin möglich.',
    'addons_purge_btn'           => '🗑️ Daten löschen',
    'addons_purge_confirm'       => 'ACHTUNG: Alle Daten des Addons "{name}" werden UNWIDERRUFLICH aus der Datenbank gelöscht (Tabellen: {tables}). Der Addon-Code selbst bleibt erhalten und kann jederzeit erneut aktiviert werden - dann aber mit leeren Tabellen. Wirklich fortfahren?',
    'addons_purge_hint'          => 'Löscht die eigenen Datenbank-Tabellen dieses deaktivierten Addons unwiderruflich. Der Addon-Code selbst bleibt erhalten.',
    'addons_purge_err_still_enabled' => 'Addon "{name}" ist noch aktiviert - zum Löschen der Daten bitte zuerst deaktivieren.',
    'addons_purge_err_no_tables'     => 'Addon "{name}" hat keine eigenen Datenbank-Tabellen deklariert - nichts zu löschen.',
    'addons_purge_flash_success'     => 'Daten von "{name}" gelöscht ({n} Tabelle(n): {tables}).',
    'addons_purge_flash_error'       => 'Löschen der Daten von "{name}" fehlgeschlagen.',
    'addons_err_depended_on'   => 'Addon "{addon}" kann nicht deaktiviert werden, da das aktivierte Addon "{by}" davon abhängt.',

    // ── Leer-Status ─────────────────────────────────────────────────────────────
    'addons_empty'             => 'Keine Addons gefunden.',
    'addons_empty_hint'        => 'Im Tab „🧭 Entdecken" findest du alle offiziellen Addons zur Direktinstallation, oder installiere ein eigenes über „⚙ Einstellungen" (ZIP-Upload oder GitHub-URL).',

    // ── Meta-Info ───────────────────────────────────────────────────────────────
    'addons_min_core'          => 'Minimale Core-Version',
    'addons_db_tables'         => 'Datenbanktabellen',
    'addons_tables_short'      => 'Tab.',
    'addons_depends_on'        => 'Abhängig von',
    'addons_dep_missing'       => 'Abhängigkeiten fehlen — Aktivieren nicht möglich',

    // ── Info-Box ─────────────────────────────────────────────────────────────────
    'addons_info_title'        => 'Wie funktionieren Addons?',
    'addons_info_text'         => 'Addons werden automatisch erkannt, wenn sie ein addon.json Manifest im Verzeichnis addon/ enthalten. Jedes Addon ist eigenständig und kann separat auf GitHub verwaltet werden. Beim Aktivieren werden Handler und Views automatisch registriert; beim Deaktivieren werden sie aus dem Router entfernt, ohne Dateien zu löschen.',

    // ── GitHub Update-Check ───────────────────────────────────────────────────────
    'addons_btn_check_updates'  => 'Updates prüfen',
    'addons_updates_available'  => '{count} Update(s) verfügbar — siehe Versionsspalte.',
    'addons_no_updates'         => 'Alle Addons sind auf dem neuesten Stand.',
    'addons_updates_badge_text'  => 'Update(s) verfügbar — jetzt prüfen:',
    'addons_update_tooltip'     => 'Version {latest} verfügbar — Update automatisch installieren',
    'addons_update_btn'          => 'Update installieren',
    'addons_update_confirm'      => 'Update für "{name}" auf Version {latest} installieren? Die aktuellen Dateien werden vorher automatisch als ZIP gesichert.',
    'addons_update_installed'    => '"{name}" erfolgreich von v{from} auf v{to} aktualisiert. Backup wurde erstellt.',
    'addons_update_err_no_github'      => 'Update fehlgeschlagen: Kein GitHub-Repo in der homepage-URL hinterlegt.',
    'addons_update_err_rate_limited'    => 'Update fehlgeschlagen: GitHub Rate-Limit erreicht. Bitte später erneut versuchen oder GitHub-Token hinterlegen.',
    'addons_update_err_no_release'      => 'Update fehlgeschlagen: Kein Release auf GitHub gefunden.',
    'addons_update_err_fetch_failed'    => 'Update fehlgeschlagen: GitHub nicht erreichbar.',
    'addons_update_err_already_current'=> '"{name}" ist bereits aktuell.',
    'addons_update_err_zip_missing'    => 'Update fehlgeschlagen: PHP ZipArchive-Erweiterung fehlt auf diesem Server.',
    'addons_update_err_no_http_client'  => 'Fehlgeschlagen: kein HTTP-Client verfügbar (weder allow_url_fopen noch cURL aktiv).',
    'addons_update_err_download_failed'=> 'Update fehlgeschlagen: Release-Download von GitHub fehlgeschlagen.',
    'addons_update_err_zip_failed'      => 'Update fehlgeschlagen: Heruntergeladenes ZIP konnte nicht geöffnet werden.',
    'addons_update_err_zip_structure'  => 'Update fehlgeschlagen: Unerwartete ZIP-Struktur im Release.',
    'addons_update_err_no_manifest'    => 'Update fehlgeschlagen: Keine addon.json im Release gefunden.',
    'addons_update_err_name_mismatch'  => 'Update fehlgeschlagen: Addon-Name im Release passt nicht zum lokalen Addon (Sicherheitsprüfung).',
    'addons_update_err_generic'        => 'Update fehlgeschlagen: {error}',
    'addons_update_error'       => 'Update-Check fehlgeschlagen (GitHub nicht erreichbar oder kein Release)',
    'addons_updates_all_failed'  => 'Update-Check fehlgeschlagen — GitHub nicht erreichbar. Bitte Server-Verbindung prüfen.',
    'addons_updates_some_failed'  => '{checked} Addons geprüft, {errors} Fehler (GitHub nicht erreichbar). Keine Updates gefunden.',

    // GitHub Token
    'addons_token_title'         => 'GitHub Token (optional)',
    'addons_token_desc'          => 'Ein Personal Access Token erhöht das Rate-Limit von 60 auf 5.000 Anfragen/Stunde. Nur Scope "public_repo" nötig. GitHub Settings → Developer settings → Personal access tokens.',
    'addons_token_btn_save'      => 'Token speichern',
    'addons_token_btn_delete'     => 'Token löschen',
    'addons_token_confirm_delete'=> 'GitHub Token wirklich löschen? Update-Checks laufen dann wieder mit 60/Std-Limit.',
    'addons_token_saved'         => 'GitHub Token gespeichert. Update-Checks nutzen jetzt 5.000 Anfragen/Stunde.',
    'addons_token_deleted'       => 'GitHub Token gelöscht. Update-Checks laufen wieder mit Standard-Limit (60/Std).',
    'addons_token_invalid'       => 'Ungültiges Token-Format. Ein GitHub Token besteht aus mindestens 16 Zeichen (Buchstaben, Zahlen, Unterstrich).',
    'addons_token_help'          => 'Token erstellen: GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic) → Generate new token. Scope "public_repo" ausreichen. Kostenlos.',
// ── ZIP-Installation ──────────────────────────────────────────────────
    'addons_install_title'        => 'Addon per ZIP installieren',
    'addons_install_desc'         => 'Lade eine ZIP-Datei hoch, die eine addon.json enthält. Das Addon wird automatisch nach addon/ installiert. Wenn es bereits existiert, wird ein Backup angelegt.',
    'addons_install_choose'       => 'ZIP-Datei wählen',
    'addons_install_btn'          => 'Installieren',
    'addons_install_hint'         => 'Die ZIP-Datei kann die addon.json direkt im Root oder in einem Unterverzeichnis enthalten (wie bei GitHub-Archiven). Max. 7 MB.',
    'addons_install_success'      => 'Addon „{name}" v{version} erfolgreich installiert.',
    'addons_install_backup_created' => '(Backup des vorherigen Addons wurde angelegt)',
    'addons_install_not_zip'     => 'Bitte eine .zip-Datei hochladen.',
    'addons_install_too_big'     => 'Datei zu groß. Maximum: {max}.',
    'addons_install_upload_error' => 'Upload-Fehler: {error}',
    'addons_install_err_no_file'   => 'Keine Datei gefunden.',
    'addons_install_err_invalid_manifest' => 'Die addon.json ist ungültig oder enthält keinen Namen.',
    'addons_install_err_invalid_name' => 'Ungültiger Addon-Name in addon.json: {name}',
    'addons_install_err_generic'   => 'Installationsfehler: {error}',

    // ── Einstellungen: Token-Status, Diagnose, Update-Check-Ergebnisse ────────
    'addons_token_status_active' => 'Token aktiv — API-Limit: 5.000 Requests/Std.',
    'addons_token_status_none' => 'Kein Token gesetzt — API-Limit: 60 Requests/Std.',
    'addons_token_step1' => '1. <a href="https://github.com/settings/tokens" target="_blank" rel="noopener" style="color:var(--accent)">GitHub → Settings → Developer settings → Personal access tokens</a>',
    'addons_token_step2' => '2. <strong>Generate new token (classic)</strong> wählen',
    'addons_token_step3' => '3. Scope <code style="background:var(--surface2);padding:1px 4px;border-radius:3px">public_repo</code> anhaken (reicht für öffentliche Repos)',
    'addons_token_step4' => '4. Token kopieren und hier einfügen',
    'addons_token_step5' => '5. <strong>Speichern</strong> — der Token wird in der Datenbank abgelegt.',
    'addons_diag_title' => 'Diagnose',
    'addons_diag_on' => 'Ein',
    'addons_diag_off' => 'Aus',
    'addons_diag_note_fopen' => 'file_get_contents für HTTP-Calls',
    'addons_diag_curl' => 'cURL-Erweiterung',
    'addons_diag_available' => 'Verfügbar',
    'addons_diag_missing' => 'Fehlt',
    'addons_diag_note_curl' => 'Fallback für HTTP-Calls',
    'addons_diag_note_zip' => 'für Auto-Update-Installation',
    'addons_diag_token' => 'GitHub Token',
    'addons_diag_token_invalid' => 'Ungültig (HTTP 401)',
    'addons_diag_token_invalid_note' => 'Token abgelaufen/widerrufen - Prüfung läuft anonym (60 Req/Std). Neuen Token speichern.',
    'addons_diag_token_set' => 'Gesetzt',
    'addons_diag_token_set_note' => '5.000 Req/Std',
    'addons_diag_token_unset' => 'Nicht gesetzt',
    'addons_diag_token_unset_note' => '60 Req/Std',
    'addons_diag_cache' => 'Update-Cache',
    'addons_diag_cache_age' => '{min} Min her',
    'addons_diag_cache_ttl' => 'TTL 60 Min',
    'addons_diag_cache_none' => 'Kein Cache vorhanden',
    'addons_diag_php' => 'PHP-Version',
    'addons_res_title' => 'Update-Check-Ergebnisse',
    'addons_res_col_current' => 'Aktuell',
    'addons_res_col_latest' => 'Neueste',
    'addons_res_col_method' => 'Methode',
    'addons_res_update' => 'Update verfügbar',
    'addons_res_current' => 'aktuell',
    'addons_res_manual' => 'manuell (Webseite)',
    'addons_res_no_repo' => 'kein GitHub-Repo',
    'addons_res_no_release' => 'kein Release',
    'addons_res_rate' => 'Rate-Limit!',
    'addons_res_fetch_error' => 'Fetch-Fehler (HTTP {code})',
    'addons_res_no_client' => 'kein HTTP-Client',
    'addons_res_none_yet' => 'Noch kein Update-Check ausgeführt. Klicke oben auf "Updates prüfen".',
    'addons_err_fetch_failed' => 'GitHub nicht erreichbar',
    'addons_err_rate_limited' => 'GitHub Rate-Limit (60 req/h — später erneut)',
    'addons_err_no_release' => 'Kein Release auf GitHub (404)',
    'addons_err_no_http_client' => 'Keine HTTP-Lib (file_get_contents/curl)',
];