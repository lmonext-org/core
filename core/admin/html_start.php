<?php
/**
 * Project: LMOnext
 * Filename: html_start.php
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

// ── HTML-Infrastruktur ──────────────────────────────────────────────────────
// ── Hilfsfunktion: Flash-HTML ─────────────────────────────────────────────────

function renderFlash(?array $flash): string {
    if (!$flash) { return ''; }
    return '<div class="flash '.h($flash['type']).'">'.h($flash['msg']).'</div>';
}
?>
<!DOCTYPE html>
<html lang="<?= h(getCurrentLanguage()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(ADMIN_TITLE) ?></title>
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
<meta name="msapplication-TileColor" content="#153A8C">
<meta name="msapplication-TileImage" content="assets/favicon/ms-icon-144x144.png">
<meta name="msapplication-config" content="assets/favicon/browserconfig.xml">
<meta name="theme-color" content="#153A8C">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0f1117;--surface:#1a1d27;--surface2:#232736;--border:#2e3247;
  --accent:#3b82f6;--green:#22c55e;--red:#ef4444;--yellow:#f59e0b;
  --text:#e2e8f0;--muted:#64748b;--radius:8px;
  --font:'Segoe UI',system-ui,sans-serif;
}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex}
.sidebar{width:220px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:10}
.sidebar-logo{padding:20px 16px 12px;font-size:1.1rem;font-weight:700;letter-spacing:.5px;border-bottom:1px solid var(--border);color:var(--accent)}
.sidebar-logo span{color:var(--text);font-weight:400}
.nav-list{list-style:none;padding:12px 8px;flex:1}
.nav-list li a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:var(--radius);color:var(--muted);text-decoration:none;font-size:.9rem;transition:background .15s,color .15s}
.nav-list li a:hover,.nav-list li a.active{background:var(--surface2);color:var(--text)}
.nav-list li a.active{color:var(--accent)}
.sidebar-footer{padding:12px 16px;border-top:1px solid var(--border);font-size:.8rem;color:var(--muted)}
.sidebar-footer a{color:var(--red);text-decoration:none}
.main{margin-left:220px;flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;gap:12px}
.topbar h1{font-size:1.05rem;font-weight:600}
.topbar .badge{background:var(--surface2);border:1px solid var(--border);border-radius:20px;padding:2px 10px;font-size:.75rem;color:var(--muted);margin-left:auto}
.content{padding:28px;flex:1;max-width:1000px}
.flash{padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:.9rem;border-left:4px solid}
.flash.success{background:#052e16;border-color:var(--green);color:#86efac}
.flash.error{background:#2d0a0a;border-color:var(--red);color:#fca5a5}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:20px}
.card h2{font-size:.8rem;font-weight:600;margin-bottom:16px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.tbl{width:100%;border-collapse:collapse;font-size:.88rem}
.tbl th{text-align:left;padding:8px 12px;border-bottom:1px solid var(--border);color:var(--muted);font-weight:500}
.tbl td{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:var(--surface2)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--radius);border:none;cursor:pointer;font-size:.85rem;font-family:var(--font);text-decoration:none;transition:opacity .15s}
.btn:hover{opacity:.85}
.btn-primary{background:var(--accent);color:#fff}
.btn-success{background:#166534;color:#bbf7d0}
.btn-danger{background:var(--red);color:#fff}
.archiv-dd-item{display:block;width:100%;text-align:left;padding:8px 14px;background:transparent;
  border:none;color:var(--text);cursor:pointer;font-size:.85rem;line-height:1.4}
.archiv-dd-item:hover{background:var(--surface2)}
details>summary{outline:none}
details>summary::-webkit-details-marker{display:none}
details[open]>.archiv-summary .archiv-arrow{transform:rotate(90deg)}
.archiv-summary:hover{background:var(--bg) !important}
.archiv-summary .archiv-arrow{transition:transform .15s;display:inline-block}
.btn-muted{background:var(--surface2);color:var(--text);border:1px solid var(--border)}
.btn-sm{padding:4px 10px;font-size:.78rem}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.82rem;color:var(--muted);margin-bottom:5px}
.form-group input[type=text],.form-group input[type=password],.form-group input[type=number],.form-group input[type=file],.form-group select{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:9px 12px;font-size:.9rem;font-family:var(--font);outline:none;transition:border-color .15s}
.form-group select option{background:var(--surface)}
.form-group input:focus,.form-group select:focus{border-color:var(--accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-row-3{display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px}
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;text-align:center}
.stat-card .val{font-size:2rem;font-weight:700;color:var(--accent)}
.stat-card .lbl{font-size:.78rem;color:var(--muted);margin-top:2px}
.wizard-steps{display:flex;margin-bottom:24px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.wizard-step{flex:1;padding:10px 6px;text-align:center;font-size:.8rem;color:var(--muted);border-right:1px solid var(--border)}
.wizard-step:last-child{border-right:none}
.wizard-step.active{background:var(--accent);color:#fff;font-weight:600}
.wizard-step.done{background:var(--surface2);color:var(--green)}
.ko-round{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:12px}
.ko-round h3{font-size:.85rem;font-weight:600;margin-bottom:10px}
.ko-pair{display:grid;grid-template-columns:1fr auto 1fr auto;gap:8px;align-items:center;margin-bottom:8px}
.ko-pair select{background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 8px;font-size:.85rem;width:100%}
.ko-pair .vs{color:var(--muted);font-size:.8rem;text-align:center}
.ko-pair .rm{background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0 4px}
.chip{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.75rem;font-weight:600}
.chip-green{background:#052e16;color:var(--green)}
.chip-blue{background:#0c1a3a;color:#93c5fd}
.chip-yellow{background:#2d1e00;color:var(--yellow)}
.login-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg);width:100%}
.login-box{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:36px 32px;width:360px}
.login-box h1{font-size:1.4rem;margin-bottom:6px}
.login-box p{font-size:.85rem;color:var(--muted);margin-bottom:24px}
.text-muted{color:var(--muted)}
.back-link{font-size:.85rem;color:var(--muted);text-decoration:none;display:inline-block;margin-bottom:16px}
.preview-scroll{max-height:400px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius)}
.warn-box{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;font-size:.84rem;color:var(--muted);margin-bottom:16px}
.lang-switch{display:inline-flex}
.lang-switch select{background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 8px;font-size:.82rem;font-family:var(--font);cursor:pointer;outline:none}
.lang-switch select:focus{border-color:var(--accent)}
.lang-switch select option{background:var(--surface)}
</style>
</head>
<body>

<?php renderFlash($flash ?? null); ?>
