<?php
/**
 * Project: LMOnext
 * Filename: view_liga_list.php
 * Fileversion: 1.3.2
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Hauptseite: Liga-Liste / Dashboard ───────────────────────────────────────
try {
    $db      = getDB();
    $cntT    = (int)$db->query('SELECT COUNT(*) FROM '.tbl('teams_global'))->fetchColumn();
    $cntP    = (int)$db->query('SELECT COUNT(*) FROM '.tbl('liga_partien'))->fetchColumn();
    $cntAll  = (int)$db->query('SELECT COUNT(*) FROM '.tbl('liga'))->fetchColumn();
    $cntArch = (int)$db->query('SELECT COUNT(*) FROM '.tbl('liga').' WHERE archiv_folder_id IS NOT NULL')->fetchColumn();
} catch (Throwable) { $cntT = 0; $cntP = 0; $cntAll = 0; $cntArch = 0; }

// Typ für jede Liga vorab laden (einmal, nicht pro Zeile)
$ligaTypes = [];
if (!empty($ligen)) {
    try {
        $sTyp = $db->prepare('SELECT liga_id, option_value FROM '.tbl('liga_options').' WHERE option_key="Type" AND liga_id IN ('.implode(',', array_column($ligen, 'id')).')');
        $sTyp->execute();
        foreach ($sTyp->fetchAll() as $r) { $ligaTypes[$r['liga_id']] = (int)$r['option_value']; }
    } catch (Throwable) {}
}

// Offene Partien pro Liga (h_tore IS NULL oder h_tore = -1)
$ligaOffen = [];
if (!empty($ligen)) {
    try {
        $ids = implode(',', array_column($ligen, 'id'));
        $sO = $db->query(
            'SELECT s.liga_id, COUNT(*) AS offen
               FROM '.tbl('liga_spieltage').' s
               JOIN '.tbl('liga_partien').' p ON p.spieltag_id = s.id
              WHERE s.liga_id IN ('.$ids.')
                AND (p.h_tore IS NULL OR p.h_tore = -1)
                AND p.heim_id != p.gast_id
              GROUP BY s.liga_id'
        );
        foreach ($sO->fetchAll() as $r) { $ligaOffen[(int)$r['liga_id']] = (int)$r['offen']; }
    } catch (Throwable) {}
}
?>
      <div class="stats-grid">
        <a href="?action=archiv" class="stat-card" style="text-decoration:none;cursor:pointer" title="<?= h(t('dash_tooltip_all_ligen')) ?>">
          <div class="val"><?= $cntAll ?></div>
          <div class="lbl"><?= h(t('dash_stat_ligen_total')) ?></div>
          <?php if ($cntArch > 0) { ?>
          <div style="font-size:.73rem;color:var(--muted);margin-top:4px"><?= h(t('dash_stat_active_archived', ['active' => count($ligen), 'archived' => $cntArch])) ?></div>
          <?php } ?>
        </a>
        <a href="?action=teams" class="stat-card" style="text-decoration:none;cursor:pointer" title="<?= h(t('dash_tooltip_teams')) ?>">
          <div class="val"><?= $cntT ?></div><div class="lbl"><?= h(t('dash_stat_teams')) ?></div>
        </a>
        <div class="stat-card"><div class="val"><?= $cntP ?></div><div class="lbl"><?= h(t('dash_stat_partien')) ?></div></div>
      </div>
      <div class="card" style="padding-bottom:0">
        <!-- Toolbar: Bulk-Archivieren -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap">
          <h2 style="margin:0"><?= h(t('dash_heading_all_ligen')) ?></h2>
          <button id="btn-offen" onclick="toggleOffenOnly()"
                  style="padding:6px 12px;border-radius:var(--radius);border:1px solid var(--muted);
                         color:var(--muted);background:transparent;cursor:pointer;font-size:.82rem;white-space:nowrap">
            <?= h(t('dash_btn_missing_results')) ?>
          </button>
          <div id="bulk-bar" style="display:none;align-items:center;gap:8px;margin-left:auto">
            <span id="bulk-count" style="font-size:.83rem;color:var(--muted)"></span>
            <form method="post" action="?action=bulk_archiv" id="bulk-form" style="display:flex;gap:6px;align-items:center">
              <select name="folder_id" id="bulk-folder"
                      style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                             border-radius:var(--radius);padding:5px 10px;font-size:.83rem">
                <option value="0"><?= h(t('dash_select_folder_placeholder')) ?></option>
                <?php
                try {
                    $archFolders = getDB()->query('SELECT id,parent_id,name,sort FROM '.tbl('liga_archiv_folders').
                        ' ORDER BY sort,name')->fetchAll();
                    $byParent2 = [];
                    foreach ($archFolders as $af) { $byParent2[(int)($af['parent_id'] ?? 0)][] = $af; }
                    function renderFolderOpts(array $byParent, int $pid=0, int $depth=0): void {
                        foreach ($byParent[$pid] ?? [] as $af) {
                            $pre = str_repeat('↳ ', $depth);
                            echo '<option value="'.(int)$af['id'].'">'.h($pre.$af['name']).'</option>';
                            renderFolderOpts($byParent, (int)$af['id'], $depth+1);
                        }
                    }
                    renderFolderOpts($byParent2);
                } catch (Throwable) {}
                ?>
              </select>
              <div id="bulk-ids"></div>
              <button type="submit" class="btn btn-muted btn-sm"
                      onclick="return validateBulk()"><?= h(t('dash_btn_archive')) ?></button>
            <?= csrfField() ?></form>
            <button class="btn btn-muted btn-sm" onclick="clearSelection()"><?= h(t('dash_btn_clear_selection')) ?></button>
          </div>
        </div>

<?php if (empty($ligen)) { ?>
        <p class="text-muted" style="font-size:.9rem"><?= h(t('dash_empty_pre')) ?>
          <a href="?action=create_liga&step=1" style="color:var(--accent)"><?= h(t('dash_empty_link')) ?></a><?= h(t('dash_empty_post')) ?></p>
<?php } else { ?>
        <table class="tbl" id="ligen-tbl">
          <thead>
            <tr>
              <th style="width:32px"><input type="checkbox" id="chk-all" title="<?= h(t('dash_tooltip_select_all')) ?>" onclick="toggleAll(this)"></th>
              <?php
              $cols = ['id' => t('dash_col_id'), 'name' => t('dash_col_name'), 'typ' => t('dash_col_typ'), 'datum' => t('dash_col_created')];
              foreach ($cols as $key => $label) { ?>
              <th class="sortable" data-col="<?= $key ?>" style="cursor:pointer;user-select:none;white-space:nowrap">
                <?= h($label) ?> <span class="sort-icon" style="font-size:.7rem;color:var(--muted)">⇅</span>
              </th>
              <?php } ?>
              <th><?= h(t('dash_col_actions')) ?></th>
            </tr>
          </thead>
          <tbody id="ligen-tbody">
<?php foreach ($ligen as $liga) {
    $ltype = $ligaTypes[(int)$liga['id']] ?? 0;
    $typLabel = $ltype === 1 ? t('dash_type_ko') : t('dash_type_liga');
    $offen = $ligaOffen[(int)$liga['id']] ?? 0; ?>
            <tr
              data-id="<?= (int)$liga['id'] ?>"
              data-name="<?= h(strtolower($liga['name'])) ?>"
              data-typ="<?= $ltype ?>"
              data-datum="<?= h($liga['datum']) ?>"
              data-offen="<?= $offen ?>">
              <td><input type="checkbox" class="liga-chk" value="<?= (int)$liga['id'] ?>"
                         onchange="updateSelection()"></td>
              <td class="text-muted" style="width:50px"><?= (int)$liga['id'] ?></td>
              <td><a href="?action=liga_detail&id=<?= (int)$liga['id'] ?>"
                     style="color:var(--accent);text-decoration:none"><?= h($liga['name']) ?></a></td>
              <td><span class="chip <?= $ltype === 1 ? 'chip-yellow' : 'chip-blue' ?>"><?= h($typLabel) ?></span></td>
              <td class="text-muted" style="font-size:.82rem"><?= h($liga['datum']) ?></td>
              <td>
                <?php if ($offen > 0) { ?>
                <a href="?action=spieltag&liga_id=<?= (int)$liga['id'] ?>&nr=1"
                   style="display:inline-block;margin-right:6px;padding:2px 8px;border-radius:20px;
                          background:#f59e0b22;color:var(--yellow);font-size:.75rem;text-decoration:none"
                   title="<?= h(t('dash_tooltip_missing_results', ['n' => $offen])) ?>">
                  ⚠️ <?= h(t('dash_open_badge', ['n' => $offen])) ?>
                </a>
                <?php } ?>
                <form method="post" action="?action=delete_liga" style="display:inline"
                      onsubmit="return confirm('<?= h(addslashes(t('dash_confirm_delete', ['name' => $liga['name']]))) ?>')">
                  <input type="hidden" name="liga_id" value="<?= (int)$liga['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm"><?= h(t('dash_btn_delete')) ?></button>
                <?= csrfField() ?></form>
              </td>
            </tr>
<?php } ?>
          </tbody>
        </table>
<?php } ?>
      </div>

<script>
const i18nSelectedCount   = <?= json_encode(t('dash_js_selected_count')) ?>;
const i18nChooseFolder    = <?= json_encode(t('dash_js_choose_folder_alert')) ?>;
const i18nArchiveOne      = <?= json_encode(t('dash_confirm_archive_one')) ?>;
const i18nArchiveMany     = <?= json_encode(t('dash_confirm_archive_many')) ?>;

function updateSelection() {
  const checked = document.querySelectorAll('.liga-chk:checked');
  const bar = document.getElementById('bulk-bar');
  bar.style.display = checked.length > 0 ? 'flex' : 'none';
  document.getElementById('bulk-count').textContent = i18nSelectedCount.replace('{n}', checked.length);
  const ids = document.getElementById('bulk-ids');
  ids.innerHTML = '';
  checked.forEach(cb => {
    const inp = document.createElement('input');
    inp.type='hidden'; inp.name='liga_ids[]'; inp.value=cb.value;
    ids.appendChild(inp);
  });
  document.getElementById('chk-all').indeterminate =
    checked.length > 0 && checked.length < document.querySelectorAll('.liga-chk').length;
  document.getElementById('chk-all').checked =
    checked.length === document.querySelectorAll('.liga-chk').length;
}
function toggleAll(el) {
  document.querySelectorAll('.liga-chk').forEach(cb => { cb.checked = el.checked; });
  updateSelection();
}
function clearSelection() {
  document.querySelectorAll('.liga-chk').forEach(cb => cb.checked = false);
  document.getElementById('chk-all').checked = false;
  updateSelection();
}
function validateBulk() {
  const folder = document.getElementById('bulk-folder').value;
  if (!folder || folder === '0') { alert(i18nChooseFolder); return false; }
  const n = document.querySelectorAll('.liga-chk:checked').length;
  return confirm(n === 1 ? i18nArchiveOne : i18nArchiveMany.replace('{n}', n));
}

(function () {
  const tbody  = document.getElementById('ligen-tbody');
  if (!tbody) return;

  let sortCol = 'datum';   // aktuell sortierte Spalte
  let sortAsc  = false;    // false = neueste zuerst (Standard)

  function sortTable(col) {
    if (sortCol === col) { sortAsc = !sortAsc; }
    else                 { sortCol = col; sortAsc = (col === 'name'); }

    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
      let va = a.dataset[col] ?? '';
      let vb = b.dataset[col] ?? '';
      // Numerisch für id
      if (col === 'id' || col === 'typ') {
        va = parseInt(va, 10); vb = parseInt(vb, 10);
        return sortAsc ? va - vb : vb - va;
      }
      return sortAsc ? va.localeCompare(vb) : vb.localeCompare(va);
    });
    rows.forEach(r => tbody.appendChild(r));

    // Icons aktualisieren
    document.querySelectorAll('.sortable').forEach(th => {
      const icon = th.querySelector('.sort-icon');
      if (th.dataset.col === col) {
        icon.textContent = sortAsc ? '↑' : '↓';
        icon.style.color = 'var(--accent)';
      } else {
        icon.textContent = '⇅';
        icon.style.color = 'var(--muted)';
      }
    });
  }

  document.querySelectorAll('.sortable').forEach(th => {
    th.addEventListener('click', () => sortTable(th.dataset.col));
  });

  // Standardsortierung: Datum absteigend
  sortTable('datum');
})();

// ── Filter: Ligen mit fehlenden Ergebnissen ───────────────────────────────────
let offenOnly = false;
function toggleOffenOnly() {
  offenOnly = !offenOnly;
  const btn = document.getElementById('btn-offen');
  btn.style.background = offenOnly ? '#f59e0b33' : 'transparent';
  btn.style.borderColor = offenOnly ? 'var(--yellow)' : 'var(--muted)';
  btn.style.color       = offenOnly ? 'var(--yellow)' : 'var(--muted)';
  document.querySelectorAll('#ligen-tbody tr[data-id]').forEach(row => {
    const offen = parseInt(row.dataset.offen ?? '0');
    row.style.display = (!offenOnly || offen > 0) ? '' : 'none';
  });
}
</script>
