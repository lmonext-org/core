<?php
/**
 * Project: LMOnext
 * Filename: view_wartung.php
 * Fileversion: 1.3.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Wartung (Wartungsmodus/Backup/Wiederherstellung) ──────────────────
$tab = ($_GET['tab'] ?? 'wartung');
$tab = in_array($tab, ['wartung', 'backup', 'restore'], true) ? $tab : 'wartung';
$tabs = [
    'wartung' => t('wartung_tab_wartung'),
    'backup'  => t('wartung_tab_backup'),
    'restore' => t('wartung_tab_restore'),
];

$allTables    = backupAllTableNames();
$bzip2Ok      = backupBzip2Available();
$zipOk        = backupZipAvailable();
$backupMaxN   = (int)getAdminSetting('backup_max_count', '10');
$backupsList  = $tab === 'restore' ? backupList() : [];
$maintenanceMode = getAdminSetting('maintenance_mode', '0') === '1';

function wartungFormatSize(int $bytes) : string
{
    if ($bytes >= 1048576) { return round($bytes / 1048576, 1) . ' MB'; }
    if ($bytes >= 1024)    { return round($bytes / 1024, 1) . ' KB'; }
    return $bytes . ' B';
}
?>

      <!-- Tab-Navigation -->
      <div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
<?php foreach ($tabs as $key => $label) {
    $active = $key === $tab; ?>
        <a href="?action=wartung&tab=<?= $key ?>"
           style="padding:8px 16px;font-size:.83rem;text-decoration:none;
                  border-radius:var(--radius) var(--radius) 0 0;
                  background:<?= $active ? 'var(--surface)' : 'var(--surface2)' ?>;
                  border:1px solid var(--border);
                  border-bottom:<?= $active ? '1px solid var(--surface)' : '1px solid var(--border)' ?>;
                  color:<?= $active ? 'var(--accent)' : 'var(--muted)' ?>;
                  font-weight:<?= $active ? '600' : '400' ?>;margin-right:3px"><?= h($label) ?></a>
<?php } ?>
      </div>

      <div class="card" style="border-radius:0 var(--radius) var(--radius) var(--radius);margin-top:0;max-width:760px">

<?php if ($tab === 'wartung') { /* ── TAB: WARTUNGSMODUS ──────────────────── */ ?>

        <p style="color:var(--muted);font-size:.86rem;margin-top:0"><?= h(t('wartung_maintenance_intro')) ?></p>

        <form method="post" action="?action=save_maintenance_mode">
        <?= csrfField() ?>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
              <input type="checkbox" name="maintenance_mode" value="1"<?= $maintenanceMode ? ' checked' : '' ?>
                     style="width:18px;height:18px;accent-color:var(--accent);flex:none"
                     onchange="this.form.submit()">
              <span><?= h(t('wartung_maintenance_label')) ?></span>
            </label>
          </div>
          <p style="color:var(--muted);font-size:.78rem;margin-top:4px;margin-bottom:0">
            <?= h(t('wartung_maintenance_hint')) ?>
          </p>
        </form>

        <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--border)">
          <div style="display:flex;gap:8px;align-items:center">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                         background:<?= $maintenanceMode ? '#ef4444' : '#22c55e' ?>;flex:none"></span>
            <span style="font-size:.86rem;color:var(--text)">
              <?= $maintenanceMode ? h(t('wartung_maintenance_status_on')) : h(t('wartung_maintenance_status_off')) ?>
            </span>
          </div>
        </div>

<?php } elseif ($tab === 'backup') { ?>

        <p style="color:var(--muted);font-size:.86rem;margin-top:0"><?= h(t('wartung_backup_intro')) ?></p>
        <p style="color:var(--muted);font-size:.82rem;margin-top:-8px">
<?php if ($zipOk) { ?>
          <?= h(t('wartung_hint_logos_included')) ?>
<?php } else { ?>
          ⚠️ <?= h(t('wartung_hint_logos_unavailable')) ?>
<?php } ?>
        </p>

        <form method="post" action="?action=run_backup">
          <h3 style="margin-bottom:10px"><?= h(t('wartung_heading_backup_options')) ?></h3>

          <div class="form-group">
            <label><?= h(t('wartung_label_backup_type')) ?></label>
            <div style="display:flex;gap:18px;margin-top:6px;font-size:.87rem">
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="radio" name="backup_type" value="complete" checked><?= h(t('wartung_backup_type_complete')) ?>
              </label>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="radio" name="backup_type" value="data"><?= h(t('wartung_backup_type_data')) ?>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label><?= h(t('wartung_label_file_type')) ?></label>
            <div style="display:flex;gap:18px;margin-top:6px;font-size:.87rem">
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="radio" name="backup_format" value="gzip" checked>gzip
              </label>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer<?= $bzip2Ok ? '' : ';opacity:.4' ?>">
                <input type="radio" name="backup_format" value="bzip2"<?= $bzip2Ok ? '' : ' disabled' ?>>bzip2<?= $bzip2Ok ? '' : ' (' . h(t('wartung_format_unavailable')) . ')' ?>
              </label>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="radio" name="backup_format" value="text">text
              </label>
            </div>
          </div>

          <div class="form-group">
            <label><?= h(t('wartung_label_table_selection')) ?></label>
            <div style="width:100%;max-width:420px;margin-top:6px;background:var(--bg);border:1px solid var(--border);
                        border-radius:var(--radius);padding:4px;max-height:280px;overflow-y:auto;font-family:monospace">
<?php foreach ($allTables as $tn) { ?>
              <label style="display:flex;align-items:center;gap:8px;padding:5px 8px;font-size:.85rem;
                            color:var(--text);cursor:pointer;border-radius:4px">
                <input type="checkbox" name="tables[]" value="<?= h($tn) ?>" checked
                       style="accent-color:var(--accent);width:14px;height:14px;flex:none">
                <?= h(DB_PREFIX . $tn) ?>
              </label>
<?php } ?>
            </div>
            <div style="margin-top:6px;font-size:.82rem">
              <a href="#" onclick="wartungSelectAll(true);return false;"><?= h(t('wartung_select_all')) ?></a>
              &nbsp;::&nbsp;
              <a href="#" onclick="wartungSelectAll(false);return false;"><?= h(t('wartung_select_none')) ?></a>
            </div>
          </div>

          <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-success btn-sm"><?= h(t('wartung_btn_submit_backup')) ?></button>
          </div>
        <?= csrfField() ?></form>

        <div style="margin-top:26px;padding-top:18px;border-top:1px solid var(--border)">
          <h3 style="margin-bottom:10px"><?= h(t('wartung_heading_backup_settings')) ?></h3>
          <form method="post" action="?action=save_backup_settings" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
            <div class="form-group" style="margin-bottom:0">
              <label><?= h(t('wartung_label_max_count')) ?></label>
              <input type="number" name="backup_max_count" min="0" value="<?= (int)$backupMaxN ?>"
                     style="width:100px;margin-top:4px;background:var(--bg);border:1px solid var(--border);
                            color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.87rem">
            </div>
            <button type="submit" class="btn btn-muted btn-sm"><?= h(t('wartung_btn_save_settings')) ?></button>
          <?= csrfField() ?></form>
          <p style="color:var(--muted);font-size:.78rem;margin-top:8px;margin-bottom:0"><?= h(t('wartung_hint_max_count')) ?></p>
        </div>

        <script>
        function wartungSelectAll(sel) {
          document.querySelectorAll('input[name="tables[]"]').forEach(cb => { cb.checked = sel; });
        }
        document.querySelectorAll('label input[name="tables[]"]').forEach(cb => {
          const lbl = cb.closest('label');
          lbl.addEventListener('mouseover', () => { lbl.style.background = 'var(--surface2)'; });
          lbl.addEventListener('mouseout',  () => { lbl.style.background = ''; });
        });
        </script>

<?php } elseif ($tab === 'restore') { /* ── TAB: WIEDERHERSTELLUNG ──────────────────────── */ ?>

        <p style="color:var(--muted);font-size:.86rem;margin-top:0"><?= h(t('wartung_restore_intro')) ?></p>

<?php if (empty($backupsList)) { ?>
        <p style="color:var(--muted);font-size:.86rem"><?= h(t('wartung_restore_empty')) ?></p>
<?php } else { ?>
        <form method="post" id="wartung-restore-form" action="?action=restore_backup">
          <div class="form-group">
            <label><?= h(t('wartung_label_choose_backup')) ?></label>
            <div style="width:100%;max-width:420px;margin-top:6px;background:var(--bg);border:1px solid var(--border);
                        border-radius:var(--radius);padding:4px;max-height:280px;overflow-y:auto;font-family:monospace">
<?php foreach ($backupsList as $i => $b) {
    $label = $b['datetime']->format('d-m-Y H:i') . '  (' . strtoupper($b['format']) . ', ' . wartungFormatSize($b['size']) . ')'; ?>
              <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;font-size:.85rem;
                            color:var(--text);cursor:pointer;border-radius:4px">
                <input type="radio" name="filename" value="<?= h($b['filename']) ?>"<?= $i === 0 ? ' checked' : '' ?>
                       style="accent-color:var(--accent);width:14px;height:14px;flex:none">
                <?= h($label) ?>
<?php if ($b['hasLogos']) { ?>
                <span title="<?= h(t('wartung_hint_includes_logos')) ?>" style="color:var(--muted);font-size:.8rem">🖼️</span>
<?php } ?>
              </label>
<?php } ?>
            </div>
          </div>
          <div style="margin-top:14px;display:flex;gap:8px">
            <button type="submit" class="btn btn-success btn-sm"
                    onclick="return confirm(<?= json_encode(t('wartung_confirm_restore')) ?>);"><?= h(t('wartung_btn_restore')) ?></button>
            <button type="submit" formaction="?action=delete_backup" formnovalidate
                    class="btn btn-danger btn-sm"
                    onclick="return confirm(<?= json_encode(t('wartung_confirm_delete')) ?>);"><?= h(t('wartung_btn_delete')) ?></button>
          </div>
        <?= csrfField() ?></form>
        <script>
        document.querySelectorAll('#wartung-restore-form label input[type="radio"]').forEach(rb => {
          const lbl = rb.closest('label');
          lbl.addEventListener('mouseover', () => { lbl.style.background = 'var(--surface2)'; });
          lbl.addEventListener('mouseout',  () => { lbl.style.background = ''; });
        });
        </script>
<?php } ?>

<?php } ?>

      </div>
