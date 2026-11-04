# Changelog: teamvergleich-Addon (LMOnext)

Teamvergleich (Direkter Vergleich, Head-to-Head): zeigt bei jeder Begegnung
ein anklickbares Vergleichs-Icon mit allen bisherigen direkten Duellen der
beiden Teams (ligaübergreifend, über alle Saisons hinweg), inkl. verknüpfter
Teams bei Umbenennung/Fusion/Abspaltung (siehe Team-Verknüpfungen in
Administrator → Teams, bleibt Core-Funktion, unabhängig von diesem Addon).

Stammt aus dem LMOnext-Core (vorher src/Liga/HeadToHeadTrait.php, direkt in
LigaService eingemischt) und wurde als eigenständiges Addon ausgegliedert
(Beitrag: Nutzerwunsch), damit es installationsweit über Administrator →
Einstellungen → Optionen → Anzeigen/Darstellung sichtbar aktiviert/
deaktiviert werden kann.

## Version 1.1.0

- Eigene Sprachdateien ergänzt (addon/teamvergleich/lang/de.php + en.php). Bei genauerer Prüfung waren 8 der zuvor als "geteilt" eingestuften Sprachschlüssel doch addon-exklusiv (keine echte Verwendung außerhalb dieses Addons bzw. außerhalb von PdfExporter::exportH2hPdf(), das ohnehin nur bei aktivem teamvergleich-Addon erreichbar ist): liga_col_spieltag_long, liga_h2h_icon_title, liga_h2h_modal_title, liga_h2h_wins, liga_h2h_draw, liga_h2h_no_matches, liga_h2h_close, h2h_today_prefix - aus lang/frontend/*.php verschoben (siehe dortiger Core-Changelog-Eintrag 1.49.0). hooks_frontend.php ruft jetzt beim Laden explizit addonManager()->loadLanguages('teamvergleich') auf.

## Version 1.0.0

- Erste Version als eigenständiges Addon. Logik übernommen aus
  src/Liga/HeadToHeadTrait.php (Fileversion 1.1.0, letzter Core-Stand) - aus
  einem Trait wurde eine eigenständige, statische Klasse
  (LMOnext\Addon\Teamvergleich\HeadToHead), da sie nicht mehr in LigaService
  eingemischt wird. Drei Methoden greifen auf public-static Methoden
  ANDERER Traits von LigaService zu (findTeamLogoPathFrontend, statusSuffix,
  roundDisplayName) - dafür jetzt volle Klassenreferenz
  \LMOnext\Liga\LigaService:: statt self::.
- Neue Datei hooks_frontend.php registriert vier Hooks (liga.h2h_matches,
  liga.h2h_payload, liga.compare_icon, liga.compare_modal_assets), an die
  der Core (frontend/data_liga.php UND die früher duplizierte
  frontend/data_liga_pretraits.php, jetzt identisch) delegiert.
- Neue Einstellung im Core sichtbar: Administrator → Einstellungen →
  Optionen → Anzeigen/Darstellung → "Teamvergleich für Besucher anzeigen?"
  (neues Feld "show_teamvergleich", gab es bisher NICHT - das Icon erschien
  bislang immer, ohne Ein-/Ausschalt-Möglichkeit). Erscheint nur, wenn
  dieses Addon aktiviert ist. Wird bewusst in hooks_frontend.php geprüft,
  nicht in HeadToHead.php selbst - die Kernlogik-Klasse bleibt so eine
  reine, wiederverwendbare Datenverarbeitung ohne Kenntnis von
  Ein-/Ausschalt-Einstellungen.
- Wichtige Trennung: "Addon aktiv" (isEnabled('teamvergleich')) entscheidet
  über die Erreichbarkeit des H2H-PDF-Exports (liga.php, h2h_pdf=1) und ob
  die Rohdaten überhaupt berechnet werden können - "show_teamvergleich"
  entscheidet NUR über die Sichtbarkeit von Icon/Modal für normale
  Besucher. Ist das Addon aktiv, aber show_teamvergleich=0, funktioniert
  ein bereits verlinkter/gespeicherter h2h_pdf-Link weiterhin (falls z.B.
  jemand ihn sich gemerkt hat) - nur das Icon zum Aufrufen fehlt in der
  normalen Ansicht.
- min_core_version: 1.9.2 (benötigt die Hook-Punkte liga.h2h_matches/
  liga.h2h_payload/liga.compare_icon/liga.compare_modal_assets in
  frontend/data_liga.php sowie die globalen Funktionsaufrufe in
  src/Liga/RenderViewsTrait.php).

**Für bestehende Installationen, die von einer älteren LMOnext-Version ohne
dieses Addon aktualisieren:** Nach dem Core-Update ist der Teamvergleich
zunächst NICHT mehr sichtbar (Vergleichs-Icon verschwindet aus allen
Ergebnis-/Spielplan-/KO-Ansichten), bis dieses Addon zusätzlich installiert
UND aktiviert wird. Team-Verknüpfungen (Umbenennung/Fusion/Abspaltung,
Administrator → Teams) bleiben davon unberührt - das ist weiterhin eine
Core-Funktion und bleibt unabhängig vom Addon-Status verfügbar/gespeichert.
