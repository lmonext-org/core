<?php
/**
 * Project: LMOnext
 * Filename: view_import.php
 * Fileversion: 1.2.2
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Import ──────────────────────────────────────────────────────────────
$importDetails = $_SESSION['import_details'] ?? [];
unset($_SESSION['import_details']);
$maxUploads = (int)ini_get('max_file_uploads') ?: 20;
?>
      <!-- Tab-Auswahl -->
      <div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
        <a href="#tab-multi" onclick="switchTab('multi')"
           id="btn-multi" style="padding:8px 18px;font-size:.83rem;text-decoration:none;
           border-radius:var(--radius) var(--radius) 0 0;border:1px solid var(--border);
           border-bottom:1px solid var(--surface);background:var(--surface);
           color:var(--accent);font-weight:600;margin-right:3px"><?= h(t('imp_tab_multi')) ?></a>
        <a href="#tab-zip" onclick="switchTab('zip')"
           id="btn-zip" style="padding:8px 18px;font-size:.83rem;text-decoration:none;
           border-radius:var(--radius) var(--radius) 0 0;border:1px solid var(--border);
           border-bottom:1px solid var(--border);background:var(--surface2);
           color:var(--muted);margin-right:3px"><?= h(t('imp_tab_zip')) ?></a>
      </div>

      <!-- Tab: Mehrere .l98 -->
      <div class="card" id="tab-multi" style="border-radius:0 var(--radius) var(--radius) var(--radius);margin-top:0">
        <h2><?= h(t('imp_multi_heading')) ?></h2>
        <p style="font-size:.88rem;color:var(--muted);margin-bottom:6px">
          <?= h(t('imp_multi_desc')) ?>
        </p>
        <div style="background:#f59e0b18;border:1px solid #f59e0b44;border-radius:var(--radius);
                    padding:8px 14px;font-size:.8rem;color:#fcd34d;margin-bottom:16px">
          <?= t('imp_multi_limit_warning', ['n' => $maxUploads]) ?>
        </div>
        <form method="post" action="?action=import" enctype="multipart/form-data">
          <input type="hidden" name="import_mode" value="multi">
          <div class="form-group" style="max-width:560px">
            <label><?= h(t('imp_label_ligadateien')) ?></label>
            <input type="file" name="l98file[]" accept=".l98" multiple required
                   style="display:block;margin-top:6px">
            <div id="file-count" style="font-size:.78rem;color:var(--muted);margin-top:6px"></div>
          </div>
          <button type="submit" class="btn btn-primary"><?= h(t('imp_btn_import')) ?></button>
        <?= csrfField() ?></form>
        <script>
        const i18nImpFileOne   = <?= json_encode(t('imp_js_file_one')) ?>;
        const i18nImpFileMany  = <?= json_encode(t('imp_js_file_many')) ?>;
        const i18nImpLimitWarn = <?= json_encode(t('imp_js_limit_warning')) ?>;
        document.querySelector('[name="l98file[]"]').addEventListener('change', function() {
          const n = this.files.length;
          const warn = n > <?= $maxUploads ?> ? ' <span style="color:var(--yellow)">' + i18nImpLimitWarn.replace('{n}', <?= $maxUploads ?>) + '</span>' : '';
          document.getElementById('file-count').innerHTML =
            n === 0 ? '' : (n === 1 ? i18nImpFileOne : i18nImpFileMany.replace('{n}', n)) + warn;
        });
        </script>
      </div>

      <!-- Tab: ZIP -->
      <div class="card" id="tab-zip" style="display:none;border-radius:0 var(--radius) var(--radius) var(--radius);margin-top:0">
        <h2><?= h(t('imp_zip_heading')) ?></h2>
        <p style="font-size:.88rem;color:var(--muted);margin-bottom:16px">
          <?= t('imp_zip_desc') ?>
        </p>
        <form method="post" action="?action=import" enctype="multipart/form-data">
          <input type="hidden" name="import_mode" value="zip">
          <div class="form-group" style="max-width:560px">
            <label><?= h(t('imp_label_zipfile')) ?></label>
            <input type="file" name="zipfile" accept=".zip" required
                   style="display:block;margin-top:6px">
          </div>
          <button type="submit" class="btn btn-primary"><?= h(t('imp_btn_import_zip')) ?></button>
        <?= csrfField() ?></form>
      </div>

      <script>
      function switchTab(tab) {
        ['multi','zip'].forEach(t => {
          document.getElementById('tab-'+t).style.display = t===tab ? 'block' : 'none';
          const btn = document.getElementById('btn-'+t);
          if (t===tab) {
            btn.style.background='var(--surface)'; btn.style.color='var(--accent)';
            btn.style.fontWeight='600'; btn.style.borderBottom='1px solid var(--surface)';
          } else {
            btn.style.background='var(--surface2)'; btn.style.color='var(--muted)';
            btn.style.fontWeight='400'; btn.style.borderBottom='1px solid var(--border)';
          }
        });
        return false;
      }
      </script>

<?php if (!empty($importDetails)) { ?>
      <div class="card">
        <h2><?= h(t('imp_details_heading', ['n' => count($importDetails)])) ?></h2>
        <div style="max-height:400px;overflow-y:auto">
          <table class="tbl">
            <tbody>
<?php foreach ($importDetails as $d) { ?>
              <tr>
                <td style="width:24px"><?= $d['type']==='success' ? '✅' : '❌' ?></td>
                <td style="font-size:.84rem"><?= $d['text'] ?></td>
              </tr>
<?php } ?>
            </tbody>
          </table>
        </div>
      </div>
<?php } ?>

<?php if (!empty($ligen)) { ?>
      <div class="card">
        <h2><?= h(t('imp_active_ligen_heading', ['n' => count($ligen)])) ?></h2>
        <table class="tbl">
          <thead><tr><th><?= h(t('dash_col_name')) ?></th><th><?= h(t('dash_col_created')) ?></th></tr></thead>
          <tbody>
<?php foreach ($ligen as $liga) { ?>
            <tr>
              <td><a href="?action=liga_detail&id=<?= (int)$liga['id'] ?>"
                     style="color:var(--accent);text-decoration:none"><?= h($liga['name']) ?></a></td>
              <td class="text-muted" style="font-size:.82rem"><?= h($liga['datum']) ?></td>
            </tr>
<?php } ?>
          </tbody>
        </table>
      </div>
<?php } ?>
