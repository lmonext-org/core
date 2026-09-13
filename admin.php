<?php
/**
 * Project: LMOnext
 * Filename: admin.php
 * Fileversion: 1.6.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

// ── Pfad zu den Include-Dateien ───────────────────────────────────────────────
define('ADMIN_INC', __DIR__ . '/admin');
define('ADDON_INC', __DIR__ . '/addon');

// ── Bootstrap: Config, DB, Hilfsfunktionen ───────────────────────────────────
require_once ADMIN_INC . '/bootstrap.php';  // inkl. session_start()
require_once ADMIN_INC . '/templates.php';
require_once ADMIN_INC . '/league-key_data.php';
require_once __DIR__ . '/src/Addon/AddonManager.php';

// ── Aktion ────────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

// ── AddonManager initialisieren (Admin) ──────────────────────────────────────
// Entdeckt addon/{name}/addon.json, laedt admin_handlers der aktivierten
// Addons automatisch (ersetzt die frueheren festen require_once-Zeilen).
global $action;
$addonManager = new \LMOnext\Addon\AddonManager(ADDON_INC, getDB(), DB_PREFIX);
addonManager($addonManager);
$addonManager->bootAdmin();

// ── CSRF-Schutz: zentral für JEDEN POST-Request, bevor irgendein Handler läuft.
// Schützt alle 30+ POST-Aktionen auf einen Schlag (Liga speichern, Ergebnisse,
// Backup, Import, Wartungsmodus etc.), ohne dass jede einzeln abgesichert werden
// muss. Bricht bei fehlendem/falschem Token sofort mit 403 ab.
requireCsrf();

// ── POST-Handler (laufen vor HTML-Ausgabe) ────────────────────────────────────
require_once ADMIN_INC . '/handler_user.php';
require_once ADMIN_INC . '/handler_settings.php';
require_once ADMIN_INC . '/handler_ko.php';            // koHeimatTeamA() muss vor import_export bekannt sein
require_once ADMIN_INC . '/handler_import_export.php';
require_once ADMIN_INC . '/handler_wizard.php';
require_once ADMIN_INC . '/handler_export.php';
require_once ADMIN_INC . '/handler_liga.php';
require_once ADMIN_INC . '/handler_backup.php';
// Addon-POST-Handler (z.B. handler_spielerstat.php, handler_tipp.php) werden
// automatisch ueber bootAdmin() der aktivierten Addons geladen (siehe oben).

// ── Daten laden (vor HTML-Ausgabe) ───────────────────────────────────────────
require_once ADMIN_INC . '/data_loader.php';

// ── HTML-Kopf: CSS, Meta, Flash-Funktion ─────────────────────────────────────
require_once ADMIN_INC . '/html_start.php';

// ── Login-Seite: kein Sidebar-Layout ─────────────────────────────────────────
if ($action === 'login') {
    require ADMIN_INC . '/view_login.php';
    exit;
}

// ── Passwort-Reset-Landingpage (aus der E-Mail erreicht): ebenfalls kein
// Sidebar-Layout, da hier noch nicht eingeloggt ─────────────────────────────
if ($action === 'reset_password') {
    require ADMIN_INC . '/view_reset_password.php';
    exit;
}

// ── Sidebar + Main-Wrapper für alle anderen Views ────────────────────────────
require ADMIN_INC . '/html_layout.php';

// ── Views ─────────────────────────────────────────────────────────────────────
// Addons registrieren eigene Admin-Views ueber addon.json (admin_views).
// Wird zuerst geprueft, damit Addon-Views wie tippspiel/spielerstatistik
// weiterhin funktionieren, sobald das jeweilige Addon aktiviert ist.
$addonView = addonManager()->getAdminView($action);
if ($addonView !== null) {
    require $addonView;

} elseif ($action === 'create_liga') {
    require ADMIN_INC . '/view_wizard.php';

} elseif ($action === 'liga_detail' && $ligaDetail) {
    require ADMIN_INC . '/view_liga_detail.php';

} elseif ($action === 'spieltag' && $spieltagData) {
    require ADMIN_INC . '/view_spieltag.php';

} elseif ($action === 'tabelle' && $tabelleData) {
    require ADMIN_INC . '/view_tabelle.php';

} elseif ($action === 'archiv') {
    require ADMIN_INC . '/view_archiv.php';

} elseif ($action === 'teams') {
    require ADMIN_INC . '/view_teams.php';

} elseif ($action === 'import') {
    require ADMIN_INC . '/view_import.php';

} elseif ($action === 'import_review') {
    require ADMIN_INC . '/view_import_review.php';

} elseif ($action === 'users') {
    require ADMIN_INC . '/view_users.php';

} elseif ($action === 'liga_settings' && $ligaSettingsData) {
    require ADMIN_INC . '/view_liga_settings.php';

} elseif ($action === 'settings') {
    require ADMIN_INC . '/view_settings.php';

} elseif ($action === 'wartung') {
    require ADMIN_INC . '/view_wartung.php';

} else {
    // Fallback + Dashboard
    require ADMIN_INC . '/view_liga_list.php';
}

doHook('admin.footer', ['action' => $action]);

?>
  </div><!-- /#content -->
  </div><!-- /.main -->
</div><!-- /.app-layout -->
</body>
</html>
