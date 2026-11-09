<?php
/**
 * Project: LMOnext
 * Filename: html_layout.php
 * Fileversion: 1.11.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Sidebar + Main-Layout (für alle Views außer Login) ───────────────────────
?>
<div class="nav-backdrop" id="navBackdrop"></div>
<nav class="sidebar" id="adminNav">
  <div class="sidebar-head"><span><?= h(t('nav_menu_title')) ?></span><button type="button" class="nav-close" id="navClose" aria-label="<?= h(t('nav_menu_close')) ?>">✕</button></div>
  <?php $coreUpdate = function_exists('checkCoreUpdateAvailable') ? checkCoreUpdateAvailable() : null; ?>
  <?php if ($coreUpdate !== null) {
    // Der Banner verlinkt jetzt bevorzugt auf die Forenankündigung
    // (announcement), die das Update beschreibt, statt auf die
    // reine Download-Datei - deutlich hilfreicher für den Admin.
    // Fällt zurück auf download, falls announcement leer ist
    // (ältere check_version.json ohne dieses Feld).
    $updateLink = ($coreUpdate['announcement'] ?? '') !== '' ? $coreUpdate['announcement'] : $coreUpdate['download'];
  ?>
  <a href="<?= h($updateLink) ?>" target="_blank" rel="noopener"
     class="sidebar-update-notice" title="<?= h(t('nav_update_tooltip')) ?>">
    🔔 <?= h(t('nav_update_available', ['version' => $coreUpdate['version']])) ?>
  </a>
  <?php } ?>
  <ul class="nav-list">
<?php foreach ($nav as $key => $item) { ?>
    <li>
      <a href="?action=<?= $key ?><?= $key === 'create_liga' ? '&step=1' : '' ?>"
         class="<?= ($action === $key || ($action === 'liga_detail' && $key === 'dashboard')) ? 'active' : '' ?>">
        <?= $item['icon'] ?> <?= h($item['label']) ?>
      </a>
    </li>
<?php } ?>
  </ul>
  <div class="sidebar-footer">
    <span style="font-size:.82rem;color:var(--muted)"><?= h($_SESSION['admin_user'] ?? '') ?></span>
    <div style="font-size:.7rem;color:var(--muted);margin-top:4px"><?= renderCopyrightNotice() ?></div>
  </div>
</nav>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var b = document.body, t = document.getElementById('navToggle');
  function set(o) { b.classList.toggle('nav-open', o); if (t) { t.setAttribute('aria-expanded', o ? 'true' : 'false'); } }
  if (t) { t.addEventListener('click', function () { set(!b.classList.contains('nav-open')); }); }
  var c = document.getElementById('navClose'), bd = document.getElementById('navBackdrop');
  if (c) { c.addEventListener('click', function () { set(false); }); }
  if (bd) { bd.addEventListener('click', function () { set(false); }); }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { set(false); } });
});
</script>

<div class="main">
  <div class="topbar">
    <div class="topbar-brand">
      <img src="assets/logo.svg" alt="LMOnext" style="height:34px;width:auto;display:block">
      <button type="button" class="nav-toggle" id="navToggle" aria-controls="adminNav" aria-expanded="false" aria-label="<?= h(t('nav_menu_open')) ?>">☰ <?= h(t('nav_menu_title')) ?></button>
    </div>
    <h1><?= h($pageTitle) ?></h1>
    <div style="display:flex;align-items:center;gap:12px">
      <?= renderLanguageSwitcher() ?>
      <span class="badge"><?= date('d.m.Y H:i') ?></span>
      <span style="font-size:.82rem;color:var(--muted)"><?= h($_SESSION['admin_user'] ?? '') ?></span>
      <a href="home.php" target="_blank" rel="noopener" class="btn btn-muted btn-sm" style="text-decoration:none"><?= h(t('topbar_visitor_link')) ?></a>
      <a href="?action=logout" class="btn btn-danger btn-sm" style="text-decoration:none">⏻ <?= h(t('topbar_logout')) ?></a>
    </div>
  </div>
  <div class="content">
    <?= renderFlash($flash ?? null) ?>
    <?php
    // Wird eine bestehende Installation per FTP aktualisiert
    // und dabei die install.php aus dem Update-Paket mit hochgeladen, blieb bisher
    // jeder Hinweis darauf aus. install.php sperrt sich zwar selbst (403-Seite,
    // sobald config.php existiert), gehört auf einer LIVE-Installation aber
    // trotzdem gelöscht - Restrisiko und unnötige Angriffsfläche vermeiden.
    if (is_file(dirname(__DIR__) . '/install.php')) { ?>
    <div class="flash error" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <span>⚠️ <?= t('install_leftover_warning') ?></span>
      <form method="post" action="?action=delete_install_php" style="margin:0"
            onsubmit="return confirm('<?= h(t('install_delete_confirm')) ?>')">
        <?= csrfField() ?>
        <button type="submit" class="btn btn-danger btn-sm">🗑️ <?= h(t('install_delete_btn')) ?></button>
      </form>
    </div>
    <?php } ?>
