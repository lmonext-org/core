<!--
  Template: addon/mini | Filename: mininext.tpl.php | Fileversion: 1.2.1
  Reines Markup + Platzhalter, kein PHP.
-->
<style>
.lmo-mininext{display:inline-block;box-sizing:border-box;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;font-size:.8rem;
  background:#fff;border:1px solid #e3e7ee;border-radius:8px;overflow:hidden;max-width:300px}
.lmo-mininext .lmo-mn-head{background:#153A8C;color:#fff;font-weight:700;padding:6px 10px}
.lmo-mininext .lmo-mn-countdown{text-align:center;padding:6px 8px 0;color:#697182;font-size:.72rem}
.lmo-mininext .lmo-mn-date{text-align:center;padding:2px 8px 6px;color:#9098a8;font-size:.72rem}
.lmo-mininext .lmo-mn-teams{display:flex;align-items:flex-start;justify-content:center;gap:8px;padding:8px 10px}
.lmo-mininext .lmo-mn-team{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;
  text-align:center;font-weight:600;color:#1f2430;font-size:.74rem;min-width:0}
.lmo-mininext .lmo-mn-team img{height:26px;width:auto;object-fit:contain;flex-shrink:0}
.lmo-mininext .lmo-mn-score{font-weight:700;font-size:1.1rem;color:#153A8C;white-space:nowrap;padding:4px 4px 0;flex-shrink:0}
.lmo-mininext .lmo-mn-note{text-align:center;color:#9098a8;font-size:.7rem;padding:0 8px 6px}
.lmo-mininext .lmo-mn-sub{background:#f4f6fb;color:#697182;font-weight:700;padding:5px 10px;
  font-size:.72rem;text-transform:uppercase;letter-spacing:.02em}
.lmo-mininext .lmo-mn-tally{text-align:center;padding:6px 8px 2px;color:#1f2430;font-size:.78rem}
.lmo-mininext .lmo-mn-bar{display:flex;height:6px;margin:4px 10px 8px;border-radius:3px;overflow:hidden;background:#eef1f6}
.lmo-mininext .lmo-mn-bar-win{background:#22c55e}
.lmo-mininext .lmo-mn-bar-draw{background:#9098a8}
.lmo-mininext .lmo-mn-bar-lost{background:#ef4444}
.lmo-mininext ul{list-style:none;margin:0;padding:0 0 6px}
.lmo-mininext li{padding:3px 10px;font-size:.72rem;color:#4b5261;border-top:1px solid #f1f3f8;
  display:flex;justify-content:space-between;gap:6px}
.lmo-mininext li.win{color:#16a34a}
.lmo-mininext li.lost{color:#dc2626}
.lmo-mininext li.draw{color:#697182}
.lmo-mininext .lmo-mn-foot{font-size:.66rem;color:#9098a8;text-align:right;padding:4px 8px}
.lmo-mininext .lmo-mn-prev{border-top:1px solid #e3e7ee}
</style>
<div class="lmo-mininext">
  <div class="lmo-mn-head"><!--gameTxt--></div>
  <div class="lmo-mn-countdown"><!--countDown--></div>
  <div class="lmo-mn-date"><!--gameDate--> <!--gameTime--></div>
  <div class="lmo-mn-teams">
    <div class="lmo-mn-team"><!--imgHomeBig--><span><!--homeName--></span></div>
    <div class="lmo-mn-score"><!--homeTore--> : <!--guestTore--></div>
    <div class="lmo-mn-team"><!--imgGuestBig--><span><!--guestName--></span></div>
  </div>
  <div class="lmo-mn-note"><!--gameNote--></div>

  <div class="lmo-mn-sub"><!--matchesTxt--></div>
  <div class="lmo-mn-tally"><!--winCount--> <!--winTxt--> · <!--drawCount--> <!--drawTxt--> · <!--lostCount--> <!--lostTxt--></div>
  <div class="lmo-mn-bar">
    <div class="lmo-mn-bar-win" style="width:<!--winWidth-->px"></div>
    <div class="lmo-mn-bar-draw" style="width:<!--drawWidth-->px"></div>
    <div class="lmo-mn-bar-lost" style="width:<!--lostWidth-->px"></div>
  </div>
  <ul>
    <!-- BEGIN matches -->
    <li class="<!--class-->"><span><!--date--> <!--hTore-->:<!--gTore--> (<!--where-->)</span><span><!--matchingName--></span></li>
    <!-- END matches -->
  </ul>

  <!-- BEGIN previous -->
  <div class="lmo-mn-prev">
    <div class="lmo-mn-sub"><!--previous_gameTxt--></div>
    <div class="lmo-mn-date"><!--previous_gameDate--> <!--previous_gameTime--></div>
    <div class="lmo-mn-teams">
      <div class="lmo-mn-team"><!--previous_imgHomeSmall--><span><!--previous_homeName--></span></div>
      <div class="lmo-mn-score"><!--previous_hTore--> : <!--previous_gTore--></div>
      <div class="lmo-mn-team"><!--previous_imgGuestSmall--><span><!--previous_guestName--></span></div>
    </div>
  </div>
  <!-- END previous -->

  <div class="lmo-mn-foot"><!--ligaDatum--></div>
  <div class="lmo-mn-foot"><!--Copyright--></div>
</div>
