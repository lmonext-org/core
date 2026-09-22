<!--
  Partial: kalender_view.tpl.php | Fileversion: 1.0.0
  Rahmen um den Monatskalender
-->
<div class="card">
  <div class="cal-nav">
    <a href="liga.php?id=<!--LigaId-->&amp;view=kalender&amp;year=<!--PrevYear-->&amp;month=<!--PrevMonth-->">«</a>
    <span class="cal-monthyear"><!--MonthYear--></span>
    <a href="liga.php?id=<!--LigaId-->&amp;view=kalender&amp;year=<!--NextYear-->&amp;month=<!--NextMonth-->">»</a>
    <a class="cal-today-link" href="liga.php?id=<!--LigaId-->&amp;view=kalender&amp;year=<!--TodayYear-->&amp;month=<!--TodayMonth-->"><!--TodayLabel--></a>
  </div>
  <table class="cal-table">
    <thead>
      <tr>
<!--Weekdays-->
      </tr>
    </thead>
    <tbody>
<!--Wochen-->
    </tbody>
  </table>
</div>
