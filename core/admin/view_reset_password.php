<?php
/**
 * Project: LMOnext
 * Filename: view_reset_password.php
 * Fileversion: 1.0.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Neues Passwort setzen (von der Reset-E-Mail aus erreicht) ─────────
ensurePasswordResetSchema();

$token   = (string)($_GET['token'] ?? '');
$isValid = false;
if ($token !== '') {
    try {
        $s = getDB()->prepare('SELECT 1 FROM '.tbl('admin_password_resets').' WHERE token=? AND expires_at >= NOW()');
        $s->execute([$token]);
        $isValid = (bool)$s->fetchColumn();
    } catch (Throwable) {
        $isValid = false;
    }
}
?>
<div class="login-wrap">
  <div class="login-box">
    <div style="background:#ffffff;border-radius:12px;padding:14px 20px;display:table;margin:0 auto 16px auto;">
      <img src="assets/logo.svg" alt="<?= h(ADMIN_TITLE) ?>" style="height:60px;width:auto;display:block">
    </div>
    <?= renderFlash($flash ?? null) ?>
<?php if ($isValid) { ?>
    <p><?= h(t('reset_pw_subtitle')) ?></p>
    <form method="post" action="?action=do_reset_password">
      <input type="hidden" name="token" value="<?= h($token) ?>">
      <div class="form-group">
        <label><?= h(t('reset_pw_label_new')) ?></label>
        <input type="password" name="new_password" placeholder="<?= h(t('install_placeholder_pass')) ?>" required autocomplete="new-password" autofocus>
      </div>
      <div class="form-group">
        <label><?= h(t('reset_pw_label_new2')) ?></label>
        <input type="password" name="new_password2" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px"><?= h(t('reset_pw_submit')) ?></button>
    <?= csrfField() ?></form>
<?php } else { ?>
    <p><?= h(t('reset_pw_invalid')) ?></p>
<?php } ?>
    <div style="text-align:center;margin-top:16px">
      <a href="?action=login" style="color:var(--muted);font-size:.85rem;text-decoration:none"><?= h(t('reset_pw_back_to_login')) ?></a>
    </div>
  </div>
</div>
