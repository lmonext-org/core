<?php
/**
 * Project: LMOnext
 * Filename: view_tabelle.php
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

// ── View: Tabelle ──────────────────────────────────────────────────────────
        $lid  = (int)($_GET['liga_id'] ?? 0);
        $liga = $tabelleData['liga'];
        $tab  = $tabelleData['tabelle'];
        $opts = $tabelleData['opts'];
        $ptW  = (int)($opts['PointsForWin']  ?? 3);
        $ptD  = (int)($opts['PointsForDraw'] ?? 1);?>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
        <a href="?action=liga_detail&id=<?= $lid ?>" class="back-link" style="margin-bottom:0">← <?= h($liga['name']) ?></a>
        <a href="?action=spieltag&liga_id=<?= $lid ?>&nr=1" class="btn btn-muted btn-sm" style="margin-left:auto"><?= h(t('tab_btn_results')) ?></a>
        <a href="?action=export&liga_id=<?= $lid ?>" class="btn btn-muted btn-sm"><?= h(t('tab_btn_export')) ?></a>
      </div>
      <div class="card">
        <h2><?= h(t('tab_heading', ['name' => $liga['name']])) ?></h2>
        <p class="text-muted" style="font-size:.8rem;margin-bottom:14px"><?= h(t('tab_scoring_line', ['win' => $ptW, 'draw' => $ptD])) ?></p>
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:36px">#</th>
              <th>Team</th>
              <th style="width:40px;text-align:center" title="<?= h(t('tab_tooltip_sp')) ?>"><?= h(t('tab_col_sp')) ?></th>
              <th style="width:40px;text-align:center" title="<?= h(t('tab_tooltip_s')) ?>"><?= h(t('tab_col_s')) ?></th>
              <th style="width:40px;text-align:center" title="<?= h(t('tab_tooltip_u')) ?>"><?= h(t('tab_col_u')) ?></th>
              <th style="width:40px;text-align:center" title="<?= h(t('tab_tooltip_n')) ?>"><?= h(t('tab_col_n')) ?></th>
              <th style="width:70px;text-align:center"><?= h(t('sp_col_tore')) ?></th>
              <th style="width:50px;text-align:center" title="<?= h(t('tab_tooltip_diff')) ?>"><?= h(t('tab_col_diff')) ?></th>
              <th style="width:50px;text-align:center"><?= h(t('tab_col_pkt')) ?></th>
            </tr>
          </thead>
          <tbody>
<?php
        foreach ($tab as $pos => $row) {
            $diff = $row['tore_h'] - $row['tore_g']; ?>
            <tr>
              <td class="text-muted" style="font-size:.8rem"><?= $pos + 1 ?></td>
              <td style="font-weight:500"><?= h($row['name']) ?></td>
              <td style="text-align:center"><?= $row['sp'] ?></td>
              <td style="text-align:center;color:var(--green)"><?= $row['g'] ?></td>
              <td style="text-align:center;color:var(--muted)"><?= $row['u'] ?></td>
              <td style="text-align:center;color:var(--red)"><?= $row['v'] ?></td>
              <td style="text-align:center"><?= $row['tore_h'] ?>:<?= $row['tore_g'] ?></td>
              <td style="text-align:center;color:<?= $diff > 0 ? 'var(--green)' : ($diff < 0 ? 'var(--red)' : 'var(--muted)') ?>">
                <?= $diff > 0 ? '+' : '' ?><?= $diff ?>
              </td>
              <td style="text-align:center;font-weight:700;color:var(--accent)"><?= $row['pkt'] ?></td>
            </tr>
<?php
        } ?>
          </tbody>
        </table>
        <?php if (empty($tab)) { ?><p class="text-muted" style="font-size:.9rem;margin-top:8px"><?= h(t('tab_empty')) ?></p><?php } ?>
      </div>

