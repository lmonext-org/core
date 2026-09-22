<?php
/**
 * Project: LMOnext
 * Filename: view_users.php
 * Fileversion: 1.5.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Users (Administrator: Userverwaltung + Log) ──────────────────────
$adminMainTab = ($_GET['tab'] ?? 'userverwaltung') === 'log' ? 'log' : 'userverwaltung';
?>
      <!-- Haupt-Tab-Navigation -->
      <div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
<?php
$adminMainTabs = ['userverwaltung' => t('usr_tab_userverwaltung'), 'log' => t('usr_tab_log')];
foreach ($adminMainTabs as $key => $label) {
    $active = $key === $adminMainTab; ?>
        <a href="?action=users&tab=<?= $key ?>"
           style="padding:8px 16px;font-size:.83rem;text-decoration:none;
                  border-radius:var(--radius) var(--radius) 0 0;
                  background:<?= $active ? 'var(--surface)' : 'var(--surface2)' ?>;
                  border:1px solid var(--border);
                  border-bottom:<?= $active ? '1px solid var(--surface)' : '1px solid var(--border)' ?>;
                  color:<?= $active ? 'var(--accent)' : 'var(--muted)' ?>;
                  font-weight:<?= $active ? '600' : '400' ?>;margin-right:3px"><?= h($label) ?></a>
<?php } ?>
      </div>
      <div style="border:1px solid var(--border);border-top:none;border-radius:0 var(--radius) var(--radius) var(--radius);
                  background:var(--surface);padding:16px;margin-bottom:16px">
<?php if ($adminMainTab === 'userverwaltung') { ?>
      <div class="card" style="margin:0">
        <h2><?= h(t('usr_heading_create')) ?></h2>
        <form method="post" action="?action=create_user">
          <div class="form-row">
            <div class="form-group"><label><?= h(t('login_username')) ?></label><input type="text" name="new_username" autocomplete="off"></div>
            <div class="form-group"><label><?= h(t('usr_label_password_min8')) ?></label><input type="password" name="new_user_password" autocomplete="new-password"></div>
            <div class="form-group"><label><?= h(t('install_label_email')) ?></label><input type="email" name="new_user_email" autocomplete="email"></div>
          </div>
          <button type="submit" class="btn btn-primary"><?= h(t('usr_btn_create')) ?></button>
        <?= csrfField() ?></form>
      </div>
      <div class="card" style="margin:16px 0 0">
        <h2><?= h(t('usr_heading_existing')) ?></h2>
<?php
        if (empty($users)) { ?><p class="text-muted" style="font-size:.9rem"><?= h(t('usr_empty')) ?></p><?php } else { ?>
          <table class="tbl">
            <thead><tr><th><?= h(t('dash_col_id')) ?></th><th><?= h(t('login_username')) ?></th><th><?= h(t('install_label_email')) ?></th><th><?= h(t('usr_col_last_login')) ?></th><th><?= h(t('dash_col_actions')) ?></th></tr></thead>
            <tbody>
<?php
            foreach ($users as $u) {
                $isMe = $u['username'] === ($_SESSION['admin_user'] ?? ''); ?>
              <tr>
                <td class="text-muted" style="width:40px"><?= (int)$u['id'] ?></td>
                <td>
                  <?= h($u['username']) ?>
                  <?php if ($isMe) { ?><span class="chip chip-green" style="margin-left:6px"><?= h(t('usr_chip_me')) ?></span><?php } ?>
                </td>
                <td class="text-muted" style="font-size:.85rem"><?= !empty($u['email']) ? h($u['email']) : '—' ?></td>
                <td class="text-muted" style="font-size:.82rem;white-space:nowrap">
                  <?php if (!empty($u['last_login'])) {
                      $dt = new DateTime($u['last_login']);
                      echo $dt->format('d.m.Y H:i');
                  } else { echo '—'; } ?>
                </td>
                <td style="width:180px">
                  <button type="button" class="btn btn-muted btn-sm"
                          onclick="toggleEdit(<?= (int)$u['id'] ?>)"><?= h(t('usr_btn_edit')) ?></button>
<?php
                if (!$isMe) { ?>
                  <form method="post" action="?action=delete_user" style="display:inline;margin-left:4px"
                        onsubmit="return confirm('<?= h(addslashes(t('usr_confirm_delete', ['name' => $u['username']]))) ?>')">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><?= h(t('dash_btn_delete')) ?></button>
                  <?= csrfField() ?></form>
<?php
                } ?>
                </td>
              </tr>
              <!-- Inline-Bearbeiten-Formular -->
              <tr id="edit_row_<?= (int)$u['id'] ?>" style="display:none">
                <td></td>
                <td colspan="4">
                  <form method="post" action="?action=edit_user"
                        style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('login_username')) ?></label>
                      <input type="text" name="edit_username" value="<?= h($u['username']) ?>"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem" required>
                    </div>
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('install_label_email')) ?></label>
                      <input type="email" name="edit_email" value="<?= h($u['email'] ?? '') ?>" autocomplete="email"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem">
                    </div>
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('usr_label_new_password')) ?> <span style="color:var(--muted)"><?= h(t('usr_hint_empty_unchanged')) ?></span></label>
                      <input type="password" name="edit_password" autocomplete="new-password"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem">
                    </div>
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('install_label_password2')) ?></label>
                      <input type="password" name="edit_password2" autocomplete="new-password"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem">
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;grid-column:1/-1">
                      <button type="submit" class="btn btn-primary btn-sm"><?= h(t('usr_btn_save')) ?></button>
                      <button type="button" class="btn btn-muted btn-sm" onclick="toggleEdit(<?= (int)$u['id'] ?>)"><?= h(t('common_cancel')) ?></button>
                    </div>
                  <?= csrfField() ?></form>
                </td>
              </tr>
<?php
            } ?>
            </tbody>
          </table>
          <script>
          function toggleEdit(id) {
            const row = document.getElementById('edit_row_' + id);
            row.style.display = row.style.display === 'none' ? '' : 'none';
          }
          </script>
<?php } ?>
      </div>
<?php } else { ?>
      <div class="card" style="margin:0">
        <h2><?= h(t('usr_heading_log')) ?></h2>
<?php if (empty($auditLog)) { ?>
        <p class="text-muted" style="font-size:.9rem"><?= h(t('usr_log_empty')) ?></p>
<?php } else { ?>
        <table class="tbl">
          <thead>
            <tr>
              <th><?= h(t('usr_log_col_time')) ?></th>
              <th><?= h(t('usr_log_col_user')) ?></th>
              <th><?= h(t('usr_log_col_action')) ?></th>
              <th><?= h(t('usr_log_col_details')) ?></th>
              <th><?= h(t('usr_log_col_ip')) ?></th>
            </tr>
          </thead>
          <tbody>
<?php foreach ($auditLog as $entry) {
    $dt = new DateTime($entry['created_at']); ?>
            <tr>
              <td class="text-muted" style="font-size:.82rem;white-space:nowrap"><?= h($dt->format('d.m.Y H:i:s')) ?></td>
              <td><?= h($entry['username']) ?></td>
              <td><span class="chip" style="font-size:.75rem"><?= h(t('usr_log_action_' . $entry['action']) !== 'usr_log_action_' . $entry['action'] ? t('usr_log_action_' . $entry['action']) : $entry['action']) ?></span></td>
              <td class="text-muted" style="font-size:.85rem"><?= $entry['details'] !== null ? h($entry['details']) : '—' ?></td>
              <td class="text-muted" style="font-size:.8rem"><?= h($entry['ip']) ?></td>
            </tr>
<?php } ?>
          </tbody>
        </table>
<?php
        $logTotalPages = max(1, (int)ceil(($logTotal ?? 0) / 50));
        if ($logTotalPages > 1) { ?>
        <div style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap">
<?php   for ($p = 1; $p <= $logTotalPages; $p++) { ?>
          <a href="?action=users&tab=log&logpage=<?= $p ?>"
             class="btn btn-sm <?= $p === $logPage ? 'btn-primary' : 'btn-muted' ?>"><?= $p ?></a>
<?php   } ?>
        </div>
<?php } ?>
<?php } ?>
      </div>
      <div class="card" style="margin:16px 0 0">
        <h2><?= h(t('usr_heading_php_log')) ?></h2>
<?php
        $phpIssues = readPhpIssueLog(100);
        if (empty($phpIssues)) { ?>
        <p class="text-muted" style="font-size:.9rem"><?= h(t('usr_php_log_empty')) ?></p>
<?php } else { ?>
        <p class="text-muted" style="font-size:.78rem;margin-bottom:10px"><?= h(t('usr_php_log_hint', ['n' => count($phpIssues)])) ?></p>
        <div style="max-height:420px;overflow-y:auto;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:10px 12px">
<?php foreach ($phpIssues as $line) {
    $levelColor = 'var(--muted)';
    if (str_contains($line, '[FATAL]') || str_contains($line, '[ERROR]')) { $levelColor = 'var(--red)'; }
    elseif (str_contains($line, '[WARNING]')) { $levelColor = '#e0a030'; } ?>
          <div style="font-family:monospace;font-size:.78rem;color:<?= $levelColor ?>;padding:3px 0;border-bottom:1px solid var(--border);word-break:break-word"><?= h($line) ?></div>
<?php } ?>
        </div>
<?php } ?>
      </div>
<?php } ?>
      </div>

