<!--
  Partial: spieltag_picker.tpl.php | Fileversion: 1.1.0
  Dropdown zur Spieltag-/Rundenauswahl
-->
<div class="spieltag-picker">
  <label for="spieltag-select"><!--PickerLabel--></label>
  <select id="spieltag-select" onchange="location.href='liga.php?id=<!--LigaId-->&amp;view=<!--View-->&amp;nr='+this.value">
<!--Optionen-->
  </select>
</div>
