<?php
/**
 * Project: LMOnext
 * Filename: view_archiv.php
 * Fileversion: 1.7.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */

$folders     = $archivData['folders']     ?? [];
$archivLigen = $archivData['archivLigen'] ?? [];
$folderMap   = $archivData['folderMap']   ?? [];
$archivOffen = $archivData['offen']       ?? [];
$archivSort  = $archivData['sort']        ?? 'name';
$archivDir   = $archivData['dir']         ?? 'desc';
$totalOffen  = array_sum($archivOffen);
$ligenMitOffen = count(array_filter($archivOffen, fn($n) => $n > 0));

// Sortier-Steuerleiste (auf Wunsch, statt einer festen Vorgabe): baut den
// Link für eine Sortier-Spalte - bei erneutem Klick auf die bereits aktive
// Spalte kehrt sich die Richtung um, sonst wird auf die neue Spalte mit
// ihrer sinnvollen Standardrichtung gewechselt (Name/Erstellt: absteigend
// als Default, ID: aufsteigend als Default).
function archivSortLink(string $key, string $label, string $activeSort, string $activeDir): string
{
    $isActive = $key === $activeSort;
    $defaultDir = $key === 'id' ? 'asc' : 'desc';
    $nextDir = $isActive ? ($activeDir === 'asc' ? 'desc' : 'asc') : $defaultDir;
    $arrow = $isActive ? ($activeDir === 'asc' ? ' ↑' : ' ↓') : '';
    $color = $isActive ? 'var(--accent)' : 'var(--muted)';
    $weight = $isActive ? '600' : '400';
    return '<a href="?action=archiv&sort=' . h($key) . '&dir=' . h($nextDir) . '"'
         . ' style="color:' . $color . ';text-decoration:none;font-size:.82rem;font-weight:' . $weight . ';margin-right:16px">'
         . h($label) . $arrow . '</a>';
}

// Ligen nach Ordner-ID gruppieren
$ligenByFolder = [];
foreach ($archivLigen as $l) {
    $ligenByFolder[(int)$l['archiv_folder_id']][] = $l;
}

// Ordner als Baum aufbauen (rekursiv)
function archivBuildTree(array $folders, ?int $parentId = null): array {
    $tree = [];
    foreach ($folders as $f) {
        $pid = $f['parent_id'] ? (int)$f['parent_id'] : null;
        if ($pid === $parentId) {
            $f['children'] = archivBuildTree($folders, (int)$f['id']);
            $tree[] = $f;
        }
    }
    usort($tree, fn($a,$b) => $a['sort'] <=> $b['sort'] ?: strcmp($a['name'], $b['name']));
    return $tree;
}

// Ordner rekursiv rendern
function archivRenderFolder(array $f, array $ligenByFolder, array $folderMap, int $depth = 0): void {
    $fid      = (int)$f['id'];
    $ligen    = $ligenByFolder[$fid] ?? [];
    $pid      = $f['parent_id'] ? (int)$f['parent_id'] : 0;
    $hasKids  = !empty($f['children']) || !empty($ligen);
    // Hauptordner (depth 0) standardmäßig zugeklappt, Unterordner auch
    $open     = $depth === 0 ? '' : '';  // alle zu – 'open' zum Aufklappen
    ?>
    <details <?= $open ?> style="margin-left:<?= $depth * 22 ?>px;margin-bottom:6px">
      <summary style="list-style:none;display:flex;align-items:center;gap:8px;
                      background:var(--surface2);border:1px solid var(--border);
                      border-radius:var(--radius);padding:8px 12px;cursor:pointer;
                      user-select:none" class="archiv-summary">
        <!-- Pfeil-Icon per CSS -->
        <span class="archiv-arrow" style="font-size:.7rem;color:var(--muted);width:12px;flex-shrink:0">▶</span>
        <span style="font-size:1rem">🗂️</span>
        <div style="flex:1">
          <strong style="font-size:.9rem"><?= h($f['name']) ?></strong>
          <?php if ($f['beschreibung']) { ?>
          <span style="font-size:.8rem;color:var(--muted);margin-left:8px"><?= h($f['beschreibung']) ?></span>
          <?php } ?>
          <span style="font-size:.75rem;color:var(--muted);margin-left:6px">
            (<?= count($ligen) !== 1 ? h(t('arch_count_ligen_many', ['n' => count($ligen)])) : h(t('arch_count_ligen_one', ['n' => count($ligen)])) ?><?= !empty($f['children']) ? h(t('arch_count_subfolders', ['n' => count($f['children'])])) : '' ?>)
          </span>
        </div>
        <div style="display:flex;gap:6px" onclick="event.stopPropagation()">
          <button class="btn btn-muted btn-sm"
                  onclick="openFolderEdit(<?= $fid ?>,<?= h(json_encode($f['name'])) ?>,<?= h(json_encode($f['beschreibung'])) ?>,<?= (int)$f['sort'] ?>,<?= $pid ?>)">✏️</button>
          <form method="post" action="?action=delete_archiv_folder" style="display:inline"
                onsubmit="return confirm('<?= h(t('arch_confirm_delete_folder')) ?>')">
            <input type="hidden" name="folder_id" value="<?= $fid ?>">
            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
          <?= csrfField() ?></form>
        </div>
      </summary>
      <!-- Inhalt (Ligen + Unterordner) -->
      <div style="margin-left:8px;border-left:2px solid var(--border);padding-left:4px;margin-top:2px">
        <?php foreach ($ligen as $l) {
            $ltype = $l['liga_type'] ?? '0';
            global $archivOffen;
            $offen = $archivOffen[(int)$l['id']] ?? 0; ?>
        <div class="archiv-liga-row" data-offen="<?= $offen ?>"
             style="display:flex;align-items:center;gap:8px;padding:5px 12px;
                    border-bottom:1px solid var(--border);background:var(--bg)">
          <span class="chip <?= $ltype==='1'?'chip-yellow':'chip-blue' ?>" style="font-size:.72rem"><?= $ltype==='1'?h(t('dash_type_ko')):h(t('dash_type_liga')) ?></span>
          <span style="color:var(--muted);font-size:.78rem;font-family:monospace">#<?= (int)$l['id'] ?></span>
          <a href="?action=liga_detail&id=<?= (int)$l['id'] ?>"
             style="color:var(--accent);text-decoration:none;font-size:.87rem;flex:1"><?= h($l['name']) ?></a>
          <?php if ($offen > 0) { ?>
          <a href="?action=spieltag&liga_id=<?= (int)$l['id'] ?>&nr=1"
             style="padding:1px 7px;border-radius:20px;background:#f59e0b22;color:var(--yellow);
                    font-size:.73rem;text-decoration:none;white-space:nowrap"
             title="<?= h(t('dash_tooltip_missing_results', ['n' => $offen])) ?>">⚠️ <?= h(t('dash_open_badge', ['n' => $offen])) ?></a>
          <?php } ?>
          <span style="font-size:.75rem;color:var(--muted)"><?= h(substr($l['datum'],0,10)) ?></span>
          <form method="post" action="?action=move_liga_archiv" style="display:inline">
            <input type="hidden" name="liga_id" value="<?= (int)$l['id'] ?>">
            <input type="hidden" name="redirect" value="?action=archiv">
            <input type="hidden" name="folder_id" value="">
            <button type="submit" class="btn btn-muted btn-sm" style="padding:2px 8px;font-size:.75rem"><?= h(t('arch_btn_reactivate')) ?></button>
          <?= csrfField() ?></form>
          <?php if (count($folderMap) > 1) { ?>
          <form method="post" action="?action=move_liga_archiv" style="display:inline">
            <input type="hidden" name="liga_id" value="<?= (int)$l['id'] ?>">
            <input type="hidden" name="redirect" value="?action=archiv">
            <select name="folder_id" onchange="if(this.value!==''){this.form.submit()}"
                    style="background:var(--bg);border:1px solid var(--border);color:var(--muted);
                           border-radius:var(--radius);padding:2px 6px;font-size:.75rem">
              <option value=""><?= h(t('arch_btn_move_to_folder')) ?></option>
              <?php foreach ($folderMap as $foldId => $fold) {
                  if ((int)$foldId === $fid) continue; ?>
              <option value="<?= $foldId ?>"><?= h($fold['name']) ?></option>
              <?php } ?>
            </select>
          <?= csrfField() ?></form>
          <?php } ?>
          <form method="post" action="?action=delete_liga" style="display:inline"
                onsubmit="return confirm('<?= h(addslashes(t('dash_confirm_delete', ['name' => $l['name']]))) ?>')">
            <input type="hidden" name="liga_id" value="<?= (int)$l['id'] ?>">
            <input type="hidden" name="redirect" value="?action=archiv">
            <button type="submit" class="btn btn-danger btn-sm" style="padding:2px 8px;font-size:.75rem"><?= h(t('dash_btn_delete')) ?></button>
          <?= csrfField() ?></form>
        </div>
        <?php } ?>
        <?php foreach ($f['children'] as $child) {
            archivRenderFolder($child, $ligenByFolder, $folderMap, $depth + 1);
        } ?>
      </div>
    </details>
    <?php
}

$tree = archivBuildTree($folders);
?>

<!-- Toolbar -->
<div style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
  <button class="btn btn-muted" onclick="openFolderEdit(0,'','',0,0)"><?= h(t('arch_btn_new_folder')) ?></button>
  <?php if ($totalOffen > 0) { ?>
  <button id="btn-archiv-offen" onclick="toggleArchivOffenFilter()"
          style="padding:6px 12px;border-radius:var(--radius);border:1px solid var(--yellow);
                 color:var(--yellow);background:transparent;cursor:pointer;font-size:.82rem;white-space:nowrap">
    <?= $ligenMitOffen === 1 ? h(t('arch_btn_missing_results_one')) : h(t('arch_btn_missing_results_many', ['n' => $ligenMitOffen])) ?>
  </button>
  <?php } ?>
  <span style="font-size:.83rem;color:var(--muted);margin-left:auto">
    <?= h(t('arch_summary_line', ['folders' => count($folders), 'ligen' => count($archivLigen)])) ?>
  </span>
</div>

<!-- Sortierung der Ligen innerhalb jedes Ordners (auf Wunsch) -->
<div style="margin-bottom:12px;padding:8px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius)">
  <span style="font-size:.78rem;color:var(--muted);margin-right:10px"><?= h(t('arch_sort_label')) ?></span>
  <?= archivSortLink('id', t('arch_sort_id'), $archivSort, $archivDir) ?>
  <?= archivSortLink('name', t('arch_sort_name'), $archivSort, $archivDir) ?>
  <?= archivSortLink('datum', t('arch_sort_datum'), $archivSort, $archivDir) ?>
</div>

<!-- Baum oder Leer-Hinweis -->
<?php if (empty($tree) && empty($archivLigen)) { ?>
<div class="card">
  <p class="text-muted" style="font-size:.9rem">
    <?= h(t('arch_empty_line1')) ?><br>
    <?= t('arch_empty_line2') ?><br>
    <?= t('arch_empty_line3') ?>
  </p>
</div>
<?php } else { ?>
<div class="card" style="padding:12px">
  <?php foreach ($tree as $folder) {
      archivRenderFolder($folder, $ligenByFolder, $folderMap);
  } ?>
  <!-- Ligen ohne Ordner -->
  <?php $orphans = $ligenByFolder[0] ?? [];
  if (!empty($orphans)) { ?>
  <div style="margin-top:12px;border-top:1px solid var(--border);padding-top:12px">
    <div style="font-size:.82rem;color:var(--muted);margin-bottom:6px"><?= h(t('arch_no_folder_label')) ?></div>
    <?php foreach ($orphans as $l) { ?>
    <div style="display:flex;align-items:center;gap:8px;padding:5px 10px;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:4px">
      <span style="color:var(--muted);font-size:.78rem;font-family:monospace">#<?= (int)$l['id'] ?></span>
      <a href="?action=liga_detail&id=<?= (int)$l['id'] ?>"
         style="color:var(--accent);text-decoration:none;font-size:.87rem;flex:1"><?= h($l['name']) ?></a>
      <form method="post" action="?action=move_liga_archiv" style="display:inline">
        <input type="hidden" name="liga_id" value="<?= (int)$l['id'] ?>">
        <input type="hidden" name="redirect" value="?action=archiv">
        <input type="hidden" name="folder_id" value="">
        <button type="submit" class="btn btn-muted btn-sm"><?= h(t('arch_btn_reactivate')) ?></button>
      <?= csrfField() ?></form>
      <form method="post" action="?action=delete_liga" style="display:inline"
            onsubmit="return confirm('<?= h(addslashes(t('dash_confirm_delete', ['name' => $l['name']]))) ?>')">
        <input type="hidden" name="liga_id" value="<?= (int)$l['id'] ?>">
        <input type="hidden" name="redirect" value="?action=archiv">
        <button type="submit" class="btn btn-danger btn-sm"><?= h(t('dash_btn_delete')) ?></button>
      <?= csrfField() ?></form>
    </div>
    <?php } ?>
  </div>
  <?php } ?>
</div>
<?php } ?>

<!-- Ordner-Modal -->
<div id="folder-modal" style="display:none;position:fixed;inset:0;background:#000a;z-index:9999;
                               align-items:center;justify-content:center">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
              padding:24px 28px;width:100%;max-width:460px;margin:16px">
    <h2 style="font-size:1rem;margin-bottom:16px" id="modal-title"><?= h(t('arch_modal_title_default')) ?></h2>
    <form method="post" action="?action=save_archiv_folder">
      <input type="hidden" name="folder_id" id="fm-id" value="0">
      <div style="margin-bottom:12px">
        <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('teams_field_name_required')) ?></label>
        <input type="text" name="folder_name" id="fm-name" required
               style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                      border-radius:var(--radius);padding:7px 12px;font-size:.88rem">
      </div>
      <div style="margin-bottom:12px">
        <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('arch_label_description')) ?></label>
        <input type="text" name="folder_beschr" id="fm-beschr"
               style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                      border-radius:var(--radius);padding:7px 12px;font-size:.88rem">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        <div>
          <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('arch_label_parent_folder')) ?></label>
          <select name="parent_id" id="fm-parent"
                  style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                         border-radius:var(--radius);padding:6px 10px;font-size:.85rem">
            <option value="0"><?= h(t('arch_option_top_level')) ?></option>
            <?php foreach ($folders as $f) { ?>
            <option value="<?= (int)$f['id'] ?>"><?= h($f['name']) ?></option>
            <?php } ?>
          </select>
        </div>
        <div>
          <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('arch_label_sort')) ?></label>
          <input type="number" name="folder_sort" id="fm-sort" value="0" min="0"
                 style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                        border-radius:var(--radius);padding:7px 12px;font-size:.88rem">
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-muted btn-sm"
                onclick="document.getElementById('folder-modal').style.display='none'"><?= h(t('common_cancel')) ?></button>
        <button type="submit" class="btn btn-success btn-sm"><?= h(t('common_save')) ?></button>
      </div>
    <?= csrfField() ?></form>
  </div>
</div>

<script>
const i18nArch = {
  modalTitleNew:  <?= json_encode(t('arch_btn_new_folder')) ?>,
  modalTitleEdit: <?= json_encode(t('arch_modal_title_edit')) ?>,
  clearFilter:    <?= json_encode(t('arch_btn_clear_filter')) ?>,
};

function openFolderEdit(id, name, beschr, sort, parentId) {
  document.getElementById('fm-id').value     = id;
  document.getElementById('fm-name').value   = name;
  document.getElementById('fm-beschr').value = beschr;
  document.getElementById('fm-sort').value   = sort;
  const sel = document.getElementById('fm-parent');
  for (let i = 0; i < sel.options.length; i++) {
    if (parseInt(sel.options[i].value) === parentId) { sel.selectedIndex = i; break; }
  }
  document.getElementById('modal-title').textContent = id > 0 ? i18nArch.modalTitleEdit : i18nArch.modalTitleNew;
  document.getElementById('folder-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('fm-name').focus(), 50);
}
document.getElementById('folder-modal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
// ── Filter: Ligen mit fehlenden Ergebnissen ──────────────────────────────────
let archivOffenOnly = false;
function toggleArchivOffenFilter() {
  archivOffenOnly = !archivOffenOnly;
  const btn = document.getElementById('btn-archiv-offen');
  if (btn) {
    btn.style.background = archivOffenOnly ? '#f59e0b33' : 'transparent';
    btn.textContent = archivOffenOnly ? i18nArch.clearFilter : btn.textContent;
  }
  // Alle Liga-Zeilen prüfen
  document.querySelectorAll('.archiv-liga-row').forEach(row => {
    const offen = parseInt(row.dataset.offen ?? '0');
    row.style.display = (!archivOffenOnly || offen > 0) ? '' : 'none';
  });
  // Leere details-Elemente einklappen wenn alle Kinder versteckt
  document.querySelectorAll('details').forEach(d => {
    const visible = d.querySelectorAll('.archiv-liga-row:not([style*="none"])').length
                  + d.querySelectorAll('details:not([style*="none"])').length;
    if (archivOffenOnly) {
      d.open = visible > 0;
      d.style.display = visible > 0 ? '' : 'none';
    } else {
      d.style.display = '';
    }
  });
}
</script>
