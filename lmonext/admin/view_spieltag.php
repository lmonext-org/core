<?php
/**
 * Project: LMOnext
 * Filename: view_spieltag.php
 * Fileversion: 1.11.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Spieltag ──────────────────────────────────────────────────────────
// ── Hilfsfunktion: Datum + Zeit als zwei separate Felder (Firefox-kompatibel) ─
function dtInput(string $hiddenName, string $atVal, string $extraStyle = ''): string {
    $datePart = $atVal ? substr($atVal, 0, 10) : '';          // YYYY-MM-DD
    $timePart = $atVal ? substr($atVal, 11, 5) : '';          // HH:MM
    $uid      = 'dt_'.preg_replace('/[^a-z0-9]/i', '_', $hiddenName);
    $inpSt    = 'background:var(--surface2);border:1px solid var(--border);color:var(--text);'
              . 'border-radius:var(--radius);padding:4px 6px;font-size:.82rem;'.$extraStyle;
    return '<span style="display:inline-flex;align-items:center;gap:4px">'
         . '<input type="date" id="'.$uid.'_d" value="'.h($datePart).'" style="'.$inpSt.'" oninput="syncDt(\''.$uid.'\')">'
         . '<input type="time" id="'.$uid.'_t" value="'.h($timePart).'" style="'.$inpSt.';width:80px" oninput="syncDt(\''.$uid.'\')">'
         . '<input type="hidden" name="'.h($hiddenName).'" id="'.$uid.'_h" value="'.h($atVal).'">'
         . '</span>';
}
        $lid   = (int)($_GET['liga_id'] ?? 0);
        $stNr  = (int)($_GET['nr'] ?? 1);
        $liga  = $spieltagData['liga'];
        $st    = $spieltagData['spieltag'];
        $alle  = $spieltagData['alle'];
        $prev  = null; $next = null;
        foreach ($alle as $i => $nr) {
            if ($nr === $stNr) {
                $prev = $alle[$i - 1] ?? null;
                $next = $alle[$i + 1] ?? null;
            }
        }

        $navLid            = $lid;
        $navLigaName       = $liga['name'];
        $navLigaDatum      = $liga['datum'] ?? '';
        $navLtype          = (int)($spieltagData['liga_type'] ?? 0);
        $navExpectedRounds = (int)($spieltagData['expected_rounds'] ?? 0);
        $navActualRounds   = (int)($spieltagData['total_rounds'] ?? 0);
        require_once ADMIN_INC . '/view_liga_nav.php';
        ?>

      <!-- Schnellnavigation alle Spieltage/Runden -->
      <div class="card">
        <h2><?= ($spieltagData['liga_type'] ?? 0) === 1 ? h(t('sp_heading_all_rounds')) : h(t('sp_heading_all_matchdays')) ?></h2>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
<?php
            foreach ($alle as $nr) {
                $isCurrent = $nr === $stNr; ?>
          <a href="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= $nr ?>"
             style="display:inline-block;padding:4px 10px;border-radius:var(--radius);font-size:.8rem;text-decoration:none;
                    background:<?= $isCurrent ? 'var(--accent)' : 'var(--surface2)' ?>;
                    color:<?= $isCurrent ? '#fff' : 'var(--muted)' ?>;
                    border:1px solid var(--border)"><?= $nr ?></a>
<?php
            } ?>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <div style="display:flex;gap:8px">
          <?php if ($prev !== null) { ?><a href="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= $prev ?>" class="btn btn-muted btn-sm">← <?= $prev ?></a><?php } ?>
          <span style="padding:4px 12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);font-size:.85rem"><?= ($spieltagData['liga_type'] ?? 0) === 1 ? h(t('sp_label_round')) : h(t('sp_label_matchday')) ?> <?= $stNr ?></span>
          <?php if ($next !== null) { ?><a href="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= $next ?>" class="btn btn-muted btn-sm"><?= $next ?> →</a><?php } ?>
        </div>
        <?php if (($spieltagData['liga_type'] ?? 0) !== 1) { ?><a href="?action=tabelle&liga_id=<?= $lid ?>" class="btn btn-primary btn-sm"><?= h(t('sp_btn_table')) ?></a><?php } ?>
      </div>

<?php
        if (!$st) { ?>
        <div class="card"><p class="text-muted"><?= h(t('sp_not_found')) ?></p></div>
<?php
        } else { ?>
      <script>
      function syncDt(uid) {
        const d = document.getElementById(uid+'_d')?.value ?? '';
        const t = document.getElementById(uid+'_t')?.value ?? '00:00';
        document.getElementById(uid+'_h').value = d ? d+'T'+t : '';
      }
      </script>
      <div class="card">
<?php
        $allTeams     = $spieltagData['teams']        ?? [];
        $isKO         = ($spieltagData['liga_type']   ?? 0) === 1;
        $klFin        = $spieltagData['kl_fin']       ?? false;
        $totalRounds  = $spieltagData['total_rounds'] ?? 0;
        $isLastRound  = $isKO && $klFin && ($stNr === $totalRounds);
        $prevWinners  = $spieltagData['prevWinners']  ?? null;
        $dropdownTeams   = ($isKO && $prevWinners !== null) ? $prevWinners : $allTeams;
        $hasWinnerFilter = $isKO && $prevWinners !== null;
?>
        <h2><?= h($liga['name']) ?> – <?= $isKO ? h(t('sp_label_round')) : h(t('sp_label_matchday')) ?> <?= $stNr ?>
          <?php if ($isLastRound) { ?>
          <span style="font-size:.75rem;color:#fbbf24;font-weight:500;margin-left:8px"><?= h(t('sp_final_and_third')) ?></span>
          <?php } ?>
        </h2>

<?php

        if ($isKO) {
            // ── KO-Modus: dynamischer Paarungs-Editor ────────────────────────
            $currentModus = (int)($st['modus'] ?? KO_MODUS_DEFAULT);
            if (!isset(KO_MODUS[$currentModus])) { $currentModus = KO_MODUS_DEFAULT; }
            $paarungen = $spieltagData['paarungen'] ?? [];
            // Bei leerer Runde: eine leere Paarung vorblenden damit der Editor sofort bedienbar ist
            $paarungCount = count($paarungen);
            $paarungenLeer = ($paarungCount === 0);
            ?>

        <!-- Spielmodus-Auswahl -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;
                    background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px">
          <span style="font-size:.82rem;color:var(--muted);font-weight:500;white-space:nowrap"><?= h(t('sp_mode_label')) ?></span>
          <div style="display:flex;gap:6px;flex-wrap:wrap" id="modus-btns">
<?php
            foreach (KO_MODUS as $val => $label) {
                $active = $val === $currentModus; ?>
            <label data-modus="<?= $val ?>" style="display:flex;align-items:center;cursor:pointer;
                          background:<?= $active ? 'var(--accent)' : 'var(--bg)' ?>;
                          border:1px solid <?= $active ? 'var(--accent)' : 'var(--border)' ?>;
                          border-radius:var(--radius);padding:5px 12px;font-size:.83rem;
                          color:<?= $active ? '#fff' : 'var(--text)' ?>;transition:all .15s">
              <input type="radio" name="runde_modus_display" value="<?= $val ?>"
                     <?= $active ? 'checked' : '' ?> style="display:none"
                     onchange="koModusChange(this.value)">
              <?= h(koModusLabel($val)) ?>
            </label>
<?php
            } ?>
          </div>
        </div>

        <form method="post" action="?action=save_ko_runde" id="ko-form">
          <input type="hidden" name="liga_id"     value="<?= $lid ?>">
          <input type="hidden" name="spieltag_nr" value="<?= $stNr ?>">
          <input type="hidden" name="spieltag_id" value="<?= (int)$st['id'] ?>">
          <input type="hidden" name="runde_modus" id="ko-modus-val" value="<?= $currentModus ?>">
          <input type="hidden" name="pair_count"  id="ko-pair-count" value="<?= $paarungCount ?>">

          <div id="ko-pairs-container">
<?php
            if ($paarungenLeer) {
                // Leere Startreihe – JS kann sofort Paarungen hinzufügen
                ?>
            <p class="text-muted" style="font-size:.88rem;padding:8px 2px">
              <?= h(t('sp_no_pairs_yet')) ?>
            </p>
<?php
            }
            // Paarungen ausgeben
            $pIdx = 0;
            foreach ($paarungen as $pNr => $spiele) {
                $erstesSpiel = reset($spiele);
                // Heim
                $paarHeimId    = $erstesSpiel['heim_id']    ? (int)$erstesSpiel['heim_id']    : 0;
                $paarHeimLabel = $erstesSpiel['heim_label'] ?? '';
                // Gast – bei Modus 2 ist Spiel 1 das Hinspiel, gast_id ist das Original-Gast
                $paarGastId    = $erstesSpiel['gast_id']    ? (int)$erstesSpiel['gast_id']    : 0;
                $paarGastLabel = $erstesSpiel['gast_label'] ?? '';
                // Bei Rückspiel als erstem Eintrag (Modus-Wechsel nachträglich): Hin/Rück-Erkennung
                if ($currentModus === 2 && isset($spiele[1])) {
                    $paarHeimId    = $spiele[1]['heim_id']    ? (int)$spiele[1]['heim_id']    : 0;
                    $paarHeimLabel = $spiele[1]['heim_label'] ?? '';
                    $paarGastId    = $spiele[1]['gast_id']    ? (int)$spiele[1]['gast_id']    : 0;
                    $paarGastLabel = $spiele[1]['gast_label'] ?? '';
                }
                $heimIsPlaceholder = ($paarHeimId === 0);
                $gastIsPlaceholder = ($paarGastId === 0);
                ?>
            <div class="ko-edit-pair" data-idx="<?= $pIdx ?>" style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:12px;margin-bottom:10px">
              <!-- Paarungszeile -->
              <?php
              // Label für letzte Runde mit KlFin
              if ($isLastRound) {
                  if ($pIdx === 0) {
                      $paarLabel = t('sp_final_label');
                      $paarStyle = 'color:#fbbf24;font-weight:700';
                  } else {
                      $paarLabel = t('sp_third_place_label');
                      $paarStyle = 'color:#94a3b8;font-weight:600';
                  }
              } else {
                  $paarLabel = t('sp_pairing_label', ['n' => $pIdx + 1]);
                  $paarStyle = 'color:var(--muted)';
              }
              ?>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
                <span style="font-size:.8rem;white-space:nowrap;min-width:160px;<?= $paarStyle ?>"><?= h($paarLabel) ?>:</span>
                <?= renderKoTeamPicker($pIdx, 'heim', $paarHeimId, $paarHeimLabel, $dropdownTeams) ?>
                <span style="color:var(--muted);font-size:.8rem;padding:0 2px">vs</span>
                <?= renderKoTeamPicker($pIdx, 'gast', $paarGastId, $paarGastLabel, $dropdownTeams) ?>
                <button type="button" class="btn btn-danger btn-sm" onclick="koRemovePair(this)" title="<?= h(t('sp_tooltip_remove_pair')) ?>">✕</button>
              </div>
              <?php if ($hasWinnerFilter) { ?>
              <div style="font-size:.74rem;color:var(--accent);margin-bottom:8px;padding-left:168px;opacity:.8">
                <?= h(t($isLastRound ? 'sp_winners_and_losers_from' : 'sp_only_winners_from', ['round' => $stNr - 1, 'count' => count($dropdownTeams)])) ?>
              </div>
              <?php } ?>
              <!-- Einzel-Spiele -->
              <div class="ko-spiele" style="padding-left:0;overflow-x:auto">
<?php
                // Teamnamen für Anzeige ermitteln
                $teamMap = array_column($allTeams, 'name', 'id');
                $heimName = $paarHeimId ? ($teamMap[$paarHeimId] ?? $paarHeimLabel) : $paarHeimLabel;
                $gastName = $paarGastId ? ($teamMap[$paarGastId] ?? $paarGastLabel) : $paarGastLabel;
                if (!$heimName) { $heimName = '?'; }
                if (!$gastName) { $gastName = '?'; }

                for ($s = 1; $s <= $currentModus; $s++) {
                    $sp      = $spiele[$s] ?? null;
                    $hTore   = $sp && $sp['h_tore'] !== null ? (int)$sp['h_tore'] : '';
                    $gTore   = $sp && $sp['g_tore'] !== null ? (int)$sp['g_tore'] : '';
                    $atVal   = ($sp && $sp['zeit']) ? str_replace(' ', 'T', substr($sp['zeit'], 0, 16)) : '';
                    $status  = $sp ? (int)($sp['status'] ?? 0) : 0;
                    $bericht = $sp ? ($sp['bericht_url'] ?? '') : '';
                    $notiz   = $sp ? ($sp['notiz'] ?? '') : '';

                    // Wer spielt in diesem Einzel-Spiel Heim/Gast?
                    if ($currentModus === 2) {
                        $spielLbl  = $s === 1 ? t('sp_leg_home') : t('sp_leg_away');
                        $spielHeim = $s === 1 ? $heimName : $gastName;
                        $spielGast = $s === 1 ? $gastName : $heimName;
                    } elseif ($currentModus > 2) {
                        $spielLbl  = t('sp_game_n', ['n' => $s]);
                        $spielHeim = ($s % 2 === 1) ? $heimName : $gastName;
                        $spielGast = ($s % 2 === 1) ? $gastName : $heimName;
                    } else {
                        $spielLbl  = '';
                        $spielHeim = $heimName;
                        $spielGast = $gastName;
                    } ?>
                <div class="ko-spiel-zeile" style="display:grid;grid-template-columns:<?= $currentModus > 1 ? '84px ' : '' ?>minmax(120px,1fr) 54px 16px 54px minmax(120px,1fr) 160px;gap:6px;align-items:center;margin-bottom:6px;padding:6px 8px;background:var(--bg);border-radius:var(--radius)">
                  <?php if ($currentModus > 1) { ?><span style="font-size:.76rem;color:var(--muted);font-style:italic"><?= h($spielLbl) ?></span><?php } ?>
                  <span class="ko-heim-name" style="font-size:.85rem;font-weight:500;text-align:right;padding-right:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($spielHeim) ?>"><?= h($spielHeim) ?></span>
                  <input type="number" name="h_<?= $pIdx ?>_<?= $s ?>" value="<?= h((string)$hTore) ?>"
                         min="0" max="99" placeholder="–"
                         style="width:54px;text-align:center;background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 6px;font-size:.9rem">
                  <span style="text-align:center;color:var(--muted);font-size:.85rem">:</span>
                  <input type="number" name="g_<?= $pIdx ?>_<?= $s ?>" value="<?= h((string)$gTore) ?>"
                         min="0" max="99" placeholder="–"
                         style="width:54px;text-align:center;background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 6px;font-size:.9rem">
                  <span class="ko-gast-name" style="font-size:.85rem;font-weight:500;padding-left:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($spielGast) ?>"><?= h($spielGast) ?></span>
                  <?= dtInput('at_'.$pIdx.'_'.$s, $atVal) ?>
                </div>
                <div style="display:flex;gap:8px;align-items:center;margin:-2px 0 8px;padding:0 8px;flex-wrap:wrap">
                  <select name="status_<?= $pIdx ?>_<?= $s ?>"
                          style="background:var(--surface2);border:1px solid var(--border);color:var(--muted);
                                 border-radius:var(--radius);padding:3px 8px;font-size:.78rem">
                    <option value="0"<?= $status === 0 ? ' selected' : '' ?>><?= h(t('sp_status_normal')) ?></option>
                    <option value="1"<?= $status === 1 ? ' selected' : '' ?>><?= h(t('sp_status_ie')) ?></option>
                    <option value="2"<?= $status === 2 ? ' selected' : '' ?>><?= h(t('sp_status_nv')) ?></option>
                  </select>
                  <input type="text" name="notiz_<?= $pIdx ?>_<?= $s ?>" value="<?= h($notiz) ?>"
                         placeholder="<?= h(t('sp_placeholder_venue')) ?>"
                         style="width:140px;background:var(--surface2);border:1px solid var(--border);
                                color:var(--text);border-radius:var(--radius);padding:3px 8px;font-size:.78rem">
                  <input type="url" name="bericht_<?= $pIdx ?>_<?= $s ?>" value="<?= h($bericht) ?>"
                         placeholder="<?= h(t('sp_placeholder_report_link')) ?>"
                         style="flex:1;min-width:180px;background:var(--surface2);border:1px solid var(--border);
                                color:var(--text);border-radius:var(--radius);padding:3px 8px;font-size:.78rem">
                </div>
<?php
                } // for $s ?>
              </div>
            </div>
<?php
                $pIdx++;
            } // foreach paarungen ?>
          </div>

          <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;align-items:center">
            <button type="button" class="btn btn-muted" onclick="koAddPair()"><?= h(t('sp_btn_add_pair')) ?></button>
            <button type="submit" class="btn btn-success"><?= h(t('sp_btn_save_round')) ?></button>
            <?php if ($next !== null) { ?><a href="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= $next ?>" class="btn btn-muted"><?= h(t('sp_next_round', ['n' => $next])) ?></a><?php } ?>
          </div>
        <?= csrfField() ?></form>

<?php
$tickerAktiv = $spieltagData['ticker']     ?? false;
$tickerText  = $spieltagData['tickertext'] ?? '';
?>
        <!-- Ticker (liga-weit, unabhängig von der Runde) -->
        <div class="card" style="margin-top:12px">
          <div style="border-bottom:1px solid var(--border);padding-bottom:8px;margin-bottom:12px;
                      font-size:.78rem;font-weight:600;color:var(--muted);letter-spacing:.05em">
            <?= h(t('sp_independent_settings_heading')) ?>
          </div>
          <form method="post" action="?action=save_liga_settings">
            <input type="hidden" name="liga_id" value="<?= $lid ?>">
            <input type="hidden" name="tab" value="ticker">
            <input type="hidden" name="redirect" value="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= $stNr ?>">
            <table style="width:100%;border-collapse:collapse">
              <tr>
                <td style="text-align:right;padding:6px 12px;font-size:.85rem;color:var(--muted);white-space:nowrap;width:180px"><?= h(t('sp_ticker_show_label')) ?></td>
                <td style="padding:6px 10px">
                  <select name="ticker" style="background:var(--bg);border:1px solid var(--border);
                         color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.85rem">
                    <option value="1"<?= $tickerAktiv ? ' selected' : '' ?>><?= h(t('common_yes')) ?></option>
                    <option value="0"<?= !$tickerAktiv ? ' selected' : '' ?>><?= h(t('common_no')) ?></option>
                  </select>
                </td>
              </tr>
              <tr>
                <td style="text-align:right;padding:6px 12px;font-size:.85rem;color:var(--muted);
                           white-space:nowrap;vertical-align:top;padding-top:10px"><?= h(t('sp_ticker_text_label')) ?></td>
                <td style="padding:6px 10px">
                  <textarea name="tickertext" rows="3"
                            style="width:100%;max-width:600px;background:var(--bg);
                                   border:1px solid var(--border);color:var(--text);
                                   border-radius:var(--radius);padding:8px 10px;
                                   font-size:.87rem;font-family:inherit;resize:vertical"
                            placeholder="<?= h(t('sp_ticker_placeholder')) ?>"><?= h($tickerText) ?></textarea>
                </td>
              </tr>
            </table>
            <div style="padding:6px 10px 0 202px">
              <button type="submit" class="btn btn-muted btn-sm"><?= h(t('sp_btn_save_ticker')) ?></button>
            </div>
          <?= csrfField() ?></form>
        </div>

        <script>
        const koTeams   = <?= json_encode(array_values($dropdownTeams)) ?>;
        const allKoTeams = <?= json_encode(array_values($allTeams)) ?>; // Fallback
        let   koModus   = <?= $currentModus ?>;

        const i18nSp = {
          pairingLabelTpl:      <?= json_encode(t('sp_pairing_label')) ?>,
          legHome:              <?= json_encode(t('sp_leg_home')) ?>,
          legAway:              <?= json_encode(t('sp_leg_away')) ?>,
          gameNTpl:             <?= json_encode(t('sp_game_n')) ?>,
          placeholderShort:     <?= json_encode(t('sp_btn_placeholder_short')) ?>,
          teamShort:            <?= json_encode(t('sp_btn_team_short')) ?>,
          exampleWinner:        <?= json_encode(t('sp_placeholder_example_winner')) ?>,
          optionPlaceholder:    <?= json_encode(t('sp_option_placeholder')) ?>,
          placeholderWord:      <?= json_encode(t('sp_placeholder_word')) ?>,
        };

        // ── Team-Picker: echtes Team ↔ Platzhalter ───────────────────────────
        function koTogglePicker(slot, pIdx) {
          const sel = document.getElementById('sel-'+slot+'-'+pIdx);
          const inp = document.getElementById('lbl-'+slot+'-'+pIdx);
          const btn = inp.nextElementSibling; // Toggle-Button
          if (sel.style.display === 'none') {
            // Wechsel zu Dropdown
            sel.style.display = '';
            inp.style.display = 'none';
            sel.name = slot+'_'+pIdx;
            inp.name = slot+'_label_'+pIdx;
            btn.textContent = i18nSp.placeholderShort;
          } else {
            // Wechsel zu Platzhalter
            sel.style.display = 'none';
            inp.style.display = '';
            // Versteckter Wert 0 für team_id
            let hid = sel.parentElement.querySelector('input[type=hidden][name="'+slot+'_'+pIdx+'"]');
            if (!hid) {
              hid = document.createElement('input');
              hid.type = 'hidden'; hid.name = slot+'_'+pIdx; hid.value = '0';
              sel.parentElement.appendChild(hid);
            }
            sel.name = '';  // aus POST rausnehmen
            btn.textContent = i18nSp.teamShort;
          }
        }

        // ── Team-Picker HTML generieren (für neue Paarungen) ─────────────────
        function koPickerHtml(slot, pIdx, selectedId, label) {
          selectedId = selectedId || 0;
          label      = label      || '';
          const selStyle = 'flex:1;min-width:140px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 8px;font-size:.85rem';
          const inpStyle = 'flex:1;min-width:140px;background:var(--bg);border:1px solid var(--yellow,#f59e0b);color:var(--text);border-radius:var(--radius);padding:5px 8px;font-size:.85rem';
          const tglStyle = 'background:none;border:1px solid var(--border);color:var(--muted);border-radius:var(--radius);padding:3px 7px;font-size:.75rem;cursor:pointer;white-space:nowrap;flex-shrink:0';
          const isPlaceholder = selectedId === 0 && label !== '';

          let opts = `<option value="0">${i18nSp.optionPlaceholder}</option>`;
          koTeams.forEach(t => {
            opts += `<option value="${t.id}"${t.id==selectedId?' selected':''}>${t.name.replace(/&/g,'&amp;').replace(/</g,'&lt;')}</option>`;
          });

          const selDisp = isPlaceholder ? 'display:none;' : '';
          const inpDisp = isPlaceholder ? '' : 'display:none;';
          const btnTxt  = isPlaceholder ? i18nSp.teamShort : i18nSp.placeholderShort;
          const hidEl   = isPlaceholder ? `<input type="hidden" name="${slot}_${pIdx}" value="0">` : '';

          return `<select name="${slot}_${pIdx}" id="sel-${slot}-${pIdx}" style="${selStyle};${selDisp}">${opts}</select>`
               + `<input type="text" name="${slot}_label_${pIdx}" id="lbl-${slot}-${pIdx}" value="${label}" placeholder="${i18nSp.exampleWinner}" style="${inpStyle};${inpDisp}">`
               + `<button type="button" style="${tglStyle}" onclick="koTogglePicker('${slot}',${pIdx})">${btnTxt}</button>`
               + hidEl;
        }

        // ── Teamnamen aus Picker lesen ────────────────────────────────────────
        function koGetName(slot, pIdx) {
          const sel = document.getElementById('sel-'+slot+'-'+pIdx);
          const inp = document.getElementById('lbl-'+slot+'-'+pIdx);
          if (sel && sel.style.display !== 'none' && sel.value > 0) {
            const opt = sel.options[sel.selectedIndex];
            return opt ? opt.text : '?';
          }
          return (inp && inp.value.trim()) ? inp.value.trim() : '?';
        }

        function koSpielNamen(pIdx, s, modus) {
          const h = koGetName('heim', pIdx);
          const g = koGetName('gast', pIdx);
          if (modus === 2) { return s === 1 ? [h, g] : [g, h]; }
          if (modus > 2)   { return s % 2 === 1 ? [h, g] : [g, h]; }
          return [h, g];
        }

        // ── Spiel-Zeilen HTML ─────────────────────────────────────────────────
        function koSpielZeilen(pIdx, modus, heimName, gastName) {
          heimName = heimName || koGetName('heim', pIdx) || '?';
          gastName = gastName || koGetName('gast', pIdx) || '?';
          let html = '';
          for (let s = 1; s <= modus; s++) {
            const lbl  = modus === 2 ? (s===1?i18nSp.legHome:i18nSp.legAway) : (modus>1?i18nSp.gameNTpl.replace('{n}',s):'');
            const lblCol = modus > 1
              ? `<span style="font-size:.76rem;color:var(--muted);font-style:italic;width:84px;flex-shrink:0">${lbl}</span>`
              : '';
            let sHeim, sGast;
            if (modus === 2) { sHeim = s===1?heimName:gastName; sGast = s===1?gastName:heimName; }
            else if (modus > 2) { sHeim = s%2===1?heimName:gastName; sGast = s%2===1?gastName:heimName; }
            else { sHeim = heimName; sGast = gastName; }

            const esc = t => t.replace(/&/g,'&amp;').replace(/</g,'&lt;');
            html += `<div class="ko-spiel-zeile" style="display:grid;grid-template-columns:${modus>1?'84px ':''}minmax(120px,1fr) 54px 16px 54px minmax(120px,1fr) 160px;gap:6px;align-items:center;margin-bottom:6px;padding:6px 8px;background:var(--bg);border-radius:var(--radius)">
              ${lblCol}
              <span class="ko-heim-name" style="font-size:.85rem;font-weight:500;text-align:right;padding-right:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(sHeim)}">${esc(sHeim)}</span>
              <input type="number" name="h_${pIdx}_${s}" value="" min="0" max="99" placeholder="–"
                     style="width:54px;text-align:center;background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 6px;font-size:.9rem">
              <span style="text-align:center;color:var(--muted);font-size:.85rem">:</span>
              <input type="number" name="g_${pIdx}_${s}" value="" min="0" max="99" placeholder="–"
                     style="width:54px;text-align:center;background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 6px;font-size:.9rem">
              <span class="ko-gast-name" style="font-size:.85rem;font-weight:500;padding-left:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(sGast)}">${esc(sGast)}</span>
              <span style="display:inline-flex;align-items:center;gap:4px">
                <input type="date" id="dt_at_${pIdx}_${s}_d" style="background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:4px 6px;font-size:.82rem" oninput="syncDt('dt_at_'+pIdx+'_'+s)">
                <input type="time" id="dt_at_${pIdx}_${s}_t" style="background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:4px 6px;font-size:.82rem;width:80px" oninput="syncDt('dt_at_'+pIdx+'_'+s)">
                <input type="hidden" name="at_${pIdx}_${s}" id="dt_at_${pIdx}_${s}_h" value="">
              </span>
            </div>`;
          }
          return html;
        }

        // ── Spiel-Zeilen Teamnamen live aktualisieren ─────────────────────────
        function koUpdateSpielNamen(pIdx) {
          const pair = document.querySelector(`.ko-edit-pair[data-idx="${pIdx}"]`);
          if (!pair) { return; }
          const h = koGetName('heim', pIdx);
          const g = koGetName('gast', pIdx);
          pair.querySelectorAll('.ko-spiel-zeile').forEach((row, i) => {
            const s = i + 1;
            let sH, sG;
            if (koModus === 2) { sH = s===1?h:g; sG = s===1?g:h; }
            else if (koModus > 2) { sH = s%2===1?h:g; sG = s%2===1?g:h; }
            else { sH = h; sG = g; }
            const hnEl = row.querySelector('.ko-heim-name');
            const gnEl = row.querySelector('.ko-gast-name');
            if (hnEl) { hnEl.textContent = sH; hnEl.title = sH; }
            if (gnEl) { gnEl.textContent = sG; gnEl.title = sG; }
          });
        }

        // ── Paarung hinzufügen ────────────────────────────────────────────────
        function koAddPair() {
          const cntInp = document.getElementById('ko-pair-count');
          const idx    = parseInt(cntInp.value);
          const wrap   = document.createElement('div');
          wrap.className = 'ko-edit-pair';
          wrap.dataset.idx = idx;
          wrap.style.cssText = 'background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:12px;margin-bottom:10px';
          wrap.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
              <span style="font-size:.75rem;color:var(--muted);white-space:nowrap;min-width:68px">${i18nSp.pairingLabelTpl.replace('{n}', idx+1)}:</span>
              ${koPickerHtml('heim', idx, 0, i18nSp.placeholderWord + ' A')}
              <span style="color:var(--muted);font-size:.8rem;padding:0 2px">vs</span>
              ${koPickerHtml('gast', idx, 0, i18nSp.placeholderWord + ' B')}
              <button type="button" class="btn btn-danger btn-sm" onclick="koRemovePair(this)">\u2715</button>
            </div>
            <div class="ko-spiele" style="padding-left:0;overflow-x:auto">
              ${koSpielZeilen(idx, koModus, i18nSp.placeholderWord + ' A', i18nSp.placeholderWord + ' B')}
            </div>`;
          document.getElementById('ko-pairs-container').appendChild(wrap);
          cntInp.value = idx + 1;
          koBindPairListeners(wrap, idx);
        }

        // ── Paarung entfernen ─────────────────────────────────────────────────
        function koRemovePair(btn) {
          const container = document.getElementById('ko-pairs-container');
          if (container.children.length <= 1) { return; }
          btn.closest('.ko-edit-pair').remove();
          Array.from(container.children).forEach((div, i) => {
            div.dataset.idx = i;
            const lbl = div.querySelector('span[style*="min-width"]');
            if (lbl) { lbl.textContent = i18nSp.pairingLabelTpl.replace('{n}', i+1)+':'; }
            div.querySelectorAll('[name],[id]').forEach(el => {
              if (el.name) { el.name = el.name.replace(/_(heim|gast|h|g|at)_\d+|_(heim|gast)_\d+$|(heim|gast|h|g|at)_\d+/,
                m => m.replace(/\d+$/, String(i))); }
              if (el.id)   { el.id   = el.id.replace(/\d+$/, String(i)); }
            });
          });
          document.getElementById('ko-pair-count').value = container.children.length;
        }

        // ── Modus wechseln ────────────────────────────────────────────────────
        function koModusChange(newModus) {
          newModus = parseInt(newModus);
          koModus  = newModus;
          document.getElementById('ko-modus-val').value = newModus;
          document.querySelectorAll('#modus-btns label').forEach(lbl => {
            const active = parseInt(lbl.dataset.modus) === newModus;
            lbl.style.background  = active ? 'var(--accent)' : 'var(--bg)';
            lbl.style.borderColor = active ? 'var(--accent)' : 'var(--border)';
            lbl.style.color       = active ? '#fff'          : 'var(--text)';
          });
          document.querySelectorAll('.ko-edit-pair').forEach((pair, i) => {
            const spielDiv = pair.querySelector('.ko-spiele');
            if (spielDiv) { spielDiv.innerHTML = koSpielZeilen(i, newModus); }
          });
        }

        // ── Live-Listener: Teamnamen aktualisieren wenn Picker sich ändert ────
        function koBindPairListeners(pairEl, pIdx) {
          ['heim','gast'].forEach(slot => {
            const sel = pairEl.querySelector('#sel-'+slot+'-'+pIdx);
            const inp = pairEl.querySelector('#lbl-'+slot+'-'+pIdx);
            if (sel) { sel.addEventListener('change', () => koUpdateSpielNamen(pIdx)); }
            if (inp) { inp.addEventListener('input',  () => koUpdateSpielNamen(pIdx)); }
          });
        }

        // Listener für alle beim Laden vorhandenen Paarungen binden
        document.querySelectorAll('.ko-edit-pair').forEach((pair, i) => {
          koBindPairListeners(pair, i);
        });
        </script>

<?php
        } else {
            // ── Liga-Modus: feste Tabelle (unverändert) ──────────────────────
            // Sport-Profil (Beitrag: Torsten Hofmann) - eigenständige Abfrage
            // statt LigaService::getLigaSportType(), da der Adminbereich die
            // Liga-Trait-Kette bewusst nicht einbindet (siehe "Tipps
            // nachtragen"-Feature). Nur für Sportarten mit eigenen
            // Ergebnisfeldern (aktuell Volleyball: Sätze) wird unter jeder
            // Zeile eine zusätzliche Satz-Eingabe eingeblendet.
            $sSportType = $db->prepare('SELECT sport_type FROM ' . tbl('liga') . ' WHERE id=?');
            $sSportType->execute([$lid]);
            $ligaSportType = (string)($sSportType->fetchColumn() ?: 'football');
            $sportProfileForForm = \LMOnext\Sport\SportRegistry::get($ligaSportType);
            $resultFormFieldsAll = $sportProfileForForm->getResultFormFields();
            // Nur "dynamic-score-list"-Felder (aktuell: Sätze bei Volleyball)
            // werden hier als Satz-Eingabe gerendert - Fußballs eigenes Feld
            // ("Halbzeit", Typ "score-pair") ist ein anderer UI-Baustein und
            // absichtlich nicht Teil dieser Änderung.
            $resultFormFields = array_values(array_filter(
                $resultFormFieldsAll,
                static fn(array $f) => ($f['type'] ?? '') === 'dynamic-score-list'
            ));
            ?>
        <!-- Einheitliches Formular: Paarungen + Ergebnisse + Anstoß -->
        <form method="post" action="?action=save_partie_teams">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <input type="hidden" name="spieltag_nr" value="<?= $stNr ?>">
        <!-- Bugfix (auf schmalen Bildschirmen quetschten sich die
             Heim-/Gast-Dropdowns auf einen einzelnen sichtbaren Buchstaben
             zusammen, da die Tabelle keinerlei Mindestbreite hatte und
             stattdessen auf width:100% zusammengedrückt wurde) - Tabelle
             bekommt jetzt eine Mindestbreite und wird bei Bedarf horizontal
             gescrollt, statt ihren Inhalt unbrauchbar zu stauchen. -->
        <div style="overflow-x:auto">
        <table class="tbl" style="margin-bottom:16px;min-width:860px">
          <thead>
            <tr>
              <th><?= h(t('sp_col_heim')) ?></th>
              <th style="width:60px;text-align:center"><?= h(t('sp_col_tore')) ?></th>
              <th style="width:16px;text-align:center">:</th>
              <th style="width:60px;text-align:center"><?= h(t('sp_col_tore')) ?></th>
              <th><?= h(t('sp_col_gast')) ?></th>
<?php if ($ligaSportType !== 'football') { ?>
              <th style="width:130px"><?= h(t('sp_col_status')) ?></th>
<?php } ?>
              <th style="width:150px"><?= h(t('sp_col_gt')) ?></th>
              <th style="width:175px"><?= h(t('sp_col_anstoss')) ?></th>
            </tr>
          </thead>
          <tbody>
<?php
            // Spaltenanzahl dieser Tabelle für die Notiz-/Sätze-Zeilen
            // weiter unten (colspan) - hängt von der bedingt ausgeblendeten
            // Status-Spalte ab (siehe $ligaSportType-Check oben).
            $tableColCount = $ligaSportType !== 'football' ? 7 : 6;
            foreach ($spieltagData['partien'] as $p) {
                $atVal = '';
                if ($p['zeit']) {
                    $atVal = str_replace(' ', 'T', substr($p['zeit'], 0, 16));
                }
                $status  = (int)($p['status'] ?? 0);
                $gt      = (int)($p['gt_entscheidung'] ?? 0);
                $bericht = $p['bericht_url'] ?? '';
                $gtGrund = $p['gt_grund'] ?? '';
                $existingSets = [];
                if (!empty($p['extra_data'])) {
                    $decoded = json_decode((string)$p['extra_data'], true);
                    if (is_array($decoded) && !empty($decoded['sets'])) {
                        $existingSets = $decoded['sets'];
                    }
                } ?>
            <tr>
              <td>
                <select name="heim_<?= (int)$p['id'] ?>"
                        style="width:100%;min-width:150px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 8px;font-size:.85rem">
<?php
                foreach ($allTeams as $team) { ?>
                  <option value="<?= (int)$team['id'] ?>"<?= (int)$team['id'] === (int)$p['heim_id'] ? ' selected' : '' ?>><?= h($team['name']) ?></option>
<?php
                } ?>
                </select>
              </td>
              <td>
                <input type="number" name="h_<?= (int)$p['id'] ?>"
                       value="<?= $p['h_tore'] !== null ? h($p['h_tore']) : '' ?>"
                       min="0" max="99" placeholder="–"
                       style="width:54px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 6px;font-size:.95rem">
              </td>
              <td style="text-align:center;color:var(--muted)">:</td>
              <td>
                <input type="number" name="g_<?= (int)$p['id'] ?>"
                       value="<?= $p['g_tore'] !== null ? h($p['g_tore']) : '' ?>"
                       min="0" max="99" placeholder="–"
                       style="width:54px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 6px;font-size:.95rem">
              </td>
              <td>
                <select name="gast_<?= (int)$p['id'] ?>"
                        style="width:100%;min-width:150px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 8px;font-size:.85rem">
<?php
                foreach ($allTeams as $team) { ?>
                  <option value="<?= (int)$team['id'] ?>"<?= (int)$team['id'] === (int)$p['gast_id'] ? ' selected' : '' ?>><?= h($team['name']) ?></option>
<?php
                } ?>
                </select>
              </td>
<?php if ($ligaSportType !== 'football') { ?>
              <td>
                <select name="status_<?= (int)$p['id'] ?>"
                        style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--muted);
                               border-radius:var(--radius);padding:4px 6px;font-size:.78rem">
                  <option value="0"<?= $status === 0 ? ' selected' : '' ?>><?= h(t('sp_status_dash')) ?></option>
                  <option value="1"<?= $status === 1 ? ' selected' : '' ?>><?= h(t('sp_status_ie_short')) ?></option>
                  <option value="2"<?= $status === 2 ? ' selected' : '' ?>><?= h(t('sp_status_nv_short')) ?></option>
                </select>
              </td>
<?php } ?>
              <td>
                <select name="gt_<?= (int)$p['id'] ?>" title="<?= h(t('sp_gt_tip')) ?>"
                        class="gt-select" data-partie="<?= (int)$p['id'] ?>"
                        style="width:100%;background:var(--bg);border:1px solid <?= $gt > 0 ? 'var(--red,#ef4444)' : 'var(--border)' ?>;color:<?= $gt > 0 ? 'var(--red,#ef4444)' : 'var(--muted)' ?>;
                               border-radius:var(--radius);padding:4px 6px;font-size:.78rem">
                  <option value="0"<?= $gt === 0 ? ' selected' : '' ?>><?= h(t('sp_gt_dash')) ?></option>
                  <option value="1"<?= $gt === 1 ? ' selected' : '' ?>><?= h(t('sp_gt_heim')) ?></option>
                  <option value="2"<?= $gt === 2 ? ' selected' : '' ?>><?= h(t('sp_gt_gast')) ?></option>
                  <option value="3"<?= $gt === 3 ? ' selected' : '' ?>><?= h(t('sp_gt_beide')) ?></option>
                </select>
              </td>
              <td>
                <?= dtInput('at_'.(int)$p['id'], $atVal) ?>
              </td>
            </tr>
            <tr>
              <td colspan="<?= $tableColCount ?>" style="padding:2px 8px 8px">
                <input type="url" name="bericht_<?= (int)$p['id'] ?>" value="<?= h($bericht) ?>"
                       placeholder="<?= h(t('sp_placeholder_report_link')) ?>"
                       style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                              border-radius:var(--radius);padding:4px 8px;font-size:.78rem">
                <?php
                  // Hook-Punkt - erlaubt Addons, sich hier mit einem eigenen
                  // Link pro Begegnung einzuklinken, ohne dass der Core
                  // das jeweilige Addon kennen muss - analog zu
                  // liga.view_render bei liga.php.
                  $partieRowHook = doHook('admin.spieltag_partie_row', [
                      'partie_id' => (int)$p['id'],
                      'liga_id'   => $lid,
                      'heim_id'   => (int)($p['heim_id'] ?? 0),
                      'gast_id'   => (int)($p['gast_id'] ?? 0),
                      'html'      => '',
                  ]);
                  if (!empty($partieRowHook['html'])) { echo $partieRowHook['html']; }
                ?>
              </td>
            </tr>
            <tr class="gt-grund-row" data-gt-grund-for="<?= (int)$p['id'] ?>" style="<?= $gt > 0 ? '' : 'display:none' ?>">
              <td colspan="<?= $tableColCount ?>" style="padding:2px 8px 8px">
                <input type="text" name="gt_grund_<?= (int)$p['id'] ?>" value="<?= h($gtGrund) ?>"
                       placeholder="<?= h(t('sp_placeholder_gt_grund')) ?>"
                       style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                              border-radius:var(--radius);padding:4px 8px;font-size:.78rem">
              </td>
            </tr>
<?php       if (!empty($resultFormFields)) { ?>
            <tr class="sport-satz-row">
              <td colspan="<?= $tableColCount ?>" style="padding:2px 8px 10px">
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:.8rem;color:var(--muted)">
                  <span><?= h(t('sp_label_saetze')) ?>:</span>
                  <span class="satz-eingaben" data-partie="<?= (int)$p['id'] ?>">
<?php
                    $satzCount = max(3, count($existingSets));
                    for ($si = 0; $si < $satzCount; $si++) {
                        $sh = $existingSets[$si]['h'] ?? '';
                        $sg = $existingSets[$si]['g'] ?? ''; ?>
                    <span class="satz-eingabe" style="display:inline-flex;align-items:center;gap:2px;margin-right:6px">
                      <?= $si + 1 ?>.
                      <input type="number" name="satz_h_<?= (int)$p['id'] ?>[]" value="<?= h((string)$sh) ?>" min="0" max="99"
                             style="width:38px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:3px 4px;font-size:.82rem">
                      :
                      <input type="number" name="satz_g_<?= (int)$p['id'] ?>[]" value="<?= h((string)$sg) ?>" min="0" max="99"
                             style="width:38px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:3px 4px;font-size:.82rem">
                    </span>
<?php               } ?>
                  </span>
                  <button type="button" class="btn-satz-add" data-partie="<?= (int)$p['id'] ?>"
                          style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:3px 9px;font-size:.85rem;cursor:pointer" title="<?= h(t('sp_btn_satz_add')) ?>">+</button>
                </div>
              </td>
            </tr>
<?php       } ?>
<?php
            } ?>
          </tbody>
        </table>
        </div>
        <script>
document.querySelectorAll('.gt-select').forEach(function (sel) {
    sel.addEventListener('change', function () {
        var pid = sel.getAttribute('data-partie');
        var row = document.querySelector('.gt-grund-row[data-gt-grund-for="' + pid + '"]');
        if (!row) { return; }
        row.style.display = sel.value === '0' ? 'none' : '';
    });
});
        </script>
<?php if (!empty($resultFormFields)) { ?>
        <script>
document.querySelectorAll('.btn-satz-add').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var pid = btn.getAttribute('data-partie');
        var container = document.querySelector('.satz-eingaben[data-partie="' + pid + '"]');
        if (!container) { return; }
        var nr = container.querySelectorAll('.satz-eingabe').length + 1;
        var wrap = document.createElement('span');
        wrap.className = 'satz-eingabe';
        wrap.style.cssText = 'display:inline-flex;align-items:center;gap:2px;margin-right:6px';
        wrap.innerHTML = nr + '. '
            + '<input type="number" name="satz_h_' + pid + '[]" min="0" max="99" style="width:38px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:3px 4px;font-size:.82rem">'
            + ' : '
            + '<input type="number" name="satz_g_' + pid + '[]" min="0" max="99" style="width:38px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:3px 4px;font-size:.82rem">';
        container.appendChild(wrap);
    });
});
        </script>
<?php } ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button type="submit" class="btn btn-success"><?= h(t('sp_btn_save_all')) ?></button>
          <?php if ($next !== null) { ?><a href="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= $next ?>" class="btn btn-muted"><?= h(t('sp_next_matchday', ['n' => $next])) ?></a><?php } ?>
        </div>
        <?= csrfField() ?></form>
<?php
        } ?>
      </div>
<?php } ?>
