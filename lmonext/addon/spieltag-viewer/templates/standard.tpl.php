<style>
.vw-wrap{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;color:#1f2430;max-width:960px;margin:0 auto}
.vw-header{text-align:center;margin:0 0 16px}
.vw-header h2{font-size:1.15rem;font-weight:700;margin:0 0 4px}
.vw-header .vw-range{font-size:.82rem;color:#9098a8}

.vw-liga-block{margin:0 0 20px;border:1px solid #e4e7ec;border-radius:10px;overflow:hidden;box-shadow:0 1px 2px rgba(20,24,32,.04)}
.vw-liga-name{font-size:.92rem;font-weight:700;margin:0;padding:9px 14px;background:#f5f6f8;border-bottom:1px solid #e4e7ec;color:#1f2430}

.vw-table{width:100%;table-layout:fixed;border-collapse:collapse;font-size:.86rem}
.vw-table col.vw-col-datum{width:15%}
.vw-table col.vw-col-zeit{width:9%}
.vw-table col.vw-col-heim{width:33%}
.vw-table col.vw-col-erg{width:10%}
.vw-table col.vw-col-gast{width:33%}
.vw-table th{text-align:left;font-size:.7rem;font-weight:600;color:#9098a8;text-transform:uppercase;letter-spacing:.03em;padding:7px 10px;border-bottom:1px solid #e4e7ec}
.vw-table th.vw-th-ergebnis{text-align:center}
.vw-table td{padding:8px 10px;border-bottom:1px solid #eef0f3;vertical-align:middle;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.vw-table tbody tr:last-child td{border-bottom:none}
.vw-table tbody tr:nth-child(even){background:#fafbfc}
.vw-table tbody tr:hover{background:#f0f4fa}
.vw-table td.vw-td-datum{color:#5a6070;font-size:.8rem}
.vw-table td.vw-td-zeit{color:#5a6070;font-size:.8rem}
.vw-table td.vw-td-ergebnis{text-align:center;font-weight:700;font-variant-numeric:tabular-nums;color:#1f2430}
.vw-table td.vw-td-heim{text-align:right}
.vw-table td.vw-td-gast{text-align:left}
.vw-table .team-logo-inline{height:20px;width:auto;max-width:26px;vertical-align:middle;object-fit:contain}
.vw-table td.vw-td-heim .team-logo-inline{margin-left:6px}
.vw-table td.vw-td-gast .team-logo-inline{margin-right:6px}
.vw-table tr.vw-empty td{color:#9098a8;font-style:italic;text-align:center;overflow:visible;white-space:normal}
</style>
<div class="vw-wrap">
  <div class="vw-header">
    <h2>Spiel&uuml;bersicht</h2>
    <p class="vw-range">Zeitraum: {VON} &ndash; {BIS}</p>
  </div>

  <!-- BEGIN LIGA -->
  <div class="vw-liga-block">
    <div class="vw-liga-name">{LIGA_NAME}</div>
    <table class="vw-table">
      <colgroup>
        <col class="vw-col-datum"><col class="vw-col-zeit"><col class="vw-col-heim"><col class="vw-col-erg"><col class="vw-col-gast">
      </colgroup>
      <thead>
        <tr>
          <th>Datum</th>
          <th>Zeit</th>
          <th class="vw-td-heim" style="text-align:right">Heim</th>
          <th class="vw-th-ergebnis">Erg.</th>
          <th class="vw-td-gast">Gast</th>
        </tr>
      </thead>
      <tbody>
        <!-- BEGIN SPIEL -->
        <tr>
          <td class="vw-td-datum">{DATUM}</td>
          <td class="vw-td-zeit">{ZEIT}</td>
          <td class="vw-td-heim">{HEIM}{HEIM_LOGO}</td>
          <td class="vw-td-ergebnis">{ERGEBNIS}{ZUSATZ}</td>
          <td class="vw-td-gast">{GAST_LOGO}{GAST}</td>
        </tr>
        <!-- END SPIEL -->
      </tbody>
    </table>
  </div>
  <!-- END LIGA -->

  {COPYRIGHT}
</div>
