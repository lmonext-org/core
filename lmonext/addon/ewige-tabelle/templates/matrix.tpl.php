<!--
  Template: addon/ewige | Filename: matrix.tpl.php | Fileversion: 1.0.0
  Mehrjahres-Vergleich (Rang/Punkte je Team pro Saison), gleiche Optik wie
  addon/mini/templates/standard.tpl.php (LMOnext-Look: #153A8C, Rahmen #e3e7ee).
                                        
  Blöcke: <!-- BEGIN Spalte --> (Saison-Spalte im Kopf),
          <!-- BEGIN TeamZeile --> (Team-Zeile) mit darin liegendem
          <!-- BEGIN Zelle --> (eine Saison-Spalte pro Team).
-->
<style>
.lmo-matrix-wrap{max-width:100%;font-family:'Segoe UI',system-ui,-apple-system,sans-serif}
.lmo-scrollx{overflow-x:auto}
.lmo-eternal{border-collapse:collapse;font-size:.82rem;background:#fff;border:1px solid #e3e7ee;border-radius:8px;overflow:hidden}
.lmo-eternal caption{background:#153A8C;color:#fff;font-weight:700;padding:8px 12px;text-align:left;caption-side:top}
.lmo-eternal thead th{background:#f4f6fa;color:#697182;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;font-weight:600;padding:8px 10px;border-bottom:1px solid #e3e7ee;white-space:nowrap}
.lmo-eternal thead th.lmo-l{text-align:left}
.lmo-eternal thead th.lmo-r{text-align:right}
.lmo-eternal td{padding:6px 10px;border-top:1px solid #eef1f6;white-space:nowrap}
.lmo-eternal td.lmo-team{text-align:left;font-weight:600}
.lmo-eternal td.lmo-r{text-align:right;font-variant-numeric:tabular-nums}
.lmo-eternal td.lmo-leer{color:#c2c8d2}
.lmo-eternal tbody tr:hover{background:#f9fbff}
.lmo-eternal-foot{font-size:.68rem;color:#9098a8;text-align:right;padding:6px 2px 0}
</style>
<div class="lmo-matrix-wrap">
<div class="lmo-scrollx">
<table class="lmo-eternal">
  <caption><!--Tabelle--></caption>
  <thead>
    <tr>
      <th class="lmo-l">Team</th>
      <!-- BEGIN Spalte -->
      <th class="lmo-r" title="<!--SaisonTitel-->"><!--SaisonName--></th>
      <!-- END Spalte -->
    </tr>
  </thead>
  <tbody>
  <!-- BEGIN TeamZeile -->
  <tr>
    <td class="lmo-team"><!--TeamName--></td>
    <!-- BEGIN Zelle -->
    <td class="lmo-r <!--ZelleKlasse-->"><!--ZelleInhalt--></td>
    <!-- END Zelle -->
  </tr>
  <!-- END TeamZeile -->
  </tbody>
</table>
</div>
<div class="lmo-eternal-foot"><!--Fusszeile--></div>
<div class="lmo-eternal-foot"><!--Copyright--></div>
</div>
