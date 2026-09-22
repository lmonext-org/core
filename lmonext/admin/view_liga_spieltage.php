<?php
/**
 * Project: LMOnext
 * Filename: view_liga_spieltage.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Spieltage-Übersicht einer Liga ───────────────────────────────────
// Vormals view_liga_detail.php / Aktion "liga_detail" - diese Aktion
// öffnet seit 1.0.0 stattdessen direkt den aktuellen Spieltag, siehe
// resolveLigaEntrySpieltagNr() in admin/bootstrap.php. Diese Übersicht ist
// jetzt über den neuen "Spieltage-Übersicht"-Link im Navigationsblock
// erreichbar, Aktion "liga_spieltage".
$ltype = (int)($ligaDetail['options']['Type']['option_value'] ?? 0);
$lid   = (int)$ligaDetail['liga']['id'];

$navLid           = $lid;
$navLigaName      = $ligaDetail['liga']['name'];
$navLigaDatum     = $ligaDetail['liga']['datum'];
$navLtype         = $ltype;
$navExpectedRounds = (int)($ligaDetail['options']['Rounds']['option_value'] ?? 0);
$navActualRounds   = count($ligaDetail['spieltage']);
?>
      <a href="?action=dashboard" class="back-link"><?= h(t('ld_back_link')) ?></a>
<?php require_once ADMIN_INC . '/view_liga_nav.php'; ?>
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
      </script>
