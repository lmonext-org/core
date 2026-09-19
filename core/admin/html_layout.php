<?php
/**
 * Project: LMOnext
 * Filename: html_layout.php
 * Fileversion: 1.6.0
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
<nav class="sidebar">
  <div class="sidebar-logo"><img src="assets/logo.svg" alt="LMOnext" style="height:34px;width:auto;display:block"></div>
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

<div class="main">
  <div class="topbar">
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
