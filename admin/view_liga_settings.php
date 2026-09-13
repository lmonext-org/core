<?php
/**
 * Project: LMOnext
 * Filename: view_liga_settings.php
 * Fileversion: 1.8.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Liga Settings ──────────────────────────────────────────────────────────
        $lid       = $ligaSettingsData['lid'];
        $liga      = $ligaSettingsData['liga'];
        $opts      = $ligaSettingsData['opts'];
        $ligaTeams = $ligaSettingsData['teams'] ?? [];
        $tab       = $_GET['tab'] ?? 'grundwerte';
        $o         = fn(string $k, string $d = '') => $opts[$k] ?? $d;
        $oc        = fn(string $k) => ($opts[$k] ?? '0') === '1';
        $isKO      = ($o('Type', '0') === '1');
        // Sportart-Auswahl (Beitrag: Torsten Hofmann) - abschaltbar über eine
        // globale Admin-Einstellung, damit reine Fußball-Installationen (wie
        // der Standardfall) die Auswahl gar nicht erst sehen müssen.
        $showSportType = getAdminSetting('show_sport_type', '1') === '1';

        // Tab-Definitionen je nach Liga-Typ
        $tabs = $isKO
            ? ['grundwerte' => t('ls_tab_grundwerte'), 'teams' => t('ls_tab_teams'), 'anzeige' => t('ls_tab_anzeige')]
            : ['grundwerte' => t('ls_tab_grundwerte'), 'teams' => t('ls_tab_teams'), 'anzeige' => t('ls_tab_anzeige'),
               'spielsystem' => t('ls_tab_spielsystem'), 'tabelle' => t('ls_tab_tabelle'),
               'strafen' => t('ls_tab_strafen'), 'spieltage' => t('ls_tab_spieltage')];

        $selSt = 'background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.87rem';
        $inpSt = $selSt . ';width:120px';
        $tdR   = 'style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);white-space:nowrap"';
        $tdL   = 'style="padding:5px 10px"';
?>

      <!-- Tab-Navigation -->
      <div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
<?php foreach ($tabs as $key => $label) {
    $active = $key === $tab; ?>
        <a href="?action=liga_settings&id=<?= $lid ?>&tab=<?= $key ?>"
           style="padding:8px 16px;font-size:.83rem;text-decoration:none;
                  border-radius:var(--radius) var(--radius) 0 0;
                  background:<?= $active ? 'var(--surface)' : 'var(--surface2)' ?>;
                  border:1px solid var(--border);
                  border-bottom:<?= $active ? '1px solid var(--surface)' : '1px solid var(--border)' ?>;
                  color:<?= $active ? 'var(--accent)' : 'var(--muted)' ?>;
                  font-weight:<?= $active ? '600' : '400' ?>;margin-right:3px"><?= h($label) ?></a>
<?php } ?>
      </div>

      <div class="card" style="border-radius:0 var(--radius) var(--radius) var(--radius);margin-top:0">
        <div style="margin-bottom:12px">
          <a href="?action=liga_detail&id=<?= $lid ?>" class="back-link">← <?= h($liga['name']) ?></a>
        </div>
<?php if ($tab !== 'teams') { ?>
        <form method="post" action="?action=save_liga_settings">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <input type="hidden" name="tab"     value="<?= h($tab) ?>">
<?php } ?>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: GRUNDWERTE
// ═══════════════════════════════════════════════════════════════════════
if ($tab === 'grundwerte') { ?>
          <table style="width:100%;border-collapse:collapse;max-width:600px">
            <tr>
              <td <?= $tdR ?> style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);width:220px"><?= h(t('ls_label_liga_name')) ?></td>
              <td <?= $tdL ?>>
                <input type="text" name="liga_name" value="<?= h($o('Name', $liga['name'])) ?>"
                       style="width:100%;max-width:340px;<?= $selSt ?>">
              </td>
            </tr>
<?php if ($showSportType): ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_sportart')) ?></td>
              <td <?= $tdL ?>>
                <select name="sport_type" style="<?= $selSt ?>" onchange="document.getElementById('sportart-draws-hinweis').style.display = (this.value !== 'football' && this.value !== 'handball') ? 'block' : 'none';">
                  <?php foreach (\LMOnext\Sport\SportRegistry::all() as $sp) { ?>
                  <option value="<?= h($sp->getKey()) ?>"<?= ($liga['sport_type'] ?? 'football') === $sp->getKey() ? ' selected' : '' ?>><?= h($sp->getLabel()) ?></option>
                  <?php } ?>
                </select>
                <div id="sportart-draws-hinweis" style="display:<?= !in_array($liga['sport_type'] ?? 'football', ['football', 'handball'], true) ? 'block' : 'none' ?>;font-size:.75rem;color:var(--muted);margin-top:4px"><?= h(t('ls_hinweis_keine_unentschieden')) ?></div>
              </td>
            </tr>
<?php else: ?>
            <input type="hidden" name="sport_type" value="<?= h($liga['sport_type'] ?? 'football') ?>">
<?php endif; ?>
<?php if (!$isKO) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_alt_pkt')) ?></td>
              <td <?= $tdL ?>><input type="text" name="namePkt" value="<?= h($o('namePkt','Punkte')) ?>" style="<?= $inpSt ?>"></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_alt_tore')) ?></td>
              <td <?= $tdL ?>><input type="text" name="nameTor" value="<?= h($o('nameTor','Tore')) ?>" style="<?= $inpSt ?>"></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_dec_pkt')) ?></td>
              <td <?= $tdL ?>>
                <select name="goalfaktor" style="<?= $selSt ?>">
                  <?php foreach (['0'=>t('ls_opt_none'),'1'=>t('ls_opt_one'),'2'=>t('ls_opt_two')] as $v=>$l) { ?>
                  <option value="<?= $v ?>"<?= $o('goalfaktor','1')===$v?' selected':'' ?>><?= h($l) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_dec_tore')) ?></td>
              <td <?= $tdL ?>>
                <select name="pointsfaktor" style="<?= $selSt ?>">
                  <?php foreach (['0'=>t('ls_opt_none'),'1'=>t('ls_opt_one'),'2'=>t('ls_opt_two')] as $v=>$l) { ?>
                  <option value="<?= $v ?>"<?= $o('pointsfaktor','1')===$v?' selected':'' ?>><?= h($l) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
<?php } ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_fav_team')) ?></td>
              <td <?= $tdL ?>>
                <select name="favTeam" style="<?= $selSt ?>;min-width:220px">
                  <option value="0"><?= h(t('ls_opt_none_dash')) ?></option>
                  <?php foreach ($ligaTeams as $i => $t) { $tNr = $i + 1; ?>
                  <option value="<?= $tNr ?>"<?= $o('favTeam','0')==(string)$tNr?' selected':'' ?>><?= h($t['name']) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spielplan')) ?></td>
              <td <?= $tdL ?>>
                <select name="selTeam" style="<?= $selSt ?>;min-width:220px">
                  <option value="0"><?= h(t('ls_opt_none_dash_m')) ?></option>
                  <?php foreach ($ligaTeams as $i => $t) { $tNr = $i + 1; ?>
                  <option value="<?= $tNr ?>"<?= $o('selTeam','0')==(string)$tNr?' selected':'' ?>><?= h($t['name']) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
          </table>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: TEAMS - hierher verschoben von der Liga-Detailseite (admin/
// view_liga_detail.php), damit die Team-Verwaltung zusammen mit den
// übrigen Liga-Einstellungen an einem Ort liegt. Eigenständig OHNE das
// äußere <form action="?action=save_liga_settings"> (siehe oben) - jede
// Zeile speichert einzeln über ihr eigenes <form action="?action=save_team">,
// verschachtelte <form>-Tags wären ungültiges HTML.
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'teams') { ?>
          <h2 style="margin-bottom:12px"><?= h(t('ld_heading_teams', ['n' => count($ligaTeams)])) ?></h2>
          <table class="tbl" id="teams-tbl">
            <thead><tr><th><?= h(t('dash_col_name')) ?></th><th><?= h(t('ld_label_mittel_short')) ?></th><th><?= h(t('teams_col_kurz')) ?></th><th style="width:80px"></th></tr></thead>
            <tbody>
<?php foreach ($ligaTeams as $t) { ?>
              <tr id="team-row-<?= (int)$t['id'] ?>">
                <td style="font-weight:500"><?= h($t['name']) ?></td>
                <td class="text-muted"><?= h($t['mittel'] ?? '') ?></td>
                <td><?php if (!empty($t['kurz'])) { ?><span class="chip chip-blue"><?= h($t['kurz']) ?></span><?php } else { echo '–'; } ?></td>
                <td>
                  <button type="button" class="btn btn-muted btn-sm"
                          onclick="openTeamEditor(<?= (int)$t['id'] ?>, <?= h(json_encode($t['name'])) ?>, <?= h(json_encode($t['mittel'] ?? '')) ?>, <?= h(json_encode($t['kurz'] ?? '')) ?>)">✏️</button>
                </td>
              </tr>
              <tr id="team-edit-<?= (int)$t['id'] ?>" style="display:none">
                <td colspan="4" style="padding:0">
                  <!-- ── DB-Suche ──────────────────────────────────────── -->
                  <div style="background:var(--surface2);border-radius:var(--radius);padding:10px 12px;margin:4px 0 2px">
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('ld_label_db_search')) ?></label>
                    <div style="display:flex;gap:6px;align-items:center">
                      <input type="text" id="dbsearch-<?= (int)$t['id'] ?>"
                             placeholder="<?= h(t('ld_placeholder_name_search')) ?>" autocomplete="off"
                             oninput="teamDbSearch(<?= (int)$t['id'] ?>, this.value)"
                             style="flex:1;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.85rem">
                    </div>
                    <div id="dbresults-<?= (int)$t['id'] ?>"
                         style="margin-top:4px;max-height:160px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius);display:none;background:var(--bg)">
                    </div>
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin:10px 0 4px"><?= h(t('ld_label_id_lookup')) ?></label>
                    <div style="display:flex;gap:6px;align-items:center">
                      <input type="number" min="1" id="dbid-<?= (int)$t['id'] ?>"
                             placeholder="<?= h(t('ld_placeholder_id_lookup')) ?>" autocomplete="off"
                             onkeydown="if(event.key==='Enter'){event.preventDefault();teamIdLookup(<?= (int)$t['id'] ?>);}"
                             style="width:120px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.85rem">
                      <button type="button" class="btn btn-muted btn-sm" onclick="teamIdLookup(<?= (int)$t['id'] ?>)"><?= h(t('ld_btn_id_apply')) ?></button>
                      <span id="dbid-msg-<?= (int)$t['id'] ?>" style="font-size:.78rem;color:var(--muted)"></span>
                    </div>
                  </div>
                  <!-- ── Felder (manuell oder aus DB) ─────────────────── -->
                  <form method="post" action="?action=save_team"
                        style="display:grid;grid-template-columns:2fr 1.5fr 1fr auto auto;gap:8px;align-items:end;padding:8px 12px 10px">
                    <input type="hidden" name="team_id"  value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="liga_id"  value="<?= $lid ?>">
                    <input type="hidden" name="redirect" value="?action=liga_settings&id=<?= $lid ?>&tab=teams">
                    <!-- global_id: wenn gesetzt, wird das bestehende Global-Team mit der Liga verknüpft statt eines neuen angelegt -->
                    <input type="hidden" name="global_id" id="te-gid-<?= (int)$t['id'] ?>" value="">
                    <div>
                      <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('teams_field_name_required')) ?></label>
                      <input type="text" name="team_name" id="te-name-<?= (int)$t['id'] ?>"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem" required>
                    </div>
                    <div>
                      <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('ld_label_mittel_short')) ?></label>
                      <input type="text" name="team_mittel" id="te-mittel-<?= (int)$t['id'] ?>"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem">
                    </div>
                    <div>
                      <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('teams_col_kurz')) ?></label>
                      <input type="text" name="team_kurz" id="te-kurz-<?= (int)$t['id'] ?>" maxlength="10"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm" style="align-self:end">💾</button>
                    <button type="button" class="btn btn-muted btn-sm" style="align-self:end"
                            onclick="closeTeamEditor(<?= (int)$t['id'] ?>)">✕</button>
                  <?= csrfField() ?></form>
                </td>
              </tr>
<?php } ?>
            </tbody>
          </table>
          <script>
          const i18nLdNoMatch = <?= json_encode(t('ld_js_no_match')) ?>;
          const i18nLdIdNotFound = <?= json_encode(t('ld_js_id_not_found')) ?>;
          const i18nLdLoading = <?= json_encode(t('common_loading')) ?>;
          // ── Team-Editor: öffnen/schließen ─────────────────────────────────────
          function openTeamEditor(id, name, mittel, kurz) {
            document.querySelectorAll('[id^="team-edit-"]').forEach(r => r.style.display = 'none');
            document.getElementById('te-name-'   + id).value = name;
            document.getElementById('te-mittel-' + id).value = mittel;
            document.getElementById('te-kurz-'   + id).value = kurz;
            document.getElementById('te-gid-'    + id).value = '';
            document.getElementById('dbsearch-'  + id).value = '';
            hideResults(id);
            document.getElementById('team-edit-' + id).style.display = '';
            document.getElementById('dbsearch-'  + id).focus();
          }

          function closeTeamEditor(id) {
            document.getElementById('team-edit-' + id).style.display = 'none';
          }

          function hideResults(id) {
            const r = document.getElementById('dbresults-' + id);
            if (r) { r.style.display = 'none'; r.innerHTML = ''; }
          }

          // ── DB-Suche ──────────────────────────────────────────────────────────
          let searchTimer = null;

          function teamDbSearch(tid, q) {
            clearTimeout(searchTimer);
            const results = document.getElementById('dbresults-' + tid);
            if (q.trim().length < 2) { hideResults(tid); return; }

            searchTimer = setTimeout(() => {
              fetch('?action=team_search&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                  if (!data.length) {
                    results.innerHTML = '<div style="padding:8px 12px;font-size:.82rem;color:var(--muted)">' + i18nLdNoMatch + '</div>';
                  } else {
                    results.innerHTML = '';
                    data.forEach(t => {
                      const div = document.createElement('div');
                      div.style.cssText = 'padding:10px 12px;font-size:.85rem;cursor:pointer;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;-webkit-tap-highlight-color:rgba(59,130,246,.2)';
                      div.innerHTML = `<span style="font-weight:500">${esc(t.name)}</span>`
                                    + `<span style="color:var(--muted);font-size:.78rem">${esc(t.mittel)}${t.kurz ? ' · '+esc(t.kurz) : ''}</span>`;

                      div.addEventListener('mouseover', () => div.style.background = 'var(--surface2)');
                      div.addEventListener('mouseout',  () => div.style.background = '');

                      function doSelect(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        selectDbTeam(tid, t.id, t.name, t.mittel, t.kurz);
                      }
                      div.addEventListener('touchstart', doSelect, { passive: false });
                      div.addEventListener('mousedown',  doSelect);

                      results.appendChild(div);
                    });
                  }
                  results.style.display = 'block';
                })
                .catch(() => hideResults(tid));
            }, 280);
          }

          function selectDbTeam(tid, globalId, name, mittel, kurz) {
            document.getElementById('te-gid-'    + tid).value = globalId;
            document.getElementById('te-name-'   + tid).value = name;
            document.getElementById('te-mittel-' + tid).value = mittel;
            document.getElementById('te-kurz-'   + tid).value = kurz;
            document.getElementById('dbsearch-'  + tid).value = name;
            hideResults(tid);
          }

          // Direkte Team-ID-Eingabe: Team per numerischer ID nachschlagen und bei
          // Erfolg wie einen DB-Suchtreffer übernehmen (selectDbTeam).
          function teamIdLookup(tid) {
            const input = document.getElementById('dbid-' + tid);
            const msg   = document.getElementById('dbid-msg-' + tid);
            const id    = parseInt(input.value, 10);
            msg.style.color = 'var(--muted)';
            if (!id || id < 1) { return; }
            msg.textContent = i18nLdLoading;
            fetch('?action=team_by_id&id=' + id)
              .then(r => r.json())
              .then(t => {
                if (!t) {
                  msg.style.color = 'var(--red)';
                  msg.textContent = i18nLdIdNotFound;
                  return;
                }
                selectDbTeam(tid, t.id, t.name, t.mittel, t.kurz);
                input.value = '';
                msg.style.color = 'var(--green)';
                msg.textContent = '✓ ' + t.name;
              })
              .catch(() => { msg.style.color = 'var(--red)'; msg.textContent = i18nLdIdNotFound; });
          }

          function esc(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
          }

          document.addEventListener('touchstart', e => {
            if (!e.target.closest('[id^="dbresults-"]') && !e.target.closest('[id^="dbsearch-"]')) {
              document.querySelectorAll('[id^="dbresults-"]').forEach(r => { r.style.display='none'; });
            }
          }, { passive: true });
          document.addEventListener('mousedown', e => {
            if (!e.target.closest('[id^="dbresults-"]') && !e.target.closest('[id^="dbsearch-"]')) {
              document.querySelectorAll('[id^="dbresults-"]').forEach(r => { r.style.display='none'; });
            }
          });
          </script>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: ANZEIGEN/DARSTELLUNG
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'anzeige') {
    $dfPresets = [
        ''              => '__',
        'd.m. H:i'      => 'd.m. H:i  (24.06. 14:38)',
        'd.m.Y H:i'     => 'd.m.Y H:i  (24.06.2026 14:38)',
        'D., d.m. H:i'  => 'D., d.m. H:i  (Mi., 24.06. 14:38)',
        'l, d.m. H:i'   => 'l, d.m. H:i  (Mittwoch, 24.06. 14:38)',
        'D., d.m.Y H:i' => 'D., d.m.Y H:i  (Mi., 24.06.2026 14:38)',
        'l, d.m.Y H:i'  => 'l, d.m.Y H:i  (Mittwoch, 24.06.2026 14:38)',
    ];
    $curDatF   = $o('DatF', 'd.m.Y H:i');
    $isPreset  = array_key_exists($curDatF, $dfPresets);
    $cbSt = ''; // inline checkbox style
?>
          <table style="width:100%;border-collapse:collapse;max-width:600px">
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_date_sort')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="DatS" value="1"<?= $oc('DatS')?' checked':'' ?>></td>
            </tr>
<?php if ($isKO) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_third_place')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="KlFin" value="1"<?= $oc('KlFin')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_playdown')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="playdown" value="1"<?= $oc('playdown')?' checked':'' ?>></td>
            </tr>
<?php } ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anstoss_termin')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="DatM" value="1"<?= $oc('DatM')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anstoss_format')) ?></td>
              <td style="padding:5px 10px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
                  <input type="radio" name="DatF_mode" value="preset" id="drf_preset"<?= $isPreset?' checked':'' ?>>
                  <select name="DatF_preset" id="drf_sel" style="<?= $selSt ?>" onchange="syncDatF()">
                    <?php foreach ($dfPresets as $v=>$l) { ?>
                    <option value="<?= h($v) ?>"<?= $curDatF===$v?' selected':'' ?>><?= h($l) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                  <input type="radio" name="DatF_mode" value="custom" id="drf_custom"<?= !$isPreset?' checked':'' ?>>
                  <input type="text" name="DatF_custom" id="drf_txt"
                         value="<?= !$isPreset ? h($curDatF) : '' ?>" placeholder="d.m.Y H:i"
                         style="<?= $inpSt ?>" oninput="syncDatF()">
                  <span style="font-size:.73rem;color:var(--muted)"><?= h(t('ls_label_php_dateformat')) ?></span>
                </div>
                <input type="hidden" name="DatF" id="drf_hidden" value="<?= h($curDatF) ?>">
                <script>
                document.querySelectorAll('[name=DatF_mode]').forEach(r => r.addEventListener('change', syncDatF));
                function syncDatF() {
                  const mode = document.querySelector('[name=DatF_mode]:checked')?.value;
                  document.getElementById('drf_hidden').value = mode === 'preset'
                    ? document.getElementById('drf_sel').value
                    : document.getElementById('drf_txt').value;
                }
                </script>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spieltagsdatum')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="DatC" value="1"<?= $oc('DatC')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_ergebnisse')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Ergebnis" value="1"<?= $oc('Ergebnis')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_show_spielfrei')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="ShowSpielfrei" value="1"<?= ($opts['ShowSpielfrei'] ?? '1') === '1' ?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_kalender')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Kalender" value="1"<?= $oc('Kalender')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_cb_spielplaene')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Plan" value="1"<?= $oc('Plan')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_show_logos')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="ShowLogos" value="1"<?= $oc('ShowLogos')?' checked':'' ?>></td>
            </tr>
<?php if (!$isKO) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_tabelle')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Tabelle" value="1"<?= $oc('Tabelle')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_kreuztabelle')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Kreuz" value="1"<?= $oc('Kreuz')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_cb_fieberkurven')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="kurve1" value="1"<?= $oc('kurve1')?' checked':'' ?>></td>
            </tr>
<?php if (function_exists('addonManager') && addonManager()->isEnabled('player')) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spielerstatistik')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="stats" value="1"<?= $oc('stats')?' checked':'' ?>></td>
            </tr>
<?php } ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_cb_ligastatistik')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Ligastats" value="1"<?= $oc('Ligastats')?' checked':'' ?>></td>
            </tr>
<?php } else { ?>
<?php if (function_exists('addonManager') && addonManager()->isEnabled('player')) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spielerstatistik')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="stats" value="1"<?= $oc('stats')?' checked':'' ?>></td>
            </tr>
<?php } ?>
<?php } ?>
            <tr>
              <td colspan="2" style="padding:10px 12px 4px;font-size:.82rem;font-weight:700;color:var(--text);
                                     background:var(--surface2);border-radius:var(--radius)"><?= h(t('ls_heading_ticker')) ?></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_ticker_show')) ?></td>
              <td style="padding:5px 10px">
                <label><input type="checkbox" name="ticker" value="1"<?= $oc('ticker')?' checked':'' ?>> <?= h(t('common_yes')) ?></label>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);vertical-align:top"><?= h(t('sp_ticker_text_label')) ?></td>
              <td style="padding:5px 10px">
                <textarea name="tickertext" rows="3"
                          style="width:100%;max-width:480px;background:var(--bg);border:1px solid var(--border);
                                 color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;
                                 font-family:inherit;resize:vertical"
                          placeholder="<?= h(t('ls_placeholder_tickertext')) ?>"><?= h($opts['tickertext'] ?? '') ?></textarea>
              </td>
            </tr>
            <tr>
              <td colspan="2" style="padding:10px 12px 4px;font-size:.82rem;font-weight:700;color:var(--text);
                                     background:var(--surface2);border-radius:var(--radius)"><?= h(t('ls_heading_verlinkungen')) ?></td>
            </tr>
            <tr>
              <td></td>
              <td style="padding:5px 10px">
                <label style="margin-right:16px"><input type="checkbox" name="urlT" value="1"<?= $oc('urlT')?' checked':'' ?>> <?= h(t('ls_cb_team_homepage')) ?></label>
              </td>
            </tr>
            <tr>
              <td></td>
              <td style="padding:5px 10px">
                <label><input type="checkbox" name="urlB" value="1"<?= $oc('urlB')?' checked':'' ?>> <?= h(t('ls_cb_spielberichte')) ?></label>
              </td>
            </tr>
<?php if ($isKO) { ?>
            <tr>
              <td colspan="2" style="padding:10px 12px 4px;font-size:.82rem;font-weight:700;color:var(--text);
                                     background:var(--surface2);border-radius:var(--radius);margin-top:8px">
                <?= h(t('ls_heading_playoff_mode')) ?>
              </td>
            </tr>
            <tr>
              <td colspan="2" style="padding:10px 12px">
                <div style="display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start">
                  <div style="font-size:.84rem;color:var(--muted);line-height:1.9">
                    <strong style="color:var(--text)">Best Of 3</strong><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_111')) ?><br>
                    <hr style="border-color:var(--border);margin:6px 0">
                    <strong style="color:var(--text)">Best Of 5</strong><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_111')) ?><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_221')) ?><br>
                    <hr style="border-color:var(--border);margin:6px 0">
                    <strong style="color:var(--text)">Best Of 7</strong><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_111')) ?><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_22111')) ?><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_232')) ?>
                  </div>
                  <div>
                    <div style="font-size:.82rem;font-weight:600;color:var(--text);margin-bottom:8px;text-align:center"><?= h(t('ls_label_modusauswahl')) ?></div>
                    <select name="playoffmode" style="<?= $selSt ?>;min-width:170px">
                      <option value="0"<?= $o('playoffmode','0')==='0'?' selected':'' ?>><?= h(t('ls_opt_mod_111')) ?></option>
                      <option value="1"<?= $o('playoffmode','0')==='1'?' selected':'' ?>><?= h(t('ls_opt_mod_221')) ?></option>
                      <option value="2"<?= $o('playoffmode','0')==='2'?' selected':'' ?>><?= h(t('ls_opt_mod_22111')) ?></option>
                      <option value="3"<?= $o('playoffmode','0')==='3'?' selected':'' ?>><?= h(t('ls_opt_mod_232')) ?></option>
                    </select>
                  </div>
                </div>
              </td>
            </tr>
<?php } ?>
          </table>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: SPIELSYSTEM (nur Liga)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'spielsystem' && !$isKO) { ?>
          <table style="width:100%;border-collapse:collapse;max-width:500px">
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="MinusPoints" value="1"<?= $oc('MinusPoints')?' checked':'' ?>> <?= h(t('ls_cb_minuspunkte')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="OnRun" value="1"<?= $oc('OnRun')?' checked':'' ?>> <?= h(t('ls_cb_spielende_offen')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="HideDraw" value="1"<?= $oc('HideDraw')?' checked':'' ?>> <?= h(t('ls_cb_hide_draw')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="Direct" value="1"<?= $oc('Direct')?' checked':'' ?>> <?= h(t('ls_cb_direct_compare')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="Spez" value="1"<?= $oc('Spez')?' checked':'' ?>> <?= h(t('ls_cb_spez')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="enableGameSort" value="1"<?= $oc('enableGameSort')?' checked':'' ?>> <?= h(t('ls_cb_hand_sort')) ?></label></td></tr>
          </table>

          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-top:14px;max-width:500px">
            <div style="font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:12px"><?= h(t('ls_heading_punktesystem')) ?></div>
            <table style="border-collapse:collapse;font-size:.85rem">
              <thead>
                <tr>
                  <td style="padding:4px 10px"></td>
                  <td style="padding:4px 14px;text-align:center;color:var(--muted)"><?= h(t('ls_col_win')) ?></td>
                  <td style="padding:4px 14px;text-align:center;color:var(--muted)"><?= h(t('ls_col_draw')) ?></td>
                  <td style="padding:4px 14px;text-align:center;color:var(--muted)"><?= h(t('ls_col_loss')) ?></td>
                </tr>
              </thead>
              <tbody>
<?php
    $numSt = 'width:54px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:4px 6px;font-size:.88rem';
?>
                <tr>
                  <td style="padding:5px 10px;text-align:right;color:var(--muted)"><?= h(t('ls_row_normal_end')) ?></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForWin"  value="<?= h($o('PointsForWin','3'))  ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForDraw" value="<?= h($o('PointsForDraw','1')) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForLost" value="<?= h($o('PointsForLost','0')) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                </tr>
                <tr>
                  <td style="padding:5px 10px;text-align:right;color:var(--muted)"><?= h(t('ls_row_extra_time')) ?></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForWinET"  value="<?= h($o('PointsForWinET',  $o('PointsForWin','3')))  ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForDrawET" value="<?= h($o('PointsForDrawET', $o('PointsForDraw','1'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForLostET" value="<?= h($o('PointsForLostET', $o('PointsForLost','0'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                </tr>
                <tr>
                  <td style="padding:5px 10px;text-align:right;color:var(--muted)"><?= h(t('ls_row_penalty')) ?></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForWinPS"  value="<?= h($o('PointsForWinPS',  $o('PointsForWin','3')))  ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForDrawPS" value="<?= h($o('PointsForDrawPS', $o('PointsForDraw','1'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForLostPS" value="<?= h($o('PointsForLostPS', $o('PointsForLost','0'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                </tr>
              </tbody>
            </table>
          </div>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: TABELLE (nur Liga)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'tabelle' && !$isKO) {
    $colorMap = [
        'Champ' => ['label' => t('ls_marker_champ'), 'bg' => '#22c55e22', 'default' => '#22c55e'],
        'CL'    => ['label' => t('ls_marker_cl'),     'bg' => '#3b82f622', 'default' => '#3b82f6'],
        'CK'    => ['label' => t('ls_marker_ck'),     'bg' => '#0ea5e922', 'default' => '#0ea5e9'],
        'UC'    => ['label' => t('ls_marker_uc'),     'bg' => '#f59e0b22', 'default' => '#f59e0b'],
        'AR'    => ['label' => t('ls_marker_ar'),     'bg' => '#f9731622', 'default' => '#f97316'],
        'AB'    => ['label' => t('ls_marker_ab'),     'bg' => '#ef444422', 'default' => '#ef4444'],
    ]; ?>
          <table style="width:100%;border-collapse:collapse;max-width:500px;margin-bottom:16px">
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="tableHinRueck" value="1"<?= $oc('tableHinRueck')?' checked':'' ?>> <?= h(t('ls_cb_hin_rueck_tables')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="tableHeimAusw" value="1"<?= $oc('tableHeimAusw')?' checked':'' ?>> <?= h(t('ls_cb_heim_ausw_tables')) ?></label></td></tr>
          </table>

          <div style="font-size:.82rem;font-weight:700;color:var(--text);margin-bottom:10px;padding:8px 12px;background:var(--surface2);border-radius:var(--radius);max-width:500px"><?= h(t('ls_heading_table_markers')) ?></div>
          <table style="border-collapse:collapse;max-width:500px">
<?php   foreach ($colorMap as $key => $info) {
            $val = $o($key, '0');
            $colorVal = $o($key . 'Color', $info['default']); ?>
            <tr>
              <td style="padding:6px 12px;width:80px;text-align:right">
<?php       if ($key === 'Champ') { ?>
                <input type="checkbox" name="Champ_enabled" value="1"<?= $val!=='0'?' checked':'' ?>>
<?php       } else { ?>
                <select name="<?= $key ?>" style="width:64px;<?= $selSt ?>">
                  <?php for ($n = 0; $n <= 36; $n++) { ?><option value="<?= $n ?>"<?= $val==(string)$n?' selected':'' ?>><?= $n ?></option><?php } ?>
                </select>
<?php       } ?>
              </td>
              <td style="padding:6px 8px">
                <input type="color" name="<?= $key ?>Color" value="<?= h($colorVal) ?>"
                       title="<?= h(t('ls_marker_color_title')) ?>"
                       style="width:36px;height:28px;padding:0;border:1px solid var(--border);border-radius:6px;background:none;cursor:pointer;vertical-align:middle">
              </td>
              <td style="padding:6px 12px">
                <span style="background:<?= h($colorVal) ?>22;border-left:3px solid <?= h($colorVal) ?>;border-radius:4px;padding:3px 12px;font-size:.84rem;color:var(--text)"><?= h($info['label']) ?></span>
              </td>
            </tr>
<?php   } ?>
          </table>
          <p style="font-size:.78rem;color:var(--muted);max-width:500px;margin-top:8px"><?= h(t('ls_marker_color_hint')) ?></p>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: STRAFEN (nur Liga) - Strafpunkte/Straftore je Team, wirken sich in
// computeStandings() auf Punkte/Tordifferenz aus (siehe StandingsTrait.php)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'strafen' && !$isKO) {
    $strafen = $ligaSettingsData['strafen'] ?? [];
    // Kleiner Helfer: Vorzeichen-Auswahl (Dropdown) + Betrag (immer positive
    // Zahl) statt eines einzelnen Zahlenfelds mit Minuszeichen - auf vielen
    // Mobilgeräten zeigt die Zifferntastatur bei <input type="number"> kein
    // Minuszeichen an, siehe Rückmeldung.
    $strafField = static function (string $name, int $rowIndex, int $signedValue) use ($selSt) : string {
        $mag = abs($signedValue);
        $isNeg = $signedValue < 0;
        $html  = '<select name="' . $name . '_dir[' . $rowIndex . ']" style="padding:5px 2px;font-size:.85rem;' . $selSt . '">';
        $html .= '<option value="1"' . (!$isNeg ? ' selected' : '') . '>+</option>';
        $html .= '<option value="-1"' . ($isNeg ? ' selected' : '') . '>−</option>';
        $html .= '</select>';
        $html .= '<input type="number" name="' . $name . '_wert[' . $rowIndex . ']" min="0" inputmode="numeric" value="' . $mag . '" style="width:55px;text-align:center;' . $selSt . '">';
        return $html;
    };
    ?>
          <p style="font-size:.82rem;color:var(--muted);max-width:640px;margin-bottom:14px"><?= h(t('ls_strafen_hinweis')) ?></p>
<?php if (empty($ligaTeams)) { ?>
          <p class="empty-msg"><?= h(t('ls_strafen_keine_teams')) ?></p>
<?php } else { ?>
          <table style="border-collapse:collapse;width:100%;max-width:1050px">
            <tr>
              <th style="text-align:left;padding:6px 8px;font-size:.8rem;color:var(--muted)"><?= h(t('ls_strafen_col_team')) ?></th>
              <th style="text-align:center;padding:6px 8px;font-size:.8rem;color:var(--muted)"><?= h(t('ls_strafen_col_punkte')) ?></th>
              <th style="text-align:center;padding:6px 8px;font-size:.8rem;color:var(--muted)"><?= h(t('ls_strafen_col_minuspunkte')) ?></th>
              <th style="text-align:center;padding:6px 8px;font-size:.8rem;color:var(--muted)"><?= h(t('ls_strafen_col_erzielt')) ?></th>
              <th style="text-align:center;padding:6px 8px;font-size:.8rem;color:var(--muted)"><?= h(t('ls_strafen_col_gegentore')) ?></th>
              <th style="text-align:center;padding:6px 8px;font-size:.8rem;color:var(--muted)"><?= h(t('ls_strafen_col_ab_spieltag')) ?></th>
              <th style="text-align:left;padding:6px 8px;font-size:.8rem;color:var(--muted)"><?= h(t('ls_strafen_col_grund')) ?></th>
            </tr>
<?php foreach ($ligaTeams as $i => $team) {
    $teamId = (int)$team['id'];
    $s = $strafen[$teamId] ?? ['strafpunkte' => 0, 'straftore' => 0, 'tore_korrektur' => 0, 'minuspunkte_korrektur' => 0, 'ab_spieltag' => 0, 'grund' => '']; ?>
            <tr>
              <td style="padding:5px 8px;font-size:.87rem"><?= h($team['name']) ?>
                <input type="hidden" name="strafe_team_id[<?= $i ?>]" value="<?= $teamId ?>"></td>
              <td style="padding:5px 8px;text-align:center;white-space:nowrap"><?= $strafField('strafe_punkte', $i, (int)$s['strafpunkte']) ?></td>
              <td style="padding:5px 8px;text-align:center;white-space:nowrap"><?= $strafField('strafe_minus', $i, (int)($s['minuspunkte_korrektur'] ?? 0)) ?></td>
              <td style="padding:5px 8px;text-align:center;white-space:nowrap"><?= $strafField('strafe_erzielt', $i, (int)($s['tore_korrektur'] ?? 0)) ?></td>
              <td style="padding:5px 8px;text-align:center;white-space:nowrap"><?= $strafField('strafe_tore', $i, (int)$s['straftore']) ?></td>
              <td style="padding:5px 8px;text-align:center">
                <input type="number" name="strafe_ab_spieltag[<?= $i ?>]" min="0" inputmode="numeric" value="<?= (int)($s['ab_spieltag'] ?? 0) ?>" style="width:55px;text-align:center;<?= $selSt ?>" title="<?= h(t('ls_strafen_col_ab_spieltag_tip')) ?>">
              </td>
              <td style="padding:5px 8px">
                <input type="text" name="strafe_grund[<?= $i ?>]" value="<?= h($s['grund'] ?? '') ?>" maxlength="255" style="width:100%;box-sizing:border-box;<?= $selSt ?>">
              </td>
            </tr>
<?php } ?>
          </table>
<?php } ?>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: SPIELTAGE (nur Liga)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'spieltage' && !$isKO) { ?>
          <div style="background:var(--red,#ef4444);color:#fff;border-radius:var(--radius);padding:10px 14px;margin-bottom:16px;font-size:.84rem;max-width:500px">
            ⚠️ <strong><?= h(t('ls_warning_heading')) ?></strong> <?= h(t('ls_warning_text')) ?>
          </div>
          <table style="border-collapse:collapse">
            <tr>
              <td style="text-align:right;padding:8px 14px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anzahl_spieltage')) ?></td>
              <td style="padding:6px 10px">
                <input type="number" name="Rounds" value="<?= h($o('Rounds','0')) ?>" min="1" max="200"
                       style="width:80px;text-align:center;<?= $selSt ?>">
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:8px 14px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anzahl_spiele_pro_spieltag')) ?></td>
              <td style="padding:6px 10px">
                <input type="number" name="Matches" value="<?= h($o('Matches','0')) ?>" min="1" max="100"
                       style="width:80px;text-align:center;<?= $selSt ?>">
              </td>
            </tr>
          </table>
<?php } ?>

<?php if ($tab !== 'teams') { ?>
          <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-success"><?= h(t('ls_btn_save')) ?></button>
            <a href="?action=liga_detail&id=<?= $lid ?>" class="btn btn-muted" style="margin-left:8px"><?= h(t('common_cancel')) ?></a>
          </div>
        <?= csrfField() ?></form>
<?php } ?>
      </div>
