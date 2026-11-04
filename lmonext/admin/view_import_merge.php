<?php
/**
 * Project: LMOnext
 * Filename: view_import_merge.php
 * Fileversion: 1.2.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * View: .l98 in eine vorhandene Liga übernehmen (siehe handler_import_merge.php).
 * Drei Zustände: Upload-Formular / Team-Zuordnung + Vorschau / Ergebnis.
 */

$inpSt = 'background:var(--bg);border:1px solid var(--border);color:var(--text);'
       . 'border-radius:var(--radius);padding:6px 10px;font-size:.85rem;width:100%;max-width:420px';

/** Zählerübersicht + Detailliste (für Vorschau und Ergebnis). */
$renderReport = static function (array $res, bool $preview) : void {
    $c = $res['counts'];
    $typeLabel = [
        'filled'      => t('impm_d_filled'),
        'overwritten' => t('impm_d_overwritten'),
        'skipped'     => t('impm_d_skipped'),
        'created'     => t('impm_d_created'),
        'swapped'     => t('impm_d_swapped'),
        'pruned'      => t('impm_d_pruned'),
    ];
    $typeIcon = ['filled' => '✅', 'overwritten' => '✏️', 'skipped' => '⏭️', 'created' => '➕', 'swapped' => '⚠️', 'pruned' => '🗑️'];
    ?>
      <div class="card">
        <h2><?= h($preview ? t('impm_preview_heading') : t('impm_done_heading')) ?></h2>
<?php if ((int)$c['created'] > 0) { ?>
        <div style="background:#f59e0b18;border:1px solid #f59e0b44;border-radius:var(--radius);padding:8px 14px;font-size:.84rem;color:#fcd34d;margin-bottom:12px">
          ⚠️ <?= h(t($preview ? 'impm_warn_created_preview' : 'impm_warn_created_done', ['n' => (int)$c['created']])) ?>
        </div>
<?php } ?>
        <table class="tbl" style="max-width:520px">
          <tbody>
            <tr><td><?= h(t('impm_c_filled')) ?></td><td style="text-align:right"><strong><?= (int)$c['filled'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_overwritten')) ?></td><td style="text-align:right"><strong><?= (int)$c['overwritten'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_skipped')) ?></td><td style="text-align:right"><strong><?= (int)$c['skippedExisting'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_unchanged')) ?></td><td style="text-align:right"><strong><?= (int)$c['unchanged'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_created')) ?></td><td style="text-align:right"><strong><?= (int)$c['created'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_spieltage')) ?></td><td style="text-align:right"><strong><?= (int)$c['spieltageCreated'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_dates')) ?></td><td style="text-align:right"><strong><?= (int)$c['datesFilled'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_swapped')) ?></td><td style="text-align:right"><strong><?= (int)$c['swapped'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_unmapped')) ?></td><td style="text-align:right"><strong><?= (int)$c['unmapped'] ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_pruned')) ?></td><td style="text-align:right"><strong><?= (int)($c['pruned'] ?? 0) ?></strong></td></tr>
            <tr><td><?= h(t('impm_c_teams_added')) ?></td><td style="text-align:right"><strong><?= (int)$c['teamsAdded'] ?></strong></td></tr>
          </tbody>
        </table>
<?php if (!empty($res['teamsCreated'])) { ?>
        <p style="font-size:.84rem;margin-top:10px"><?= h(t('impm_teams_created', ['teams' => implode(', ', $res['teamsCreated'])])) ?></p>
<?php } ?>
<?php
    $lines = array_values(array_filter($res['details'], static fn(array $d) => $d['type'] !== 'spieltag'));
    if (!empty($lines)) {
        $maxLines = 300; ?>
        <div style="max-height:420px;overflow-y:auto;margin-top:12px">
          <table class="tbl">
            <thead><tr><th><?= h(t('impm_col_st')) ?></th><th><?= h(t('impm_col_match')) ?></th><th><?= h(t('impm_col_action')) ?></th><th><?= h(t('impm_col_result')) ?></th></tr></thead>
            <tbody>
<?php   foreach (array_slice($lines, 0, $maxLines) as $d) { ?>
              <tr>
                <td class="text-muted"><?= (int)$d['nr'] ?></td>
                <td style="font-size:.84rem"><?= h($d['text']) ?><?php if (!empty($d['hint'])) { ?><div class="text-muted" style="font-size:.72rem;white-space:normal"><?= h($d['hint']) ?></div><?php } ?></td>
                <td style="font-size:.82rem;white-space:nowrap"><?= $typeIcon[$d['type']] ?? '' ?> <?= h($typeLabel[$d['type']] ?? $d['type']) ?></td>
                <td style="font-size:.82rem;white-space:nowrap"><?= h(($d['res'] ?? '') !== '' ? ($d['res'] ?? '') : '–') ?><?= isset($d['old']) ? ' <span class="text-muted">(' . h(t('impm_was')) . ' ' . h($d['old'] !== '' ? $d['old'] : '–') . ')</span>' : '' ?></td>
              </tr>
<?php   } ?>
            </tbody>
          </table>
        </div>
<?php   if (count($lines) > $maxLines) { ?>
        <p class="text-muted" style="font-size:.78rem"><?= h(t('impm_more_lines', ['n' => count($lines) - $maxLines])) ?></p>
<?php   } ?>
<?php } ?>
      </div>
<?php
};

// ═══ Zustand 3: Ergebnis nach dem Übernehmen ═══════════════════════════════
$done = $_SESSION['import_merge_done'] ?? null;
if ($action === 'import_merge' && !empty($_GET['done']) && is_array($done)) {
    unset($_SESSION['import_merge_done']); ?>
      <div class="card" style="max-width:820px">
        <h2>✅ <?= h(t('impm_done_title', ['liga' => $done['ligaName']])) ?></h2>
        <p class="text-muted" style="font-size:.84rem">📄 <?= h($done['fileName']) ?></p>
        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn btn-primary btn-sm" style="text-decoration:none" href="?action=liga_detail&id=<?= (int)$done['ligaId'] ?>"><?= h(t('impm_btn_to_liga')) ?></a>
          <a class="btn btn-muted btn-sm" style="text-decoration:none" href="?action=import_merge"><?= h(t('impm_btn_again')) ?></a>
<?php if (!empty($_SESSION['import_merge_undo'])) { ?>
          <form method="post" action="?action=import_merge_undo" style="display:inline"
                onsubmit="return confirm(<?= h(json_encode(t('impm_undo_confirm'))) ?>)">
            <button type="submit" class="btn btn-danger btn-sm">↩️ <?= h(t('impm_btn_undo')) ?></button>
          <?= csrfField() ?></form>
<?php } ?>
        </div>
      </div>
<?php
    $renderReport($done['result'], false);
    return;
}

// ═══ Zustand 2: Team-Zuordnung + Vorschau ══════════════════════════════════
if ($action === 'import_merge_review') {
    $pending = $_SESSION['import_merge'] ?? null;
    if (!is_array($pending)) {
        redirect('?action=import_merge');
    }
    $suggest  = mergeSuggestTeams($pending['data'], (int)$pending['ligaId']);
    $savedMap = $pending['map'] ?? null; ?>
      <div class="card" style="max-width:820px">
        <h2><?= h(t('impm_review_heading')) ?></h2>
        <p style="font-size:.86rem;color:var(--muted);margin-bottom:4px">
          📄 <?= h($pending['fileName']) ?> → <strong><?= h($pending['ligaName']) ?></strong>
          · <?= h(t('impm_review_counts', ['teams' => count($pending['data']['teams']), 'st' => count($pending['data']['spieltage'])])) ?>
        </p>
        <p style="font-size:.86rem;color:var(--muted);margin-bottom:16px"><?= h(t('impm_review_intro')) ?></p>

        <form method="post" action="?action=import_merge_apply">
          <table class="tbl" style="margin-bottom:14px">
            <thead><tr><th><?= h(t('impm_col_file_team')) ?></th><th><?= h(t('impm_col_target_team')) ?></th></tr></thead>
            <tbody>
<?php foreach ($suggest as $nr => $s) {
    $sel = $savedMap[$nr] ?? $s['suggest']; ?>
              <tr>
                <td style="font-size:.86rem"><?= h($s['name']) ?></td>
                <td>
                  <select name="map[<?= (int)$nr ?>]" style="<?= $inpSt ?>">
<?php   foreach ($s['options'] as $o) { ?>
                    <option value="<?= (int)$o['id'] ?>"<?= (string)$sel === (string)$o['id'] ? ' selected' : '' ?>><?= h($o['name']) ?><?= $o['inLiga'] ? '' : ' (' . h(t('impm_not_in_liga')) . ')' ?></option>
<?php   } ?>
                    <option value="new"<?= (string)$sel === 'new' ? ' selected' : '' ?>>➕ <?= h(t('impm_opt_new')) ?></option>
                    <option value="skip"<?= (string)$sel === 'skip' ? ' selected' : '' ?>>🚫 <?= h(t('impm_opt_skip')) ?></option>
                  </select>
                </td>
              </tr>
<?php } ?>
            </tbody>
          </table>
          <label style="display:flex;align-items:center;gap:8px;font-size:.86rem;margin-bottom:14px;cursor:pointer">
            <input type="checkbox" name="overwrite" value="1"<?= !empty($pending['overwrite']) ? ' checked' : '' ?>>
            <?= h(t('impm_label_overwrite')) ?>
          </label>
          <label style="display:flex;align-items:center;gap:8px;font-size:.86rem;margin-bottom:14px;cursor:pointer">
            <input type="checkbox" name="prune" value="1"<?= !empty($pending['prune']) ? ' checked' : '' ?>>
            <?= h(t('impm_label_prune')) ?>
          </label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:12px;border-top:1px solid var(--border)">
            <button type="submit" name="do" value="preview" class="btn btn-muted btn-sm">🔍 <?= h(t('impm_btn_preview')) ?></button>
            <button type="submit" name="do" value="apply" class="btn btn-success btn-sm"
                    onclick="return confirm(<?= h(json_encode(t('impm_confirm_apply'))) ?>)"><?= h(t('impm_btn_apply')) ?></button>
            <a href="?action=import_merge_cancel" class="btn btn-muted btn-sm" style="text-decoration:none"><?= h(t('imp_review_btn_cancel')) ?></a>
          </div>
        <?= csrfField() ?></form>
      </div>
<?php
    if (!empty($pending['report'])) {
        $renderReport($pending['report'], true);
    }
    return;
}

// ═══ Zustand 1: Upload-Formular ════════════════════════════════════════════
$targets   = mergeTargetLigen();
$preselect = (int)($_GET['liga_id'] ?? 0); ?>
      <div class="card" style="max-width:820px">
        <h2><?= h(t('impm_heading')) ?></h2>
        <p style="font-size:.88rem;color:var(--muted);margin-bottom:16px"><?= h(t('impm_intro')) ?></p>
        <form method="post" action="?action=import_merge" enctype="multipart/form-data">
          <div class="form-group" style="max-width:560px">
            <label><?= h(t('impm_label_target')) ?></label>
            <select name="target_liga" required style="<?= $inpSt ?>;margin-top:6px">
              <option value=""><?= h(t('impm_opt_choose')) ?></option>
<?php foreach ($targets as $l) { ?>
              <option value="<?= (int)$l['id'] ?>"<?= $preselect === (int)$l['id'] ? ' selected' : '' ?>><?= h($l['name']) ?> (ID <?= (int)$l['id'] ?>)</option>
<?php } ?>
            </select>
          </div>
          <div class="form-group" style="max-width:560px">
            <label><?= h(t('impm_label_file')) ?></label>
            <input type="file" name="l98file" accept=".l98" required style="display:block;margin-top:6px">
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-size:.86rem;margin-bottom:14px;cursor:pointer">
            <input type="checkbox" name="overwrite" value="1">
            <?= h(t('impm_label_overwrite')) ?>
          </label>
          <label style="display:flex;align-items:center;gap:8px;font-size:.86rem;margin-bottom:14px;cursor:pointer">
            <input type="checkbox" name="prune" value="1">
            <?= h(t('impm_label_prune')) ?>
          </label>
          <button type="submit" class="btn btn-primary"><?= h(t('impm_btn_upload')) ?></button>
        <?= csrfField() ?></form>
      </div>
