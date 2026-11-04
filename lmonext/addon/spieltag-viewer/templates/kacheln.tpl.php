<style>
.vw-wrap{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;color:#1f2430;max-width:960px;margin:0 auto}
.vw-header{text-align:center;margin:0 0 12px}
.vw-header h2{font-size:1.15rem;font-weight:700;margin:0 0 2px}

.vw-day-bar{display:flex;align-items:stretch;gap:4px;margin:0 0 16px}
.vw-arrow{display:flex;align-items:center;justify-content:center;border:1px solid #d4d7de;background:#f0f2f5;border-radius:6px;padding:0 14px;font-size:1rem;color:#5a6070;text-decoration:none;user-select:none;min-width:36px}
.vw-arrow:hover{background:#e4e7ec;color:#1f2430}
.vw-tiles{display:flex;gap:4px;flex:1}
.vw-tile{flex:1;min-width:0;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px 2px;border:2px solid #d4d7de;background:#fff;border-radius:8px;cursor:pointer;font-family:inherit;transition:all .15s;text-align:center}
.vw-tile:hover{border-color:#a7adb8;background:#f8f9fb}
.vw-tile-today{background:#dcfce7;border-color:#22c55e}
.vw-tile-selected{border-color:#3b82f6;box-shadow:0 0 0 1px #3b82f6}
.vw-tile-today.vw-tile-selected{background:#dcfce7;border-color:#3b82f6}
.vw-tile-day{font-size:.68rem;font-weight:600;color:#9098a8;text-transform:uppercase;letter-spacing:.04em}
.vw-tile-num{font-size:1.15rem;font-weight:700;color:#1f2430;line-height:1.2}
.vw-tile-month{font-size:.64rem;color:#9098a8}

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
  </div>

  <div class="vw-day-bar">
    <a href="{LEFT_URL}" class="vw-arrow">&#9664;</a>
    <div class="vw-tiles">{TILES}</div>
    <a href="{RIGHT_URL}" class="vw-arrow">&#9654;</a>
  </div>

  {DAY_BLOCKS}

  {COPYRIGHT}
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

<script>
(function(){
    var tilesBox = document.querySelector('.vw-tiles');
    if(!tilesBox) return;
    var dayDivs = document.querySelectorAll('.vw-day-content');

    tilesBox.addEventListener('click', function(e){
        var el = e.target;
        while(el && el !== tilesBox){
            if(el.classList && el.classList.contains('vw-tile')){
                var date = el.getAttribute('data-date');
                for(var i=0;i<dayDivs.length;i++){
                    dayDivs[i].style.display = (dayDivs[i].getAttribute('data-date')===date) ? '' : 'none';
                }
                var tiles = tilesBox.querySelectorAll('.vw-tile');
                for(var j=0;j<tiles.length;j++){
                    if(tiles[j].getAttribute('data-date')===date) tiles[j].classList.add('vw-tile-selected');
                    else tiles[j].classList.remove('vw-tile-selected');
                }
                return;
            }
            el = el.parentNode;
        }
    });
})();
</script>
