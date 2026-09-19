<?php
/**
 * Project: LMOnext
 * Filename: view_login.php
 * Fileversion: 1.4.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Login-Seite ───────────────────────────────────────────────────────────────
?>
<div class="login-wrap">
  <div class="login-box">
    <div style="background:#ffffff;border-radius:12px;padding:14px 20px;display:table;margin:0 auto 16px auto;">
      <img src="assets/logo.svg" alt="<?= h(ADMIN_TITLE) ?>" style="height:60px;width:auto;display:block">
    </div>
    <div style="text-align:center;margin-bottom:16px"><?= renderLanguageSwitcher() ?></div>
    <p><?= h(t('login_subtitle')) ?></p>
    <?= renderFlash($flash ?? null) ?>
    <form method="post" action="?action=login">
      <div class="form-group">
        <label><?= h(t('login_username')) ?></label>
        <input type="text" name="username" autofocus autocomplete="username">
      </div>
      <div class="form-group">
        <label><?= h(t('login_password')) ?></label>
        <input type="password" name="password" autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px"><?= h(t('login_submit')) ?></button>
    <?= csrfField() ?></form>
    <div style="text-align:center;margin-top:12px">
      <a href="#" onclick="document.getElementById('forgot-modal').style.display='flex';return false;"
         style="color:var(--muted);font-size:.85rem;text-decoration:none"><?= h(t('login_forgot_link')) ?></a>
    </div>
    <div style="text-align:center;margin-top:16px">
      <a href="home.php" style="color:var(--muted);font-size:.85rem;text-decoration:none"><?= h(t('login_visitor_link')) ?></a>
    </div>
  </div>
</div>

<!-- "Passwort vergessen"-Modal -->
<div id="forgot-modal" style="display:none;position:fixed;inset:0;background:#000a;z-index:9999;
                               align-items:center;justify-content:center">
  <div style="background:var(--surface);border-radius:var(--radius);padding:24px;max-width:380px;width:calc(100% - 32px)">
    <h3 style="margin-top:0"><?= h(t('reset_modal_title')) ?></h3>
    <p style="color:var(--muted);font-size:.85rem"><?= h(t('reset_modal_intro')) ?></p>
    <form method="post" action="?action=request_password_reset">
      <div class="form-group">
        <label><?= h(t('reset_modal_label_email')) ?></label>
        <input type="email" name="reset_email" required autocomplete="email" autofocus>
      </div>
      <div style="display:flex;gap:8px;margin-top:14px">
        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><?= h(t('reset_modal_submit')) ?></button>
        <button type="button" class="btn btn-muted" onclick="document.getElementById('forgot-modal').style.display='none'"><?= h(t('reset_modal_cancel')) ?></button>
      </div>
    <?= csrfField() ?></form>
  </div>
</div>
