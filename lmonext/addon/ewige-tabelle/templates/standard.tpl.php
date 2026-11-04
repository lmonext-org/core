<!--
  Template: addon/ewige | Filename: standard.tpl.php | Fileversion: 1.2.2
  Ewige Tabelle (aufsummierte Stände über mehrere Ligen), gleiche Optik wie
  addon/mini/templates/standard.tpl.php (LMOnext-Look: #153A8C, Rahmen #e3e7ee).
-->
<style>
.lmo-eternal-wrap{max-width:820px;margin:0 auto;font-family:'Segoe UI',system-ui,-apple-system,sans-serif}
.lmo-eternal{width:100%;border-collapse:collapse;font-size:.82rem;background:#fff;border:1px solid #e3e7ee;border-radius:8px;overflow:hidden}
.lmo-eternal caption{background:#153A8C;color:#fff;font-weight:700;padding:8px 12px;text-align:left;caption-side:top}
.lmo-eternal thead th{background:#f4f6fa;color:#697182;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;font-weight:600;padding:8px 10px;border-bottom:1px solid #e3e7ee;white-space:nowrap}
.lmo-eternal thead th.lmo-l{text-align:left}
.lmo-eternal thead th.lmo-r{text-align:right}
.lmo-eternal td{padding:6px 10px;border-top:1px solid #eef1f6;white-space:nowrap}
.lmo-eternal td.lmo-platz{color:#697182;width:1%;text-align:center;font-weight:600}
.lmo-eternal td.lmo-logo{width:1%;text-align:center;padding-left:2px;padding-right:2px}
.lmo-eternal td.lmo-logo img{height:18px;width:auto;vertical-align:middle}
.lmo-eternal td.lmo-team{text-align:left;font-weight:600}
.lmo-eternal .ewige-ehemals{font-weight:400;color:#9098a8;font-size:.85em;white-space:normal}
.lmo-eternal td.lmo-r{text-align:right;font-variant-numeric:tabular-nums}
.lmo-eternal td.lmo-pkt{text-align:right;font-weight:700;color:#153A8C;font-variant-numeric:tabular-nums}
.lmo-eternal tbody tr:hover{background:#f9fbff}
.lmo-eternal-foot{font-size:.68rem;color:#9098a8;text-align:right;padding:6px 2px 0}
.st-straf-hinweis{cursor:help;font-size:.75rem;margin-left:3px}
.st-straf-hinweis a{color:inherit;text-decoration:none}
.st-straf-hinweis a:hover{text-decoration:underline}
.st-footnotes{padding:10px 4px 0;border-top:1px solid #e3e7ee;margin-top:8px}
.st-footnote-item{font-size:.72rem;color:#697182;margin:4px 0}
.st-footnote-item a{color:#697182;text-decoration:none;margin-right:2px}
.st-footnote-item a:hover{text-decoration:underline}
</style>
<div class="lmo-eternal-wrap">
<table class="lmo-eternal">
  <caption><!--Tabelle--></caption>
  <thead>
    <tr>
      <th class="lmo-l">#</th>
      <th class="lmo-l"></th>
      <th class="lmo-l">Team</th>
      <th class="lmo-r">Sa.</th>
      <th class="lmo-r">Sp.</th>
      <th class="lmo-r">S</th>
      <th class="lmo-r">U</th>
      <th class="lmo-r">N</th>
      <th class="lmo-r"><!--ToreLabel--></th>
      <th class="lmo-r">Diff</th>
      <th class="lmo-r" title="Punkte nach dem in der jeweiligen Saison tatsächlich gültigen Punktesystem">Pkt (hist.)</th>
      <th class="lmo-r" title="Punkte, als hätte immer das 2-Punkte-System gegolten (Sieg=2, Unentschieden=1)">Pkt (2er)</th>
      <th class="lmo-r" title="Punkte, als hätte immer das 3-Punkte-System gegolten (Sieg=3, Unentschieden=1)">Pkt (3er)</th>
    </tr>
  </thead>
  <tbody>
  <!-- BEGIN Inhalt -->
  <tr class="<!--Class-->" style="<!--Style-->">
    <td class="lmo-platz"><!--Platz--></td>
    <td class="lmo-logo"><!--Logo--></td>
    <td class="lmo-team" title="<!--TeamLangTitel-->"><!--TeamLang--><!--StrafHinweis--></td>
    <td class="lmo-r"><!--Saisons--></td>
    <td class="lmo-r"><!--Spiele--></td>
    <td class="lmo-r"><!--Siege--></td>
    <td class="lmo-r"><!--Unentschieden--></td>
    <td class="lmo-r"><!--Niederlagen--></td>
    <td class="lmo-r"><!--PlusTore-->:<!--MinusTore--></td>
    <td class="lmo-r"><!--Tordifferenz--></td>
    <td class="lmo-pkt"><!--Punkte--></td>
    <td class="lmo-pkt"><!--Punkte2-->:<!--Minuspunkte2--></td>
    <td class="lmo-pkt"><!--Punkte3--></td>
  </tr>
  <!-- END Inhalt -->
  </tbody>
</table>
<div class="lmo-eternal-foot"><!--Fusszeile--></div>
<!--Fussnoten-->
<div class="lmo-eternal-foot"><!--Copyright--></div>
</div>
