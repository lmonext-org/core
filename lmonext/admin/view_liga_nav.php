<?php
/**
 * Project: LMOnext
 * Filename: view_liga_nav.php
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

// ── Partial: gemeinsamer Liga-Navigationsblock ─────────────────────────────
// Zeigt Liganame/-typ, ID/Erstellt-Datum sowie die Aktions-Buttons
// (Einstellungen, Teams, Tabelle, Ergebnisse eintragen, Export, Spieler-
// statistik, Archivieren, Spieltage-Übersicht) und ggf. den Hinweis auf
// fehlende KO-Runden. Wird sowohl auf der Spieltage-Übersicht
// (view_liga_spieltage.php) als auch auf der Ergebniseingabe eines
// einzelnen Spieltags (view_spieltag.php) eingebunden.
//
// Erwartete Variablen aus dem aufrufenden View:
//   $navLid            int    Liga-ID
//   $navLigaName        string Liganame
//   $navLigaDatum       string Erstellt-Datum (Roh-DB-Wert)
//   $navLtype           int    Liga-Typ (1 = KO)
//   $navExpectedRounds  int    erwartete Rundenanzahl (liga_options "Rounds")
//   $navActualRounds    int    tatsächlich angelegte Spieltage/Runden
$lid   = $navLid;
$ltype = $navLtype;
?>
      <div class="card">
        <h2><?= h($navLigaName) ?>
          &nbsp;<?= $ltype === 1 ? '<span class="chip chip-yellow">'.h(t('dash_type_ko')).'</span>' : '<span class="chip chip-blue">'.h(t('dash_type_liga')).'</span>' ?>
        </h2>
        <p class="text-muted" style="font-size:.85rem"><?= h(t('ld_id_created', ['id' => $lid, 'datum' => $navLigaDatum])) ?></p>
        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
          <a href="?action=liga_spieltage&id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_overview')) ?></a>
          <a href="?action=liga_settings&id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_settings')) ?></a>
          <a href="?action=liga_settings&id=<?= $lid ?>&tab=teams" class="btn btn-muted"><?= h(t('ld_btn_teams')) ?></a>
<?php if ($ltype !== 1) { ?>
          <a href="?action=tabelle&liga_id=<?= $lid ?>" class="btn btn-primary"><?= h(t('sp_btn_table')) ?></a>
<?php } ?>
          <a href="?action=liga_detail&id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_enter_results')) ?></a>
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
                if (!function_exists('renderArchivDdFolders')) {
                    function renderArchivDdFolders(array $byParent, int $lid, int $parentId = 0, int $depth = 0): void {
                        if (empty($byParent[$parentId])) return;
                        if ($depth > 0 && $parentId !== 0) { ?>
              <div style="height:1px;background:var(--border);margin:2px 10px"></div>
<?php               }
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
<?php               renderArchivDdFolders($byParent, $lid, (int)$af['id'], $depth + 1);
                        }
                    }
                }
                renderArchivDdFolders($byParent, $lid);
            }
        } catch (Throwable) {}
?>
            </div>
          </div>
<?php
        if ($ltype === 1 && $navExpectedRounds > $navActualRounds) { ?>
          <form method="post" action="?action=fix_ko_rounds" style="display:inline">
            <input type="hidden" name="liga_id" value="<?= $lid ?>">
            <button type="submit" class="btn btn-muted" style="border-color:var(--yellow);color:var(--yellow)">
              <?= h(t('ld_btn_fix_rounds', ['n' => $navExpectedRounds - $navActualRounds])) ?>
            </button>
          <?= csrfField() ?></form>
<?php
        } ?>
        </div>
      </div>
      <script>
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
