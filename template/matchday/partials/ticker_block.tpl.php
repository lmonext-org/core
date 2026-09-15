<!--
  Partial: ticker_block.tpl.php | Fileversion: 2.0.0
  Liga-Ticker: scrollendes Laufband (auf Wunsch - im alten LMO war der
  Ticker ein echtes Laufband, kein statischer Hinweis), oberhalb der Tabs
  auf jedem Reiter der Liga-Detailseite sichtbar. Nur gerendert, wenn
  aktiviert und Inhalt vorhanden (siehe RenderViewsTrait::renderTickerBlock()
  für Text- vs. Ergebnisticker-Variante).

  Der Text steckt ZWEIMAL hintereinander im scrollenden Track (".liga-
  ticker-track"), die CSS-Animation verschiebt ihn um exakt -50% - dadurch
  ist beim Erreichen von -50% die zweite Kopie exakt an der Stelle, an der
  die erste Kopie beim Start war, der Loop wirkt nahtlos ohne sichtbaren
  Sprung. Reine CSS-Animation, keine JavaScript-Abhängigkeit.
-->
<div class="liga-ticker">
  <span class="liga-ticker-icon">📢</span>
  <div class="liga-ticker-viewport">
    <div class="liga-ticker-track">
      <span class="liga-ticker-text"><!--Text--></span>
      <span class="liga-ticker-text" aria-hidden="true"><!--Text--></span>
    </div>
  </div>
</div>
