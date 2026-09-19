<?php
/**
 * Project: LMOnext
 * Filename: view_import_review.php
 * Fileversion: 1.2.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Import-Abgleich (ungefähre Teamnamen-Treffer bestätigen, und/oder
// erkannte Sportart bestätigen/übersteuern) ─────────────────────────────────
$ambiguous  = $_SESSION['import_pending_ambiguous'] ?? [];
$parsedList = $_SESSION['import_pending'] ?? null;
if ($parsedList === null) {
    // Direkter Aufruf ohne vorherigen Upload, oder Session abgelaufen
    redirect('?action=import');
}

// Nach Datei gruppieren, für eine übersichtlichere Anzeige
$byFile = [];
foreach ($ambiguous as $amb) {
    $byFile[$amb['fileIdx']]['fileName'] = $amb['fileName'];
    $byFile[$amb['fileIdx']]['items'][]  = $amb;
}

// Sportarten-Optionen für das Dropdown je Datei (Beitrag: Torsten Hofmann
// für das Sport-Profile-Feature, hier für die .l98-Import-Bestätigung
// genutzt) - "Vorschlag" markiert die automatisch erkannte Sportart
// (siehe l98DetectSportType()), der Admin kann sie hier übersteuern.
$sportOptions = \LMOnext\Sport\SportRegistry::all();
?>
      <div class="card" style="max-width:820px">
        <h2><?= h(t('imp_review_heading', ['n' => count($ambiguous)])) ?></h2>
        <p style="color:var(--muted);font-size:.86rem;margin-bottom:18px"><?= h(t('imp_review_intro')) ?></p>

        <form method="post" action="?action=import_confirm">
<?php foreach ($parsedList as $fileIdx => $entry) {
    $detected = $entry['data']['detectedSportType'] ?? 'football';
    $group = $byFile[$fileIdx] ?? null; ?>
          <div style="margin-bottom:22px">
            <div style="font-weight:600;font-size:.88rem;margin-bottom:8px;color:var(--text)">
              📄 <?= h($entry['fileName']) ?>
            </div>
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);
                        padding:10px 14px;margin-bottom:8px">
              <label for="sportType-<?= (int)$fileIdx ?>" style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('imp_review_sportart_label')) ?></label>
              <select id="sportType-<?= (int)$fileIdx ?>" name="sportType[<?= (int)$fileIdx ?>]"
                      style="width:100%;max-width:420px;background:var(--surface);border:1px solid var(--border);
                             color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.85rem">
<?php foreach ($sportOptions as $sp) { ?>
                <option value="<?= h($sp->getKey()) ?>"<?= $sp->getKey() === $detected ? ' selected' : '' ?>><?= h($sp->getLabel()) ?><?= $sp->getKey() === $detected ? ' (' . h(t('imp_review_erkannt')) . ')' : '' ?></option>
<?php } ?>
              </select>
            </div>
<?php if ($group !== null) {
    foreach ($group['items'] as $amb) {
        $selId = 'adopt-' . $amb['fileIdx'] . '-' . $amb['nr']; ?>
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);
                        padding:10px 14px;margin-bottom:8px">
              <div style="font-size:.85rem;line-height:1.5;margin-bottom:8px">
<?php       if (count($amb['candidates']) > 1) { ?>
                <?= t('imp_review_item_multi', [
                      'import' => '<strong>' . h($amb['importName']) . '</strong>',
                      'n'      => count($amb['candidates']),
                    ]) ?>
<?php       } else {
                $cand = $amb['candidates'][0]; ?>
                <?= t('imp_review_item', [
                      'import' => '<strong>' . h($amb['importName']) . '</strong>',
                      'db'     => '<strong>' . h($cand['name']) . '</strong>',
                      'id'     => (int)$cand['id'],
                    ]) ?>
<?php       } ?>
              </div>
              <label for="<?= h($selId) ?>" style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('imp_review_select_label')) ?></label>
              <select id="<?= h($selId) ?>" name="adopt[<?= (int)$amb['fileIdx'] ?>][<?= (int)$amb['nr'] ?>]"
                      style="width:100%;max-width:420px;background:var(--surface);border:1px solid var(--border);
                             color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.85rem">
<?php       foreach ($amb['candidates'] as $i => $cand) { ?>
                <option value="<?= (int)$cand['id'] ?>"<?= $i === 0 ? ' selected' : '' ?>>
                  <?= h($cand['name']) ?> (ID <?= (int)$cand['id'] ?>)<?= $cand['kurz'] !== '' ? ' · ' . h($cand['kurz']) : '' ?>
                </option>
<?php       } ?>
                <option value="0"><?= h(t('imp_review_option_new')) ?></option>
              </select>
            </div>
<?php   }
} ?>
          </div>
<?php } ?>
          <div style="display:flex;gap:8px;margin-top:10px;padding-top:14px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-success btn-sm"><?= h(t('imp_review_btn_confirm')) ?></button>
            <a href="?action=import_cancel" class="btn btn-muted btn-sm" style="text-decoration:none"><?= h(t('imp_review_btn_cancel')) ?></a>
          </div>
        <?= csrfField() ?></form>
      </div>
