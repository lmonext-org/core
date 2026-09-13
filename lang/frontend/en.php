<?php
/**
 * Project: LMOnext
 * Filename: lang/frontend/en.php
 * Fileversion: 1.50.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Any key missing here automatically falls back to lang/frontend/de.php.
 */
declare(strict_types = 1);

return [

    'lang_switch_label' => 'Language',
    'template_switch_label' => 'Theme',
    'site_title'         => 'LMOnext – Overview',

    // ── Home page (home.php, template/*/home.php) ────────────────────────────
    'home_heading_active_ligen' => 'Active leagues',
    'home_no_active_ligen'      => 'No active leagues at the moment.',
    'home_overview_disabled'    => 'The league overview is disabled.',
    'home_heading_archiv'       => 'Archive',
    'home_type_ko'               => 'KO tournament',
    'home_type_liga'             => 'League',
    'home_folder_empty'          => 'This folder is empty.',

    // ── League detail page (liga.php, template/*/liga.php) ───────────────────
    'liga_not_found'            => 'League not found.',
    'liga_back_link'            => '← Back to overview',
    'liga_label_matchday'       => 'Matchday {n}',
    'liga_no_results_yet'       => 'No results have been entered for this league yet.',
    'liga_spielfrei_label'      => 'Bye:',
    'liga_subtitle_matchday'     => 'Results – Matchday {n}',
    'liga_subtitle_round'        => 'Results – {name}',
    'liga_label_pick_matchday'   => 'Select matchday:',
    'liga_label_pick_round'      => 'Select round:',
    'liga_heading_matchday_range'=> 'Matchday {n} {range}',
    'liga_col_datum'             => 'Date',
    'liga_col_heim'              => 'Home',
    'liga_col_gast'              => 'Away',
    'liga_col_ergebnis'          => 'Result',
    'liga_pdf_export_button'     => 'Export as PDF',
    'liga_pdf_title_matchday'    => 'Results Matchday {n}',

    // ── Standings ─────────────────────────────────────────────────────────────
    'liga_tab_tabelle'            => 'Standings',
    'liga_tab_kreuztabelle'       => 'Cross table',
    'liga_tab_fieberkurve'        => 'Position chart',
    'liga_tab_ligastatistik'      => 'League stats',
    'liga_tab_spielerstatistik'   => 'Player stats',
    'liga_fieberkurve_no_data'    => 'Results are needed before the position chart can be shown.',
    'liga_col_spieltag_short'     => 'MD',
    'liga_stat_home'              => 'Home',
    'liga_stat_away'              => 'Away',
    'liga_stat_home_short'        => 'H',
    'liga_stat_away_short'        => 'A',
    'liga_stat_position'          => 'Table position',
    'liga_stat_points'            => 'Pts.',
    'liga_stat_played'            => 'Played',
    'liga_stat_ppg'               => 'Pts./game',
    'liga_stat_goals'             => 'Goals',
    'liga_stat_goals_per_game'    => 'Goals/game',
    'liga_stat_wins'              => 'Wins',
    'liga_stat_best_win'          => 'Biggest win',
    'liga_stat_losses'            => 'Losses',
    'liga_stat_worst_loss'        => 'Biggest loss',
    'liga_stat_current_streak'    => 'Current streak',
    'liga_stat_remaining'         => 'Remaining fixtures',
    'liga_stat_overall_title'     => 'League statistics',
    'liga_stat_games'             => 'Total games',
    'liga_stat_home_wins'         => 'Home wins',
    'liga_stat_draws'             => 'Draws',
    'liga_stat_away_wins'         => 'Away wins',
    'liga_stat_goals_total'       => 'Total goals',
    'liga_stat_home_goals'        => 'Home goals',
    'liga_stat_away_goals'        => 'Away goals',
    'liga_stat_highest_home_win'  => 'Biggest home win(s)',
    'liga_stat_highest_away_win'  => 'Biggest away win(s)',
    'liga_stat_most_goals'        => 'Most goals in a match',
    'liga_stat_streaks_title'     => 'Streaks',
    'liga_stat_streaks_current'   => 'Current',
    'liga_stat_streaks_season'    => 'Season',
    'liga_stat_streak_cat_won'      => 'Won',
    'liga_stat_streak_cat_unbeaten' => 'Unbeaten',
    'liga_stat_streak_cat_draw'     => 'Drawn',
    'liga_stat_streak_cat_winless'  => 'Winless',
    'liga_stat_streak_cat_lost'     => 'Lost',
    'liga_stat_pick_team'         => 'Select team',
    'liga_stat_pick_team_msg'     => 'Please select one or two teams now.',
    'liga_stat_chances'           => 'Chances against each other',
    'liga_stat_tendenz_equal'     => 'about equally hard',
    'liga_stat_tendenz_harder'    => 'harder for {team}',
    'liga_stat_remaining_eval_title' => 'Remaining schedule evaluation',
    'liga_stat_remaining_ppg'     => 'Avg. points/game of remaining opponents',
    'liga_stat_streak_wins'       => '{n} wins in a row',
    'liga_stat_streak_losses'     => '{n} losses in a row',
    'liga_stat_streak_draws'      => '{n} draws in a row',
    'liga_stat_streak_unbeaten'   => '{n} games unbeaten',
    'liga_stat_streak_winless'    => '{n} games without a win',
    'liga_standings_col_platz'    => '#',
    'liga_standings_darstellung'  => 'Table view',
    'sport_vb_col_sp'     => 'Played',
    'sport_vb_col_s'      => 'Won',
    'sport_vb_col_n'      => 'Lost',
    'sport_vb_col_3p'     => '3P',
    'sport_vb_col_2p'     => '2P',
    'sport_vb_col_1p'     => '1P',
    'sport_vb_col_0p'     => '0P',
    'sport_vb_col_saetze' => 'Sets',
    'sport_vb_col_pkt'    => 'Points',
    'sport_vb_col_bquot'  => 'Ball ratio',
    'sport_vb_col_bverh'  => 'Balls',
    'sport_vb_col_squot'  => 'Set ratio',
    'sport_vb_col_sverh'  => 'Set score',
    'liga_standings_col_form'    => 'Form',
    'liga_standings_col_trend'   => 'Trend',
    'liga_standings_nav_gesamt'  => 'Overall',
    'liga_standings_nav_heim'    => 'Home',
    'liga_standings_nav_gast'    => 'Away',
    'liga_standings_nav_hin'     => '1st Half',
    'liga_standings_nav_rueck'   => '2nd Half',
    'liga_standings_vorheriger_spieltag' => 'previous matchday',
    'liga_standings_naechster_spieltag'  => 'next matchday',
    'liga_standings_col_team'     => 'Team',
    'liga_standings_col_sp'       => 'MP',
    'liga_standings_col_s'        => 'W',
    'liga_standings_col_u'        => 'D',
    'liga_standings_col_n'        => 'L',
    'liga_standings_col_tore'     => 'Goals',
    'liga_standings_col_diff'     => 'GD',
    'liga_standings_col_pkt'      => 'Pts',
    'liga_standings_straf_erzielt'   => 'goals',
    'liga_standings_straf_gegentore' => 'goals against',
    'liga_standings_straf_minuspunkte' => 'minus points',
    'liga_schedule_pick_team'     => 'Please select a team now.',

    // ── Head-to-head comparison modal ─────────────────────────────────────────
    'liga_status_ie'             => 'pens.',
    'liga_status_nv'             => 'AET',
    'liga_stats_line'            => 'Average home: {heim}   Average away: {gast}   Goals: {tore}   Goals/match: {proSpiel}',
    'liga_stats_heading'         => 'Stats – {label}:',

    // ── KO round names by team count (data_liga.php: koRoundName()) ─────────
    'liga_round_finale'          => 'Final',
    'liga_round_halbfinale'      => 'Semi-final',
    'liga_round_viertelfinale'   => 'Quarter-final',
    'liga_round_achtelfinale'    => 'Round of 16',
    'liga_round_generic'         => 'Round {n}',
    'liga_heading_platz3'        => 'Third-place match',
    'footer_render_time'         => 'Calculation & page build time: {sekunden} sec.',
    'footer_template'            => 'Template: {name}',
    'footer_template_prefix'     => 'Template:',

    // ── Tab navigation (Calendar/Results/Bracket/Info) ───────────────────────
    'liga_tab_kalender'      => 'Calendar',
    'liga_weekday_mo' => 'Mon',
    'liga_weekday_di' => 'Tue',
    'liga_weekday_mi' => 'Wed',
    'liga_weekday_do' => 'Thu',
    'liga_weekday_fr' => 'Fri',
    'liga_weekday_sa' => 'Sat',
    'liga_weekday_so' => 'Sun',
    'liga_tab_ergebnisse'    => 'Results',
    'liga_tab_spielplaene'   => 'Bracket',
    'liga_tab_info'          => 'Info',

    // ── Info view ─────────────────────────────────────────────────────────────
    'liga_info_title'      => 'LMOnext – Version {version}',
    'liga_info_copyright'  => '© 2026 Dietmar Kersting',
    'liga_info_text_1'     => 'LMOnext is software for managing sports leagues and tournaments – for regular leagues as well as knockout tournaments with automatic round structure.',
    'liga_info_text_2'     => 'It is a complete rewrite for PHP 8 and MySQL/MariaDB, inspired by Liga Manager Online (LMO).',
    'liga_info_license'    => 'This project is licensed under the GNU General Public License v3.0 (GPLv3).',
    'liga_info_link_homepage' => '<a href="https://www.liga-manager-online.org" target="_blank" rel="noopener">Homepage</a>',
    'liga_info_link_forum'    => '<a href="https://www.liga-manager-online.org/forum/" target="_blank" rel="noopener">Forum</a>',

    // ── Calendar view ─────────────────────────────────────────────────────────
    'liga_kalender_today' => 'Today',
    'liga_month_1'  => 'January',
    'liga_month_2'  => 'February',
    'liga_month_3'  => 'March',
    'liga_month_4'  => 'April',
    'liga_month_5'  => 'May',
    'liga_month_6'  => 'June',
    'liga_month_7'  => 'July',
    'liga_month_8'  => 'August',
    'liga_month_9'  => 'September',
    'liga_month_10' => 'October',
    'liga_month_11' => 'November',
    'liga_month_12' => 'December',

    // ── Player stats (visitor view) ──────────────────────────────────────────

    // ── Maintenance mode (contribution: Torsten Hofmann) ───────────────────────
    'maintenance_title'            => 'Maintenance mode',
    'maintenance_heading'          => 'Maintenance in progress',
    'maintenance_message'          => 'This instance is currently in maintenance mode.',
    'maintenance_subtext'          => 'This may take a while. Please try again later.',
    'maintenance_contact'          => 'For urgent questions, please contact the system administrator.',
    'maintenance_footer'           => 'LMOnext — Liga-Manager-Online',

];
