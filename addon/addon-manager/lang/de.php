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
    'addons_empty_hint'        => 'Lege Addons im Verzeichnis addon/ ab (mit addon.json Manifest).',

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
];