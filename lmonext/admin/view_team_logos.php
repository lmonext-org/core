<?php
/**
 * Project: LMOnext
 * Filename: view_team_logos.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * View: Massen-Upload von Team-Logos (siehe handler_team_logos.php).
 * Drei Zustände: Auswahl / Zuordnung prüfen / Ergebnis.
 */

$inpSt = 'background:var(--bg);border:1px solid var(--border);color:var(--text);'
       . 'border-radius:var(--radius);padding:6px 10px;font-size:.85rem';

// ═══ Zustand 3: Ergebnis ═══════════════════════════════════════════════════
$tlbDone = $_SESSION['team_logos_done'] ?? null;
if ($action === 'team_logos' && !empty($_GET['done']) && is_array($tlbDone)) {
    unset($_SESSION['team_logos_done']);
    $labels = [
        'ok'      => ['✅', t('tlb_st_ok')],
        'skip'    => ['⏭️', t('tlb_st_skip')],
        'noteam'  => ['⚠️', t('tlb_st_noteam')],
        'dup'     => ['⚠️', t('tlb_st_dup')],
        'haslogo' => ['⏭️', t('tlb_st_haslogo')],
        'error'   => ['❌', t('tlb_st_error')],
    ]; ?>
      <div class="card" style="max-width:820px">
        <h2>✅ <?= h(t('tlb_done_title', ['n' => (int)$tlbDone['ok']])) ?></h2>
        <div style="max-height:460px;overflow-y:auto;margin-top:12px">
          <table class="tbl">
            <thead><tr><th><?= h(t('tlb_col_file')) ?></th><th><?= h(t('tlb_col_team')) ?></th><th><?= h(t('tlb_col_status')) ?></th></tr></thead>
            <tbody>
<?php foreach ($tlbDone['results'] as $r) { $l = $labels[$r['status']] ?? ['', $r['status']]; ?>
              <tr>
                <td style="font-size:.84rem"><?= h($r['file']) ?></td>
                <td style="font-size:.84rem"><?= h($r['team'] !== '' ? $r['team'] : '–') ?></td>
                <td style="font-size:.82rem"><?= $l[0] ?> <?= h($l[1]) ?><?= $r['msg'] !== '' ? ' – ' . h($r['msg']) : '' ?></td>
              </tr>
<?php } ?>
            </tbody>
          </table>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn btn-primary btn-sm" style="text-decoration:none" href="?action=teams"><?= h(t('tlb_btn_to_teams')) ?></a>
          <a class="btn btn-muted btn-sm" style="text-decoration:none" href="?action=team_logos"><?= h(t('tlb_btn_again')) ?></a>
        </div>
      </div>
<?php
    return;
}

// ═══ Zustand 2: Zuordnung prüfen ═══════════════════════════════════════════
if ($action === 'team_logos_review') {
    $s = $_SESSION['team_logos'] ?? null;
    if (!is_array($s)) { redirect('?action=team_logos'); }
    $teams = getDB()->query('SELECT id,name FROM ' . tbl('teams_global') . ' ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    $teamName = [];
    foreach ($teams as $t) { $teamName[(int)$t['id']] = (string)$t['name']; }
    $label = static fn(int $id) => ($teamName[$id] ?? '?') . ' [' . $id . ']';
    $mime = ['svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif'];
    $cntMatched = 0;
    foreach ($s['match'] as $m) { if ($m['team_id'] !== null) { $cntMatched++; } } ?>
      <datalist id="tlb-teams">
<?php foreach ($teams as $t) { ?>
        <option value="<?= h($t['name'] . ' [' . (int)$t['id'] . ']') ?>"></option>
<?php } ?>
      </datalist>
      <div class="card">
        <h2><?= h(t('tlb_review_heading')) ?></h2>
        <p class="text-muted" style="font-size:.86rem;margin-bottom:6px">
          <?= h(t('tlb_review_counts', ['n' => count($s['files']), 'm' => $cntMatched])) ?>
<?php if (!empty($s['skipped'])) { ?>
          · <?= h(t('tlb_review_skipped', ['n' => (int)$s['skipped']])) ?>
<?php } ?>
        </p>
        <p class="text-muted" style="font-size:.84rem;margin-bottom:14px"><?= h(t('tlb_review_intro')) ?></p>

        <form method="post" action="?action=team_logos_apply">
          <div style="max-height:620px;overflow-y:auto;margin-bottom:14px">
          <table class="tbl">
            <thead><tr>
              <th style="width:40px"><input type="checkbox" id="tlb-all" checked title="<?= h(t('tlb_toggle_all')) ?>"></th>
              <th style="width:64px"></th>
              <th><?= h(t('tlb_col_file')) ?></th>
              <th><?= h(t('tlb_col_target')) ?></th>
              <th><?= h(t('tlb_col_match')) ?></th>
            </tr></thead>
            <tbody>
<?php foreach ($s['files'] as $i => $f) {
    $m = $s['match'][$i] ?? ['team_id' => null, 'how' => 'none', 'cands' => []];
    $tid = $m['team_id'];
    $preset = $tid !== null ? $label((int)$tid) : '';
    $hasLogo = $tid !== null && findTeamLogoPath((int)$tid) !== null;
    $ext = strtolower(pathinfo($f['orig'], PATHINFO_EXTENSION));
    $thumb = '';
    if ($f['size'] <= 150 * 1024) {
        $raw = @file_get_contents($s['dir'] . '/' . $f['stored']);
        if ($raw !== false) { $thumb = 'data:' . ($mime[$ext] ?? 'image/png') . ';base64,' . base64_encode($raw); }
    }
    $howLbl = match ($m['how']) {
        'exact'     => '<span style="color:var(--green,#3fb950)">✔ ' . h(t('tlb_how_exact')) . '</span>',
        'fuzzy'     => '<span style="color:var(--yellow,#d29922)">≈ ' . h(t('tlb_how_fuzzy')) . '</span>',
        'ambiguous' => '<span style="color:var(--yellow,#d29922)">? ' . h(t('tlb_how_ambiguous')) . '</span>',
        default     => '<span class="text-muted">– ' . h(t('tlb_how_none')) . '</span>',
    }; ?>
              <tr>
                <td><input type="checkbox" class="tlb-use" name="use[<?= (int)$i ?>]" value="1"<?= $tid !== null ? ' checked' : '' ?>></td>
                <td><?php if ($thumb !== '') { ?><img src="<?= h($thumb) ?>" alt="" style="max-height:36px;max-width:56px;object-fit:contain"><?php } ?></td>
                <td style="font-size:.84rem">
                  <?= h($f['orig']) ?>
                  <input type="hidden" name="file[<?= (int)$i ?>]" value="<?= h($f['stored']) ?>">
                </td>
                <td>
                  <input type="text" name="team[<?= (int)$i ?>]" list="tlb-teams" value="<?= h($preset) ?>"
                         placeholder="<?= h(t('tlb_team_placeholder')) ?>" style="<?= $inpSt ?>;width:100%;min-width:260px">
<?php if (!empty($m['cands']) && $m['how'] === 'ambiguous') { ?>
                  <div class="text-muted" style="font-size:.72rem"><?= h(t('tlb_candidates')) ?>
                    <?= h(implode(' · ', array_map(static fn($c) => $label((int)$c), $m['cands']))) ?></div>
<?php } ?>
                </td>
                <td style="font-size:.8rem;white-space:nowrap"><?= $howLbl ?><?= $hasLogo ? ' <span class="text-muted">(' . h(t('tlb_has_logo')) . ')</span>' : '' ?></td>
              </tr>
<?php } ?>
            </tbody>
          </table>
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-size:.86rem;margin-bottom:8px;cursor:pointer">
            <input type="checkbox" name="overwrite" value="1"> <?= h(t('tlb_label_overwrite')) ?>
          </label>
<?php if (!empty($s['inbox'])) { ?>
          <label style="display:flex;align-items:center;gap:8px;font-size:.86rem;margin-bottom:14px;cursor:pointer">
            <input type="checkbox" name="delete_done" value="1" checked> <?= h(t('tlb_label_delete_done')) ?>
          </label>
<?php } ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:12px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-success btn-sm">✅ <?= h(t('tlb_btn_apply')) ?></button>
            <a class="btn btn-muted btn-sm" style="text-decoration:none" href="?action=team_logos_cancel"><?= h(t('tlb_btn_cancel')) ?></a>
          </div>
        <?= csrfField() ?></form>
      </div>
      <script>
        document.getElementById('tlb-all').addEventListener('change', function () {
          document.querySelectorAll('.tlb-use').forEach(function (c) { c.checked = this.checked; }, this);
        });
      </script>
<?php
    return;
}

// ═══ Zustand 1: Auswahl ════════════════════════════════════════════════════
$inboxDir = tlbInboxDir();
$inboxCnt = count(tlbListDir($inboxDir, true));
$maxUploads = (int)ini_get('max_file_uploads');
?>
      <div class="card" style="max-width:820px">
        <h2>🖼️ <?= h(t('tlb_heading')) ?></h2>
        <p class="text-muted" style="font-size:.86rem;margin-bottom:12px"><?= h(t('tlb_intro')) ?></p>
        <p class="text-muted" style="font-size:.82rem;margin-bottom:16px"><?= h(t('tlb_formats', ['px' => TEAM_LOGO_MIN_HEIGHT_PX, 'mb' => (int)(TLB_MAX_FILE_BYTES / 1048576)])) ?></p>

        <form method="post" action="?action=team_logos_upload" enctype="multipart/form-data" style="margin-bottom:20px">
          <input type="hidden" name="source" value="files">
          <h3 style="font-size:.95rem;margin-bottom:6px">1. <?= h(t('tlb_src_zip')) ?></h3>
          <p class="text-muted" style="font-size:.8rem;margin-bottom:6px"><?= h(t('tlb_src_zip_hint')) ?></p>
          <input type="file" name="zipfile" accept=".zip" style="display:block;margin-bottom:12px">
          <h3 style="font-size:.95rem;margin-bottom:6px">2. <?= h(t('tlb_src_files')) ?></h3>
          <p class="text-muted" style="font-size:.8rem;margin-bottom:6px"><?= h(t('tlb_src_files_hint', ['n' => $maxUploads > 0 ? $maxUploads : 20])) ?></p>
          <input type="file" name="logos[]" accept=".svg,.png,.jpg,.jpeg,.gif" multiple style="display:block;margin-bottom:12px">
          <button type="submit" class="btn btn-primary"><?= h(t('tlb_btn_upload')) ?></button>
        <?= csrfField() ?></form>

        <form method="post" action="?action=team_logos_upload" style="padding-top:16px;border-top:1px solid var(--border)">
          <input type="hidden" name="source" value="inbox">
          <h3 style="font-size:.95rem;margin-bottom:6px">3. <?= h(t('tlb_src_inbox')) ?></h3>
          <p class="text-muted" style="font-size:.8rem;margin-bottom:6px"><?= h(t('tlb_src_inbox_hint')) ?></p>
          <p style="font-size:.84rem;margin-bottom:10px"><code>store/logo_inbox/</code> – <?= h(t('tlb_inbox_count', ['n' => $inboxCnt])) ?></p>
          <button type="submit" class="btn btn-muted"<?= $inboxCnt === 0 ? ' disabled' : '' ?>><?= h(t('tlb_btn_inbox')) ?></button>
        <?= csrfField() ?></form>
      </div>
