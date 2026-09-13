<?php
/**
 * Project: LMOnext
 * Filename: view_addons.php
 * Fileversion: 2.2.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── View: Addon-Verwaltung (v2: Settings-Tab) ────────────────────────────────
// Zeigt alle entdeckten Addons (addon/*/addon.json) mit Status
// (aktiviert/deaktiviert), Version, Typ, Autor und Actions.
// Tab 1: Addon-Liste, Tab 2: Einstellungen (Token + Diagnostics)

/** @var AddonManager $addonManager */
$allAddons = $addonManager->getAllAddons();

// $currentLanguage wird sonst nur lokal in view_settings.php gesetzt und ist
// hier nicht automatisch verfügbar — direkt über die i18n-Funktion holen.
$currentLanguage = getCurrentLanguage('admin');

// ── GitHub Update-Check (cached, nicht-blocking) ─────────────────────────────
$updateInfo = $addonManager->checkGithubUpdates(false);
$updatesAvailable = 0;
foreach ($updateInfo as $uName => $uInfo) {
    if (($uInfo['update_available'] ?? false) && ($allAddons[$uName]['enabled'] ?? false)) {
        $updatesAvailable++;
    }
}

// ── Statistiken ──────────────────────────────────────────────────────────────
$totalAddons   = count($allAddons);
$enabledAddons = count(array_filter($allAddons, fn($a) => $a['enabled']));
$disabledAddons = $totalAddons - $enabledAddons;

// ── Nach Typ gruppieren ──────────────────────────────────────────────────────
$byType = ['admin' => [], 'frontend' => [], 'both' => [], 'standalone' => []];
foreach ($allAddons as $addon) {
    $type = $addon['manifest']['type'] ?? 'standalone';
    if (!isset($byType[$type])) {
        $type = 'standalone';
    }
    $byType[$type][] = $addon;
}

// ── Typ-Badges ──────────────────────────────────────────────────────────────
$typeBadges = [
    'admin'      => ['label' => 'Admin',     'color' => 'var(--accent)'],
    'frontend'   => ['label' => 'Frontend',  'color' => 'var(--green)'],
    'both'       => ['label' => 'Admin+FE',  'color' => 'var(--yellow)'],
    'standalone' => ['label' => 'Standalone', 'color' => 'var(--muted)'],
];

// ── Tab-Umschaltung ──────────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'all';
$tab = in_array($tab, ['all', 'admin', 'frontend', 'both', 'standalone', 'settings'], true) ? $tab : 'all';

$displayAddons = ($tab === 'all' || $tab === 'settings') ? $allAddons : ($byType[$tab] ?? []);

// ── Sortierung: aktivierte zuerst, dann nach Namen ──────────────────────────
usort($displayAddons, function ($a, $b) {
    if ($a['enabled'] !== $b['enabled']) {
        return $a['enabled'] ? -1 : 1;
    }
    return strcasecmp($a['manifest']['name'] ?? '', $b['manifest']['name'] ?? '');
});
?>

<div class="content-inner" style="padding:24px">

  <!-- ── Stat-Karten ─────────────────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
    <div class="card" style="margin:0;padding:18px">
      <div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_stat_total')) ?></div>
      <div style="font-size:2rem;font-weight:700;margin-top:4px"><?= $totalAddons ?></div>
    </div>
    <div class="card" style="margin:0;padding:18px;border-color:var(--green)">
      <div style="font-size:.75rem;color:var(--green);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_stat_enabled')) ?></div>
      <div style="font-size:2rem;font-weight:700;margin-top:4px;color:var(--green)"><?= $enabledAddons ?></div>
    </div>
    <div class="card" style="margin:0;padding:18px">
      <div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_stat_disabled')) ?></div>
      <div style="font-size:2rem;font-weight:700;margin-top:4px;color:var(--muted)"><?= $disabledAddons ?></div>
    </div>
  </div>

  <!-- ── Tab-Navigation ─────────────────────────────────────────────────── -->
  <div style="display:flex;gap:6px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:0">
    <?php
    $tabs = [
        'all'       => ['label' => t('addons_tab_all'),       'count' => $totalAddons],
        'admin'     => ['label' => t('addons_tab_admin'),    'count' => count($byType['admin'])],
        'frontend'  => ['label' => t('addons_tab_frontend'),  'count' => count($byType['frontend'])],
        'both'      => ['label' => t('addons_tab_both'),      'count' => count($byType['both'])],
        'standalone'=> ['label' => t('addons_tab_standalone'),'count' => count($byType['standalone'])],
        'settings'  => ['label' => '⚙ ' . t('addons_tab_settings'), 'count' => null],
    ];
    foreach ($tabs as $tabKey => $tabInfo) {
        $active = $tab === $tabKey;
    ?>
      <a href="?action=addons&tab=<?= $tabKey ?>"
         style="padding:10px 16px;border-bottom:2px solid <?= $active ? 'var(--accent)' : 'transparent' ?>;
                color:<?= $active ? 'var(--accent)' : 'var(--muted)' ?>;text-decoration:none;font-size:.85rem;font-weight:<?= $active ? '600' : '400' ?>;
                margin-bottom:-1px">
        <?= h($tabInfo['label']) ?>
        <?php if ($tabInfo['count'] !== null): ?>
          <span style="background:var(--surface2);border-radius:10px;padding:1px 8px;font-size:.72rem;color:var(--muted)"><?= $tabInfo['count'] ?></span>
        <?php endif; ?>
      </a>
    <?php } ?>
  </div>

<?php if ($tab === 'settings'): ?>
  <!-- ════════════════════════════════════════════════════════════════════════
       TAB: Einstellungen (Token + Diagnostics)
       ════════════════════════════════════════════════════════════════════════ -->

    <?php
    $ghToken    = $addonManager->getGithubToken();
    $tokenSet   = $ghToken !== '';
    $allowFopen = (bool) ini_get('allow_url_fopen');
    $curlAvail  = function_exists('curl_init');
    $zipAvail   = class_exists('ZipArchive', false);
    $cacheFile  = sys_get_temp_dir() . '/lmonext_addon_updates_v2.json';
    $cacheData  = is_file($cacheFile) ? json_decode(@file_get_contents($cacheFile), true) : null;
    $cacheResults = is_array($cacheData) ? ($cacheData['results'] ?? []) : [];
    $lastCheck  = is_array($cacheData) ? ($cacheData['timestamp'] ?? null) : null;
    $cacheAge   = $lastCheck !== null ? time() - (int)$lastCheck : null;
    ?>

    <!-- ── GitHub Token ─────────────────────────────────────────────────── -->
    <div class="card" style="margin:0 0 20px;padding:24px">
      <h3 style="margin:0 0 4px;font-size:1.05rem;display:flex;align-items:center;gap:8px">
        🔑 <?= h(t('addons_token_title')) ?>
      </h3>
      <p style="margin:0 0 16px;font-size:.82rem;color:var(--muted);max-width:560px">
        <?= h(t('addons_token_desc')) ?>
      </p>

      <form method="post" action="?action=addons&tab=settings" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <?= csrfField() ?>
        <input type="hidden" name="addon_action" value="save_token">
        <div style="flex:1;min-width:280px">
          <label style="display:block;font-size:.75rem;color:var(--muted);margin-bottom:4px;font-weight:600">
            GitHub Personal Access Token
          </label>
          <input type="password" name="github_token" value="<?= h($ghToken) ?>"
                 placeholder="ghp_… oder github_pat_…"
                 autocomplete="off"
                 style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:var(--radius);
                        font-family:monospace;font-size:.85rem;background:var(--surface);color:var(--text)">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:10px 20px">
          💾 <?= h(t('addons_token_btn_save')) ?>
        </button>
        <?php if ($tokenSet): ?>
          <button type="submit" formmethod="post" name="addon_action" value="delete_token"
                  class="btn btn-muted"
                  style="padding:10px 16px"
                  onclick="return confirm('<?= h(t('addons_token_confirm_delete')) ?>')">
            🗑 <?= h(t('addons_token_btn_delete')) ?>
          </button>
        <?php endif; ?>
      </form>

      <div style="margin-top:14px;padding:10px 14px;border-radius:var(--radius);font-size:.78rem;
                  background:<?= $tokenSet ? 'var(--green)' : 'var(--surface2)' ?>22;
                  color:<?= $tokenSet ? 'var(--green)' : 'var(--muted)' ?>;
                  border:1px solid <?= $tokenSet ? 'var(--green)' : 'var(--border)' ?>44">
        <?php if ($tokenSet): ?>
          ✓ Token aktiv — API-Limit: 5.000 Requests/Std.
        <?php else: ?>
          ⚠ Kein Token gesetzt — API-Limit: 60 Requests/Std.
        <?php endif; ?>
      </div>

      <details style="margin-top:14px">
        <summary style="cursor:pointer;font-size:.78rem;color:var(--muted);user-select:none">
          <?= h(t('addons_token_help')) ?>
        </summary>
        <div style="margin-top:8px;padding:12px 16px;background:var(--surface2);border-radius:var(--radius);
                    font-size:.78rem;color:var(--muted);line-height:1.6">
          1. <a href="https://github.com/settings/tokens" target="_blank" rel="noopener" style="color:var(--accent)">GitHub → Settings → Developer settings → Personal access tokens</a><br>
          2. <strong>Generate new token (classic)</strong> wählen<br>
          3. Scope <code style="background:var(--surface2);padding:1px 4px;border-radius:3px">public_repo</code> anhaken (reicht für öffentliche Repos)<br>
          4. Token kopieren und hier einfügen<br>
          5. <strong>Speichern</strong> — der Token wird in der Datenbank abgelegt.
        </div>
      </details>
    </div>

    <!-- ── Addon per ZIP installieren ──────────────────────────────────── -->
<?php if (defined('DEMO_MODE') && DEMO_MODE === true) { ?>
    <div class="card" style="margin:0 0 20px;padding:24px;border:1px solid var(--warning, #b45309)">
      <h3 style="margin:0 0 4px;font-size:1.05rem;display:flex;align-items:center;gap:8px">
        🔒 <?= h(t('addons_install_title')) ?>
      </h3>
      <p style="margin:0;font-size:.85rem;color:var(--muted);max-width:560px">
        <?= h(t('addons_demo_mode_notice')) ?>
      </p>
    </div>
<?php } else { ?>
    <div class="card" style="margin:0 0 20px;padding:24px">
      <h3 style="margin:0 0 4px;font-size:1.05rem;display:flex;align-items:center;gap:8px">
        📦 <?= h(t('addons_install_title')) ?>
      </h3>
      <p style="margin:0 0 16px;font-size:.82rem;color:var(--muted);max-width:560px">
        <?= h(t('addons_install_desc')) ?>
      </p>

      <form method="post" action="?action=addons&tab=settings"
            enctype="multipart/form-data"
            style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap"
            onsubmit="return this['addon_zip'].files.length > 0">
        <?= csrfField() ?>
        <input type="hidden" name="addon_action" value="install_zip">
        <input type="hidden" name="MAX_FILE_SIZE" value="7340032">
        <div style="flex:1;min-width:280px">
          <label style="display:block;font-size:.75rem;color:var(--muted);margin-bottom:4px;font-weight:600">
            <?= h(t('addons_install_choose')) ?>
          </label>
          <input type="file" name="addon_zip" accept=".zip"
                 style="width:100%;padding:8px 14px;border:1px solid var(--border);border-radius:var(--radius);
                        font-size:.85rem;background:var(--surface);color:var(--text)">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:10px 20px"
                onclick="return this.form['addon_zip'].files.length > 0">
          ⬆ <?= h(t('addons_install_btn')) ?>
        </button>
      </form>

      <div style="margin-top:12px;padding:10px 14px;border-radius:var(--radius);font-size:.78rem;
                  background:var(--surface2);color:var(--muted);border:1px solid var(--border)">
        💡 <?= h(t('addons_install_hint')) ?>
      </div>
    </div>
<?php } ?>

    <!-- ── Diagnostics ──────────────────────────────────────────────────── -->
    <div class="card" style="margin:0 0 20px;padding:24px">
      <h3 style="margin:0 0 16px;font-size:1.05rem;display:flex;align-items:center;gap:8px">
        🔧 Diagnostics
      </h3>

      <table style="width:100%;border-collapse:collapse;font-size:.82rem">
        <tbody>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 14px;color:var(--muted);width:260px">allow_url_fopen</td>
            <td style="padding:10px 14px">
              <?php if ($allowFopen): ?>
                <span style="color:var(--green);font-weight:600">✓ On</span>
              <?php else: ?>
                <span style="color:var(--red);font-weight:600">✗ Off</span>
              <?php endif; ?>
              <span style="color:var(--muted);margin-left:8px;font-size:.75rem">file_get_contents für HTTP-Calls</span>
            </td>
          </tr>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 14px;color:var(--muted)">cURL-Erweiterung</td>
            <td style="padding:10px 14px">
              <?php if ($curlAvail): ?>
                <span style="color:var(--green);font-weight:600">✓ Verfügbar</span>
              <?php else: ?>
                <span style="color:var(--red);font-weight:600">✗ Fehlt</span>
              <?php endif; ?>
              <span style="color:var(--muted);margin-left:8px;font-size:.75rem">Fallback für HTTP-Calls</span>
            </td>
          </tr>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 14px;color:var(--muted)">ZipArchive</td>
            <td style="padding:10px 14px">
              <?php if ($zipAvail): ?>
                <span style="color:var(--green);font-weight:600">✓ Verfügbar</span>
              <?php else: ?>
                <span style="color:var(--red);font-weight:600">✗ Fehlt</span>
              <?php endif; ?>
              <span style="color:var(--muted);margin-left:8px;font-size:.75rem">für Auto-Update-Installation</span>
            </td>
          </tr>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 14px;color:var(--muted)">GitHub Token</td>
            <td style="padding:10px 14px">
              <?php if ($tokenSet): ?>
                <span style="color:var(--green);font-weight:600">✓ Gesetzt</span>
                <span style="color:var(--muted);margin-left:8px;font-size:.75rem">5.000 Req/Std</span>
              <?php else: ?>
                <span style="color:var(--yellow);font-weight:600">⚠ Nicht gesetzt</span>
                <span style="color:var(--muted);margin-left:8px;font-size:.75rem">60 Req/Std</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 14px;color:var(--muted)">Update-Cache</td>
            <td style="padding:10px 14px">
              <?php if ($lastCheck !== null): ?>
                <span style="color:var(--text)">
                  <?= h(date('d.m.Y H:i', (int)$lastCheck)) ?>
                </span>
                <span style="color:var(--muted);margin-left:8px;font-size:.75rem">
                  (<?= $cacheAge !== null ? round($cacheAge / 60) . ' Min her' : '' ?>, TTL 60 Min)
                </span>
              <?php else: ?>
                <span style="color:var(--muted)">Kein Cache vorhanden</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 14px;color:var(--muted)">LMONEXT_VERSION</td>
            <td style="padding:10px 14px;font-family:monospace;color:var(--text)">
              v<?= h(LMONEXT_VERSION) ?>
            </td>
          </tr>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 14px;color:var(--muted)">PHP-Version</td>
            <td style="padding:10px 14px;font-family:monospace;color:var(--text)">
              <?= h(PHP_VERSION) ?>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 14px;color:var(--muted)">sys_get_temp_dir()</td>
            <td style="padding:10px 14px;font-family:monospace;font-size:.75rem;color:var(--text);max-width:400px;word-break:break-all">
              <?= h(sys_get_temp_dir()) ?>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- ── Update-Check Button ──────────────────────────────────────── -->
      <div style="margin-top:16px;text-align:right">
        <form method="post" action="?action=addons&tab=settings" style="display:inline">
          <?= csrfField() ?>
          <input type="hidden" name="addon_action" value="check_updates">
          <button type="submit" class="btn btn-muted btn-sm" style="text-decoration:none">
            🔄 <?= h(t('addons_btn_check_updates')) ?>
          </button>
        </form>
      </div>
    </div>

    <!-- ── Per-Addon Update-Status aus Cache ─────────────────────────── -->
    <?php if (!empty($cacheResults)): ?>
    <div class="card" style="margin:0;padding:24px">
      <h4 style="margin:0 0 12px;font-size:.95rem">📋 Update-Check-Ergebnisse</h4>
      <table style="width:100%;border-collapse:collapse;font-size:.78rem">
        <thead>
          <tr style="border-bottom:1px solid var(--border)">
            <th style="text-align:left;padding:8px 12px;color:var(--muted);font-weight:600">Addon</th>
            <th style="text-align:left;padding:8px 12px;color:var(--muted);font-weight:600">Aktuell</th>
            <th style="text-align:left;padding:8px 12px;color:var(--muted);font-weight:600">Latest</th>
            <th style="text-align:left;padding:8px 12px;color:var(--muted);font-weight:600">Status</th>
            <th style="text-align:left;padding:8px 12px;color:var(--muted);font-weight:600">Methode</th>
            <th style="text-align:right;padding:8px 12px;color:var(--muted);font-weight:600">HTTP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cacheResults as $cName => $cInfo): ?>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:8px 12px;font-weight:600"><?= h($cName) ?></td>
              <td style="padding:8px 12px;font-family:monospace;color:var(--muted)">v<?= h($cInfo['current'] ?? '?') ?></td>
              <td style="padding:8px 12px;font-family:monospace">
                <?php if (!empty($cInfo['latest'])): ?>
                  <span style="color:var(--green)">v<?= h($cInfo['latest']) ?></span>
                <?php else: ?>
                  <span style="color:var(--muted)">—</span>
                <?php endif; ?>
              </td>
              <td style="padding:8px 12px">
                <?php
                $err = $cInfo['error'] ?? '';
                if ($err === '' && !empty($cInfo['latest'])):
                  if (!empty($cInfo['update_available'])): ?>
                    <span style="color:var(--green);font-weight:600">↑ Update verfügbar</span>
                  <?php else: ?>
                    <span style="color:var(--muted)">aktuell</span>
                  <?php endif; ?>
                <?php elseif ($err === 'no_github'): ?>
                  <span style="color:var(--muted)">kein GitHub-Repo</span>
                <?php elseif ($err === 'no_release'): ?>
                  <span style="color:var(--muted)">kein Release</span>
                <?php elseif ($err === 'rate_limited'): ?>
                  <span style="color:var(--red);font-weight:600">⚠ Rate-Limit!</span>
                <?php elseif ($err === 'fetch_failed'): ?>
                  <span style="color:var(--red)">Fetch-Fehler (HTTP <?= h($cInfo['http_code'] ?? '?') ?>)</span>
                <?php elseif ($err === 'no_http_client'): ?>
                  <span style="color:var(--red)">kein HTTP-Client</span>
                <?php else: ?>
                  <span style="color:var(--red)"><?= h($err) ?></span>
                <?php endif; ?>
              </td>
              <td style="padding:8px 12px;font-family:monospace;font-size:.72rem;color:var(--muted)">
                <?= h($cInfo['method'] ?? '') ?>
              </td>
              <td style="padding:8px 12px;text-align:right;font-family:monospace;font-size:.72rem;color:var(--muted)">
                <?= h($cInfo['http_code'] ?? '') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="card" style="margin:0;padding:24px;text-align:center;color:var(--muted)">
      <p style="font-size:.85rem">Noch kein Update-Check ausgeführt. Klicke oben auf "Updates prüfen".</p>
    </div>
    <?php endif; ?>

<?php else: ?>
  <!-- ════════════════════════════════════════════════════════════════════════
       TAB: Addon-Liste (all/admin/frontend/both/standalone)
       ════════════════════════════════════════════════════════════════════════ -->

  <!-- ── Update-Banner ─────────────────────────────────────────────────── -->
  <?php if ($updatesAvailable > 0): ?>
    <div class="card" style="margin:0 0 16px;padding:14px 18px;border-color:var(--yellow);
                              display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:1.2rem">🔄</span>
        <span style="font-size:.9rem">
          <strong><?= $updatesAvailable ?></strong> <?= h(t('addons_updates_badge_text')) ?>
        </span>
      </div>
      <form method="post" action="?action=addons" style="display:inline">
        <?= csrfField() ?>
        <input type="hidden" name="addon_action" value="check_updates">
        <button type="submit" class="btn btn-primary btn-sm" style="text-decoration:none">
          🔄 <?= h(t('addons_btn_check_updates')) ?>
        </button>
      </form>
    </div>
  <?php else: ?>
    <div style="margin-bottom:16px;text-align:right">
      <form method="post" action="?action=addons" style="display:inline">
        <?= csrfField() ?>
        <input type="hidden" name="addon_action" value="check_updates">
        <button type="submit" class="btn btn-muted btn-sm" style="text-decoration:none">
          🔄 <?= h(t('addons_btn_check_updates')) ?>
        </button>
      </form>
    </div>
  <?php endif; ?>

  <!-- ── Addon-Liste ────────────────────────────────────────────────────── -->
  <?php if (empty($displayAddons)): ?>
    <div class="card" style="text-align:center;padding:48px;color:var(--muted)">
      <p style="font-size:1.1rem"><?= h(t('addons_empty')) ?></p>
      <p style="font-size:.85rem;margin-top:8px"><?= h(t('addons_empty_hint')) ?></p>
    </div>
  <?php else: ?>

    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr style="border-bottom:1px solid var(--border)">
          <th style="text-align:left;padding:12px 14px;font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_col_addon')) ?></th>
          <th style="text-align:left;padding:12px 14px;font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_col_type')) ?></th>
          <th style="text-align:left;padding:12px 14px;font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_col_version')) ?></th>
          <th style="text-align:left;padding:12px 14px;font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_col_description')) ?></th>
          <th style="text-align:center;padding:12px 14px;font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_col_status')) ?></th>
          <th style="text-align:right;padding:12px 14px;font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px"><?= h(t('addons_col_actions')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($displayAddons as $addon): ?>
          <?php
          $m      = $addon['manifest'];
          $name   = $m['name'] ?? 'unknown';
          $enabled = $addon['enabled'];
          $type   = $m['type'] ?? 'standalone';
          $badge  = $typeBadges[$type] ?? $typeBadges['standalone'];
          $desc   = $m['description'][$currentLanguage] ?? $m['description']['de'] ?? $m['description']['en'] ?? '';
          $author = $m['author'] ?? '';
          $home   = $m['homepage'] ?? '';
          $ver    = $m['version'] ?? '0.0.0';
          $minCore = $m['min_core_version'] ?? '';
          $deps   = $m['dependencies'] ?? [];
          $tables = $m['db_tables'] ?? [];

          $depMissing = [];
          foreach ($deps as $dep) {
              if (!$addonManager->isEnabled($dep)) {
                  $depMissing[] = $dep;
              }
          }
          $canEnable = empty($depMissing);
          ?>
          <tr style="border-bottom:1px solid var(--border);transition:background .15s"
              onmouseover="this.style.background='var(--surface2)'"
              onmouseout="this.style.background=''">

            <!-- Name + Icon -->
            <td style="padding:14px">
              <div style="display:flex;align-items:center;gap:12px">
                <div style="width:36px;height:36px;border-radius:var(--radius);background:var(--surface2);
                            display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">
                  <?= h($m['icon'] ?? '🧩') ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:.95rem">
                    <?= h($name) ?>
                    <?php if ($home): ?>
                      <a href="<?= h($home) ?>" target="_blank" rel="noopener"
                         style="color:var(--muted);font-size:.75rem;text-decoration:none;margin-left:4px">↗</a>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:.75rem;color:var(--muted);margin-top:2px">
                    <?= h($author) ?>
                    <?php if ($minCore): ?>
                      · <span title="<?= h(t('addons_min_core')) ?>">core ≥ <?= h($minCore) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($tables)): ?>
                      · <span title="<?= h(t('addons_db_tables')) ?>: <?= h(implode(', ', $tables)) ?>"><?= count($tables) ?> <?= h(t('addons_tables_short')) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </td>

            <!-- Typ -->
            <td style="padding:14px">
              <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600;
                          background:<?= $badge['color'] ?>22;color:<?= $badge['color'] ?>;
                          border:1px solid <?= $badge['color'] ?>44">
                <?= h($badge['label']) ?>
              </span>
            </td>

            <!-- Version -->
            <td style="padding:14px;font-size:.85rem;color:var(--muted);font-family:monospace">
              v<?= h($ver) ?>
              <?php
              $uInfo = $updateInfo[$name] ?? null;
              if ($uInfo && ($uInfo['update_available'] ?? false)):
                  $latest = h($uInfo['latest']);
              ?>
                <div style="display:inline-block;margin-left:6px">
                  <span style="display:inline-block;padding:2px 8px;border-radius:10px;
                              font-size:.7rem;font-weight:600;background:var(--yellow)22;color:var(--yellow);
                              border:1px solid var(--yellow)44;margin-right:4px">
                    ⬆ v<?= $latest ?>
                  </span>
<?php if (defined('DEMO_MODE') && DEMO_MODE === true) { ?>
                  <span style="font-size:.7rem;color:var(--muted)" title="<?= h(t('addons_demo_mode_notice')) ?>">🔒</span>
<?php } else { ?>
                  <form method="post" action="?action=addons" style="display:inline-block">
                    <?= csrfField() ?>
                    <input type="hidden" name="addon_action" value="install_update">
                    <input type="hidden" name="addon_name" value="<?= h($name) ?>">
                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:.7rem;padding:3px 10px"
                            onclick="return confirm('<?= h(t('addons_update_confirm', ['name' => $name, 'latest' => $latest])) ?>')"
                            title="<?= h(t('addons_update_tooltip', ['latest' => $latest])) ?>">
                      <?= h(t('addons_update_btn')) ?>
                    </button>
                  </form>
<?php } ?>
                </div>
              <?php
              elseif ($uInfo && ($uInfo['error'] ?? '') === 'no_github'):
                  // Keine Homepage → kein Badge
              elseif ($uInfo && ($uInfo['error'] ?? '') !== ''):
                  $errCode = $uInfo['error'] ?? '';
                  $errLabels = [
                      'fetch_failed'    => 'GitHub nicht erreichbar',
                      'rate_limited'    => 'GitHub Rate-Limit (60 req/h — später erneut)',
                      'no_release'      => 'Kein Release auf GitHub (404)',
                      'no_http_client'  => 'Keine HTTP-Lib (file_get_contents/curl)',
                  ];
                  $errLabel = $errLabels[$errCode] ?? $errCode;
              ?>
                <span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:10px;
                            font-size:.7rem;font-weight:600;background:var(--red)22;color:var(--red);
                            border:1px solid var(--red)44"
                      title="<?= h($errLabel) ?>">
                  ⚠
                </span>
              <?php endif; ?>
            </td>

            <!-- Beschreibung -->
            <td style="padding:14px;font-size:.85rem;color:var(--text);max-width:280px">
              <?= h($desc) ?>
              <?php if (!empty($deps)): ?>
                <div style="margin-top:4px;font-size:.72rem;color:var(--muted)">
                  <?= h(t('addons_depends_on')) ?>: <?= h(implode(', ', $deps)) ?>
                  <?php if (!empty($depMissing)): ?>
                    <span style="color:var(--red)">⚠ <?= h(implode(', ', $depMissing)) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>

            <!-- Status -->
            <td style="padding:14px;text-align:center">
              <?php if ($enabled): ?>
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.82rem;color:var(--green);font-weight:600">
                  <span style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block"></span>
                  <?= h(t('addons_status_active')) ?>
                </span>
              <?php else: ?>
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.82rem;color:var(--muted)">
                  <span style="width:8px;height:8px;border-radius:50%;background:var(--muted);display:inline-block"></span>
                  <?= h(t('addons_status_inactive')) ?>
                </span>
              <?php endif; ?>
            </td>

            <!-- Actions -->
            <td style="padding:14px;text-align:right">
              <?php if ($enabled): ?>
                <form method="post" action="?action=addons" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="addon_action" value="disable">
                  <input type="hidden" name="addon_name" value="<?= h($name) ?>">
                  <button type="submit" class="btn btn-muted btn-sm"
                          style="text-decoration:none"
                          onclick="return confirm('<?= h(t('addons_confirm_disable', ['name' => $name])) ?>')">
                    ⏻ <?= h(t('addons_btn_disable')) ?>
                  </button>
                </form>
              <?php else: ?>
                <form method="post" action="?action=addons" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="addon_action" value="enable">
                  <input type="hidden" name="addon_name" value="<?= h($name) ?>">
                  <button type="submit" class="btn btn-primary btn-sm"
                          style="text-decoration:none" <?= $canEnable ? '' : 'disabled title="' . h(t('addons_dep_missing')) . '"' ?>>
                    ✓ <?= h(t('addons_btn_enable')) ?>
                  </button>
                </form>
<?php
    $ownTables = $addonManager->getDbTables($name);
    if (!empty($ownTables)):
?>
                <form method="post" action="?action=addons" style="display:inline;margin-left:6px">
                  <?= csrfField() ?>
                  <input type="hidden" name="addon_action" value="purge_data">
                  <input type="hidden" name="addon_name" value="<?= h($name) ?>">
                  <button type="submit" class="btn btn-muted btn-sm"
                          style="text-decoration:none;color:var(--red, #c0392b)"
                          title="<?= h(t('addons_purge_hint')) ?>"
                          onclick="return confirm('<?= h(t('addons_purge_confirm', ['name' => $name, 'tables' => implode(', ', $ownTables)])) ?>')">
                    <?= h(t('addons_purge_btn')) ?>
                  </button>
                </form>
<?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- ── Detail-Info ───────────────────────────────────────────────────── -->
    <div style="margin-top:20px;padding:14px 18px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);font-size:.8rem;color:var(--muted)">
      <strong style="color:var(--text)"><?= h(t('addons_info_title')) ?></strong><br>
      <?= h(t('addons_info_text')) ?>
    </div>

  <?php endif; ?>

<?php endif; /* tab === 'settings' */ ?>

  <!-- ── Version-Marker (nur sichtbar in Seitenquelle) ─────────────────── -->
  <!-- VIEW_ADDONS_V2.0.0 — diese Datei ist addon/addon-manager/view_addons.php -->

  <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border);
              text-align:center;font-size:.72rem;color:var(--muted);opacity:.5">
    Addon-Manager View v2.0.0 · <?php echo date('Y-m-d H:i'); ?>
  </div>

</div>
