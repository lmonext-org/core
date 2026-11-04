<!--
  Template: addon/mini | Filename: standard.tpl.php | Fileversion: 1.2.1
  Reines Markup + Platzhalter (siehe addon/mini/lmo-minitab.php), kein PHP.
-->
<style>
.lmo-mini-wrap{display:inline-block;max-width:340px;width:100%;box-sizing:border-box;
  font-family:'Segoe UI',system-ui,-apple-system,sans-serif}
.lmo-mini{width:100%;max-width:100%;border-collapse:collapse;table-layout:auto;
  font-size:.8rem;background:#fff;border:1px solid #e3e7ee;border-radius:8px;overflow:hidden}
.lmo-mini caption{background:#153A8C;color:#fff;font-weight:700;padding:6px 10px;text-align:left;caption-side:top}
.lmo-mini caption a{color:#fff;text-decoration:none}
.lmo-mini caption a:hover{text-decoration:underline}
.lmo-mini td{padding:4px 8px;border-top:1px solid #eef1f6;white-space:nowrap}
.lmo-mini td.lmo-mini-platz{color:#697182;width:1%;text-align:center;font-weight:600}
.lmo-mini td.lmo-mini-logo{width:1%;text-align:center;padding-left:2px;padding-right:2px}
.lmo-mini td.lmo-mini-logo img{height:18px;width:auto;vertical-align:middle}
.lmo-mini td.lmo-mini-team{text-align:left;width:100%}
.lmo-mini td.lmo-mini-diff,.lmo-mini td.lmo-mini-pkt{text-align:center;width:1%}
.lmo-mini td.lmo-mini-pkt{font-weight:700;color:#153A8C}
.lmo-mini tr.mini-fav td{background:#fff7d6}
.lmo-mini-foot{font-size:.68rem;color:#9098a8;text-align:right;padding:4px 2px 0}
</style>
<div class="lmo-mini-wrap">
<table class="lmo-mini">
  <caption><a href="<!--Link-->" target="_blank" rel="noopener"><!--Tabelle--></a></caption>
  <!-- BEGIN Inhalt -->
  <tr class="<!--Class-->" style="<!--Style-->">
    <td class="lmo-mini-platz"><!--Platz--></td>
    <td class="lmo-mini-logo"><!--Logo--></td>
    <td class="lmo-mini-team" title="<!--TeamLang-->"><!--Team--></td>
    <td class="lmo-mini-diff"><!--Tordifferenz--></td>
    <td class="lmo-mini-pkt"><!--Punkte--></td>
  </tr>
  <!-- END Inhalt -->
</table>
<div class="lmo-mini-foot"><!--ligaDatum--></div>
<div class="lmo-mini-foot"><!--Copyright--></div>
</div>
