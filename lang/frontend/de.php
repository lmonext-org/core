<?php
/**
 * Project: LMOnext
 * Filename: lang/frontend/de.php
 * Fileversion: 1.50.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Deutsch ist die Referenzsprache des Besucherbereichs (separat vom
 * Adminbereich, siehe lang/admin/de.php). Fehlende Schlüssel in anderen
 * Sprachdateien fallen automatisch auf den hier hinterlegten Text zurück.
 */
declare(strict_types = 1);

return [

    'lang_switch_label' => 'Sprache',
    'template_switch_label' => 'Design',
    'site_title'         => 'LMOnext – Übersicht',

    // ── Startseite (home.php, template/*/home.php) ──────────────────────────
    'home_heading_active_ligen' => 'Aktive Ligen',
    'home_no_active_ligen'      => 'Aktuell sind keine aktiven Ligen vorhanden.',
    'home_overview_disabled'    => 'Die Liga-Übersicht ist deaktiviert.',
    'home_heading_archiv'       => 'Archiv',
    'home_type_ko'               => 'KO-Turnier',
    'home_type_liga'             => 'Liga',
    'home_folder_empty'          => 'Dieser Ordner ist leer.',

    // ── Liga-Detailseite (liga.php, template/*/liga.php) ────────────────────
    'liga_not_found'            => 'Liga nicht gefunden.',
    'liga_back_link'            => '← Zur Übersicht',
    'liga_label_matchday'       => 'Spieltag {n}',
    'liga_no_results_yet'       => 'Für diese Liga wurden noch keine Ergebnisse eingetragen.',
    'liga_spielfrei_label'      => 'Spielfrei:',
    'liga_subtitle_matchday'     => 'Ergebnisse Spieltag {n}',
    'liga_subtitle_round'        => 'Ergebnisse {name}',
    'liga_label_pick_matchday'   => 'Spieltag wählen:',
    'liga_label_pick_round'      => 'Runde wählen:',
    'liga_heading_matchday_range'=> '{n}. Spieltag {range}',
    'liga_col_datum'             => 'Datum',
    'liga_col_heim'              => 'Heim',
    'liga_col_gast'              => 'Gast',
    'liga_col_ergebnis'          => 'Ergebnis',
    'liga_pdf_export_button'     => 'Als PDF exportieren',
    'liga_pdf_title_matchday'    => 'Ergebnisse Spieltag {n}',

    // ── Tabelle (Standings) ───────────────────────────────────────────────────
    'liga_tab_tabelle'            => 'Tabelle',
    'liga_tab_kreuztabelle'       => 'Kreuztabelle',
    'liga_tab_fieberkurve'        => 'Fieberkurven',
    'liga_tab_ligastatistik'      => 'Ligastatistik',
    'liga_tab_spielerstatistik'   => 'Spielerstatistik',
    'liga_fieberkurve_no_data'    => 'Für die Fieberkurve werden erst Ergebnisse benötigt.',
    'liga_col_spieltag_short'     => 'ST',
    'liga_stat_home'              => 'Heim',
    'liga_stat_away'              => 'Auswärts',
    'liga_stat_home_short'        => 'H',
    'liga_stat_away_short'        => 'A',
    'liga_stat_position'          => 'Tabellenposition',
    'liga_stat_points'            => 'Pkt.',
    'liga_stat_played'            => 'Spiele',
    'liga_stat_ppg'               => 'Pkt./Spiel',
    'liga_stat_goals'             => 'Tore',
    'liga_stat_goals_per_game'    => 'Tore/Spiel',
    'liga_stat_wins'              => 'Siege',
    'liga_stat_best_win'          => 'Höchster Sieg',
    'liga_stat_losses'            => 'Niederlagen',
    'liga_stat_worst_loss'        => 'Höchste Niederlage',
    'liga_stat_current_streak'    => 'Aktuelle Serie',
    'liga_stat_remaining'         => 'Restprogramm',
    'liga_stat_overall_title'     => 'Statistische Daten zur Liga',
    'liga_stat_games'             => 'Spiele ges.',
    'liga_stat_home_wins'         => 'Heimsiege',
    'liga_stat_draws'             => 'Unentschieden',
    'liga_stat_away_wins'         => 'Auswärtssiege',
    'liga_stat_goals_total'       => 'Tore ges.',
    'liga_stat_home_goals'        => 'Heim-Tore',
    'liga_stat_away_goals'        => 'Auswärts-Tore',
    'liga_stat_highest_home_win'  => 'Höchste(r) Heimsieg(e)',
    'liga_stat_highest_away_win'  => 'Höchste(r) Auswärtssieg(e)',
    'liga_stat_most_goals'        => 'Die meisten Tore',
    'liga_stat_streaks_title'     => 'Serien',
    'liga_stat_streaks_current'   => 'Aktuell',
    'liga_stat_streaks_season'    => 'Saison',
    'liga_stat_streak_cat_won'      => 'Gewonnen',
    'liga_stat_streak_cat_unbeaten' => 'Ungeschlagen',
    'liga_stat_streak_cat_draw'     => 'Unentschieden',
    'liga_stat_streak_cat_winless'  => 'Sieglos',
    'liga_stat_streak_cat_lost'     => 'Verloren',
    'liga_stat_pick_team'         => 'Team wählen',
    'liga_stat_pick_team_msg'     => 'Bitte wählen Sie jetzt eine oder zwei Mannschaften aus.',
    'liga_stat_chances'           => 'Chancen gegeneinander',
    'liga_stat_tendenz_equal'     => 'etwa gleich schwer',
    'liga_stat_tendenz_harder'    => 'schwerer für {team}',
    'liga_stat_remaining_eval_title' => 'Bewertung der Restprogramme',
    'liga_stat_remaining_ppg'     => 'Ø Pkt.-Schnitt der verbleibenden Gegner',
    'liga_stat_streak_wins'       => '{n} Siege in Folge',
    'liga_stat_streak_losses'     => '{n} Niederlagen in Folge',
    'liga_stat_streak_draws'      => '{n} Unentschieden in Folge',
    'liga_stat_streak_unbeaten'   => '{n} Spiele ungeschlagen',
    'liga_stat_streak_winless'    => '{n} Spiele ohne Sieg',
    'liga_standings_col_platz'    => '#',
    'liga_standings_darstellung'  => 'Tabellendarstellung',
    'sport_vb_col_sp'     => 'Spiele',
    'sport_vb_col_s'      => 'Siege',
    'sport_vb_col_n'      => 'Niederlagen',
    'sport_vb_col_3p'     => '3P',
    'sport_vb_col_2p'     => '2P',
    'sport_vb_col_1p'     => '1P',
    'sport_vb_col_0p'     => '0P',
    'sport_vb_col_saetze' => 'Sätze',
    'sport_vb_col_pkt'    => 'Punkte',
    'sport_vb_col_bquot'  => 'Ballquotient',
    'sport_vb_col_bverh'  => 'Ballverhältnis',
    'sport_vb_col_squot'  => 'Satzquotient',
    'sport_vb_col_sverh'  => 'Satzverhältnis',
    'liga_standings_col_form'    => 'Form',
    'liga_standings_col_trend'   => 'Tend.',
    'liga_standings_nav_gesamt'  => 'Gesamt',
    'liga_standings_nav_heim'    => 'Heim',
    'liga_standings_nav_gast'    => 'Gast',
    'liga_standings_nav_hin'     => 'Hin',
    'liga_standings_nav_rueck'   => 'Rück',
    'liga_standings_vorheriger_spieltag' => 'vorheriger Spieltag',
    'liga_standings_naechster_spieltag'  => 'nächster Spieltag',
    'liga_standings_col_team'     => 'Team',
    'liga_standings_col_sp'       => 'Sp',
    'liga_standings_col_s'        => 'S',
    'liga_standings_col_u'        => 'U',
    'liga_standings_col_n'        => 'N',
    'liga_standings_col_tore'     => 'Tore',
    'liga_standings_col_diff'     => 'Diff',
    'liga_standings_col_pkt'      => 'Pkt',
    'liga_standings_straf_erzielt'   => 'Tore',
    'liga_standings_straf_gegentore' => 'Gegentore',
    'liga_standings_straf_minuspunkte' => 'Minuspunkte',
    'liga_schedule_pick_team'     => 'Bitte wählen Sie jetzt eine Mannschaft aus.',

    // ── Direkter Vergleich (Vergleichs-Modal) ─────────────────────────────────
    'liga_status_ie'             => 'i.E.',
    'liga_status_nv'             => 'n.V.',
    'liga_stats_line'            => 'Schnitt Heim: {heim}   Schnitt Gast: {gast}   Tore: {tore}   Tore/Spiel: {proSpiel}',
    'liga_stats_heading'         => 'Statistik {label}:',

    // ── KO-Rundennamen nach Teamanzahl (data_liga.php: koRoundName()) ────────
    'liga_round_finale'          => 'Finale',
    'liga_round_halbfinale'      => 'Halbfinale',
    'liga_round_viertelfinale'   => 'Viertelfinale',
    'liga_round_achtelfinale'    => 'Achtelfinale',
    'liga_round_generic'         => 'Runde {n}',
    'liga_heading_platz3'        => 'Kleines Finale – Spiel um Platz 3',
    'footer_render_time'         => 'Dauer Berechnungen u. Seitenaufbau: {sekunden} sek.',
    'footer_template'            => 'Template: {name}',
    'footer_template_prefix'     => 'Template:',

    // ── Reiter-Navigation (Kalender/Ergebnisse/Spielpläne/Info) ──────────────
    'liga_tab_kalender'      => 'Kalender',
    'liga_weekday_mo' => 'Mo',
    'liga_weekday_di' => 'Di',
    'liga_weekday_mi' => 'Mi',
    'liga_weekday_do' => 'Do',
    'liga_weekday_fr' => 'Fr',
    'liga_weekday_sa' => 'Sa',
    'liga_weekday_so' => 'So',
    'liga_tab_ergebnisse'    => 'Ergebnisse',
    'liga_tab_spielplaene'   => 'Spielpläne',
    'liga_tab_info'          => 'Info',

    // ── Info-Ansicht ──────────────────────────────────────────────────────────
    'liga_info_title'      => 'LMOnext – Version {version}',
    'liga_info_copyright'  => '© 2026 Dietmar Kersting',
    'liga_info_text_1'     => 'LMOnext ist eine Software zur Verwaltung von Sportligen und Turnieren – für reguläre Ligen ebenso wie für KO-Turniere mit automatischer Rundenstruktur.',
    'liga_info_text_2'     => 'Es handelt sich um eine komplette Neuentwicklung für PHP 8 und MySQL/MariaDB, inspiriert vom Liga Manager Online (LMO).',
    'liga_info_license'    => 'Dieses Projekt steht unter der GNU General Public License v3.0 (GPLv3).',
    'liga_info_link_homepage' => '<a href="https://www.liga-manager-online.org" target="_blank" rel="noopener">Homepage</a>',
    'liga_info_link_forum'    => '<a href="https://www.liga-manager-online.org/forum/" target="_blank" rel="noopener">Forum</a>',

    // ── Kalender-Ansicht ──────────────────────────────────────────────────────
    'liga_kalender_today' => 'Heute',
    'liga_month_1'  => 'Januar',
    'liga_month_2'  => 'Februar',
    'liga_month_3'  => 'März',
    'liga_month_4'  => 'April',
    'liga_month_5'  => 'Mai',
    'liga_month_6'  => 'Juni',
    'liga_month_7'  => 'Juli',
    'liga_month_8'  => 'August',
    'liga_month_9'  => 'September',
    'liga_month_10' => 'Oktober',
    'liga_month_11' => 'November',
    'liga_month_12' => 'Dezember',

    // ── Wartungsmodus (Beitrag: Torsten Hofmann) ───────────────────────────────
    'maintenance_title'            => 'Wartungsmodus',
    'maintenance_heading'          => 'Wartungsarbeiten',
    'maintenance_message'          => 'Diese Instanz befindet sich aktuell im Wartungsmodus.',
    'maintenance_subtext'          => 'Die Arbeiten können eine Weile dauern. Bitte versuche es später erneut.',
    'maintenance_contact'          => 'Bei dringenden Fragen wende dich bitte an den Systemadministrator.',
    'maintenance_footer'           => 'LMOnext — Liga-Manager-Online',

];
