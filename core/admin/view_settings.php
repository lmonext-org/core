<?php
/**
 * Project: LMOnext
 * Filename: view_settings.php
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

// Bekannte Zeitzonen für Dropdown
$tzGroups = [
    'Africa' => ['Africa/Abidjan','Africa/Accra','Africa/Addis_Ababa','Africa/Algiers','Africa/Asmara','Africa/Bamako','Africa/Bangui','Africa/Banjul','Africa/Bissau','Africa/Blantyre','Africa/Brazzaville','Africa/Bujumbura','Africa/Cairo','Africa/Casablanca','Africa/Ceuta','Africa/Conakry','Africa/Dakar','Africa/Dar_es_Salaam','Africa/Djibouti','Africa/Douala','Africa/El_Aaiun','Africa/Freetown','Africa/Gaborone','Africa/Harare','Africa/Johannesburg','Africa/Juba','Africa/Kampala','Africa/Khartoum','Africa/Kigali','Africa/Kinshasa','Africa/Lagos','Africa/Libreville','Africa/Lome','Africa/Luanda','Africa/Lubumbashi','Africa/Lusaka','Africa/Malabo','Africa/Maputo','Africa/Maseru','Africa/Mbabane','Africa/Mogadishu','Africa/Monrovia','Africa/Nairobi','Africa/Ndjamena','Africa/Niamey','Africa/Nouakchott','Africa/Ouagadougou','Africa/Porto-Novo','Africa/Sao_Tome','Africa/Tripoli','Africa/Tunis','Africa/Windhoek'],
    'America' => ['America/Adak','America/Anchorage','America/Anguilla','America/Antigua','America/Araguaina','America/Argentina','America/Aruba','America/Asuncion','America/Atikokan','America/Bahia','America/Bahia_Banderas','America/Barbados','America/Belem','America/Belize','America/Blanc-Sablon','America/Boa_Vista','America/Bogota','America/Boise','America/Cambridge_Bay','America/Campo_Grande','America/Cancun','America/Caracas','America/Cayenne','America/Cayman','America/Chicago','America/Chihuahua','America/Ciudad_Juarez','America/Costa_Rica','America/Coyhaique','America/Creston','America/Cuiaba','America/Curacao','America/Danmarkshavn','America/Dawson','America/Dawson_Creek','America/Denver','America/Detroit','America/Dominica','America/Edmonton','America/Eirunepe','America/El_Salvador','America/Fortaleza','America/Fort_Nelson','America/Glace_Bay','America/Goose_Bay','America/Grand_Turk','America/Grenada','America/Guadeloupe','America/Guatemala','America/Guayaquil','America/Guyana','America/Halifax','America/Havana','America/Hermosillo','America/Indiana','America/Inuvik','America/Iqaluit','America/Jamaica','America/Juneau','America/Kentucky','America/Kralendijk','America/La_Paz','America/Lima','America/Los_Angeles','America/Lower_Princes','America/Maceio','America/Managua','America/Manaus','America/Marigot','America/Martinique','America/Matamoros','America/Mazatlan','America/Menominee','America/Merida','America/Metlakatla','America/Mexico_City','America/Miquelon','America/Moncton','America/Monterrey','America/Montevideo','America/Montserrat','America/Nassau','America/New_York','America/Nome','America/Noronha','America/North_Dakota','America/Nuuk','America/Ojinaga','America/Panama','America/Paramaribo','America/Phoenix','America/Port-au-Prince','America/Port_of_Spain','America/Porto_Velho','America/Puerto_Rico','America/Punta_Arenas','America/Rankin_Inlet','America/Recife','America/Regina','America/Resolute','America/Rio_Branco','America/Santarem','America/Santiago','America/Santo_Domingo','America/Sao_Paulo','America/Scoresbysund','America/Sitka','America/St_Barthelemy','America/St_Johns','America/St_Kitts','America/St_Lucia','America/St_Thomas','America/St_Vincent','America/Swift_Current','America/Tegucigalpa','America/Thule','America/Tijuana','America/Toronto','America/Tortola','America/Vancouver','America/Whitehorse','America/Winnipeg','America/Yakutat'],
    'Antarctica' => ['Antarctica/Casey','Antarctica/Davis','Antarctica/DumontDUrville','Antarctica/Macquarie','Antarctica/Mawson','Antarctica/McMurdo','Antarctica/Palmer','Antarctica/Rothera','Antarctica/Syowa','Antarctica/Troll','Antarctica/Vostok'],
    'Arctic' => ['Arctic/Longyearbyen'],
    'Asia' => ['Asia/Aden','Asia/Almaty','Asia/Amman','Asia/Anadyr','Asia/Aqtau','Asia/Aqtobe','Asia/Ashgabat','Asia/Atyrau','Asia/Baghdad','Asia/Bahrain','Asia/Baku','Asia/Bangkok','Asia/Barnaul','Asia/Beirut','Asia/Bishkek','Asia/Brunei','Asia/Chita','Asia/Colombo','Asia/Damascus','Asia/Dhaka','Asia/Dili','Asia/Dubai','Asia/Dushanbe','Asia/Famagusta','Asia/Gaza','Asia/Hebron','Asia/Ho_Chi_Minh','Asia/Hong_Kong','Asia/Hovd','Asia/Irkutsk','Asia/Jakarta','Asia/Jayapura','Asia/Jerusalem','Asia/Kabul','Asia/Kamchatka','Asia/Karachi','Asia/Kathmandu','Asia/Khandyga','Asia/Kolkata','Asia/Krasnoyarsk','Asia/Kuala_Lumpur','Asia/Kuching','Asia/Kuwait','Asia/Macau','Asia/Magadan','Asia/Makassar','Asia/Manila','Asia/Muscat','Asia/Nicosia','Asia/Novokuznetsk','Asia/Novosibirsk','Asia/Omsk','Asia/Oral','Asia/Phnom_Penh','Asia/Pontianak','Asia/Pyongyang','Asia/Qatar','Asia/Qostanay','Asia/Qyzylorda','Asia/Riyadh','Asia/Sakhalin','Asia/Samarkand','Asia/Seoul','Asia/Shanghai','Asia/Singapore','Asia/Srednekolymsk','Asia/Taipei','Asia/Tashkent','Asia/Tbilisi','Asia/Tehran','Asia/Thimphu','Asia/Tokyo','Asia/Tomsk','Asia/Ulaanbaatar','Asia/Urumqi','Asia/Ust-Nera','Asia/Vientiane','Asia/Vladivostok','Asia/Yakutsk','Asia/Yangon','Asia/Yekaterinburg','Asia/Yerevan'],
    'Atlantic' => ['Atlantic/Azores','Atlantic/Bermuda','Atlantic/Canary','Atlantic/Cape_Verde','Atlantic/Faroe','Atlantic/Madeira','Atlantic/Reykjavik','Atlantic/South_Georgia','Atlantic/St_Helena','Atlantic/Stanley'],
    'Australia' => ['Australia/Adelaide','Australia/Brisbane','Australia/Broken_Hill','Australia/Darwin','Australia/Eucla','Australia/Hobart','Australia/Lindeman','Australia/Lord_Howe','Australia/Melbourne','Australia/Perth','Australia/Sydney'],
    'Europe' => ['Europe/Amsterdam','Europe/Andorra','Europe/Astrakhan','Europe/Athens','Europe/Belgrade','Europe/Berlin','Europe/Bratislava','Europe/Brussels','Europe/Bucharest','Europe/Budapest','Europe/Busingen','Europe/Chisinau','Europe/Copenhagen','Europe/Dublin','Europe/Gibraltar','Europe/Guernsey','Europe/Helsinki','Europe/Isle_of_Man','Europe/Istanbul','Europe/Jersey','Europe/Kaliningrad','Europe/Kirov','Europe/Kyiv','Europe/Lisbon','Europe/Ljubljana','Europe/London','Europe/Luxembourg','Europe/Madrid','Europe/Malta','Europe/Mariehamn','Europe/Minsk','Europe/Monaco','Europe/Moscow','Europe/Oslo','Europe/Paris','Europe/Podgorica','Europe/Prague','Europe/Riga','Europe/Rome','Europe/Samara','Europe/San_Marino','Europe/Sarajevo','Europe/Saratov','Europe/Simferopol','Europe/Skopje','Europe/Sofia','Europe/Stockholm','Europe/Tallinn','Europe/Tirane','Europe/Ulyanovsk','Europe/Vaduz','Europe/Vatican','Europe/Vienna','Europe/Vilnius','Europe/Volgograd','Europe/Warsaw','Europe/Zagreb','Europe/Zurich'],
    'Indian' => ['Indian/Antananarivo','Indian/Chagos','Indian/Christmas','Indian/Cocos','Indian/Comoro','Indian/Kerguelen','Indian/Mahe','Indian/Maldives','Indian/Mauritius','Indian/Mayotte','Indian/Reunion'],
    'Pacific' => ['Pacific/Apia','Pacific/Auckland','Pacific/Bougainville','Pacific/Chatham','Pacific/Chuuk','Pacific/Easter','Pacific/Efate','Pacific/Fakaofo','Pacific/Fiji','Pacific/Funafuti','Pacific/Galapagos','Pacific/Gambier','Pacific/Guadalcanal','Pacific/Guam','Pacific/Honolulu','Pacific/Kanton','Pacific/Kiritimati','Pacific/Kosrae','Pacific/Kwajalein','Pacific/Majuro','Pacific/Marquesas','Pacific/Midway','Pacific/Nauru','Pacific/Niue','Pacific/Norfolk','Pacific/Noumea','Pacific/Pago_Pago','Pacific/Palau','Pacific/Pitcairn','Pacific/Pohnpei','Pacific/Port_Moresby','Pacific/Rarotonga','Pacific/Saipan','Pacific/Tahiti','Pacific/Tarawa','Pacific/Tongatapu','Pacific/Wake','Pacific/Wallis'],
    'UTC' => ['UTC'],
];
$currentTz = getAdminSetting('timezone', 'Europe/Berlin');
$tzNow     = '';
try { $tzNow = (new DateTime('now', new DateTimeZone($currentTz)))->format('H:i T'); } catch (Throwable) {}

$currentLanguage = getAdminSetting('language', DEFAULT_LANGUAGE);
if (!array_key_exists($currentLanguage, AVAILABLE_LANGUAGES)) { $currentLanguage = DEFAULT_LANGUAGE; }

// Besucherbereich: verfügbare Templates + aktuelle Einstellungen
require_once dirname(__DIR__) . '/frontend/template_engine.php';
$availableTemplates = getAvailableTemplates();
$activeTemplate     = getAdminSetting('active_template', DEFAULT_TEMPLATE);
if (!array_key_exists($activeTemplate, $availableTemplates)) { $activeTemplate = DEFAULT_TEMPLATE; }
$allowTemplateSwitch = getAdminSetting('allow_template_switch', '0') === '1';
$showPdfButtons      = getAdminSetting('show_pdf_buttons', '1') === '1';
$showTeamvergleich   = getAdminSetting('show_teamvergleich', '1') === '1';
$showLanguageSwitcher = getAdminSetting('show_language_switcher', '1') === '1';
$showBackLink        = getAdminSetting('show_back_link', '1') === '1';

// ── View: Einstellungen (zweistufige Tabs: Optionen > Optionen/Anzeigen,
// Info) ───────────────────────────────────────────────────────────────────
$mainTab = ($_GET['tab'] ?? 'optionen') === 'info' ? 'info' : 'optionen';
$subTab  = ($_GET['subtab'] ?? 'optionen') === 'anzeige' ? 'anzeige' : 'optionen';
?>
      <!-- Haupt-Tab-Navigation -->
      <div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
<?php
$mainTabs = ['optionen' => t('settings_tab_optionen'), 'info' => t('settings_tab_info')];
foreach ($mainTabs as $key => $label) {
    $active = $key === $mainTab; ?>
        <a href="?action=settings&tab=<?= $key ?>"
           style="padding:8px 16px;font-size:.83rem;text-decoration:none;
                  border-radius:var(--radius) var(--radius) 0 0;
                  background:<?= $active ? 'var(--surface)' : 'var(--surface2)' ?>;
                  border:1px solid var(--border);
                  border-bottom:<?= $active ? '1px solid var(--surface)' : '1px solid var(--border)' ?>;
                  color:<?= $active ? 'var(--accent)' : 'var(--muted)' ?>;
                  font-weight:<?= $active ? '600' : '400' ?>;margin-right:3px"><?= h($label) ?></a>
<?php } ?>
      </div>
      <div style="border:1px solid var(--border);border-top:none;border-radius:0 var(--radius) var(--radius) var(--radius);
                  background:var(--surface);padding:16px;margin-bottom:16px">
<?php if ($mainTab === 'optionen') { ?>
        <!-- Unter-Tab-Navigation -->
        <div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
<?php
        $subTabs = ['optionen' => t('settings_tab_optionen'), 'anzeige' => t('settings_tab_anzeige')];
        foreach ($subTabs as $key => $label) {
            $active = $key === $subTab; ?>
          <a href="?action=settings&tab=optionen&subtab=<?= $key ?>"
             style="padding:6px 14px;font-size:.8rem;text-decoration:none;
                    border-radius:var(--radius) var(--radius) 0 0;
                    background:<?= $active ? 'var(--bg)' : 'transparent' ?>;
                    border:1px solid var(--border);
                    border-bottom:<?= $active ? '1px solid var(--bg)' : '1px solid var(--border)' ?>;
                    color:<?= $active ? 'var(--accent)' : 'var(--muted)' ?>;
                    font-weight:<?= $active ? '600' : '400' ?>;margin-right:3px"><?= h($label) ?></a>
<?php   } ?>
        </div>
        <div style="border:1px solid var(--border);border-top:none;border-radius:0 var(--radius) var(--radius) var(--radius);
                    background:var(--bg);padding:16px">
<?php if ($subTab === 'optionen') { ?>
      <div class="card" style="max-width:480px;margin:0">
        <h2><?= h(t('settings_heading_system')) ?></h2>
        <form method="post" action="?action=save_admin_settings">
          <div class="form-group">
            <label><?= h(t('settings_label_language')) ?></label>
            <select name="language" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <?php foreach (AVAILABLE_LANGUAGES as $code => $meta) { ?>
              <option value="<?= h($code) ?>"<?= $code === $currentLanguage ? ' selected' : '' ?>><?= h($meta['flag']) ?> <?= h($meta['label']) ?></option>
              <?php } ?>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= h(t('settings_hint_language')) ?>
            </div>
          </div>
          <div class="form-group">
            <label><?= h(t('settings_label_timezone')) ?></label>
            <select name="timezone" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <?php foreach ($tzGroups as $group => $tzList) { ?>
              <optgroup label="<?= h($group) ?>">
                <?php foreach ($tzList as $tz) { ?>
                <option value="<?= $tz ?>"<?= $tz === $currentTz ? ' selected' : '' ?>><?= $tz ?></option>
                <?php } ?>
              </optgroup>
              <?php } ?>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= t('settings_current_time_line', ['time' => '<strong>'.h($tzNow).'</strong>']) ?><br>
              <?= h(t('settings_hint_timezone')) ?>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button>
        <?= csrfField() ?></form>
      </div>
<?php } else { ?>
      <div class="card" style="max-width:480px;margin:0">
        <h2><?= h(t('settings_heading_frontend')) ?></h2>
        <form method="post" action="?action=save_admin_settings">
          <div class="form-group">
            <label><?= h(t('settings_label_active_template')) ?></label>
            <select name="active_template" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <?php foreach ($availableTemplates as $key => $meta) { ?>
              <option value="<?= h($key) ?>"<?= $key === $activeTemplate ? ' selected' : '' ?>>
                <?= h($meta['name']) ?><?= $meta['description'] !== '' ? ' – '.h($meta['description']) : '' ?>
              </option>
              <?php } ?>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= h(t('settings_hint_active_template')) ?>
            </div>
          </div>
          <div class="form-group">
            <label><?= h(t('settings_label_allow_template_switch')) ?></label>
            <select name="allow_template_switch" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <option value="1"<?= $allowTemplateSwitch ? ' selected' : '' ?>><?= h(t('common_yes')) ?></option>
              <option value="0"<?= !$allowTemplateSwitch ? ' selected' : '' ?>><?= h(t('common_no')) ?></option>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= h(t('settings_hint_allow_template_switch')) ?>
            </div>
          </div>
<?php if (function_exists('addonManager') && addonManager()->isEnabled('pdf-export')) { ?>
          <div class="form-group">
            <label><?= h(t('settings_label_show_pdf_buttons')) ?></label>
            <select name="show_pdf_buttons" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <option value="1"<?= $showPdfButtons ? ' selected' : '' ?>><?= h(t('common_yes')) ?></option>
              <option value="0"<?= !$showPdfButtons ? ' selected' : '' ?>><?= h(t('common_no')) ?></option>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= h(t('settings_hint_show_pdf_buttons')) ?>
            </div>
          </div>
<?php } ?>
<?php if (function_exists('addonManager') && addonManager()->isEnabled('teamvergleich')) { ?>
          <div class="form-group">
            <label><?= h(t('settings_label_show_teamvergleich')) ?></label>
            <select name="show_teamvergleich" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <option value="1"<?= $showTeamvergleich ? ' selected' : '' ?>><?= h(t('common_yes')) ?></option>
              <option value="0"<?= !$showTeamvergleich ? ' selected' : '' ?>><?= h(t('common_no')) ?></option>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= h(t('settings_hint_show_teamvergleich')) ?>
            </div>
          </div>
<?php } ?>
          <div class="form-group">
            <label><?= h(t('settings_label_show_language_switcher')) ?></label>
            <select name="show_language_switcher" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <option value="1"<?= $showLanguageSwitcher ? ' selected' : '' ?>><?= h(t('common_yes')) ?></option>
              <option value="0"<?= !$showLanguageSwitcher ? ' selected' : '' ?>><?= h(t('common_no')) ?></option>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= h(t('settings_hint_show_language_switcher')) ?>
            </div>
          </div>
          <div class="form-group">
            <label><?= h(t('settings_label_show_back_link')) ?></label>
            <select name="show_back_link" style="width:100%;background:var(--bg);border:1px solid var(--border);
                   color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;margin-top:4px">
              <option value="1"<?= $showBackLink ? ' selected' : '' ?>><?= h(t('common_yes')) ?></option>
              <option value="0"<?= !$showBackLink ? ' selected' : '' ?>><?= h(t('common_no')) ?></option>
            </select>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">
              <?= h(t('settings_hint_show_back_link')) ?>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button>
        <?= csrfField() ?></form>
      </div>
<?php } ?>
        </div>
<?php } else { ?>
      <div class="card" style="max-width:560px;margin:0">
        <h2><?= h(t('settings_heading_db')) ?></h2>
        <?php
          try { $v = getDB()->query('SELECT VERSION()')->fetchColumn(); echo '<p style="font-size:.88rem;color:var(--green)">✓ '.h(t('settings_db_connected', ['version' => $v])).'</p>'; }
          catch (Throwable $e) { echo '<p style="font-size:.88rem;color:var(--red)">✗ '.h($e->getMessage()).'</p>'; }
        ?>
        <p style="font-size:.8rem;color:var(--muted);margin-top:8px"><?= t('settings_hint_db_config') ?></p>
      </div>
      <div class="card" style="max-width:560px;margin:16px 0 0">
        <h2><?= h(t('settings_heading_ssl')) ?></h2>
<?php if (lmoIsHttps()) { ?>
        <p style="font-size:.88rem;margin:0;color:var(--green)">✓ <?= h(t('settings_ssl_active')) ?></p>
<?php } else { ?>
        <p style="font-size:.88rem;margin:0 0 8px;color:var(--red)">✗ <?= h(t('settings_ssl_inactive')) ?></p>
        <div style="background:#f59e0b18;border:1px solid #f59e0b44;border-radius:var(--radius);
                    padding:10px 12px;font-size:.83rem;color:var(--text)">
          ⚠️ <?= t('settings_ssl_warning_text') ?>
        </div>
<?php } ?>
      </div>
      <div class="card" style="max-width:560px;margin:16px 0 0">
        <h2><?= h(t('settings_heading_php')) ?></h2>
        <p style="font-size:.88rem;margin:0 0 12px"><?= t('settings_php_version_line', ['version' => '<strong>'.h(PHP_VERSION).'</strong>']) ?></p>
        <table style="width:100%;border-collapse:collapse;font-size:.85rem">
          <thead>
            <tr style="text-align:left;border-bottom:1px solid var(--border)">
              <th style="padding:4px 8px 4px 0"><?= h(t('settings_col_extension')) ?></th>
              <th style="padding:4px 8px"><?= h(t('settings_col_status')) ?></th>
              <th style="padding:4px 0"><?= h(t('settings_col_info')) ?></th>
            </tr>
          </thead>
          <tbody>
<?php foreach (checkRuntimeExtensions() as $chk) { ?>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:6px 8px 6px 0"><?= h($chk['label']) ?><?= $chk['required'] ? ' <span style="color:var(--muted);font-size:.75rem">('.h(t('settings_required')).')</span>' : '' ?></td>
              <td style="padding:6px 8px">
                <?php if ($chk['ok']) { ?>
                <span style="color:var(--green)">✓ <?= h(t('settings_loaded')) ?></span>
                <?php } else { ?>
                <span style="color:<?= $chk['required'] ? 'var(--red)' : 'var(--muted)' ?>">✗ <?= h(t('settings_not_loaded')) ?></span>
                <?php } ?>
              </td>
              <td style="padding:6px 0;color:var(--muted)"><?= h($chk['info']) ?></td>
            </tr>
<?php } ?>
          </tbody>
        </table>
      </div>
<?php } ?>
      </div>
