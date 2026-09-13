<?php
/**
 * Project: LMOnext
 * Filename: lang/admin/addons_en.php
 * Language variables for addon management
 *
 * @license   GPL-3.0-only
 */

return [
    // ── Navigation ────────────────────────────────────────────────────────────
    'nav_addons'               => 'Addons',

    // ── Stat cards ───────────────────────────────────────────────────────────
    'addons_stat_total'        => 'Total Addons',
    'addons_stat_enabled'      => 'Enabled',
    'addons_stat_disabled'     => 'Disabled',

    // ── Tabs ───────────────────────────────────────────────────────────────────
    'addons_tab_all'           => 'All',
    'addons_tab_admin'        => 'Admin',
    'addons_tab_frontend'     => 'Frontend',
    'addons_tab_both'         => 'Admin + Frontend',
    'addons_tab_standalone'   => 'Standalone',
    'addons_tab_settings'   => 'Settings',

    // ── Table ────────────────────────────────────────────────────────────────
    'addons_col_addon'         => 'Addon',
    'addons_col_type'          => 'Type',
    'addons_col_version'       => 'Version',
    'addons_col_description'   => 'Description',
    'addons_col_status'        => 'Status',
    'addons_col_actions'       => 'Action',

    // ── Status ──────────────────────────────────────────────────────────────────
    'addons_status_active'     => 'Active',
    'addons_status_inactive'   => 'Inactive',

    // ── Buttons ────────────────────────────────────────────────────────────────
    'addons_btn_enable'        => 'Enable',
    'addons_btn_disable'       => 'Disable',

    // ── Confirmations ──────────────────────────────────────────────────────────
    'addons_confirm_disable'   => 'Really disable addon "{name}"? Frontend and admin functions will no longer be available.',

    // ── Flash messages ─────────────────────────────────────────────────────────
    'addons_flash_enabled'     => 'Addon "{name}" has been enabled.',
    'addons_flash_disabled'    => 'Addon "{name}" has been disabled.',
    'addons_flash_enable_error'=> 'Could not enable addon "{name}": {error}',
    'addons_flash_disable_error'=> 'Could not disable addon "{name}": {error}',

    // ── Errors ──────────────────────────────────────────────────────────────────
    'addons_err_invalid_action'=> 'Invalid action.',
    'addons_err_invalid_name'  => 'Invalid addon name.',
    'addons_err_not_found'    => 'Addon not found.',
    'addons_err_dep_missing'   => 'Missing dependencies: {deps}',
    'addons_err_core_version'  => 'Core version too low. Required: {need}, current: {have}',
    'addons_install_err_unsafe_path'         => 'Security check failed: the ZIP contains a disallowed file path (path traversal). Installation rejected.',
    'addons_install_err_disallowed_filetype' => 'Security check failed: the ZIP contains a disallowed file type. Allowed: php, json, md, txt, png, jpg, jpeg, gif, svg, ico, webp, css, js.',
    'addons_install_err_lint_failed'         => 'Security check failed: the file "{file}" is not valid PHP code. Installation rejected.',
    'addons_install_err_dangerous_pattern'   => 'Security check failed: the file "{file}" contains a suspicious function call (e.g. shell execution or dynamic code execution). Installation rejected. If this is a false positive for a legitimate add-on, contact the add-on author.',
    'addons_install_err_copy_failed'         => 'The new files could not be fully installed (e.g. because a file was currently in use). Nothing was changed - please try again.',
    'addons_demo_mode_blocked' => 'This action is disabled on this demo instance. Enabling/disabling already installed add-ons still works normally.',
    'addons_demo_mode_notice'  => 'On this demo instance, installing/updating add-on code is disabled. Enabling/disabling already installed add-ons is still possible.',
    'addons_purge_btn'           => '🗑️ Delete data',
    'addons_purge_confirm'       => 'WARNING: All data of the add-on "{name}" will be PERMANENTLY deleted from the database (tables: {tables}). The add-on code itself stays intact and can be re-enabled at any time - but then with empty tables. Really continue?',
    'addons_purge_hint'          => 'Permanently deletes this disabled add-on\'s own database tables. The add-on code itself stays intact.',
    'addons_purge_err_still_enabled' => 'Add-on "{name}" is still enabled - please disable it first before deleting its data.',
    'addons_purge_err_no_tables'     => 'Add-on "{name}" has no own database tables declared - nothing to delete.',
    'addons_purge_flash_success'     => 'Data of "{name}" deleted ({n} table(s): {tables}).',
    'addons_purge_flash_error'       => 'Deleting the data of "{name}" failed.',
    'addons_err_depended_on'   => 'Cannot disable addon "{addon}" because enabled addon "{by}" depends on it.',

    // ── Empty state ─────────────────────────────────────────────────────────────
    'addons_empty'             => 'No addons found.',
    'addons_empty_hint'        => 'Place addons in the addon/ directory (with addon.json manifest).',

    // ── Meta info ───────────────────────────────────────────────────────────────
    'addons_min_core'          => 'Minimum core version',
    'addons_db_tables'         => 'Database tables',
    'addons_tables_short'      => 'tables',
    'addons_depends_on'        => 'Depends on',
    'addons_dep_missing'       => 'Dependencies missing — cannot enable',

    // ── Info box ─────────────────────────────────────────────────────────────────
    'addons_info_title'        => 'How do addons work?',
    'addons_info_text'         => 'Addons are automatically discovered when they contain an addon.json manifest in the addon/ directory. Each addon is self-contained and can be maintained in a separate GitHub repo. When enabled, handlers and views are automatically registered; when disabled, they are removed from the router without deleting files.',

    // ── GitHub Update Check ─────────────────────────────────────────────────────
    'addons_btn_check_updates'  => 'Check for updates',
    'addons_updates_available'  => '{count} update(s) available — see version column.',
    'addons_no_updates'         => 'All addons are up to date.',
    'addons_updates_badge_text'  => 'Update(s) available — check now:',
    'addons_update_tooltip'     => 'Version {latest} available — install update automatically',
    'addons_update_btn'          => 'Install update',
    'addons_update_confirm'      => 'Install update for "{name}" to version {latest}? Current files will be backed up as a ZIP first.',
    'addons_update_installed'    => '"{name}" successfully updated from v{from} to v{to}. Backup created.',
    'addons_update_err_no_github'      => 'Update failed: no GitHub repo configured in the homepage URL.',
    'addons_update_err_rate_limited'    => 'Update failed: GitHub rate limit reached. Try again later or add a GitHub token.',
    'addons_update_err_no_release'      => 'Update failed: no release found on GitHub.',
    'addons_update_err_fetch_failed'    => 'Update failed: GitHub unreachable.',
    'addons_update_err_already_current'=> '"{name}" is already up to date.',
    'addons_update_err_zip_missing'    => 'Update failed: PHP ZipArchive extension missing on this server.',
    'addons_update_err_download_failed'=> 'Update failed: release download from GitHub failed.',
    'addons_update_err_zip_failed'      => 'Update failed: downloaded ZIP could not be opened.',
    'addons_update_err_zip_structure'  => 'Update failed: unexpected ZIP structure in release.',
    'addons_update_err_no_manifest'    => 'Update failed: no addon.json found in release.',
    'addons_update_err_name_mismatch'  => 'Update failed: addon name in release does not match local addon (safety check).',
    'addons_update_err_generic'        => 'Update failed: {error}',
    'addons_update_error'       => 'Update check failed (GitHub unreachable or no release)',
    'addons_updates_all_failed'  => 'Update check failed — GitHub unreachable. Please check server connection.',
    'addons_updates_some_failed'  => 'Checked {checked} addons, {errors} errors (GitHub unreachable). No updates found.',

    // GitHub Token
    'addons_token_title'         => 'GitHub Token (optional)',
    'addons_token_desc'          => 'A Personal Access Token increases the rate limit from 60 to 5,000 requests/hour. Only "public_repo" scope needed. GitHub Settings → Developer settings → Personal access tokens.',
    'addons_token_btn_save'      => 'Save token',
    'addons_token_btn_delete'     => 'Delete token',
    'addons_token_confirm_delete'=> 'Really delete the GitHub token? Update checks will revert to the 60/hr limit.',
    'addons_token_saved'         => 'GitHub token saved. Update checks now use 5,000 requests/hour.',
    'addons_token_deleted'       => 'GitHub token deleted. Update checks revert to standard limit (60/hr).',
    'addons_token_invalid'       => 'Invalid token format. A GitHub token consists of at least 16 characters (letters, digits, underscore).',
    'addons_token_help'          => 'Create token: GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic) → Generate new token. Scope "public_repo" is sufficient. Free.',
// ── ZIP-Installation ──────────────────────────────────────────────────
    'addons_install_title'        => 'Install Addon via ZIP',
    'addons_install_desc'         => 'Upload a ZIP file containing an addon.json. The addon will be automatically installed to addon/. If it already exists, a backup is created.',
    'addons_install_choose'       => 'Choose ZIP file',
    'addons_install_btn'          => 'Install',
    'addons_install_hint'         => 'The ZIP can contain addon.json in the root or in a subdirectory (like GitHub archives). Max 7 MB.',
    'addons_install_success'      => 'Addon "{name}" v{version} installed successfully.',
    'addons_install_backup_created' => '(A backup of the previous addon was created)',
    'addons_install_not_zip'     => 'Please upload a .zip file.',
    'addons_install_too_big'     => 'File too large. Maximum: {max}.',
    'addons_install_upload_error' => 'Upload error: {error}',
    'addons_install_err_no_file'   => 'No file found.',
    'addons_install_err_invalid_manifest' => 'The addon.json is invalid or missing a name.',
    'addons_install_err_invalid_name' => 'Invalid addon name in addon.json: {name}',
    'addons_install_err_generic'   => 'Installation error: {error}',
];