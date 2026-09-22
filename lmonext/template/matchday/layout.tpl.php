<!DOCTYPE html>
<html lang="<!--HtmlLang-->">
<head>
<!--
  Template: matchday | Filename: layout.tpl.php | Fileversion: 1.4.2
-->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><!--Titel--></title>
<link rel="shortcut icon" href="assets/favicon/favicon.ico">
<link rel="apple-touch-icon" sizes="57x57" href="assets/favicon/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="assets/favicon/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="assets/favicon/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="assets/favicon/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="assets/favicon/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="assets/favicon/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="assets/favicon/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="assets/favicon/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192" href="assets/favicon/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="assets/favicon/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
<link rel="manifest" href="assets/favicon/manifest.json">
<meta name="msapplication-TileColor" content="#0E0F12">
<meta name="msapplication-TileImage" content="assets/favicon/ms-icon-144x144.png">
<meta name="msapplication-config" content="assets/favicon/browserconfig.xml">
<meta name="theme-color" content="#0E0F12">
<style>
@font-face{
  font-family:'Oswald';
  src:url('assets/fonts/oswald/Oswald-Variable.ttf');
  font-weight:200 700;
  font-style:normal;
  font-display:swap;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#ffffff;--bg-alt:#f4f5f7;--surface:#ffffff;
  --ink:#0e0f12;--muted:#6b7280;--line:#e4e6eb;
  --accent:#c6f000;--accent-ink:#0e0f12;
  --pitch:#1f6f4a;--warn:#b45309;--danger:#dc2626;
  /* Aliase für zwischen den Templates geteiltes CSS (z.B. Form-Tooltip),
     das die in den anderen 4 Templates üblichen Namen --text/--border nutzt -
     dieses Template heißt sie stattdessen --ink/--line. Rein additiv, ändert
     an der bisherigen matchday-eigenen Nutzung von --ink/--line nichts. */
  --text:var(--ink);--border:var(--line);
  --radius:3px;
  --font-display:'Oswald',system-ui,sans-serif;
  --font-body:system-ui,-apple-system,'Segoe UI',sans-serif;
}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--bg);color:var(--ink);line-height:1.5;min-height:100vh;font-size:15px}
a{color:inherit}
:focus-visible{outline:3px solid var(--accent);outline-offset:2px}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}*{animation-duration:.001ms !important;transition-duration:.001ms !important}}

.team-logo-inline{height:18px;width:auto;vertical-align:middle;margin-right:6px;margin-left:6px;border-radius:2px}
.st-team-logo-wrap{display:inline-block;width:26px;height:18px;text-align:center;vertical-align:middle;margin-right:6px}
.st-team-logo-wrap .team-logo-inline{width:100%;height:100%;object-fit:contain;margin:0}

.lang-switch select,.template-switch select{background:var(--bg-alt);border:1px solid var(--line);
  border-radius:var(--radius);padding:7px 11px;font-size:.82rem;color:var(--ink);
  font-family:var(--font-body);cursor:pointer}

main{max-width:960px;margin:0 auto;padding:52px 20px 64px}

.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);
  padding:22px 24px;margin-bottom:20px}
.card h2{font-family:var(--font-display);font-size:1rem;font-weight:600;margin-bottom:14px;
  color:var(--ink);text-transform:uppercase;letter-spacing:.04em}
.card p{margin-bottom:10px;font-size:.9rem;line-height:1.6}
.card p:last-child{margin-bottom:0}

.liga-list{list-style:none}
.liga-list li{border-bottom:1px solid var(--line)}
.liga-list li:last-child{border-bottom:none}
a.liga-link{display:flex;align-items:center;gap:10px;padding:13px 4px;text-decoration:none;
  color:var(--ink);border-left:3px solid transparent;padding-left:10px;transition:border-color .12s,background .12s}
a.liga-link:hover{border-left-color:var(--accent);background:var(--bg-alt)}
a.liga-link .liga-name{font-weight:600}

.chip{display:inline-block;font-size:.68rem;padding:3px 9px;border-radius:2px;font-weight:700;
  white-space:nowrap;text-transform:uppercase;letter-spacing:.04em}
.chip-blue{background:var(--ink);color:#fff}
.chip-yellow{background:var(--accent);color:var(--accent-ink)}

details.archiv-folder{border-bottom:1px solid var(--line)}
details.archiv-folder:last-child{border-bottom:none}
details.archiv-folder summary{cursor:pointer;padding:13px 4px;font-weight:600;list-style:none;
  display:flex;align-items:center;gap:8px;user-select:none}
details.archiv-folder summary::-webkit-details-marker{display:none}
details.archiv-folder summary .arrow{transition:transform .15s;display:inline-block;color:var(--accent);
  font-size:.75rem;font-family:var(--font-display)}
details.archiv-folder[open]>summary .arrow{transform:rotate(90deg)}
details.archiv-folder .folder-desc{color:var(--muted);font-size:.82rem;font-weight:400}
.folder-content{padding:2px 0 10px 22px}
.folder-content.folder-empty{color:var(--muted);font-size:.85rem;padding-left:22px}

.empty-msg{color:var(--muted);font-size:.9rem}

.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;
  font-size:.82rem;margin-bottom:18px;font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.back-link:hover{color:var(--ink)}
.back-link::before{content:'←'}

.liga-title{font-family:var(--font-display);font-size:1.9rem;font-weight:700;margin-bottom:8px;
  display:flex;align-items:center;gap:12px;flex-wrap:wrap;text-transform:uppercase;letter-spacing:.01em}
.liga-subtitle{font-size:1rem;font-weight:700;color:var(--ink);margin-bottom:16px;
  padding-left:10px;border-left:3px solid var(--accent)}

.spieltag-picker{display:flex;align-items:center;gap:8px;margin-bottom:22px;font-size:.88rem}
.spieltag-picker select{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);
  padding:8px 12px;font-size:.88rem;color:var(--ink);font-family:var(--font-body);cursor:pointer;font-weight:600}

.spieltag-heading{font-family:var(--font-display);font-size:1.05rem;font-weight:600;margin-bottom:12px;
  text-transform:uppercase;letter-spacing:.03em}

.table-scroll{overflow-x:auto;border:1px solid var(--line);border-radius:var(--radius);margin-bottom:10px}
table.results-table{width:100%;border-collapse:collapse;font-size:.88rem;background:var(--surface)}
table.results-table thead th{background:var(--ink);color:#fff;text-align:left;padding:11px 14px;
  font-weight:600;font-size:.72rem;white-space:nowrap;text-transform:uppercase;letter-spacing:.05em;
  font-family:var(--font-display)}
table.results-table thead th.col-ergebnis{text-align:center}
table.results-table thead th.col-heim{text-align:right}
table.results-table tbody tr:nth-child(even){background:var(--bg-alt)}
table.results-table tbody tr:hover{background:#fbfbc8}
table.results-table td{padding:10px 14px;border-top:1px solid var(--line)}
table.results-table td.col-datum{color:var(--muted);white-space:nowrap;font-size:.8rem;font-variant-numeric:tabular-nums}
table.results-table td.col-heim{text-align:right}
table.results-table td.col-gast{text-align:left}
table.results-table td.col-ergebnis{text-align:center;font-weight:700;white-space:nowrap;
  font-family:var(--font-display);font-size:1rem;font-variant-numeric:tabular-nums}
table.results-table td.col-ergebnis.ergebnis-offen{color:var(--muted);font-weight:500;font-family:var(--font-body);font-size:.88rem}
table.results-table td.col-vergleich,table.results-table thead th.col-vergleich{width:1%;text-align:center;padding-left:6px;padding-right:10px}

.h2h-icon{background:none;border:none;padding:4px;margin:0;color:var(--muted);cursor:pointer;
  border-radius:2px;display:inline-flex;align-items:center;justify-content:center;line-height:0}
.h2h-icon:hover{color:var(--ink);background:var(--accent)}
.h2h-overlay{position:fixed;inset:0;background:rgba(14,15,18,.7);display:flex;align-items:center;
  justify-content:center;padding:20px;z-index:1000}
.h2h-overlay[hidden]{display:none}
.h2h-modal{background:var(--surface);border-radius:var(--radius);max-width:560px;width:100%;
  max-height:85vh;overflow-y:auto;padding:26px 28px;position:relative;border-top:5px solid var(--accent);
  box-shadow:0 24px 60px rgba(0,0,0,.35)}
.h2h-close{position:absolute;top:16px;right:16px;background:none;border:none;font-size:1.5rem;
  line-height:1;color:var(--muted);cursor:pointer;padding:4px}
.h2h-close:hover{color:var(--ink)}
.h2h-title{margin:0 34px 18px 0;font-size:1.15rem;color:var(--ink);font-family:var(--font-display);
  text-transform:uppercase;letter-spacing:.01em}
.h2h-record{display:flex;gap:2px;margin-bottom:20px;border:1px solid var(--line)}
.h2h-chip{flex:1;text-align:center;padding:10px 6px;font-weight:700;font-size:.95rem;
  background:var(--bg-alt);color:var(--ink)}
.h2h-chip-a,.h2h-chip-b{display:flex;flex-direction:column;gap:3px}
.h2h-chip-label{font-weight:600;font-size:.68rem;color:var(--muted);white-space:normal;line-height:1.2;
  text-transform:uppercase;letter-spacing:.03em}
.h2h-chip-num{font-size:1.3rem;font-weight:700;font-family:var(--font-display);font-variant-numeric:tabular-nums}
.h2h-chip-draw{font-weight:600;font-size:.82rem;color:var(--muted);background:var(--surface)}
.h2h-list{display:flex;flex-direction:column;gap:2px}
.h2h-match-row{padding:10px 0;border-top:1px solid var(--line)}
.h2h-match-row:first-child{border-top:none}
.h2h-match-meta{font-size:.74rem;color:var(--muted);margin-bottom:4px;text-decoration:none;display:inline-block;
  font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.h2h-rd-short{display:none}
@media (max-width:480px){.h2h-rd-long{display:none}.h2h-rd-short{display:inline}}
.h2h-match-meta:hover{color:var(--ink);text-decoration:underline}
.h2h-match-teams{display:flex;align-items:center;gap:10px;font-size:.9rem}
.h2h-match-team{flex:1;color:var(--muted)}
.h2h-match-team .h2h-match-today{display:block;font-size:.7rem;font-weight:600;color:var(--muted);
  text-transform:uppercase;letter-spacing:.02em}
.h2h-match-team:first-child{text-align:right}
.h2h-match-team.h2h-winner{color:var(--ink);font-weight:700}
.h2h-match-score{flex:none;font-weight:700;color:var(--ink);min-width:48px;text-align:center;
  font-family:var(--font-display);font-variant-numeric:tabular-nums}
.h2h-empty{color:var(--muted);font-size:.88rem}

.spieltag-stats{font-size:.82rem;color:var(--muted);margin-bottom:22px;padding:10px 14px;
  background:var(--bg-alt);border-radius:var(--radius)}
.spieltag-stats .stats-heading{font-weight:700;color:var(--ink);margin-bottom:3px;
  text-transform:uppercase;letter-spacing:.03em;font-size:.72rem}

.spielfrei-note{font-size:.8rem;color:var(--muted);margin:-6px 0 20px;padding:9px 14px;
  background:var(--bg-alt);border-left:3px solid var(--accent)}
.spielfrei-note strong{color:var(--ink);font-weight:700}

.gt-footnote{font-size:.8rem;color:var(--muted);margin:16px 0;padding:9px 14px;
  background:var(--bg-alt);border-left:3px solid var(--accent)}
.gt-footnote > strong{color:var(--ink);font-weight:700;display:block;margin-bottom:4px}
.gt-footnote-line .gt-footnote-nr{display:inline;margin-bottom:0}
.gt-footnote-line{margin:4px 0}

.pdf-export-row{text-align:right;margin-top:16px}
.btn-pdf-export{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;
  border:1px solid var(--ink);padding:9px 18px;border-radius:var(--radius);font-size:.8rem;font-weight:700;
  text-decoration:none;text-transform:uppercase;letter-spacing:.04em;transition:background .15s ease,color .15s ease}
.btn-pdf-export:hover{background:var(--accent);color:var(--accent-ink);border-color:var(--accent)}

.tabs-bar{display:flex;gap:2px;flex-wrap:wrap;margin-bottom:20px;background:var(--bg-alt);padding:4px;border-radius:var(--radius)}
.tab-item{padding:9px 16px;font-size:.8rem;text-decoration:none;color:var(--muted);font-weight:700;
  border-radius:2px;text-transform:uppercase;letter-spacing:.03em}
.tab-item:hover{color:var(--ink)}
.tab-item-active{color:var(--accent-ink);font-weight:700;background:var(--accent)}

.tipp-form{max-width:380px}
.tipp-form label{display:block;margin:12px 0 4px;font-size:.85rem;color:var(--muted)}
.tipp-form input[type=text],.tipp-form input[type=email],.tipp-form input[type=password]{
  width:100%;box-sizing:border-box;background:var(--bg-alt);border:1px solid var(--line);
  color:var(--ink);border-radius:var(--radius);padding:8px 10px;font-size:.9rem}
.btn-primary{display:inline-block;background:var(--accent);color:var(--accent-ink);border:none;border-radius:var(--radius);
  padding:9px 18px;font-size:.9rem;font-weight:700;cursor:pointer;margin-top:14px;text-decoration:none;
  text-transform:uppercase;letter-spacing:.03em}
.btn-primary:hover{opacity:.85}
.flash{padding:10px 14px;border-radius:var(--radius);margin-bottom:16px;font-size:.87rem;
  background:var(--surface);border:1px solid var(--line);border-left:4px solid var(--muted)}
.flash-success{border-left-color:var(--pitch)}
.flash-error{border-left-color:var(--danger)}
table.tipp-table{width:100%;border-collapse:collapse;font-size:.87rem;background:var(--surface)}
table.tipp-table thead th{background:var(--ink);color:#fff;text-align:left;padding:9px 12px;font-weight:600;font-size:.8rem;
  text-transform:uppercase;letter-spacing:.03em}
table.tipp-table tbody tr:nth-child(even){background:var(--bg-alt)}
table.tipp-table td{padding:8px 12px;border-top:1px solid var(--line)}
table.tipp-table td.tipp-col-datum{color:var(--muted);font-size:.8rem;white-space:nowrap}
table.tipp-table input[type=number]{width:52px;box-sizing:border-box;background:var(--bg-alt);
  border:1px solid var(--line);color:var(--ink);border-radius:var(--radius);padding:6px;
  font-size:.9rem;text-align:center}
.tipp-row-me{font-weight:700}
.tipp-readonly{color:var(--muted)}
/* Tippübersicht-Matrix (Rangliste + Tipps je Spieltag in einer Tabelle) */
table.tipp-matrix{width:100%;border-collapse:collapse;font-size:.8rem;background:var(--surface);white-space:nowrap}
table.tipp-matrix th,table.tipp-matrix td{padding:5px 8px;text-align:center;border-top:1px solid var(--border)}
table.tipp-matrix thead th{font-weight:600;color:var(--muted);font-size:.72rem}
table.tipp-matrix .tipp-matrix-team{line-height:1.3}
table.tipp-matrix .tipp-matrix-erg{font-weight:700;color:var(--text)}
table.tipp-matrix tbody td:nth-child(3){text-align:left;font-weight:600}
table.tipp-matrix .tipp-matrix-treffer{color:var(--text);font-weight:600}
table.tipp-matrix .tipp-matrix-treffer sub{color:var(--accent);font-weight:700;margin-left:1px}
table.tipp-matrix .tipp-matrix-leer{color:var(--muted)}
.tipp-rang-auf{color:var(--green)}
.tipp-rang-ab{color:#dc2626}
.tipp-spieltag-nav a{color:var(--accent);text-decoration:none;font-size:1rem}
.tipp-spieltag-sieger{color:#dc2626;font-weight:700}
.tipp-archiv-badge{display:inline-block;font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;padding:2px 7px;border-radius:999px;background:var(--bg);color:var(--muted);border:1px solid var(--border);vertical-align:middle}
/* Spielregeln-Erklärseite */
.tipp-regeln-block{border:1px solid var(--border);border-radius:var(--radius);margin-bottom:14px;padding:0 14px}
.tipp-regeln-block summary{padding:14px 4px;font-weight:700;cursor:pointer;color:var(--accent);list-style:none}
.tipp-regeln-block summary::-webkit-details-marker{display:none}
.tipp-regeln-block summary::after{content:"+";float:right;font-size:1.3rem;line-height:1}
.tipp-regeln-block[open] summary::after{content:"−"}
.tipp-regeln-block p{font-size:.88rem;margin:0 0 10px}
.tipp-regeln-punkte-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin:12px 0 16px}
.tipp-regeln-punkt-karte{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:12px;text-align:center;display:flex;flex-direction:column;gap:2px}
.tipp-regeln-punkt-karte .wert{font-size:1.4rem;font-weight:800;color:var(--accent)}
.tipp-regeln-punkt-karte .einheit{font-size:.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
.tipp-regeln-punkt-karte .label{font-size:.72rem;color:var(--muted);font-weight:600;margin-top:4px}
/* Statistik-Seite (Chart.js-Diagramme) */
.tipp-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.tipp-stat-box{border:1px solid var(--border);border-radius:var(--radius);padding:12px}
.tipp-stat-box h3{font-size:.85rem;margin:0 0 8px;color:var(--muted)}
.tipp-stat-erklaerung{font-size:.75rem;color:var(--muted);margin:0 0 8px}
.tipp-stat-chart{position:relative;height:200px}
.tipp-stat-chart-hoch{height:420px}
/* Diagonale Spaltenüberschriften für dynamische Sport-Tabellen (Vorbild
   volleyball-bundesliga.de) - wird gezielt auf die Kopfzellen der
   Zusatzspalten (nicht Platz/Team) der Volleyball-Tabelle angewendet. */
.standings-table thead th.st-diag{
  height:90px;white-space:nowrap;position:relative;vertical-align:bottom;padding:0 2px 4px;
}

/* Dropdown-Team-Auswahl im Spielplan (nur Volleyball) */
.schedule-picker-card{padding:14px 16px}
.schedule-picker-select{width:100%;max-width:320px;background:var(--surface);border:1px solid var(--border);
  color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.9rem}

.standings-table thead th.st-diag > span{
  position:absolute;left:2px;bottom:4px;
  /* Fester Drehpunkt am linken Spaltenrand (kein translateX/left:50% mehr) -
     siehe template/default/layout.tpl.php fuer die ausfuehrliche Begruendung
     (zwei Zwischenstaende mit zentrierter Box bzw. zentriertem Drehpunkt
     wurden getestet, verursachten bei den hier schmalen Spalten aber
     Textueberlappungen in Nachbarspalten bzw. in der Datenzeile). */
  transform:rotate(-45deg);transform-origin:left bottom;
  white-space:nowrap;font-size:.72rem;
}

.tipp-tipptabelle-hinweis{background:#fdf3d8;border:1px solid #f0d68a;color:#7a5c00;border-radius:var(--radius);padding:12px 14px;font-size:.85rem;margin-bottom:16px}





.info-copyright{color:var(--muted);font-size:.85rem}
.info-license{color:var(--muted);font-size:.8rem;margin-top:14px}
.info-links{font-size:.85rem;color:var(--muted)}
.info-links a{color:var(--ink);text-decoration:underline;text-decoration-color:var(--accent);text-decoration-thickness:2px}
.info-links a:hover{color:var(--ink);background:var(--accent)}

.cal-nav{display:flex;align-items:center;gap:14px;margin-bottom:16px;font-size:.9rem}
.cal-nav a{text-decoration:none;color:var(--ink);font-weight:700}
.cal-nav a:hover{color:var(--accent-ink);background:var(--accent)}
.cal-monthyear{font-weight:700;flex:1;text-align:center;font-family:var(--font-display);
  text-transform:uppercase;letter-spacing:.03em}
.cal-today-link{font-size:.78rem;color:var(--muted) !important;font-weight:600 !important}
table.cal-table{width:100%;border-collapse:collapse;table-layout:fixed}
table.cal-table th{background:var(--ink);color:#fff;padding:7px 4px;font-size:.68rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.03em}
table.cal-table td.cal-day{border:1px solid var(--line);vertical-align:top;height:58px;padding:4px 5px;font-size:.78rem}
table.cal-table td.cal-empty{background:var(--bg-alt);border:1px solid var(--line)}
.cal-daynum{color:var(--muted);font-size:.72rem;margin-bottom:2px;font-weight:700}
td.cal-today{background:#fbfbc8}
td.cal-today .cal-daynum{color:var(--ink);font-weight:700}
a.cal-entry{display:inline-block;background:var(--ink);color:#fff;border-radius:2px;
  padding:1px 5px;font-size:.68rem;text-decoration:none;margin:0 2px 2px 0;font-weight:600}
a.cal-entry:hover{background:var(--accent);color:var(--accent-ink)}

.bracket-scroll{overflow-x:auto;padding-bottom:6px}
.bracket{display:flex;gap:24px;min-width:min-content}
.bracket-round{display:flex;flex-direction:column;min-width:176px}
.bracket-round-heading{font-family:var(--font-display);font-size:.75rem;font-weight:700;color:var(--ink);
  text-align:center;margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em;
  border-bottom:2px solid var(--accent);padding-bottom:6px}
.bracket-round-pairings{display:flex;flex-direction:column;justify-content:space-around;flex:1}
.bracket-pairing{background:var(--surface);border:1px solid var(--line);border-left:3px solid var(--ink);
  padding:9px 11px;min-height:66px;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;margin:6px 0}
.bracket-team{padding:1px 0;font-size:.83rem;line-height:1.35;font-weight:600}
.bracket-team-name{display:inline-block}
.bracket-score{margin-top:4px;font-size:.85rem;font-weight:700;color:var(--ink);text-align:center;
  font-family:var(--font-display);font-variant-numeric:tabular-nums}
.bracket-compare{display:flex;justify-content:center;margin-top:2px}
.bracket-kickoff{margin-top:2px;font-size:.68rem;color:var(--muted);text-align:center}

table.standings-table{width:100%;border-collapse:collapse;font-size:.87rem;background:var(--surface)}
table.standings-table tbody tr{border-left:5px solid transparent}
table.standings-table thead th{background:var(--ink);color:#fff;padding:10px 10px;font-weight:600;
  font-size:.72rem;white-space:nowrap;text-transform:uppercase;letter-spacing:.05em;font-family:var(--font-display);vertical-align:bottom}
table.standings-table tbody tr:nth-child(even){background:var(--bg-alt)}
table.standings-table tbody tr:hover{background:#fbfbc8}
table.standings-table td{padding:9px 10px;border-top:1px solid var(--line)}
.st-platz{width:1%;text-align:center;color:var(--ink);font-weight:700;font-family:var(--font-display);
  font-size:1.25rem;font-variant-numeric:tabular-nums}
.st-team{font-weight:600;white-space:nowrap}
.st-team.fav-team{font-weight:700;color:var(--ink)}
.st-team.fav-team::after{content:'★';color:var(--accent-ink);background:var(--accent);
  font-size:.6rem;padding:1px 3px;margin-left:6px;border-radius:2px;vertical-align:middle}
.st-num{text-align:center;width:1%;white-space:nowrap;font-variant-numeric:tabular-nums}
.st-pkt{text-align:center;font-weight:700;color:var(--ink);width:1%;white-space:nowrap;
  font-family:var(--font-display);font-size:1.05rem;font-variant-numeric:tabular-nums}
.st-straf-hinweis{cursor:help;font-size:.8rem;margin-left:3px}
.st-straf-hinweis a{color:inherit;text-decoration:none}
.st-straf-hinweis a:hover{text-decoration:underline}
.st-footnotes{padding:10px 16px 4px;border-top:1px solid var(--line)}
.st-spieltag-nav{display:flex;justify-content:space-between;align-items:center;padding:10px 16px;font-size:.85rem;gap:10px}
.st-spieltag-nav a{color:var(--ink);text-decoration:none;font-weight:700;text-transform:uppercase;font-size:.78rem;letter-spacing:.02em}
.st-spieltag-nav a:hover{text-decoration:underline}
.st-spieltag-nav-next{margin-left:auto}
/* Form-Dots (letzte 5 Spiele) */
.st-form{white-space:nowrap;text-align:center;min-width:70px}
.form-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin:0 1px;vertical-align:middle}
.form-win{background:#22c55e}.form-draw{background:#9ca3af}.form-loss{background:#1f2937}
.form-dot-wrap{position:relative;display:inline-block}
.form-tooltip{display:none;position:absolute;top:135%;right:-8px;left:auto;
  background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 16px;
  min-width:170px;max-width:230px;box-shadow:0 6px 20px rgba(0,0,0,.28);z-index:30;text-align:center;
  font-size:.8rem;white-space:nowrap;color:var(--text)}
.form-dot-wrap:hover .form-tooltip{display:block}
.form-tooltip .ft-meta{display:block;color:var(--muted);font-size:.7rem;margin-bottom:6px}
.form-tooltip .ft-match{display:flex;align-items:center;justify-content:center;gap:14px;margin:8px 0}
.form-tooltip .ft-side{display:flex;flex-direction:column;align-items:center;gap:8px;min-width:34px}
.form-tooltip .ft-side img{height:28px;width:auto;margin:0;vertical-align:top}
.form-tooltip .ft-side-name{font-size:.68rem;color:var(--muted);white-space:nowrap}
.form-tooltip .ft-side.ft-own .ft-side-name{color:var(--text);font-weight:700}
.form-tooltip .ft-score{font-size:1.05rem}
.form-tooltip .ft-score-open{color:var(--muted)}
.form-tooltip .ft-date{display:block;color:var(--muted);margin-top:5px}
.form-next{display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;
  border-radius:50%;background:var(--muted);color:#fff;font-size:.6rem;font-weight:700;
  line-height:1;margin-left:3px;vertical-align:middle;padding:0}
/* Trend-Pfeil */
.st-trend{text-align:center;width:40px}
.trend-arrow{font-size:.7rem;display:inline-block}
.trend-up{color:#22c55e}.trend-down{color:#ef4444}.trend-same{color:#9ca3af}
/* Tabellen-Navigation (Gesamt/Heim/Gast/Hin/Rueck) */
.standings-nav{display:flex;gap:0;flex-wrap:wrap;margin-bottom:14px;border-bottom:1px solid var(--border)}
.standings-nav-item{padding:8px 14px;font-size:.85rem;text-decoration:none;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-1px;white-space:nowrap}
.standings-nav-item:hover{color:var(--text)}
.standings-nav-item.standings-nav-active{color:var(--accent);font-weight:600;border-bottom-color:var(--accent)}

.st-footnote-item{font-size:.8rem;color:var(--muted);margin:4px 0}
.st-footnote-item a{color:var(--muted);text-decoration:none;margin-right:2px}
.st-footnote-item a:hover{text-decoration:underline}
.st-num.diff-pos{color:var(--pitch);font-weight:700}
.st-num.diff-neg{color:var(--danger);font-weight:700}

.schedule-wrap{display:flex;gap:0;padding:0;overflow:hidden;border:1px solid var(--line);border-radius:var(--radius)}
.schedule-sidebar{flex:0 0 176px;display:flex;flex-direction:column;border-right:1px solid var(--line);
  max-height:540px;overflow-y:auto;background:var(--bg-alt)}
.team-sidebar-item{padding:9px 12px;text-align:left;font-size:.82rem;font-weight:700;color:var(--ink);
  text-decoration:none;border-bottom:1px solid var(--line);border-left:3px solid transparent}
.team-sidebar-item:hover{background:var(--surface);border-left-color:var(--accent)}
.team-sidebar-item.team-sidebar-active{background:var(--ink);color:#fff;border-left-color:var(--accent)}
.schedule-content{flex:1;padding:22px 24px;min-width:0;background:var(--surface)}
.schedule-content .empty-msg{margin:0}
.col-nr{color:var(--muted);width:1%;white-space:nowrap;text-align:center;font-variant-numeric:tabular-nums}
.col-vs{color:var(--muted);width:1%;text-align:center}
.col-heim,.col-gast{text-align:left}
.col-heim.schedule-own,.col-gast.schedule-own{font-weight:700;color:var(--ink)}

table.kreuz-table{border-collapse:collapse;font-size:.76rem;background:var(--surface);white-space:nowrap}
table.kreuz-table th, table.kreuz-table td{border:1px solid var(--line);padding:6px 9px}
table.kreuz-table thead th{background:var(--ink);color:#fff;font-weight:700;text-align:center;
  font-family:var(--font-display);letter-spacing:.02em}
.kz-corner{background:var(--ink)}
.kz-rowlabel{background:var(--ink);color:#fff;font-weight:700;text-align:left;position:sticky;left:0}
.kz-cell{text-align:center;color:var(--ink);font-variant-numeric:tabular-nums}
.kz-cell.kz-diag{background:var(--bg-alt)}
.kz-col.kz-fav,.kz-rowlabel.kz-fav{background:var(--accent);color:var(--accent-ink)}
.kz-col:hover,.kz-rowlabel:hover{background:#2a2c33}
.kz-cell.kz-fav-row,.kz-cell.kz-fav-col{background:#fbfbc8}
.kz-cell.kz-diag.kz-fav-row,.kz-cell.kz-diag.kz-fav-col{background:#f3f5a8}

.fk-chart-wrap{position:relative;width:100%;min-height:420px}

.ligastat-picker{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap}
.ligastat-picker select{background:var(--bg-alt);border:1px solid var(--line);border-radius:var(--radius);
  padding:8px 12px;font-size:.88rem;color:var(--ink);font-family:var(--font-body);cursor:pointer;flex:1;
  min-width:180px;font-weight:600}
.ligastat-compare{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
@media (max-width:640px){.ligastat-compare{grid-template-columns:1fr}}
.ligastat-box{border:1px solid var(--line);border-radius:var(--radius);padding:16px 18px;background:var(--bg-alt);
  border-top:4px solid var(--ink)}
.ligastat-box h3{font-size:1rem;margin-bottom:12px;font-family:var(--font-display);text-transform:uppercase;letter-spacing:.02em}
table.ligastat-kv{width:100%;font-size:.83rem;border-collapse:collapse}
table.ligastat-kv td{padding:6px 4px;border-top:1px solid var(--line);vertical-align:top}
table.ligastat-kv td:first-child{color:var(--muted);width:46%;font-weight:600}
.ligastat-chances{font-size:.92rem;margin:16px 0}
table.ligastat-overall{width:100%;border-collapse:collapse;font-size:.83rem;margin:14px 0}
table.ligastat-overall th{background:var(--ink);color:#fff;padding:8px 10px;text-align:left;font-weight:700;
  font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-family:var(--font-display)}
table.ligastat-overall td{padding:8px 10px;border-top:1px solid var(--line)}
.ligastat-remaining-eval{margin-top:16px}

footer.site{text-align:center;color:var(--muted);font-size:.8rem;padding:28px 20px;border-top:1px solid var(--line);margin-top:12px}
footer.site a{color:inherit;text-decoration:underline dotted;text-underline-offset:2px}
footer.site a:hover{color:var(--accent)}
.tipp-site-link{display:inline-flex;align-items:center;gap:6px;text-decoration:none;font-weight:600}
.tipp-site-link-icon{height:20px;width:auto;display:inline-block;vertical-align:middle}
.tipp-section-heading{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.tipp-section-heading-logo{height:44px;width:auto;display:block}
.tipp-section-heading h1{margin:0;font-size:1.3rem}

footer.site .lang-switch-wrap{margin-bottom:14px}
footer.site .template-switch{display:inline-block}
footer.site .template-switch select{background:transparent;border:none;color:var(--muted);
  font-size:.8rem;font-family:inherit;padding:0;cursor:pointer;text-decoration:underline;text-underline-offset:2px}
footer.site .template-switch select:hover{color:var(--ink)}
</style>
</head>
<body>

<main>
<!--Hauptteil-->
</main>

<footer class="site">
  <div class="lang-switch-wrap"><!--TippspielLink--> <!--Sprachauswahl--></div>
  <!--PoweredBy--><br><!--TemplateZeile--><br><!--Berechnungszeit-->
</footer>

<script>
// Form-Tooltip-Positionierung (Vorbild: Tippy.js-Technik anderer
// Portale) - hängt das Tooltip beim Hover per JS an <body> ("Portal"),
// damit es nicht vom overflow:auto des Tabellen-Scrollcontainers
// abgeschnitten wird, und positioniert es dynamisch: kippt nach oben, wenn
// unten kein Platz ist, und klemmt horizontal an den Viewport-Rand statt
// darüber hinauszuragen. Ohne JavaScript bleibt die einfache CSS-:hover-
// Variante (siehe .form-dot-wrap .form-tooltip weiter oben) als Fallback
// aktiv - dieses Skript ist eine reine Verbesserung, keine Voraussetzung.
(function () {
  if (typeof document === 'undefined') { return; }
  var homes = new WeakMap();  // wrap -> {tip, parent, next}
  var visible = new Set();    // aktuell portierte+sichtbare Einträge

  function position(wrap, tip) {
    document.body.appendChild(tip);
    tip.style.position = 'fixed';
    tip.style.visibility = 'hidden';
    tip.style.display = 'block';
    tip.style.left = '0px';
    tip.style.top = '0px';

    var wrapRect = wrap.getBoundingClientRect();
    var tipRect = tip.getBoundingClientRect();
    var vw = document.documentElement.clientWidth;
    var vh = document.documentElement.clientHeight;
    var margin = 8;

    var spaceBelow = vh - wrapRect.bottom;
    var openBelow = spaceBelow >= tipRect.height + margin || wrapRect.top < tipRect.height + margin;
    var top = openBelow ? wrapRect.bottom + 6 : wrapRect.top - tipRect.height - 6;

    var left = wrapRect.right - tipRect.width;
    if (left < margin) { left = margin; }
    if (left + tipRect.width > vw - margin) { left = vw - margin - tipRect.width; }

    tip.style.top = Math.max(margin, top) + 'px';
    tip.style.left = left + 'px';
    tip.style.visibility = 'visible';
  }

  function hide(entry) {
    entry.tip.style.display = 'none';
    if (entry.tip.parentNode !== entry.parent) {
      entry.parent.insertBefore(entry.tip, entry.next);
    }
    visible.delete(entry);
  }

  document.addEventListener('mouseover', function (e) {
    var wrap = e.target.closest && e.target.closest('.form-dot-wrap');
    if (!wrap) { return; }
    var entry = homes.get(wrap);
    if (!entry) {
      var tip = wrap.querySelector('.form-tooltip');
      if (!tip) { return; }
      entry = { tip: tip, parent: tip.parentNode, next: tip.nextSibling };
      homes.set(wrap, entry);
    }
    position(wrap, entry.tip);
    visible.add(entry);
  });

  document.addEventListener('mouseout', function (e) {
    var wrap = e.target.closest && e.target.closest('.form-dot-wrap');
    if (!wrap) { return; }
    var entry = homes.get(wrap);
    if (!entry) { return; }
    var related = e.relatedTarget;
    if (related && (wrap.contains(related) || entry.tip.contains(related))) { return; }
    hide(entry);
  });

  // Beim Scrollen/Resizen löst sich das fixierte Tooltip optisch vom Punkt -
  // dann lieber ausblenden statt hinterherzuspringen.
  ['scroll', 'resize'].forEach(function (evt) {
    window.addEventListener(evt, function () {
      visible.forEach(hide);
    }, true);
  });
})();
</script>
</body>
</html>
