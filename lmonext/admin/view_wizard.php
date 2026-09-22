<?php
/**
 * Project: LMOnext
 * Filename: view_wizard.php
 * Fileversion: 1.4.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Wizard: Liga erstellen ────────────────────────────────────────────────────
$stepLabels = [t('wiz_step_label_1'), t('wiz_step_label_2'), t('wiz_step_label_3'), t('wiz_step_label_4')];
?>
      <div class="wizard-steps">
<?php
        for ($s = 1; $s <= 4; $s++) {
            $cls = $s < $wizStepInt ? 'done' : ($s === $wizStepInt ? 'active' : ''); ?>
        <div class="wizard-step <?= $cls ?>"><?= $s < $wizStepInt ? '✓ ' : '' ?><?= h($stepLabels[$s - 1]) ?></div>
<?php
        } ?>
      </div>

<?php
        if ($wizStepInt === 1 && $wizStep !== "1b") { ?>
        <!-- ── Vorlagen ────────────────────────────────────────────────── -->
        <div class="card" style="margin-bottom:16px">
          <h2><?= h(t('wiz_quickstart_heading')) ?></h2>
          <p class="text-muted" style="font-size:.84rem;margin-bottom:14px"><?= h(t('wiz_quickstart_desc')) ?></p>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
<?php
          foreach (LIGA_TEMPLATES as $key => $tpl) { ?>
            <a href="?action=apply_template&tpl=<?= urlencode($key) ?>"
               style="display:block;text-decoration:none;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;transition:border-color .15s"
               onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
              <div style="font-size:1.5rem;margin-bottom:6px"><?= $tpl['icon'] ?></div>
              <div style="font-size:.9rem;font-weight:600;color:var(--text);margin-bottom:4px"><?= h(t($tpl['label'])) ?></div>
              <div style="font-size:.78rem;color:var(--muted);margin-bottom:6px"><?= h(t($tpl['beschreibung'])) ?></div>
              <div style="font-size:.74rem;color:var(--muted);line-height:1.4;border-top:1px solid var(--border);padding-top:8px;margin-top:4px"><?= h(t($tpl['detail'])) ?></div>
            </a>
<?php
          } ?>
          </div>
        </div>

        <div class="card">
          <h2><?= h(t('wiz_step1_heading')) ?></h2>
          <form method="post" action="?action=create_liga&step=2">
            <div class="form-group"><label><?= h(t('wiz_label_liga_name')) ?></label><input type="text" name="liga_name" placeholder="<?= h(t('wiz_placeholder_liga_name_example')) ?>" required autofocus></div>
<?php if (getAdminSetting('show_sport_type', '1') === '1') { ?>
            <div class="form-group">
              <label><?= h(t('ls_label_sportart')) ?></label>
              <select name="sport_type">
<?php foreach (\LMOnext\Sport\SportRegistry::all() as $sp) { ?>
                <option value="<?= h($sp->getKey()) ?>"<?= $sp->getKey() === 'football' ? ' selected' : '' ?>><?= h($sp->getLabel()) ?></option>
<?php } ?>
              </select>
            </div>
<?php } ?>
            <div class="form-row">
              <div class="form-group">
                <label><?= h(t('wiz_label_liga_type')) ?></label>
                <select name="liga_type" id="sel_type" onchange="updateKOFields()">
                  <option value="0"><?= h(t('dash_type_liga')) ?></option>
                  <option value="1"><?= h(t('wiz_option_ko_tournament')) ?></option>
                </select>
              </div>
              <!-- Liga: freie Anzahl -->
              <div class="form-group" id="field_liga_teams">
                <label><?= h(t('wiz_label_team_count')) ?></label>
                <input type="number" name="team_count_liga" id="inp_liga_teams" min="2" max="256" value="18">
              </div>
              <!-- KO: Dropdown mit 2^n + 24 -->
              <div class="form-group" id="field_ko_teams" style="display:none">
                <label><?= h(t('wiz_label_team_count')) ?></label>
                <select name="team_count_ko" id="sel_ko_teams" onchange="calcKORounds()">
                  <option value="2"><?= h(t('wiz_option_n_teams', ['n' => 2])) ?></option>
                  <option value="4"><?= h(t('wiz_option_n_teams', ['n' => 4])) ?></option>
                  <option value="8"><?= h(t('wiz_option_n_teams', ['n' => 8])) ?></option>
                  <option value="16" selected><?= h(t('wiz_option_n_teams', ['n' => 16])) ?></option>
                  <option value="24"><?= h(t('wiz_option_uefa_teams')) ?></option>
                  <option value="32"><?= h(t('wiz_option_n_teams', ['n' => 32])) ?></option>
                  <option value="64"><?= h(t('wiz_option_n_teams', ['n' => 64])) ?></option>
                  <option value="128"><?= h(t('wiz_option_n_teams', ['n' => 128])) ?></option>
                </select>
              </div>
            </div>
            <!-- KO: automatisch berechnete Runden -->
            <div id="field_ko_rounds" style="display:none">
              <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;margin-bottom:12px">
                <div style="font-size:.82rem;color:var(--muted);margin-bottom:8px"><?= h(t('wiz_auto_rounds_label')) ?></div>
                <div id="ko_round_preview" style="font-size:.85rem;line-height:1.8"></div>
              </div>
              <input type="hidden" name="round_count" id="inp_round_count" value="4">
            </div>
            <button type="submit" class="btn btn-primary"><?= h(t('common_next')) ?></button>
          <?= csrfField() ?></form>
        </div>
        <script>
        const koRoundNames = {
          0: <?= json_encode(t('wiz_round_finale')) ?>, 1: <?= json_encode(t('wiz_round_halbfinale')) ?>, 2: <?= json_encode(t('wiz_round_viertelfinale')) ?>,
          3: <?= json_encode(t('wiz_round_achtelfinale')) ?>, 4: <?= json_encode(t('wiz_round_sechzehntelfinale')) ?>, 5: <?= json_encode(t('wiz_round_32tel')) ?>, 6: <?= json_encode(t('wiz_round_64tel')) ?>
        };
        const koModi = {
          1: <?= json_encode(t('ko_mode_1')) ?>, 2: <?= json_encode(t('ko_mode_2')) ?>, 3: <?= json_encode(t('ko_mode_3')) ?>, 5: <?= json_encode(t('ko_mode_5')) ?>, 7: <?= json_encode(t('ko_mode_7')) ?>
        };
        const i18nWizRoundNTpl   = <?= json_encode(t('wiz_round_n')) ?>;
        const i18nPairingOne     = <?= json_encode(t('wiz_pairing_word_one')) ?>;
        const i18nPairingMany    = <?= json_encode(t('wiz_pairing_word_many')) ?>;

        function calcKORounds() {
          const n    = parseInt(document.getElementById('sel_ko_teams').value);
          const prev = document.getElementById('ko_round_preview');
          const inp  = document.getElementById('inp_round_count');
          let rounds = [], html = '';

          if (n === 24) {
            // UEFA-Sondermodus: 5 Runden
            rounds = [
              {r:1, name:<?= json_encode(t('wiz_round_zwischenrunde')) ?>, teams:16, paare:8,  modus:2},
              {r:2, name:<?= json_encode(t('wiz_round_achtelfinale')) ?>,  teams:16, paare:8,  modus:2},
              {r:3, name:<?= json_encode(t('wiz_round_viertelfinale')) ?>, teams:8,  paare:4,  modus:2},
              {r:4, name:<?= json_encode(t('wiz_round_halbfinale')) ?>,    teams:4,  paare:2,  modus:2},
              {r:5, name:<?= json_encode(t('wiz_round_finale')) ?>,        teams:2,  paare:1,  modus:1},
            ];
            inp.value = 5;
          } else {
            // Standard: log2(n) Runden
            const nRounds = Math.round(Math.log2(n));
            inp.value = nRounds;
            for (let r = 1; r <= nRounds; r++) {
              const fromEnd = nRounds - r;
              const name    = koRoundNames[fromEnd] ?? i18nWizRoundNTpl.replace('{n}', r);
              const teams   = n / Math.pow(2, r - 1);
              const modus   = (fromEnd === 0) ? 1 : 1; // Standard: Einzelspiel (änderbar in Schritt 3)
              rounds.push({r, name, teams, paare: teams/2, modus});
            }
          }

          html = rounds.map(rd =>
            `<div style="display:flex;gap:12px;padding:4px 0;border-bottom:1px solid var(--border)">
              <span style="color:var(--muted);min-width:24px">${rd.r}.</span>
              <span style="font-weight:500;min-width:160px">${rd.name}</span>
              <span style="color:var(--muted)">${rd.paare} ${rd.paare!==1?i18nPairingMany:i18nPairingOne} · ${koModi[rd.modus]}</span>
            </div>`
          ).join('');
          prev.innerHTML = html || '<span style="color:var(--muted)">–</span>';
        }

        function updateKOFields() {
          const isKO = document.getElementById('sel_type').value === '1';
          document.getElementById('field_liga_teams').style.display  = isKO ? 'none'  : '';
          document.getElementById('field_ko_teams').style.display    = isKO ? ''      : 'none';
          document.getElementById('field_ko_rounds').style.display   = isKO ? ''      : 'none';
          if (isKO) calcKORounds();
        }
        updateKOFields();
        </script>

<?php
        } elseif ($wizStep === "1b") {
            $wiz = $_SESSION['wiz'] ?? null;
            $tpl = LIGA_TEMPLATES[$wiz['tpl_key'] ?? ''] ?? null; ?>
        <div class="card">
          <h2><?= h(t('wiz_template_heading_prefix')) ?><?= $tpl ? $tpl['icon'].' '.h(t($tpl['label'])) : h(t('wiz_template_fallback')) ?></h2>
          <?php if ($tpl) { ?>
          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;margin-bottom:16px;font-size:.84rem;color:var(--muted)">
            <strong style="color:var(--text)"><?= h(t($tpl['beschreibung'])) ?></strong><br>
            <span style="font-size:.78rem;margin-top:4px;display:block"><?= h(t($tpl['detail'])) ?></span>
          </div>
          <?php } ?>
          <form method="post" action="?action=create_liga&step=1b">
            <div class="form-group">
              <label><?= h(t('wiz_label_liga_name')) ?></label>
              <input type="text" name="liga_name" placeholder="<?= h(t('wiz_placeholder_liga_name_example2')) ?>" required autofocus
                     value="<?= $tpl ? h(t($tpl['label'])) : '' ?>">
            </div>
            <div style="display:flex;gap:10px">
              <a href="?action=create_liga&step=1" class="btn btn-muted"><?= h(t('wiz_back_other_template')) ?></a>
              <button type="submit" class="btn btn-primary"><?= h(t('wiz_next_edit_teams')) ?></button>
            </div>
          <?= csrfField() ?></form>
        </div>

<?php
        } elseif ($wizStepInt === 2 && $wiz) { ?>
        <div class="card">
          <h2><?= h(t('wiz_step2_heading', ['n' => (int)$wiz['team_count']])) ?></h2>
          <p class="text-muted" style="font-size:.85rem;margin-bottom:16px"><?= h(t('wiz_step2_desc')) ?></p>
          <form method="post" action="?action=create_liga&step=3">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:6px;margin-bottom:8px;font-size:.78rem;color:var(--muted);padding:0 2px">
              <span><?= h(t('wiz_col_teamname')) ?></span><span><?= h(t('teams_field_mittel')) ?></span><span><?= h(t('teams_field_kurz')) ?></span>
            </div>

<?php
            for ($i = 0; $i < $wiz['team_count']; $i++) {
                $saved   = $wiz['teams'][$i] ?? [];
                $default = $saved['name'] ?? sprintf('Team %02d', $i + 1); ?>
            <div class="form-row-3" style="margin-bottom:8px">
              <input type="text" name="team_name_<?= $i ?>" value="<?= h($default) ?>" required <?= $i === 0 ? 'autofocus' : '' ?>>
              <input type="text" name="team_mittel_<?= $i ?>" placeholder="<?= h(t('wiz_placeholder_kurzname')) ?>" value="<?= h($saved['mittel'] ?? '') ?>">
              <input type="text" name="team_kurz_<?= $i ?>" placeholder="<?= h(t('wiz_placeholder_kurzel')) ?>" maxlength="6" value="<?= h($saved['kurz'] ?? '') ?>">
            </div>

<?php
            } ?>
            <div style="display:flex;gap:10px;margin-top:8px">
              <a href="?action=create_liga&step=1" class="btn btn-muted"><?= h(t('common_back')) ?></a>
              <button type="submit" class="btn btn-primary"><?= h(t('common_next')) ?></button>
            </div>
          <?= csrfField() ?></form>
        </div>

<?php
        } elseif ($wizStepInt === 3 && $wiz && $wiz['type'] === 0) {
            $spieltage    = $wiz['spieltage'];
            $teams        = $wiz['teams'];
            $hinRunden    = count($spieltage) / 2;
            $scheduleMode = $wiz['schedule_mode'] ?? 'leaguekey';
            $hasLeagueKey = getLeagueKeyPattern(count($teams)) !== null; ?>
        <div class="card" style="margin-bottom:16px">
          <h2><?= h(t('wiz_schedule_mode_heading')) ?></h2>
          <form method="post" action="?action=create_liga&step=3&regen=1">
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:14px;font-size:.87rem">
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="radio" name="schedule_mode" value="leaguekey"<?= $scheduleMode === 'leaguekey' ? ' checked' : '' ?><?= $hasLeagueKey ? '' : ' disabled' ?>>
                <?= h(t('wiz_schedule_mode_leaguekey')) ?><?= $hasLeagueKey ? '' : ' <span style="color:var(--muted);font-size:.8em">(' . h(t('wiz_schedule_mode_unavailable')) . ')</span>' ?>
              </label>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="radio" name="schedule_mode" value="random"<?= $scheduleMode === 'random' ? ' checked' : '' ?>>
                <?= h(t('wiz_schedule_mode_random')) ?>
              </label>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="radio" name="schedule_mode" value="none"<?= $scheduleMode === 'none' ? ' checked' : '' ?>>
                <?= h(t('wiz_schedule_mode_none')) ?>
              </label>
            </div>
            <button type="submit" class="btn btn-muted btn-sm"><?= h(t('wiz_schedule_mode_apply')) ?></button>
          <?= csrfField() ?></form>
        </div>
        <div class="card">
          <h2><?= h(t('wiz_step3_liga_heading')) ?></h2>
          <p class="text-muted" style="font-size:.85rem;margin-bottom:16px">
            <?= h(t('wiz_step3_liga_desc', ['days' => count($spieltage), 'matches' => count($spieltage[1] ?? [])])) ?>
          </p>
          <div class="preview-scroll">
            <table class="tbl">
              <thead><tr><th style="width:60px"><?= h(t('wiz_col_hash')) ?></th><th><?= h(t('sp_col_heim')) ?></th><th>–</th><th><?= h(t('sp_col_gast')) ?></th></tr></thead>
              <tbody>
<?php
            foreach ($spieltage as $nr => $pairs) {
                $isRueck = $nr > $hinRunden; ?>
                <tr><td colspan="4" style="background:var(--surface2);font-size:.78rem;color:var(--muted);padding:6px 12px">
                  <?= h(t('sp_label_matchday')) ?> <?= $nr ?><?= $isRueck ? h(t('wiz_matchday_second_half')) : h(t('wiz_matchday_first_half')) ?>
                </td></tr>
<?php
                foreach ($pairs as [$h, $g]) {
                    $heimName = $h >= 0 ? $teams[$h]['name'] : '___';
                    $gastName = $g >= 0 ? $teams[$g]['name'] : '___'; ?>
                <tr>
                  <td class="text-muted" style="font-size:.8rem"><?= $nr ?></td>
                  <td><?= h($heimName) ?></td>
                  <td class="text-muted" style="font-size:.8rem;text-align:center">vs</td>
                  <td><?= h($gastName) ?></td>
                </tr>
<?php
                }
            } ?>
              </tbody>
            </table>
          </div>
        </div>
        <form method="post" action="?action=create_liga&step=4" style="display:flex;gap:10px">
          <a href="?action=create_liga&step=2" class="btn btn-muted"><?= h(t('wiz_back_edit_teams')) ?></a>
          <button type="submit" class="btn btn-success"><?= h(t('common_save_liga')) ?></button>
        <?= csrfField() ?></form>

<?php
        } elseif ($wizStepInt === 3 && $wiz && $wiz['type'] === 1) {
            $teams      = $wiz['teams'];
            $nRounds    = (int)$wiz['round_count'];
            $teamCount  = count($teams);
            $isUefa24   = ($teamCount === 24);

            // Rundenbezeichnungen + Standard-Modi
            $koRoundDefs = [];
            if ($isUefa24) {
                $koRoundDefs = [
                    1 => ['name'=>t('wiz_round_zwischenrunde'),  'modus'=>2],
                    2 => ['name'=>t('wiz_round_achtelfinale'),    'modus'=>2],
                    3 => ['name'=>t('wiz_round_viertelfinale'),   'modus'=>2],
                    4 => ['name'=>t('wiz_round_halbfinale'),      'modus'=>2],
                    5 => ['name'=>t('wiz_round_finale'),          'modus'=>1],
                ];
            } else {
                $nameMap = [0=>t('wiz_round_finale'),1=>t('wiz_round_halbfinale'),2=>t('wiz_round_viertelfinale'),3=>t('wiz_round_achtelfinale'),4=>t('wiz_round_sechzehntelfinale'),5=>t('wiz_round_32tel'),6=>t('wiz_round_64tel')];
                for ($r = 1; $r <= $nRounds; $r++) {
                    $fromEnd = $nRounds - $r;
                    $koRoundDefs[$r] = [
                        'name'  => $nameMap[$fromEnd] ?? t('wiz_round_n', ['n' => $r]),
                        'modus' => ($fromEnd === 0) ? 1 : 1, // Standard Einzelspiel, änderbar
                    ];
                }
            } ?>
        <div class="card">
          <h2><?= t('wiz_step3_ko_heading') ?></h2>
          <?php if ($isUefa24) { ?>
          <div style="background:#3b82f618;border:1px solid #3b82f644;border-radius:var(--radius);padding:10px 14px;margin-bottom:16px;font-size:.83rem;color:#93c5fd">
            <?= t('wiz_uefa24_note') ?>
          </div>
          <?php } ?>
          <p class="text-muted" style="font-size:.85rem;margin-bottom:16px">
            <?= h(t('wiz_step3_ko_desc')) ?>
          </p>
          <form method="post" action="?action=create_liga&step=4" id="koform">
<?php
            for ($r = 1; $r <= $nRounds; $r++) {
                $roundModi  = $wiz['round_modi'] ?? [];
                $def        = $koRoundDefs[$r];
                $savedModus = (int)($wiz['spieltage'][$r]['modus'] ?? $roundModi[$r-1] ?? $def['modus']);
                // Anzahl Paarungen für Hinweis
                if ($isUefa24) {
                    $nPaare = [1=>8,2=>8,3=>4,4=>2,5=>1][$r] ?? 1;
                } else {
                    $nPaare = (int)(count($teams) / pow(2, $r));
                }
                $nPaare = max(1, $nPaare);
                ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;
                        background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px">
              <div style="flex:1">
                <strong style="font-size:.9rem"><?= h($def['name']) ?></strong>
                <span style="font-size:.78rem;color:var(--muted);margin-left:10px">
                  <?= $nPaare ?> <?= $nPaare !== 1 ? h(t('wiz_pairing_word_many')) : h(t('wiz_pairing_word_one')) ?> <?= h(t('wiz_dummy_teams_label')) ?>
                </span>
              </div>
              <div style="display:flex;align-items:center;gap:6px">
                <label style="font-size:.78rem;color:var(--muted);white-space:nowrap"><?= h(t('wiz_label_spielmodus')) ?></label>
                <select name="rnd_<?= $r ?>_modus"
                        style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.85rem">
<?php
                foreach (KO_MODUS as $val => $label) { ?>
                  <option value="<?= $val ?>"<?= $val === $savedModus ? ' selected' : '' ?>><?= h(koModusLabel($val)) ?></option>
<?php
                } ?>
                </select>
              </div>
            </div>
<?php
            } ?>
            <div style="margin-top:14px;padding:12px 16px;background:#3b82f618;border:1px solid #3b82f644;border-radius:var(--radius);font-size:.82rem;color:#93c5fd">
              <?= t('wiz_dummy_info') ?>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px">
              <a href="?action=create_liga&step=2" class="btn btn-muted"><?= h(t('wiz_back_edit_teams')) ?></a>
              <button type="submit" class="btn btn-success"><?= h(t('common_save_liga')) ?></button>
            </div>
          <?= csrfField() ?></form>
        </div>

<?php
        } else { ?>
        <div class="card">
          <p class="text-muted"><?= h(t('wiz_session_expired')) ?></p>
          <a href="?action=create_liga&step=1" class="btn btn-primary" style="margin-top:12px"><?= h(t('wiz_restart')) ?></a>
        </div>
<?php } ?>
