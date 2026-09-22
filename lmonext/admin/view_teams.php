<?php
/**
 * Project: LMOnext
 * Filename: view_teams.php
 * Fileversion: 1.7.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */

$teams     = $teamsData['teams']      ?? [];
$dupIds    = $teamsData['dup_ids']    ?? [];
$dupCount  = count($dupIds);
?>
      <!-- Filter + Suche -->
      <div class="card" style="margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <input type="text" id="team-filter" placeholder="<?= h(t('teams_filter_placeholder')) ?>"
                 oninput="filterTeams(this.value)"
                 style="flex:1;min-width:220px;background:var(--bg);border:1px solid var(--border);
                        color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.88rem">
          <?php if ($dupCount > 0) { ?>
          <button onclick="toggleDupsOnly()" id="btn-dups"
                  style="padding:7px 14px;border-radius:var(--radius);border:1px solid var(--yellow);
                         color:var(--yellow);background:transparent;cursor:pointer;font-size:.84rem;white-space:nowrap">
            <?= h(t('teams_btn_show_dups', ['n' => $dupCount])) ?>
          </button>
          <?php } ?>
          <span id="team-count" style="font-size:.82rem;color:var(--muted);white-space:nowrap">
            <?= h(t('teams_count_total', ['n' => count($teams)])) ?>
          </span>
        </div>
      </div>

      <?php if ($dupCount > 0) { ?>
      <div style="background:#f59e0b18;border:1px solid #f59e0b44;border-radius:var(--radius);
                  padding:12px 16px;margin-bottom:16px;font-size:.83rem;color:#fcd34d">
        ⚠️ <?= $dupCount === 1 ? t('teams_dup_found_one') : t('teams_dup_found_many', ['n' => $dupCount]) ?>
        <?= t('teams_dup_detail') ?>
      </div>
      <?php } ?>

      <div class="card" style="padding:0;overflow:hidden">
        <table class="tbl" id="teams-global-tbl" style="margin:0">
          <thead>
            <tr>
              <th style="width:50px" class="sortable" data-col="id"><?= h(t('teams_col_id')) ?> ⇅</th>
              <th style="width:44px"><?= h(t('teams_col_logo')) ?></th>
              <th class="sortable" data-col="name"><?= h(t('teams_col_name')) ?> ⇅</th>
              <th style="width:140px" class="sortable" data-col="mittel"><?= h(t('teams_col_mittel')) ?> ⇅</th>
              <th style="width:90px" class="sortable" data-col="kurz"><?= h(t('teams_col_kurz')) ?> ⇅</th>
              <th style="width:70px;text-align:center" class="sortable" data-col="ligen"><?= h(t('teams_col_ligen')) ?> ⇅</th>
              <th style="width:150px"></th>
            </tr>
          </thead>
          <tbody id="teams-tbody">
<?php
foreach ($teams as $t) {
    $isDup   = isset($dupIds[(int)$t['id']]);
    $dupType = $dupIds[(int)$t['id']] ?? '';
    $dupHint = $dupType === 'name' ? t('teams_dup_hint_name') : ($dupType === 'mittel' ? t('teams_dup_hint_mittel') : '');
    $rowBg       = $isDup ? 'background:#f59e0b0a;' : '';
?>
            <tr id="tr-<?= $t['id'] ?>"
                data-id="<?= $t['id'] ?>"
                data-name="<?= h(strtolower($t['name'])) ?>"
                data-mittel="<?= h(strtolower($t['mittel'])) ?>"
                data-kurz="<?= h(strtolower($t['kurz'])) ?>"
                data-ligen="<?= (int)$t['liga_count'] ?>"
                data-dup="<?= $isDup ? '1' : '0' ?>"
                style="<?= $rowBg ?>">
              <td class="text-muted" style="font-size:.8rem"><?= (int)$t['id'] ?></td>
              <td>
                <img src="<?= h(findTeamLogoPath((int)$t['id']) ?? 'assets/img/nopic-team.svg') ?>"
                     alt="" style="height:28px;width:28px;object-fit:contain;border-radius:4px;vertical-align:middle">
              </td>
              <td>
                <span class="team-disp-name" style="font-weight:500"><?= h($t['name']) ?></span>
                <?php if (!empty($t['url'])) { ?>
                <a href="<?= h($t['url']) ?>" target="_blank" rel="noopener" title="<?= h($t['url']) ?>"
                   style="margin-left:4px;text-decoration:none">🔗</a>
                <?php } ?>
                <?php if ($isDup) { ?>
                <span title="<?= h($dupHint) ?>"
                      style="font-size:.7rem;color:var(--yellow);margin-left:4px">⚠️ <?= h($dupHint) ?></span>
                <?php } ?>
              </td>
              <td class="text-muted team-disp-mittel"><?= h($t['mittel']) ?></td>
              <td>
                <?php if ($t['kurz']) { ?>
                <span class="chip chip-blue"><?= h($t['kurz']) ?></span>
                <?php } else { echo '–'; } ?>
              </td>
              <td style="text-align:center">
                <?php if ($t['liga_count'] > 0) { ?>
                <button onclick="showTeamLigen(<?= (int)$t['id'] ?>, <?= h(json_encode($t['name'] ?: '(kein Name)')) ?>)"
                        class="chip chip-blue"
                        style="font-size:.75rem;cursor:pointer;border:none;padding:2px 8px">
                  <?= (int)$t['liga_count'] ?>
                </button>
                <?php } else { ?>
                <span class="chip" style="font-size:.75rem;background:var(--surface2);color:var(--muted)">0</span>
                <?php } ?>
              </td>
              <td style="white-space:nowrap">
                <button class="btn btn-muted btn-sm"
                        onclick="openGlobalEdit(<?= $t['id'] ?>, <?= h(json_encode($t['name'])) ?>, <?= h(json_encode($t['mittel'])) ?>, <?= h(json_encode($t['kurz'])) ?>, <?= h(json_encode($t['url'] ?? '')) ?>)">✏️</button>
                <button class="btn btn-muted btn-sm" style="position:relative"
                        title="<?= h(t('teams_btn_links')) ?>"
                        onclick="openLinkModal(<?= $t['id'] ?>, <?= h(json_encode($t['name'])) ?>)">🔗<?php if ((int)($t['link_count'] ?? 0) > 0) { ?><span
                        style="position:absolute;top:-6px;right:-6px;background:var(--accent);color:#fff;
                               border-radius:50%;min-width:15px;height:15px;font-size:.62rem;line-height:15px;
                               text-align:center;padding:0 2px;font-weight:700"><?= (int)$t['link_count'] ?></span><?php } ?></button>
                <?php if ($isDup) { ?>
                <button class="btn btn-sm" style="background:#f59e0b22;border:1px solid var(--yellow);color:var(--yellow)"
                        onclick="openMerge(<?= $t['id'] ?>, <?= h(json_encode($t['name'])) ?>)"><?= h(t('teams_btn_merge_short')) ?></button>
                <?php } ?>
                <?php if ((int)$t['liga_count'] === 0) { ?>
                <form method="post" action="?action=delete_global_team" style="display:inline"
                      onsubmit="return confirm('<?= h(addslashes(t('teams_confirm_delete_team', ['name' => $t['name']]))) ?>')">
                  <input type="hidden" name="team_id" value="<?= (int)$t['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                <?= csrfField() ?></form>
                <?php } ?>
              </td>
            </tr>
            <!-- Inline-Edit-Zeile -->
            <tr id="edit-<?= $t['id'] ?>" style="display:none;background:var(--surface2)">
              <td colspan="7" style="padding:10px 12px">
                <form method="post" action="?action=save_global_team" enctype="multipart/form-data"
                      style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                  <input type="hidden" name="team_id" value="<?= (int)$t['id'] ?>">
                  <div>
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:2px"><?= h(t('teams_field_name_required')) ?></label>
                    <input type="text" name="team_name" id="ge-name-<?= $t['id'] ?>"
                           style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                                  border-radius:var(--radius);padding:5px 10px;font-size:.87rem;width:220px" required>
                  </div>
                  <div>
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:2px"><?= h(t('teams_field_mittel')) ?></label>
                    <input type="text" name="team_mittel" id="ge-mittel-<?= $t['id'] ?>"
                           style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                                  border-radius:var(--radius);padding:5px 10px;font-size:.87rem;width:140px">
                  </div>
                  <div>
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:2px"><?= h(t('teams_field_kurz')) ?></label>
                    <input type="text" name="team_kurz" id="ge-kurz-<?= $t['id'] ?>" maxlength="10"
                           style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                                  border-radius:var(--radius);padding:5px 10px;font-size:.87rem;width:70px">
                  </div>
                  <div>
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:2px"><?= h(t('teams_field_url')) ?></label>
                    <input type="text" name="team_url" id="ge-url-<?= $t['id'] ?>" placeholder="https://…"
                           style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                                  border-radius:var(--radius);padding:5px 10px;font-size:.87rem;width:220px">
                  </div>
                  <div>
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:2px"><?= h(t('teams_field_logo')) ?></label>
                    <div style="display:flex;align-items:center;gap:8px">
                      <img src="<?= h(findTeamLogoPath((int)$t['id']) ?? 'assets/img/nopic-team.svg') ?>" alt=""
                           style="height:28px;width:28px;object-fit:contain;border-radius:4px">
                      <input type="file" name="team_logo" accept=".svg,.jpg,.jpeg,.png,.gif"
                             style="font-size:.8rem;color:var(--text);max-width:180px">
                    </div>
                    <div style="font-size:.7rem;color:var(--muted);margin-top:2px"><?= h(t('teams_logo_hint')) ?></div>
                    <?php if (findTeamLogoPath((int)$t['id']) !== null) { ?>
                    <label style="font-size:.72rem;color:var(--muted);display:flex;align-items:center;gap:4px;margin-top:3px;cursor:pointer">
                      <input type="checkbox" name="remove_logo" value="1" style="accent-color:var(--red)">
                      <?= h(t('teams_logo_remove')) ?>
                    </label>
                    <?php } ?>
                  </div>
                  <button type="submit" class="btn btn-success btn-sm"><?= h(t('common_save')) ?></button>
                  <button type="button" class="btn btn-muted btn-sm"
                          onclick="document.getElementById('edit-<?= $t['id'] ?>').style.display='none'">✕</button>
                <?= csrfField() ?></form>
              </td>
            </tr>
<?php } ?>
          </tbody>
        </table>
      </div>

      <!-- Merge-Modal -->
      <div id="merge-modal" style="display:none;position:fixed;inset:0;background:#000a;z-index:9999;
                                    align-items:center;justify-content:center">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
                    padding:22px 26px;width:100%;max-width:520px;margin:16px;max-height:90vh;overflow-y:auto">
          <h2 style="font-size:1rem;margin-bottom:8px"><?= h(t('teams_merge_title')) ?></h2>
          <p style="font-size:.82rem;color:var(--muted);margin-bottom:16px">
            <?= h(t('teams_merge_desc')) ?>
          </p>
          <form method="post" action="?action=merge_teams">
            <input type="hidden" name="keep_id"   id="merge-keep-id">
            <input type="hidden" name="delete_id" id="merge-delete-id">

            <!-- ✓ Behalten -->
            <div style="margin-bottom:14px">
              <label style="font-size:.78rem;color:var(--green);display:block;margin-bottom:4px"><?= h(t('teams_merge_keep_label')) ?></label>
              <input type="text" id="merge-keep-q" placeholder="<?= h(t('teams_search_placeholder')) ?>" autocomplete="off"
                     oninput="mergeFilter('keep')"
                     style="width:100%;background:var(--bg);border:1px solid var(--green);color:var(--text);
                            border-radius:var(--radius);padding:7px 10px;font-size:.87rem;margin-bottom:4px">
              <div id="merge-keep-result" style="font-size:.82rem;color:var(--accent);min-height:18px;margin-bottom:2px"></div>
              <div id="merge-keep-list" style="display:none;background:var(--bg);border:1px solid var(--green);
                   border-radius:var(--radius);max-height:180px;overflow-y:auto"></div>
            </div>

            <!-- 🗑 Löschen -->
            <div style="margin-bottom:16px">
              <label style="font-size:.78rem;color:var(--red);display:block;margin-bottom:4px"><?= h(t('teams_merge_delete_label')) ?></label>
              <input type="text" id="merge-del-q" placeholder="<?= h(t('teams_search_placeholder')) ?>" autocomplete="off"
                     oninput="mergeFilter('del')"
                     style="width:100%;background:var(--bg);border:1px solid var(--red);color:var(--text);
                            border-radius:var(--radius);padding:7px 10px;font-size:.87rem;margin-bottom:4px">
              <div id="merge-del-result" style="font-size:.82rem;color:var(--red);min-height:18px;margin-bottom:2px"></div>
              <div id="merge-del-list" style="display:none;background:var(--bg);border:1px solid var(--red);
                   border-radius:var(--radius);max-height:180px;overflow-y:auto"></div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end">
              <button type="button" class="btn btn-muted btn-sm"
                      onclick="document.getElementById('merge-modal').style.display='none'"><?= h(t('common_cancel')) ?></button>
              <button type="submit" class="btn btn-danger btn-sm" id="merge-submit"
                      onclick="event.preventDefault();validateMerge().then(ok=>{if(ok)this.closest('form').submit();})">
                <?= h(t('teams_merge_submit')) ?>
              </button>
            </div>
          <?= csrfField() ?></form>
        </div>
      </div>

      <!-- Team-Verknüpfungen-Modal -->
      <div id="link-modal" style="display:none;position:fixed;inset:0;background:#000a;z-index:9999;
                                    align-items:center;justify-content:center">
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
                    padding:22px 26px;width:100%;max-width:520px;margin:16px;max-height:90vh;overflow-y:auto">
          <h2 style="font-size:1rem;margin-bottom:8px"><?= h(t('teams_links_title')) ?> – <span id="link-modal-team-name"></span></h2>
          <p style="font-size:.82rem;color:var(--muted);margin-bottom:16px"><?= h(t('teams_links_desc')) ?></p>

          <div id="link-existing-list" style="margin-bottom:18px"></div>

          <form method="post" action="?action=add_team_link" onsubmit="return validateAddLink()">
            <input type="hidden" name="team_a_id" id="link-team-a-id">
            <input type="hidden" name="team_b_id" id="link-team-b-id">

            <div style="margin-bottom:12px">
              <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('teams_links_pick_team')) ?></label>
              <input type="text" id="link-pick-q" placeholder="<?= h(t('teams_search_placeholder')) ?>" autocomplete="off"
                     oninput="linkFilter()"
                     style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                            border-radius:var(--radius);padding:7px 10px;font-size:.87rem;margin-bottom:4px">
              <div id="link-pick-result" style="font-size:.82rem;color:var(--accent);min-height:18px;margin-bottom:2px"></div>
              <div id="link-pick-list" style="display:none;background:var(--bg);border:1px solid var(--border);
                   border-radius:var(--radius);max-height:180px;overflow-y:auto"></div>
            </div>

            <div style="margin-bottom:12px">
              <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('teams_links_type')) ?></label>
              <select name="type" style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                            border-radius:var(--radius);padding:7px 10px;font-size:.87rem">
                <option value="umbenennung"><?= h(t('teams_links_type_umbenennung')) ?></option>
                <option value="fusion"><?= h(t('teams_links_type_fusion')) ?></option>
                <option value="abspaltung"><?= h(t('teams_links_type_abspaltung')) ?></option>
                <option value="sonstige"><?= h(t('teams_links_type_sonstige')) ?></option>
              </select>
            </div>

            <div style="margin-bottom:12px">
              <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('teams_links_direction')) ?></label>
              <div style="font-size:.82rem;display:flex;flex-direction:column;gap:6px">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                  <input type="radio" name="newer_team_choice" value="a" id="link-dir-a">
                  <span><?= h(t('teams_links_direction_prefix')) ?> <strong id="link-dir-a-name"></strong></span>
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                  <input type="radio" name="newer_team_choice" value="b" id="link-dir-b">
                  <span><?= h(t('teams_links_direction_prefix')) ?> <strong id="link-dir-b-name"></strong></span>
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                  <input type="radio" name="newer_team_choice" value="" checked>
                  <span style="color:var(--muted)"><?= h(t('teams_links_direction_unknown')) ?></span>
                </label>
              </div>
              <div style="font-size:.72rem;color:var(--muted);margin-top:4px"><?= h(t('teams_links_direction_hint')) ?></div>
            </div>

            <div style="margin-bottom:16px">
              <label style="font-size:.78rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('teams_links_note')) ?></label>
              <input type="text" name="note" maxlength="255" placeholder="<?= h(t('teams_links_note_placeholder')) ?>"
                     style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
                            border-radius:var(--radius);padding:7px 10px;font-size:.87rem">
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end">
              <button type="button" class="btn btn-muted btn-sm"
                      onclick="document.getElementById('link-modal').style.display='none'"><?= h(t('common_cancel')) ?></button>
              <button type="submit" class="btn btn-success btn-sm" id="link-submit" disabled>
                <?= h(t('teams_links_add')) ?>
              </button>
            </div>
          <?= csrfField() ?></form>
        </div>
      </div>

<script>
const i18nTeams = {
  alertPickKeep:      <?= json_encode(t('teams_js_alert_pick_keep')) ?>,
  alertPickDelete:    <?= json_encode(t('teams_js_alert_pick_delete')) ?>,
  alertPickDifferent: <?= json_encode(t('teams_js_alert_pick_different')) ?>,
  confirmMerge:       <?= json_encode(t('teams_js_confirm_merge')) ?>,
  countTpl:           <?= json_encode(t('teams_js_count')) ?>,
  ligenTitleTpl:      <?= json_encode(t('teams_js_ligen_title')) ?>,
  loading:            <?= json_encode(t('common_loading')) ?>,
  noLigen:            <?= json_encode(t('teams_js_no_ligen')) ?>,
  loadError:          <?= json_encode(t('common_load_error')) ?>,
  typeKo:             <?= json_encode(t('dash_type_ko')) ?>,
  typeLiga:           <?= json_encode(t('dash_type_liga')) ?>,
  linksLoading:       <?= json_encode(t('common_loading')) ?>,
  linksNone:          <?= json_encode(t('teams_links_none')) ?>,
  confirmUnlink:      <?= json_encode(t('teams_links_confirm_delete')) ?>,
  alertPickTeam:      <?= json_encode(t('teams_links_alert_pick')) ?>,
  linksDirLabel:      <?= json_encode(t('teams_links_direction_short')) ?>,
  linksUnknown:       <?= json_encode(t('teams_links_direction_unknown')) ?>,
  linksDirFailed:     <?= json_encode(t('teams_links_direction_failed')) ?>,
  linkTypes: {
    umbenennung: <?= json_encode(t('teams_links_type_umbenennung')) ?>,
    fusion:      <?= json_encode(t('teams_links_type_fusion')) ?>,
    abspaltung:  <?= json_encode(t('teams_links_type_abspaltung')) ?>,
    sonstige:    <?= json_encode(t('teams_links_type_sonstige')) ?>,
  },
};

// ── Inline-Edit ───────────────────────────────────────────────────────────────
function openGlobalEdit(id, name, mittel, kurz, url) {
  document.querySelectorAll('[id^="edit-"]').forEach(r => r.style.display = 'none');
  document.getElementById('ge-name-'   + id).value = name;
  document.getElementById('ge-mittel-' + id).value = mittel;
  document.getElementById('ge-kurz-'   + id).value = kurz;
  document.getElementById('ge-url-'    + id).value = url || '';
  document.getElementById('edit-' + id).style.display = '';
  document.getElementById('ge-name-' + id).focus();
}

// ── Merge-Modal mit Suche ─────────────────────────────────────────────────────
let mergeAllTeams = null; // lazy geladen
let mergeKeepId = null, mergeDelId = null;

async function loadMergeTeams() {
  if (mergeAllTeams !== null) return;
  try {
    const r = await fetch('?action=team_search_all');
    mergeAllTeams = await r.json();
  } catch(e) { mergeAllTeams = []; }
}

function normalize(s) {
  return s.toLowerCase()
    .replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss')
    .replace(/à|á|â|ã/g,'a').replace(/è|é|ê/g,'e').replace(/ì|í|î/g,'i')
    .replace(/ò|ó|ô|õ/g,'o').replace(/ù|ú|û/g,'u').replace(/ñ/g,'n')
    .replace(/ç/g,'c');
}

function fuzzyMatch(q, str) {
  if (!q) return true;
  // Normalisierte Varianten für Vergleich
  const qn  = normalize(q.trim());
  const strn = normalize(str);

  // 1. Direkte Teilstring-Übereinstimmung (normalisiert)
  if (strn.includes(qn)) return true;

  // 2. Alle Wörter müssen vorkommen (normalisiert)
  const words = qn.split(/\s+/).filter(Boolean);
  if (words.length > 1 && words.every(w => strn.includes(w))) return true;

  // 3. Abkürzungserkennung: Anfangsbuchstaben der Wörter
  const strWords = strn.split(/[\s\-\.]+/).filter(Boolean);
  const abbr = strWords.map(w => w[0]).join('');
  if (abbr.includes(qn)) return true;

  // 4. Trigram-Ähnlichkeit (toleriert Tippfehler)
  function trigrams(s) {
    const t = new Set();
    const p = '  ' + s + '  ';
    for (let i = 0; i < p.length - 2; i++) t.add(p.slice(i, i+3));
    return t;
  }
  const tq = trigrams(qn), ts = trigrams(strn);
  let common = 0;
  tq.forEach(t => { if (ts.has(t)) common++; });
  const similarity = (2 * common) / (tq.size + ts.size);
  const threshold = qn.length <= 4 ? 0.5 : qn.length <= 7 ? 0.35 : 0.25;
  return similarity >= threshold;
}

async function mergeFilter(side) {
  await loadMergeTeams();
  const q    = document.getElementById(side === 'keep' ? 'merge-keep-q' : 'merge-del-q').value;
  const list = document.getElementById(side === 'keep' ? 'merge-keep-list' : 'merge-del-list');
  // ID zurücksetzen wenn neu getippt
  if (side === 'keep') { mergeKeepId = null; document.getElementById('merge-keep-id').value = ''; }
  else                 { mergeDelId  = null; document.getElementById('merge-delete-id').value = ''; }
  document.getElementById('merge-submit').disabled = true;

  const results = mergeAllTeams.filter(t => fuzzyMatch(q, t.name + ' ' + t.mittel));
  if (!q || results.length === 0) { list.style.display = 'none'; return; }

  // Exakter Treffer → sofort auswählen
  const exact = results.find(t => t.name.toLowerCase() === q.toLowerCase());
  if (exact) { mergeSelect(side, exact.id, exact.name); list.style.display = 'none'; return; }

  list.innerHTML = results.slice(0, 30).map(t => {
    const escaped = JSON.stringify(t.name).replace(/'/g,"\\'");
    return `<div data-id="${t.id}" data-name="${t.name.replace(/"/g,'&quot;')}"
          style="padding:10px 12px;cursor:pointer;font-size:.87rem;border-bottom:1px solid var(--border)">
      <strong>${esc(t.name)}</strong>${t.mittel?' <span style="color:var(--muted);font-size:.78rem">'+esc(t.mittel)+'</span>':''}
     </div>`;
  }).join('');

  // Touch + Mouse Handler für jeden Eintrag
  list.querySelectorAll('div[data-id]').forEach(el => {
    const handler = (e) => {
      e.preventDefault(); e.stopPropagation();
      mergeSelect(side, parseInt(el.dataset.id), el.dataset.name);
      list.style.display = 'none';
    };
    el.addEventListener('touchstart', handler, {passive: false});
    el.addEventListener('mousedown',  handler);
  });

  list.style.display = 'block';
}

async function validateMerge() {
  await loadMergeTeams();
  // Falls ID noch nicht gesetzt: per exaktem Namen nachschlagen
  if (!mergeKeepId) {
    const q = document.getElementById('merge-keep-q').value.trim();
    const t = mergeAllTeams.find(t => t.name.toLowerCase() === q.toLowerCase());
    if (t) { mergeKeepId = t.id; document.getElementById('merge-keep-id').value = t.id; }
  }
  if (!mergeDelId) {
    const q = document.getElementById('merge-del-q').value.trim();
    const t = mergeAllTeams.find(t => t.name.toLowerCase() === q.toLowerCase());
    if (t) { mergeDelId = t.id; document.getElementById('merge-delete-id').value = t.id; }
  }
  if (!mergeKeepId) { alert(i18nTeams.alertPickKeep); return false; }
  if (!mergeDelId)  { alert(i18nTeams.alertPickDelete); return false; }
  if (mergeKeepId === mergeDelId) { alert(i18nTeams.alertPickDifferent); return false; }
  return confirm(i18nTeams.confirmMerge);
}

function mergeSelect(side, id, name) {
  const qId  = side === 'keep' ? 'merge-keep'  : 'merge-del';
  document.getElementById(qId + '-q').value      = name;
  document.getElementById(qId + '-list').style.display = 'none';
  document.getElementById(qId + '-result').textContent = '✓ ' + name + ' (ID ' + id + ')';
  if (side === 'keep') {
    mergeKeepId = id;
    document.getElementById('merge-keep-id').value = id;
  } else {
    mergeDelId = id;
    document.getElementById('merge-delete-id').value = id;
  }
  document.getElementById('merge-submit').disabled = !(mergeKeepId && mergeDelId && mergeKeepId !== mergeDelId);
}

async function openMerge(id, name) {
  await loadMergeTeams();
  mergeKeepId = null; mergeDelId = null;
  document.getElementById('merge-keep-id').value   = '';
  document.getElementById('merge-delete-id').value = '';
  document.getElementById('merge-keep-q').value    = '';
  document.getElementById('merge-del-q').value     = name;
  document.getElementById('merge-keep-result').textContent = '';
  document.getElementById('merge-del-result').textContent  = '✓ ' + name + ' (ID ' + id + ')';
  document.getElementById('merge-delete-id').value = id;
  mergeDelId = id;
  document.getElementById('merge-submit').disabled = true;
  document.getElementById('merge-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('merge-keep-q').focus(), 50);
}

// ── Team-Verknüpfungen-Modal (nutzt dieselbe Team-Suche wie das Merge-Modal) ──
let linkCurrentTeamId = null, linkPickedId = null;

async function openLinkModal(id, name) {
  await loadMergeTeams();
  linkCurrentTeamId = id;
  linkPickedId = null;
  document.getElementById('link-modal-team-name').textContent = name;
  document.getElementById('link-team-a-id').value = id;
  document.getElementById('link-team-b-id').value = '';
  document.getElementById('link-pick-q').value = '';
  document.getElementById('link-pick-result').textContent = '';
  document.getElementById('link-pick-list').style.display = 'none';
  document.getElementById('link-submit').disabled = true;
  document.getElementById('link-dir-a-name').textContent = name;
  document.getElementById('link-dir-b-name').textContent = '';
  document.getElementById('link-dir-a').disabled = true;
  document.getElementById('link-dir-b').disabled = true;
  document.querySelector('input[name="newer_team_choice"][value=""]').checked = true;
  document.getElementById('link-existing-list').innerHTML = '<div style="font-size:.82rem;color:var(--muted)">' + i18nTeams.linksLoading + '</div>';
  document.getElementById('link-modal').style.display = 'flex';

  try {
    const r = await fetch('?action=team_links_for&team_id=' + id);
    const links = await r.json();
    renderExistingLinks(links);
  } catch (e) {
    document.getElementById('link-existing-list').innerHTML = '';
  }
  setTimeout(() => document.getElementById('link-pick-q').focus(), 50);
}

function renderExistingLinks(links) {
  const box = document.getElementById('link-existing-list');
  if (!links.length) {
    box.innerHTML = '<div style="font-size:.82rem;color:var(--muted)">' + i18nTeams.linksNone + '</div>';
    return;
  }
  box.innerHTML = links.map(l => {
    const typeLabel = i18nTeams.linkTypes[l.type] || l.type;
    const note = l.note ? ' – <span style="color:var(--muted)">' + esc(l.note) + '</span>' : '';
    const newer = l.newer_team_id ? parseInt(l.newer_team_id) : '';
    return `<div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem">
      <div style="display:flex;align-items:center;gap:8px">
        <div style="flex:1">
          <strong>${esc(l.other_name)}</strong>
          <span class="chip chip-blue" style="font-size:.68rem;margin-left:4px">${esc(typeLabel)}</span>
          ${note}
        </div>
        <form method="post" action="?action=delete_team_link" onsubmit="return confirm(i18nTeams.confirmUnlink)">
          <input type="hidden" name="link_id" value="${l.id}">
          <input type="hidden" name="team_id" value="${linkCurrentTeamId}">
          <button type="submit" class="btn btn-danger btn-sm" style="font-size:.7rem;padding:3px 7px">✕</button>
        <?= csrfField() ?></form>
      </div>
      <div style="margin-top:4px">
        <select onchange="updateLinkDirection(${l.id}, this.value)"
                style="font-size:.76rem;background:var(--bg);border:1px solid var(--border);color:var(--muted);
                       border-radius:4px;padding:2px 5px">
          <option value=""${newer===''?' selected':''}>${i18nTeams.linksDirLabel}: ${i18nTeams.linksUnknown}</option>
          <option value="${linkCurrentTeamId}"${newer===linkCurrentTeamId?' selected':''}>${i18nTeams.linksDirLabel}: ${esc(document.getElementById('link-modal-team-name').textContent)}</option>
          <option value="${l.other_id}"${newer===l.other_id?' selected':''}>${i18nTeams.linksDirLabel}: ${esc(l.other_name)}</option>
        </select>
      </div>
    </div>`;
  }).join('');
}

async function updateLinkDirection(linkId, newerTeamId) {
  try {
    const body = new URLSearchParams({ link_id: linkId, newer_team_id: newerTeamId });
    const r = await fetch('?action=set_team_link_direction', { method: 'POST', body });
    const res = await r.json();
    if (!res.ok) { alert(i18nTeams.linksDirFailed); }
  } catch (e) { /* still fine, dropdown reflects the attempted choice */ }
}

function linkFilter() {
  const q = document.getElementById('link-pick-q').value;
  const list = document.getElementById('link-pick-list');
  linkPickedId = null;
  document.getElementById('link-team-b-id').value = '';
  document.getElementById('link-submit').disabled = true;

  const results = mergeAllTeams.filter(t => t.id !== linkCurrentTeamId && fuzzyMatch(q, t.name + ' ' + t.mittel));
  if (!q || results.length === 0) { list.style.display = 'none'; return; }

  const exact = results.find(t => t.name.toLowerCase() === q.toLowerCase());
  if (exact) { linkSelect(exact.id, exact.name); list.style.display = 'none'; return; }

  list.innerHTML = results.slice(0, 30).map(t => {
    return `<div data-id="${t.id}" data-name="${t.name.replace(/"/g,'&quot;')}"
          style="padding:10px 12px;cursor:pointer;font-size:.87rem;border-bottom:1px solid var(--border)">
      <strong>${esc(t.name)}</strong>${t.mittel?' <span style="color:var(--muted);font-size:.78rem">'+esc(t.mittel)+'</span>':''}
     </div>`;
  }).join('');

  list.querySelectorAll('div[data-id]').forEach(el => {
    const handler = (e) => {
      e.preventDefault(); e.stopPropagation();
      linkSelect(parseInt(el.dataset.id), el.dataset.name);
      list.style.display = 'none';
    };
    el.addEventListener('touchstart', handler, {passive: false});
    el.addEventListener('mousedown',  handler);
  });

  list.style.display = 'block';
}

function linkSelect(id, name) {
  linkPickedId = id;
  document.getElementById('link-pick-q').value = name;
  document.getElementById('link-pick-list').style.display = 'none';
  document.getElementById('link-pick-result').textContent = '✓ ' + name + ' (ID ' + id + ')';
  document.getElementById('link-team-b-id').value = id;
  document.getElementById('link-submit').disabled = false;
  document.getElementById('link-dir-b-name').textContent = name;
  document.getElementById('link-dir-a').disabled = false;
  document.getElementById('link-dir-b').disabled = false;
}

function validateAddLink() {
  if (!linkPickedId) { alert(i18nTeams.alertPickTeam); return false; }
  return true;
}

// ── Filter + Sortierung (global scope – wird per onclick aufgerufen) ──────────
let showDupsOnly = false;

function filterTeams(q) {
  q = q.trim();
  let visible = 0;
  document.querySelectorAll('#teams-tbody tr[data-id]').forEach(row => {
    const name   = row.dataset.name   ?? '';
    const mittel = row.dataset.mittel ?? '';
    const kurz   = row.dataset.kurz   ?? '';
    const isDup  = row.dataset.dup === '1';
    const matchQ = !q || fuzzyMatch(q, name + ' ' + mittel + ' ' + kurz);
    const matchD = !showDupsOnly || isDup;
    const show   = matchQ && matchD;
    row.style.display = show ? '' : 'none';
    const editRow = document.getElementById('edit-' + row.dataset.id);
    if (editRow && !show) editRow.style.display = 'none';
    if (show) visible++;
  });
  const cnt = document.getElementById('team-count');
  if (cnt) cnt.textContent = i18nTeams.countTpl.replace('{n}', visible);
}

function toggleDupsOnly() {
  showDupsOnly = !showDupsOnly;
  const btn = document.getElementById('btn-dups');
  if (btn) btn.style.background = showDupsOnly ? '#f59e0b33' : 'transparent';
  filterTeams(document.getElementById('team-filter')?.value ?? '');
}

document.addEventListener('DOMContentLoaded', function() {

const mergeModal = document.getElementById('merge-modal');
if (mergeModal) {
  mergeModal.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
}

const linkModal = document.getElementById('link-modal');
if (linkModal) {
  linkModal.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
}

// Dropdown schließen bei Klick außerhalb
document.addEventListener('click', e => {
  ['keep','del'].forEach(s => {
    const list = document.getElementById('merge-' + s + '-list');
    const inp  = document.getElementById('merge-' + (s==='keep'?'keep':'del') + '-q');
    if (list && inp && !list.contains(e.target) && e.target !== inp) {
      list.style.display = 'none';
    }
  });
  const linkList = document.getElementById('link-pick-list');
  const linkInp  = document.getElementById('link-pick-q');
  if (linkList && linkInp && !linkList.contains(e.target) && e.target !== linkInp) {
    linkList.style.display = 'none';
  }
});

// ── Spalten sortieren ─────────────────────────────────────────────────────────
let sortCol = 'name', sortAsc = true;
document.querySelectorAll('.sortable').forEach(th => {
  th.style.cursor = 'pointer';
  th.addEventListener('click', () => {
    const col = th.dataset.col;
    if (sortCol === col) { sortAsc = !sortAsc; } else { sortCol = col; sortAsc = true; }
    const tbody = document.getElementById('teams-tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr[data-id]'));
    rows.sort((a, b) => {
      let va = a.dataset[col] ?? '', vb = b.dataset[col] ?? '';
      if (col === 'id' || col === 'ligen') { va = parseInt(va)||0; vb = parseInt(vb)||0; return sortAsc ? va-vb : vb-va; }
      return sortAsc ? va.localeCompare(vb,'de') : vb.localeCompare(va,'de');
    });
    rows.forEach(r => {
      const edit = document.getElementById('edit-' + r.dataset.id);
      tbody.appendChild(r);
      if (edit) tbody.appendChild(edit);
    });
    document.querySelectorAll('.sortable').forEach(h => {
      const sp = h.querySelector('span') || h;
      h.textContent = h.textContent.replace(/[↑↓⇅]/g,'').trim() + ' ' + (h.dataset.col === col ? (sortAsc?'↑':'↓') : '⇅');
    });
  });
});
}); // DOMContentLoaded
</script>

<!-- Team-Ligen-Modal -->
<div id="team-ligen-modal" style="display:none;position:fixed;inset:0;background:#000a;z-index:9999;
                                    align-items:center;justify-content:center">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
              padding:22px 26px;width:100%;max-width:500px;margin:16px;max-height:80vh;overflow-y:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h2 style="font-size:1rem;margin:0" id="tl-title"><?= h(t('teams_ligen_modal_default')) ?></h2>
      <button onclick="document.getElementById('team-ligen-modal').style.display='none'"
              style="background:none;border:none;color:var(--muted);font-size:1.2rem;cursor:pointer">✕</button>
    </div>
    <div id="tl-content" style="font-size:.87rem"></div>
  </div>
</div>

<script>
function showTeamLigen(teamId, teamName) {
  const modal   = document.getElementById('team-ligen-modal');
  const title   = document.getElementById('tl-title');
  const content = document.getElementById('tl-content');
  title.textContent = i18nTeams.ligenTitleTpl.replace('{name}', teamName);
  content.innerHTML = '<p style="color:var(--muted);font-size:.83rem">' + i18nTeams.loading + '</p>';
  modal.style.display = 'flex';

  fetch('?action=team_ligen&team_id=' + teamId)
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        content.innerHTML = '<p style="color:var(--muted)">' + i18nTeams.noLigen + '</p>';
        return;
      }
      content.innerHTML = '<table style="width:100%;border-collapse:collapse">'
        + data.map(l => `
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:7px 4px">
              <a href="?action=liga_detail&id=${l.id}"
                 style="color:var(--accent);text-decoration:none;font-weight:500">${esc(l.name)}</a>
            </td>
            <td style="padding:7px 4px;text-align:right">
              <span class="chip ${l.type==='1'?'chip-yellow':'chip-blue'}" style="font-size:.72rem">
                ${l.type==='1'?i18nTeams.typeKo:i18nTeams.typeLiga}
              </span>
            </td>
          </tr>`).join('')
        + '</table>';
    })
    .catch(() => { content.innerHTML = '<p style="color:var(--red)">' + i18nTeams.loadError + '</p>'; });
}
document.getElementById('team-ligen-modal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
