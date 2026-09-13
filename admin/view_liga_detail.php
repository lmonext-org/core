<?php
/**
 * Project: LMOnext
 * Filename: view_liga_detail.php
 * Fileversion: 1.10.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Liga Detail ─────────────────────────────────────────────────────────
$ltype = (int)($ligaDetail['options']['Type']['option_value'] ?? 0);
$lid   = (int)$ligaDetail['liga']['id'];
?>
      <a href="?action=dashboard" class="back-link"><?= h(t('ld_back_link')) ?></a>
      <div class="card">
        <h2><?= h($ligaDetail['liga']['name']) ?>
          &nbsp;<?= $ltype === 1 ? '<span class="chip chip-yellow">'.h(t('dash_type_ko')).'</span>' : '<span class="chip chip-blue">'.h(t('dash_type_liga')).'</span>' ?>
        </h2>
        <p class="text-muted" style="font-size:.85rem"><?= h(t('ld_id_created', ['id' => $lid, 'datum' => $ligaDetail['liga']['datum']])) ?></p>
        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
          <a href="?action=liga_settings&id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_settings')) ?></a>
          <a href="?action=liga_settings&id=<?= $lid ?>&tab=teams" class="btn btn-muted"><?= h(t('ld_btn_teams')) ?></a>
<?php if ($ltype !== 1) { ?>
          <a href="?action=tabelle&liga_id=<?= $lid ?>" class="btn btn-primary"><?= h(t('sp_btn_table')) ?></a>
<?php } ?>
          <a href="?action=spieltag&liga_id=<?= $lid ?>&nr=1" class="btn btn-muted"><?= h(t('ld_btn_enter_results')) ?></a>
          <a href="?action=export&liga_id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_export')) ?></a>
<?php if (function_exists('addonManager') && addonManager()->isEnabled('player')) { ?>
          <a href="?action=spielerstatistik&liga_id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_spielerstatistik')) ?></a>
<?php } ?>
          <!-- Ins Archiv verschieben -->
          <div style="position:relative;display:inline-block" id="archiv-dd">
            <button type="button" class="btn btn-muted" onclick="toggleArchivMenu()"
                    style="border-color:var(--muted)"><?= h(t('ld_btn_archive_dd')) ?></button>
            <div id="archiv-menu" style="display:none;position:absolute;top:100%;left:0;z-index:100;
                 background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
                 min-width:200px;padding:6px 0;margin-top:4px;box-shadow:0 4px 12px #0004">
<?php
        try {
            $archFolders = getDB()->query('SELECT id,parent_id,name,sort FROM '.tbl('liga_archiv_folders').' ORDER BY sort,name')->fetchAll();
            if (empty($archFolders)) { ?>
              <div style="padding:8px 14px;font-size:.82rem;color:var(--muted)">
                <?= h(t('ld_no_folders')) ?>
                <a href="?action=archiv" style="color:var(--accent)"><?= h(t('ld_create_in_archive')) ?></a>
              </div>
<?php       } else {
                // Hierarchisch rendern: Eltern zuerst, dann Kinder eingerückt
                $byParent = [];
                foreach ($archFolders as $af) {
                    $byParent[(int)($af['parent_id'] ?? 0)][] = $af;
                }
                function renderArchivDdFolders(array $byParent, int $lid, int $parentId = 0, int $depth = 0): void {
                    if (empty($byParent[$parentId])) return;
                    if ($depth > 0 && $parentId !== 0) { ?>
              <div style="height:1px;background:var(--border);margin:2px 10px"></div>
<?php           }
                    foreach ($byParent[$parentId] as $af) {
                        $pad   = 10 + $depth * 16;
                        $icon  = $depth === 0 ? '📁' : '↳';
                        $style = $depth > 0 ? 'color:var(--muted);font-size:.8rem' : ''; ?>
              <form method="post" action="?action=move_liga_archiv" style="display:block">
                <input type="hidden" name="liga_id"   value="<?= $lid ?>">
                <input type="hidden" name="folder_id" value="<?= (int)$af['id'] ?>">
                <input type="hidden" name="redirect"  value="?action=archiv">
                <button type="submit" class="archiv-dd-item"
                        style="padding-left:<?= $pad ?>px;<?= $style ?>">
                  <?= $icon ?> <?= h($af['name']) ?>
                </button>
              <?= csrfField() ?></form>
<?php           renderArchivDdFolders($byParent, $lid, (int)$af['id'], $depth + 1);
                    }
                }
                renderArchivDdFolders($byParent, $lid);
            }
        } catch (Throwable) {}
?>
            </div>
          </div>
<?php
        if ($ltype === 1) {
            $expectedRounds = (int)($ligaDetail['options']['Rounds']['option_value'] ?? 0);
            $actualRounds   = count($ligaDetail['spieltage']);
            if ($expectedRounds > $actualRounds) { ?>
          <form method="post" action="?action=fix_ko_rounds" style="display:inline">
            <input type="hidden" name="liga_id" value="<?= $lid ?>">
            <button type="submit" class="btn btn-muted" style="border-color:var(--yellow);color:var(--yellow)">
              <?= h(t('ld_btn_fix_rounds', ['n' => $expectedRounds - $actualRounds])) ?>
            </button>
          <?= csrfField() ?></form>
<?php
            }
        } ?>
        </div>
      </div>
      <div class="card">
        <h2><?= h(t('ld_heading_spieltage', ['n' => count($ligaDetail['spieltage'])])) ?></h2>
          <table class="tbl">
            <thead><tr><th><?= h(t('wiz_col_hash')) ?></th><th><?= h(t('ld_col_start')) ?></th><th><?= h(t('ld_col_partien')) ?></th><th><?= h(t('ld_col_gespielt')) ?></th><th></th></tr></thead>
            <tbody>
<?php
        foreach ($ligaDetail['spieltage'] as $st) {
            $g = (int)$st['gespielt']; $tc = (int)$st['partie_count'];
            $cls = $g === $tc && $tc > 0 ? 'chip-green' : ($g > 0 ? 'chip-yellow' : 'chip-blue');
            $startVal = $st['start'] ? substr($st['start'], 0, 10) : ''; ?>
              <tr id="st-row-<?= (int)$st['id'] ?>">
                <td><?= (int)$st['nummer'] ?></td>
                <td class="text-muted" style="font-size:.8rem"><?= $startVal ?: '—' ?></td>
                <td><?= $tc ?></td>
                <td><span class="chip <?= $cls ?>"><?= $g ?>/<?= $tc ?></span></td>
                <td style="white-space:nowrap">
                  <button type="button" class="btn btn-muted btn-sm"
                          onclick="toggleStDatum(<?= (int)$st['id'] ?>, '<?= h($startVal) ?>')"
                          title="<?= h(t('ld_tooltip_edit_date')) ?>">📅</button>
                  <a href="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= (int)$st['nummer'] ?>" class="btn btn-muted btn-sm"><?= h(t('ld_btn_paarungen')) ?></a>
                </td>
              </tr>
              <tr id="st-edit-<?= (int)$st['id'] ?>" style="display:none">
                <td colspan="5">
                  <form method="post" action="?action=save_spieltag_datum"
                        style="display:flex;gap:8px;align-items:end;padding:6px 0">
                    <input type="hidden" name="spieltag_id" value="<?= (int)$st['id'] ?>">
                    <input type="hidden" name="liga_id" value="<?= $lid ?>">
                    <div>
                      <label style="font-size:.75rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('ld_label_startdatum')) ?></label>
                      <input type="date" name="start_datum" id="st-datum-<?= (int)$st['id'] ?>"
                             style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">💾</button>
                    <button type="button" class="btn btn-muted btn-sm"
                            onclick="document.getElementById('st-edit-<?= (int)$st['id'] ?>').style.display='none'">✕</button>
                  <?= csrfField() ?></form>
                </td>
              </tr>
<?php
        } ?>
            </tbody>
          </table>
        </div>
      <script>
      function toggleStDatum(id, current) {
        const editRow = document.getElementById('st-edit-' + id);
        if (editRow.style.display === 'none') {
          document.getElementById('st-datum-' + id).value = current;
          editRow.style.display = '';
        } else {
          editRow.style.display = 'none';
        }
      }

      function toggleArchivMenu() {
        const m = document.getElementById('archiv-menu');
        m.style.display = m.style.display === 'none' ? 'block' : 'none';
      }
      document.addEventListener('click', e => {
        const dd = document.getElementById('archiv-dd');
        if (dd && !dd.contains(e.target)) {
          document.getElementById('archiv-menu').style.display = 'none';
        }
      });
      </script>
