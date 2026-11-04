<?php
/**
 * Project: LMOnext
 * Filename: addon/teamvergleich/lang/en.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Language keys used EXCLUSIVELY by the teamvergleich addon (or by the
 * pdf-export addon only within its exportH2hPdf(), which is only reachable
 * with the teamvergleich addon active anyway) - verified: no real usage in
 * any core file outside of lang/ files - moved out of lang/frontend/en.php.
 */

return [
    'liga_col_spieltag_long' => 'Matchday',
    'liga_h2h_icon_title'    => 'Head-to-head comparison',
    'liga_h2h_modal_title'   => '{heim} vs {gast}',
    'liga_h2h_wins'          => 'Wins {team}',
    'liga_h2h_draw'          => 'Draw',
    'liga_h2h_no_matches'    => 'No previous matches between these two teams yet.',
    'liga_h2h_close'         => 'Close',
    'h2h_today_prefix'       => 'today',
];
