# Changelog LMOnext

## Hinweis: Addons als separate Pakete (ab 1.9.0-beta+addons)

Mit der Integration des Addon-Manager-Frameworks (Beitrag Torsten Hofmann, siehe src/Addon/AddonManager.php 1.0.0) wurden die bis dahin fest im Core mitgelieferten Addons **ewige** (ewige-tabelle), **mini** (mini-tabelle), **player**, **relegation**, **tabellenrechner**, **tipp** und **viewer** (spieltag-viewer) aus dem LMOnext-Kernpaket entfernt. Sie werden jetzt als eigenständige, self-contained addon.json-Pakete separat verteilt und vom Administrator bei Bedarf über Administrator → Addons (Upload/GitHub-Update) installiert und aktiviert/deaktiviert - statt fest verdrahtet und immer mitzuinstallieren. Ihre bisherige Entwicklungshistorie bleibt unten unter den jeweiligen addon/{name}/-Abschnitten als Referenz erhalten; neue Änderungen an diesen Addons werden ab sofort im Changelog des jeweiligen Addon-Pakets geführt.

## addon/addon-manager/ (handler_addons.php, view_addons.php, lang/de.php, lang/en.php, addon.json)

- Changelog: addon.json 1.7.0 / handler_addons.php 1.4.0 / view_addons.php 2.3.0 - Neues Formular "Addon von GitHub-URL installieren" im Einstellungen-Tab (neue Aktion "install_from_url", ruft AddonManager::installFromGithubUrl() 1.6.0 auf, siehe dortiger Changelog-Eintrag). Auch im Demo-Modus gesperrt (schreibt fremden Code, gleiches Risiko wie ZIP-Upload/GitHub-Update). Nebenbei behoben: Sprachschlüssel "addons_update_err_no_http_client" fehlte komplett, obwohl bereits im Fehler-Mapping von installUpdate() referenziert (vorbestehende Lücke, jetzt ergänzt).
- Changelog: addon.json 1.6.1 / handler_addons.php 1.3.1 - neuer Fehlercode "copy_failed" abgefangen (siehe src/Addon/AddonManager.php 1.5.1 für den Hintergrund - Selbst-Update-Bugfix).
- Changelog: addon.json 1.5.0 / handler_addons.php 1.3.0 / view_addons.php 2.2.0 - Neue Aktion "purge_data" (🗑️-Button, nur bei deaktivierten Addons mit deklarierten db_tables sichtbar) - ruft AddonManager::purgeData() 1.4.0 auf (siehe dortiger Changelog-Eintrag). Doppelte serverseitige Absicherung: Aktion nur erlaubt, wenn addonManager()->isEnabled() aktuell false liefert (unabhängig vom UI-Zustand), zusätzlich im Demo-Modus (DEMO_MODE) komplett gesperrt wie ZIP-Upload/GitHub-Update - unwiderrufliches Datenlöschen ist auf einer öffentlichen Demo-Instanz genauso riskant.
- Changelog: addon.json 1.2.0 / handler_addons.php 1.2.0 / view_addons.php 2.1.0 - Neuer Demo-Modus-Schalter: die optionale Konstante DEMO_MODE (config.php) sperrt bei Wert true den ZIP-Upload UND den Ein-Klick-Update-Weg über GitHub im Addon-Manager komplett (zentrale Prüfung in handler_addons.php, direkt nach der addon_action-Validierung). Aktivieren/Deaktivieren bereits installierter Addons bleibt uneingeschränkt möglich. UI zeigt statt des Upload-Formulars/Update-Buttons einen erklärenden Hinweis (🔒), statt die Sperre erst nach einem fehlgeschlagenen Klick sichtbar zu machen. Zweck: eine öffentlich zugängliche Demo-/Vorstellungs-Instanz kann so mit DERSELBEN Codebasis wie die produktive Installation betrieben werden, ohne Angriffsfläche für das Hochladen fremden Codes zu bieten - der Schalter lebt in config.php (individuell pro Installation, nicht Teil der verteilten ZIP), nicht im Code. install.php 2.7.0 ergänzt einen auskommentierten Hinweis auf diese Konstante in jeder neu generierten config.php.
- Changelog: addon.json 1.1.0 / handler_addons.php 1.1.0 - Sicherheitsüberarbeitung: Upload-Größenlimit von 50 MB auf 7 MB reduziert (war für ein reines PHP-/Sprach-/Template-Paket unrealistisch großzügig). Neue Fehlermeldungen für die in AddonManager.php 1.1.0 ergänzte Inhaltsprüfung (Zip-Slip, Dateityp-Whitelist, PHP-Lint, verdächtige Funktionsaufrufe) sowohl beim ZIP-Upload als auch beim GitHub-Update-Weg.
- Changelog: addon.json 1.0.1 - min_core_version von "1.4.0" auf "1.9.0" korrigiert (derselbe Copy-Paste-Rest wie bei den anderen 7 Addons, siehe deren jeweilige CHANGELOG.md-Einträge - "1.9.0" ist die tatsächliche LMOnext-Core-Version, ab der der Addon-Manager überhaupt existiert). Nur Metadaten-Korrektur, keine Verhaltensänderung.
- Changelog: 1.0.0 / 2.0.0 - Initiale Version (Beitrag Torsten Hofmann): der Addon-Manager verwaltet sich selbst als "Core-Addon" (type: admin) über dieselbe addon.json-Mechanik wie alle anderen Addons. view_addons.php (Fileversion 2.0.0) zeigt alle entdeckten Addons mit Status/Version/Typ/Autor, Aktivieren/Deaktivieren, GitHub-Update-Check (Token-Verwaltung, Diagnostics-Tab) und ZIP-Upload/-Installation. handler_addons.php verarbeitet die zugehörigen POST-Aktionen (enable/disable, Token speichern/löschen, Update-Check erzwingen, ZIP-Installation). Nav-Eintrag "Addons" (Position 90, siehe admin/data_loader.php 1.8.0).

## addon/mini/debugTeamCompare.php

- Changelog: 1.0.1 - Review-Korrektur (Dietmar Kersting): fehlender require_once für src/Liga/Eternal/EternalTableService.php ergänzt (gleicher Fehler wie in lmo-ewigetab.php 1.1.1 - Klasse war nicht auffindbar). Kein Produktivwerkzeug - reines Entwickler-Debug-Skript von Torsten Hofmann zur Verifikation der Ewige-Tabelle-Berechnung (fest auf "Borussia Dortmund" verdrahtet), nicht von irgendeiner Navigation aus verlinkt
- Changelog: 1.0.0 - Initiale Version (Torsten Hofmann)

## addon/mini/lmo-ewigetab.php

- Changelog: 1.1.1 - Review-Korrekturen (Dietmar Kersting): (1) kritisch - fehlender require_once für src/Liga/Eternal/EternalTableService.php ergänzt (Klasse war nicht auffindbar, jeder Aufruf endete in einem Fatal Error); (2) Standard-Sortierung von 'pkt3' auf 'pkt' (historische Original-Punkte) geändert - entspricht dem traditionellen "Ewige Tabelle"-Ansatz; (3) die im Docblock dokumentierte PHP-Variable $wertung für den include()-Modus wurde vom Code nie gelesen (nur $_GET) - jetzt konsistent mit den anderen drei Parametern ($_REQUEST, dann vorher gesetzte Variable, dann Standard); (4) $wertung-Dokumentationsbeispiel im Docblock korrigiert (fehlende Anführungszeichen) und als Steuerparameter ergänzt (fehlte komplett)
- Changelog: 1.1.0 - Neue Strafpunkte-Fußnoten in der Ewigen Tabelle (ewigeStrafHinweis()/ewigeStrafFootnotes(), Beitrag Torsten Hofmann, hier auf den Stil/die Sprachkeys der normalen Tabelle angepasst) - dabei eine doppelte HTML-Escaping in Torstens Original behoben (führte bei Sonderzeichen im Grund-Text zu doppelt kodierten Tooltips).
- Changelog: 1.0.0 - Initiale Version: Ewige Tabelle (aufsummierte Stände über mehrere Ligen) + Mehrjahres-Vergleich (Rang/Punkte je Saison) als Addon. Nutzt dieselbe Mechanik (Direktaufruf vs. include(), ProjektRoot-URL-Präfix, Template-System mit "<!-- BEGIN ... -->"-Blöcken). Die eigentliche Optik steckt in template/addon/ewige/{standard,matrix}.tpl.php Berechnung über LMOnext\Liga\Eternal\EternalTableService, der wiederum LigaService::computeStandings() je Liga aufruft – gleiche Punktwerte wie im normalen Ligabetrieb.

## addon/mini/lmo-mininext.php

- Changelog: 1.0.4 - Spenden-Link ergänzt (Beitrag Torsten Hofmann, hier auf die zentrale renderCopyrightNotice()-Funktion abgestimmt): neuer Platzhalter `<!--Copyright-->` im outer-Replacement-Array, siehe mininext.tpl.php 1.2.1 für die zugehörige Template-Position. Dieses Addon zeigte bisher als einziges keinen Copyright-/Spenden-Hinweis (alle anderen Embed-Addons - viewer, tabellenrechner, relegation, ewige, minitab - hatten ihn bereits).
- Changelog: 1.0.3 - Wie viewer 3.0.1: Frame-Schutz-Header wieder entfernt für die beabsichtigte iframe-Einbettung.
- Changelog: 1.0.2 - Bugfix: Team-Logos zeigten ins Leere, da renderTeamLogoImg() Pfade relativ zum Projekt-Root zurückgibt – korrekt für liga.php/home.php (die selbst im Projekt-Root liegen), aber falsch für dieses Addon (liegt unter addon/mini/, zwei Ebenen tiefer, siehe direkter URL-Aufruf in der Fehlermeldung des Nutzers). Neue Funktion miniProjectRootUrlPrefix() berechnet das korrekte URL-Präfix dynamisch über Document-Root-Abgleich (funktioniert bei Direktaufruf UND bei include() aus einer beliebig platzierten Wrapper-Datei), an allen 8 Logo-Stellen verwendet
- Changelog: 1.0.1 - Bugfix: <!--ligaDatum--> zeigte immer das heutige Tagesdatum statt des tatsächlichen letzten Speicherdatums der Liga. Liest jetzt liga.datum aus der DB, siehe lmo-minitab.php 1.1.0 für Details
- Changelog: 1.0.0 - Initiale Version: Portierung des alten LMO-Addons "Mininext" (siehe doc/help/addons/mininext.html + template/mini/mininext.tpl.php im alten LMO). Zeigt die nächste (oder, falls die Saison vorbei ist, letzte) Begegnung einer Mannschaft, das direkt vorangegangene Spiel, sowie eine Bilanz aller bisherigen Begegnungen der beiden Teams zum Einbinden auf externen Webseiten – entweder per include() oder direkt als URL/IFrame. Der alte "Archivordner"-Mechanismus (Durchsuchen alter .l98-Dateien nach früheren Begegnungen) entfällt komplett: LMOnext speichert alles in einer Datenbank, daher übernimmt getHeadToHeadMatches() aus data_liga.php (schon für den Teamvergleich in der normalen Besucheransicht verwendet) diese Aufgabe automatisch über ALLE Ligen hinweg, ganz ohne Ordnerkonfiguration.

## addon/mini/lmo-minitab.php

- Changelog: 1.2.4 - Spenden-Link ergänzt (Beitrag Torsten Hofmann, hier auf die zentrale renderCopyrightNotice()-Funktion abgestimmt): neuer Platzhalter `<!--Copyright-->` im outer-Replacement-Array, siehe standard.tpl.php 1.2.1 für die zugehörige Template-Position.
- Changelog: 1.2.3 - Wie viewer 3.0.1: Frame-Schutz-Header wieder entfernt für die beabsichtigte iframe-Einbettung.
- Changelog: 1.2.2 - $ligaId an computeStandings() übergeben, damit admin-seitig hinterlegte Strafpunkte/Straftore auch in diesem Mini-Widget korrekt berücksichtigt werden (siehe src/Liga/StandingsTrait.php 1.1.0)
- Changelog: 1.2.1 - Bugfix: Team-Logo-Bild und der "Zur Tabelle"-Link in der Kopfzeile zeigten ins Leere, da renderTeamLogoImg()/die "liga.php?id=..."-Verlinkung Pfade relativ zum Projekt-Root zurückgeben – korrekt für liga.php/home.php (die selbst im Projekt-Root liegen), aber falsch für dieses Addon (liegt unter addon/mini/, zwei Ebenen tiefer). Neue Funktion miniProjectRootUrlPrefix() berechnet das korrekte URL-Präfix dynamisch über Document-Root-Abgleich (funktioniert bei Direktaufruf UND bei include() aus einer beliebig platzierten Wrapper-Datei, nicht nur bei einer festen Verzeichnistiefe), Fallback auf einfache Heuristik falls nicht ermittelbar
- Changelog: 1.2.0 - Neuer Platzhalter "Logo" (Team-Logo, siehe Admin → Teams (global)), zwischen Tabellenplatz und Teamname positioniert (siehe standard.tpl.php 1.2.0). Immer angezeigt (unabhängig von der liga-eigenen "Logo anzeigen"-Einstellung, da dieses Widget als eigenständiges, extern eingebundenes Element davon unabhängig sein soll)
- Changelog: 1.1.0 - Bugfix: <!--ligaDatum--> zeigte immer das heutige Tagesdatum statt des tatsächlichen letzten Speicherdatums der Liga (im alten LMO das Änderungsdatum der .l98-Datei). Liest jetzt liga.datum aus der DB, das save_ergebnisse (siehe handler_liga.php 1.6.2) bei jeder Ergebnis-Speicherung aktualisiert
- Changelog: 1.0.0 - Initiale Version: Portierung des alten LMO-Addons "Minitabellen" (siehe doc/help/addons/minitabellen.html + template/mini/standard.tpl.php im alten LMO). Zeigt einen Ausschnitt der Tabelle einer Liga (z.B. "3 Plätze über und 2 unter der Lieblingsmannschaft") zum Einbinden auf externen Webseiten, entweder per include() oder direkt als URL/IFrame. Nutzt bewusst dieselben Berechnungsfunktionen wie die normale Tabellenansicht (computeStandings()/computeStandingsMarkerColor() aus data_liga.php), damit Platzierungen und Randmarkierungen immer exakt übereinstimmen.

## addon/player/frontend_spielerstat.php

- Changelog: 1.1.0 - Datei nach addon/player/ verschoben (einheitliche Addon-Ordnerstruktur, neben addon/mini/). Neu: Spaltenüberschriften-Grafiken (siehe findSpielerstatColumnImage(), Ordner assets/addon/player/) und Spielerfotos (siehe findPlayerPhotoPath(), Ordner assets/img/player/) werden jetzt angezeigt, sofern hinterlegt
- Changelog: 1.0.1 - Bugfix: Kopfzellen von Text-Spalten (z.B. "Name") bekamen fälschlich die rechtsbündige st-num-Klasse wie Zahlenspalten
- Changelog: 1.0.0 - Initiale Version: Besucher-Ansicht für das neue Spielerstatistik-Addon (siehe admin/spielerstat_lib.php für Schema/CRUD). Rewrite von addon/spieler/lmo-statshow.php (altes LMO-Addon) auf DB-Basis. Sortierbare Spalten, Pagination, optionaler Vereinsfilter (siehe Konfiguration "Vereinsweise anzeigen" in der Admin-Verwaltung).

## addon/player/handler_spielerstat.php

- Changelog: 1.2.0 - spst_import blockiert jetzt serverseitig, sobald für die Liga bereits mindestens eine Spalte existiert (nicht nur die Oberfläche versteckt das Formular, siehe view_spielerstatistik.php 1.2.0) – schützt auch bei direktem Aufruf der Aktion
- Changelog: 1.1.0 - Datei nach addon/player/ verschoben. Neuer Handler "spst_upload_photo" (Spielerfoto hochladen/entfernen, siehe savePlayerPhotoUpload() in spielerstat_lib.php)
- Changelog: 1.0.0 - Initiale Version: POST-Handler für das neue Spielerstatistik-Addon (CRUD für Spalten/Spieler/Werte/Konfiguration, siehe admin/spielerstat_lib.php) sowie der Import-Flow für alte .stat/.cfg- Dateipaare (siehe admin/spielerstat_import.php), inkl. Team-Abgleich- Review-Schritt analog zum bestehenden .l98-Liga-Import (view_import_review.php / import_confirm).

## addon/player/spielerstat_import.php

- Changelog: 1.1.0 - Datei nach addon/player/ verschoben. Importierte Spieler bekommen jetzt eine persistente globale Identität (findOrCreateGlobalPlayer(), verknüpft über den Namen der ersten Spalte) statt nur als Text in dieser einen Liga zu existieren
- Changelog: 1.0.0 - Initiale Version: Import alter Spielerstatistik-Dateien (.stat + .cfg aus dem Original-LMO-Addon addon/spieler/). Erkennt automatisch das verwendete Trennzeichen (§ aus der ältesten LMO-Version, | aus einer mittleren Version, # aus der aktuellsten Version – analog zu lmo_import.php, das dieselbe Unterscheidung bereits für Ligadateien trifft). Die Vereinsspalte wird beim Import per Fuzzy-Matching (findFuzzyTeamMatches() aus handler_import_export.php) gegen teams_global abgeglichen, mit demselben Review-Schritt wie beim .l98-Liga-Import bei mehrdeutigen/ungefähren Treffern.

## addon/player/spielerstat_lib.php

- Changelog: 1.1.0 - Datei nach addon/player/ verschoben (einheitliche Addon-Ordnerstruktur, neben addon/mini/). Neu: persistente Spieler-Entität spieler_global (eigene ID, saison-/vereinsübergreifend, siehe ensureSpielerGlobalSchema()/ findOrCreateGlobalPlayer()) statt Namen nur als Zellwert; Spielerfoto- Upload analog zu Team-Logos (findPlayerPhotoPath()/savePlayerPhotoUpload()/ deletePlayerPhoto(), Ordner assets/img/player/); Spaltenüberschriften- Grafiken (findSpielerstatColumnImage(), Ordner assets/addon/player/) als Pendant zum alten LMO-Verhalten (Bild statt Textüberschrift, wenn eine gleichnamige Grafik vorliegt)
- Changelog: 1.0.0 - Initiale Version: DB-Schema (ensureSpielerstatSchema()), sicherer Formel-Parser (kein eval() – siehe evaluateSpielerstatFormula()) mit +,-,*,/,(),MIN,MAX,ROUND, sowie CRUD-Helfer für Spalten/Spieler/Werte und die Liga-Konfiguration. Rewrite des alten "Spielerstatistik"-Addons (addon/spieler/lmo-stat*.php im Original-LMO), das Flatfiles (.stat/.cfg) mit einer eigenen eval()-basierten Formel-Auswertung nutzte. Diese Neufassung schreibt stattdessen direkt in die Datenbank (siehe spielerstat_spalten/spielerstat_spieler/spielerstat_werte/ spielerstat_config) und ersetzt eval() durch einen eigenen, Sandbox-sicheren Tokenizer/Parser/Evaluator. Anders als im Original wird die Rolle einer Spalte ("Verein"/"Spielerlink") nicht mehr per (übersetzungsabhängigem) Namensvergleich erkannt, sondern über ein explizites `rolle`-Feld je Spalte – robuster und sprachunabhängig.

## addon/player/view_spielerstatistik.php

- Changelog: 1.3.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.3.0 - Spalten mit dem Namen "Team"/"Mannschaft"/"Verein" (unabhängig von Groß-/Kleinschreibung) bekommen beim Werte-Eintragen jetzt ein Dropdown mit den aktuellen Teams der Liga statt eines Freitextfeldes (siehe data_loader.php 1.7.1 für die Team-Liste). Erleichtert die Zuordnung und vermeidet Tippfehler; der gespeicherte Wert bleibt einfacher Text, kein Fremdschlüssel. Ein bestehender Wert, der zu keinem aktuellen Team passt (z.B. nach Umbenennung), wird als zusätzliche, vorausgewählte Option angezeigt statt beim Speichern unbemerkt verloren zu gehen
- Changelog: 1.2.0 - Import-Karte für alte .stat/.cfg-Dateien wird jetzt komplett ausgeblendet, sobald mindestens eine Spalte manuell angelegt wurde (statt nur eine Warnung anzuzeigen, wenn schon Spieler existieren) – der Import ist nur für eine noch komplett unkonfigurierte Spielerstatistik gedacht, siehe auch die serverseitige Absicherung in handler_spielerstat.php 1.2.0
- Changelog: 1.1.0 - Datei nach addon/player/ verschoben. Foto-Upload/-Entfernen je Spielerzeile ergänzt (globale Spieler-ID, siehe savePlayerPhotoUpload()), Hinweis auf Spaltenüberschriften-Grafiken (assets/addon/player/) ergänzt
- Changelog: 1.0.0 - Initiale Version: Admin-Oberfläche für das neue Spielerstatistik-Addon. Rewrite von addon/spieler/lmo-statadmin.php (altes LMO-Addon) auf das LMOnext-Datenmodell (siehe admin/spielerstat_lib.php) – DB-gestützt statt Flatfile, eigener Formel-Parser statt eval(). Kein separates Hilfsadmin-Rechtemodell (LMOnext kennt aktuell nur eine Adminrolle).

## addon/player/view_spst_import_review.php

- Changelog: 1.0.2 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.0.1 - Datei nach addon/player/ verschoben (einheitliche Addon-Ordnerstruktur)
- Changelog: 1.0.0 - Initiale Version: Team-Abgleichsseite für den Spielerstatistik-Import, strukturell analog zu view_import_review.php (.l98-Ligaimport), aber pro Zeilenindex der importierten .stat-Datei statt pro Team-Nr.

## addon/tipp/frontend_tipp.php

- Changelog: 0.10.0 - tippGetTipperStatistik() um "ergebnisse_pro_spieltag" erweitert (vierte Ausbaustufe): Verlauf der tatsächlichen Liga-Ergebnisse nach Tendenz, je Spieltag.
- Changelog: 0.9.0 - tippGetTipperStatistik() um 4 weitere Datensätze erweitert (dritte Ausbaustufe): häufigste getippte Ergebnisse je Tendenz, häufigste tatsächliche Ergebnisse je Tendenz, Spieltagsplatzierung (Rang nur an diesem Spieltag, nicht kumuliert), Vergleich der eigenen Punkte mit dem jeweiligen Spieltagssieger.
- Changelog: 0.8.0 - tippGetTipperStatistik() um 4 weitere Datensätze erweitert (zweite Ausbaustufe): Verteilung der echten Liga-Ergebnisse nach Tendenz, eigene Tipp-Tendenz je Spieltag, Punkte je Spieltag, Punkte-Rückstand zum jeweiligen Spieltagsführenden.
- Changelog: 0.7.0 - Neue Funktion tippGetTipperStatistik() (Vorbild kicktipp.de "Statistik", erste Ausbaustufe): Tipp-/Punkte-Verteilung nach Tendenz, Rang-Verlauf über die Saison und Punkte je Mannschaft für einen Tipper. Der Rang-Verlauf baut auf tippGetSpieltagspunkteUebersicht() auf, keine zusätzliche Abfrage nötig.
- Changelog: 0.6.0 - Neue Funktion tippGetSpieltagspunkteUebersicht() (Vorbild kicktipp.de "Gesamtübersicht"): liefert pro Tipper die je Spieltag erzielten Punkte über die ganze Saison sowie die Gesamtsumme, inkl. Markierung des jeweiligen Spieltagssiegers.
- Changelog: 0.5.0 - Neue Funktion tippGetSpieltagMatrix() (Vorbild kicktipp.de): liefert pro Spieltag die kumulierten Gesamtpunkte, die Rangveränderung zum vorherigen Spieltag und die einzelnen Tipps aller Tipper für die Matrix-Ansicht der Tippeinsicht (siehe view_tippspiel_frontend.php).
- Changelog: 0.4.1 - Bestätigungs-Mail-Link und tippSiteBaseUrl()-Fallback von der entfernten tipp.php auf home.php?view=tippspiel umgestellt (siehe view_tippspiel_ frontend.php 1.0.0)
- Changelog: 0.4.0 - redirectTo() zeigt jetzt auf home.php?view=tippspiel statt auf die entfernte eigenständige tipp.php - Tippspiel läuft jetzt als View innerhalb des Templates, analog zur Spielerstatistik (siehe addon/tipp/view_tippspiel_frontend.php, home.php). Alle bestehenden redirectTo()-Aufrufstellen unverändert, nur die Basis-URL hat sich geändert
- Changelog: 0.3.0 - Neue Funktion tippGetRangliste(): globale Rangliste über alle jemals getippten (und ausgewerteten) Spiele, live berechnet aus den Rohdaten. Wendet die drei in "Was zählt bei Punktgleichheit" konfigurierten Tie-Break-Kriterien an (alle acht bestätigten Original-Optionen implementiert, inkl. Trefferquote und geteilter Spieltagswertungen). Deckt nur den Ergebnis-Tippmodus ab - Tendenz-Modus-Tipps werden bewusst übersprungen statt fälschlich mit 0 Punkten gezählt (siehe Funktions- Docblock), da die Tendenz-Punkteberechnung noch aussteht
- Changelog: 0.2.0 - Tippeinsicht: tippGetEinsichtDaten() liefert alle Tipps aller Tipper für eine Spiel-Liste, respektiert dabei je Partie einzeln den eingestellten Veröffentlichungszeitpunkt (sofort/nach Abgabeschluss/nach Ergebnis)
- Changelog: 0.1.0 - Initiale (vorläufige) Version der Tipper-Ansicht: Session-Verwaltung, Anmeldung, Login/Logout, Tippabgabe (nur Ligenweise-Modus, nur Ergebnis-Tippmodus vollständig getestet) und eine einfache Live- Punkteberechnung fürs Anzeigen der erzielten Punkte je Spiel. Bewusst KOMPLETT neu geschrieben statt vom alten Flatfile-Addon übernommen (siehe Projekt-Historie: genau die verstreute, typunsichere Berechnung dort war die Hauptfehlerquelle) - eine einzige zentrale Funktion (calculateTippPunkte()) für die gesamte Punktelogik, durchgehend strict_types und ===-Vergleiche. Noch NICHT enthalten (folgt in weiteren Schritten): Datumsweise Tippabgabe, Tendenz-Tippmodus, Tippeinsicht, Tipp-Tabelle/Rangliste, Team-Beitritt/-Gründung durch den Tipper selbst, Passwort-Vergessen, E-Mail-Bestätigungscode-Freischaltung (nur "sofort" und "admin" fertig getestet).

## addon/tipp/handler_tipp.php

- Changelog: 1.2.0 - save_tipp_anzeige speichert jetzt zusätzlich die neue anzeige_spielregeln-Einstellung.
- Changelog: 1.1.0 - save_tipp_nachtragen prüft jetzt zusätzlich serverseitig, dass die übergebene Liga tatsächlich vom Tipper abonniert ist, bevor Tipps gespeichert werden.
- Changelog: 1.0.0 - Neuer Handler save_tipp_nachtragen für das "Tipps nachtragen"-Feature.
- Changelog: 0.9.0 - Neue Speicherfunktion save_tipp_anzeige für den Reiter "Anzeigen/Darstellung".
- Changelog: 0.8.0 - Neue Aktion send_tipp_mail: verschickt echte Mails für alle drei Versandarten, inkl. Tipper-Bereich/"an alle" und echter [spiele]- Ermittlung beim Reminder (überspringt Tipper ohne offene Tipps)
- Changelog: 0.7.1 - Bugfix: beim nachträglichen Eintragen der neu erzeugten Team-ID wurde der Array-Operator "+" statt array_merge() genutzt - da "team_id" in $data bereits (mit null) existierte, gewann bei "+" immer die linke Seite, wodurch neu gegründete Teams nie am Tipper gespeichert wurden
- Changelog: 0.7.0 - Neue Aktionen save_tipp_user (Anlegen/Bearbeiten inkl. Team-Auflösung) und delete_tipp_user für die Userverwaltung
- Changelog: 0.6.0 - Neue Speicher-Aktion save_tipp_ligen für "Tippbare Ligen"
- Changelog: 0.5.0 - Neue Speicher-Aktion save_tipp_punktgleichheit für die drei Kriterien
- Changelog: 0.4.1 - Bugfix: Validierungs-Wertelisten für abgabeschluss_ohne_termin und max_spieltage_voraus korrigiert (siehe view_tippspiel.php 0.6.1)
- Changelog: 0.4.0 - Neue Speicher-Aktion save_tipp_anmeldung für den Tab "Anmeldung"
- Changelog: 0.3.0 - Neue Speicher-Aktion save_tipp_abgabe für den Tab "Tippabgabe", inkl. serverseitiger Prüfung, dass mindestens eine Abgabe-Variante aktiv bleibt
- Changelog: 0.2.0 - Neue Speicher-Aktion save_tipp_regeln für den Tab "Regeltechnisches"
- Changelog: 0.1.0 - Initiale Version: bindet tipp_lib.php ein, erste Speicher-Aktion für den Tab "Punkteverteilung" (save_tipp_punkte)

## addon/tipp/tipp_lib.php

- Changelog: 1.1.0 - tippRenderSiteLink() zeigt jetzt das neue Tippspiel-Icon vor dem Linktext (statt eines Emoji, das dafür aus den Sprachdateien entfernt wurde).
- Changelog: 1.0.0 - Neue Funktion tippGetLetzterAusgewerteterSpieltag(): höchste Spieltag-Nummer einer Liga mit mindestens einem echten Ergebnis - für den Sprung zum zuletzt ausgewerteten Spieltag beim Klick auf einen Tippernamen in der Tippeinsicht.
- Changelog: 0.9.0 - Neue Funktion getArchivierteLigenMitTipps(): findet archivierte Ligen, für die tatsächlich Tipps abgegeben wurden - Grundlage für den neuen "Archivierte Tippsaisons"-Link unter Tippeinsicht/Gesamtübersicht.
- Changelog: 0.8.0 - Neue Funktionen für "Tipps nachtragen": getTipperById(), adminTippGetSpieltage()/adminTippGetSpieltagPartien() (eigenständige, schlanke Kopien der Liga-Funktionen - der Adminbereich bindet frontend/data_liga.php bewusst nicht ein, ein require_once davon würde "Cannot redeclare getDB()" auslösen) sowie adminTippNachtragen() (speichert unabhängig von der sonst geltenden Abgabefrist, live geprüft mit einer Partie aus der Vergangenheit - der reguläre Frontend-Weg lehnt dieselbe Eingabe weiterhin korrekt ab).
- Changelog: 0.7.0 - Bugfix: die Einstellung "Tippeinsicht öffentlich" (Anzeigen/Darstellung) hatte keinen erreichbaren Einstiegspunkt für nicht angemeldete Besucher - der allgemeine Header-Link und die Startseiten-Karte zeigten weiterhin auf den Standard-Einstieg (?action=abgabe), der Login voraussetzt und ohne erkennbaren Ausweg dorthin führte. Neue Funktion tippZielUrlOhneLogin() verlinkt für nicht eingeloggte Besucher jetzt direkt auf die Tippeinsicht, wenn die Einstellung aktiv ist - für eingeloggte Tipper und bei deaktivierter Einstellung bleibt das Ziel unverändert.
- Changelog: 0.6.1 - tippRequestPasswordReset() sucht jetzt wahlweise über Nickname ODER Email (statt nur Email) und liefert bei Nichtfund eine konkrete Rückmeldung (welches der beiden Felder nichts fand) statt der bisherigen neutralen "falls diese Email existiert..."-Meldung - bewusste Abkehr vom Standard-Security-Pattern auf expliziten Wunsch für diese Testinstallation. Signatur geändert: (string $nickname, string $email), Rückgabe jetzt array{ok,reason} statt bool
- Changelog: 0.6.0 - Self-Service-Kontobearbeitung für Tipper: tippUpdateOwnAccount() (Nickname und "freigeschaltet" bleiben admin-exklusiv unveränderbar), tippRequestPasswordReset()/tippResetPassword() ("Passwort vergessen", Reset-Code 1h gültig, Einmal-Nutzung). Neue DB-Spalten reset_code/ reset_code_expires per Migration (SHOW COLUMNS/ALTER TABLE, analog zu admin/bootstrap.php) ergänzt
- Changelog: 0.5.1 - Nav-Link/Startseiten-Karte zeigen jetzt auf home.php?view=tippspiel statt auf die entfernte eigenständige addon/tipp/tipp.php - Tippspiel läuft jetzt als View innerhalb des Templates, analog zur Spielerstatistik (siehe view_tippspiel_frontend.php). Nebenbei: falsche CSS-Klasse "btn" in der Startseiten-Karte korrigiert (nur "btn-primary" existiert)
- Changelog: 0.5.0 - Neue Funktionen tippIstAktiv() (mind. eine Liga fürs Tippspiel freigegeben?), tippRenderSiteLink() (Header-/Footer-Link, je nach Template - siehe layout.tpl.php) und tippRenderHomeCard() (Werbe-Karte auf der Startseite, siehe home.tpl.php). Behebt eine echte Lücke: das Tippspiel war bislang nirgends von der Besucherseite aus verlinkt, nur per direkter URL erreichbar
- Changelog: 0.4.1 - Bugfix: getAllTippSettings() cachte die Einstellungen statisch pro Request, ohne dass setTippSetting()/setTippSettings() diesen Cache invalidierten - ein erneuter getTippSetting()-Aufruf im selben Request (z.B. bei einer Live-Neuberechnung ohne zwischenzeitlichen Redirect) lieferte dadurch stille alte Werte. Cache liegt jetzt per Referenz in tippSettingsCacheRef() und wird von beiden Setter-Funktionen über die neue resetTippSettingsCache() gezielt geleert. Admin-Options-Tabs waren nicht betroffen (Post/Redirect/Get-Muster in handler_tipp.php lädt den Cache ohnehin bei jedem Request neu), gefunden beim Testen von calculateTippPunkte() in frontend_tipp.php
- Changelog: 0.4.0 - Mail-Versand für "Newsletter/Reminder": sendTippMail() (exakt nach dem Muster von sendPasswordResetEmail() in admin/bootstrap.php, bewusst ohne externe Mail-Bibliothek), replaceTippPlaceholders() ([nick]/[name]/ [spiele], bewusst kein [pass]), getTippReminderSpiele()/ formatSpieleListe() für die echte Ermittlung noch nicht getippter Spiele je Tipper im gewählten Zeitfenster
- Changelog: 0.3.0 - Vollständiges Tipper/Team-CRUD für die Userverwaltung: getAllTipper() (mit live abgeleitetem "letzter Tipp" per MAX(updated_at)), getTipperByNickname(), getAllTeamsWithCount() (live per COUNT(*)), createTippTeam(), saveTipper() (Anlegen/Bearbeiten, Passwort nur bei Angabe überschrieben), deleteTipper(), getTipperAboLigaIds()/ setTipperAbos()
- Changelog: 0.2.0 - Neue Funktionen für "Tippbare Ligen": getTippbareLigenKandidaten() (Ligen aus dem obersten Ordner, nicht archiviert - eigene, einfache Abfrage statt frontend/data_home.php einzubinden), getTippLigaFreigabeIds()/ setTippLigaFreigabe() für tipp_liga_freigabe
- Changelog: 0.1.0 - Initiale Version: Datenbankschema für alle sechs in den Vorgesprächen festgelegten Tabellen (tipp_user, tipp_team, tipp_liga_freigabe, tipp_abo, tipp_tipp, tipp_settings), plus Zugriffsfunktionen für die Einstellungen (getTippSetting()/setTippSetting()/getAllTippSettings()). tipp_tipp bekommt sowohl Ergebnis- (tipp_heim/tipp_gast) als auch Tendenz-Felder (tipp_tendenz) nebeneinander, je nach aktivem Tippmodus wird nur eines der beiden befüllt - siehe Projekt-Historie für die Begründung (eigenes Feld statt codierter Platzhalterwerte)

## addon/tipp/view_tippspiel.php

- Changelog: 1.4.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.4.0 - Neue Einstellung "Spielregeln-Link anzeigen" im Tab Anzeigen/Darstellung - steuert, ob der neue Spielregeln-Reiter im Frontend erscheint (Standard: aus).
- Changelog: 1.3.0 - "Tipps nachtragen": die Liga-Auswahl zeigt jetzt nur noch die Ligen, die der jeweilige Tipper tatsächlich abonniert hat (statt aller tippbaren Ligen). Manipulierte/veraltete URLs mit einer nicht abonnierten Liga fallen serverseitig auf die erste tatsächlich abonnierte Liga zurück. Neue Hinweismeldung, falls der Tipper noch gar keine Liga abonniert hat.
- Changelog: 1.2.0 - Neues Feature "Tipps nachtragen" im Tab Userverwaltung (historisches Feature aus dem alten LMO): der Admin kann Tipps für einen Tipper eintragen, unabhängig von der sonst geltenden Abgabefrist, auch nach Anpfiff/Abpfiff. Gedacht für Tipper, die ihren Tipp rechtzeitig vor Anpfiff per Mail geschickt haben, statt selbst über die Website zu tippen.
- Changelog: 1.1.0 - Neuer Reiter "Anzeigen/Darstellung" mit der Einstellung "Tippeinsicht öffentlich".
- Changelog: 1.0.1 - Bugfix: Die Checkboxen "freigeschaltet"/"Newsletter"/"Tipp-Reminder" im Bearbeiten-Formular zeigten den gespeicherten Zustand nie an (immer unchecked), obwohl der Wert in der DB korrekt 1 war - Ursache: getDB() nutzt PDO::ATTR_EMULATE_PREPARES=false, wodurch TINYINT-Spalten als natives PHP int statt als String zurückkommen. Der bisherige strikte Vergleich mit '1' (String) via === war dadurch immer false. Jetzt (int)-Cast vor dem Vergleich mit int 1
- Changelog: 1.0.0 - Tab "Newsletter/Reminder" vollständig umgesetzt: drei Versandarten (Newsletter an Alle/Persönliche Email/Tipp-Reminder mit Liga+Spieltag- Auswahl je tippbarer Liga), Tipper-Bereich von-bis PLUS die neue "an alle Tipper"-Kurzoption, Vorlagen-Umschaltung per JS beim Wechsel der Versandart
- Changelog: 0.9.1 - Bugfix: $tdR/$tdL/$selSt/$inpSt waren nur innerhalb des "Optionen"-Tabs definiert und fehlten im "Userverwaltung"-Tab (führte zu PHP-Warnungen + kaputtem HTML) - an eine gemeinsame Stelle vor der Tab-Weiche verschoben
- Changelog: 0.9.0 - Tab "Userverwaltung" vollständig umgesetzt: sortierbare Liste (Nickname als mailto-Link, Realname, Team, letzter Tipp, Editieren), sowie das Bearbeiten-/Neuanlegen-Formular mit allen Original-Feldern inkl. der drei Team-Radio-Optionen (keinem/bestehendes Team mit [Mitgliederzahl]/ neues Team gründen) und abonnierten Ligen
- Changelog: 0.8.0 - Letzter der sechs Optionen-Bereiche fertig: "Tippbare Ligen" mit "immer alle"-Checkbox (deaktiviert bei Aktivierung die Einzelauswahl, genau wie im Original) und gezielter Einzelauswahl aus dem obersten Ordner der Ligenübersicht (keine archivierten Ligen)
- Changelog: 0.7.0 - "Was zählt bei Punktgleichheit" vollständig umgesetzt: drei Kriterien-Dropdowns mit den acht aus Screenshots UND Original-Quellcode doppelt bestätigten Werten (kein Kriterium/höhere Quote/höhere Anzahl Spiele getippt/Anzahl richtiger Ergebnistipps/Anzahl richtiger Tendenz- u. Tordiff.tipps/Anzahl richtiger Tendenztipps/durch Joker dazugewonnene Punkte/Gewonnene Spieltagswertungen)
- Changelog: 0.6.1 - Bugfix: Zwei Dropdown-Wertelisten in "Regeltechnisches" waren nur Annahmen ("Tippabgabeschluss ohne Anstoßtermin", "max. Spieltage im Voraus") (lmo-admintippoptions.php) auf die tatsächlich korrekten Werte korrigiert: "Standard-Anstoßzeit"/"Erstes Spieltagsdatum 0 Uhr" (statt der erfundenen Option "kein Abgabeschluss"), sowie 0/1/2/3/4/5/unbegrenzt (statt 1/2/3/5/10/unbegrenzt). Annahme-Warnhinweise entfernt, da jetzt bestätigt korrekt
- Changelog: 0.6.0 - "Anmeldung" vollständig umgesetzt: Adresse/Realname optional abfragbar, drei Freischaltungsarten (sofort/E-Mail/Admin), Admin-Benachrichtigung + Bestätigungsmail an Tipper - alles exakt nach den Vorgesprächen
- Changelog: 0.5.0 - Neuer sechster Optionen-Bereich "Tippabgabe": Ligenweise/Datumsweise Tippabgabe (beide gemäß Original-Hilfedokumentation möglich, mind. eine muss aktiv bleiben - client- UND serverseitig geprüft), plus die Anzeige-Details (Pfeile, Tendenzen anderer, Durchschnittstipps, automatische Tippeinsicht-Aktualisierung). Bewusst nur die Admin-Schalter - die eigentliche Tipper-Ansicht folgt in einem eigenen Abschnitt
- Changelog: 0.4.0 - "Regeltechnisches" vollständig umgesetzt: Tippabgabefrist, Team- Höchstgröße, Joker an/aus + Multiplikator, max. Spieltage im Voraus, plus die neue Warnung-Einstellung (Stunden vor Fristende) aus den Vorgesprächen. Zwei Dropdown-Wertelisten (Abgabeschluss ohne Termin, max. Spieltage im Voraus) waren nie im Detail besprochen worden - mit sinnvollen Annahmen befüllt und im UI selbst als Annahme gekennzeichnet
- Changelog: 0.3.0 - Tab "Optionen" bekommt eine Unter-Navigation für die fünf besprochenen Bereiche (Punkteverteilung/Regeltechnisches/Anmeldung/Was zählt bei Punktgleichheit/Tippbare Ligen). "Punkteverteilung" ist jetzt vollständig funktionsfähig: alle vier Basiswerte + Sonderregeln aus den Vorgesprächen, inkl. Tippmodus-Auswahl (Ergebnis/Tendenz) ganz oben - im Tendenz-Modus werden die torzahl-abhängigen Felder per JS ausgegraut/deaktiviert (nicht nur versteckt), analog zum Verhalten im Original-Screenshot. Speichert über die neue Aktion save_tipp_punkte (siehe handler_tipp.php)
- Changelog: 0.2.0 - Die vier Karteikarten des alten LMO-Tippspiels nachgebaut (Auswertung / Newsletter-Reminder / Userverwaltung / Optionen), als Tab-Navigation nach demselben Muster wie admin/view_liga_settings.php. Jeder Tab zeigt aktuell noch einen eigenen Platzhalter - die tatsächlichen Inhalte (Rangliste, Mailversand, Tipper-Verwaltung, die zahlreichen in den Vorgesprächen festgelegten Einstellungen) folgen in kommenden Sitzungen, ein Tab nach dem anderen.
- Changelog: 0.1.0 - Initiale Version: reiner Platzhalter, damit der neue Navigationspunkt "Tippspiel" nicht ins Leere führt.

## addon/tipp/view_tippspiel_frontend.php

- Changelog: 1.20.2 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.20.1 - Bugfix: die neue Tippspiel-Bereichsüberschrift aus 1.20.0 erzeugte eine Dopplung mit der bereits bestehenden Seiten-Überschrift (kommt separat über home.php/renderTemplate() als "Titel"). Wieder entfernt - das Logo wird stattdessen direkt im Template vor die bestehende Überschrift gesetzt (siehe tippspiel.tpl.php).
- Changelog: 1.20.0 - Neue "Tippspiel"-Bereichsüberschrift mit vorangestelltem Logo, wird jetzt über der Tab-Leiste auf jeder Tippspiel-Unterseite angezeigt (renderTippspielTabsBar()). Live getestet: Überschrift samt Logo erscheint korrekt im HTML.
- Changelog: 1.19.1 - Besser unterscheidbare Farben im "Vergleich mit dem Spieltagssieger"-Diagramm: Blau (eigener Verlauf) und gestricheltes Rot (Spieltagssieger) statt der schwer zu unterscheidenden Grün-/Orangetöne mit überlappender Füllfläche.
- Changelog: 1.19.0 - Bugfix: "Vergleich mit dem Spieltagssieger" (Statistik-Seite 3) zeigte verzerrte/verschobene Daten - $vsMich/$vsSieger wurden per array_map() aus einem bei 1 (nicht 0) beginnenden Array gebaut, wodurch json_encode() ein JSON-Objekt statt eines Arrays erzeugte ({"1":26,...} statt [26,...]) - Chart.js interpretierte das fehlerhaft. Mit array_values() behoben, live geprüft (Chart-Daten jetzt als sauberes Array). Alle anderen Statistik-Diagramme auf dasselbe Muster geprüft - keine weiteren Fälle gefunden. Außerdem: die Seiten-Navigation (Seite 1/2/3/4) erscheint jetzt zusätzlich unterhalb der Diagramme, nicht mehr nur oben.
- Changelog: 1.18.0 - Statistik-Seite 4 mit den letzten beiden noch offenen Diagrammen: "Ergebnisse pro Spieltag" (Verlauf der echten Ergebnisse, analog zu Tipps pro Spieltag) und "Punkte (alle Mannschaften)" als vollständige, sortierte Liste statt nur Top-3/Flop-3 (oberes Drittel grün, unteres Drittel rot).
- Changelog: 1.17.0 - Statistik-Seite 3 mit vier weiteren Diagrammen: Tipps (Top 3 pro Ergebnis), Ergebnisse (Top 3 pro Ergebnis), Spieltagsplatzierungen, Vergleich mit dem Spieltagssieger.
- Changelog: 1.16.1 - "Statistik" aus dem Tippspiel-Hauptmenü entfernt - bleibt weiterhin erreichbar über den Link unter der Tipper-Spieltagsansicht (renderTippEinsichtTipperView(), Klick auf einen Tippernamen in der Tippeinsicht).
- Changelog: 1.16.0 - Statistik-Seite bekommt Seiten-Navigation (Vorbild kicktipp.de: mehrere Diagramm-Seiten statt alles auf einmal) - Seite 2 mit vier weiteren Diagrammen: Ergebnisse-Verteilung, Tipps pro Spieltag, Punkte pro Spieltag, Punkte zur Spitze. BUGFIX während der Entwicklung: $maxNr war in renderTippStatistikView() nie definiert, wodurch die Seite-2-Diagramme trotz vorhandener Daten leer blieben - vor Auslieferung selbst gefunden und behoben.
- Changelog: 1.15.0 - Kurze Erklärungstexte unter jeder der vier Diagramm-Überschriften auf der Statistik-Seite ergänzt.
- Changelog: 1.14.1 - Bugfix: renderTippStatistikView() und renderTippTipptabelleView() fehlte der Bypass für archivierte Ligen mit Tipp-Historie (im Gegensatz zu Tippeinsicht/Gesamtübersicht) - eine über die Tipper-Spieltagsansicht angeforderte archivierte Liga fiel dadurch stillschweigend auf die erste aktive Liga des angemeldeten Nutzers zurück (Statistik/Tipptabelle zeigten dann falsche Liga/falschen Tipper). Live geprüft: beide Links aus einer archivierten Liga heraus zeigen jetzt korrekt die Daten des gewählten Tippers in der archivierten Liga.
- Changelog: 1.14.0 - Tippername in der Tippeinsicht-Matrix ist jetzt anklickbar, öffnet eine neue Detailansicht (renderTippEinsichtTipperView()) mit Heim/Gast/Erg/Tipp/Pkt je Partie des zuletzt ausgewerteten Spieltags, Spieltags- und Gesamtpunkten, sowie Rang- und Spieltag-Navigation. Darunter zwei neue Links zu Statistik und zur neuen "Tipptabelle" (renderTippTipptabelleView()) - letztere berechnet die Liga-Tabelle so, wie sie mit den Tipps eines Tippers statt der echten Ergebnisse aussähe, unter Wiederverwendung von LigaService::computeStandings(). Nur bereits sichtbare Tipps fließen ein - live geprüft: eine noch nicht sichtbare Partie verändert die Tabelle nicht, nach Ablauf der Abgabefrist fließt sie korrekt mit ein.
- Changelog: 1.13.0 - Neuer Reiter "Statistik" mit vier Chart.js-Diagrammen (Tipps-Verteilung, Punkte-Verteilung, Gesamtplatzierungsverlauf, Top-3/Flop-3-Mannschaften nach Punkten) - erste Ausbaustufe, weitere Diagramme folgen. Nutzt das bereits im Projekt vorhandene, selbst gehostete Chart.js (siehe Fieberkurven), kein zusätzliches Paket. Dieselbe Rang-basierte Vor/Zurück-Navigation wie bei der Gesamtübersicht-Tipper-Detailansicht.
- Changelog: 1.12.0 - Neue "Spielregeln"-Erklärseite (historisches Feature aus dem alten LMO): Anleitung, Punkteverteilung und Haftungsausschluss, als eigener Reiter neben Tippabgabe/Tippeinsicht/Rangliste/Gesamtübersicht. Die Punktewerte werden live aus den Admin-Einstellungen gelesen (dieselben Schlüssel wie calculateTippPunkte()), nicht hartkodiert - live geprüft: eine Änderung von pkt_ergebnis wirkt sich sofort auf die angezeigte Zahl aus. Enthält keine persönlichen Daten und ist daher immer ohne Login erreichbar, unabhängig von der "Tippeinsicht öffentlich"-Einstellung.
- Changelog: 1.11.1 - Die "Ergebnis"-Spalte (Kopfzeile und Werte) ist jetzt zentriert - sowohl im Tippabgabe-Formular als auch im Tippeinsicht-Spielplan.
- Changelog: 1.11.0 - Bugfix: "Gast" (und "Termin"/"Ergebnis") in den .tipp-table-Kopfzeilen wirkten nicht linksbündig, obwohl die CSS-Regel dafür existierte - die Kopfzeile steckte nie in einem <thead>, wodurch die Regel (table.tipp-table thead th) nie griff und der Browser stattdessen seine eigene Standard-Zentrierung für <th> anwendete. Alle .tipp-table-Vorkommen mit Kopfzeile (Tippabgabe, Tippeinsicht-Spielplan, Rangliste) jetzt korrekt in <thead>/<tbody> gekapselt.
- Changelog: 1.10.1 - Die "Heim"-Spalte in der Partien-Tabelle der Tippeinsicht ist jetzt rechtsbündig ausgerichtet (Überschrift und Teamnamen).
- Changelog: 1.10.0 - Archivierte Ligen mit Tipp-Historie bleiben jetzt in Tippeinsicht/Gesamtübersicht/Tipper-Detailansicht einsehbar (nur nicht mehr tippbar, mit "archiviert"-Badge). Neuer Link "Archivierte Tippsaisons" unter der Anzeige, erscheint nur, wenn es überhaupt archivierte Ligen mit Tipp-Historie gibt; die Liste dahinter zeigt bewusst nur Ligen mit tatsächlichen Tipps, keine leeren archivierten Ligen. Live geprüft: Link erscheint korrekt erst nach dem Archivieren einer Liga mit Tipps, eine parallel angelegte leere archivierte Liga taucht in der Liste nicht auf.
- Changelog: 1.9.0 - renderTippspielTabsBar() bekommt einen optionalen $ligaId-Parameter, hängt "&liga=X" an die liga-bezogenen Tabs (Tippabgabe/Tippeinsicht/Gesamtübersicht) an - ein Reiterwechsel bleibt jetzt bei der gerade angeschauten Liga statt immer auf die erste Liga zurückzuspringen. Rangliste bleibt bewusst ohne Parameter (nicht liga-bezogen). Nebenbei einen stehengebliebenen Verweis in einem Kommentar entfernt.
- Changelog: 1.8.0 - Gesamtübersicht: Spieltag-Spaltenköpfe verlinken jetzt zur Tippeinsicht des jeweiligen Spieltags, Tippernamen verlinken zu einer neuen Detailansicht (renderTippGesamtuebersichtTipperView()) mit Pfeil-Navigation zum nächst-/vorherplatzierten Tipper (nach aktueller Rangliste, nicht alphabetisch).
- Changelog: 1.7.0 - Neuer Reiter "Gesamtübersicht" neben Tippabgabe/Tippeinsicht/Rangliste - eine Tabelle mit einer Zeile je Tipper und einer Spalte je Spieltag (erzielte Punkte, Spieltagssieger rot hervorgehoben) plus Gesamtsumme. Respektiert dieselbe "Tippeinsicht öffentlich"-Einstellung wie die Tippeinsicht.
- Changelog: 1.6.0 - Tippeinsicht komplett umgebaut: statt eines Blocks pro Partie (mit allen Tippern darunter) jetzt eine kompakte Matrix-Tabelle - eine Spalte pro Partie mit Team-Kurzcodes im Kopf, jede Zeile ein Tipper mit Rang, Rangveränderung, den einzelnen Tipps (mit erzielten Punkten als Hochzahl bei Treffern) sowie Spieltags- und Gesamtpunkten. Spieltag-Navigation jetzt als Pfeile mit Beschriftung statt einer Liste aller Nummern.
- Changelog: 1.5.1 - Zusätzlicher Link zur öffentlichen Tippeinsicht auf der Login-Seite selbst (falls jemand trotzdem dort landet, z.B. durch eine abgelaufene Sitzung), wenn die Einstellung aktiv ist.
- Changelog: 1.5.0 - renderTippEinsichtView() funktioniert jetzt auch ohne eingeloggten Tipper (neue Einstellung "Tippeinsicht öffentlich" unter Admin → Tippspiel → Anzeigen/Darstellung) - zeigt dann alle tippbaren Ligen ohne persönliche Abo-Filterung und einen Hinweistext mit Login-Link statt der Konto-Leiste. Der Login-Zwang wird für die Aktion "einsicht" gezielt übersprungen, wenn die Einstellung aktiv ist.
- Changelog: 1.4.1 - Bugfix: Die Tippeinsicht hatte (anders als die Tippabgabe) gar keinen Liga-Umschalter - bei mehr als einer abonnierten Liga wurde immer nur die erste angezeigt, die zweite war unerreichbar. Denselben Umschalter-Block wie in der Tippabgabe ergänzt
- Changelog: 1.4.0 - "Passwort vergessen" bietet jetzt zwei Eingabefelder (Nickname ODER Email) statt nur Email, sucht entsprechend und meldet bei Nichtfund konkret zurück, welches Feld nichts fand - siehe tippRequestPasswordReset() in tipp_lib.php 0.6.1
- Changelog: 1.3.0 - Verhalten korrigiert (Rückmeldung: Fallback war andersrum gewünscht): tippFilterLigenByAbo() liefert jetzt bei leerem Abo eine LEERE Liste statt aller Ligen. Dafür fragt die Registrierung (renderTippRegisterView()) die zu abonnierenden Ligen direkt mit ab (Checkboxen unter den Passwortfeldern), damit ein frisch registrierter Tipper nicht ohne Abo dasteht. Tippabgabe/-einsicht zeigen bei leerem Abo (aber vorhandenen Ligen) jetzt einen Hinweis mit Link zur Kontoseite statt der irreführenden "keine Liga freigegeben"-Meldung
- Changelog: 1.2.0 - Liga-Abo wirkt jetzt tatsächlich: neue Funktion tippFilterLigenByAbo() schränkt Tippabgabe/Tippeinsicht auf die abonnierten Ligen ein - bisher war das Abo nur eine wirkungslose Merkliste. Ohne jegliches Abo bleibt die volle Liste sichtbar (kein versehentliches Aussperren neu registrierter Tipper). Speichern eines Tipps (?action=save) bleibt bewusst uneingeschränkt - das Abo ist eine Anzeige-Filterung, keine Zugriffssperre
- Changelog: 1.1.1 - Bugfix: Die Kontoseite (?action=konto) hatte keinerlei Navigation zurück zur Tippabgabe/Einsicht/Rangliste - jetzt bekommt sie dieselbe Tab-Leiste wie die anderen Ansichten (keiner der drei Tabs aktiv markiert, da Konto kein eigener Tab ist)
- Changelog: 1.1.0 - Neue Self-Service-Kontoseite (?action=konto): Email/Passwort/Name/Adresse ändern, Team beitreten/gründen/verlassen, Newsletter/Reminder, Liga-Abos - Nickname bewusst NICHT editierbar. Neue "Passwort vergessen"-Flow (?action=passwort_vergessen/?action=passwort_reset), Link auf der Login-Seite ergänzt. Rangliste um vier Spalten erweitert (RE/RTD/RT/JP, analog zum alten LMO-Tippspiel-Addon) - die Werte wurden von tippGetRangliste() bereits berechnet, waren aber nicht angezeigt
- Changelog: 1.0.0 - Initiale Version: löst die bisherige eigenständige addon/tipp/tipp.php ab (eigenes <html>/<head>/CSS) und bindet das Tippspiel stattdessen als View ins bestehende Template-System ein - analog zur Spielerstatistik (renderSpielerstatistikView() in addon/player/frontend_spielerstat.php), erreichbar über home.php?view=tippspiel&action=... Läuft dadurch automatisch im vom Besucher/Betreiber gewählten Template (default, colored, dark, light, matchday) statt in einem eigenen, unveränderlichen Design. tippspielHandleRequest() übernimmt die Rolle von "Phase 1" aus der alten tipp.php (POST-/Redirect-Verarbeitung vor jeder HTML-Ausgabe, siehe dortiger Changelog 0.3.0/0.3.1) - muss von home.php VOR renderTemplate() aufgerufen werden. Die gesamte Geschäftslogik (tippLogin(), tippRegister(), tippSaveAbgabe(), tippGetEinsichtDaten(), tippGetRangliste(), calculateTippPunkte() usw.) bleibt unverändert in frontend_tipp.php - diese Datei ist reine Präsentation.

## addon-run.php

- Changelog: 1.0.0 - Neu (Beitrag: Sicherheitsüberarbeitung): zentraler, einziger Einstiegspunkt für alle "Standalone"-Addon-Skripte (z.B. das Mini-Tabellen-Addon, zum Einbetten via iframe auf fremden Websites). Ersetzt den bisherigen direkten URL-Aufruf einzelner Dateien unter addon/{name}/ - diese sind ab jetzt per addon/.htaccess vollständig vor direkter PHP-Ausführung via Web-Request geschützt (siehe dortiger Changelog-Eintrag). Prüft jeden Aufruf gegen eine explizite Whitelist im jeweiligen addon.json (neues Feld "standalone_entrypoints") sowie per realpath()-Abgleich gegen Pfad-Traversal, BEVOR ein require() stattfindet. Betroffene Addons (jeweils eigenes CHANGELOG.md): mini, viewer, ewige, tabellenrechner, relegation, liga-klassen-rekorde.

## admin.php

- Changelog: 1.6.0 - Addon-Manager-Framework integriert (Beitrag Torsten Hofmann): AddonManager wird gebootet (bootAdmin() lädt admin_handlers aller aktivierten Addons dynamisch, ersetzt die vorher festen require_once-Zeilen für handler_spielerstat.php/handler_tipp.php); Admin-Views werden zuerst über addonManager()->getAdminView($action) geprüft (ersetzt die festen Routen für "tippspiel", "spielerstatistik", "spst_import_review" - diese Addons registrieren ihre Views jetzt selbst über addon.json); neuer Hook admin.footer am Seitenende.
- Changelog: 1.5.4 - Sicherheitsfix: requireCsrf() zentral vor allen POST-Handlern aufgerufen, schützt alle 30+ POST-Aktionen (Liga speichern, Ergebnisse, Backup, Import, Wartung etc.) auf einen Schlag.
- Changelog: 1.5.3 - Bindet addon/tipp/handler_tipp.php ein (neues Tippspiel-Addon, erste Speicher-Aktion für die Punkteverteilung)
- Changelog: 1.5.2 - Route für die neue Aktion "tippspiel" ergänzt (neues Tippspiel-Addon, siehe addon/tipp/view_tippspiel.php)
- Changelog: 1.5.1 - Spielerstatistik-Addon nach addon/player/ verschoben (neben addon/mini/, einheitliche Addon-Ordnerstruktur); neue Konstante ADDON_INC
- Changelog: 1.5.0 - Routen für das neue Spielerstatistik-Addon ergänzt: "spielerstatistik" (Verwaltung je Liga) und "spst_import_review" (Team-Abgleich beim Import alter .stat/.cfg-Dateien), siehe handler_spielerstat.php
- Changelog: 1.4.3 - require_once-Pfad an die Datei-Umbenennung angepasst (interne Bezeichnungen jetzt durchgehend auf Englisch, siehe league-key_data.php 1.2.0)
- Changelog: 1.4.2 - Route für "reset_password" (Passwort-Reset-Landingpage aus der E-Mail) ergänzt
- Changelog: 1.4.1 - Route für "import_review" (Team-Namensabgleich beim .l98-Import) ergänzt
- Changelog: 1.4.0 - Route + Handler für "Wartung" (Datenbank-Backup/Wiederherstellung) ergänzt
- Changelog: 1.3.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.0 - Route für archiv-Action
- Changelog: 1.2.0 - Route für teams-Action hinzugefügt

## admin/bootstrap.php

- Changelog: 1.23.0 - Drei Sicherheits-Verbesserungen aus Torsten Hofmanns parallelem Update übernommen (Review/Vergleich siehe SECURITY_COMPARISON.md): (1) Content-Security-Policy deutlich verschärft - statt nur `frame-ancestors 'none'` jetzt zusätzlich `default-src`/`style-src`/`script-src`/`img-src`/`font-src`/`object-src 'none'`/`base-uri 'self'`, plus neuer `Referrer-Policy: strict-origin-when-cross-origin`-Header; (2) checkSessionIdleTimeout() ruft nach dem Session-Reset jetzt session_regenerate_id(true) auf (verhindert Session-Fixation über die abgelaufene Session-ID hinweg); (3) Torstens eigene Varianten dieser drei Punkte wurden NICHT direkt übernommen, sondern auf unsere bestehende Architektur adaptiert (DB-basiertes Rate-Limiting, DOMDocument-SVG-Sanitizing und die strengere safeRedirectTarget() bleiben unverändert bestehen, da sie im Vergleich robuster sind als Torstens Entsprechungen - siehe Vergleichsdokument).
- Changelog: 1.22.0 - Neue Datei-basierte Protokollierung für PHP-Fehler/Warnungen: der bestehende globale Exception-Handler schreibt fatale Fehler jetzt zusätzlich in eine dedizierte Log-Datei (store/php_issues.log), neuer set_error_handler() fängt zusätzlich Warnungen/Notices/Deprecated-Meldungen (die zuvor gar nicht protokolliert wurden) ein. Die eigentlichen Lese-/Schreibfunktionen (phpIssueLogFile/logPhpIssue/readPhpIssueLog) wurden nach config_loader.php verschoben, damit sowohl Admin- als auch Frontend-Fehler in derselben Datei landen, ohne die bewusste Trennung der beiden Bootstrap-Dateien aufzuweichen. Live geprüft: ausgelöste Warnung UND fataler Fehler landen beide korrekt in der Datei.
- Changelog: 1.21.1 - Zusätzlicher Fund beim Nachprüfen: eine verwaiste, doppelte Kopie von ensureLoginAttemptsTable() (ohne Funktionssignatur, wurde beim Laden der Datei als nutzloser Bare-Block sofort ausgeführt statt als aufrufbare Funktion zu existieren) direkt neben der korrekten Definition entfernt. Danach eine systematische Prüfung der gesamten Datei (und aller anderen in den letzten Sitzungen bearbeiteten Dateien) auf ähnliche verwaiste Funktionsköpfe durchgeführt - keine weiteren Fälle gefunden.
- Changelog: 1.21.0 - Bugfix: die Funktion checkRuntimeExtensions() hatte durch eine frühere Bearbeitung dieser Datei ihre Funktionssignatur verloren (nur der Funktionskörper blieb übrig, derselbe Fehlertyp wie zuvor bei ensureLoginAttemptsTable()) - dadurch zeigte die Info-Seite unter Einstellungen "Call to undefined function" statt der PHP-Erweiterungstabelle. Wiederhergestellt und live bestätigt: Info-Tab zeigt jetzt wieder korrekt PHP-Version und Erweiterungsstatus. Zusätzlich: der globale Fehler-Handler zeigt bei einem unerwarteten Fehler jetzt einen Link zum Aktivitätsprotokoll (Administrator → Log) an - nur sichtbar, wenn eine eingeloggte Admin-Session besteht. Live über echte HTTP-Requests bestätigt: Link erscheint korrekt bei aktiver Session, bleibt bei anonymen Anfragen unsichtbar.
- Changelog: 1.20.0 - Neues Audit-Log für sicherheitsrelevante Admin-Aktionen: neue admin_audit_log-Tabelle (ensureAuditLogTable()) und zentrale Funktion logAdminAction(). Live getestet: Login, Liga-Löschung und Einstellungen-Speichern erzeugen nachweislich korrekte Log-Einträge mit Benutzername, Aktion, Details und IP.
- Changelog: 1.19.0 - Bugfix: die Funktion ensureLoginAttemptsTable() war bei einer früheren Bearbeitung dieser Datei versehentlich verlorengegangen (nur der Funktionsaufruf blieb übrig) - dadurch war das Login-Rate-Limiting aus einer vorherigen Session stillschweigend kaputt. Beim Testen des neuen Passwort-Reset-Rate-Limitings entdeckt und wiederhergestellt; beide Rate-Limiting-Funktionen (Login und Passwort-Reset) erneut end-to-end bestätigt.
- Changelog: 1.18.0 - Neue Funktion checkRuntimeExtensions(): eigenständige PHP-Erweiterungsprüfung für die neue Info-Seite unter Einstellungen, unabhängig von install.php (das sich nach der Installation selbst löscht und danach nicht mehr verfügbar wäre).
- Changelog: 1.17.0 - Sicherheitsfix: SVG-Uploads werden jetzt aktiv bereinigt (nicht nur oberflächlich geprüft). Neue sanitizeSvgContent()-Funktion (nutzt DOMDocument statt reiner Regex, mit deaktivierter externer Entity-Auflösung gegen XXE): entfernt <script>-Elemente, alle "on*"-Event-Handler-Attribute und "javascript:"-/"data:text/html"-URIs in beliebigen Attributen sowie <foreignObject>. saveTeamLogoUpload() schreibt bei SVG jetzt den bereinigten Inhalt statt die ungefilterten Original-Upload-Bytes zu übernehmen; nicht sauber parsbare SVGs werden komplett abgelehnt. Live mit einer präparierten Angriffsdatei getestet (Script-Tag, onload/onclick, javascript:-URI, foreignObject) - alles korrekt entfernt, legitimer Inhalt (Kreis, Text) bleibt erhalten; eine echte, harmlose SVG-Datei bleibt dabei byte-identisch unverändert.
- Changelog: 1.16.0 - Sicherheitsfix: automatisches Logout nach Inaktivität eingebaut. Neue checkSessionIdleTimeout()-Funktion, in requireLogin() integriert (läuft auf jeder geschützten Admin-Seite automatisch mit) - nach 30 Minuten ohne Aktivität wird die Session verworfen und zum Login umgeleitet, mit erklärender Meldung. Live geprüft: aktive Session (5 Min. her) bleibt bestehen und aktualisiert den Zeitstempel, inaktive Session (35 Min. her, Limit 30) wird korrekt beendet inkl. passender Flash-Nachricht.
- Changelog: 1.15.0 - Sicherheitsfix: Security-Header ergänzt (X-Frame-Options: DENY, X-Content-Type-Options: nosniff, Content-Security-Policy: frame-ancestors 'none'). Der Adminbereich hat keinen legitimen Grund, jemals eingebettet zu werden. Live per echtem HTTP-Request bestätigt.
- Changelog: 1.14.0 - Sicherheitsfix: Login-Rate-Limiting eingebaut. Neue login_attempts-Tabelle + Funktionen loginRateLimitSecondsLeft()/recordFailedLoginAttempt()/clearLoginAttempts(): nach 5 Fehlversuchen (Benutzername ODER IP) 15 Minuten Sperre. IP-Erkennung nutzt bewusst nur REMOTE_ADDR (kein X-Forwarded-For), da dieser Header vom Client frei mitschickbar wäre. Live geprüft: Sperre greift exakt nach dem 5. Versuch, IP-übergreifend über verschiedene Benutzernamen erkannt, Reset nach erfolgreichem Login bestätigt, echter Handler-Aufruf bricht nachweislich vor der Passwortprüfung ab (kein 6. DB-Eintrag).
- Changelog: 1.13.0 - Sicherheitsfix: CSRF-Schutz eingebaut. Neue Funktionen csrfToken()/csrfField()/requireCsrf() - ein Token pro Session, zentral vor jedem POST geprüft, statt jede der 38 POST-Aktionen einzeln abzusichern. Zusätzlich safeRedirectTarget(): validiert Redirect-Ziele aus dem Request (nur interne "?..."-Query-Strings erlaubt) gegen Open-Redirect-Angriffe. Live geprüft: Angriff ohne Token korrekt mit 403 blockiert, legitimer Request mit Token durchgelassen, kompletter Login-Rundlauf über den echten Handler bestätigt funktionsfähig.
- Changelog: 1.12.0 - Neue Funktion ensureSportProfileColumns(): zentrale on-demand-Migration für sport_type (liga) und extra_data (liga_partien), analog zu ensureSpielstatusColumns(). Bugfix: diese Absicherung fehlte bisher komplett - admin/data_loader.php las sport_type direkt ohne eigenen Schutz, wodurch nicht nur der .l98-Import, sondern die komplette Liga-Einstellungsseite auf einer nicht neu migrierten Installation abgestürzt wäre. Live geprüft: Spalte gezielt entfernt, echte Admin-Seite darüber geladen - kein Absturz, Spalte automatisch wiederhergestellt.
- Changelog: 1.11.0 - Nur die Sport-Profile eingebunden (bewusst NICHT die komplette Liga-Trait-Kette wie in Torstens ursprünglichem Vorschlag - das hätte der bewussten Trennung von Admin- und Frontend-Bootstrap widersprochen, siehe "Tipps nachtragen"-Feature). Der einzige tatsächlich benötigte Aufruf (getLigaOptions() beim Sportart-Wechsel) wurde stattdessen als eigenständige Admin-Query gebaut.
- Changelog: 1.10.3 - Logo-Format-Priorität für die Admin-Vorschau auf SVG-zuerst umgestellt (TEAM_LOGO_ALLOWED_EXT), passend zur Browser-Ausgabe der echten Website - betrifft nur die Vorschau/Löschfunktion, nie den PDF-Export.
- Changelog: 1.10.2 - Logo-Format-Priorität umgekehrt (Rasterformate vor svg), identisch zu src/Liga/TeamFormattingTrait.php 1.0.2, damit Admin-Vorschau und tatsächliche Anzeige übereinstimmen.
- Changelog: 1.10.2 - Logo-Format-Priorität umgekehrt (Rasterformate vor svg), siehe src/Liga/TeamFormattingTrait.php 1.0.2 für die Begründung - identische Reihenfolge, damit Admin-Vorschau und tatsächliche Anzeige übereinstimmen
- Changelog: 1.10.1 - ensureAdminSettings() seedet jetzt zusätzlich show_back_link=1 (Liga- Übersicht sichtbar), analog zu timezone - für Bestandsinstallationen, die die neue install.php-Seedung (1.8.0) nie durchlaufen haben
- Changelog: 1.10.0 - Session-Cookie jetzt mit HttpOnly, SameSite=Lax und (bei HTTPS) Secure, analog zu frontend/bootstrap.php 1.6.0. Globaler Exception-Handler: unerwartete Fehler landen zusätzlich im Server-Log (Kurzfassung bleibt im Adminbereich sichtbar, da für Fehlersuche durch den Betreiber hilfreich)
- Changelog: 1.9.0 - Bugfix: die "(heute TEAM_HEUTE)"-Kennzeichnung im Teamvergleich hing bisher vom zufällig angeklickten Team ab (z.B. je nachdem von welcher Liga/welchem Spiel aus man den Vergleich öffnete, zeigte mal der eine, mal der andere Name als "heute"). Neue Spalte team_links.newer_team_id legt jetzt fest, welches Team der tatsächlich aktuelle Name ist – unabhängig vom Aufrufkontext. addTeamLink() nimmt das jetzt optional entgegen, neue Funktion setTeamLinkDirection() zum nachträglichen Ändern. Bestehende Verknüpfungen ohne Richtungsangabe (newer_team_id NULL) fallen weiterhin auf das alte, kontextabhängige Verhalten zurück, bis die Richtung nachträglich gesetzt wird
- Changelog: 1.8.0 - Neue Funktionen für Team-Verknüpfungen ergänzt: ensureTeamLinksSchema()/ getTeamLinksForTeam()/addTeamLink()/deleteTeamLink() (Tabelle team_links). Nicht-destruktive Alternative zu mergeTeams() für Umbenennung/Fusion/Abspaltung – beide Team-Datensätze bleiben eigenständig, nur eine Verknüpfung wird gespeichert. Wird von resolveLinkedTeamIds() in data_liga.php für den Teamvergleich genutzt
- Changelog: 1.7.4 - interne Bezeichnungen jetzt durchgehend auf Englisch ("League Key" statt der vorherigen deutschen Bezeichnung, die hier nicht mehr vorkommen soll). Der sichtbare UI-Text hieß schon vorher "Schlüsselplan" und ist unverändert. Funktionsname, Konstante und interner Modus-Wert entsprechend angepasst (siehe league-key_data.php)
- Changelog: 1.7.3 - Logo-Ordner von assets/img/Teams auf assets/img/teams umbenannt (kleingeschrieben)
- Changelog: 1.7.2 - Neues Feature "Teams (global)": Logo & Vereinslink. ensureTeamUrlSchema() ergänzt teams_global.url. Neue Funktionen findTeamLogoPath()/ deleteTeamLogo()/saveTeamLogoUpload(): Team-Logos liegen als assets/img/teams/{team-id}.{ext} (SVG/JPG/PNG/GIF, Mindesthöhe 50px, Inhalts-/MIME-Prüfung statt reiner Endungs-Prüfung), keine eigene DB-Spalte nötig, da einfach übers Dateisystem gefunden
- Changelog: 1.7.1 - buildScheduleForMode('none'): legt jetzt trotzdem die korrekte Anzahl Spieltage/Begegnungen an (wie ein normaler Rundenplan für die Teamzahl), nur mit Leerteam-Platzhaltern (-1, siehe createLigaInDB()) statt echter Paarungen – vorher wurden bei "kein Spielplan" gar keine Spieltage/ Partien-Zeilen angelegt
- Changelog: 1.7.0 - Neue Funktionen getLeagueKeyPattern()/buildScheduleForMode(): Spielplan für reguläre Ligen kann jetzt wahlweise nach dem DFB-League-Key-Muster (siehe admin/league-key_data.php, für 6/8/10/12/14/16/18 Teams), per Zufall (bisheriges generateRoundRobin()) oder gar nicht erstellt werden
- Changelog: 1.6.0 - "Passwort vergessen"-Grundlagen ergänzt: ensurePasswordResetSchema() (email-Spalte + admin_password_resets-Tabelle, Migration für bestehende Installationen), getSiteBaseUrl(), sendPasswordResetEmail() (reine PHP-mail()-Funktion, keine externe Mail-Bibliothek)
- Changelog: 1.5.0 - getAppVersion() ergänzt (liest Version aus composer.json)
- Changelog: 1.4.4 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.4.3 - lang/i18n.php domain-fähig (admin/frontend getrennt): getCurrentLanguage()-Aufruf angepasst ('admin' explizit übergeben)
- Changelog: 1.4.2 - koModusLabel() ergänzt (übersetzte KO-Modus-Bezeichnungen; KO_MODUS-Werte bleiben interne Keys)
- Changelog: 1.4.1 - Standardsprache aus admin_settings ("language") wird an getCurrentLanguage() übergeben
- Changelog: 1.4.0 - Mehrsprachigkeit: lang/i18n.php eingebunden, initLanguage() aufgerufen
- Changelog: 1.3.4 - tsToDatetime: konfigurierbare Zeitzone statt UTC; getAdminTimezone(); ensureAdminSettings()
- Changelog: 1.3.2 - ensureSpielstatusColumns(): status (n.V./i.E.) + bericht_url Spalten
- Changelog: 1.3.1 - ensureLastLoginColumn() Migration für admin_users.last_login
- Changelog: 1.3.0 - ensureArchivColumns() hinzugefügt
- Changelog: 1.2.0 - config.php Pfad: __DIR__ -> dirname(__DIR__)

## admin/data_loader.php

- Changelog: 1.8.0 - Addon-Manager-Framework integriert (Beitrag Torsten Hofmann): Nav-Array wird jetzt dynamisch um Addon-Einträge ergänzt (addonManager()->getNavItems(), aus admin_nav in addon.json, sortiert nach position) - fester Eintrag für "tippspiel" entfernt, wird jetzt vom tipp-Addon selbst registriert. Spielerstatistik-Datenladung nur noch aktiv, wenn das "player"-Addon aktiviert ist (addonManager()->isEnabled('player')) statt festem require_once.
- Changelog: 1.7.11 - Lädt für die neue Log-Ansicht unter Administrator die letzten Audit-Log-Einträge (paginiert, 50 pro Seite).
- Changelog: 1.7.10 - Ruft die neue ensureSportProfileColumns() auf jedem eingeloggten Admin-Seitenaufruf auf, analog zu den bestehenden ensure*()-Migrationsfunktionen.
- Changelog: 1.7.9 - Liga-Settings-Abfrage liest jetzt zusätzlich sport_type mit, für die korrekte Vorauswahl im neuen Sportart-Dropdown.
- Changelog: 1.7.8 - Lädt jetzt die neue "ab Spieltag"-Spalte mit (Beitrag Torsten Hofmann), inkl. Schema-Migration.
- Changelog: 1.7.7 - minuspunkte_korrektur-Spalte/Migration ergänzt (analog zu tore_korrektur)
- Changelog: 1.7.6 - tore_korrektur-Spalte/Migration auch beim reinen Anzeigen des "Strafen"-Tabs berücksichtigt (nicht erst beim Speichern), damit bereits gespeicherte Strafpunkte/Straftore nicht scheinbar verschwinden
- Changelog: 1.7.5 - $ligaSettingsData['teams'] liefert jetzt auch kurz/mittel (nicht nur id/name), benötigt für den neuen "Teams"-Tab in admin/view_liga_settings.php 1.5.0
- Changelog: 1.7.4 - $ligaSettingsData['strafen'] ergänzt (Strafpunkte/Straftore je Team, siehe admin/handler_settings.php 1.3.7, neuer Tab "Strafen")
- Changelog: 1.7.3 - nav-Eintrag + pageTitle für "Tippspiel" ergänzt (neues Addon, siehe addon/tipp/view_tippspiel.php - aktuell nur ein Platzhalter, die eigentliche Verwaltungsoberfläche folgt in kommenden Sitzungen)
- Changelog: 1.7.2 - Teams-Liste liefert jetzt zusätzlich link_count je Team (Anzahl Team-Verknüpfungen, siehe team_links), damit in "Teams (global)" auf einen Blick erkennbar ist, welche Teams schon verknüpft sind
- Changelog: 1.7.1 - spielerstatData enthält jetzt zusätzlich die Namen aller Teams der Liga ("teams"), damit Team-/Mannschaft-/Verein-Spalten im Spielerstatistik- Addon als Dropdown statt Freitext angeboten werden können, siehe view_spielerstatistik.php
- Changelog: 1.7.0 - Neuer Datenlader für die Spielerstatistik-Verwaltung ("spielerstatistik" Action, siehe admin/spielerstat_lib.php + view_spielerstatistik.php)
- Changelog: 1.6.6 - Bugfix: Duplikat-Erkennung bei "Teams (global)" nutzte ungeschützt mb_strtolower() – auf Hosting ohne mbstring-Extension (die laut Projektkonvention NICHT garantiert ist, siehe handler_import_export.php/ pdf_export.php) führte das zu einer Exception, wodurch die komplette Teams-Seite leer blieb. Fällt jetzt auf strtolower() + Umlaut-Ersetzung zurück, wenn mbstring fehlt
- Changelog: 1.6.5 - Teams-Query liefert jetzt auch teams_global.url mit (Logo&Link-Feature), ruft dafür vorher ensureTeamUrlSchema() auf
- Changelog: 1.6.4 - Bugfix KO-Team-Dropdown: die letzte Runde eines Turniers mit "Spiel um Platz 3" (KlFin) enthält zwei Paarungen (Finale + kleines Finale), die unterschiedliche Vorrunden-Teams brauchen – das Finale die Sieger, das Spiel um Platz 3 aber die VERLIERER der Halbfinals. Bisher wurden nur Sieger ermittelt, wodurch sich das Spiel um Platz 3 gar nicht eintragen ließ. Jetzt werden in der letzten Runde Sieger UND Verlierer der Vorrunde gemeinsam zur Auswahl angeboten
- Changelog: 1.6.3 - Users-Query liefert jetzt auch die E-Mail-Adresse mit (für das neue E-Mail-Feld in der Benutzerverwaltung), ruft dafür vorher ensurePasswordResetSchema() auf (stellt sicher, dass admin_users.email auch auf bestehenden, noch nicht migrierten Installationen existiert)
- Changelog: 1.6.2 - Bugfix: die pauschale requireLogin()-Pflicht griff auch für "reset_password" (die Landingpage aus der "Passwort vergessen"-E-Mail), wodurch ausgeloggte Besucher dort sofort zum Login zurückgeschickt wurden statt das Neues-Passwort-Formular zu sehen – genau der Fall, für den die Seite gedacht ist. "reset_password" ist jetzt wie "login" von der Pflicht ausgenommen
- Changelog: 1.6.1 - pageTitle für "import_review" (Team-Namensabgleich) ergänzt
- Changelog: 1.6.0 - nav-Eintrag + pageTitle für "Wartung" (Datenbank-Backup/Wiederherstellung) ergänzt
- Changelog: 1.5.2 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.5.1 - Bugfix: 'teams'-Action fehlte im pageTitle-match (zeigte fälschlich "Admin" statt "Teams (global)")
- Changelog: 1.5.0 - nav-Labels + pageTitle über t() übersetzt; 'settings'-Titel ergänzt (fiel bisher auf 'Admin' zurück)
- Changelog: 1.4.3 - date_default_timezone_set() nach Login aus DB-Einstellung
- Changelog: 1.4.1 - ticker/tickertext in spieltagData geladen
- Changelog: 1.3.6 - prevWinners: allPlayed prüft auch -1 (LMO-Legacy für nicht gespielt)
- Changelog: 1.3.5 - prevWinners: Hin+Rückspiel paarungsweise auswerten (Gesamttore); Dummy-Teams ausschließen
- Changelog: 1.3.2 - KlFin + totalRounds in spieltagData geladen für Finale/Platz3-Erkennung
- Changelog: 1.2.0 - teamsData-Lader; nav-Eintrag Teams; pageTitle Teams

## admin/handler_backup.php

- Changelog: 1.5.0 - Neue Aktion save_maintenance_mode (Beitrag: Torsten Hofmann, hier zusätzlich mit Audit-Log-Eintrag über logAdminAction() abgesichert - Torstens Originalversion hatte keine eigene Protokollierung dieser Aktion). Speichert den Wartungsmodus-Schalter als admin_settings-Eintrag, ausgewertet in frontend/bootstrap.php 1.12.0.
- Changelog: 1.4.0 - Protokolliert Backup erstellen/wiederherstellen/löschen im neuen Audit-Log - besonders wichtig bei "wiederherstellen", da dabei bestehende Daten überschrieben werden.
- Changelog: 1.3.0 - Spielerfotos (assets/img/player/, siehe addon/player/spielerstat_lib.php) werden jetzt im selben Logo-ZIP mitgesichert (eigenes Unterverzeichnis "player/" neben "teams/"), inkl. Wiederherstellung. Kein zusätzliches ZIP nötig, kein Verhaltensunterschied für ältere Backups ohne Fotos
- Changelog: 1.2.0 - Team-Logo-Ordner (assets/img/teams/) wird jetzt mitgesichert: neue Funktionen backupCreateLogosZip()/backupRestoreLogosZip()/ backupLogosZipFilenameFor(). Bei jedem Backup wird (falls ZipArchive verfügbar ist und mindestens ein Logo hochgeladen wurde) ein begleitendes "backup_{Zeitstempel}_logos.zip" im selben /store-Ordner angelegt, mit demselben Zeitstempel wie der SQL-Dump. Wird beim Wiederherstellen automatisch mit zurückgespielt (vorhandene Logos werden vorher entfernt, analog zur "Komplett ersetzen"-Logik der DB-Wiederherstellung), und beim Löschen/automatischen Aufräumen (Max-Anzahl) zusammen mit dem zugehörigen SQL-Backup entfernt. ZipArchive ist optional (wie bzip2) – fehlt die Erweiterung, wird die Logo-Sicherung übersprungen, die Datenbank-Sicherung funktioniert unverändert weiter
- Changelog: 1.1.1 - .htaccess-Text (Kommentare) für /store auf Englisch umgestellt, sowohl die Datei selbst als auch die Auto-Wiederherstellungs-Logik in backupDir()
- Changelog: 1.1.0 - Backups sind jetzt zwischen Installationen mit unterschiedlichem Tabellenprefix portabel: der beim Backup verwendete Prefix wird als Metadaten-Kommentar im Dump gespeichert ("-- Prefix: xyz_"); beim Wiederherstellen wird er bei Bedarf automatisch auf den aktuell in config.php konfigurierten Prefix umgeschrieben (reiner Textersatz auf den Backtick-Tabellenbezeichnern, bevor irgendetwas ausgeführt wird). Ältere Backups ohne diese Metadatenzeile laufen unverändert wie bisher
- Changelog: 1.0.0 - Initiale Version: Datenbank-Backup/Wiederherstellung für die neue Wartung-Seite (admin/view_wartung.php). Komplett ohne externe Bibliothek/Composer-Paket – SQL-Dump wird selbst geschrieben (analog zum abhängigkeitsfreien PDF-Export in frontend/pdf_export.php), Kompression über die PHP-Kernfunktionen gzencode()/bzcompress() (bzip2 nur wenn die Extension verfügbar ist, sonst wird die Option ausgeblendet). Backups landen in /store (Projekt-Root, per .htaccess abgesichert) und werden nach Anzahl begrenzt (älteste werden automatisch gelöscht, Einstellung "Maximale Anzahl an Backups").

## admin/handler_export.php

- Changelog: 1.2.4 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.3 - Flash-Meldung über t() übersetzt
- Changelog: 1.2.2 - Echter Tickertext aus liga_options statt Platzhalter NC=0 exportiert
- Changelog: 1.2.0 - Duplikat Actual entfernt; KlFin/playdown/playoffmode ergaenzt; ORDER BY p.spiel_nr -> spiel_nr

## admin/handler_import_export.php

- Changelog: 1.12.0 - Protokolliert jeden erfolgreichen .l98-Import zentral in runL98Import() im neuen Audit-Log, inkl. Dateiname und importiertem Liganamen.
- Changelog: 1.11.0 - createLigaInDB() bekommt einen neuen, optionalen $sportType-Parameter (mit derselben defensiven on-demand-Schema-Prüfung wie an anderer Stelle etabliert, bewusst vor beginTransaction()). Bestehende Aufrufer ohne den Parameter bleiben unverändert (Fußball als DB-Standardwert).
- Changelog: 1.10.0 - Bugfix: importL98IntoDB() versuchte sport_type zu setzen, ohne vorher zu prüfen, ob die Spalte existiert - schlug mit "Unknown column sport_type" fehl auf jeder Installation, die install.php seit der Sport-Profile-Erweiterung noch nicht erneut ausgeführt hatte. Eigene defensive Prüfung ergänzt (zusätzlich zur zentralen in admin/bootstrap.php, damit die Funktion auch bei einem eigenständigen/programmatischen Aufruf außerhalb der normalen admin.php-Anfrage sicher funktioniert).
- Changelog: 1.9.0 - Bugfix: Bei importL98IntoDB() liefen alle vier on-demand-Schema-Migrationen (Strafpunkte-Spalten tore_korrektur/minuspunkte_korrektur/ab_spieltag, sowie extra_data für Sätze) innerhalb der Import-Transaktion. ALTER TABLE löst in MySQL/MariaDB ein implizites Commit aus - die Transaktion wurde dadurch unbemerkt mittendrin aufgebrochen (ein späterer rollBack() hätte nur noch den Teil NACH der ersten ALTER TABLE zurückgerollt, nicht den kompletten Import). Alle vier Schema-Prüfungen jetzt konsequent VOR beginTransaction() verschoben, derselbe Fehler wie zuvor schon einmal bei der Spielerstatistik-Erweiterung behoben. Live geprüft: Import auf eine frische Datenbank ohne die vier Spalten läuft fehlerfrei durch, inTransaction() sauber vor und nach dem Import, alle Spalten korrekt migriert, Satzdaten vollständig übernommen (90 Partien mit extra_data).
- Changelog: 1.8.0 - .l98-Import erkennt jetzt automatisch Volleyball-Ligen (nameTor="Satz"/"Set" oder parsebare NTx-Satzergebnisse) und übernimmt die Satzergebnisse selbst als extra_data (vorher gingen sie beim Import komplett verloren). Neue Funktionen l98ParseSets() (Komma- UND Semikolon-Trennung unterstützt, mit/ohne Leerzeichen um die Doppelpunkte) und l98DetectSportType() (prüft sowohl Liga- als auch KO-Turnier-Format). importL98IntoDB()/runL98Import() bekommen einen neuen, optionalen Sportart-Parameter für die manuelle Übersteuerung durch den Admin. Bugfix während der Entwicklung: die erste Version von l98DetectSportType() prüfte nur das Liga-Format (partien), nicht das KO-Turnier-Format (paarungen/spiele) - beim Testen mit einer echten KO-Volleyball-Datei gefunden und behoben. Live mit drei echten Volleyball-.l98-Dateien geprüft (BiH Premijer Liga, 3.Liga Nord, sowie ein KO-Pokal-Turnier) - alle korrekt erkannt, importiert und die Punkteberechnung bestätigt korrekte 3:0/3:1/3:2-Aufschlüsselung.
- Changelog: 1.7.0 - .l98-Import übernimmt jetzt zusätzlich Strafpunkte-Daten (Beitrag: Torsten Hofmann, unabhängig vom Multi-Sport-Feature): [TeamN].SP (Punkteabzug), TOR1/TOR2 (Tore-/Gegentore-Korrektur), STDA ("ab Spieltag") und NOT (Grund) werden automatisch in liga_strafpunkte übernommen und wirken direkt in der Tabellenberechnung. tore_korrektur/minuspunkte_korrektur/ab_spieltag sind on-demand-Spalten (kommen erst mit dem "ab Spieltag"-Feature in admin/handler_settings.php dazu) - hier defensiv per SHOW COLUMNS geprüft und bei Bedarf angelegt, damit der Import auch auf einer Installation funktioniert, die die entsprechende Admin-Seite noch nie geöffnet hat. Live end-to-end getestet: .l98-Datei mit SP=5/TOR1=2/TOR2=1/STDA=3 importiert, korrekt als strafpunkte=-5/tore_korrektur=2/straftore=1/ab_spieltag=3 in der DB gelandet und wirkt sich nachweislich auf die berechneten Punkte aus (3 Siegpunkte - 5 Strafpunkte = -2).
- Changelog: 1.6.0 - Team-Abgleich beim Import zeigt jetzt ALLE ähnlichen vorhandenen Teams zur Auswahl an (z.B. Haupt- und Reserve-Team "FC Bayern Muenchen"/"FC Bayern Muenchen II"), statt automatisch nur den einen besten Treffer vorzuschlagen. Neue Funktion findFuzzyTeamMatches() (Mehrzahl) liefert alle Kandidaten sortiert nach Ähnlichkeit; die Review-Seite zeigt sie als Dropdown statt einer einzelnen Ja/Nein-Checkbox. import_confirm liest jetzt die gewählte Team-ID statt eines Häkchens
- Changelog: 1.5.5 - Bugfix Team-Abgleich beim Import: die reine Trigramm-Ähnlichkeit produzierte zu viele Fehltreffer zwischen thematisch völlig unverwandten, aber ähnlich langen Namen (z.B. "Norwegen" ↔ "TSG Balingen", "Schweden" ↔ "BSV Schwarz-Weiß Rehden" – beide lagen über dem alten Schwellenwert von 0.25 bei Namen >7 Zeichen). teamNamesAreFuzzyMatch() verlangt jetzt zusätzlich eine hohe normalisierte Levenshtein-Distanz (≥0.72) – beide Kennzahlen müssen unabhängig voneinander übereinstimmen, nicht nur eine. Echte Tippfehler-/Formatierungs-Varianten (z.B. "FC St Pauli" ↔ "FC St. Pauli", "Hansa Rostock" ↔ "Hansa Rostock II") erkennt die Funktion weiterhin zuverlässig
- Changelog: 1.5.4 - l98DecodeText() jetzt auch auf den Spielbericht-Link (BE-Feld) angewendet, in beiden Zweigen (KO und regulär) – relevant für "&amp;" in Query-Parametern von URLs, war bisher übersehen worden
- Changelog: 1.5.3 - Zwei Bugfixes beim .l98-Import: (1) Runden im regulären Liga-Format (Type=0) behandelten GA/GB=-1 (LMO-Legacy für "kein Ergebnis") nicht wie im KO-Zweig als null, sondern übernahmen die -1 wörtlich bis in die DB/ Admin-Oberfläche. (2) Ältere .l98-Exporte liefern Freitext (Teamnamen/ -kürzel/-mittelnamen, Liganamen, Spielnotizen, Ticker) teils schon HTML-entity-kodiert, dabei oft sogar ohne das abschließende Semikolon (z.B. "M&oumlnchengladbach" statt korrekt "M&ouml;nchengladbach"); neue l98DecodeText()-Hilfsfunktion ergänzt bei Bedarf erst das fehlende Semikolon für die gängigen Buchstaben und dekodiert dann korrekt
- Changelog: 1.5.2 - Bugfix (gefunden beim Testen der neuen Team-Abgleich-Funktion): teams_global.name hat keinen UNIQUE-Key, wodurch "INSERT ... ON DUPLICATE KEY UPDATE" bei exakter Namensgleichheit NIE griff und stattdessen stumpf ein doppeltes Team anlegte (in importL98IntoDB() UND createLigaInDB(), betraf also auch die "Liga erstellen"-Wizard-Seite, nicht nur den .l98-Import). Beide Stellen prüfen jetzt explizit per SELECT vor dem Anlegen statt sich auf einen nicht existierenden DB-Constraint zu verlassen
- Changelog: 1.5.1 - Bugfix: Fuzzy-Matching nutzte mb_strtolower()/mb_strlen()/mb_substr(), die mbstring-Extension ist nicht auf jedem Shared-Hosting garantiert vorhanden (führte zu einem Fatalen Fehler). Komplett ohne mbstring umgebaut: Umlaute/Akzente werden explizit (Groß+klein) per strtr() ersetzt, danach reicht strtolower() für den ASCII-Rest; UTF-8-Zeichen werden über preg_split('//u', ...) zerlegt statt mb_substr()
- Changelog: 1.5.0 - Vorhandene Teams werden beim .l98-Import nicht mehr überschrieben: bei exaktem Namenstreffer gelten Name/Kurz/Mittel aus der DB als maßgeblich (vorher wurden nicht-leere Werte aus der .l98-Datei übernommen). Neu: bei ungefährer (nicht exakter) Namensgleichheit wird vor dem eigentlichen Import ein Abgleichsschritt eingeschoben (siehe view_import_review.php, Action "import_review"/"import_confirm") – der Admin entscheidet dort pro Team, ob der Name aus der DB übernommen werden soll. Fuzzy-Matching (teamNormalizeName()/teamTrigramSimilarity()/findFuzzyTeamMatch()) ist ein PHP-Port derselben Logik, die im Teams-Suchfeld schon länger läuft
- Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.4.0 - Import-Handler + importL98IntoDB()-Meldungen über t() übersetzt
- Changelog: 1.3.1 - createLigaInDB(): Erfolgs-/Fehlermeldung über t() übersetzt
- Changelog: 1.3.0 - Kommentar Status-Mapping korrigiert (1=i.E., 2=n.V., war vertauscht dokumentiert)
- Changelog: 1.2.9 - [News]-Sektion (Tickertext) geparst und in liga_options gespeichert
- Changelog: 1.2.3 - KO-Import: Dummy-Team ___ fuer TA/TB=0 Paarungen: alle .l98 aus ZIP-Archiv importieren (umgeht max_file_uploads)
- Changelog: 1.2.0 - kurz/mittel nur ueberschreiben wenn nicht leer (ON DUPLICATE KEY)

## admin/handler_ko.php

- Changelog: 1.3.2 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.1 - Flash-Meldungen (save_ko_runde, fix_ko_rounds) über t() übersetzt
- Changelog: 1.3.0 - renderKoTeamPicker(): Texte über t() übersetzt
- Changelog: 1.2.2 - notiz (Spielort) im save_ko_runde gespeichert
- Changelog: 1.2.0 - koHeimatTeamA() mit playoffMode-Parameter; playoffMode aus liga_options

## admin/handler_liga.php

- Changelog: 1.9.0 - Protokolliert Liga-Löschung im neuen Audit-Log, inkl. Liganame (vor dem Löschen ermittelt). Live bestätigt: Log-Eintrag enthält korrekt Benutzername, Aktion "liga_deleted" und den Liganamen als Detail.
- Changelog: 1.8.0 - Sicherheitsfix: Open-Redirect-Lücke behoben. redirect($_POST['redirect'] ?? ...) an beiden Stellen (Liga löschen, Team-Zuordnung) durch safeRedirectTarget() abgesichert - beliebige externe Ziel-URLs (https://evil.com, //evil.com, javascript:...) werden jetzt auf den sicheren Standardwert zurückgesetzt. Live mit sechs Angriffs-Payloads und zwei legitimen Zielen getestet.
- Changelog: 1.7.0 - save_partie_teams speichert jetzt zusätzlich die eingegebenen Satzergebnisse als extra_data (mit derselben defensiven on-demand-Spaltenprüfung wie an anderer Stelle etabliert). Leere Satz-Paare werden übersprungen statt als "0:0" gespeichert.
- Changelog: 1.6.5 - save_team respektiert jetzt ein optionales POST-Feld "redirect" (nur eigene "?action=..."-Ziele erlaubt), damit der neue "Teams"-Tab in den Liga- Einstellungen nach dem Speichern dorthin zurückkehrt statt zur Liga-Detailseite (Standardverhalten bleibt unverändert, falls kein redirect-Feld gesendet wird)
- Changelog: 1.6.4 - add_team_link übersetzt jetzt die a/b/unbekannt-Richtungswahl in die tatsächliche Team-ID (newer_team_id). Neue Aktion set_team_link_direction zum nachträglichen Ändern bestehender Verknüpfungen, siehe bootstrap.php 1.9.0
- Changelog: 1.6.3 - Neue Aktionen team_links_for (JSON, Team-Verknüpfungen laden), add_team_link, delete_team_link – siehe bootstrap.php 1.8.0
- Changelog: 1.6.2 - save_ergebnisse aktualisiert jetzt liga.datum (bisher nur beim Anlegen der Liga gesetzt, nie danach) – gibt den Mini-Addons (Minitabelle/Mininext, <!--ligaDatum-->) ein echtes "letztes Speicherdatum" statt des bisherigen, immer aktuellen Tagesdatums
- Changelog: 1.6.1 - save_global_team speichert jetzt zusätzlich Vereins-URL (team_url, "https://" wird automatisch ergänzt falls fehlend) und verarbeitet einen optionalen Logo-Upload (team_logo) bzw. dessen Entfernung (remove_logo), siehe saveTeamLogoUpload()/deleteTeamLogo() in bootstrap.php
- Changelog: 1.6.0 - Bugfix delete_liga: löscht jetzt kaskadierend auch liga_options/ liga_teams/liga_team_values/liga_spieltage/liga_partien mit (das Schema hat bewusst keine FOREIGN-KEY-Constraints, vorher blieben diese Zeilen verwaist zurück). Unterstützt jetzt außerdem denselben "redirect"-POST- Parameter wie move_liga_archiv, damit der Löschen-Button auch aus der Archiv-Ansicht heraus wieder dorthin zurückführt statt zum Dashboard
- Changelog: 1.5.0 - Neuer AJAX-Handler team_by_id: Team direkt per numerischer ID nachschlagen (für die neue direkte Team-ID-Eingabe im Liga-Detail-Team-Editor)
- Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.4.0 - Alle Flash-Meldungen über t() übersetzt
- Changelog: 1.3.4 - status (n.V./i.E.) und bericht_url in save_ergebnisse gespeichert
- Changelog: 1.3.3 - team_search_all AJAX für Merge-Modal
- Changelog: 1.3.2 - AJAX Handler team_ligen: Ligen eines Teams abfragen
- Changelog: 1.3.1 - Handler bulk_archiv: mehrere Ligen auf einmal archivieren
- Changelog: 1.3.0 - Handler save_archiv_folder, delete_archiv_folder, move_liga_archiv
- Changelog: 1.2.0 - Handler save_global_team, delete_global_team, merge_teams

## admin/handler_settings.php

- Changelog: 1.6.0 - "stats"-Checkbox (Spielerstatistik-Anzeige, Tab Anzeigen/Darstellung) wird nur noch gespeichert, wenn das player-Addon installiert/aktiviert ist - die Checkbox ist sonst in view_liga_settings.php ausgeblendet (siehe dortiger Changelog-Eintrag 1.8.0) und wurde hier vorher bei JEDEM Speichern dieses Tabs unbemerkt auf '0' zurückgesetzt, selbst wenn ein per .l98-Import mitgebrachter Wert '1' erhalten bleiben sollte.
- Changelog: 1.5.0 - Speichert die gewählte Sportart in der liga-Tabelle und übernimmt beim erstmaligen Sportartwechsel die sport-spezifische Standard-Punktekonfiguration (nur falls noch keine eigene Konfiguration existiert). Eigenständige Admin-Query statt LigaService::getLigaOptions() (siehe admin/bootstrap.php).
- Changelog: 1.4.0 - Speichert jetzt die neue "ab Spieltag"-Spalte mit (Beitrag Torsten Hofmann), inkl. Schema-Migration.
- Changelog: 1.3.9 - Vorzeichen-Eingabe im Tab "Strafen" auf Dropdown (+/−) + Betragsfeld umgestellt (Minuszeichen auf Mobilgeräten oft nicht per Zifferntastatur erreichbar). Vierter Korrekturwert "Minuspunkte" ergänzt
- Changelog: 1.3.8 - Tab "Strafen" um dritten Korrekturwert "erzielte Tore" erweitert (neue Spalte tore_korrektur, Migration inklusive), damit z.B. bei Lizenzentzug Punkte UND beide Tor-Werte unabhängig korrigiert werden können
- Changelog: 1.3.7 - Neuer Tab "Strafen" (Liga-Einstellungen): Strafpunkte/Straftore je Team, eigene Tabelle liga_strafpunkte, wirkt sich nur in dieser Liga aus (siehe admin/view_liga_settings.php, computeStandings() in src/Liga/StandingsTrait.php 1.1.0)
- Changelog: 1.3.6 - Speichert die neue Einstellung ShowSpielfrei (Tab Anzeigen/Darstellung)
- Changelog: 1.3.5 - Bugfix: Tab "spielsystem" speicherte versehentlich goalfaktor/ pointsfaktor (kollidierte mit dem Grundwerte-Tab, siehe view_liga_settings.php 1.4.3) statt der neuen ET/PS-Punktefelder; jetzt PointsForWin/Draw/LostET und PointsForWin/Draw/LostPS
- Changelog: 1.3.4 - Neue Einstellung "ShowLogos" (Tab Anzeigen/Darstellung) wird jetzt mitgespeichert
- Changelog: 1.3.3 - Bugfix: "Meister wird ausgespielt"-Checkbox wurde nie gespeichert (Formularfeld heißt "Champ_enabled", Handler las aber "Champ" – dieses POST-Feld existierte nie). Neu: Randfarben der Tabellenmarkierungen ({Key}Color) werden jetzt mitgespeichert (nur gültige #rrggbb-Hexwerte)
- Changelog: 1.3.2 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.1 - Bugfix: "Kalender"-Option wird jetzt unter eigenem Schlüssel gespeichert (statt der Namenskollision mit DatC/Spieltagsdatum)
- Changelog: 1.3.0 - Flash-Meldungen über t() übersetzt
- Changelog: 1.2.2 - tab=ticker für schnelles Speichern von ticker+tickertext aus Spieltagansicht

## admin/handler_user.php

- Changelog: 1.9.0 - Speicherlogik für neue Einstellung "show_teamvergleich" ergänzt (Teamvergleich als eigenständiges Addon "teamvergleich" ausgegliedert, siehe frontend/data_liga.php 3.1.0). Analog zu show_pdf_buttons per isset($_POST[...]) geschützt - kein Reset-Risiko.
- Changelog: 1.8.0 - Protokolliert jetzt Login, Logout, Benutzer anlegen/bearbeiten/löschen und Einstellungen-Speichern im neuen Audit-Log (siehe admin/bootstrap.php).
- Changelog: 1.7.0 - Sicherheitsfix: User-Enumeration beim Passwort-Reset behoben (immer dieselbe Meldung, egal ob die E-Mail-Adresse zu einem Konto gehört) sowie Rate-Limiting ergänzt (dieselbe Infrastruktur wie beim Login-Rate-Limiting, mit einer IP-spezifischen Kennung). Bugfix während der Entwicklung: die erste Version nutzte einen für alle Anfragen GLEICHEN Platzhalter-Namen, wodurch loginRateLimitSecondsLeft()s "username = ? OR ip = ?"-Prüfung versehentlich zu einer SITE-WEITEN Sperre nach 5 Anfragen von IRGENDEINER IP geführt hätte, statt korrekt pro IP zu begrenzen - beim Testen selbst gefunden und auf eine IP-codierte Kennung umgestellt. Live geprüft: identische Rückmeldung für existierende/nicht-existierende E-Mail, Token wird nur bei echtem Konto angelegt, Rate-Limit sperrt nur die anfragende IP und lässt andere IPs unberührt.
- Changelog: 1.6.1 - Verwaisten change_password-Handler entfernt (kein Formular zeigte mehr darauf, nachdem die Karte in admin/view_settings.php entfernt wurde).
- Changelog: 1.6.0 - Login-Handler prüft jetzt vor jeder Passwortverifikation das Rate-Limit und trägt Fehlversuche ein (siehe admin/bootstrap.php).
- Changelog: 1.5.4 - save_admin_settings speichert jetzt zusätzlich show_back_link
- Changelog: 1.5.3 - save_admin_settings speichert jetzt zusätzlich show_language_switcher
- Changelog: 1.5.2 - save_admin_settings speichert jetzt zusätzlich show_pdf_buttons (neue Einstellung "PDF-Export für Besucher anzeigen?" im Besucherbereich)
- Changelog: 1.5.1 - E-Mail-Adresse jetzt auch nachträglich in der Benutzerverwaltung editierbar (create_user + edit_user), nicht mehr nur beim Erst-Setup in install.php möglich. Validiert per filter_var(FILTER_VALIDATE_EMAIL), leeres Feld löscht die hinterlegte Adresse wieder (NULL)
- Changelog: 1.5.0 - "Passwort vergessen"-Handler ergänzt: request_password_reset (E-Mail mit 4h gültigem Link verschicken, invalidiert vorherige offene Anfragen desselben Users) und do_reset_password (Token prüfen, neues Passwort setzen, Token danach verbraucht)
- Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.4.0 - save_admin_settings: aktualisiert nur tatsächlich gesendete Felder (Bugfix, da jetzt mehrere Formulare dieselbe Action nutzen); speichert active_template + allow_template_switch
- Changelog: 1.3.2 - langSessionKey('admin') statt entfernter LANG_SESSION_KEY-Konstante (i18n.php domain-fähig)
- Changelog: 1.3.1 - save_admin_settings speichert jetzt auch die Standardsprache ("language")
- Changelog: 1.3.0 - Flash-Meldungen über t() übersetzt
- Changelog: 1.2.3 - save_admin_settings Handler (Zeitzone)
- Changelog: 1.2.2 - ensureLastLoginColumn() vor UPDATE aufrufen (neue DB fix)
- Changelog: 1.2.1 - last_login Zeitstempel beim Login speichern
- Changelog: 1.2.0 - password_hash Spaltenname -> password

## admin/handler_wizard.php

- Changelog: 1.4.0 - Der Liga-Erstellen-Assistent übernimmt jetzt die in Schritt 1 gewählte Sportart durch den kompletten Ablauf und übergibt sie an createLigaInDB() - die Liga wird direkt mit der richtigen Sportart angelegt, statt sie nachträglich in den Ligaeinstellungen ändern zu müssen.
- Changelog: 1.3.2 - interne Bezeichnungen jetzt durchgehend auf Englisch ("League Key" statt der vorherigen deutschen Bezeichnung) – Funktionsname, Konstante und interner Modus-Wert entsprechend angepasst (siehe bootstrap.php/league-key_data.php)
- Changelog: 1.3.1 - Bugfix: liest Teamzahl jetzt aus dem zum Liga-Typ passenden, dauerhaft eigenen Feld ("team_count_liga"/"team_count_ko" statt eines gemeinsamen "team_count"), siehe view_wizard.php 1.3.1 für die Ursache. Liga-Maximum außerdem von 128 auf 256 angehoben (passend zum max-Attribut im Formular)
- Changelog: 1.3.0 - Reguläre Liga: Spielplan wird jetzt standardmäßig per DFB-League-Key- Muster erstellt (falls für die Teamzahl vorhanden), statt immer per Zufall. Neue Aktion "?action=create_liga&step=3&regen=1" zum Wechseln der Spielplan-Art (League Key/Zufall/kein Spielplan) auf der Vorschauseite, ohne die Teamnamen erneut eingeben zu müssen. Der bestehende Teamnamen-Handler (auch step=3+POST) musste dafür "regen" explizit ausschließen, sonst fing er die regen-Anfrage ab und interpretierte die fehlenden team_name_X-Felder fälschlich als leeren Teamnamen
- Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.0 - Flash-Meldungen über t() übersetzt
- Changelog: 1.1.2 - Team-Limit von 64 auf 128 erhöht

## admin/html_layout.php

- Changelog: 1.6.0 - Admin-Sidebar zeigt jetzt den vollständigen Copyright-/Spenden-Hinweis statt nur der schlichten Versionsnummer (Beitrag: Torsten Hofmann).
- Changelog: 1.5.0 - Link zur Benutzeransicht (home.php) in der Topbar ergänzt, zwischen Benutzername und Logout-Button, öffnet in neuem Tab (target=_blank)
- Changelog: 1.4.0 - Versionsnummer (aus composer.json) im Sidebar-Footer ergänzt
- Changelog: 1.3.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.0 - Sprachauswahl-Dropdown in Topbar (zwischen Titel und Datum); Logout-Label übersetzt
- Changelog: 1.2.0 - Logout-Button + Benutzername in Topbar; Sidebar-Footer vereinfacht

## admin/html_start.php

- Changelog: 1.4.1 - Favicon-Dateien nach assets/favicon/ verschoben, Links angepasst
- Changelog: 1.4.0 - Favicon-Verlinkung ergänzt (apple-touch-icon, android/ms-icons, manifest.json)
- Changelog: 1.3.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.0 - <html lang> dynamisch aus getCurrentLanguage(); CSS für Sprachauswahl-Dropdown
- Changelog: 1.2.1 - CSS für details/summary Archiv-Klappfunktion

## admin/league-key_data.php

- Changelog: 1.2.0 - Datei und Konstante nutzen jetzt durchgehend die englische Bezeichnung "League Key" statt der vorherigen deutschen Bezeichnung. Ebenso angepasst: die zugehörige Funktion in bootstrap.php, der interne Spielplan-Erstellungsmodus in handler_wizard.php/ view_wizard.php, sowie alle betroffenen Kommentare
- Changelog: 1.1.0 - Neues Muster für 4 Teams ergänzt: die ursprüngliche 04er-Referenzdatei war fehlerhaft (Team 4 kam nie vor), der Nutzer hat eine korrigierte Version bereitgestellt. Team 4 kommt jetzt korrekt in jeder Runde vor, jedes Team spielt gegen jedes andere Team genau zweimal (Hin- und Rückspiel), 3 Gegner × 2 = 6 Runden, geprüft. getLeagueKeyPattern()/der Wizard bieten "Schlüsselplan (DFB-Muster)" für 4-Team-Ligen jetzt automatisch an (vorher immer generateRoundRobin()-Fallback wegen fehlendem Muster)
- Changelog: 1.0.0 - Initiale Version: DFB-League-Key-Spielplanmuster fuer reguläre Ligen (6/8/10/12/14/16/18 Teams), extrahiert aus den vom Nutzer bereitgestellten Referenz-.l98-Dateien. Team-Positionen sind 0-basiert (Index in die vom Nutzer im Wizard eingegebene Teamliste). Rueckrunde ist bereits enthalten (Hin+Rueck zusammen), Heim/Gast wie im Original-Muster.

## admin/templates.php

- Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.0 - label/beschreibung/detail sind jetzt Übersetzungs-Keys (über t() aufzulösen), nicht mehr Rohtext

## admin/view_archiv.php

- Changelog: 1.6.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.6.0 - Bugfix: der "Reaktivieren"-Link im Archiv war funktionslos - er war die bereits vorausgewählte erste Option in einem <select onchange="this.form.submit()">-Dropdown, wodurch Browser beim Anklicken kein change-Ereignis auslösten (der Wert ändert sich ja nicht). Reaktivieren ist jetzt ein eigenständiger Button, das Dropdown daneben dient nur noch zum gezielten Verschieben in einen anderen Ordner.
- Changelog: 1.5.1 - Liga-ID (#123) wird jetzt auch in den Archiv-Zeilen angezeigt (sowohl innerhalb von Ordnern als auch bei Ligen ohne Ordner), analog zur ID-Spalte in der Ligen-Übersicht
- Changelog: 1.5.0 - Löschen-Button pro Liga ergänzt (in Ordnern gruppiert + ohne Ordner), nutzt den bestehenden "delete_liga"-Handler mit redirect=?action=archiv (siehe handler_liga.php 1.6.0 für das zugehörige kaskadierende Löschen)
- Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.4.0 - Alle Texte (PHP + JS) über t() übersetzt
- Changelog: 1.3.3 - Filter nach fehlenden Ergebnissen; ⚠️-Badge pro Liga
- Changelog: 1.3.0 - Funktionsnamen mit archiv-Prefix; Reihenfolge fix (Funktionen vor HTML)

## admin/view_import.php

- Changelog: 1.2.2 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.0 - Alle Texte (PHP + JS) über t() übersetzt
- Changelog: 1.1.2 - ZIP-Upload Option für Massenimport (umgeht max_file_uploads=20)
- Changelog: 1.1.1 - Mehrfach-Import (multiple); Detailergebnisse pro Datei

## admin/view_import_review.php

- Changelog: 1.2.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.2.0 - Neue Sportart-Auswahl pro Datei auf der Import-Bestätigungsseite, mit der automatisch erkannten Sportart vorausgewählt (als "erkannt" markiert) - der Admin kann sie bei Bedarf übersteuern. Die Seite erscheint jetzt auch dann, wenn keine unklaren Teamnamen vorliegen, aber eine Nicht-Fußball-Sportart erkannt wurde, damit die Erkennung nicht unbemerkt im Hintergrund passiert.
- Changelog: 1.1.0 - Zeigt jetzt ALLE ähnlichen vorhandenen Teams als Dropdown-Auswahl an (statt nur den einen besten Treffer per Ja/Nein-Checkbox), z.B. wenn Haupt- und Reserve-Team beide ähnlich zum importierten Namen sind. Zusätzliche Option "Kein passendes Team – neues Team anlegen"
- Changelog: 1.0.0 - Initiale Version: Abgleichsseite zwischen Upload und tatsächlichem Import. Wird nur angezeigt, wenn detectFuzzyTeamMatchesForImport() ungefähre (nicht exakte) Namenstreffer mit bereits vorhandenen Teams gefunden hat – der Admin entscheidet hier pro Team, ob der Name aus der DB übernommen werden soll, bevor der eigentliche Import (?action=import_confirm) läuft.

## admin/view_liga_detail.php

- Changelog: 1.10.0 - "Spielerstatistik"-Button jetzt nur noch sichtbar, wenn das player-Addon aktiviert ist (addonManager()->isEnabled('player')) - vorher unbedingt gerendert, auch ohne installiertes Addon (toter Link).
- Changelog: 1.9.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.9.0 - Team-Verwaltung (Karte + kompletter Editor) nach admin/view_liga_settings.php verschoben (neuer Tab "Teams", siehe dortiger Changelog 1.5.0) - liegt jetzt zusammen mit den übrigen Liga-Einstellungen. Neuer Schnellzugriffs-Button "Teams" oben auf dieser Seite ergänzt, verlinkt direkt auf den neuen Tab
- Changelog: 1.8.0 - Neuer Button "Spielerstatistik" verlinkt auf die neue Verwaltungsseite des gleichnamigen Addons (siehe admin/view_spielerstatistik.php)
- Changelog: 1.7.0 - Team-Editor: neue direkte Team-ID-Eingabe (Alternative zur Namenssuche) – Team-ID eintippen + "Übernehmen", schlägt per neuem team_by_id-AJAX- Endpunkt nach und übernimmt den Treffer wie ein Suchergebnis (selectDbTeam()); Fehlermeldung, falls die ID nicht existiert
- Changelog: 1.6.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.6.0 - Alle Texte (PHP + JS) über t() übersetzt
- Changelog: 1.5.0 - Archiv-Dropdown: hierarchische Ordner mit Einrückung
- Changelog: 1.4.0 - Archiv-Dropdown: dunkles Styling, Hover via CSS-Klasse, Exception-Handling
- Changelog: 1.3.0 - Archivieren-Dropdown in Aktions-Buttons; toggleArchivMenu JS
- Changelog: 1.2.0 - toggleStDatum in script-Block verschoben; DB-Suche Team-Editor

## admin/view_liga_list.php

- Changelog: 1.3.2 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.3.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.0 - Alle Texte (PHP + JS) über t() übersetzt
- Changelog: 1.2.3 - Offene Ergebnisse pro Liga; Filter nach fehlenden Ergebnissen
- Changelog: 1.2.1 - Ligen-Stat-Karte zeigt Gesamtanzahl inkl. Archiv; Link zur Gesamtübersicht
- Changelog: 1.2.0 - Teams-Stat-Karte als Link zu ?action=teams

## admin/view_liga_settings.php

- Changelog: 1.8.0 - Bugfix: die "Spielerstatistik"-Checkbox (Tab Anzeigen/Darstellung) erschien bisher unbedingt, auch wenn das zugehörige player-Addon gar nicht installiert war - dort angehakt hatte dann keinerlei sichtbare Wirkung, was verwirrend war (Übersehen bei der ursprünglichen Extraktion des Player-Addons aus dem Core, analog zum "Spielerstatistik"-Button in admin/view_liga_detail.php 1.10.0). Checkbox erscheint jetzt nur noch bei aktiviertem player-Addon (addonManager()->isEnabled('player')), in beiden betroffenen Tab-Varianten (K.o.- und Rundenliga-Modus).
- Changelog: 1.7.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.7.0 - Sportart-Dropdown im Tab Grundwerte ergänzt (Beitrag: Torsten Hofmann, Bugfix bei der Übernahme: doppeltes name="sport_type"-Feld - verstecktes Input UND Dropdown gleichzeitig im selben Formular - auf ein einziges Feld bereinigt), abschaltbar über die Admin-Einstellung show_sport_type.
- Changelog: 1.6.0 - Neue Spalte "ab Spieltag" im Strafen-Tab (Beitrag Torsten Hofmann, siehe src/Liga/StandingsTrait.php).
- Changelog: 1.5.3 - Spaltenreihenfolge im Tab "Strafen" geändert: Punkte, Minuspunkte, Erzielte Tore, Gegentore - reine Anzeigeänderung, die Felder werden weiterhin über ihren Namen ausgewertet, nicht die Position
- Changelog: 1.5.2 - Tab "Strafen": Eingabe auf Dropdown (+/−) + positives Betragsfeld umgestellt statt eines Zahlenfelds mit Minuszeichen (auf Mobilgeräten oft nicht per Zifferntastatur eingebbar). Vierte Spalte "Minuspunkte" ergänzt
- Changelog: 1.5.1 - Tab "Strafen": dritte Spalte "Erzielte Tore +/-" ergänzt, alle drei Spalten als "+/-" beschriftet (Bonus/Strafe), Erklärungstext erweitert inkl. Lizenzentzug-Beispiel
- Changelog: 1.5.0 - Neuer Tab "Teams": komplette Team-Verwaltung (Name/Mittel/Kürzel bearbeiten, DB-Suche, direkte ID-Übernahme) von der Liga-Detailseite hierher verschoben (siehe admin/view_liga_detail.php 1.9.0), damit sie zusammen mit den übrigen Liga-Einstellungen an einem Ort liegt. Läuft bewusst OHNE das äußere <form action="?action=save_liga_settings"> - jede Zeile speichert einzeln über ein eigenes <form>, verschachtelte Forms wären ungültiges HTML
- Changelog: 1.4.5 - Neuer Tab "Strafen": Strafpunkte/Straftore je Team dieser Liga verwalten (siehe admin/handler_settings.php 1.3.7)
- Changelog: 1.4.4 - Neue Einstellung "Spielfrei anzeigen" (ShowSpielfrei) im Tab Anzeigen/ Darstellung, direkt unter "Ergebnisse" - steuert, ob der "Spielfrei: TEAMNAME"-Hinweis in Ergebnisse-Ansicht und PDF-Export erscheint (siehe liga.php 3.10.3). Default "an" (kein stiller Verhaltenswechsel für bestehende Ligen, da die Anzeige bereits ohne diese Einstellung ausgeliefert wurde)
- Changelog: 1.4.3 - Bugfix: Punktesystem-Tabelle (Tab Spielsystem) hatte für "nach Verlängerung"/"nach Elfmeterschießen" nur ein einzelnes Eingabefeld (Sieg) statt der vollen S/U/N-Spalten wie im alten LMO – die beiden Felder missbrauchten zudem versehentlich goalfaktor/pointsfaktor, dieselben Schlüssel wie der Grundwerte-Tab (Dezimalstellen-Anzeige), wodurch sich beide Tabs beim Speichern gegenseitig überschrieben haben. Jetzt volles 3×3-Eingabegitter mit eigenen Schlüsseln PointsForWin/Draw/LostET bzw. PS, siehe computeStandings() 2.15.5
- Changelog: 1.4.2 - Kalender/Spielpläne, Kreuztabelle/Fieberkurven und Spielerstatistik/ Ligastatistik wieder in einzelne Tabellenzeilen aufgeteilt (statt jeweils zwei Checkboxen in einer gemeinsamen Zeile), konsistent zum Rest der Tabelle (ein Eintrag pro Zeile)
- Changelog: 1.4.1 - Neue Einstellung "Logo anzeigen" (ShowLogos) im Tab Anzeigen/Darstellung, gilt für KO- und reguläre Ligen gleichermaßen. Steuert, ob Team-Logos (siehe Teams (global) → Logo-Upload) in der Besucheransicht dieser Liga erscheinen (Tabelle, Ergebnisse, Kreuztabelle, Teamvergleich, Ligastatistik, Spielpläne)
- Changelog: 1.4.0 - Farbwähler (Color-Picker) neben jeder Tabellenmarkierung ergänzt (Champions League/-Qualifikation/Euroleague/Relegation/Absteiger/ Meister) – Farben waren bisher hartkodiert und wirkten sich zudem gar nicht auf die Besucheransicht aus. Neue Options-Schlüssel {Key}Color, Vorschau-Chip in der Admin-Ansicht zeigt jetzt die gewählte Farbe live
- Changelog: 1.3.3 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.2 - Bugfix: "Tabelle"-Checkbox fehlte in dieser Ansicht komplett, obwohl handler_settings.php den Schlüssel schon speicherte – wurde dadurch bei jedem Speichern stillschweigend auf "0" zurückgesetzt; Checkbox ergänzt
- Changelog: 1.3.1 - Bugfix: "Kalender"-Checkbox nutzte versehentlich denselben Schlüssel wie "Spieltagsdatum" (DatC) und wurde nie gespeichert; jetzt eigener Schlüssel "Kalender"
- Changelog: 1.3.0 - Alle Texte über t() übersetzt
- Changelog: 1.2.1 - Ticker-Sektion: Ein/Aus-Checkbox + Tickertext-Feld
- Changelog: 1.2.0 - KO/Liga-Verzweigung; Playoff-Modus-Einstellungen fuer KO

## admin/view_login.php

- Changelog: 1.4.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.4.0 - "Passwort vergessen?"-Link samt Modal ergänzt (E-Mail eingeben, POST an ?action=request_password_reset). Backend/Reset-Landingpage/E-Mail-Versand existierten bereits (handler_user.php, view_reset_password.php, bootstrap.php), aber ohne diesen Einstiegspunkt auf der Login-Seite war die Funktion für Besucher gar nicht erreichbar
- Changelog: 1.3.3 - Link "Zum Besucherbereich" unterhalb des Formulars ergänzt (wie im alten LMO, das dort "Wechsel in den User-Bereich" hatte)
- Changelog: 1.3.2 - Weiße Karte hinter dem Logo (dunkles Navy hatte auf dem sehr dunklen Login-Hintergrund zu wenig Kontrast)
- Changelog: 1.3.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.0 - Logo-Grafik (assets/logo.svg) statt Fußball-Emoji + Text
- Changelog: 1.2.0 - Texte über t() übersetzt

## admin/view_reset_password.php

- Changelog: 1.0.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.0.0 - Initiale Version: Landingpage für den "Passwort vergessen"-Link aus der E-Mail. Prüft den Token (vorhanden + noch nicht abgelaufen) und zeigt bei Gültigkeit ein Formular für das neue Passwort (2x Eingabe), sonst eine Fehlermeldung mit Link zurück zum Login.

## admin/view_settings.php

- Changelog: 1.8.0 - Neues Feld "show_teamvergleich" (Tab Anzeigen/Darstellung, Wortlaut/Hinweistext analog zu show_pdf_buttons) - steuert erstmals, ob Besucher das Teamvergleich-Icon bei Begegnungen sehen (gab es bisher nicht als Einstellung, das Icon erschien immer). Nur sichtbar, wenn das neue teamvergleich-Addon aktiviert ist (Auslagerung als eigenständiges Addon, siehe frontend/data_liga.php 3.1.0).
- Changelog: 1.7.0 - "PDF-Buttons anzeigen"-Feld (show_pdf_buttons, Tab Anzeigen/Darstellung) erscheint jetzt nur noch, wenn das neue pdf-export-Addon aktiviert ist (Auslagerung als eigenständiges Addon, siehe liga.php 3.13.0). Speicherung in admin/handler_user.php war bereits sicher (nutzt isset($_POST[...]) - kein Reset-Risiko wie beim früheren "stats"-Checkbox-Bug), daher dort keine Änderung nötig.
- Changelog: 1.6.0 - Neue Karte "Verbindungssicherheit (SSL/HTTPS)" im Info-Tab, zwischen Datenbankverbindung und PHP-Erweiterungen: zeigt per lmoIsHttps() (config_loader.php 1.5.0), ob die aktuelle Verbindung über HTTPS läuft. Läuft das Script ohne SSL, erscheint zusätzlich eine deutliche Warnbox mit der Empfehlung, ein SSL-Zertifikat zu aktivieren (die meisten Hoster bieten kostenlose Let's-Encrypt-Zertifikate) und dem Hinweis auf den neuen LMO_FORCE_HTTPS-Schalter, falls dieser für einen Testbetrieb gesetzt wurde.
- Changelog: 1.5.0 - Komplett umgebaut auf zweistufige Tab-Navigation: Haupt-Tabs "Optionen" und "Info" (analog zum bestehenden Tab-Muster der Wartung-Seite). "Optionen" hat zwei Unter-Tabs ("Optionen" mit Sprache/Zeitzone, "Anzeigen/Darstellung" mit Template/PDF/Sprachauswahl-Einstellungen). "Info" enthält die bisherige Datenbankverbindung-Karte plus neu: PHP-Version und eine Tabelle aller für den Betrieb relevanten PHP-Erweiterungen (geladen/nicht geladen, erforderlich/optional). Live alle drei Tab-Kombinationen geprüft, CSRF-Schutz aus der vorherigen Session blieb intakt.
- Changelog: 1.4.0 - Passwort-ändern-Karte entfernt - war doppelt zur bereits vorhandenen Passwortänderung unter Administrator/Benutzerverwaltung (die auch für den eigenen Account funktioniert). Live geprüft: Karte weg, die anderen drei Karten (Systemeinstellungen, Besucherbereich, Datenbankverbindung) unverändert vorhanden.
- Changelog: 1.3.5 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.3.4 - Neue Einstellung "Übersicht-Link anzeigen?" in der Karte Besucherbereich, entspricht "Ligaauswahl" im alten LMO
- Changelog: 1.3.3 - Neue globale Einstellung "Sprachauswahl anzeigen?" in der Karte Besucherbereich – blendet die Sprachauswahl für Besucher auf allen Seiten aus, wenn deaktiviert
- Changelog: 1.3.2 - Neue globale Einstellung "PDF-Export für Besucher anzeigen?" in der Karte Besucherbereich – blendet den PDF-Button in Ergebnisse/Tabelle/ Spielplänen/Teamvergleich für alle Liga-Typen und alle Seiten aus, wenn deaktiviert
- Changelog: 1.3.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.0 - Neue Karte "Besucherbereich": aktives Template wählen, Template-Wechsel für Besucher erlauben
- Changelog: 1.2.0 - Alle Texte über t() übersetzt (war bisher übersehen, nur Sprachauswahl war übersetzt)
- Changelog: 1.1.4 - Standardsprache-Dropdown (speicherbar) ergänzt, wie im alten LMO
- Changelog: 1.1.3 - Label auf "Zeitzone"; Hilfstext aktualisiert
- Changelog: 1.1.2 - Vollständige Zeitzonenliste wie im alten LMO
- Changelog: 1.1.1 - Zeitzone-Einstellung für Import-Konvertierung

## admin/view_spieltag.php

- Changelog: 1.4.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.4.0 - Neue Satz-Eingabe für Sportarten mit eigenen Ergebnisfeldern (aktuell Volleyball): unter jeder Partie erscheint jetzt eine "Sätze"-Zeile mit nummerierten Eingabepaaren und einem "+"-Button für weitere Sätze (Vorbild: Torstens Screenshot). Nutzt das bereits vorhandene, aber bisher nirgends angebundene SportProfile::getResultFormFields()-Interface. Bugfix während der Entwicklung: erste Version prüfte nur, ob getResultFormFields() überhaupt Einträge hat - Fußball deklariert dort aber selbst ein Feld (Halbzeit, Typ "score-pair"), wodurch die Satz-Zeile fälschlich auch bei Fußball erschienen wäre. Jetzt gezielt nach Feldtyp "dynamic-score-list" gefiltert. Live geprüft: Fußball zeigt keine Satz-Zeile, Volleyball zeigt sie korrekt, Speichern und erneutes Laden der eingegebenen Sätze bestätigt fehlerfrei.
- Changelog: 1.3.3 - Hinweistext über dem Team-Dropdown unterscheidet jetzt, ob nur Sieger ("Nur Sieger aus Runde X") oder Sieger+Verlierer angeboten werden (in der letzten Runde bei Finale + Spiel um Platz 3, siehe data_loader.php 1.6.4)
- Changelog: 1.3.2 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.3.1 - "Tabelle"-Button ebenfalls über t() übersetzt (war übersehen worden)
- Changelog: 1.3.0 - Alle Texte (PHP + JS) über t() übersetzt; koModusLabel() statt rohem KO_MODUS-Label
- Changelog: 1.2.9 - Notizfeld (Spielort) im KO-Ergebnisformular wieder angezeigt
- Changelog: 1.2.6 - Status-Dropdown (n.V./i.E.) und Spielbericht-Link bei KO-Ergebnissen
- Changelog: 1.2.5 - $isKO/$isLastRound vor h2 definiert (Undefined variable fix)
- Changelog: 1.2.4 - Tabelle-Button fix: liga_type vor $isKO-Definition prüfen
- Changelog: 1.2.3 - KO Runde 2+: Dropdown zeigt nur Vorrundensieger (mit Fallback alle Teams)
- Changelog: 1.2.1 - KlFin: letzte Runde zeigt Finale/Spiel um Platz 3 Beschriftung

## admin/view_tabelle.php

- Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.0 - Alle Texte über t() übersetzt

## admin/view_teams.php

- Changelog: 1.7.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.7.0 - Bugfix: Team-Verknüpfungen-Modal bietet jetzt eine explizite Richtungswahl ("Wer ist der heutige/aktuelle Name?") beim Anlegen einer Verknüpfung, plus ein Dropdown zum nachträglichen Ändern bei bestehenden Verknüpfungen – behebt, dass die "(heute...)"-Kennzeichnung im Teamvergleich vom Aufrufkontext abhing statt fest zu sein
- Changelog: 1.6.1 - Der 🔗-Button zeigt jetzt eine kleine Zahlen-Markierung, wenn das Team bereits Verknüpfungen hat (siehe data_loader.php 1.7.2) – auf einen Blick erkennbar, ohne jedes Team einzeln öffnen zu müssen
- Changelog: 1.6.0 - Neues Modal "Team-Verknüpfungen" (🔗-Button je Team), nicht-destruktive Alternative zum Merge: verknüpft zwei eigenständige Teams mit Typ (Umbenennung/Fusion/Abspaltung/Sonstige) + Freitext-Notiz. Nutzt dieselbe Fuzzy-Suche (mergeAllTeams/fuzzyMatch) wie das Merge-Modal
- Changelog: 1.5.0 - Neue Spalte "Logo" in der Tabelle (zeigt hochgeladenes Logo oder Platzhalter assets/img/nopic-team.svg, einheitliche Höhe 28px). Im Bearbeiten-Formular neues Feld für die Vereins-URL (🔗-Link erscheint neben dem Namen, wenn gesetzt) sowie Logo-Upload (SVG/JPG/PNG/GIF, min. 50px hoch) inkl. "Logo entfernen"-Checkbox, wenn eins hinterlegt ist. Formular braucht dafür enctype="multipart/form-data"
- Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.4.0 - Alle Texte (PHP + JS) über t() übersetzt
- Changelog: 1.3.0 - toggleDupsOnly/filterTeams in globalem Scope (waren in DOMContentLoaded eingeschlossen)

## admin/view_users.php

- Changelog: 1.5.0 - Neuer zweiter Block "Fehler/Warnungen" unterhalb des Aktivitätsprotokolls im Log-Tab, zeigt die letzten 100 Einträge aus der neuen Datei-basierten Fehlerprotokollierung (neueste zuerst, farblich nach Schweregrad markiert). Live geprüft: echte Log-Einträge (Fatal-Fehler und Warnung) erscheinen korrekt sortiert und formatiert.
- Changelog: 1.4.0 - Administrator-Seite auf zweistufige Tab-Navigation umgebaut: "Userverwaltung" (bisheriger Inhalt) und "Log" (neue Audit-Log-Anzeige mit Zeitpunkt/Benutzer/Aktion/Details/IP, paginiert). Live geprüft: beide Tabs rendern korrekt, echte Log-Einträge erscheinen mit übersetzten Aktionsnamen in der richtigen Reihenfolge.
- Changelog: 1.3.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.3.0 - E-Mail-Adresse (für "Passwort vergessen") jetzt auch nachträglich editierbar: neues Feld im "Benutzer anlegen"-Formular, neue Spalte in der Tabelle, neues Feld im Inline-Bearbeiten-Formular
- Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.0 - Alle Texte (PHP + JS-Bestätigung) über t() übersetzt
- Changelog: 1.1.1 - Spalte "Letzter Login" in Benutzerliste

## admin/view_wartung.php

- Changelog: 1.3.0 - Neuer erster Tab "Wartung" mit Wartungsmodus-Schalter, übernommen aus Torsten Hofmanns parallelem Update (siehe frontend/bootstrap.php 1.12.0 und admin/handler_backup.php 1.5.0 für die zugehörige Sperr-Logik/den Speicher-Handler). Checkbox sendet sich per onchange sofort ab (kein extra Speichern-Button), Statuszeile mit farbiger Ampel (rot=aktiv/grün=inaktiv) darunter. $tab-Auflösung von der bisherigen Zwei-Wege-Prüfung auf ein `in_array()` gegen alle drei Tabs umgestellt, Standard-Tab jetzt "wartung" statt "backup".
- Changelog: 1.2.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.2.0 - Hinweis auf der Backup-Karte, ob Team-Logos mitgesichert werden (bzw. Warnung, wenn ZipArchive fehlt). Backup-Liste in der Wiederherstellen- Karte zeigt jetzt pro Eintrag ein kleines Symbol, wenn dieses Backup auch Team-Logos enthält, siehe handler_backup.php 1.2.0
- Changelog: 1.1.0 - Bugfix: native <select multiple>/<select size> hatten browserübergreifend sehr schlechten Kontrast bei markierten Zeilen im Dark-Theme (kaum lesbar). Tabellen-Auswahl und Backup-Auswahl durch selbst gestylte Checkbox-/Radio-Listen ersetzt (volle Farbkontrolle, gleiches Verhalten/POST-Format wie zuvor, kein Backend-Änderung nötig)
- Changelog: 1.0.0 - Initiale Version: "Wartung"-Seite mit zwei Karteikartenreitern (Backup / Wiederherstellung), Layout an die phpBB-Referenz-Screenshots angelehnt. Backup-Tab zusätzlich mit einstellbarer maximaler Backup-Anzahl (ältestes Backup wird beim Überschreiten automatisch gelöscht, siehe handler_backup.php backupEnforceMaxCount())

## admin/view_wizard.php

- Changelog: 1.4.1 - Sicherheitsfix: csrfField() in jedes POST-Formular eingefügt (CSRF-Schutz, siehe admin/bootstrap.php).
- Changelog: 1.4.0 - Neues Sportart-Dropdown in Schritt 1 (Ligagrundlagen), direkt nach dem Liganamen, abschaltbar über dieselbe show_sport_type-Einstellung wie in den Liga-Einstellungen. Live geprüft: Sportart wird korrekt bis zur fertigen Liga durchgereicht (createLigaInDB() end-to-end getestet), Standardverhalten ohne Parameter (Fußball) bleibt unverändert.
- Changelog: 1.3.5 - interne Bezeichnungen jetzt durchgehend auf Englisch ("League Key" statt der vorherigen deutschen Bezeichnung) – Variablenname, Funktionsname, interner Modus-Wert und Lang-Schlüssel entsprechend angepasst (siehe bootstrap.php/league-key_data.php). Der sichtbare UI-Text hieß schon vorher "Schlüsselplan" (siehe Changelog 1.3.2) und ist unverändert
- Changelog: 1.3.4 - Vorschautabelle zeigt bei "kein Spielplan" jetzt korrekt "___" für die Leerteam-Platzhalter (-1) statt eines PHP-Fehlers/leerer Zelle, siehe bootstrap.php 1.7.1
- Changelog: 1.3.3 - Auf Wunsch zurückgenommen: Schlüsselplan-Option wieder wie ursprünglich immer sichtbar (nur ausgegraut mit "nicht verfügbar"-Hinweis) statt komplett zu verschwinden, wenn kein Muster zur Teamzahl passt
- Changelog: 1.3.2 - Umbenennung "League Key" (intern) zu "Schlüsselplan" (UI-Text, siehe lang-Dateien). Die Option erscheint jetzt komplett nicht mehr (statt nur ausgegraut mit "nicht verfügbar"-Hinweis), wenn für die gewählte Teamzahl kein Muster hinterlegt ist
- Changelog: 1.3.1 - Bugfix (der eigentliche Grund für "immer nur 16 Teams"): Zahlenfeld (Liga, freie Anzahl) und Dropdown (KO, Vorauswahl 16) hießen beide "team_count" gleichzeitig im DOM. Das umbenennende JS lief nur bei einer Änderung des Liga-Typs, nicht beim ersten Laden – blieb der Admin beim voreingestellten Liga-Typ (der Normalfall), sendete der Browser BEIDE Werte mit demselben Namen, PHP übernahm nur den letzten (das versteckte KO-Dropdown mit 16). Felder heißen jetzt dauerhaft unterschiedlich ("team_count_liga"/"team_count_ko"), keine Namensumschaltung per JS mehr nötig
- Changelog: 1.3.0 - Schritt 3 (reguläre Liga): neue Auswahl "Spielplan-Erstellung" (League Key/Zufall/kein Spielplan) oberhalb der Vorschautabelle, per eigenem Formular ohne die Teamnamen erneut einzugeben (siehe handler_wizard.php "step=3&regen=1"). League-Key-Option ist ausgegraut, wenn für die gewählte Teamzahl kein Muster hinterlegt ist
- Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.0 - Alle Texte (PHP + JS) über t() übersetzt; Vorlagen-Texte (label/beschreibung/detail) über t($tpl[...]) aufgelöst
- Changelog: 1.1.3 - KO-Modus-Auswahl nutzt koModusLabel() (übersetzt) statt rohem KO_MODUS-Label
- Changelog: 1.1.2 - KO Schritt 3: Paarungen werden automatisch angelegt; nur Spielmodus wählen

## composer.json

- Changelog: 1.9.2-beta - Core-Versionssprung nach der Sicherheitsüberarbeitung des Addon-Managers (siehe addon-run.php, addon/.htaccess, src/Addon/AddonManager.php ab 1.1.0) und dem neuen "Daten löschen"-Feature (AddonManager.php 1.4.0) - fasst mehrere Turns an Core-Änderungen zu einer neuen, für Addons referenzierbaren min_core_version zusammen.

## config_loader.php

- Changelog: 1.6.0 - Addon-Manager-Framework integriert (Beitrag Torsten Hofmann): neue Konstante LMONEXT_VERSION aus composer.json (version-Feld), wird vom AddonManager benutzt, um min_core_version aus addon.json gegen die tatsächlich laufende LMOnext-Version zu prüfen.
- Changelog: 1.5.0 - Neue Konstante LMO_FORCE_HTTPS (Standard: true), mit der die automatische HTTP→HTTPS-Weiterleitung für Testinstallationen auf einem Host ohne SSL-Zertifikat abschaltbar ist (setzbar in config.php per define() oder in der .env als LMO_FORCE_HTTPS=0) - ohne diesen Schalter wäre eine solche Installation nach dem Speichern der Konfiguration komplett unerreichbar (jeder Request würde in einen ins Leere laufenden 301-Redirect auf https:// laufen). Dafür musste der "Konfiguration laden"-Block (config.php/.env einlesen) vor den HTTPS-Erzwingung-Block gezogen werden - die Konstante muss bekannt sein, BEVOR die Weiterleitung entschieden wird; alle anderen Blöcke behalten ihre bisherige Reihenfolge. Neue wiederverwendbare Funktion lmoIsHttps() (vorher inline im Redirect-Block) wird jetzt zusätzlich vom neuen Info-Hinweis unter Administrator → Einstellungen (siehe admin/view_settings.php 1.6.0) und vom Installer (siehe install.php 2.6.0) verwendet.
- Changelog: 1.4.0 - Neue zentrale Funktionen phpIssueLogFile()/logPhpIssue()/readPhpIssueLog() für die Datei-basierte Fehler/Warnungen-Protokollierung (Datei statt DB-Tabelle, damit das Log auch bei einem Datenbankproblem noch funktioniert), mit automatischer Größenbegrenzung/Rotation.
- Changelog: 1.3.0 - Sicherheitsfix: aktive HTTP→HTTPS-Weiterleitung (301) ergänzt, unabhängig vom vorherigen Cookie-Secure-Flag-Verhalten. Erkennt sowohl direktes HTTPS als auch X-Forwarded-Proto (für Hosts, die TLS auf einem vorgelagerten Proxy terminieren). Nur im Web-Kontext aktiv, nicht bei CLI-Aufrufen. Live über einen echten HTTP-Request bestätigt: 301 mit korrekt erhaltenem Pfad/Query-String, kein Redirect bei bereits aktivem HTTPS oder korrektem X-Forwarded-Proto-Header.
- Changelog: 1.2.0 - Sicherheitsfix: PHP-Fehleranzeige (display_errors) wird jetzt unabhängig von der Hosting-Konfiguration explizit abgeschaltet, Fehler werden stattdessen weiterhin protokolliert (log_errors). Verhindert, dass interne Pfade/Details bei einem PHP-Fehler öffentlich sichtbar würden, falls das Hosting display_errors=On gesetzt hat. Live über einen echten HTTP-Request mit absichtlich ausgelöstem PHP-Warning bestätigt: Seiteninhalt bleibt sauber, keine Fehlerdetails sichtbar.
- Changelog: 1.1.0 - Neue globale Funktion renderCopyrightNotice() (Beitrag: Torsten Hofmann): zentral verfügbarer "© LMOnext <Version> <Jahr>"-Hinweis mit Spenden-Link, nutzbar von jedem Addon/Frontend/Admin. Live getestet.
- Changelog: 1.0.0 - Initiale Version: gemeinsame Konfigurations-Ladedatei für frontend/bootstrap.php und admin/bootstrap.php. Unterstützt zwei gleichwertige Betriebsarten, die install.php je nach Server-Fähigkeiten wählt (siehe dort): 1. Composer/.env-Variante (falls beim Installieren composer.phar erfolgreich lief): lädt vendor/autoload.php + .env über LMOnext\Core\Env 2. Klassische config.php-Variante (Standard-Fallback, funktioniert auf jedem Shared-Hosting ohne Shell-Zugriff) In BEIDEN Fällen werden am Ende dieselben Konstanten definiert (DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS/DB_CHARSET/DB_PREFIX) - der gesamte übrige Code (getDB(), tbl(), alle Traits unter src/Liga+Home, Addons) muss dadurch NICHT wissen, welche Variante gerade aktiv ist und bleibt komplett unverändert.

## frontend/bootstrap.php

- Changelog: 1.13.0 - Addon-Manager-Framework integriert (Beitrag Torsten Hofmann): AddonManager wird gebootet (bootFrontend() lädt frontend_handlers aller aktivierten Addons dynamisch, ersetzt die vorher festen require_once-Zeilen für addon/player/frontend_spielerstat.php und addon/tipp/tipp_lib.php).
- Changelog: 1.12.0 - Wartungsmodus fürs Frontend übernommen (Beitrag: Torsten Hofmann): ist der neue Schalter "Wartungsmodus" (Administrator → Wartung, siehe view_wartung.php 1.3.0/handler_backup.php 1.5.0) aktiv, zeigen alle Besucherseiten (home.php, liga.php sowie sämtliche Embed-Addons, da diese ebenfalls diese Datei laden) statt ihres normalen Inhalts eine gestaltete Wartungsseite (HTTP 503 + Retry-After-Header). Der Adminbereich selbst ist davon unberührt, da admin/bootstrap.php separat lädt. Prüfung zentral direkt nach der Sprachauflösung, vor Template-Engine/Datenfunktionen - kein zusätzlicher DB-Zugriff nötig (nutzt die bereits vorhandene getAdminSetting()-Zwischenspeicherung).
- Changelog: 1.11.0 - Zwei Sicherheits-Verbesserungen aus Torsten Hofmanns parallelem Update übernommen (siehe admin/bootstrap.php 1.23.0 für den dritten Punkt und SECURITY_COMPARISON.md für den vollständigen Vergleich): (1) gleiche verschärfte CSP + Referrer-Policy-Header wie im Adminbereich, frame-ancestors bleibt bewusst bei 'self' (unverändert - Haupt-Besucherseiten dürfen weiterhin same-origin eingebettet werden); (2) neuer Idle-Timeout (60 Minuten) speziell für Tippspiel-Logins (tipp_user_id) - bisher lief ein einmal angemeldeter Tipper unbegrenzt weiter, jetzt wird die Tipp-Anmeldung nach einer Stunde Inaktivität automatisch aufgehoben (betrifft nur den Tippspiel-Login, nicht die übrige Besuchersitzung).
- Changelog: 1.10.0 - Frontend-Fehler landen jetzt ebenfalls in der zentralen Fehler/Warnungen-Log-Datei: globaler Exception-Handler erweitert, neuer set_error_handler() für nicht-fatale Meldungen ergänzt (analog zum Adminbereich).
- Changelog: 1.9.0 - Sicherheitsfix: Security-Header ergänzt (X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff, Content-Security-Policy: frame-ancestors 'self'). Schützt die Hauptseiten (liga.php, home.php) vor Clickjacking über fremde iframes. Live per echtem HTTP-Request bestätigt.
- Changelog: 1.8.0 - Sicherheitsfix: CSRF-Schutz auch im Frontend (Tippspiel-Formulare) - gleiche csrfToken()/csrfField()/requireCsrf()-Funktionen wie im Adminbereich, zentral nach session_start() geprüft.
- Changelog: 1.7.0 - Sport-Profile eingebunden (require_once vor data_liga.php, da StandingsTrait/RenderViewsTrait SportRegistry referenzieren).
- Changelog: 1.6.1 - addon/tipp/tipp_lib.php zentral eingebunden (analog zu addon/player/frontend_spielerstat.php), damit tippIstAktiv()/ tippRenderSiteLink()/tippRenderHomeCard() auf jeder Besucherseite verfügbar sind - Tippspiel-Link in Header/Footer und Startseiten-Karte
- Changelog: 1.6.0 - Performance-/Robustheitsverbesserungen: getAdminSetting() liest alle Einstellungen jetzt in EINER Abfrage pro Request statt einer eigenen Abfrage pro Schlüssel. pdf_export.php wird nicht mehr pauschal für jeden Seitenaufruf eingebunden (belastete auch home.php/die Mini-Addons, die es nie brauchen), sondern nur noch direkt in liga.php, dem einzigen tatsächlichen Verwender. Session-Cookie jetzt mit HttpOnly, SameSite=Lax und (bei HTTPS) Secure. Globaler Exception-Handler: unerwartete Fehler landen im Server-Log, Besucher sehen nur eine schlichte, technikfreie Meldung statt Stacktrace/Dateipfaden
- Changelog: 1.5.0 - data_spielerstat.php eingebunden (Besucher-Ansicht für das neue Spielerstatistik-Addon, siehe admin/spielerstat_lib.php)
- Changelog: 1.4.1 - Bugfix: Die in den Admin-Einstellungen konfigurierte "Standardsprache" wurde im gesamten Besucherbereich nie berücksichtigt (getCurrentLanguage() wurde ganz am Anfang der Datei OHNE den Standardsprache-Parameter aufgerufen, bevor getAdminSetting() überhaupt verfügbar war – betraf dadurch nicht nur die neuen Addons, sondern auch home.php/liga.php direkt). Sprachauflösung jetzt an das Ende der Funktionsdefinitionen verschoben, mit getAdminSetting('language', DEFAULT_LANGUAGE) als Standardsprache-Parameter (identisches Muster wie admin/bootstrap.php)
- Changelog: 1.4.0 - pdf_export.php eingebunden (Ergebnisse-als-PDF-Export für reguläre Ligen)
- Changelog: 1.3.0 - getAppVersion() ergänzt (liest Version aus composer.json)
- Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.2.0 - Kein funktionaler Unterschied hier, aber Teil des Umbaus auf reine Platzhalter-Templates (siehe frontend/template_engine.php v2.0.0)
- Changelog: 1.1.0 - data_liga.php eingebunden (Liga-Detailseite: letzte Ergebnisse)
- Changelog: 1.0.0 - Initiale Version: eigenständiger Bootstrap für den Besucherbereich, komplett getrennt vom Adminbereich (eigene Session, eigene Sprache, eigenes Template).

## frontend/data_home.php

- Changelog: 3.0.0 - Umstrukturierung analog zu data_liga.php 3.0.0: Implementierung liegt jetzt unter src/Home/ (HomeRepository, HomeRenderer, zusammengeführt in HomeService). Diese Datei ist eine reine Kompatibilitätsschicht - alle bisherigen Funktionsnamen bleiben unverändert erhalten. Kein Composer/vendor nötig: HomeService wird direkt per require_once geladen. Alte Version vollständig als data_home_pretraits.php erhalten

## frontend/data_home_pretraits.php

- Changelog: 2.0.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 2.0.0 - Kein HTML mehr direkt in dieser Datei: renderLigaLink() und renderArchivFolderTree() nutzen jetzt renderPartial() mit template/<aktiv>/partials/liga_list_item.tpl.php bzw. archiv_folder.tpl.php. Diese Datei ist reines "Grundgerüst" (Abfragen + Schleifen), das Markup steckt komplett im Template.
- Changelog: 1.0.0 - Initiale Version: aktive Ligen, Archiv-Baum, wiederverwendbares Rendering des Archiv-Baums (von jedem Template nutzbar)

## frontend/data_liga.php

- Changelog: 3.1.0 - Teamvergleich (H2H) als eigenständiges Addon "teamvergleich" ausgegliedert. Die vier Wrapper-Funktionen getHeadToHeadMatches()/buildHeadToHeadPayload()/renderH2hIcon()/renderH2hModalAssets() delegieren jetzt über je einen Hook (liga.h2h_matches/liga.h2h_payload/liga.compare_icon/liga.compare_modal_assets) statt direkt an LigaService (das die zugehörige Trait nicht mehr enthält, siehe LigaService.php 1.1.0). resolveLinkedTeamIds()/resolveCanonicalTeamId() als globale Wrapper komplett entfernt - hatten keinen externen Aufrufer außerhalb der jetzt ausgelagerten H2H-Logik selbst. require_once auf das entfernte src/Liga/HeadToHeadTrait.php ebenfalls entfernt. Ist das Addon nicht aktiv, liefern alle vier Funktionen einfach leere Werte (kein Teamvergleich sichtbar), kein Fehler. WICHTIG für Bestandsinstallationen: nach diesem Core-Update ist der Teamvergleich nicht mehr verfügbar, bis das neue Addon zusätzlich installiert und aktiviert wird.
- Changelog: 3.0.7 - getAllLigaPartien()-Wrapper um den neuen optionalen $ligaId-Parameter erweitert.
- Changelog: 3.0.6 - BUGFIX: der findTeamLogoPathFrontend()-Kompatibilitäts-Wrapper akzeptierte nur EINEN Parameter (int $teamId) und ignorierte den neu ergänzten zweiten Parameter $forBrowser komplett - dadurch wurde ein von frontend/pdf_export.php explizit übergebenes "false" (PDF-Modus) stillschweigend verworfen, das PDF landete immer im Browser-Modus (SVG zuerst) statt beim gewünschten Raster-zuerst. Wrapper reicht $forBrowser jetzt korrekt durch.
- Changelog: 3.0.5 - renderStandingsView()-Wrapper reicht den neuen $tableMode-Parameter durch.
- Changelog: 3.0.4 - renderTabsBar()- und renderSpieltagPicker()-Wrapper reichen die neuen optionalen Parameter ($activeNr bzw. $targetView) durch.
- Changelog: 3.0.3 - renderStandingsView()-Wrapper reicht den neuen optionalen $uptoSpieltag-Parameter durch (siehe src/Liga/RenderViewsTrait.php 1.4.0).
- Changelog: 3.0.2 - computeStandings()-Wrapper reicht jetzt den neuen optionalen $ligaId- Parameter durch, neue Wrapper getLigaStrafpunkte()/setLigaStrafpunkte() ergänzt (siehe src/Liga/StandingsTrait.php 1.1.0, neues Strafpunkte-Feature)
- Changelog: 3.0.1 - renderBackLinkBlock() von liga.php hierher verschoben, damit auch home.php (Tippspiel-View) denselben "← Zur Übersicht"-Link nutzen kann
- Changelog: 3.0.0 - Große Umstrukturierung: die komplette bisherige Implementierung (~2900 Zeilen) wurde in fokussierte, einzeln verständliche Traits unter src/Liga/ aufgeteilt (LigaRepositoryTrait, TeamRepositoryTrait, HeadToHeadTrait, StandingsTrait, RenderViewsTrait usw.), zusammengeführt in der Fassade LMOnext\Liga\LigaService. Diese Datei ist jetzt eine reine Kompatibilitätsschicht: alle 61 bisherigen globalen Funktionsnamen bleiben unverändert erhalten und delegieren 1:1 an die neue Struktur - kein Aufrufer (liga.php, PDF-Export, Addons) musste angepasst werden. Bewusst OHNE Composer/vendor-Abhängigkeit: die Trait-Dateien werden direkt per require_once geladen (kein PSR-4-Autoloading nötig), damit die bisherige "Dateien hochladen und fertig"-Installation auf Shared Hosting unverändert funktioniert. Ursprüngliche Idee/Struktur-Vorlage stammt von einer Version von Torsten Hofmann (danke!), hier ohne die dortige .env/Composer- Anbindung übernommen. Alte Version vollständig als data_liga_pretraits.php erhalten
- Changelog: 2.21.0 - Performance: gezielte Speicher-Caches (pro Request) für getLigaOptions(), getLigaTeamsList() und resolveTeamNumberToId() ergänzt (siehe data_liga_pretraits.php für die vollständige Historie älterer Einträge)

## frontend/data_liga_pretraits.php

- Changelog: 2.23.0 - Die komplette Duplikat-Implementierung der H2H-Funktionen (resolveLinkedTeamIds, resolveCanonicalTeamId, getHeadToHeadMatches, buildHeadToHeadPayload, renderH2hIcon, renderH2hModalAssets - ca. 440 Zeilen, eine reine Code-Duplikation der gleichnamigen Funktionen in frontend/data_liga.php) komplett ENTFERNT und durch identische Hook-Wrapper ersetzt (siehe frontend/data_liga.php 3.1.0 für die vollständige, kommentierte Version) - Teil der Auslagerung des Teamvergleichs als eigenständiges Addon "teamvergleich". Beseitigt nebenbei die seit Langem bestehende Code-Duplikation zwischen dieser Datei und data_liga.php für diesen Funktionsbereich.
- Changelog: 2.22.0 - renderH2hModalAssets(): der PDF-Button IM Teamvergleich-Popup prüft jetzt zusätzlich den Status des neuen pdf-export-Addons (Auslagerung als eigenständiges Addon, siehe liga.php 3.13.0) - ohne diese Ergänzung wäre der Button trotz deaktiviertem Addon weiterhin sichtbar gewesen, nur der Klick darauf wäre ins Leere gelaufen (liga.php blockte den eigentlichen h2h_pdf-Aufruf bereits). Identische Ergänzung in der parallelen Implementierung src/Liga/HeadToHeadTrait.php::renderH2hModalAssets() nötig (siehe dortiger Changelog-Eintrag) - beide enthalten dieselbe H2H-Modal-Logik dupliziert.
- Changelog: 2.21.0 - Performance: gezielte Speicher-Caches (pro Request) für getLigaOptions(), getLigaTeamsList() und resolveTeamNumberToId() ergänzt - alle drei wurden bisher bei jedem Aufruf neu aus der DB gelesen, obwohl sie innerhalb eines Seitenaufrufs oft mehrfach für dieselbe Liga gebraucht werden (Tabelle, Ergebnisse, PDF-Export, Mini-Addons usw. greifen unabhängig voneinander darauf zu). Bewusst als einfache statische Caches statt einer größeren Architekturumstellung, um das Risiko gering zu halten
- Changelog: 2.20.0 - Auf Wunsch: der "(heute TEAM_HEUTE)"-Zusatz im Teamvergleich-Modal steht jetzt als eigene, kleinere Unterzeile UNTER dem Teamnamen statt inline dahinter (verhinderte vorher unschönen Zeilenumbruch mitten im Namen, siehe Screenshot des Nutzers). heim_name/gast_name in getHeadToHeadMatches() enthalten den Zusatz daher nicht mehr direkt, stattdessen neue separate Felder heim_today/gast_today. PDF-Export bleibt bewusst einzeilig (siehe pdf_export.php), da dort kein Zeilenumbruch dieser Art möglich ist
- Changelog: 2.19.0 - Bugfix: die "(heute TEAM_HEUTE)"-Kennzeichnung zeigte je nach Aufrufkontext (von welchem Spiel/welcher Liga aus der Vergleich geöffnet wurde) mal den einen, mal den anderen Namen als "heute" an, statt immer denselben. Neue Funktion resolveCanonicalTeamId() nutzt die jetzt fest hinterlegte Richtung (team_links.newer_team_id, siehe bootstrap.php 1.9.0), wenn eindeutig vorhanden – fällt sonst weiterhin auf das alte kontextabhängige Verhalten zurück
- Changelog: 2.18.0 - Neue Funktion resolveLinkedTeamIds() (transitive Auflösung von Team-Verknüpfungen, siehe team_links in admin/bootstrap.php 1.8.0). getHeadToHeadMatches() löst beide Teams jetzt zu ihrer vollständigen verknüpften Gruppe auf, bevor gesucht wird – Spiele unter früheren Namen (Umbenennung/Fusion/Abspaltung) erscheinen jetzt mit im Teamvergleich, mit "(heute TEAM_HEUTE)"-Kennzeichnung wenn der historische Name vom heute verglichenen Team abweicht. Wirkt sich überall aus, wo getHeadToHeadMatches() genutzt wird (H2H-Modal, PDF-Export, Minitabelle/Mininext-Addons)
- Changelog: 2.17.0 - Neu: "Spielfrei: TEAMNAME"-Hinweis unterhalb der Ergebnistabelle eines Spieltags, analog zum alten LMO (siehe Screenshot-Vorlage des Nutzers). findSpielfreiTeams() ermittelt betroffene Teams durch Abwesenheit (kein expliziter "Spielfrei"-Datensatz im Modell, das Team taucht im Spieltag einfach in keiner Paarung auf - identisch zur Kodierung in den alten .l98-Dateien). renderSpielfreiNote() rendert das über das neue Partial "spielfrei_note"
- Changelog: 2.16.0 - "spielerstatistik"-Reiter ergänzt (wired an den bereits reservierten "stats"-Options-Key, siehe Liga-Einstellungen); Default aus, da neue Ligen ohne Spielerstatistik-Daten sonst einen leeren Reiter zeigen würden
- Changelog: 2.15.6 - renderH2hModalAssets() blendet den PDF-Button im Teamvergleich-Modal jetzt aus, wenn die globale Einstellung "PDF-Export für Besucher anzeigen?" deaktiviert ist; JS-Zuweisung auf pdfLinkEl entsprechend gegen ein fehlendes Element abgesichert
- Changelog: 2.15.5 - Bugfix: computeStandings() ignorierte den Spielstatus (n.V./i.E.) komplett und wertete jede Partie immer mit den normalen Punktwerten (PointsForWin/Draw/Lost), entgegen dem alten LMO, das für "nach Verlängerung" und "nach Elfmeterschießen" eigene Punktetabellen erlaubte. Neue Options-Schlüssel PointsForWin/Draw/LostET (n.V.) und PointsForWin/Draw/LostPS (i.E.), fallen mangels Einstellung auf die normalen Werte zurück (keine Verhaltensänderung für bestehende Ligen ohne explizite ET/PS-Konfiguration)
- Changelog: 2.15.4 - H2H-PDF-Link im Modal übergibt jetzt "&logos=1", wenn der aktuelle Payload Logo-Pfade enthält, damit exportH2hPdf() weiß, ob Team-Logos eingebettet werden sollen (der PDF-Export ist teamübergreifend, kennt also sonst keine Liga-Einstellung an der Stelle)
- Changelog: 2.15.3 - Logo-Ordner von assets/img/Teams auf assets/img/teams umbenannt (kleingeschrieben)
- Changelog: 2.15.2 - Logo-Reihenfolge verfeinert: Heim-Spalte bei Ergebnissen/Spielplänen regulärer Ligen zeigt jetzt Name-zuerst-dann-Logo (neue Funktion partieTeamNameWithLogoReversed(), KO-Turnierbaum bleibt unverändert Logo-zuerst). Teamvergleich-Titel: Team A jetzt "Name Logo", Team B weiterhin "Logo Name" (Logos "schauen" zum vs in der Mitte). Kreuztabelle: bei aktiviertem ShowLogos zeigt die Kopfzeile NUR das Logo (kein Kürzel mehr) und die linke Spalte Logo + Mittelname statt Logo + vollem Namen
- Changelog: 2.15.1 - Neue Funktion renderTeamLogoImgWrapped(): Logo in einen <span> fester Mindestbreite verpackt, nur für die Liga-Tabelle verwendet (dort jetzt "Logo" als eigener Platzhalter getrennt von "Team"), damit die Teamnamen untereinander bündig ausgerichtet bleiben
- Changelog: 2.15.0 - Neues Feature "Logo anzeigen" (ShowLogos-Liga-Einstellung): Team-Logos (siehe Admin → Teams (global)) erscheinen jetzt in der Besucheransicht überall, wo Teams auftauchen – Tabelle, Ergebnisse, Kreuztabelle, Spielpläne (KO-Turnierbaum + regulärer Spielplan inkl. Sidebar), Ligastatistik/Teamvergleich und im Direkter-Vergleich-Modal. Neue Funktionen findTeamLogoPathFrontend()/renderTeamLogoImg()/ partieTeamNameWithLogo(); partieTeamName() selbst bleibt unverändert (liefert weiterhin reinen Text, wird auch für den PDF-Export verwendet – Logos in PDFs sind nicht Teil dieses Features)
- Changelog: 2.14.9 - PDF-Button im Direkter-Vergleich-Modal ergänzt (unten, wie bei den anderen PDF-Exporten). Payload (buildHeadToHeadPayload()) liefert jetzt teamAId/teamBId mit, der Button verlinkt auf "liga.php?h2h_pdf=1&a=..&b=.." (siehe exportH2hPdf() in pdf_export.php)
- Changelog: 2.14.8 - Teamvergleich-Modal (H2H): zeigte bisher immer hartkodiert "N. Sp.tag", auch bei KO-Turnieren. getHeadToHeadMatches() ermittelt jetzt pro Begegnung Liga-Typ + Rundenzahl + Paarungsanzahl und berechnet über roundDisplayName()/koRoundName() den korrekten Rundennamen (z.B. "Achtelfinale", "Halbfinale", "Finale") für KO-Ligen. Bei regulären Ligen werden Lang- ("Spieltag") und Kurzform ("ST") mitgegeben, responsive Umschaltung über CSS (.h2h-rd-long/.h2h-rd-short) je nach Bildschirmbreite
- Changelog: 2.14.7 - Dieselbe Leerbegegnungs-Filterung (siehe 2.14.5/2.14.6) jetzt auch im Turnierbaum ("Spielpläne" bei KO-Ligen) angewendet – galt bisher nur für die Ergebnisse-Ansicht. Das Bracket-Layout ist eine reine Box-Liste pro Runde ohne feste Positionen/Verbindungslinien, ein Weglassen einzelner Paarungen verschiebt also nichts anderes
- Changelog: 2.14.6 - Bugfix: partieIsEmptyPlaceholder() (siehe 2.14.5) prüfte nur, ob heim_id/gast_id überhaupt gesetzt sind – erkannte dadurch echte Dummy-Team-Zeilen namens "___" (die das alte LMO für Freilos- Auffüllplätze anlegt, siehe getOrCreateDummyTeam()) nicht als leer, weil dort ja eine echte (Dummy-)Team-ID vorliegt. Prüft jetzt stattdessen den aufgelösten Anzeigenamen selbst (leer ODER wörtlich "___")
- Changelog: 2.14.5 - Neue Funktion partieIsEmptyPlaceholder(): erkennt reine Leer-Begegnungen ohne jede Team-Zuordnung (z.B. bei KO-Turnieren mit auf die nächste Zweierpotenz aufgefüllter Teilnehmerzahl im alten LMO). Wird in liga.php genutzt, um diese aus der Ergebnisse-Ansicht herauszufiltern
- Changelog: 2.14.4 - Vergleichs-Icon jetzt auch im Turnierbaum (KO-Ligen, "Spielpläne"): neue Zeile zwischen Ergebnis und Anstoßtermin in jeder Paarungs-Box
- Changelog: 2.14.3 - Bugfix: Vergleichs-Modal zeigte den n.V./i.E.-Zusatz bei Begegnungen nicht an, da status weder abgefragt noch ins Payload übernommen wurde. getHeadToHeadMatches() liefert jetzt p.status mit, buildHeadToHeadPayload() gibt ihn über statusSuffix() als suffix pro Begegnung mit
- Changelog: 2.14.2 - Vergleichs-Modal: Überschrift jeder Begegnung (Datum · Liga, Spieltag) ist jetzt ein Link zur jeweiligen Liga/zum jeweiligen Spieltag (liga.php?id=…&view=ergebnisse&nr=…). getHeadToHeadMatches() liefert dafür zusätzlich liga_id, buildHeadToHeadPayload() gibt sie als ligaId pro Begegnung mit
- Changelog: 2.14.1 - Vergleichs-Modal: Sieg-Chips zeigen jetzt "Siege {Team}" über der Zahl statt nur der nackten Zahl; Vergleichs-Icon durch vom Nutzer bereitgestelltes Pfeile-Icon ersetzt (vorher zwei sich überlappende Kreise)
- Changelog: 2.14.0 - Neues "Direkter Vergleich"-Icon in Ergebnissen und Spielplänen: Klick öffnet ein Modal mit der bisherigen ligaübergreifenden Begegnungshistorie der beiden Teams (Sieg/Unentschieden/Sieg-Bilanz + Liste aller bisherigen Spiele). Neue Funktionen getHeadToHeadMatches(), buildHeadToHeadPayload(), renderH2hIcon(), renderH2hModalAssets()
- Changelog: 2.13.2 - Kreuztabelle: Klick auf eine Spalten-Kopfzelle oder ein Zeilen-Label hebt jetzt diese Mannschaft hervor (ersetzt die zuvor angezeigte favTeam-Hervorhebung client-seitig per JS). Ohne hinterlegte Lieblingsmannschaft ist beim Aufruf noch nichts markiert.
- Changelog: 2.13.1 - Kreuztabelle: Zeile und Spalte der favTeam-Mannschaft werden jetzt mit einem leichten Auswahlschatten hervorgehoben (Zeilen-Label + Spalten-Kopf in Akzentfarbe, alle Zellen der Zeile/Spalte mit hellem Hintergrund)
- Changelog: 2.13.0 - Bugfix: Liga-Einstellungen "Lieblingsmannschaft" (favTeam) und "Spielplan" (selTeam) wurden gespeichert, aber im Frontend nie ausgewertet. Neue Funktion resolveTeamNumberToId() löst die in den Einstellungen gespeicherte Team-Nummer (alphabetische Position, wie im Adminbereich) in die tatsächliche Team-ID auf. renderTeamScheduleView() wird jetzt ohne ?team=-Parameter automatisch mit dem selTeam-Team aufgerufen; renderPartieRow()/renderResultsTable() und renderStandingsView() heben die favTeam-Mannschaft jetzt fett hervor (Ergebnisse und Tabelle)
- Changelog: 2.12.3 - Fieberkurve: keine Punkt-Marker mehr auf der Linie (nur beim Hovern), Linien jetzt sanft geschwungen (tension 0.35) statt spitz/eckig, passend zum alten LMO-Look
- Changelog: 2.12.2 - Fieberkurve zeigt beim ersten Laden nur die ersten 2 Teams an (Rest über die Chart.js-Legende anklickbar dazuschaltbar), damit es bei vielen Teams nicht sofort überladen wirkt
- Changelog: 2.12.1 - Bugfix: Chart.js wird jetzt lokal aus assets/vendor/ geladen statt von einem externen CDN (cdnjs.cloudflare.com) – dort blockierten offenbar Werbeblocker/Netzwerkfilter das Skript, wodurch die Fieberkurve bei manchen Nutzern komplett leer blieb
- Changelog: 2.12.0 - Fieberkurve umgebaut: reines SVG durch interaktives Chart.js-Liniendiagramm ersetzt (CDN), da bei vielen Teams die handgebaute SVG-Version zu unübersichtlich wurde. Legende jetzt anklickbar (Team ein-/ausblenden), Tooltip beim Hovern
- Changelog: 2.11.0 - Ligastatistik ergänzt (computeTeamStreaks, computeAllTeamsStreakRecords, findExtremeMatches, computeTeamDetailStats, renderTeamStatBox, renderOverallStatsBlock, renderLigastatistikView): Team-Auswahl (0/1/2), Einzel-Statistik-Box, Zwei-Team-Vergleich mit einfacher Chancen-Schätzung (Punkteschnitt-Verhältnis) und Restprogramm-Bewertung (Ø Punkteschnitt der verbleibenden Gegner), sowie immer sichtbarer ligaweiter Statistik-Block (Spiele/Tore/Extremwerte/Serien-Rekorde)
- Changelog: 2.10.0 - Fieberkurve ergänzt (renderFieberkurveView, fieberkurveColors): reines SVG-Liniendiagramm der Tabellenposition je Spieltag, rekonstruiert die Tabelle nach jedem gespielten Spieltag aus computeStandings()
- Changelog: 2.9.1 - Kreuztabelle: Kopfzeile zeigt jetzt Kürzel (kurz) statt vollem Namen (Zeilen-Beschriftung links bleibt der volle Name); kurz-Feld wird jetzt auch durch computeStandings() durchgereicht
- Changelog: 2.9.0 - Kreuztabelle ergänzt (renderKreuztabelleView): N×N-Gitter aller Teams, sortiert nach aktueller Tabellenposition, Heim/Gast-Ergebnisse je Zelle
- Changelog: 2.8.2 - Spielplan-Sidebar zeigt jetzt den mittellangen Teamnamen (mittel) statt des Kürzels (kurz), mit Fallback auf den vollen Namen
- Changelog: 2.8.1 - Bugfix: getLigaTeamsList() lieferte leere Sidebar in der Spielpläne-Ansicht für Ligen, deren liga_teams-Zuordnung nie befüllt wurde (z.B. ältere importierte Ligen). Fallback ergänzt: Teams werden in diesem Fall direkt aus den vorhandenen Partien abgeleitet (Dummy-Team "___" ausgeschlossen)
- Changelog: 2.8.0 - Team-Spielplan-Ansicht für reguläre Ligen ergänzt (getLigaTeamsList um "kurz" erweitert, renderTeamScheduleView): Sidebar mit allen Team-Kurznamen, bei Auswahl alle Partien dieser Mannschaft chronologisch, eigenes Team fett hervorgehoben
- Changelog: 2.7.1 - Wertungshinweis-Zeile über der Tabelle entfernt
- Changelog: 2.7.0 - Tabellen-Ansicht ergänzt (getLigaTeamsList, getAllLigaPartien, computeStandings, renderStandingsView): Punkte/Sp/S/U/N/Tore/Diff je Team, sortiert nach Punkte → Tordifferenz → Tore; "tabelle"-Reiter ergänzt (Flag "Tabelle" aus den Liga-Einstellungen)
- Changelog: 2.6.1 - Info-Ansicht: Links zu Homepage + Forum ergänzt
- Changelog: 2.6.0 - Ergebnisse nach Verlängerung/Elfmeterschießen zeigen jetzt "n.V."/"i.E." (Ergebnistabelle UND Turnierbaum); getSpieltagPartien() liest dafür die status-Spalte (mit Fallback, falls noch nicht angelegt). Turnierbaum zeigt jetzt außerdem den Anstoßtermin je Paarung, wenn in den Liga-Einstellungen aktiviert (DatM), im dort gewählten Format (DatF).
- Changelog: 2.5.2 - Info-Ansicht nutzt jetzt getAppVersion() (composer.json) statt der APP_VERSION-Konstante aus config.php, damit überall dieselbe Versionsnummer angezeigt wird (Footer + Info-Seite)
- Changelog: 2.5.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 2.5.0 - Neue Tabellen-Ansicht (Liga-Typ, kein KO): renderStandingsView() berechnet die Tabelle aus allen gespielten Partien (Punkte → Tordifferenz → Tore, Wertung aus liga_options PointsForWin/PointsForDraw) und ermittelt die Platzierungsbewegung (rauf/runter/gleich) durch Vergleich mit dem Stand vor dem letzten gespielten Spieltag.
- Changelog: 2.4.0 - renderInfoView() umgebaut: zeigt jetzt eine allgemeine "Über LMOnext"- Seite (Version, Copyright, Kurzbeschreibung, Lizenz) statt Liga- Metadaten, analog zur Info-Seite des alten LMO
- Changelog: 2.3.1 - Bugfix (Fatal Error): reorderBracketPairings() begrenzt die Anzahl der zu verarbeitenden Team-Slots jetzt auf die Paarungsanzahl der aktuellen Runde. Ohne die Begrenzung wurde bei einer letzten Runde mit Finale + Spiel um Platz 3 jede Halbfinal-Paarung doppelt angefragt (als Sieger- UND Verlierer-Quelle), was zu "null"-Einträgen und einem Fatal Error in partieTeamName() führte. Zusätzlich defensiver Null-Check in renderBracketView() als Sicherheitsnetz.
- Changelog: 2.3.0 - Turnierbaum: Paarungen werden jetzt per Team-ID zwischen den Runden zugeordnet und entsprechend umsortiert (reorderBracketPairings()), statt sich auf die reine spiel_nr-Reihenfolge zu verlassen – die bildete die tatsächliche Zuführung nicht immer korrekt ab. Dummy-Platzhalter-Team wird beim Zuordnen ausgeschlossen.
- Changelog: 2.2.0 - Reiter-Navigation (renderTabsBar), Info-Ansicht (renderInfoView), Kalender-Ansicht (renderKalenderView, monatsbasiert, klickbare Spieltage/Runden) und Spielpläne-Ansicht als klassischer Turnierbaum (renderBracketView, nur KO) ergänzt
- Changelog: 2.1.0 - getLigaOptions()/ligaFlagEnabled() ergänzt: Basis für die neuen Besucher-Reiter (Kalender/Ergebnisse/Spielpläne/Info). Ohne explizite Einstellung gelten Kalender/Ergebnisse/Spielpläne als AN (neue Ligen haben diese Schlüssel noch gar nicht gesetzt).
- Changelog: 2.0.0 - Kein HTML mehr direkt in dieser Datei: renderPartieRow() sowie neue Funktionen renderResultsTable(), renderStatsBlock() und renderSpieltagPicker() bauen ihr Markup jetzt über renderPartial() aus template/<aktiv>/partials/*.tpl.php zusammen. roundDisplayName() + getMaxSpieltagNummer() aus dem Template hierher verschoben (waren vorher eine Closure in template/default/liga.php).
- Changelog: 1.4.0 - koRoundName(): benannte Stufen (Achtelfinale usw.) erst ab 16 Teams; bei mehr Teams generisches "Runde {nummer}" nach fortlaufender Rundennummer
- Changelog: 1.3.0 - KO-Rundennamen nach Teamanzahl (Achtelfinale/Viertelfinale/Halbfinale/Finale); Gruppierung nach Paarung für die letzte Runde (Finale + ggf. Spiel um Platz 3); getAllSpieltage() liefert dafür zusätzlich pairing_count
- Changelog: 1.2.0 - Tabellen-Ansicht wie alte LMO-Ergebnisseite
- Changelog: 1.1.0 - getAllSpieltage() + getSpieltagByNummer() ergänzt
- Changelog: 1.0.0 - Initiale Version: Liga-Basisdaten + letzter Spieltag mit Ergebnissen

## frontend/pdf_export.php

- Changelog: AUSGEGLIEDERT (Version 1.9.1 war der letzte Core-Stand) - diese Datei existiert im Core nicht mehr, siehe liga.php 3.13.0: PDF-Export wurde komplett als eigenständiges Addon "pdf-export" ausgegliedert. Neuer Speicherort: addon/pdf-export/pdf_export.php, eigenes CHANGELOG.md dort.
- Changelog: 1.9.1 - Wrapper-Funktionen exportTabellePdf() und buildStandingsPdf() um die neuen Parameter $tmode bzw. $landscape ergänzt, siehe PdfExporter.php 1.10.0.
- Changelog: 1.9.0 - Architektur-Umbau analog zu template_engine.php: Implementierung liegt jetzt unter src/Pdf/PdfExporter.php. WICHTIG: die Klasse wurde aus dem eigenen, vollständigen Stand neu aufgebaut statt Torstens Version zu übernehmen, da dort das Strafpunkte-Fußnoten-Feature fehlte (Torsten hatte auf einem älteren Zwischenstand aufgesetzt) - mit PDF-Export verifiziert, dass Fußnoten weiterhin korrekt erscheinen.
- Changelog: 1.8.0 - exportTabellePdf() um Torstens Heim/Auswärts/Hin-Rück-Modus erweitert (identische Logik wie im Web), dabei einen vorhandenen Bugfix nachgezogen: \u{2013} in einem einfach gequoteten String wurde nie interpretiert (PHP wertet \u{}-Escapes nur in Doppelt-Quotes aus) - der PDF-Untertitel zeigte bisher wörtlich "\u{2913}" statt eines Gedankenstrichs. Jetzt echtes UTF-8-Zeichen.
- Changelog: 1.7.2 - Regressions-Bugfix: das pauschale Entfernen von clip-path-Referenzen (siehe 1.6.x-Changelog, ursprünglich fürs FC-Bayern-Rautenmuster gedacht) hat sich als falsch herausgestellt - beim Eintracht-Braunschweig-Logo nutzt clip-path gerade dazu, Farbverlaufs-Rechtecke in die Wappenform zu beschneiden; ohne Beschneidung wurde daraus ein unkenntlicher Fleck aus großen Farbblöcken. Mit compare -metric AE bestätigt: das Entfernen erzeugte exakt das gemeldete Fehlbild, mit intaktem clip-path rendert das Logo wieder korrekt (Löwe + Rundschrift vollständig sichtbar). clip-path wird jetzt nicht mehr angetastet
- Changelog: 1.7.1 - Bugfix: manche SVG-Team-Logos wurden im PDF gar nicht oder fast leer dargestellt, obwohl kein Fehler auftrat. Ursache: fehlt auf dem Server sowohl rsvg-convert als auch ein voller librsvg-Imagick-Delegate, fällt ImageMagick auf seinen eigenen, eingeschränkten SVG-Renderer zurück - der hat bekannte Schwächen bei der allgemeinen matrix(a,b,c,d,e,f)- Transform-Form. pdfInlineSvgClassStyles() vereinfacht reine Skalierung+Verschiebung (b=0, c=0, keine Rotation/Scherung) jetzt zu getrennten translate()/scale()-Aufrufen, die von einfacheren Renderern zuverlässiger unterstützt werden. Mit den beiden Logos verifiziert: pixelgenau identisches Ergebnis (compare -metric AE = 0) gegenüber der unveränderten Fassung, wo sie ohnehin schon funktionierte
- Changelog: 1.7.0 - Strafpunkte-Begründungen erscheinen jetzt auch im PDF-Export der Tabelle, im selben Wikipedia-Stil wie in der Besucheransicht - Teamname bekommt "(N)" angehängt, unter der Tabelle erscheint eine Fußnotenliste "(N) Begründungstext" (mit Zeilenumbruch für lange Texte, neue Hilfsfunktion pdfWrapText()). Neuer optionaler Parameter $footnotes in buildStandingsPdf() (rückwärtskompatibel, Standard null = keine Verhaltensänderung für andere PDF-Exporte, die diesen Parameter nicht nutzen)
- Changelog: 1.6.9 - Bugfix: der "(heute TEAM_HEUTE)"-Zusatz wurde bisher an JEDE einzelne Ergebniszeile angehängt, wodurch die Ergebnis-Spalten im Teamvergleich-PDF unnötig breit wurden (siehe Nutzer-Feedback mit Beispiel-PDF). Steht jetzt nur noch einmal als zusammenfassender Hinweis unter dem Untertitel (neuer optionaler Parameter $noteLine in buildStandingsPdf(), Default null - betrifft die normale Tabellen-PDF nicht). Tabellenzeilen zeigen wieder die schlichten historischen Namen ohne Zusatz
- Changelog: 1.6.8 - Angepasst an die neue Feldaufteilung in getHeadToHeadMatches() (heim_today/gast_today statt im Namen enthalten, siehe data_liga.php 2.20.0) – rekonstruiert den "(heute TEAM_HEUTE)"-Zusatz weiterhin inline an den Namen angehängt, da PDF-Text keinen Zeilenumbruch dieser Art kennt
- Changelog: 1.6.7 - "Spielfrei: TEAMNAME"-Zeile jetzt auch im PDF-Export der Ergebnisse (direkt nach den Ergebniszeilen, vor der Tore-Schnitt-Zeile - gleiche Reihenfolge wie in der HTML-Ansicht), siehe liga.php 3.10.2
- Changelog: 1.6.6 - Bugfix + Verbesserung: die Logos im Teamvergleich-Titel ("TeamA Logo vs Logo TeamB") wurden immer in der kleinen Zeilen-Logo-Höhe (9.5pt) gezeichnet, obwohl der reservierte Platz für 15pt berechnet war ($drawTeamLogoAt() ignorierte $vsLogoHeightPt komplett) – dadurch wirkten sie kleiner als beabsichtigt. $drawTeamLogoAt() akzeptiert jetzt eine optionale Höhen-Override, Titel-Logos sind zusätzlich von 15pt auf 24pt vergrößert (deutlich sichtbarer neben dem 17pt-Titeltext)
- Changelog: 1.6.5 - Zwei Bugfixes: (1) pdfEstimateTextWidth() nutzte eine grobe "alle Zeichen gleich breit"-Schätzung statt echter Helvetica-Zeichenbreiten (neue Konstanten PDF_HELVETICA_WIDTHS/PDF_HELVETICA_BOLD_WIDTHS, aus den offiziellen Adobe-AFM-Metriken) – bei Namen mit überdurchschnittlich vielen breiten Zeichen (z.B. "FC Bayern München") unterschätzte die alte Formel die tatsächliche Breite um mehrere Punkt, wodurch der Text ins reservierte Logo-Feld hineinragte. (2) pdfInlineSvgClassStyles() entfernt jetzt zusätzlich clip-path-Referenzen aus SVGs – manche (insbesondere minimale) SVG-Renderer machen Elemente mit nicht auflösbarer clip-path-Referenz komplett unsichtbar statt sie nur ungeclippt darzustellen, wodurch z.B. das innere bayerische Rautenmuster im FC-Bayern-Logo fehlte
- Changelog: 1.6.4 - Bugfix: Team-Logos, die Füllfarben über CSS-Klassen im <style>-Block definieren (übliches Muster bei aus Corel Draw/Illustrator/Inkscape exportierten SVGs), erschienen auf manchen Servern als reine schwarze Silhouette – der dortige SVG-Renderer (z.B. ImageMagicks eingebauter "MSVG"-Delegate ohne echtes librsvg) unterstützt <style>-Klassen nicht zuverlässig und fällt auf die SVG-Standardfüllung Schwarz zurück. Neue Funktion pdfInlineSvgClassStyles(): schreibt die Füllfarbe VOR der Rasterisierung direkt als fill="..."-Attribut ins Element, unabhängig von der <style>-Unterstützung des jeweiligen Renderers
- Changelog: 1.6.3 - Bugfix: der reservierte Platz für Logos neben dem Teamnamen war ein fester Schätzwert (16pt) statt an der tatsächlichen Logo-Breite bemessen – bei breiteren/nicht-quadratischen Logos (z.B. FC Bayern München) reichte das nicht, wodurch das Logo mit der Ergebnis-Spalte kollidierte und abgeschnitten wirkte. logoReserve wird jetzt aus der tatsächlich breitesten geladenen Logo-Datei berechnet (in buildResultsPdf() UND buildStandingsPdf())
- Changelog: 1.6.2 - Neuer erster Best-Effort-Weg für SVG-Rasterisierung über die Imagick- PHP-Erweiterung (pdfRasterizeSvgViaImagick()), braucht kein shell_exec() und ist damit auf abgesichertem Shared-Hosting eher nutzbar als rsvg-convert. Reihenfolge: Imagick zuerst, dann rsvg-convert, dann Logo überspringen – je nachdem was auf dem jeweiligen Server verfügbar ist
- Changelog: 1.6.1 - SVG-Logos werden jetzt per Best-Effort-Aufruf des externen Tools "rsvg-convert" (falls vorhanden) zu PNG gerastert und dann wie ein normales PNG eingebettet, statt immer übersprungen zu werden. Fehlt shell_exec, GD oder rsvg-convert, bleibt das bisherige Verhalten (Logo wird übersprungen, kein Absturz). GD-Rohpixel-Extraktion in eigene Funktion pdfGdImageToRaw() ausgelagert (jetzt von PNG/GIF UND vom SVG-Rasterisierungsweg gemeinsam genutzt)
- Changelog: 1.6.0 - Team-Logos werden jetzt in allen PDF-Exporten eingebettet, wenn "Logo anzeigen" für die Liga aktiv ist (Ergebnisse, Tabelle, Spielplan, Teamvergleich) – Reihenfolge/Position spiegelt exakt die HTML-Ansicht. Neue Funktionen pdfLoadTeamLogoImage()/pdfLoadTeamLogos(): JPEG wird nativ per DCTDecode eingebettet (keine Bildbibliothek nötig), PNG/GIF nur wenn GD verfügbar ist (sonst wird das einzelne Logo übersprungen statt abzustürzen), SVG kann diese schlanke, selbstgeschriebene PDF-Engine mangels Vektor-Renderer grundsätzlich nicht einbetten. assemblePdfBytes() verallgemeinert: beliebig viele zusätzliche Bild-XObjects statt nur des einen festen LMOnext-Logos. buildResultsPdf()/buildStandingsPdf() zeichnen die Logos jetzt vor/nach dem jeweiligen Team-Namen je nach Spalte; buildStandingsPdf() kann zusätzlich eine "TeamA Logo vs Logo TeamB"- Titelzeile für den Teamvergleich-Export rendern
- Changelog: 1.5.6 - Bugfix: bei Finale + Spiel um Platz 3 war nur die zweite Überschrift ("Kleines Finale – Spiel um Platz 3") fett, die erste ("Finale") noch im alten gedämpften/normalen Stil des Einzelabschnitt-Falls. Beide Überschriften sind jetzt einheitlich fett, sobald es mehr als einen Abschnitt gibt; der normale Einzelabschnitt-Fall (z.B. "Spieltag 5 · 16.08.2025") bleibt bewusst unverändert im gedämpften Stil
- Changelog: 1.5.5 - buildResultsPdf() verallgemeinert: nimmt jetzt ein Array von Abschnitten entgegen (jeweils eigene Unterüberschrift + Tabelle + Tore-Schnitt-Zeile) statt nur einer einzigen Tabelle. exportErgebnissePdf() nimmt passend dazu jetzt $sectionSpecs statt Einzelparametern entgegen. Damit zeigt das PDF bei Finale + Spiel um Platz 3 zwei getrennte Tabellen mit jeweils eigenem Datum, statt beide Begegnungen in einer Tabelle mit einem gemeinsamen (falschen) Datumsbereich zusammenzufassen (siehe liga.php für den Aufbau der Abschnitte, mirrort die gleiche Bedingung wie die HTML-Ansicht)
- Changelog: 1.5.4 - Tabellenmarkierungen (Champions League/-Qualifikation/Euroleague/ Relegation/Absteiger/Meister, siehe Admin → Liga-Einstellungen → Tabelle) werden jetzt auch im Tabelle-PDF-Export übernommen: neuer optionaler $rowBorderColors-Parameter in buildStandingsPdf() zeichnet einen 3pt breiten farbigen Rand am linken Zeilenrand, exportTabellePdf() berechnet die Farben pro Zeile über dieselbe computeStandingsMarkerColor() wie die HTML-Ansicht
- Changelog: 1.5.3 - Neue Funktion exportH2hPdf(): PDF-Export für den direkten Vergleich zweier Teams (Head-to-Head-Modal), teamübergreifend statt an eine Liga gebunden. Titel "{TeamA} vs {TeamB}", Untertitel mit Sieg/Unentschieden-Bilanz, Tabelle mit Datum/Runde (KO-aware Rundenname via runde_label aus getHeadToHeadMatches())/Heim/Ergebnis/Gast. Nutzt dieselbe generische Tabellen-Engine wie Tabelle/Spielplan-Export
- Changelog: 1.5.2 - buildStandingsPdf() verallgemeinert: neuer optionaler $accentColIndex- Parameter (vorher fest auf die letzte Spalte/Pkt hartkodiert, jetzt abschaltbar), neue Rechtsbündig-Ausrichtung ("right") für Spalten. Neue Funktion exportSpielplanPdf(): PDF-Export für den Spielplan eines einzelnen Teams (reguläre Ligen), nutzt dieselbe generische Tabellen- Engine wie die Tabelle (Standings), Titel ist hier der Teamname
- Changelog: 1.5.1 - exportErgebnissePdf() nimmt jetzt ein bereits fertig formatiertes Runden-Label statt einer Spieltag-Nummer entgegen ("Spieltag N" für reguläre Ligen, Rundenname wie "Achtelfinale" für KO-Turniere) – damit funktioniert der PDF-Export jetzt auch für KO-Ligen (siehe liga.php)
- Changelog: 1.5.0 - Neue Funktionen buildStandingsPdf()/exportTabellePdf(): PDF-Export jetzt auch für die Tabelle (Standings) regulärer Ligen, mit denselben Formatvorgaben wie der Ergebnisse-Export (Logo, Liganame fett/farbig, zentrierte Tabelle, Zebra-Streifen, Fußzeile). 9 Spalten (#, Team, Sp, S, U, N, Tore, Diff, Pkt), Pkt-Spalte fett/Akzentfarbe wie in der HTML-Ansicht (.st-pkt)
- Changelog: 1.4.0 - Fußzeile ergänzt (zentriert, jede Seite): "© {Jahr} www.liga-manager-online.org. Alle Rechte vorbehalten. Version {Version}". Jahr über date('Y'), Version über die bereits vorhandene getAppVersion() (liest composer.json) – bei mehrseitigen PDFs wird die Fußzeile nachträglich an jeden fertigen Seiten-Content-Stream angehängt
- Changelog: 1.3.2 - Bugfix: Text saß nach dem Ascender-Fix (1.3.1) zu weit oben im Streifen statt mittig (Streifen begann direkt an der Textoberkante statt symmetrisch Platz oben+unten zu lassen). Per Pixel-Messung neu justiert: Streifen jetzt bei Grundlinie+12pt bis Grundlinie-5pt (Höhe weiter 17pt), Text dadurch vertikal zentriert im Streifen
- Changelog: 1.3.1 - Bugfix: Zeilen-Hintergrundstreifen begann nur 4pt über der Grundlinie, Großbuchstaben (Cap-Height ca. 7pt bei 9.5pt Schrift) ragten oben über den grauen Streifen hinaus. Streifen beginnt jetzt 8pt über der Grundlinie (Höhe bleibt bei 17pt, also 9pt darunter statt 13pt)
- Changelog: 1.3.0 - Ergebnis-Spalte jetzt zentriert (Header + Zeilen); die gesamte Tabelle wird horizontal auf der Seite zentriert statt am linken Rand fixiert zu sein; jede zweite Zeile bekommt einen leichten hellgrauen Hintergrund (Zebra-Streifen) zur besseren Lesbarkeit. Neue Hilfsfunktionen addTextCentered() und addRect()
- Changelog: 1.2.0 - Tabelle nur noch so breit wie der tatsächlich benötigte Inhalt (vorherige Version stretchte auf feste, teils zu breite Spaltenpositionen über nahezu die volle Seitenbreite); Spaltenbreiten werden jetzt vorab aus der längsten Zelle je Spalte geschätzt. Kopfzeilen getauscht: Liganame jetzt fett/farbig obendrüber, Spieltag-Angabe normal darunter (vorher umgekehrt)
- Changelog: 1.1.1 - Heim-Spalte jetzt rechtsbündig (Header + Zeilen), analog zur rechtsbündigen .col-heim-Spalte in der HTML-Ergebnistabelle. Neue addTextRight()-Hilfsfunktion nutzt die vorhandene pdfEstimateTextWidth()-Schätzung, um den Text an einer festen rechten Kante enden zu lassen
- Changelog: 1.1.0 - Überarbeitetes Layout: LMOnext-Logo oben links (eingebettet als vorkomprimiertes PNG->Rohbild in assets/pdf/, keine Bildbibliothek zur Laufzeit nötig), zentrierter Titel in Akzentfarbe ("Ergebnisse Spieltag N"), Liganame als Untertitel, Ergebnis jetzt im "H - G"-Format wie im Referenzbeispiel, Tore-Schnitt-Zeile am Fuß des Dokuments ergänzt (gleiche Werte wie computeSpieltagStats() auf der HTML-Seite). Teamicons bewusst noch nicht eingebunden (folgt später)
- Changelog: 1.0.1 - Bugfix: mb_strlen()/mb_substr() durch strlen()/substr() ersetzt, da mbstring auf manchem Shared-Hosting fehlen kann. Kürzung passiert jetzt erst NACH der Umwandlung nach CP1252 (Single-Byte), damit strlen() wieder der sichtbaren Zeichenzahl entspricht; komplett ohne mbstring-Abhängigkeit
- Changelog: 1.0.0 - Initiale Version: minimaler, abhängigkeitsfreier PDF-Generator (kein Composer-Paket nötig) für den "Ergebnisse als PDF"-Export bei regulären (Round-Robin-)Ligen. Baut das PDF-Dateiformat direkt in reinem PHP zusammen (Catalog/Pages/Page/Contents-Objekte + xref/trailer von Hand), Text über die PDF-Kernschriften Helvetica/Helvetica-Bold mit WinAnsiEncoding (deckt deutsche Umlaute/ß ab)

## frontend/template_engine.php

- Changelog: 3.0.0 - Architektur-Umbau (Beitrag Torsten Hofmann): die eigentliche Implementierung liegt jetzt unter src/Template/TemplateEngine.php, diese Datei ist nur noch eine dünne Kompatibilitätsschicht mit denselben globalen Funktionsnamen wie bisher. 1:1 geprüft identisches Verhalten.
- Changelog: 2.6.0 - Neuer automatischer Platzhalter "TippspielLink" (analog zu "Sprachauswahl"): renderTemplate() ruft tippRenderSiteLink() auf, das selbst prüft ob das Tippspiel aktiv ist (tippIstAktiv()) und ggf. leer bleibt - Controller/Templates müssen nichts Zusätzliches tun
- Changelog: 2.5.1 - Neue globale Einstellung "Sprachauswahl anzeigen?" ausgewertet: die Sprachauswahl im Footer/Header wird unterdrückt, wenn deaktiviert – gilt zentral für alle Templates und alle Seiten (renderTemplate() wird sowohl von home.php als auch liga.php genutzt)
- Changelog: 2.5.0 - Template-Auswahl-Dropdown vom Header in den Footer verschoben: steht jetzt direkt in der "Template: ..."-Zeile anstelle des Klartext-Namens (nur wenn der Wechsel erlaubt ist und mehr als ein Template existiert – sonst wie gehabt reiner Klartext). Der separate Header-Platzhalter "Vorlagenauswahl" entfällt dadurch wieder
- Changelog: 2.4.0 - Neue Funktion renderTemplateSwitcher(): sichtbares Dropdown, mit dem Besucher (falls in den Einstellungen erlaubt) zwischen den vorhandenen Templates wechseln können. Die Einstellung "Besucher erlauben, Template zu wechseln" schaltete bisher nur den ?template=xxx-URL-Parameter frei, ohne dass es dafür je eine sichtbare Bedienmöglichkeit für Besucher gab. Neuer automatisch befüllter Platzhalter "Vorlagenauswahl" (analog zu "Sprachauswahl"), erscheint nur bei mehr als einem verfügbaren Template
- Changelog: 2.3.0 - renderTemplate() ergänzt zusätzlich Platzhalter "TemplateZeile" (zeigt den Namen des aktiven Templates im Footer, z.B. "Template: Default")
- Changelog: 2.2.0 - renderTemplate() ergänzt Platzhalter "Version" automatisch (aus composer.json, für den Footer "LMOnext {Version}")
- Changelog: 2.1.2 - Interne Bezeichner (TEMPLATE_SESSION_KEY, globale Variable) von "olv_" auf "lmonext_" umgestellt
- Changelog: 2.1.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 2.1.0 - renderTemplate() ergänzt Platzhalter "Berechnungszeit" automatisch (Dauer Berechnungen u. Seitenaufbau, wie im alten LMO-Footer)
- Changelog: 2.0.0 - Umbau auf reine Platzhalter-Templates (wie das alte LMO mit HTML_Template_IT/<!--Platzhalter-->): Templates sind jetzt reine .tpl.php-Dateien ohne jegliches PHP – nur Markup + <!--Platzhalter-->. Alle Logik (Schleifen, Bedingungen, Datenaufbereitung) lebt in frontend/*.php ("Grundgerüst"), das fertige HTML-Fragmente an die Templates übergibt. Neu: renderPartial() für wiederkehrende Bausteine (Tabellenzeile, Ordnereintrag, Dropdown-Option, …), jeweils eine eigene .tpl.php-Datei unter template/<name>/partials/.
- Changelog: 1.0.0 - Initiale Version (PHP-Include-basiert, überholt)

## home.php

- Changelog: 2.4.0 - Addon-Manager-Framework integriert (Beitrag Torsten Hofmann): fester "?view=tippspiel"-Branch durch generischen Addon-Frontend-View-Router ersetzt (addonManager()->getFrontendView($feView)) - jedes Addon kann jetzt über frontend_views in addon.json eigene ?view=-Routen registrieren, statt fest verdrahtet zu sein. TippspielCard auf der Startseite kommt jetzt über den neuen Hook frontend.home_cards (das Tipp-Addon registriert sich selbst dafür, siehe addon/tipp/hooks_frontend.php), damit die Karte verschwindet, sobald das Addon deaktiviert wird.
- Changelog: 2.3.1 - "ZurueckLinkBlock" (Link zur Liga-Übersicht) an die Tippspiel-Route ergänzt - fehlte bisher komplett auf allen Tippspiel-Unterseiten (siehe renderBackLinkBlock() jetzt in frontend/data_liga.php 3.0.1)
- Changelog: 2.3.0 - Neue Route "?view=tippspiel": bindet das Tippspiel jetzt als View ins Template-System ein (analog zur Spielerstatistik in liga.php), statt als eigenständige Seite mit eigenem HTML/CSS. Läuft komplett getrennt von der normalen Startseite, ruft tippspielHandleRequest() (kann per redirectTo() umleiten) VOR renderTemplate() auf - siehe addon/tipp/view_tippspiel_frontend.php. Ersetzt die bisherige eigenständige addon/tipp/tipp.php
- Changelog: 2.2.0 - Neuer Platzhalter "TippspielCard": wirbt auf der Startseite fürs Tippspiel (tippRenderHomeCard(), siehe addon/tipp/tipp_lib.php 0.5.0), bleibt leer wenn keine Liga freigegeben ist
- Changelog: 2.1.0 - Die globale Einstellung "Liga-Übersicht anzeigen?" (Admin → Einstellungen → Besucherbereich, bisher nur für den "← Zur Übersicht"- Link auf der Liga-Detailseite) blendet jetzt auch die komplette Liga-Auswahl hier auf der Startseite aus (aktive Ligen + Archiv) - gedacht für Betreiber, die nur eine einzelne, feste Liga per iframe/include auf einer fremden Webseite einbinden möchten, ohne dass Besucher zu einer Gesamtübersicht gelangen können
- Changelog: 2.0.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 2.0.0 - Umbau auf reine Platzhalter-Templates: baut jetzt fertige HTML-Fragmente (aktive Ligen, Archiv-Bereich) und übergibt sie als Platzhalterwerte an renderTemplate(). Die .tpl.php-Dateien enthalten dadurch kein PHP mehr, nur noch Markup + Platzhalter.
- Changelog: 1.0.0 - Initiale Version: Besucher-Startseite (aktive Ligen + Archiv-Baum)

## install.php

- Changelog: 2.7.0 - writeConfig() ergänzt einen auskommentierten Hinweis auf die neue, optionale Konstante DEMO_MODE in jeder neu generierten config.php (Beitrag: Sicherheitsüberarbeitung, siehe addon/addon-manager/ 1.2.0 im Core-CHANGELOG) - sperrt bei Bedarf die Installation/Aktualisierung von Addon-Code über den Addon-Manager, für öffentlich zugängliche Demo-/Vorstellungs-Instanzen.
- Changelog: 2.6.0 - Neuer, optionaler Systemcheck "Verbindung über HTTPS" (blockiert die Installation nicht, nur informativ) plus deutliche Warnbox in Schritt 1, falls kein HTTPS erkannt wird (installIsHttps(), eigenständige Kopie von config_loader.php's lmoIsHttps() - der Installer bindet config_loader.php bewusst nicht ein). Hintergrund: config_loader.php leitet seit 1.4.0 jeden Request automatisch auf https:// um; läuft der Server ohne SSL-Zertifikat und wird das nicht rechtzeitig bemerkt, wäre die komplette Installation nach dem Speichern der config.php nicht mehr erreichbar. Der Hinweis erklärt den neuen Schalter define('LMO_FORCE_HTTPS', false) als Ausweg für reine Testinstallationen.
- Changelog: 2.5.0 - Neue admin_audit_log-Tabelle für frische Installationen ergänzt.
- Changelog: 2.4.0 - Neue login_attempts-Tabelle für das Login-Rate-Limiting ergänzt.
- Changelog: 2.3.0 - Vervollständigt: install.php erstellte bisher nur sport_type/extra_data (Volleyball-Erweiterung) vollständig, aber nicht die Strafpunkte-Spalten (tore_korrektur/minuspunkte_korrektur/ab_spieltag, benötigt vom "ab Spieltag"-Feature UND vom .l98-Import mit Strafpunkten) sowie status/bericht_url - diese entstanden bisher nur über verstreute Laufzeit-Migrationen. Jetzt in CREATE TABLE und Migrationsblock ergänzt, damit ein einzelner install.php-Lauf die komplette aktuelle Datenbankstruktur abdeckt. Live über die echte install.php-Webroute mit den tatsächlichen Zugangsdaten getestet (Spalten vorher gezielt entfernt, danach korrekt wiederhergestellt, keine bestehenden Daten verloren) - dabei bestätigt, dass sich install.php nach erfolgreichem Lauf wie vorgesehen selbst löscht (Sicherheitsfeature, kein Bug).
- Changelog: 2.2.0 - Sport-Profile-Migration (Beitrag: Torsten Hofmann, gegen meinen aktuellen Stand nachgezogen statt Torstens älterer Version übernommen): sport_type-Spalte auf liga, extra_data (JSON) auf liga_partien, h_tore/g_tore von TINYINT auf SMALLINT erweitert (Basketball-Ergebnisse über 127). Zwei unabhängige, nicht mit dem Sport-Feature zusammenhängende Spaltenänderungen (key_value/option_value DEFAULT '' entfernt) bewusst NICHT übernommen - technisch nicht nötig auf dieser MariaDB/MySQL-Umgebung (live geprüft) und ein leichtes Rückschritts-Risiko.
- Changelog: 2.1.1 - Neue Tabelle liga_strafpunkte ergänzt (Strafpunkte/Straftore je Team und Liga, siehe src/Liga/StandingsTrait.php 1.1.0). Auf Bestandsinstallationen wird sie zusätzlich automatisch bei Bedarf nachgezogen (ensureStrafpunkteSchema()), install.php muss dafür nicht erneut laufen
- Changelog: 2.1.0 - Ein bereits systemweit installiertes composer-Kommando (z.B. /usr/bin/composer) wird jetzt bevorzugt verwendet, falls vorhanden (findSystemComposer()) - meist besser gepflegt/aktueller als die mitgelieferte bin/composer.phar. Schlägt das fehl oder ist keins vorhanden, wird wie bisher auf bin/composer.phar zurückgefallen. Die eigentliche Prozess-Ausführung wurde in runComposerCommand() ausgelagert, damit beide Varianten dieselbe robuste Timeout/Fehlerbehandlung nutzen
- Changelog: 2.0.1 - composer.phar liegt jetzt in bin/ statt im Projekt-Hauptordner (mit .htaccess-Sperre analog zu store/), damit der Hauptordner übersichtlich bleibt. Composer selbst braucht dafür keine Anpassung, da das Arbeitsverzeichnis für die Abhängigkeitsauflösung ohnehin bewusst auf den Projekt-Hauptordner gesetzt ist (proc_open(..., __DIR__)), unabhängig davon, wo composer.phar selbst liegt
- Changelog: 2.0.0 - Optionale Composer/.env-Variante ergänzt: versucht bei der Installation automatisch, composer.phar auszuführen (composerFilesReady(), findPhpCli(), installComposerDependencies(), tryComposerSetup()). Bei Erfolg wird eine .env-Datei geschrieben (writeEnvFile()) statt der klassischen config.php - schlägt IRGENDEIN Schritt fehl (kein composer.phar, proc_open deaktiviert, Netzwerkproblem o.ä.), wird transparent auf die bisherige config.php-Variante zurückgefallen, exakt wie zuvor. Für den Nutzer identisches Ergebnis in beiden Fällen - config_loader.php (siehe dort) entscheidet zur Laufzeit, welche Variante aktiv ist
- Changelog: 1.9.0 - Zeitzonen-Auswahl direkt im Installationsformular (bei den Admin- Zugangsdaten), gruppiert nach Kontinent über PHPs eingebaute DateTimeZone::listIdentifiers() (buildTimezoneGroups()) - die gewählte Zeitzone wird sofort mit in admin_settings geschrieben, statt erst nachträglich über die Einstellungsseite gesetzt werden zu müssen
- Changelog: 1.8.0 - admin_settings wird jetzt explizit bei der Installation angelegt und mit sinnvollen Startwerten befüllt (show_back_link=1: Liga-Übersicht bei einer frischen Installation sichtbar, timezone=Europe/Berlin), statt sich erst beim ersten Admin-Seitenaufruf implizit auf die PHP-seitigen Standardwerte zu verlassen. INSERT IGNORE - überschreibt bei erneuter Installation über eine vorhandene DB keine bereits gesetzten Werte
- Changelog: 1.7.0 - Zwei fehlende Voraussetzungsprüfungen ergänzt: Schreibrecht für store/ (Datenbank-Backups, wird bei Bedarf automatisch angelegt wie der Team-Logo-Ordner) und die optionale bzip2-Erweiterung (zweites Backup-Kompressionsformat neben gzip). Außerdem: DB-Verbindungsfehler werden jetzt über translateDbError() in klare, handlungsleitende Meldungen übersetzt (nicht erreichbarer Host, falscher Zugang, fehlende Berechtigung zum Anlegen der Datenbank, unbekannter Host) statt der oft kryptischen rohen PDO-Fehlermeldung
- Changelog: 1.6.1 - Neue empfohlene (nicht blockierende) Prüfung für die ZipArchive- Erweiterung ergänzt – wird für die Team-Logo-Mitsicherung bei Backup/Wiederherstellung benötigt (siehe handler_backup.php 1.2.0)
- Changelog: 1.6.0 - Systemprüfung um die seither hinzugekommenen Anforderungen ergänzt: GD-Erweiterung (Team-Logo-Uploads/PNG-GIF-Einbettung in PDFs), Imagick/rsvg-convert (SVG-Rasterisierung für PDF-Export, rein informativ), Schreibrecht für assets/img/teams/ (wird bei Bedarf automatisch angelegt). mbstring ist nicht mehr zwingend erforderlich – der Code funktioniert inzwischen überall auch ohne (siehe data_loader.php/handler_import_export.php/pdf_export.php), zählt daher jetzt nur noch als Empfehlung statt die Installation zu blockieren. Neues 'required'-Feld pro Prüfung (Standard: true) unterscheidet Pflicht- von Empfehlungs-Prüfungen; allChecksPassed() blockiert nur noch bei fehlgeschlagenen Pflicht-Prüfungen
- Changelog: 1.5.0 - Optionales E-Mail-Feld im Administrator-Konto ergänzt (für "Passwort vergessen"), neue Spalte admin_users.email + Tabelle admin_password_resets, inkl. Migration für bestehende Installationen
- Changelog: 1.4.4 - Favicon-Dateien nach assets/favicon/ verschoben, Links angepasst
- Changelog: 1.4.3 - Favicon-Verlinkung ergänzt (Basisset: apple-icon-180, favicon-32/16)
- Changelog: 1.4.2 - Standard-Datenbank-Präfix von "olv_" auf "lmonext_" umgestellt (gilt nur für neue Installationen, siehe Hinweis zu bestehenden DBs)
- Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.4.0 - Mehrsprachigkeit: lang/i18n.php eingebunden, alle Texte über t() übersetzt, Sprachauswahl-Dropdown
- Changelog: 1.3.0 - Tabelle liga_archiv_folders; archiv_folder_id in liga
- Changelog: 1.2.0 - config.php-Generator; 2-Schritt-Assistent; Selbstlöschung

## lang/admin/de.php

- Changelog: 1.34.0 - Spielerstatistik-Sprachschlüssel (54× spst_*, ld_btn_spielerstatistik) ins player-Addon ausgelagert (addon/player/lang/de.php), da das Addon aus dem Core extrahiert ist. Zusätzlich neuer Schlüssel nav_spielerstatistik (Nav-Label des Addons) direkt dort ergänzt - dieser existierte vorher nirgends, da "Spielerstatistik" bislang kein eigener Top-Nav-Punkt war. Betraf konkret einen Bug: nach Addon-Installation stand "nav_spielerstatistik" statt Klartext in der Navigation, weil das Addon.json-Manifest lang_dir deklarierte, aber der Ordner fehlte.
- Changelog: 1.33.0 - Tippspiel-Sprachschlüssel (nav_tippspiel + 150× tipp_*) ins tipp-Addon ausgelagert (addon/tipp/lang/de.php), da das Addon jetzt aus dem Core extrahiert ist (siehe src/Addon/AddonManager.php 1.0.0, lang/i18n.php 1.2.0 für die Addon-Übersetzungs-Bridge).
- Changelog: 1.32.0 - Neuer Sprachschlüssel nav_addons für den neuen Addon-Manager-Menüpunkt (Beitrag Torsten Hofmann).
- Changelog: 1.31.0 - Neue Sprachschlüssel für die HTTPS-Warnung im Installer (install_check_https, install_https_ok, install_https_missing, install_https_warning_notice) und die neue SSL-Info-Karte im Adminbereich (settings_heading_ssl, settings_ssl_active, settings_ssl_inactive, settings_ssl_warning_text), siehe install.php 2.6.0 und admin/view_settings.php 1.6.0.
- Changelog: 1.30.0 - Neue Sprachschlüssel für den Wartungsmodus-Tab (wartung_tab_wartung, wartung_maintenance_*, wartung_flash_maintenance_*), siehe admin/view_wartung.php 1.3.0.
- Changelog: 1.29.0 - Neue Sprachschlüssel für den Fehler/Warnungen-Block.
- Changelog: 1.28.0 - Neue Sprachschlüssel für die Administrator-Tabs und das Audit-Log.
- Changelog: 1.27.0 - Reset-Erfolgsmeldung auf die Standard-Anti-Enumeration-Formulierung umgestellt ("falls diese E-Mail-Adresse... zugeordnet ist" statt einer definitiven Aussage).
- Changelog: 1.26.0 - Neue Sprachschlüssel für die Einstellungen-Tabs und die PHP-Erweiterungsanzeige.
- Changelog: 1.25.0 - Neuer Sprachschlüssel für die abgelaufene Sitzung.
- Changelog: 1.24.0 - Neuer Sprachschlüssel für die Login-Sperre.
- Changelog: 1.23.0 - Neue Sprachschlüssel für die Satz-Eingabe.
- Changelog: 1.22.0 - Neue Sprachschlüssel für die Sportart-Auswahl auf der Import-Bestätigungsseite.
- Changelog: 1.21.0 - Neue Sprachschlüssel für die Sportart-Auswahl.
- Changelog: 1.20.0 - Neue Sprachschlüssel für die Spielregeln-Link-Einstellung.
- Changelog: 1.19.0 - Neuer Sprachschlüssel für die Hinweismeldung, falls ein Tipper keine Liga abonniert hat.
- Changelog: 1.18.0 - Neue Sprachschlüssel für "Tipps nachtragen".
- Changelog: 1.17.1 - Neuer Sprachschlüssel für das "In anderen Ordner verschieben"-Dropdown im Archiv (siehe admin/view_archiv.php 1.6.0).
- Changelog: 1.17.0 - Übersetzungen für den neuen Reiter "Anzeigen/Darstellung" ergänzt.
- Changelog: 1.16.1 - Übersetzungen für die neue "ab Spieltag"-Spalte ergänzt (Torsten hatte den Schlüssel in der Ansicht verwendet, aber nie in den Sprachdateien definiert).
- Changelog: 1.16.0 - Übersetzungen für die vierte Strafen-Spalte "Minuspunkte" und die überarbeitete Dropdown-Eingabe ergänzt
- Changelog: 1.15.9 - Übersetzungen für die erweiterten Strafen/Bonus-Felder (Punkte/erzielte Tore/Gegentore, alle vorzeichenbehaftet) aktualisiert
- Changelog: 1.15.8 - Übersetzungen für den neuen "Teams"-Tab bei Liga-Einstellungen sowie den neuen Schnellzugriffs-Button auf der Liga-Detailseite ergänzt
- Changelog: 1.15.7 - Übersetzungen für den neuen Liga-Einstellungen-Tab "Strafen" (Strafpunkte/Straftore) ergänzt
- Changelog: 1.15.6 - Übersetzungen für "Newsletter/Reminder" ergänzt
- Changelog: 1.15.5 - Übersetzungen für die Tippspiel-Userverwaltung ergänzt, common_delete/ common_edit als wiederverwendbare Begriffe ergänzt
- Changelog: 1.15.4 - Übersetzungen für "Tippbare Ligen" ergänzt
- Changelog: 1.15.3 - Übersetzungen für "Was zählt bei Punktgleichheit" ergänzt
- Changelog: 1.15.2 - Bugfix: Übersetzung für "Erstes Spieltagsdatum, 0 Uhr" ergänzt (ersetzt die erfundene "kein Abgabeschluss"-Option), Annahme-Hinweistext entfernt
- Changelog: 1.15.1 - Übersetzungen für "Anmeldung" ergänzt
- Changelog: 1.15.0 - Übersetzungen für den neuen Optionen-Bereich "Tippabgabe" ergänzt
- Changelog: 1.14.9 - Übersetzungen für "Regeltechnisches" ergänzt (inkl. Hinweis auf zwei angenommene Dropdown-Wertelisten)
- Changelog: 1.14.8 - Übersetzungen für die Tippspiel-Punkteverteilung und die vier weiteren Optionen-Unterbereiche ergänzt
- Changelog: 1.14.7 - Übersetzungen für die vier Tippspiel-Karteikarten (Auswertung/ Newsletter-Reminder/Userverwaltung/Optionen) ergänzt, alter einzelner Platzhalter dafür entfernt (siehe addon/tipp/view_tippspiel.php 0.2.0)
- Changelog: 1.14.6 - Seitenleisten-Begriff "Benutzer" zu "Administrator" geändert (zur klaren Abgrenzung von der künftigen Tipper-Userverwaltung im neuen Tippspiel-Addon)
- Changelog: 1.14.5 - Übersetzungen für den neuen "Tippspiel"-Navigationspunkt ergänzt
- Changelog: 1.14.4 - Übersetzungen für die neue Zeitzonen-Auswahl im Installationsformular ergänzt
- Changelog: 1.14.3 - Beschriftung/Hinweis für "Liga-Übersicht anzeigen?" aktualisiert - die Einstellung blendet jetzt auch die Liga-Auswahl auf der Startseite aus, nicht mehr nur den "Zur Übersicht"-Link (siehe home.php 2.1.0)
- Changelog: 1.14.2 - Übersetzungen für die neue Einstellung "Übersicht-Link anzeigen?" ergänzt
- Changelog: 1.14.1 - Übersetzungen für die Richtungswahl bei Team-Verknüpfungen ergänzt
- Changelog: 1.14.0 - Übersetzungen für die neue Team-Verknüpfungen-Funktion ergänzt
- Changelog: 1.13.4 - Übersetzung für den neuen Team-Spalten-Dropdown-Hinweis im Spielerstatistik-Addon ergänzt
- Changelog: 1.13.3 - Übersetzung für die neue Einstellung "Spielfrei anzeigen" ergänzt
- Changelog: 1.13.2 - Übersetzungen für die neuen Installer-Prüfungen (store/-Schreibrecht, bzip2) und die verständlicheren DB-Verbindungsfehlermeldungen ergänzt
- Changelog: 1.13.1 - Neue Meldung für den blockierten Spielerstatistik-Import ergänzt
- Changelog: 1.13.0 - Übersetzungen für Foto-Upload und Spaltenüberschriften-Grafiken ergänzt
- Changelog: 1.12.0 - Übersetzungen für das neue Spielerstatistik-Addon ergänzt (Verwaltung, Import alter .stat/.cfg-Dateien, Team-Abgleich)
- Changelog: 1.11.6 - Übersetzung für die neue Installer-Systemprüfung "ZIP-Erweiterung" ergänzt
- Changelog: 1.11.5 - Übersetzungen für die neue Team-Logo-Mitsicherung bei Backup/ Wiederherstellung ergänzt
- Changelog: 1.11.4 - Lang-Schlüssel für die Spielplan-Erstellungsart umbenannt (interne Bezeichnungen jetzt durchgehend auf Englisch, "League Key" statt der vorherigen deutschen Bezeichnung)
- Changelog: 1.11.3 - Übersetzungen für die neue Einstellung "Sprachauswahl anzeigen?" ergänzt
- Changelog: 1.11.2 - Übersetzungen für die neue Einstellung "PDF-Export für Besucher anzeigen?" ergänzt
- Changelog: 1.11.1 - Übersetzungen für die erweiterten Installer-Systemprüfungen ergänzt (GD, SVG-Rasterisierung, Team-Logo-Ordner, "optional"-Kennzeichnung)
- Changelog: 1.11.0 - Übersetzung für die neue Einstellung "Logo anzeigen" ergänzt
- Changelog: 1.10.9 - Übersetzungen für Logo & Vereinslink bei "Teams (global)" ergänzt
- Changelog: 1.10.8 - Zusatz "Berger-Tabelle: " aus der Spielplan-Beschreibung entfernt (das ist ein Schach-Fachbegriff, gehört hier nicht rein)
- Changelog: 1.10.7 - Bezeichnung "League Key" (intern) zu "Schlüsselplan" (UI-Text) geändert (Wizard, Spielplan-Erstellungsart)
- Changelog: 1.10.6 - Übersetzungen für die neue Spielplan-Erstellungsart-Auswahl im Liga-Wizard ergänzt (League Key/Zufall/kein Spielplan)
- Changelog: 1.10.5 - Übersetzungen für die Mehrfachauswahl beim Team-Abgleich (Import) ergänzt/ angepasst: Dropdown statt Ja/Nein-Checkbox, wenn mehrere ähnliche Teams gefunden wurden
- Changelog: 1.10.4 - Übersetzungen für die neuen Farbwähler bei den Tabellenmarkierungen ergänzt
- Changelog: 1.10.3 - Übersetzung für den Hinweistext "Sieger & Verlierer aus Runde..." ergänzt (letzte Runde bei Finale + Spiel um Platz 3)
- Changelog: 1.10.2 - Fehlende Übersetzungen für die "Passwort vergessen"-Funktion ergänzt (Modal auf der Login-Seite, Reset-Landingpage, Flash-Meldungen, E-Mail-Text) – Backend/Reset-Landingpage referenzierten diese Schlüssel bereits, sie fehlten aber komplett in den Sprachdateien
- Changelog: 1.10.1 - Übersetzung für den neuen "Benutzeransicht"-Link in der Admin-Topbar ergänzt
- Changelog: 1.10.0 - Übersetzungen für den neuen Team-Namensabgleich beim .l98-Import ergänzt (Abgleichsseite zwischen Upload und tatsächlichem Import)
- Changelog: 1.9.9 - Übersetzung für den Hinweis bei Tabellenprefix-Umschreibung während der Wiederherstellung ergänzt (Backups jetzt zwischen Installationen mit unterschiedlichem Prefix portabel)
- Changelog: 1.9.8 - Übersetzungen für die neue "Wartung"-Seite ergänzt (Datenbank-Backup/ Wiederherstellung, Backup-Optionen, Tabellen-Auswahl, Fehlermeldungen)
- Changelog: 1.9.7 - Übersetzungen für die direkte Team-ID-Eingabe im Liga-Detail-Team-Editor ergänzt (Alternative zur Namenssuche)
- Changelog: 1.9.6 - Link "Zum Besucherbereich" auf der Login-Seite ergänzt
- Changelog: 1.9.5 - Beispieltext im Präfix-Hinweis von "olv_" auf "lmonext_" umgestellt
- Changelog: 1.9.4 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.9.3 - "Tabelle"-Label ergänzt (Bugfix: fehlende Checkbox in view_liga_settings.php)
- Changelog: 1.9.2 - Übersetzungen für Besucherbereich-Einstellungen (aktives Template, Template-Wechsel erlauben) ergänzt
- Changelog: 1.9.1 - tpl_bundesliga_detail: "eingleisig" entfernt (unnötiger Fachbegriff)
- Changelog: 1.9.0 - Übersetzungen für Einstellungen (view_settings.php) ergänzt (war bisher übersehen)
- Changelog: 1.8.0 - Übersetzungen für Benutzerverwaltung (view_users.php) ergänzt
- Changelog: 1.7.0 - Übersetzungen für Tabelle (view_tabelle.php) ergänzt
- Changelog: 1.6.1 - Flash-Meldung für handler_settings.php ergänzt (ungültige Liga-ID)
- Changelog: 1.6.0 - Übersetzungen für Liga-Einstellungen (view_liga_settings.php) ergänzt
- Changelog: 1.5.0 - Übersetzungen für Liga-Details (view_liga_detail.php) ergänzt
- Changelog: 1.4.1 - Flash-Meldung für handler_export.php ergänzt
- Changelog: 1.4.0 - Übersetzungen für Import (view_import.php + handler_import_export.php) ergänzt
- Changelog: 1.3.1 - Flash-Meldungen für handler_liga.php ergänzt
- Changelog: 1.3.0 - Übersetzungen für Archiv (view_archiv.php) ergänzt
- Changelog: 1.2.1 - Flash-Meldungen für handler_wizard.php + createLigaInDB()-Erfolgsmeldung ergänzt
- Changelog: 1.2.0 - Übersetzungen für Wizard (view_wizard.php) + Liga-Vorlagen (templates.php) ergänzt
- Changelog: 1.1.0 - Verschoben von lang/de.php nach lang/admin/de.php (Trennung Admin-/Besucherbereich)
- Changelog: 1.0.4 - Flash-Meldungen für handler_ko.php (Runde speichern, Runden anlegen) + sp_btn_table ergänzt
- Changelog: 1.0.3 - Übersetzungen für Spieltag/KO-Editor + koModusLabel() ergänzt
- Changelog: 1.0.2 - Übersetzungen für Teams (global) + gemeinsame Begriffe (common_*) ergänzt
- Changelog: 1.0.1 - Übersetzungen für Dashboard/Ligen-Liste ergänzt
- Changelog: 1.0.0 - Initiale Version (Referenzsprache)

## lang/admin/en.php

- Changelog: 1.33.0 - Player statistics language keys (54× spst_*, ld_btn_spielerstatistik) moved into the player add-on (addon/player/lang/en.php), see lang/admin/de.php 1.34.0. New key nav_spielerstatistik added there directly (fixes missing nav label after installing the player add-on).
- Changelog: 1.32.0 - Prediction game language keys (nav_tippspiel + 150× tipp_*) moved into the tipp add-on (addon/tipp/lang/en.php), since the add-on is now extracted from the core (see src/Addon/AddonManager.php 1.0.0, lang/i18n.php 1.2.0 for the add-on translation bridge).
- Changelog: 1.31.0 - New language key nav_addons for the new addon manager menu item (contribution: Torsten Hofmann).
- Changelog: 1.30.0 - Added language keys for the installer's HTTPS warning (install_check_https, install_https_ok, install_https_missing, install_https_warning_notice) and the new SSL info card in the admin area (settings_heading_ssl, settings_ssl_active, settings_ssl_inactive, settings_ssl_warning_text), see install.php 2.6.0 and admin/view_settings.php 1.6.0.
- Changelog: 1.29.0 - Added language keys for the new maintenance mode tab (wartung_tab_wartung, wartung_maintenance_*, wartung_flash_maintenance_*), see admin/view_wartung.php 1.3.0.
- Changelog: 1.28.0 - Added language keys for the errors/warnings block.
- Changelog: 1.27.0 - Added language keys for the Administrator tabs and the audit log.
- Changelog: 1.26.0 - Updated the reset-success message to the standard anti-enumeration phrasing.
- Changelog: 1.25.0 - Added language keys for the settings tabs and the PHP extension display.
- Changelog: 1.24.0 - Added language key for the expired session message.
- Changelog: 1.23.0 - Added language key for the login lockout message.
- Changelog: 1.22.0 - Added language keys for the set-score entry.
- Changelog: 1.21.0 - Added language keys for the sport selection on the import review page.
- Changelog: 1.20.0 - Added language keys for the sport selection.
- Changelog: 1.19.0 - Added language keys for the game-rules-link setting.
- Changelog: 1.18.0 - Added the notice message for tippers without a league subscription.
- Changelog: 1.17.0 - Added language keys for "Enter tips".
- Changelog: 1.16.1 - Added the "Move to another folder" dropdown label for the archive (see admin/view_archiv.php 1.6.0).
- Changelog: 1.16.0 - Added translations for the new "Display" tab.
- Changelog: 1.15.1 - Added translations for the new "from matchday" column (the key was used in the view but never defined in the language files).
- Changelog: 1.15.0 - Added translations for the fourth "Minus points" penalty column and the reworked dropdown-based input
- Changelog: 1.14.9 - Updated translations for the extended penalty/bonus fields (points/goals scored/goals against, all signed)
- Changelog: 1.14.8 - Added translations for the new "Teams" tab in league settings and the new quick-access button on the league detail page
- Changelog: 1.14.7 - Added translations for the new "Penalties" liga settings tab (penalty points/goals)
- Changelog: 1.14.6 - Added translations for "Newsletter/Reminder"
- Changelog: 1.14.5 - Added translations for the prediction game user management, added common_delete/common_edit shared terms
- Changelog: 1.14.4 - Added translations for "Tippable Leagues"
- Changelog: 1.14.3 - Added translations for "Tie-Breaking"
- Changelog: 1.14.2 - Bugfix: added translation for "First matchday date, midnight" (replaces the made-up "no deadline" option), removed the assumption hint text
- Changelog: 1.14.1 - Added translations for "Registration"
- Changelog: 1.14.0 - Added translations for the new "Tip Submission" options section
- Changelog: 1.13.9 - Added translations for "Rules & Technical"
- Changelog: 1.13.8 - Added translations for the prediction game point distribution and the four other options sub-sections
- Changelog: 1.13.7 - Added translations for the four Prediction Game tabs (Standings/ Newsletter-Reminder/User Management/Options), removed the old single placeholder for it (see addon/tipp/view_tippspiel.php 0.2.0)
- Changelog: 1.13.6 - Sidebar label "Users" changed to "Administrators" (to clearly distinguish from the future tipper user management in the new Prediction Game addon)
- Changelog: 1.13.5 - Added translations for the new "Prediction Game" nav entry
- Changelog: 1.13.4 - Added translations for the new timezone selector in the installer
- Changelog: 1.13.3 - Updated label/hint for "Show league overview?" - now also hides the league selection on the homepage, not just the back-link (see home.php 2.1.0)
- Changelog: 1.13.2 - Added translations for the new "Show back to overview link?" setting
- Changelog: 1.13.1 - Added translations for the team-link direction feature
- Changelog: 1.13.0 - Added translations for the new team links feature
- Changelog: 1.12.4 - Added translation for the new team-column dropdown hint in the Player Statistics addon
- Changelog: 1.12.3 - Added translation for the new "Show bye teams" setting
- Changelog: 1.12.2 - Added translations for the new installer checks (store/ write permission, bzip2) and the clearer DB connection error messages
- Changelog: 1.12.1 - Added message for the blocked player-stats import
- Changelog: 1.12.0 - Added translations for photo upload and column-header images
- Changelog: 1.11.0 - Added translations for the new player-stats addon (management, legacy .stat/.cfg import, team matching)
- Changelog: 1.10.15 - Added translation for the new "ZIP extension" installer check
- Changelog: 1.10.14 - Added translations for the new team-logo backup/restore inclusion
- Changelog: 1.10.13 - Renamed the fixture-generation-mode lang key (user request: internal naming now consistently uses the English term "League Key" instead of the previous German term)
- Changelog: 1.10.12 - Added translations for the new "Show language selector?" setting
- Changelog: 1.10.11 - Added translations for the new "Show PDF export to visitors?" setting
- Changelog: 1.10.10 - Added translations for the extended installer system checks (GD, SVG rasterization, team logo directory, "optional" label)
- Changelog: 1.10.9 - Added translation for the new "Show logo" setting
- Changelog: 1.10.8 - Added translations for logo & club link on "Teams (global)"
- Changelog: 1.10.7 - Renamed "Fixture key ring" to "Fixture plan" label (wizard, schedule creation mode), matching the German rename to "Schlüsselplan"
- Changelog: 1.10.6 - Added translations for the new schedule-creation-mode selector in the league wizard (fixture key ring/random/no schedule)
- Changelog: 1.10.5 - Added/updated translations for multi-candidate team matching during import: dropdown instead of a yes/no checkbox when several similar teams were found
- Changelog: 1.10.4 - Added translations for the new color pickers on the table markers
- Changelog: 1.10.3 - Added translation for the "Winners & losers from round..." hint text (final round with final + 3rd-place match)
- Changelog: 1.10.2 - Added missing translations for the "forgot password" feature (login-page modal, reset landing page, flash messages, email text) – the backend/ reset landing page already referenced these keys, but they were entirely missing from the language files
- Changelog: 1.10.1 - Added translation for the new "Visitor view" link in the admin topbar
- Changelog: 1.10.0 - Added translations for the new team name matching step during .l98 import (review screen between upload and the actual import)
- Changelog: 1.9.9 - Added translation for the table-prefix remap notice during restore (backups are now portable between installs with a different prefix)
- Changelog: 1.9.8 - Translations for the new "Maintenance" page added (database backup/ restore, backup options, table selection, error messages)
- Changelog: 1.9.7 - Translations for direct team-ID entry in the league detail team editor added (alternative to name search)
- Changelog: 1.9.6 - Link "Go to visitor area" added on the login page
- Changelog: 1.9.5 - Example text in prefix hint changed from "olv_" to "lmonext_"
- Changelog: 1.9.4 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.9.3 - "Tabelle" label added (bugfix: missing checkbox in view_liga_settings.php)
- Changelog: 1.9.2 - Translations for visitor area settings (active template, allow template switch) added
- Changelog: 1.9.1 - tpl_bundesliga_detail: reworded, dropped awkward "single" from "single round-robin"
- Changelog: 1.9.0 - Translations for settings (view_settings.php) added (previously missed)
- Changelog: 1.8.0 - Translations for user management (view_users.php) added
- Changelog: 1.7.0 - Translations for table (view_tabelle.php) added
- Changelog: 1.6.1 - Flash message for handler_settings.php added (invalid league ID)
- Changelog: 1.6.0 - Translations for league settings (view_liga_settings.php) added
- Changelog: 1.5.0 - Translations for league details (view_liga_detail.php) added
- Changelog: 1.4.1 - Flash message for handler_export.php added
- Changelog: 1.4.0 - Translations for import (view_import.php + handler_import_export.php) added
- Changelog: 1.3.1 - Flash messages for handler_liga.php added
- Changelog: 1.3.0 - Translations for archive (view_archiv.php) added
- Changelog: 1.2.1 - Flash messages for handler_wizard.php + createLigaInDB() success message added
- Changelog: 1.2.0 - Translations for wizard (view_wizard.php) + league templates (templates.php) added
- Changelog: 1.1.0 - Moved from lang/en.php to lang/admin/en.php (separation of admin/visitor areas)
- Changelog: 1.0.4 - Flash messages for handler_ko.php (save round, create rounds) + sp_btn_table added
- Changelog: 1.0.3 - Translations for matchday/KO editor + koModusLabel() added
- Changelog: 1.0.2 - Translations for teams (global) + common terms (common_*) added
- Changelog: 1.0.1 - Translations for dashboard/league list added
- Changelog: 1.0.0 - Initial version

## lang/frontend/de.php

- Changelog: 1.50.1 - KRITISCHER Bugfix zu 1.50.0: die 7 liga_weekday_*-Schlüssel wurden dort fälschlich als "nur vom viewer-Addon genutzt" eingestuft und aus dem Core entfernt - tatsächlich baut aber auch die Core-eigene Kalenderansicht (src/Liga/RenderViewsTrait.php UND frontend/data_liga_pretraits.php, jeweils die Wochentags-Kopfzeile) sie dynamisch über ein Array + Variable auf (`tf($key)` statt `tf('liga_weekday_mo')` direkt) - diese Nutzungsform hatte die automatisierte Prüfung übersehen, da sie nur nach direkten Literal-Aufrufen suchte. Bei deaktiviertem viewer-Addon hätte der Core-Kalender dadurch rohe Schlüsselnamen statt "Mo, Di, Mi, ..." angezeigt. Schlüssel wieder ergänzt - bleiben jetzt bewusst SOWOHL im Core als auch im viewer-Addon (addon/viewer/lang/), da sie tatsächlich von beiden gebraucht werden.
- Changelog: 1.50.0 - Systematische Nachprüfung aller übrigen Addons ergab 8 weitere, tatsächlich exklusive Sprachschlüssel: liga_stand_datum ins mini-Addon (addon/mini/lang/de.php) verschoben; liga_weekday_mo/di/mi/do/fr/sa/so (7 Schlüssel) ins viewer-Addon (addon/viewer/lang/de.php) verschoben.
- Changelog: 1.49.0 - Bei genauerer Prüfung waren 11 Sprachschlüssel entgegen erster Einschätzung doch addon-exklusiv (keine echte Verwendung außerhalb von PdfExporter.php/HeadToHead.php): liga_col_nr, liga_pdf_footer, h2h_pdf_renamed_note ins pdf-export-Addon (addon/pdf-export/lang/de.php) verschoben; liga_col_spieltag_long, liga_h2h_icon_title, liga_h2h_modal_title, liga_h2h_wins, liga_h2h_draw, liga_h2h_no_matches, liga_h2h_close, h2h_today_prefix ins teamvergleich-Addon (addon/teamvergleich/lang/de.php) verschoben.
- Changelog: 1.48.0 - Spielerstatistik-Sprachschlüssel (8× spst_*) ins player-Addon ausgelagert (addon/player/lang/de.php), siehe lang/admin/de.php 1.34.0. Verwaisten Abschnittskommentar "Spielerstatistik (Besucheransicht)" entfernt.
- Changelog: 1.47.0 - Tippspiel-Sprachschlüssel (193× tf_tipp_*) ins tipp-Addon ausgelagert (addon/tipp/lang/de.php), siehe lang/admin/de.php 1.33.0. Verwaisten Abschnittskommentar "Tippspiel (Tipper-Ansicht, vorläufig)" entfernt.
- Changelog: 1.46.0 - Verwaiste Sprachschlüssel mini_next_* (10 Stück) entfernt: gehörten zum mini-Addon, das mit der Addon-Manager-Integration aus dem Core extrahiert wurde (siehe src/Addon/AddonManager.php 1.0.0) und seine eigenen Sprachdateien jetzt selbst mitbringt (addon/mini/lang/de.php).
- Changelog: 1.45.0 - Neue Sprachschlüssel für die Wartungsseite (maintenance_title/heading/message/subtext/contact/footer), angezeigt bei aktivem Wartungsmodus, siehe frontend/bootstrap.php 1.12.0.
- Changelog: 1.44.0 - Emoji aus tf_tipp_header_link entfernt (durch echtes Icon-Bild ersetzt), neuer Sprachschlüssel tf_tipp_section_titel für die neue Bereichsüberschrift.
- Changelog: 1.43.0 - Neue Sprachschlüssel für die Volleyball-Tabellenspalten.
- Changelog: 1.42.0 - Neuer Sprachschlüssel für die Tabellendarstellungs-Umschaltung.
- Changelog: 1.41.0 - Neue Sprachschlüssel für Statistik-Seite 4.
- Changelog: 1.40.0 - Neue Sprachschlüssel für Statistik-Seite 3.
- Changelog: 1.39.0 - Neue Sprachschlüssel für Statistik-Seite 2 und die Seiten-Navigation.
- Changelog: 1.38.0 - Neue Sprachschlüssel für die Diagramm-Erklärungen auf der Statistik-Seite.
- Changelog: 1.37.0 - Neue Sprachschlüssel für die Tipper-Spieltag-Detailansicht und die Tipptabelle.
- Changelog: 1.36.0 - Neue Sprachschlüssel für die Statistik-Seite.
- Changelog: 1.35.1 - Bugfix: der Joker-Erklärtext in den Spielregeln war fachlich falsch - der Multiplikator wirkt auf die Punkte des EINEN gewählten Spiels, nicht auf den ganzen Spieltag (nur die Auswahl ist auf einen Joker pro Spieltag begrenzt, siehe ist_joker in tipp_tipp bzw. calculateTippPunkte()).
- Changelog: 1.35.0 - Neue Sprachschlüssel für die Spielregeln-Erklärseite.
- Changelog: 1.34.0 - Neue Sprachschlüssel für "Archivierte Tippsaisons".
- Changelog: 1.33.0 - Neue Sprachschlüssel für die Tipper-Detailansicht (Vor/Zurück-Navigation, Gesamtpunkte-Zeile).
- Changelog: 1.32.0 - Neuer Sprachschlüssel für den Gesamtübersicht-Reiter.
- Changelog: 1.31.0 - Neue Sprachschlüssel für die Tippeinsicht-Matrix (Spaltenüberschriften, Spieltag-Navigation).
- Changelog: 1.30.0 - Neuer Sprachschlüssel für den Tippeinsicht-Link auf der Login-Seite.
- Changelog: 1.29.0 - Hinweistext für die öffentliche Tippeinsicht ergänzt.
- Changelog: 1.28.0 - Übersetzungen für Form/Trend/Tabellen-Modus-Navigation ergänzt (Beitrag Torsten Hofmann).
- Changelog: 1.27.0 - Übersetzungen für die neue "vorheriger/nächster Spieltag"-Navigation in der Tabellenansicht ergänzt.
- Changelog: 1.26.0 - Tooltip-Key für die neue Minuspunkte-Korrektur ergänzt
- Changelog: 1.25.0 - Strafpunkte-Tooltip-Keys aktualisiert für die erweiterten Bonus/Strafe-Felder (erzielte Tore ergänzt)
- Changelog: 1.24.0 - Übersetzungen für den neuen Strafpunkte/Straftore-Tooltip in der Liga-Tabelle ergänzt (siehe renderStrafHinweis() in src/Liga/StandingsTrait.php 1.1.0)
- Changelog: 1.23.0 - Übersetzungen für die Template-Integration des Tippspiels ergänzt (Seitentitel, Tab-Beschriftungen) - siehe addon/tipp/view_tippspiel_frontend.php 1.0.0. Mehrere alte Einzel-Titel (tf_tipp_*_titel, tf_tipp_zur_abgabe) sind jetzt ungenutzt, da die neue Tab-Leiste sie ersetzt - bewusst nicht gelöscht (harmlos, falls doch irgendwo referenziert)
- Changelog: 1.22.0 - Übersetzungen für den neuen Tippspiel-Link in Header/Footer und die Startseiten-Werbekarte ergänzt (siehe addon/tipp/tipp_lib.php 0.5.0)
- Changelog: 1.21.0 - Übersetzungen für die neue Tippspiel-Rangliste ergänzt (siehe addon/tipp/tipp.php 0.3.0, tippGetRangliste() in frontend_tipp.php 0.3.0)
- Changelog: 1.20.2 - Fehlenden Schlüssel "tf_tipp_col_nickname" für die Spaltenüberschrift in der Tippeinsicht ergänzt (siehe addon/tipp/tipp.php)
- Changelog: 1.20.1 - Übersetzungen für die Tippeinsicht ergänzt
- Changelog: 1.20.0 - Übersetzungen für die neue (vorläufige) Tippspiel-Tipperansicht ergänzt (Login/Registrierung/Tippabgabe, siehe addon/tipp/tipp.php)
- Changelog: 1.19.3 - Neuer Schlüssel "home_overview_disabled" für den Hinweis auf der Startseite, wenn die Liga-Übersicht deaktiviert ist (siehe home.php 2.1.0)
- Changelog: 1.19.2 - Neuer Schlüssel "h2h_pdf_renamed_note" für den zusammenfassenden Umbenennungs-Hinweis im Teamvergleich-PDF (siehe pdf_export.php 1.6.9)
- Changelog: 1.19.1 - Neuer Schlüssel "h2h_today_prefix" für die "(heute TEAM_HEUTE)"- Kennzeichnung bei verknüpften Teams im Teamvergleich (siehe resolveLinkedTeamIds()/getHeadToHeadMatches() in data_liga.php 2.18.0)
- Changelog: 1.19.0 - Übersetzung für den neuen "Spielfrei"-Hinweis ergänzt
- Changelog: 1.18.0 - Übersetzungen für den neuen Besucher-Reiter "Spielerstatistik" ergänzt
- Changelog: 1.17.0 - Übersetzungen für das neue Addon "Mininext" (Portierung aus dem alten LMO, siehe addon/mini/lmo-mininext.php) ergänzt
- Changelog: 1.16.9 - Übersetzung "Stand: {datum}" ergänzt, für das neue Minitabellen-Addon (addon/mini/lmo-minitab.php)
- Changelog: 1.16.8 - 'liga_col_spieltag_short' auf 'ST' geändert (vorher 'Sp.tag'), neuer Schlüssel 'liga_col_spieltag_long' ('Spieltag') ergänzt – für die responsive Lang-/Kurzform im Teamvergleich-Modal (Web/Mobil)
- Changelog: 1.16.7 - Übersetzung "Template:" (ohne Namen) ergänzt, für den Footer, wenn dort das Auswahl-Dropdown statt des reinen Namens steht
- Changelog: 1.16.6 - Übersetzung "Design" für das neue Template-Auswahl-Dropdown im Header ergänzt
- Changelog: 1.16.5 - Übersetzung "Nr." für die Spieltag-Nummer-Spalte im Spielplan-PDF-Export ergänzt
- Changelog: 1.16.4 - Übersetzung für die PDF-Fußzeile ("© {year} www.liga-manager-online.org. Alle Rechte vorbehalten. Version {version}") ergänzt
- Changelog: 1.16.3 - Übersetzung "Ergebnisse Spieltag {n}" als PDF-Titel ergänzt
- Changelog: 1.16.2 - Übersetzung für den "Als PDF exportieren"-Button auf der Ergebnisseite ergänzt
- Changelog: 1.16.1 - Übersetzung "Siege {team}" für die Sieg-Chips im Vergleichs-Modal ergänzt
- Changelog: 1.16.0 - Übersetzungen für das Direkter-Vergleich-Modal (Vergleichs-Icon, Modaltitel, "Unentschieden", "keine bisherigen Begegnungen") ergänzt
- Changelog: 1.15.0 - Umfangreiche Übersetzungen für Ligastatistik ergänzt (Team-Stat-Box, ligaweiter Statistik-Block, Serien-Kategorien, Chancen/Restprogramm)
- Changelog: 1.14.0 - Übersetzungen für Fieberkurven-Reiter + Platzhaltertext ergänzt
- Changelog: 1.13.0 - Übersetzung für Kreuztabelle-Reiter ergänzt
- Changelog: 1.12.2 - Übersetzung für Team-Spielplan-Platzhalter ergänzt
- Changelog: 1.12.1 - Wertungshinweis-Übersetzung entfernt (nicht mehr angezeigt)
- Changelog: 1.12.0 - Übersetzungen für Tabellen-Ansicht (Spaltenköpfe, Wertungshinweis) ergänzt
- Changelog: 1.11.3 - Footer-Zeile "Template: {name}" ergänzt
- Changelog: 1.11.2 - Testseiten-spezifischen Hinweis ("aktuell MySQL 8.0") wieder entfernt – die Info-Seite läuft ja auch bei anderen Nutzern auf eigenen Servern mit ggf. anderer Datenbank; Text bleibt generisch bei "MySQL/MariaDB"
- Changelog: 1.11.1 - Info-Text erwähnt jetzt MySQL/MariaDB (statt nur MariaDB) und weist darauf hin, dass diese Testseite aktuell mit MySQL 8.0 läuft
- Changelog: 1.11.0 - Links zu Homepage + Forum auf der Info-Seite ergänzt
- Changelog: 1.10.0 - Übersetzungen für Ergebnis-Zusatz "n.V."/"i.E." ergänzt
- Changelog: 1.9.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.9.0 - Info-Ansicht umgebaut: zeigt jetzt "Über LMOnext" (Version, Copyright, Kurzbeschreibung, Lizenz) statt Liga-Metadaten – analog zur Info-Seite des alten LMO, die ebenfalls eine reine Software-Info-Seite ist
- Changelog: 1.8.0 - Übersetzungen für Reiter-Navigation (Kalender/Ergebnisse/Spielpläne/Info), Info-Ansicht und Kalender-Ansicht (Monatsnamen, Wochentage) ergänzt
- Changelog: 1.7.0 - Übersetzung für Footer-Zeile "Dauer Berechnungen u. Seitenaufbau" ergänzt
- Changelog: 1.6.0 - Benannte KO-Stufen (Sechzehntelfinale/32tel/64tel) entfernt, durch generisches "Runde {n}" ersetzt (Namen gelten jetzt erst ab 16 Teams)
- Changelog: 1.5.0 - Kleine Überschrift "Statistik {label}:" über der Statistikzeile ergänzt
- Changelog: 1.4.0 - Übersetzungen für KO-Rundennamen nach Teamanzahl + Spiel um Platz 3 ergänzt
- Changelog: 1.3.0 - Übersetzungen für Tabellen-Ansicht (Spieltag-Dropdown, Datumsspanne, Statistik) ergänzt
- Changelog: 1.2.0 - Übersetzungen für Spieltag-Navigation (Vorherige/Nächste) ergänzt
- Changelog: 1.1.0 - Übersetzungen für Liga-Detailseite (letzte Ergebnisse) ergänzt
- Changelog: 1.0.0 - Initiale Version: Besucher-Startseite

## lang/frontend/en.php

- Changelog: 1.50.1 - Same correction as lang/frontend/de.php 1.50.1: the 7 liga_weekday_* keys were wrongly moved out in 1.50.0 - the core calendar view (src/Liga/RenderViewsTrait.php and frontend/data_liga_pretraits.php) also builds its weekday header dynamically via array + variable (tf($key)), which the automated check missed since it only looked for direct literal calls. Keys restored, now intentionally kept both in core and in the viewer add-on (addon/viewer/lang/), since both genuinely need them.
- Changelog: 1.50.0 - Same 8 keys as lang/frontend/de.php 1.50.0 moved out: liga_stand_datum into the mini add-on (addon/mini/lang/en.php); liga_weekday_mo/di/mi/do/fr/sa/so (7 keys) into the viewer add-on (addon/viewer/lang/en.php).
- Changelog: 1.49.0 - Same 11 addon-exclusive keys as lang/frontend/de.php 1.49.0 moved out: liga_col_nr, liga_pdf_footer, h2h_pdf_renamed_note into the pdf-export add-on (addon/pdf-export/lang/en.php); liga_col_spieltag_long, liga_h2h_icon_title, liga_h2h_modal_title, liga_h2h_wins, liga_h2h_draw, liga_h2h_no_matches, liga_h2h_close, h2h_today_prefix into the teamvergleich add-on (addon/teamvergleich/lang/en.php).
- Changelog: 1.48.0 - Player statistics language keys (8× spst_*) moved into the player add-on (addon/player/lang/en.php), see lang/admin/en.php 1.33.0.
- Changelog: 1.47.0 - Prediction game language keys (193× tf_tipp_*) moved into the tipp add-on (addon/tipp/lang/en.php), see lang/admin/en.php 1.32.0. Removed the now-orphaned "Prediction game (tipster view, preliminary)" section comment.
- Changelog: 1.46.0 - Removed orphaned language keys mini_next_* (10 keys): belonged to the mini add-on, which was extracted from the core with the addon manager integration (see src/Addon/AddonManager.php 1.0.0) and now ships its own language files (addon/mini/lang/en.php).
- Changelog: 1.45.0 - Added language keys for the maintenance page (maintenance_title/heading/message/subtext/contact/footer), shown while maintenance mode is active, see frontend/bootstrap.php 1.12.0.
- Changelog: 1.44.0 - Removed emoji from tf_tipp_header_link (replaced by a real icon image), added new tf_tipp_section_titel language key for the new section heading.
- Changelog: 1.43.0 - Added language keys for the volleyball table columns.
- Changelog: 1.42.0 - Added language key for the table view toggle.
- Changelog: 1.41.0 - Added language keys for statistics page 4.
- Changelog: 1.40.0 - Added language keys for statistics page 3.
- Changelog: 1.39.0 - Added language keys for statistics page 2 and the page navigation.
- Changelog: 1.38.0 - Added language keys for the chart explanations on the statistics page.
- Changelog: 1.37.0 - Added language keys for the tipper matchday detail view and the prediction table.
- Changelog: 1.36.0 - Added language keys for the statistics page.
- Changelog: 1.35.1 - Same fix as de.php 1.35.1 (Joker explanation text was factually wrong about scope: match vs matchday).
- Changelog: 1.35.0 - Added language keys for the game rules explanation page.
- Changelog: 1.34.0 - Added language keys for "Archived tipping seasons".
- Changelog: 1.33.0 - Added language keys for the per-tipper detail view (prev/next navigation, total points row).
- Changelog: 1.32.0 - Added the language key for the new overview tab.
- Changelog: 1.31.0 - Added new language keys for the tip-overview matrix (column headers, matchday navigation).
- Changelog: 1.30.0 - Added the tip-overview link label for the login page.
- Changelog: 1.29.0 - Added notice text for public tip viewing.
- Changelog: 1.28.0 - Added translations for form/trend/table mode navigation (contribution by Torsten Hofmann).
- Changelog: 1.27.0 - Added translations for the new "previous/next matchday" navigation in the standings view.
- Changelog: 1.26.0 - Added tooltip key for the new minus-points correction field
- Changelog: 1.25.0 - Updated penalty-points tooltip keys for the extended bonus/penalty fields (goals scored added)
- Changelog: 1.24.0 - Added translations for the new penalty points/goals tooltip in the league table (see renderStrafHinweis() in src/Liga/StandingsTrait.php 1.1.0)
- Changelog: 1.23.0 - Added translations for the Tippspiel template integration (page title, tab labels) - see addon/tipp/view_tippspiel_frontend.php 1.0.0. Several old per-view titles (tf_tipp_*_titel, tf_tipp_zur_abgabe) are now unused since the new tab bar replaces them - intentionally left in place (harmless if still referenced anywhere)
- Changelog: 1.22.0 - Added translations for the new Tippspiel link in the header/footer and the homepage promo card (see addon/tipp/tipp_lib.php 0.5.0)
- Changelog: 1.21.0 - Added translations for the new Tippspiel leaderboard (see addon/tipp/tipp.php 0.3.0, tippGetRangliste() in frontend_tipp.php 0.3.0)
- Changelog: 1.20.2 - Added missing key "tf_tipp_col_nickname" for the column header in the tip overview (see addon/tipp/tipp.php)
- Changelog: 1.20.1 - Added translations for the tip overview (Tippeinsicht)
- Changelog: 1.20.0 - Added translations for the new (preliminary) prediction game tipster view (login/registration/tip submission, see addon/tipp/tipp.php)
- Changelog: 1.19.3 - Added key "home_overview_disabled" for the homepage message shown when the league overview is disabled (see home.php 2.1.0)
- Changelog: 1.19.2 - Added key "h2h_pdf_renamed_note" for the summarized rename note in the head-to-head PDF export (see pdf_export.php 1.6.9)
- Changelog: 1.19.1 - Added key "h2h_today_prefix" for the "(today TEAM_TODAY)" annotation on linked teams in the head-to-head comparison (see resolveLinkedTeamIds()/getHeadToHeadMatches() in data_liga.php 2.18.0)
- Changelog: 1.19.0 - Added translation for the new "Spielfrei" (bye) note
- Changelog: 1.18.0 - Added translations for the new "Player stats" visitor tab
- Changelog: 1.17.0 - Added translations for the new "Mininext" addon (ported from old LMO, see addon/mini/lmo-mininext.php)
- Changelog: 1.16.9 - Added "As of: {datum}" translation for the new Minitabellen addon (addon/mini/lmo-minitab.php)
- Changelog: 1.16.8 - Added 'liga_col_spieltag_long' ('Matchday') for the responsive long/short form in the head-to-head comparison modal (web/mobile); short key ('MD') unchanged
- Changelog: 1.16.7 - Added "Template:" (without name) translation, for the footer when the selector dropdown appears there instead of the plain name
- Changelog: 1.16.6 - Added "Theme" translation for the new template-switcher dropdown in the header
- Changelog: 1.16.5 - Added "No." translation for the matchday-number column in the schedule PDF export
- Changelog: 1.16.4 - Added PDF footer translation ("© {year} www.liga-manager-online.org. All rights reserved. Version {version}")
- Changelog: 1.16.3 - Added "Results Matchday {n}" PDF title translation
- Changelog: 1.16.2 - Added translation for the "Export as PDF" button on the results page
- Changelog: 1.16.1 - Added "Wins {team}" translation for the win chips in the comparison modal
- Changelog: 1.16.0 - Translations for the head-to-head comparison modal (compare icon, modal title, "Draw", "no previous matches") added
- Changelog: 1.15.0 - Extensive translations for league statistics added (team stat box, overall statistics block, streak categories, chances/remaining schedule)
- Changelog: 1.14.0 - Translations for position-chart tab + placeholder text added
- Changelog: 1.13.0 - Translation for cross table tab added
- Changelog: 1.12.2 - Translation for team schedule placeholder added
- Changelog: 1.12.1 - Removed scoring-line translation (no longer displayed)
- Changelog: 1.12.0 - Translations for standings view (column headers, scoring line) added
- Changelog: 1.11.3 - Footer line "Template: {name}" added
- Changelog: 1.11.2 - Removed the test-site-specific note ("currently MySQL 8.0") again – the Info page also runs for other users on their own servers with possibly a different database; text stays generic ("MySQL/MariaDB")
- Changelog: 1.11.1 - Info text now mentions MySQL/MariaDB (instead of just MariaDB) and notes that this test site currently runs on MySQL 8.0
- Changelog: 1.11.0 - Links to homepage + forum added on the Info page
- Changelog: 1.10.0 - Translations for result suffix "AET"/"pens." added
- Changelog: 1.9.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.9.0 - Info view rebuilt: now shows "About LMOnext" (version, copyright, short description, license) instead of league metadata – matching the old LMO's Info page, which is likewise a plain software-info page
- Changelog: 1.8.0 - Translations for tab navigation (Calendar/Results/Bracket/Info), info view and calendar view (month names, weekdays) added
- Changelog: 1.7.0 - Translation for footer line "Calculation & page build time" added
- Changelog: 1.6.0 - Removed named KO stages (Round of 32/64/128), replaced with generic "Round {n}" (named stages now only apply from 16 teams downward)
- Changelog: 1.5.0 - Small heading "Stats – {label}:" above the stats line added
- Changelog: 1.4.0 - Translations for KO round names by team count + third-place match added
- Changelog: 1.3.0 - Translations for table view (matchday dropdown, date range, stats) added
- Changelog: 1.2.0 - Translations for matchday navigation (Previous/Next) added
- Changelog: 1.1.0 - Translations for league detail page (latest results) added
- Changelog: 1.0.0 - Initial version: visitor home page

## lang/i18n.php

- Changelog: 1.2.0 - loadTranslations() mischt jetzt Addon-Übersetzungen ein (Beitrag Torsten Hofmann): neue Funktion registerAddonTranslations() nimmt die lang/{de,en}.php-Einträge aktivierter Addons entgegen (aufgerufen aus AddonManager::loadLanguages()), Core-Strings haben bei Namenskollisionen immer Vorrang. Voraussetzung dafür, dass Addons ihre eigenen Sprachdateien mitbringen können, statt Schlüssel im Core zu duplizieren (siehe lang/admin/de.php+en.php und lang/frontend/de.php+en.php 1.32.0/1.47.0: Tippspiel-Sprachschlüssel dorthin ausgelagert).

- Changelog: 1.1.2 - LANG_SESSION_PREFIX von "olv_lang_" auf "lmonext_lang_" umgestellt
- Changelog: 1.1.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 1.1.0 - Domain-fähig gemacht (admin/frontend getrennt): eigene Sprachdateien, eigener Session-Key und eigener Cache je Bereich. t() bleibt unverändert und ist fest an die Domain "admin" gebunden -> keine Änderung an bestehenden Aufrufstellen nötig. Neue Funktion tf() für den künftigen Besucherbereich ("frontend"), sobald dieser existiert.
- Changelog: 1.0.1 - initLanguage()/getCurrentLanguage() akzeptieren optionalen, in der DB gespeicherten Standardwert (Standardsprache)
- Changelog: 1.0.0 - Initiale Version: Sprachumschaltung, Übersetzungs-Engine

## liga.php

- Changelog: 3.14.0 - h2h_pdf-Einstiegspunkt prüft jetzt zusätzlich das neue teamvergleich-Addon (Auslagerung als eigenständiges Addon, siehe frontend/data_liga.php 3.1.0), nicht mehr nur pdf-export - ohne aktives teamvergleich-Addon liefert getHeadToHeadMatches() zwar dank Hook-Fallback kein Fatal, ein inhaltsleeres PDF ohne echte Daten wäre aber verwirrend gewesen.
- Changelog: 3.13.0 - PDF-Export als eigenständiges Addon "pdf-export" ausgegliedert. Der bisher unbedingte require_once auf frontend/pdf_export.php ist jetzt bedingt (nur wenn addonManager()->isEnabled('pdf-export')) und zeigt auf den neuen Speicherort addon/pdf-export/pdf_export.php. Bewusst NICHT über AddonManager::bootFrontend()/frontend_handlers geladen (das würde die Datei bei JEDEM Frontend-Request laden, nicht nur hier - siehe Kommentar in frontend/bootstrap.php zur ursprünglichen Performance-Entscheidung, die dadurch weiterhin gilt). Der h2h_pdf-Einstiegspunkt und die zentrale $showPdfButtons-Variable (steuert alle vier PDF-Export-Aufrufstellen: Ergebnisse, Tabelle, Spielplan, Teamvergleich) prüfen jetzt zusätzlich den Addon-Status - ohne aktives Addon existieren die exportXxxPdf()-Funktionen schlicht nicht, ein ungeschützter Aufruf würde sonst fatal abbrechen. WICHTIG für Bestandsinstallationen: nach diesem Core-Update ist PDF-Export nicht mehr verfügbar, bis das neue Addon zusätzlich installiert und aktiviert wird.
- Changelog: 3.12.0 - Addon-Manager-Framework integriert (Beitrag Torsten Hofmann): viewOrder-Array über den neuen Hook liga.view_order erweiterbar (das player-Addon hängt "spielerstatistik" jetzt selbst an, statt fest im Array zu stehen); fester "spielerstatistik"-Case im View-Switch ersetzt durch generischen default-Fallthrough mit Hook liga.view_render - jedes Addon kann so eigene Liga-Unteransichten registrieren, ohne den Core anzufassen. Fällt auf "ergebnisse" zurück, wenn kein Addon-Hook zuständig ist.
- Changelog: 3.11.2 - $_GET['tmode'] (kurz/mittel/vollständig-Auswahl der Tabellendarstellung, siehe RenderViewsTrait.php) wird jetzt an exportTabellePdf() durchgereicht und im PDF-Export-Button-Link mitgeführt (&tmode=...), damit das heruntergeladene PDF exakt die Spaltenauswahl zeigt, die der Besucher gerade in der HTML-Ansicht vor sich hat, siehe PdfExporter.php 1.10.0.
- Changelog: 3.11.1 - _liga_id wird jetzt in die für die Ergebnisse-Ansicht geladenen Partien injiziert (getSpieltagPartien() kennt die Liga selbst nicht) - ohne diese Ergänzung blieb die sport-profil-abhängige Anzeige auf der echten Ergebnisse-Seite wirkungslos, obwohl computeStandings() bereits korrekt rechnete (beim Testen selbst gefunden). PDF-Export-Sektionen bekommen ebenfalls die Liga-ID mit.
- Changelog: 3.10.9 - Liest jetzt zusätzlich ?table= (Gesamt/Heim/Gast/Hin/Rück, Beitrag Torsten Hofmann) und reicht es an renderStandingsView()/exportTabellePdf() durch, bleibt beim Spieltag-Wechsel erhalten.
- Changelog: 3.10.8 - $activeNr (aktuell angezeigter Spieltag bei Ergebnisse/Tabelle) wird jetzt an renderTabsBar() übergeben, damit ein Wechsel zwischen den beiden Reitern denselben Spieltag beibehält statt auf den letzten zurückzuspringen. Neuer Spieltag-Picker (renderSpieltagPicker(..., 'tabelle')) auch für die Tabellenansicht, analog zu Ergebnisse.
- Changelog: 3.10.7 - Liest jetzt ?nr=N für die Tabellen-Ansicht (Tabelle nach Spieltag N, siehe RenderViewsTrait.php 1.4.0), analog zum bereits bestehenden ?nr= bei der Ergebnisse-Ansicht.
- Changelog: 3.10.6 - renderBackLinkBlock() nach frontend/data_liga.php verschoben (siehe dortiger Changelog 3.0.1), damit auch home.php (Tippspiel-View) denselben Link nutzen kann - hier nur noch der Aufruf, keine Verhaltensänderung
- Changelog: 3.10.5 - Neue globale Einstellung "Übersicht-Link anzeigen?" (Admin → Einstellungen → Besucherbereich), entspricht "Ligaauswahl" im alten LMO. Neue Funktion renderBackLinkBlock() baut den "← Zur Übersicht"-Link jetzt als vollständigen HTML-Block (statt nur den Text), damit er sich bei Bedarf komplett ausblenden lässt - alle Templates nutzen jetzt den neuen Platzhalter "ZurueckLinkBlock" statt der fest verankerten Verlinkung
- Changelog: 3.10.4 - Bindet pdf_export.php jetzt direkt hier ein (nicht mehr über den gemeinsamen frontend/bootstrap.php), da liga.php der einzige tatsächliche Verwender ist, siehe bootstrap.php 1.6.0
- Changelog: 3.10.3 - "Spielfrei"-Anzeige (HTML + PDF) jetzt über die neue Liga-Einstellung ShowSpielfrei steuerbar (siehe view_liga_settings.php 1.4.4), Default "an"
- Changelog: 3.10.2 - PDF-Export der Ergebnisse übergibt jetzt die "Spielfrei"-Teams pro Spieltag mit (siehe pdf_export.php 1.6.7)
- Changelog: 3.10.1 - Neuer "Spielfrei: TEAMNAME"-Hinweis unterhalb der Ergebnistabelle eines Spieltags (siehe renderSpielfreiNote() in data_liga.php 2.17.0), analog zum alten LMO
- Changelog: 3.10.0 - "spielerstatistik"-Reiter ergänzt (neues Addon, siehe frontend/data_spielerstat.php + admin/spielerstat_lib.php)
- Changelog: 3.9.4 - Neue globale Einstellung "PDF-Export für Besucher anzeigen?" (Admin → Einstellungen → Besucherbereich) ausgewertet: blendet bei Deaktivierung nicht nur die PDF-Buttons aus (Ergebnisse/Tabelle/Spielplan), sondern blockiert auch den direkten Aufruf über ?pdf=1 bzw. ?h2h_pdf=1, gilt für KO- und reguläre Ligen gleichermaßen
- Changelog: 3.9.3 - Alle PDF-Export-Aufrufe (Ergebnisse/Tabelle/Spielplan/Teamvergleich) übergeben jetzt $showLogos, damit Team-Logos auch im PDF erscheinen, wenn die Liga-Einstellung "Logo anzeigen" aktiv ist. Der Teamvergleich-PDF-Link ist teamübergreifend (kein Liga-Kontext an der Stelle) – bekommt das Flag deshalb über einen neuen "&logos=1"-Query-Parameter mit, den das Modal selbst anhand seines eigenen Payloads setzt
- Changelog: 3.9.2 - Name-zuerst-dann-Logo für die Heim-Spalte gilt jetzt auch bei KO-Ligen in der Ergebnisse-Ansicht (vorher nur reguläre Ligen). Der KO-Turnierbaum (Spielpläne) bleibt bewusst unverändert bei Logo-zuerst, da dort die Teams untereinander geschrieben werden
- Changelog: 3.9.1 - renderResultsTable()-Aufrufe übergeben jetzt !$isKO als reverseHeim, damit die Heim-Spalte bei regulären Ligen Name-zuerst-dann-Logo zeigt (KO bleibt unverändert Logo-zuerst)
- Changelog: 3.9.0 - Neue liga-weite Variable $showLogos (aus der Einstellung ShowLogos), wird an renderResultsTable() weitergereicht, damit Ergebnisse/Finale+Platz3 bei aktiviertem "Logo anzeigen" die Team-Logos einblenden
- Changelog: 3.8.9 - PDF-Export bei Finale + Spiel um Platz 3: baut jetzt zwei getrennte Abschnitte (sectionSpecs) mit jeweils eigenem Datum, statt beider Begegnungen in einer gemeinsamen Tabelle mit einem (falschen) über beide Spiele gemittelten Datumsbereich. Spiegelt exakt dieselbe Bedingung wie die HTML-Ansicht (siehe exportErgebnissePdf()/buildResultsPdf() in pdf_export.php)
- Changelog: 3.8.8 - Bugfix: Finale + Spiel um Platz 3 zeigten in der Überschrift beide den Datumsbereich der GESAMTEN Runde (z.B. "18.07.2026 - 19.07.2026"), obwohl jede Paarung nur ein eigenes Einzeldatum hat. Datumsbereich wird jetzt pro Paarung berechnet (spieltagDateRange() nur mit den Partien dieser Paarung), zeigt dadurch bei Einzelspielen korrekt nur das eine Datum
- Changelog: 3.8.7 - Neue Route "?h2h_pdf=1&a=X&b=Y": PDF-Export des Team-Vergleichs (Direkter-Vergleich-Modal). Teamübergreifend, deshalb vor der normalen id/view-Auflösung abgefangen (keine gültige Liga-ID nötig). Siehe exportH2hPdf() in pdf_export.php und den neuen PDF-Button im Modal (data_liga.php, renderH2hModalAssets())
- Changelog: 3.8.6 - "Als PDF exportieren"-Button jetzt auch unter dem Spielplan einer Mannschaft (reguläre Ligen) – erscheint nur, wenn tatsächlich ein Team ausgewählt ist (nicht bei der "Bitte wählen Sie..."-Leeranzeige). Neue exportSpielplanPdf() in pdf_export.php
- Changelog: 3.8.5 - PDF-Export-Buttons (Ergebnisse + Tabelle) neu gestaltet: nur noch "PDF" als Text statt der übersetzten Beschriftung (spart die Übersetzung, ist universell verständlich), neues Dokumenten-Icon, Übersetzung bleibt als title-Tooltip erhalten. Passendes Hell/Blau-Hover-Styling siehe layout.tpl.php
- Changelog: 3.8.4 - "Als PDF exportieren"-Button jetzt auch für KO-Turniere (Rundenname wie "Achtelfinale"/"Runde 1" statt "Spieltag N" als PDF-Titel); Button-HTML in eine gemeinsame Variable ausgelagert, damit er sowohl im normalen Zweig als auch im KO-Mehrgruppen-Zweig (Finale + Spiel um Platz 3 auf einer Seite) erscheint
- Changelog: 3.8.3 - Ergebnisse-Ansicht: reine Leer-Begegnungen (kein Team, kein Label auf beiden Seiten) werden jetzt herausgefiltert, bevor sie gerendert werden (siehe partieIsEmptyPlaceholder() in data_liga.php) – relevant für KO-Turniere, deren Teilnehmerzahl im alten LMO auf die nächste Zweierpotenz aufgefüllt werden musste
- Changelog: 3.8.2 - "Als PDF exportieren"-Button auch unter der Tabelle (Standings) ergänzt (?pdf=1 löst exportTabellePdf() aus). Der Tabelle-Reiter ist ohnehin nur für reguläre Ligen erreichbar (bei KO-Turnieren wird er über $flags komplett ausgeblendet), daher kein zusätzlicher !$isKO-Check nötig
- Changelog: 3.8.1 - exportErgebnissePdf()-Aufruf an neue Signatur angepasst (Spieltag-Nummer + Datumsbereich statt fertigem Überschrift-String, siehe pdf_export.php v1.1.0 für das überarbeitete PDF-Layout mit Logo/Tore-Schnitt)
- Changelog: 3.8.0 - "Als PDF exportieren"-Button unter der Ergebnistabelle für reguläre (Round-Robin-)Ligen ergänzt (?pdf=1 löst Download über exportErgebnissePdf() aus, danach exit; kein Button bei KO-Turnieren)
- Changelog: 3.7.0 - Bugfix: favTeam/selTeam aus den Liga-Einstellungen werden jetzt tatsächlich verwendet. "Spielpläne" wählt ohne ?team=-Parameter automatisch das selTeam-Team; "Ergebnisse" hebt die favTeam-Mannschaft fett hervor (resolveTeamNumberToId() löst die gespeicherte Team-Nummer in die echte team_id auf)
- Changelog: 3.6.0 - "ligastatistik"-Reiter ergänzt (Team-Auswahl per team1/team2 GET-Parameter); für KO-Ligen ausgeblendet
- Changelog: 3.5.0 - "fieberkurve"-Reiter ergänzt; für KO-Ligen ausgeblendet
- Changelog: 3.4.0 - "kreuztabelle"-Reiter ergänzt; Tabelle + Kreuztabelle für KO-Ligen ausgeblendet (ergeben dort keinen Sinn)
- Changelog: 3.3.0 - "spielplaene"-Reiter jetzt auch für reguläre Ligen aktiv (Team-Spielplan statt Turnierbaum); Einschränkung "nur für KO-Ligen" entfernt
- Changelog: 3.2.0 - "tabelle"-Reiter ergänzt (Liga-Tabelle für reguläre Ligen)
- Changelog: 3.1.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
- Changelog: 3.1.0 - renderInfoView()-Aufruf vereinfacht: Info zeigt jetzt "Über LMOnext" statt Liga-Metadaten, braucht daher keine Liga-Parameter mehr
- Changelog: 3.0.0 - Reiter-Navigation ergänzt: neben "Ergebnisse" jetzt auch "Kalender" (Monatskalender mit klickbaren Spieltagen/Runden), "Spielpläne" (klassischer Turnierbaum, vorerst nur für KO-Ligen) und "Info" (Kerndaten zur Liga). Reiter werden nur gezeigt, wenn in den Liga-Einstellungen aktiviert (Kalender/Ergebnis/Plan). Auswahl über ?view=kalender|ergebnisse|spielplaene|info.
- Changelog: 2.0.0 - Umbau auf reine Platzhalter-Templates
- Changelog: 1.4.2 - "Heim"-Spaltenüberschrift bekommt class="col-heim" (für Rechtsbündigkeit)
- Changelog: 1.4.1 - koRoundName()-Aufruf um Rundennummer ergänzt
- Changelog: 1.4.0 - Kleine Überschrift "Statistik {label}:" über der Statistikzeile ergänzt
- Changelog: 1.3.0 - KO-Rundennamen nach Teamanzahl statt "Runde N"
- Changelog: 1.2.0 - Umbau auf Tabellen-Ansicht wie alte LMO-Ergebnisseite
- Changelog: 1.1.0 - Auswahl/Navigation zwischen allen Spieltagen/Runden (?nr=N)
- Changelog: 1.0.0 - Initiale Version: zeigt die letzten Ergebnisse einer Liga

## src/Addon/AddonManager.php

- Changelog: 1.6.0 - Neues Feature "Addon von GitHub-URL installieren": neue Methode installFromGithubUrl() - Alternative zum manuellen ZIP-Upload für die Erstinstallation eines noch nicht vorhandenen Addons, direkt per Repo-URL (z.B. "https://github.com/henshingly/lmonext_addon-tipp"), ohne die ZIP-Datei erst manuell herunterladen und wieder hochladen zu müssen. Lädt das neueste GitHub-Release, extrahiert und installiert es. Der gemeinsame Installationskern (Muster-Scan, Manifest-Suche, Namens-/Core-Versions-Validierung, Backup, atomarer Dateiaustausch) wurde dafür aus installFromZip() in eine neue, gemeinsam genutzte private Methode installFromExtractedDir() ausgelagert - beide Installationswege durchlaufen dadurch garantiert exakt dieselben Sicherheitsprüfungen, keine doppelte, potenziell inkonsistente Logik an zwei Stellen.
- Changelog: 1.5.1 - KRITISCHER Bugfix (gemeldet: "addon-manager"-Selbst-Update meldet Erfolg, installierte Version ändert sich aber nicht, "Update installieren"-Button bleibt weiter aktiv): copyDirectory() prüfte den Rückgabewert von copy() nicht - schlug das Kopieren einer einzelnen Datei still fehl, wurde das ignoriert. Bei einem Selbst-Update von "addon-manager" versucht handler_addons.php (die Datei, die den Update-Vorgang selbst durchführt) sich dabei SELBST zu überschreiben - auf manchen Server-Konfigurationen ist eine gerade ausgeführte PHP-Datei gesperrt, wodurch genau diese eine Datei (und ggf. addon.json selbst) beim Kopieren übersprungen wurde, während alle anderen Dateien erfolgreich aktualisiert wurden - der Vorgang meldete trotzdem "success". Fix: neue Methode atomicReplaceAddonDir() - Standard-Muster für Selbst-Updates: neue Dateien zuerst vollständig in ein FRISCHES, unbenutztes Verzeichnis kopieren (dort kollidiert nichts mit offenen Datei-Handles), dann per rename() (atomare Operation, ändert nur den Verzeichniseintrag, nicht den Dateiinhalt - funktioniert auch bei offen gehaltenen Dateien) das alte Verzeichnis beiseiteschieben und das neue an dessen Stelle setzen. copyDirectory() gibt jetzt außerdem einen echten Erfolgs-Rückgabewert zurück, statt Fehler stillschweigend zu verschlucken - installUpdate() und installFromZip() melden bei einem fehlgeschlagenen Kopiervorgang jetzt ehrlich "success: false" (neuer Fehlercode "copy_failed") statt einen falschen Erfolg.
- Changelog: 1.5.0 - KRITISCHER Bugfix (gemeldet: "ewige-tabelle aktivieren deaktiviert tipp/relegation/tabellenrechner, ewige-tabelle deaktivieren aktiviert sie wieder"): loadEnabled() fiel bei einer leeren SELECT...WHERE enabled=1-Abfrage auf eine feste Liste "CORE_ADDONS" zurück (u.a. mit veralteten/nicht mehr existierenden Namen wie "translator"), gedacht als Erstinstallations-Bootstrapping. Der Fallback konnte aber NICHT zwischen einer echten Erstinstallation (Registry-Tabelle hat GAR KEINE Zeilen) und dem Zustand "Tabelle hat Zeilen, aber zufällig gerade KEINE mit enabled=1" unterscheiden. Solange z.B. tipp/relegation/tabellenrechner NIE eine echte enabled=1-Zeile hatten, erschienen sie NUR wegen dieses Fallbacks als aktiv - sobald ein ANDERES Addon zum ersten Mal eine echte Zeile bekam, griff der Fallback nicht mehr und sie erschienen plötzlich als deaktiviert, und umgekehrt beim erneuten Deaktivieren dieses einen Addons. Fix: CORE_ADDONS-Fallback komplett entfernt - jedes Addon (außer addon-manager, der weiterhin separat immer erzwungen wird) startet jetzt konsistent als INAKTIV, bis es explizit per enable() aktiviert wurde, exakt wie es für alle neueren Addons (liga-klassen-rekorde, mini-tabelle, spieltag-viewer) bereits korrekt beobachtet wurde. WICHTIG für Bestandsinstallationen: Addons, die bisher NUR wegen dieses Fallbacks als aktiv erschienen (nie eine echte DB-Zeile hatten), erscheinen nach diesem Update als deaktiviert und müssen einmalig bewusst neu aktiviert werden.
- Changelog: 1.4.2 - Zusatzfix zum disable()-Bugfix aus 1.4.1 (gemeldet: Problem bestand auf einer Test-Installation trotz eingespieltem Fix weiterhin): ensureRegistryTable() prüft jetzt zusätzlich per SHOW INDEX, ob die Spalte "name" tatsächlich ein UNIQUE-Constraint hat, und ergänzt es bei Bedarf nachträglich per ALTER TABLE - "CREATE TABLE IF NOT EXISTS" ändert an einer bereits bestehenden Tabelle nichts mehr, sodass eine VOR dem 1.4.1-Fix angelegte addon_registry-Tabelle ohne dieses Constraint das neue "INSERT ... ON DUPLICATE KEY UPDATE" in enable()/disable() wirkungslos gemacht hätte (jeder Aufruf hätte eine zusätzliche Zeile statt eines Updates erzeugt). Vor dem Ergänzen des Constraints werden eventuell bereits vorhandene Duplikate pro Addon-Name bereinigt (nur die neueste Zeile bleibt erhalten), da ein UNIQUE-Constraint sonst nicht angelegt werden kann.
- Changelog: 1.4.1 - KRITISCHER Bugfix (gemeldet: "Addon wurde deaktiviert"-Erfolgsmeldung erscheint, Addon bleibt aber als aktiv gelistet - reproduzierbar bei jedem Addon, das noch NIE zuvor explizit (de)aktiviert wurde, unabhängig vom Demo-Modus): disable() nutzte ein reines "UPDATE ... WHERE name = ?", das 0 Zeilen betraf, wenn für dieses Addon noch kein Registry-Eintrag existierte (Standardzustand ohne Eintrag ist "aktiv") - der In-Memory-Status im aktuellen Request zeigte "deaktiviert" (daher die scheinbar erfolgreiche Meldung), die DB blieb aber unverändert, sodass der nächste Request wieder "aktiv" las. enable() nutzte hierfür bereits korrekt ein "INSERT ... ON DUPLICATE KEY UPDATE" (Upsert) - disable() jetzt analog umgestellt.
- Changelog: 1.4.0 - Neues Feature "Addon-Daten löschen" (analog zu phpBB's "purge extension data"): neue Methode purgeData() löscht unwiderruflich die im Manifest deklarierten eigenen Datenbank-Tabellen (Feld "db_tables") eines Addons - erlaubt nur für bereits deaktivierte Addons. Wichtig: db_tables enthält BASIS-Tabellennamen OHNE DB-Präfix (analog zu jedem tbl()-Aufruf im übrigen Code) - purgeData() wendet den tatsächlich konfigurierten Präfix ($this->tablePrefix, pro Installation unterschiedlich) selbst zur Laufzeit an, statt sich auf einen im Manifest fest verdrahteten Präfix zu verlassen (wäre auf Installationen mit abweichendem DB_PREFIX sonst wirkungslos gewesen). Betrifft ausschließlich vom Addon EXKLUSIV besessene, vollständige Tabellen - keine addon_settings-Einträge (globale, schlüsselbasierte Tabelle ohne Addon-Zuordnung, Risiko fremde Einstellungen zu treffen) und keine per ALTER TABLE ergänzten Spalten in Core-Tabellen (z.B. liga.saison/liga.klasse_id vom liga-klassen-rekorde-Addon - zu invasiv für eine automatische Funktion, bleiben bei einem Purge bestehen).
- Changelog: 1.3.0 - Zwei Fehlalarme im Muster-Scan aus Version 1.1.0 behoben (gemeldet anhand einer echten Installation des tipp-Addons, das trotz harmlosem Inhalt abgelehnt wurde): 1) das "exec"-Muster (und die übrigen Funktionsmuster: system, shell_exec, passthru, proc_open, popen, pcntl_exec, eval, assert, create_function) trafen fälschlich auch auf harmlose METHODENAUFRUFE gleichen Namens (z.B. PDO::exec()/$db->exec() für SQL-Statements - eine der in diesem Projekt allgegenwärtigsten Methoden) - jetzt mit negativem Lookbehind (?<!->)(?<!::) auf reine globale Funktionsaufrufe eingegrenzt. 2) das Backtick-Muster für PHP-Shell-Ausführung wurde KOMPLETT ENTFERNT: SQL mit Backtick-quotierten Spaltennamen (projektweiter Standardstil, siehe tbl()-Helper) hätte praktisch JEDES SQL-schreibende Addon fälschlich blockiert. Zusätzlich: neue Methode stripPhpComments() entfernt Kommentare (nicht String-Literale) vor der Muster-Suche - verhindert Fehlalarme, wenn ein sicherheitsrelevanter Funktionsname nur in einem erläuternden Kommentar auftaucht (beobachtet beim player-Addon, das explizit dokumentiert, eval() NICHT zu verwenden). Gegen alle aktuellen Addon-Pakete nachträglich verifiziert: keine Fehlalarme mehr.
- Changelog: 1.2.0 - KRITISCHER Bugfix (gemeldet: Addon-Installation blieb auf einer frischen Installation unbegrenzt hängen, sehr lange Ladezeit, keine Fehlermeldung - auf einer anderen, älteren Installation dagegen unauffällig): der php -l-Syntaxcheck aus Version 1.1.0 rief PHP_BINARY per ungeschütztem exec() auf. Auf PHP-FPM-Umgebungen (typisch für Shared-Hosting) zeigt PHP_BINARY oft NICHT auf einen normal per exec() aufrufbaren CLI-Interpreter, sondern auf den FPM-Worker-Prozess - der exec()-Aufruf blieb dadurch unbegrenzt hängen, bis der komplette Request vom Webserver selbst per Timeout beendet wurde (ohne dass PHP je die Chance hatte, eine eigene Fehlermeldung auszugeben). Fix: neue Methode lintPhpFileWithTimeout() nutzt proc_open()/proc_get_status()/proc_terminate() mit einem harten 3-Sekunden-Zeitlimit statt eines ungeschützten exec()-Aufrufs - wird das Limit erreicht, wird der Prozess zwangsweise beendet und der Upload NICHT blockiert (fail-open: die übrigen Prüfungen - Dateityp-Whitelist, Zip-Slip-Schutz, Muster-Suche nach verdächtigen Funktionsaufrufen - bleiben davon unberührt und greifen weiterhin).
- Changelog: 1.1.0 - Sicherheitsüberarbeitung des ZIP-Installationswegs (installFromZip() UND installUpdate()): neue Methoden validateZipEntries() (Zip-Slip-Schutz + Dateiendungs-Whitelist, läuft VOR dem Entpacken) und scanExtractedFiles() (php -l-Syntaxcheck + Muster-Suche nach verdächtigen Funktionsaufrufen wie eval/system/exec/Backticks, läuft NACH dem Entpacken, VOR dem Kopieren in den öffentlich erreichbaren addon/-Ordner). Hintergrund: der bisherige Schutz (addon/.htaccess als Blockliste nach Dateinamensmustern) verhinderte nicht, dass eine hochgeladene Datei mit beliebigem, unauffälligem Namen direkt per URL ausführbar gewesen wäre - diese Inhaltsprüfung ist eine zusätzliche, unabhängige Verteidigungsebene, kein Ersatz für den parallel umgesetzten Architektur-Fix (siehe addon-run.php, addon/.htaccess 2.0).
- Changelog: 1.0.0 - Initiale Version (Beitrag Torsten Hofmann): zentrale Addon- und Event-System-Komponente. Discovery (addon/{name}/addon.json scannen), Lifecycle (enable/disable über DB-Tabelle addon_registry), Boot (lädt admin_handlers/frontend_handlers aktivierter Addons automatisch statt fester require_once-Zeilen im Core), Navigation (admin_nav aus Manifesten mergen), Sprachen (Addon-eigene lang/-Dateien), Templates (Addon-eigene templates_dir registrieren), Hooks (registerHook()/doHook() für Event-basierte Core-Addon-Interaktion, z.B. liga.view_order, liga.view_render, frontend.home_cards, admin.footer). Framework-frei (kein Composer/Laravel), Addons werden weiterhin per require_once geladen, nur dynamisch statt fest verdrahtet. Zusätzlich: ZIP-Installation (installFromZip()), GitHub-Update-Check (checkGithubUpdates()/installUpdate()), Export als ZIP.

## src/Home/HomeRenderer.php

- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_home.php (siehe frontend/data_home.php 3.0.0 für den vollen Kontext der Umstellung). HTML-Rendering für die Startseite (Liga-Links, Archiv-Ordnerbaum als HTML).

## src/Home/HomeRepository.php

- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_home.php (siehe frontend/data_home.php 3.0.0 für den vollen Kontext der Umstellung). Datenzugriff für die Startseite (aktive Ligen, Archiv-Ordnerbaum, archivierte Ligen je Ordner).

## src/Home/HomeService.php

- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_home.php (siehe frontend/data_home.php 3.0.0 für den vollen Kontext der Umstellung). Fassade: kombiniert HomeRepository + HomeRenderer zu einer stabilen API für home.php.

## src/Liga/Eternal/EternalTableService.php

- Changelog: 1.1.0 - Strafpunkte/Straftore/Korrekturen werden jetzt über alle Saisons aufsummiert und die Begründungen gesammelt (Beitrag Torsten Hofmann), für die neue Fußnoten-Anzeige in der Ewigen Tabelle (siehe addon/mini/lmo-ewigetab.php).
- Changelog: 1.0.1 - $ligaId an LigaService::computeStandings() übergeben, damit admin-seitig hinterlegte Strafpunkte/Straftore (siehe StandingsTrait.php 1.1.0) auch in der Ewigen Tabelle korrekt in die historischen Punkte einfließen, statt dort ignoriert zu werden
- Changelog: 1.0.0 - Initiale Version (Torsten Hofmann) Ewige Tabelle + Mehrjahres-Vergleich (Teamvergleich über mehrere Jahre). Grundlage sind die vorhandenen Liga-Daten (lmonext_liga_partien / lmonext_teams_global). Die eigentliche Tabellenberechnung je Liga läuft über LigaService::computeStandings(), damit Punktewerte (3/1/0, n.V., i.E.) und Status genau wie im normalen Ligabetrieb behandelt werden. Dieser Service summiert nur die bereits berechneten Liga-Zeilen über mehrere Ligen hinweg auf bzw. stellt sie pro Saison als Matrix bereit. Eingebaut als eigenständige PSR-4-Klasse – bestehende Dateien bleiben unangetastet.

## src/Liga/HeadToHeadTrait.php

- Changelog: AUSGEGLIEDERT (Version 1.1.0 war der letzte Core-Stand) - diese Datei existiert im Core nicht mehr, siehe frontend/data_liga.php 3.1.0: Teamvergleich (H2H) wurde komplett als eigenständiges Addon "teamvergleich" ausgegliedert. Neuer Speicherort: addon/teamvergleich/HeadToHead.php (jetzt eine eigenständige Klasse statt eines in LigaService eingemischten Traits), eigenes CHANGELOG.md dort. Die gesamte bisherige Versionshistorie (unten) bleibt als Referenz erhalten.
- Changelog: 1.1.0 - renderH2hModalAssets(): identische Ergänzung wie in frontend/data_liga_pretraits.php 2.22.0 (siehe dortiger Changelog-Eintrag für den vollen Hintergrund) - PDF-Button im Teamvergleich-Popup prüft jetzt zusätzlich den Status des neuen pdf-export-Addons.
- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Team-Verknüpfungen (Umbenennung/Fusion/Abspaltung) und Teamvergleich (resolveLinkedTeamIds, resolveCanonicalTeamId, getHeadToHeadMatches, H2H-Modal/PDF-Hilfsfunktionen).

## src/Liga/LigaRepositoryTrait.php

- Changelog: 1.1.0 - Neue Funktion getLigaSportType() (Beitrag: Torsten Hofmann).
- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Grundfunktionen zu einzelnen Ligen (getLigaById, getLigaType, getLigaTeamCount, getLigaOptions, ligaFlagEnabled, getLigaViewFlags).

## src/Liga/LigaService.php

- Changelog: 1.1.0 - "use HeadToHeadTrait;" entfernt (Auslagerung des Teamvergleichs als eigenständiges Addon "teamvergleich", siehe frontend/data_liga.php 3.1.0) - die Logik lebt jetzt unter addon/teamvergleich/HeadToHead.php als eigenständige Klasse, nicht mehr als in diese Fassade eingemischte Trait.
- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Fassade: fasst alle Liga-Traits zu einer stabilen statischen API zusammen.

## src/Liga/RenderViewsTrait.php

- Changelog: 1.12.0 - Alle 6 Aufrufstellen (3× renderH2hIcon, 3× renderH2hModalAssets, in den Zeilenaufbauten für Ergebnisliste/KO-Baum/Team-Spielplan) von self::-Methodenaufrufen auf globale Funktionsaufrufe (mit führendem Backslash, z.B. \renderH2hIcon(...)) umgestellt (Auslagerung des Teamvergleichs als eigenständiges Addon "teamvergleich", siehe frontend/data_liga.php 3.1.0) - seit dem Entfernen von HeadToHeadTrait aus LigaService (siehe LigaService.php 1.1.0) existieren diese Methoden nicht mehr als self::-Aufruf in dieser Klasse. Die globalen Funktionen (frontend/data_liga.php) delegieren jetzt über Hooks und liefern '' zurück, wenn das Addon nicht aktiv ist - kein Fataler Fehler.
- Changelog: 1.11.2 - resolveStandingsCell() von private auf public umgestellt, damit PdfExporter::exportTabellePdf() dieselbe Zellwert-Auflösung wiederverwenden kann statt sie zu duplizieren (siehe PdfExporter.php 1.10.0). Keine Verhaltensänderung der Funktion selbst.
- Changelog: 1.11.1 - renderDynamicStandingsTable(): st-diag-Klasse (diagonale Kopfzeile) wird in der Lang-Ansicht jetzt nur noch gesetzt, wenn die Spalte explizit 'diag'=>true trägt (siehe VolleyballProfile.php 1.2.0), statt pauschal für alle Spalten. Spalten ohne dieses Flag werden wie in short/medium-Ansicht als normale, horizontale <th> gerendert.
- Changelog: 1.11.0 - Neue Wrapper-Methode LigaService::renderCopyrightNotice() für Addon-Ausgaben (Beitrag: Torsten Hofmann), delegiert an die neue globale Funktion in config_loader.php und packt sie in einen <p>-Block.
- Changelog: 1.10.0 - Team-Auswahl in der Spielplan-Ansicht sportartabhängig gemacht: Volleyball-Ligen zeigen jetzt ein kompaktes Dropdown-Menü statt der Team-Sidebar (Vorbild: Torstens Vorschlag, an mein Kartenlayout angepasst), alle anderen Sportarten (inkl. Fußball) behalten unverändert die bestehende Sidebar-Liste mit Logos. Live geprüft: Fußball rendert weiterhin die echte Sidebar, Volleyball das echte Dropdown, keine Vermischung.
- Changelog: 1.9.1 - Die diagonalen Spaltenüberschriften gelten jetzt nur noch für die "vollständig"-Ansicht - "kurz" und "mittel" zeigen die Spaltennamen wie gewünscht wieder normal waagerecht.
- Changelog: 1.9.0 - Spaltenköpfe der dynamischen Sport-Tabelle (Volleyball "vollständig" etc.) werden jetzt diagonal dargestellt (Vorbild volleyball-bundesliga.de), damit auch ausgeschriebene Beschriftungen wie "Ballquotient" auf schmalem Bildschirm Platz finden. Fußball bleibt unberührt (normale waagerechte Kopfzeile, keine st-diag-Klasse).
- Changelog: 1.8.0 - Dynamische Tabellen-Spaltenbeschriftungen für Sportarten mit eigenen Darstellungsmodi (Beitrag: Torsten Hofmann, Vorbild volleyball-bundesliga.de): neue Funktion renderDynamicStandingsTable() mit "Tabellendarstellung: kurz | mittel | vollständig"-Umschalter. Fußball bleibt komplett unberührt (leeres getDisplayModes() → alte Logik). Live gegen die drei Original-Ansichten von volleyball-bundesliga.de geprüft: "kurz" (Sp/S/Sätze/Pkt) und "vollständig" (inkl. 3:0/3:1/3:2/2:3/1:3/0:3-Aufschlüsselung, Ballquotient, Ballverhältnis, Satzquotient, Satzverhältnis) stimmen exakt überein.
- Changelog: 1.7.0 - Neue sportProfile()/formatScore()-Hilfsfunktionen (Beitrag: Torsten Hofmann, an mein Format angepasst). Sieben Ergebnis-Anzeige-Stellen auf sport-profil-abhängige Formatierung umgestellt (Ergebnistabelle, Team-Spielplan, Kreuztabelle-Einzelzelle, Ligastatistik-Extremwerte); die Kreuztabelle-Aggregatzelle (Hin-/Rückspiel-Summe) bewusst nicht umgestellt, da Sätze/Perioden sich nicht sinnvoll über zwei Spiele summieren lassen. Live doppelt bestätigt: Fußball zeigt weiterhin exakt "3 : 0", Volleyball korrekt "3 : 1 / 95:85 (25:20 25:22 20:25 25:18)".
- Changelog: 1.6.0 - $currentSpieltag ($nr) wird jetzt an computeStandings() durchgereicht, damit das neue "ab Spieltag"-Feature (siehe StandingsTrait.php) mit der Tabelle-nach-Spieltag-Navigation korrekt zusammenspielt.
- Changelog: 1.6.0 - Torstens Gesamt/Heim/Auswärts/Hin-/Rückrunde-Umschalter (renderStandingsModeNav()) mit der bestehenden Spieltag-Navigation zusammengeführt: der Spieltag-Filter wirkt zuerst, der Tabellen-Modus danach (z.B. "Rückrunde bis Spieltag 20" funktioniert sinnvoll). Neue Spalten "Form" und "Trend" in der Tabelle. Beide Navigationsleisten behalten die jeweils andere Auswahl beim Wechsel bei (Modus bleibt bei Spieltag-Wechsel erhalten und umgekehrt).
- Changelog: 1.5.0 - renderTabsBar() bekommt einen optionalen $activeNr-Parameter, hängt "&nr=N" gezielt an die Links zu "ergebnisse"/"tabelle" an (die einzigen zwei Reiter, die ?nr= lesen). renderSpieltagPicker() bekommt einen optionalen $targetView-Parameter (Standard 'ergebnisse', rückwärtskompatibel), damit derselbe Picker jetzt auch für "Tabelle nach Spieltag N" wiederverwendet werden kann statt einen zweiten Picker zu bauen.
- Changelog: 1.4.0 - Neue Funktion: Tabelle nach Spieltag N ("Tabelle nach dem X. Spieltag", analog zu kicker.de). renderStandingsView() bekommt einen optionalen $uptoSpieltag-Parameter, filtert die Partien auf _spieltag_nummer <= N vor der Berechnung. Neue "vorheriger/nächster Spieltag"-Navigation ober- und unterhalb der Tabelle (renderStandingsSpieltagNav()), fehlt am ersten bzw. letzten Spieltag automatisch. Ohne Parameter unverändertes bisheriges Verhalten (aktueller/finaler Stand).
- Changelog: 1.3.0 - Neuer Platzhalter "Fussnoten" für die Strafpunkte-Begründungen im Wikipedia-Stil (siehe StandingsTrait.php 1.4.0)
- Changelog: 1.2.0 - Die Admin-Einstellung "Minuspunkte" (Tab Spielsystem) wird jetzt tatsächlich ausgewertet: Pkt-Spalte zeigt bei aktivierter Option "Pkt:Minuspunkte" statt nur "Pkt" (siehe StandingsTrait.php 1.2.0 für die Berechnung)
- Changelog: 1.1.0 - $ligaId an alle vier computeStandings()-Aufrufe übergeben (Tabelle, Kreuztabelle, Fieberkurven, Ligastatistik), damit admin-seitig hinterlegte Strafpunkte/Straftore korrekt einfließen (siehe StandingsTrait.php 1.1.0). Neuer Platzhalter "StrafHinweis" in der Tabellen-Zeile (⚠-Marker mit Tooltip, renderStrafHinweis())
- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). HTML-Rendering aller Liga-Ansichten (Ergebnisse, Spielpläne, Kalender, Kreuztabelle, Fieberkurven, Info, Tabs, Spieltag-Auswahl, Spielfrei-Hinweis).

## src/Liga/SpieltagRepositoryTrait.php

- Changelog: 1.3.0 - getSpieltagPartien() liest jetzt zusätzlich extra_data (defensiv geprüft wie status, für Installationen vor der Migration). getAllLigaPartien() bekommt einen neuen, optionalen $ligaId-Parameter, der _liga_id in jede Partie einträgt (Grundlage für die sport-profil-abhängige Anzeige, bestehende Aufrufer bleiben unverändert).
- Changelog: 1.1.0 - getSpieltagPartien() liefert jetzt zusätzlich die Team-Kurzcodes (heim_kurz/gast_kurz) mit, für die Spaltenköpfe der neuen Tippeinsicht-Matrix.
- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Spieltag-Abfragen (getAllSpieltage, getMaxSpieltagNummer, getLatestSpieltagWithResults, getSpieltagByNummer, getSpieltagPartien).

## src/Liga/StandingsTrait.php

- Changelog: 1.6.0 - Volleyball-Punktevergabe (Beitrag: Torsten Hofmann, gegen meinen aktuellen 1.5.0-Stand nachgezogen statt Torstens älterer 1.4.1-Version, um mein "ab Spieltag"-Feature nicht zu verlieren): satzabhängige Punkte + w30/w31/w32/l23/l13/l03/Ballpunkte-Statistik, wirkt nur bei sport_type=volleyball, alle anderen Sportarten (inkl. football als Standardwert) unverändert. Live an einer Volleyball-Testliga (3:1-Ergebnis) UND einer bestehenden Fußball-Liga (Regressionstest) bestätigt.
- Changelog: 1.5.0 - Torstens "ab Spieltag"-Feature integriert (Beitrag Torsten Hofmann): eine Strafe/ein Bonus kann jetzt erst ab einem bestimmten Spieltag greifen (neue Spalte ab_spieltag, neuer Parameter $currentSpieltag in computeStandings()), sinnvoll bei Lizenzentzügen, die erst mitten in der Saison bekannt werden. computePositionTrend() übergibt jetzt korrekt den jeweiligen Spieltag-Kontext an beide internen computeStandings()-Aufrufe. WICHTIGER BUGFIX (vorbestehend, nicht durch diese Änderung verursacht, aber dadurch aufgedeckt): "PDO::FETCH_COLUMN" ohne führenden Backslash wurde innerhalb des Namespace LMOnext\Liga fälschlich als LMOnext\Liga\PDO aufgelöst - dadurch schlug die komplette Schema-Migration in ensureStrafpunkteSchema() bisher IMMER mit einer stillschweigend verschluckten Exception fehl. Nie aufgefallen, weil admin/handler_settings.php dieselben Spalten über einen eigenen Migrationscode im globalen Namespace ohnehin schon anlegte.
- Changelog: 1.5.0 - Beitrag von Torsten Hofmann integriert (aus lmonext_plastic.zip, gegen Torstens veralteten Ausgangsstand neu aufgebaut statt direkt übernommen, da er auf einem älteren Commit basierte): computeStandings() bekommt einen $mode-Parameter ('overall'/'home'/'away') für Heim-/Auswärts-Tabellen, mit der bestehenden Strafpunkte-/Minuspunkte-Logik zusammengeführt. Neue Funktionen computeLast5Form() (Form der letzten 5 Spiele) und computePositionTrend() (Positionsänderung zum vorherigen Spieltag) - Letztere so angepasst, dass sie sich korrekt auf den gerade angezeigten Spieltag bezieht statt immer auf den letzten der Saison, damit sie mit der Tabelle-nach-Spieltag-Navigation zusammenspielt.
- Changelog: 1.4.1 - Die Fußnote erscheint jetzt schon, sobald "Grund" befüllt ist - unabhängig davon, ob überhaupt eine der vier Zahlenkorrekturen (Punkte/erzielte Tore/Gegentore/Minuspunkte) von 0 abweicht. Vorher wurde ein reiner Grund ohne Zahlenänderung fälschlich unterdrückt
- Changelog: 1.4.0 - Strafpunkte-Begründungen erscheinen jetzt automatisch als Fußnoten unter der Tabelle, im Wikipedia-Stil ("(1) Begründungstext") - neue Funktionen assignStrafFootnotes() (vergibt fortlaufende Nummern in Tabellenreihenfolge, nur an Teams mit Grund UND tatsächlichem Effekt) und renderStrafFootnotes() (baut die Liste). renderStrafHinweis() zeigt bei vorhandenem Grund jetzt eine anklickbare Fußnoten-Nummer "(N)" statt nur eines Warnsymbols, Tooltip mit den genauen Deltas bleibt zusätzlich erhalten
- Changelog: 1.3.0 - (1) Vierten Korrekturwert "minuspunkte_korrektur" ergänzt, damit die separate Minuspunkte-Anzeige ebenfalls (z.B. auf 0) korrigierbar ist - vorher blieb sie bei einer Punkte-Korrektur unverändert bestehen; (2) die eigentliche Vorzeichen- Eingabe (Minuszeichen auf Mobilgeräten oft nicht erreichbar) wurde in admin/view_liga_settings.php auf ein Dropdown (+/−) plus Betragsfeld umgestellt - hier nur die Datenschicht dafür erweitert
- Changelog: 1.2.0 - (1) Minuspunkte-Anzeige (Admin-Einstellung "MinusPoints" existierte schon lange, wurde aber nirgends gelesen) - neue Berechnung der klassischen "Gewinnpunkte:Verlustpunkte"-Darstellung je Team, respektiert das jeweils konfigurierte Punktesystem statt fest 2/1/0 anzunehmen; (2) Strafen/Bonus-Feature erweitert: dritter Korrekturwert "tore_korrektur" (erzielte Tore) ergänzt, damit Punkte UND beide Tor-Werte unabhängig voneinander mit +/- korrigiert werden können (z.B. Lizenzentzug: Team komplett auf 0:0/0 setzen). Alle drei Werte jetzt klar als vorzeichenbehaftet (Bonus/Strafe) dokumentiert und im Tooltip entsprechend mit korrektem Vorzeichen angezeigt
- Changelog: 1.1.0 - Neues Feature "Strafpunkte/Straftore": computeStandings() bekommt einen optionalen $ligaId-Parameter (Rückwärtskompatibel, Standard null = kein Verhaltenswechsel für bestehende Aufrufer ohne Liga-Bezug) und zieht damit admin-seitig hinterlegte Strafpunkte von den regulär berechneten Punkten ab bzw. addiert Straftore zu den Gegentoren, VOR der finalen Sortierung - wirkt sich also korrekt auf Tabellenplatz und Tordifferenz aus. Neue Funktionen getLigaStrafpunkte()/setLigaStrafpunkte() plus neue Tabelle liga_strafpunkte (per ensureStrafpunkteSchema() bei Bedarf angelegt, auch auf Bestandsinstallationen ohne erneuten install.php-Lauf)
- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Tabellenberechnung (computeStandings, computeStandingsMarkerColor, renderStandingsView).

## src/Liga/StatisticsTrait.php

- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Ligastatistik (Serien, Extremwerte, Team-Detailstatistik, renderLigastatistikView).

## src/Liga/TeamFormattingTrait.php

- Changelog: 1.0.3 - Logo-Format-Priorität für Browser-Ausgabe und PDF-Export getrennt - Browser zeigt jetzt wieder SVG zuerst (beste Qualität), PDF-Export bleibt bei Rasterformaten zuerst (zuverlässiger). Ich habe nur den Bug behoben, der die Trennung verhinderte (siehe frontend/data_liga.php).
- Changelog: 1.0.2 - Logo-Format-Priorität umgekehrt - Rasterformate (jpg/png/gif) werden jetzt VOR svg gesucht. Grund: SVG-Rasterung für den PDF-Export ist je nach Server unterschiedlich zuverlässig, während JPG/PNG über GD garantiert funktionieren.
- Changelog: 1.0.2 - Logo-Format-Priorität umgekehrt - Rasterformate (jpg/png/gif) werden jetzt VOR svg gesucht, nicht mehr danach. Grund: SVG-Rasterung für den PDF-Export ist je nach Server unterschiedlich zuverlässig (mehrere SVG-Logos wurden falsch/gar nicht im PDF dargestellt), während JPG/PNG über GD garantiert funktionieren - wer beide Formate hinterlegt, bekommt jetzt automatisch die zuverlässigere Variante
- Changelog: 1.0.1 - Kritischer Bugfix: findTeamLogoPathFrontend() suchte Team-Logos unter src/assets/img/teams/ statt im echten assets/img/teams/-Ordner im Projekt-Root. Ursache: dirname(__DIR__) geht nur eine Verzeichnisebene hoch - korrekt für admin/bootstrap.php (liegt 1 Ebene unter Root), aber diese Datei liegt unter src/Liga/, also 2 Ebenen unter Root. Jede Besucheransicht zeigte dadurch für JEDES Team immer nur den "kein Logo"-Platzhalter, obwohl im Admin hochgeladene Logos korrekt vorhanden waren. Jetzt dirname(__DIR__, 2)
- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Team-Anzeige/Logos in Partie-Zeilen (partieTeamName, findTeamLogoPathFrontend, renderTeamLogoImg(Wrapped), partieTeamNameWithLogo(Reversed)).

## src/Liga/TeamRepositoryTrait.php

- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). Team-Listen und -Auflösung je Liga (getLigaTeamsList, resolveTeamNumberToId, getDummyTeamId, getAllLigaPartien).

## src/Liga/TournamentTrait.php

- Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen Kontext der Umstellung). KO-Turnier-Hilfsfunktionen (koRoundName, roundDisplayName, groupPartienByPairing, reorderBracketPairings, renderBracketView).

## template/addon/ewige/standard.tpl.php

- Changelog: 1.2.0 - Copyright-Platzhalter ergänzt (Beitrag: Torsten Hofmann).
- Changelog: 1.0.1 - Die drei Punkte-Spalten waren alle nur mit "Pkt" beschriftet, nicht unterscheidbar - jetzt "Pkt (hist.)"/"Pkt (2er)"/"Pkt (3er)" mit erklärendem title-Tooltip Ewige Tabelle (aufsummierte Stände über mehrere Ligen), gleiche Optik wie template/addon/mini/standard.tpl.php (LMOnext-Look: #153A8C, Rahmen #e3e7ee).

## template/addon/mini/mininext.tpl.php

- Changelog: 1.2.1 - Neuer `<!--Copyright-->`-Platzhalter in der Fußzeile, unter dem bestehenden Datumshinweis (siehe lmo-mininext.php 1.0.4).
- Changelog: 1.2.0 - Auf Wunsch zurückgebaut: Logo steht wieder über dem Teamnamen (gestapelt) statt daneben – bei langen Namen wie "Bayer 04 Leverkusen" führte die horizontale Anordnung zu abgeschnittenem Text ("Bayer 04 Leve..."). Gleiche Rückänderung im "Vorheriges Spiel"-Block
- Changelog: 1.1.0 - Team-Anzeige auf Wunsch umgebaut: Logo steht jetzt neben dem Namen statt darüber ("TEAMNAME LOGO -:- LOGO TEAMNAME", Logos "schauen" zum Ergebnis in der Mitte), analog zur Ergebnisse-Ansicht der normalen Besucherseite. CSS-Selektor für die Logo-Größe generisch auf "img im Team-Bereich" gesetzt statt auf die globale .team-logo-inline-Klasse (existiert auf dieser eigenständigen Seite nicht). Gleiche Anpassung im "Vorheriges Spiel"-Block
- Changelog: 1.0.1 - box-sizing:border-box + display:inline-block ergänzt (gleiche defensive Absicherung wie beim Minitabelle-Template-Fix, siehe standard.tpl.php 1.1.0) gegen abweichende CSS-Regeln auf der Zielseite
- Changelog: 1.0.0 - Initiale Version, angelehnt an das gleichnamige Template des alten LMO (siehe template/mini/mininext.tpl.php dort, vom Nutzer als Referenz bereitgestellt), aber mit eigenständigem, modernem CSS passend zum LMOnext-Look. Läuft komplett unabhängig vom restlichen LMOnext-Stylesheet, da dieses Widget auf fremden Webseiten eingebunden wird (kein Zugriff auf die dortigen CSS-Variablen). Platzhalternamen bewusst identisch zur Referenzvorlage gehalten, siehe addon/mini/lmo-mininext.php. Reines Markup + Platzhalter, kein PHP.

## template/addon/mini/standard.tpl.php

- Changelog: 1.2.0 - Neue Logo-Spalte zwischen Tabellenplatz und Teamname (Platzhalter "Logo", siehe lmo-minitab.php 1.2.0), eigenständiges CSS (kein Zugriff auf die globale .team-logo-inline-Klasse des Hauptstylesheets, da diese Tabelle auf fremden Webseiten läuft)
- Changelog: 1.1.0 - Bugfix: Tabelle hatte keine Breitenbegrenzung – auf Zielseiten mit eigenen CSS-Regeln für <table> (z.B. eine übliche "table{width:100%}"-Reset-Regel) zog sich das Widget dadurch über die volle Seitenbreite statt kompakt zu bleiben. Jetzt in einen eigenen Wrapper mit max-width + display:inline-block verpackt, der von den CSS-Regeln der Zielseite unabhängig ist
- Changelog: 1.0.0 - Initiale Version, angelehnt an das gleichnamige Template des alten LMO (siehe template/mini/standard.tpl.php dort), aber mit eigenständigem, modernem CSS passend zum LMOnext-Look. Läuft komplett unabhängig vom restlichen LMOnext-Stylesheet, da diese Tabelle auf fremden Webseiten eingebunden wird (kein Zugriff auf die dortigen CSS-Variablen). Reines Markup + Platzhalter (siehe addon/mini/lmo-minitab.php), kein PHP.

## template/default/home.tpl.php

- Changelog: 1.1.0 - Neuer Platzhalter "TippspielCard" ergänzt (siehe home.php 2.2.0) Inhalt der Besucher-Startseite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom Root-Controller home.php.

## template/default/liga.tpl.php

- Changelog: 2.1.0 - "ZurueckLinkBlock" (vollständiger HTML-Block statt nur Text) ersetzt die bisher fest verankerte Verlinkung, damit sich der Link über die neue globale Einstellung "Übersicht-Link anzeigen?" komplett ausblenden lässt (siehe renderBackLinkBlock() in liga.php) Inhalt der Liga-Detailseite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom Root-Controller liga.php. "TabsBar" und "ViewInhalt" sind bereits fertige HTML-Blöcke – ViewInhalt enthält je nach gewähltem Reiter (Kalender/Ergebnisse/Spielpläne/Info) unterschiedlichen Inhalt.

## template/default/liga_not_found.tpl.php

- Changelog: 1.1.0 - "ZurueckLinkBlock" ersetzt die fest verankerte Verlinkung (siehe liga.tpl.php 2.1.0) Wird gezeigt, wenn liga.php mit einer ungültigen/unbekannten id aufgerufen wird.

## template/default/partials/archiv_card.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/archiv_folder.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/bracket_pairing.tpl.php

- Changelog: 1.2.0 - Neue Zeile mit Direkter-Vergleich-Icon zwischen Ergebnis und Anstoßtermin
- Changelog: 1.1.0 - Anstoßtermin-Zeile ergänzt (nur befüllt, wenn in den Liga-Einstellungen aktiviert; sonst leer und unsichtbar)
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/bracket_round.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/bracket_view.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/fieberkurve_view.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/info_view.tpl.php

- Changelog: 1.2.0 - Links zu Homepage + Forum ergänzt
- Changelog: 1.1.0 - Projektname auf "LMOnext" umgestellt (vorher "OLVBoard")

## template/default/partials/kalender_day.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/kalender_entry.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/kalender_view.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/kalender_week.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/kreuz_cell.tpl.php

- Changelog: 1.1.0 - data-row/data-col ergänzt, damit per Klick eine beliebige Mannschaft hervorgehoben werden kann (siehe kreuz_view.tpl.php)
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/kreuz_header_cell.tpl.php

- Changelog: 1.2.0 - data-team ergänzt, damit per Klick eine beliebige Mannschaft hervorgehoben werden kann (siehe kreuz_view.tpl.php)
- Changelog: 1.1.0 - HeaderClass ergänzt, damit die favTeam-Spalte hervorgehoben werden kann
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/kreuz_row.tpl.php

- Changelog: 1.2.0 - data-team ergänzt, damit per Klick eine beliebige Mannschaft hervorgehoben werden kann (siehe kreuz_view.tpl.php)
- Changelog: 1.1.0 - RowLabelClass ergänzt, damit die favTeam-Zeile hervorgehoben werden kann
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/kreuz_view.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/liga_list_item.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/partie_row.tpl.php

- Changelog: 1.2.0 - CompareIcon-Spalte ergänzt (Direkter-Vergleich-Icon)
- Changelog: 1.1.0 - HeimClass/GastClass ergänzt, damit die Lieblingsmannschaft (favTeam-Einstellung) fett hervorgehoben werden kann
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/results_table.tpl.php

- Changelog: 1.1.0 - Leere Kopfspalte für das Direkter-Vergleich-Icon ergänzt
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/spieltag_option.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/spieltag_picker.tpl.php

- Changelog: 1.1.0 - Neuer Platzhalter "View" im onchange-Ziel, damit derselbe Picker auf verschiedene Reiter navigieren kann (z.B. "tabelle" statt immer "ergebnisse").
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/standings_row.tpl.php

- Changelog: 1.5.0 - Neue Platzhalter "Form"/"Trend" (Beitrag Torsten Hofmann).
- Changelog: 1.4.0 - Neuer Platzhalter "StrafHinweis" (⚠-Marker mit Tooltip bei Strafpunkten/ Straftoren, siehe renderStrafHinweis() in src/Liga/StandingsTrait.php)
- Changelog: 1.3.0 - Platzhalter "Logo" von "Team" getrennt (eigenes <span> mit fester Breite), damit die Teamnamen untereinander bündig ausgerichtet bleiben, auch wenn die Logos unterschiedlich breit sind (siehe .st-team-logo-wrap CSS)
- Changelog: 1.2.0 - Neuer Platzhalter "RowStyle" für die farbige Rand-Markierung (Champions League/Europa League/Relegation/Abstieg usw., siehe Admin → Liga- Einstellungen → Tabelle → Tabellenmarkierungen)
- Changelog: 1.1.0 - TeamClass ergänzt, damit die Lieblingsmannschaft (favTeam-Einstellung) fett hervorgehoben werden kann
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/standings_view.tpl.php

- Changelog: 1.4.0 - Neue Spaltenüberschriften "ColForm"/"ColTrend" (Beitrag Torsten Hofmann).
- Changelog: 1.3.0 - Neue Platzhalter "SpieltagNavOben"/"SpieltagNavUnten" für die vorheriger/nächster-Spieltag-Navigation (siehe RenderViewsTrait.php 1.4.0).
- Changelog: 1.2.0 - Neuer Platzhalter "Fussnoten" für die Strafpunkte-Begründungen im Wikipedia-Stil ("(1) Begründungstext" unter der Tabelle, siehe renderStrafFootnotes() in src/Liga/StandingsTrait.php)
- Changelog: 1.1.0 - Wertungshinweis-Zeile entfernt
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/stats_block.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/tab_item.tpl.php

- Changelog: 1.1.0 - Neuer Platzhalter "NrParam" (hängt bei Bedarf "&nr=N" an den Tab-Link an, siehe RenderViewsTrait.php 1.5.0).
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/tabs_bar.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/team_schedule_row.tpl.php

- Changelog: 1.1.0 - CompareIcon-Spalte ergänzt (Direkter-Vergleich-Icon)
- Changelog: 1.0.0 - Initiale Version

## template/default/partials/team_schedule_table.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/team_schedule_view.tpl.php

- Changelog: 1.0.0 - Initiale Version

## template/default/partials/team_sidebar_item.tpl.php

- Changelog: 1.1.0 - Neuer Platzhalter "Logo" (Team-Logo/Platzhalter, nur wenn die Liga-Einstellung "Logo anzeigen" aktiv ist, sonst leer)
- Changelog: 1.0.0 - Initiale Version

## template/default/tippspiel.tpl.php

- Changelog: 1.2.0 - Tippspiel-Logo direkt vor die bestehende Seiten-Überschrift gesetzt (behebt die Dopplung aus view_tippspiel_frontend.php 1.20.0). Live geprüft: nur noch eine "Tippspiel"-Überschrift auf der Seite.
- Changelog: 1.1.0 - Neuer Platzhalter "ZurueckLinkBlock" ergänzt (Link zur Liga-Übersicht, fehlte bisher komplett - siehe home.php 2.3.1)
- Changelog: 1.0.0 - Initiale Version: Tippspiel läuft jetzt als eigene Seite innerhalb des Templates (?view=tippspiel auf home.php), analog zu liga.tpl.php. Anders als bei liga.tpl.php gibt es hier KEINEN separaten "TabsBar"-Platzhalter - die Tab-Leiste ist bereits Teil von "ViewInhalt" (siehe renderTippspielUserBar()/renderTippspielTabsBar() in addon/tipp/view_tippspiel_frontend.php), da sie nur bei den drei eingeloggt-erforderlichen Ansichten erscheint, nicht bei Login/Registrierung. Inhalt der Tippspiel-Seite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom Root-Controller home.php über renderTippspielView(). "ZurueckLinkBlock" kommt von renderBackLinkBlock() (dieselbe Funktion wie bei liga.tpl.php), respektiert die globale Einstellung "Übersicht-Link anzeigen?".

## template/matchday/home.tpl.php

- Changelog: 1.1.0 - Neuer Platzhalter "TippspielCard" ergänzt (siehe home.php 2.2.0)
- Changelog: 1.0.0 - Initiale Version (eigenständiges Template, siehe layout.tpl.php) Inhalt der Besucher-Startseite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom Root-Controller home.php.

## template/matchday/liga.tpl.php

- Changelog: 1.1.0 - "ZurueckLinkBlock" ersetzt die fest verankerte Verlinkung, damit sich der Link über die neue globale Einstellung "Übersicht-Link anzeigen?" komplett ausblenden lässt (siehe renderBackLinkBlock() in liga.php)
- Changelog: 1.0.0 - Initiale Version (eigenständiges Template, siehe layout.tpl.php) Inhalt der Liga-Detailseite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom Root-Controller liga.php. "TabsBar" und "ViewInhalt" sind bereits fertige HTML-Blöcke.

## template/matchday/liga_not_found.tpl.php

- Changelog: 1.1.0 - "ZurueckLinkBlock" ersetzt die fest verankerte Verlinkung (siehe liga.tpl.php 1.1.0)
- Changelog: 1.0.0 - Initiale Version (eigenständiges Template, siehe layout.tpl.php) Wird gezeigt, wenn liga.php mit einer ungültigen/unbekannten id aufgerufen wird.

## template/matchday/partials/standings_row.tpl.php

- Changelog: 1.2.0 - Neue Platzhalter "Form"/"Trend" (siehe default 1.5.0).
- Changelog: 1.1.0 - Neuer Platzhalter "StrafHinweis" (⚠-Marker mit Tooltip bei Strafpunkten/ Straftoren, siehe default 1.4.0 für die Begründung)

## template/matchday/partials/standings_view.tpl.php

- Changelog: 1.3.0 - Neue Spaltenüberschriften "ColForm"/"ColTrend" (siehe default 1.4.0).
- Changelog: 1.2.0 - Neue Platzhalter "SpieltagNavOben"/"SpieltagNavUnten" (siehe default 1.3.0).
- Changelog: 1.1.0 - Neuer Platzhalter "Fussnoten" (siehe default 1.2.0)

## template/matchday/tippspiel.tpl.php

- Changelog: 1.2.0 - Wie template/default/tippspiel.tpl.php 1.2.0. Live geprüft (exakt das vom Nutzer gemeldete Template): nur noch eine "Tippspiel"-Überschrift auf der Seite.
- Changelog: 1.1.0 - Neuer Platzhalter "ZurueckLinkBlock" ergänzt (siehe home.php 2.3.1)
- Changelog: 1.0.0 - Initiale Version (eigenständiges Template, siehe layout.tpl.php). Tippspiel läuft jetzt als eigene Seite innerhalb des Templates (?view=tippspiel auf home.php), analog zu liga.tpl.php. Kein separater "TabsBar"-Platzhalter - siehe default/tippspiel.tpl.php für die Begründung. Inhalt der Tippspiel-Seite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom Root-Controller home.php über renderTippspielView(). "ZurueckLinkBlock" kommt von renderBackLinkBlock() (dieselbe Funktion wie bei liga.tpl.php).

## template/default/layout.tpl.php

- Changelog: 1.17.10 - Diagonale Spaltenüberschriften nochmal korrigiert: Drehpunkt liegt jetzt fest am linken Rand der jeweils eigenen Spalte (left:2px, kein translateX(-50%)/left:50% mehr), statt von der Textlänge abhängig zu sein. Grund: die vorherige "left:50% + translateX(-50%)"-Zentrierung ließ den tatsächlichen Drehpunkt bei langen Wörtern wie "Niederlagen" spürbar in die linke Nachbarspalte hineinwandern (im Livesystem als Screenshot bestätigt: "Niederlagen" überlappte sichtbar mit "Siege"). Die getestete Alternative "origin: center bottom" (siehe 1.17.7-1.17.8) verursachte stattdessen ein Hineinragen in die Datenzeile. Der jetzt feste Drehpunkt je Spalte vermeidet beides: jede Beschriftung beginnt exakt an ihrer eigenen Spalte, unabhängig von der Wortlänge. Mit Playwright unter realistischen schmalen Spaltenbreiten verifiziert.
- Changelog: 1.17.9 - table.standings-table thead th: vertical-align:bottom ergänzt, damit auch die (jetzt teils horizontalen, siehe VolleyballProfile.php 1.2.0) Kopfzellen unten in der Zeile sitzen und mit den diagonalen Beschriftungen auf einer Linie bleiben, statt vertikal mittig in der durch die diagonalen Nachbarspalten hohen Kopfzeile zu "schweben". Wirkt sich nur auf Tabellen mit mindestens einer diagonalen Spalte aus (nur dort ist die Kopfzeile überhaupt höher als der reine Zellinhalt) - kurz/mittel-Ansichten unverändert.
- Changelog: 1.17.8 - Regression aus 1.17.7 zurückgenommen: transform-origin zurück auf "left bottom" gesetzt. "center bottom" zentrierte den Drehpunkt zwar exakt, führte aber bei den tatsächlich sehr schmalen Datenspalten (Breite richtet sich nach dem kurzen Zahlenwert, nicht nach der langen Kopfzeile) zu Textüberlappungen zwischen benachbarten Diagonal-Überschriften und mit der Datenzeile darunter - im echten Livesystem bestätigt (Screenshot). Mit Playwright unter realistischen, schmalen Spaltenbreiten nachgestellt und verifiziert, dass "left bottom" hier weniger überlappt, bevor die Rücknahme übernommen wurde.
- Changelog: 1.17.7 - Diagonale Spaltenüberschriften der Sport-Tabelle (.st-diag) besser zentriert: transform-origin des rotierten <span> von "left bottom" auf "center bottom" geändert. Ursache des bisherigen Versatzes: transform-origin ist ein fester Punkt in den lokalen (unrotierten) Koordinaten der Beschriftung, unabhängig von der vorherigen translateX(-50%)-Zentrierung - dadurch lag der tatsächliche Drehpunkt sichtbar links der Spaltenmitte (die Beschriftung "wanderte" nach der Rotation zusätzlich um die halbe Textbreite nach links). Mit "center bottom" liegt der Drehpunkt exakt auf der Spaltenmittellinie. Mit Playwright-Screenshot-Vergleich (mehrere transform-origin-Varianten nebeneinander gerendert, Spaltenmittellinie als Hilfslinie eingeblendet) vor der Umsetzung verifiziert.
- Changelog: 1.17.6 - CSS für das neue Tippspiel-Link-Icon und die neue Tippspiel-Bereichsüberschrift ergänzt.
- Changelog: 1.17.5 - CSS für das neue Dropdown-Team-Auswahl (nur Volleyball) ergänzt.
- Changelog: 1.17.4 - CSS für diagonale Spaltenüberschriften ergänzt.
- Changelog: 1.17.3 - CSS-Klasse für die höhere Diagramm-Box der vollständigen Mannschafts-Punkteliste ergänzt.
- Changelog: 1.17.2 - CSS für die Diagramm-Erklärungstexte ergänzt.
- Changelog: 1.17.1 - CSS für den Tipptabelle-Hinweisbanner ergänzt.
- Changelog: 1.17.0 - CSS für das Statistik-Diagramm-Grid ergänzt.
- Changelog: 1.16.9 - Bugfix: das "−"-Zeichen (Minus) für aufgeklappte Spielregeln-Abschnitte zeigte stattdessen eine "2" an. Ursache: beim Einfügen der CSS-Regel wurde die Unicode-Escape-Sequenz durch eine fehlerhafte Zwischenverarbeitung falsch kodiert. Auf Byte-Ebene korrigiert und geprüft, dass keine weiteren Stellen im Projekt denselben Fehler haben.
- Changelog: 1.16.8 - CSS für die neue Spielregeln-Seite (Accordion-Blöcke, Punkte-Karten) ergänzt.
- Changelog: 1.16.7 - CSS für das "archiviert"-Badge in Tippeinsicht/Gesamtübersicht ergänzt.
- Changelog: 1.16.6 - CSS für die Spieltagssieger-Hervorhebung in der Gesamtübersicht ergänzt.
- Changelog: 1.16.5 - CSS für die neue Tippeinsicht-Matrix (.tipp-matrix) ergänzt.
- Changelog: 1.16.4 - CSS für Form-Dots/Trend-Pfeile/Tabellen-Modus-Navigation ergänzt (Beitrag Torsten Hofmann).
- Changelog: 1.16.3 - CSS für die neue Spieltag-Navigation (.st-spieltag-nav) in der Tabellenansicht ergänzt.

## template/colored/layout.tpl.php

- Changelog: 1.6.10 - Fester Drehpunkt am linken Spaltenrand wie default 1.17.10.
- Changelog: 1.6.9 - vertical-align:bottom ergänzt wie default 1.17.9.
- Changelog: 1.6.8 - Regression aus 1.6.7 zurückgenommen, siehe default 1.17.8.
- Changelog: 1.6.7 - transform-origin-Korrektur wie default 1.17.7 (bessere Zentrierung der diagonalen Spaltenüberschriften).
- Changelog: 1.6.6 - CSS wie default 1.17.6 ergänzt.
- Changelog: 1.6.5 - CSS wie default 1.17.5 ergänzt.
- Changelog: 1.6.4 - CSS wie default 1.17.4 ergänzt.
- Changelog: 1.6.3 - CSS wie default 1.17.3 ergänzt.
- Changelog: 1.6.2 - CSS für die Diagramm-Erklärungstexte ergänzt (siehe default 1.17.2).
- Changelog: 1.6.1 - CSS für den Tipptabelle-Hinweisbanner ergänzt (siehe default 1.17.1).
- Changelog: 1.6.0 - CSS für das Statistik-Diagramm-Grid ergänzt (siehe default 1.17.0).
- Changelog: 1.5.9 - Bugfix wie default 1.16.9 (falsches Minus-Zeichen bei aufgeklappten Spielregeln-Abschnitten).
- Changelog: 1.5.8 - CSS für die Spielregeln-Seite ergänzt (siehe default 1.16.8).
- Changelog: 1.5.7 - CSS für das "archiviert"-Badge ergänzt (siehe default 1.16.7).
- Changelog: 1.5.6 - CSS für die Spieltagssieger-Hervorhebung ergänzt (siehe default 1.16.6).
- Changelog: 1.5.5 - CSS für die neue Tippeinsicht-Matrix ergänzt (siehe default 1.16.5).
- Changelog: 1.5.4 - CSS für Form-Dots/Trend-Pfeile/Tabellen-Modus-Navigation ergänzt (siehe default 1.16.4).
- Changelog: 1.5.3 - CSS für die neue Spieltag-Navigation ergänzt (siehe default 1.16.3).

## template/dark/layout.tpl.php

- Changelog: 1.5.10 - Fester Drehpunkt am linken Spaltenrand wie default 1.17.10.
- Changelog: 1.5.9 - vertical-align:bottom ergänzt wie default 1.17.9.
- Changelog: 1.5.8 - Regression aus 1.5.7 zurückgenommen, siehe default 1.17.8.
- Changelog: 1.5.7 - transform-origin-Korrektur wie default 1.17.7 (bessere Zentrierung der diagonalen Spaltenüberschriften).
- Changelog: 1.5.6 - CSS wie default 1.17.6 ergänzt.
- Changelog: 1.5.5 - CSS wie default 1.17.5 ergänzt.
- Changelog: 1.5.4 - CSS wie default 1.17.4 ergänzt.
- Changelog: 1.5.3 - CSS wie default 1.17.3 ergänzt.
- Changelog: 1.5.2 - CSS für die Diagramm-Erklärungstexte ergänzt (siehe default 1.17.2).
- Changelog: 1.5.1 - CSS für den Tipptabelle-Hinweisbanner ergänzt (siehe default 1.17.1).
- Changelog: 1.5.0 - CSS für das Statistik-Diagramm-Grid ergänzt (siehe default 1.17.0).
- Changelog: 1.4.9 - Bugfix wie default 1.16.9 (falsches Minus-Zeichen bei aufgeklappten Spielregeln-Abschnitten).
- Changelog: 1.4.8 - CSS für die Spielregeln-Seite ergänzt (siehe default 1.16.8).
- Changelog: 1.4.7 - CSS für das "archiviert"-Badge ergänzt (siehe default 1.16.7).
- Changelog: 1.4.6 - CSS für die Spieltagssieger-Hervorhebung ergänzt (siehe default 1.16.6).
- Changelog: 1.4.5 - CSS für die neue Tippeinsicht-Matrix ergänzt (siehe default 1.16.5).
- Changelog: 1.4.4 - CSS für Form-Dots/Trend-Pfeile/Tabellen-Modus-Navigation ergänzt (siehe default 1.16.4).
- Changelog: 1.4.3 - CSS für die neue Spieltag-Navigation ergänzt (siehe default 1.16.3).

## template/light/layout.tpl.php

- Changelog: 1.5.10 - Fester Drehpunkt am linken Spaltenrand wie default 1.17.10.
- Changelog: 1.5.9 - vertical-align:bottom ergänzt wie default 1.17.9.
- Changelog: 1.5.8 - Regression aus 1.5.7 zurückgenommen, siehe default 1.17.8.
- Changelog: 1.5.7 - transform-origin-Korrektur wie default 1.17.7 (bessere Zentrierung der diagonalen Spaltenüberschriften).
- Changelog: 1.5.6 - CSS wie default 1.17.6 ergänzt.
- Changelog: 1.5.5 - CSS wie default 1.17.5 ergänzt.
- Changelog: 1.5.4 - CSS wie default 1.17.4 ergänzt.
- Changelog: 1.5.3 - CSS wie default 1.17.3 ergänzt.
- Changelog: 1.5.2 - CSS für die Diagramm-Erklärungstexte ergänzt (siehe default 1.17.2).
- Changelog: 1.5.1 - CSS für den Tipptabelle-Hinweisbanner ergänzt (siehe default 1.17.1).
- Changelog: 1.5.0 - CSS für das Statistik-Diagramm-Grid ergänzt (siehe default 1.17.0).
- Changelog: 1.4.9 - Bugfix wie default 1.16.9 (falsches Minus-Zeichen bei aufgeklappten Spielregeln-Abschnitten).
- Changelog: 1.4.8 - CSS für die Spielregeln-Seite ergänzt (siehe default 1.16.8).
- Changelog: 1.4.7 - CSS für das "archiviert"-Badge ergänzt (siehe default 1.16.7).
- Changelog: 1.4.6 - CSS für die Spieltagssieger-Hervorhebung ergänzt (siehe default 1.16.6).
- Changelog: 1.4.5 - CSS für die neue Tippeinsicht-Matrix ergänzt (siehe default 1.16.5).
- Changelog: 1.4.4 - CSS für Form-Dots/Trend-Pfeile/Tabellen-Modus-Navigation ergänzt (siehe default 1.16.4).
- Changelog: 1.4.3 - CSS für die neue Spieltag-Navigation ergänzt (siehe default 1.16.3).

## template/matchday/layout.tpl.php

- Changelog: 1.2.10 - Fester Drehpunkt am linken Spaltenrand wie default 1.17.10.
- Changelog: 1.2.9 - vertical-align:bottom ergänzt wie default 1.17.9.
- Changelog: 1.2.8 - Regression aus 1.2.7 zurückgenommen, siehe default 1.17.8.
- Changelog: 1.2.7 - transform-origin-Korrektur wie default 1.17.7 (bessere Zentrierung der diagonalen Spaltenüberschriften).
- Changelog: 1.2.6 - CSS wie default 1.17.6 ergänzt.
- Changelog: 1.2.5 - CSS wie default 1.17.5 ergänzt.
- Changelog: 1.2.4 - CSS wie default 1.17.4 ergänzt.
- Changelog: 1.2.3 - CSS wie default 1.17.3 ergänzt.
- Changelog: 1.2.2 - CSS für die Diagramm-Erklärungstexte ergänzt (siehe default 1.17.2).
- Changelog: 1.2.1 - CSS für den Tipptabelle-Hinweisbanner ergänzt (siehe default 1.17.1).
- Changelog: 1.2.0 - CSS für das Statistik-Diagramm-Grid ergänzt (siehe default 1.17.0).
- Changelog: 1.1.9 - Bugfix wie default 1.16.9 (falsches Minus-Zeichen bei aufgeklappten Spielregeln-Abschnitten).
- Changelog: 1.1.8 - CSS für die Spielregeln-Seite ergänzt (siehe default 1.16.8).
- Changelog: 1.1.7 - CSS für das "archiviert"-Badge ergänzt (siehe default 1.16.7).
- Changelog: 1.1.6 - CSS für die Spieltagssieger-Hervorhebung ergänzt (siehe default 1.16.6).
- Changelog: 1.1.5 - CSS für die neue Tippeinsicht-Matrix ergänzt (siehe default 1.16.5).
- Changelog: 1.1.4 - CSS für Form-Dots/Trend-Pfeile/Tabellen-Modus-Navigation ergänzt (siehe default 1.16.4).
- Changelog: 1.1.3 - CSS für die neue Spieltag-Navigation ergänzt (siehe default 1.16.3).

## template/matchday/partials/tab_item.tpl.php

- Changelog: 1.1.0 - Neuer Platzhalter "NrParam" (siehe default 1.1.0).

## template/matchday/partials/spieltag_picker.tpl.php

- Changelog: 1.1.0 - Neuer Platzhalter "View" (siehe default 1.1.0).


## src/Template/TemplateEngine.php

- Changelog: 2.7.1 - KRITISCHER Bugfix: renderTemplate() (läuft bei JEDEM Frontend-Seitenaufruf, nicht nur Tippspiel-bezogenen) rief tippRenderSiteLink() unbedingt auf, ohne zu prüfen ob die Funktion überhaupt existiert. Bei deaktiviertem/nicht installiertem tipp-Addon (die Funktion wird nur von addon/tipp/tipp_lib.php bereitgestellt, siehe AddonManager::bootFrontend()) führte das zu einem fatalen "Call to undefined function" auf JEDER Seite, inkl. home.php - Übersehen bei der ursprünglichen Extraktion des Tippspiel-Addons aus dem Core (der analoge Fall in home.php wurde damals über den Hook frontend.home_cards korrekt behoben, dieser zweite Aufrufort hier nicht). Jetzt mit function_exists()-Absicherung, Fallback auf leeren String (identisch zu tippRenderSiteLink()'s eigenem Verhalten bei deaktiviertem Tippspiel).
- Changelog: 2.7.0 - Neues CopyrightNotice-Platzhalter für alle Frontend-Seiten-Templates (Beitrag: Torsten Hofmann).
- Changelog: 1.0.0 - Neu (Beitrag Torsten Hofmann): enthält die tatsächliche Implementierung, vorher direkt in frontend/template_engine.php. 1:1 identisches Verhalten geprüft.

## src/Pdf/PdfExporter.php

- Changelog: AUSGEGLIEDERT (Version 1.11.0 war der letzte Core-Stand) - diese Datei existiert im Core nicht mehr, siehe liga.php 3.13.0: PDF-Export wurde komplett als eigenständiges Addon "pdf-export" ausgegliedert. Neuer Speicherort: addon/pdf-export/PdfExporter.php, eigenes CHANGELOG.md dort. Die gesamte bisherige Versionshistorie (unten) bleibt als Referenz erhalten.
- Changelog: 1.11.0 - Robustheitsverbesserung zu 1.10.1 (nach zwei Vorfällen mit demselben "Rauschmuster statt Logo"-Fehlerbild - siehe 1.9.1 und 1.10.1): pdfLoadLogoData() liest Breite/Höhe des Logos jetzt aus einer neuen, separaten Datei assets/pdf/logo_meta.json statt aus einer im PHP-Code hartkodierten Konstante - wird das Logo künftig neu gerendert, muss nur noch diese eine JSON-Datei angepasst werden. ZUSÄTZLICH wird die angegebene Auflösung gegen die tatsächliche Größe der entpackten Rohdaten geprüft (Alpha-Kanal exakt 1 Byte/Pixel, RGB-Kanal exakt 3 Byte/Pixel) - bei jeder Abweichung (z.B. veraltete/vergessene Metadaten-Aktualisierung) wird das Logo jetzt komplett übersprungen (PDF bleibt ohne Logo, mit error_log()-Eintrag) statt wie bisher ein sichtbar kaputtes Rauschmuster einzubetten. Ein fehlendes Logo fällt kaum auf, ein zerlaufenes Bild dagegen schon.
- Changelog: 1.10.1 - Bugfix (gemeldet: Logo im PDF-Export zeigt ein Streifen-/Rauschmuster statt des echten LMOnext-Logos): dieselbe Problemklasse wie bereits in Version 1.9.1 - die in pdfLoadLogoData() hartkodierten Bildmaße (widthPx/heightPx) waren wieder nicht mehr zur tatsächlichen Auflösung der Rohpixel-Dateien assets/pdf/logo_rgb.zz/logo_alpha.zz passend (353×78 im Code vs. tatsächlich 642×140 - verifiziert durch Dekomprimieren beider Dateien: die dekomprimierte Byte-Anzahl ergab bei keiner Ganzzahl-Kombination nahe 353×78 einen Sinn, wohl aber exakt bei 642×140). Bei falscher angenommener Breite×Höhe "rutschen" die Rohpixel beim Zusammensetzen des PDF-Bildstreams zeilenweise durcheinander - daher das beobachtete Rauschmuster statt eines erkennbaren Bildes. Werte auf 642×140 korrigiert. WIEDERKEHRENDES MUSTER, siehe 1.9.1: sobald das Logo (assets/logo.svg) künftig erneut geändert und die .zz-Rohdaten neu vorbereitet werden, müssen diese beiden Werte an dieser einen Stelle manuell mit angepasst werden - es gibt aktuell keinen automatischen Abgleich zwischen Dateigröße und Code-Konstante.
- Changelog: 1.10.0 - PDF-Tabellenexport (exportTabellePdf()) ist jetzt sportartabhängig, analog zur HTML-Ansicht (RenderViewsTrait::renderStandingsView()): Sportarten mit eigenen Darstellungsmodi (aktuell nur Volleyball) bekommen im PDF dieselben sportartspezifischen Spalten (3:0/3:1/3:2-Siege, Ballquotient, Ballverhältnis, Satzquotient, Satzverhältnis je nach kurz/mittel/vollständig) statt der zuvor IMMER fest verwendeten Fußball-Spalten Sp/S/U/N/Tore/Diff/Pkt - bei Volleyball ergab "U" (Unentschieden) ohnehin keinen Sinn, und die eigentlich relevanten Spalten fehlten komplett (im Livesystem als PDF-Screenshot bestätigt). Neuer optionaler Parameter $tmode (aus liga.php 1:1 aus der URL übernommen, siehe dort), fällt bei fehlendem/ungültigem Wert auf den ersten Modus der Sportart zurück (Volleyball: "kurz", identisch zum HTML-Standardverhalten ohne ?tmode). Zellwerte werden über die neu public gemachte LigaService::resolveStandingsCell() aufgelöst (siehe RenderViewsTrait.php 1.11.2) statt die Logik ein zweites Mal zu duplizieren. buildStandingsPdf() bekommt einen neuen $landscape-Parameter (Querformat statt Hochformat) und wird automatisch aktiviert, sobald die Tabelle mindestens 12 Spalten hat (betrifft aktuell nur Volleyball "vollständig" mit 16 Spalten) - im Hochformat wäre die Tabelle bei so vielen Spalten über den rechten Seitenrand hinausgelaufen, da buildStandingsPdf() Spaltenbreiten grundsätzlich am tatsächlichen Zellinhalt bemisst statt sie auf die Seitenbreite zu stauchen.
- Changelog: 1.9.1 - Feste Logo-Maße in pdfLoadLogoData() an die neuen Rohpixel-Daten angepasst (353×78 statt 320×78).
- Changelog: 1.9.0 - Ergebnis-PDF-Export (exportErgebnissePdf()) sport-profil-abhängig gemacht (Beitrag: Torsten Hofmann, an mein Format angepasst). Live geprüft: beide PDFs (Fußball/Volleyball) korrekt erzeugt, Volleyball zeigt die volle Satz-Formatierung im PDF.
- Changelog: 1.8.2 - KRITISCHER BUGFIX: "Class LMOnext\\Pdf\\Imagick not found" - beim Umbau von frontend/pdf_export.php in diese Klasse wurden mehrere bare globale Klassennamen (Imagick, ImagickPixel, DateTime) sowie ALLE vier "catch (Throwable)"-Blöcke nicht mit einem führenden Backslash versehen. Innerhalb des Namespace LMOnext\\Pdf wurden diese fälschlich als LMOnext\\Pdf\\Imagick usw. aufgelöst statt als globale PHP-Klassen - dadurch stürzte der PDF-Export mit einem unabgefangenen Fatal Error ab, sobald ein SVG-Logo über den Imagick-Rasterungsweg verarbeitet werden sollte (die catch-Blöcke konnten den Fehler nicht abfangen, da auch "Throwable" bare war). Alle Vorkommen auf \\Imagick/\\ImagickPixel/\\DateTime/\\Throwable korrigiert und mit einem SVG-Logo end-to-end verifiziert (kein Absturz mehr, PDF wird korrekt erzeugt). Alle anderen seit dieser Session neu in Namespaces verschobenen Dateien auf denselben Fehler geprüft - keine weiteren Fälle gefunden.
- Changelog: 1.8.1 - BUGFIX (unabhängig vom Logo-Prioritäts-Thema, aber dabei aufgefallen): pdfLoadLogoData() und pdfLoadTeamLogoImage() nutzten noch "dirname(__DIR__)" von der Zeit, als dieser Code direkt in frontend/pdf_export.php lag (eine Ebene unter dem Projekt-Root). Nach dem Umbau in diese Klasse unter src/Pdf/ (zwei Ebenen unter dem Root) zeigte der Pfad fälschlich auf src/assets/... statt auf das echte assets/-Verzeichnis - dadurch wurden das LMOnext-Logo im PDF-Kopf UND alle Team-Logos (PNG/JPEG/GIF/SVG gleichermaßen) seit diesem Umbau nie mehr gefunden. Beide Stellen auf dirname(__DIR__, 2) korrigiert. Zusätzlich: findTeamLogoPathFrontend()-Aufruf für Team-Logos übergibt jetzt explizit "false" (PDF-Modus, Rasterformate zuerst).
- Changelog: 1.0.0 - Neu: enthält die tatsächliche PDF-Export-Implementierung, vorher direkt in frontend/pdf_export.php. Aus dem eigenen vollständigen Stand aufgebaut (nicht aus Torstens Zwischenstand), damit das Strafpunkte-Fußnoten-Feature erhalten bleibt.

## src/Sport/SportProfile.php

- Changelog: 1.0.0 - Neu (Beitrag: Torsten Hofmann): Interface für sportartspezifische Profile (Punktevergabe, Ergebnis-Anzeige, Tabellenspalten). Unverändert übernommen.

## src/Sport/SportRegistry.php

- Changelog: 1.0.0 - Neu (Beitrag: Torsten Hofmann): Factory für Sport-Profile nach Schlüssel. Unverändert übernommen.

## src/Sport/FootballProfile.php

- Changelog: 1.0.1 - Neu (Beitrag: Torsten Hofmann), Bugfix bei der Übernahme: Status-Zusätze ("n.V."/"i.E.") waren fest auf Deutsch einkodiert statt die bestehende Übersetzungsfunktion zu nutzen - hätte für die englische Sprachversion falsche Kürzel gezeigt. Korrigiert, nutzt jetzt wieder tf('liga_status_ie')/tf('liga_status_nv') wie der Rest des Projekts.

## src/Sport/VolleyballProfile.php

- Changelog: 1.2.0 - Diagonale Spaltenüberschriften in der "vollständig"-Ansicht auf die tatsächlich langen Wortbezeichner beschränkt (Spiele/Siege/Niederlagen/Ballquotient/Ballverhältnis/Satzquotient/Satzverhältnis - neues 'diag'=>true je Spalte), die kurzen Ergebnis-Spalten (3:0/3:1/3:2/2:3/1:3/0:3) und "Punkte" bleiben horizontal. Bisher wurden ALLE Spalten der Lang-Ansicht diagonal gestellt (siehe RenderViewsTrait.php 1.11.0 und älter), was bei den schmalen, an den kurzen Datenwert angepassten Spalten zu Textüberlappungen zwischen benachbarten Kopfzeilen und mit der Datenzeile führte (im Livesystem als Screenshot bestätigt). Vorbild: volleyball-bundesliga.de macht es identisch - dort bleiben exakt dieselben kurzen Spalten horizontal.
- Changelog: 1.1.0 - Spaltenüberschriften der Tabellendarstellung (kurz/mittel/vollständig) sind jetzt sprachabhängig über tf() statt hartkodiert Deutsch ("Sp"/"S"/"N"/"Pkt"/"B-Quot" etc.) - Bugfix bei der Übernahme: die englische Sprachversion hätte trotzdem die deutschen Kürzel gezeigt.
- Changelog: 1.0.0 - Neu (Beitrag: Torsten Hofmann): Volleyball-Profil mit satzabhängiger Punktevergabe (3:0/3:1 = 3 Punkte, 3:2 = 2/1 Punkte) und Ballpunkte-Statistik. Unverändert übernommen.

## src/Sport/BasketballProfile.php

- Changelog: 1.0.0 - Neu (Beitrag: Torsten Hofmann). Unverändert übernommen.

## src/Sport/HandballProfile.php

- Changelog: 1.0.0 - Neu (Beitrag: Torsten Hofmann). Unverändert übernommen.

## src/Sport/IceHockeyProfile.php

- Changelog: 1.0.0 - Neu (Beitrag: Torsten Hofmann). Unverändert übernommen.

## src/Sport/BadmintonProfile.php

- Changelog: 1.0.0 - Neu (Beitrag: Torsten Hofmann). Unverändert übernommen.

## assets/logo.svg

- Changelog: (neu) - Hauptlogo erneut ausgetauscht (echtes Vektor-SVG, 126 KB, 1000×226px Ansicht). Hinweis: Diese Version hat weiterhin KEINEN echt transparenten Hintergrund - nur die vier abgerundeten Ecken sind transparent, die Fläche dahinter (dunkles Blau) bleibt voll deckend, gleiches Verhalten wie die vorherige Version. Auf hellem Grund (z.B. Login-Seite mit hellem Template) ist der dunkle Rahmen entsprechend sichtbar - auf ausdrücklichen Wunsch dennoch übernommen, nach vorheriger Prüfung/Rückmeldung dazu.
- Changelog: (neu) -  - Hauptlogo durch neues Design ersetzt. Originaldatei (1,93 MB, eingebettetes Rasterbild) auf eine optimierte, schlanke Version (172 KB, Anzeigegröße 723×160px) reduziert und im echten Browser (Playwright) bestätigt korrekt dargestellt. Alte Version gesichert.

## assets/pdf/logo_rgb.zz + logo_alpha.zz

- Changelog: (neu) -  - Rohpixel-Daten für die PDF-Kopfzeile passend zum neuen Logo neu erzeugt (353×78px statt bisher 320×78px), mit derselben Extraktionslogik wie PdfExporter::pdfGdImageToRaw(). Live im echten PDF-Export bestätigt korrekt dargestellt.

## assets/favicon/*

- Changelog: (neu) - Komplettes Favicon-Set (24 Icon-Dateien + favicon.ico) aus der neuen Vorlage mit echtem transparentem Hintergrund neu generiert (vorherige Version hatte einen fest eingebrannten dunklen Hintergrund statt echter Transparenz - siehe assets/logo.svg für den Hintergrund dazu). Quelle 803×814px auf ein transparentes Quadrat (814×814) zentriert gepolstert, dann verlustfrei auf alle 24 Zielgrößen herunterskaliert (Android 36–192px, Apple-Touch 57–180px, MS-Tile 70–310px, Standard-Favicons 16/32/96/256px) plus favicon.ico (16/32/48px Multi-Auflösung). manifest.json und browserconfig.xml unverändert, da nur die referenzierten Dateinamen betroffen sind, nicht deren Struktur.
- Changelog: (neu) -  - Komplettes Favicon-Set (26 Dateien: Android-, Apple-Touch-, MS-Tile-Icons in allen Größen sowie favicon.ico) aus dem neuen "LN"-Icon-Design neu generiert.

## assets/img/tippspiel-icon.png

- Changelog: (neu) - Gegen neue Vorlage mit echtem transparentem Hintergrund ausgetauscht (39×38px, natives Seitenverhältnis der neuen Vorlage beibehalten statt verzerrt - die CSS-Regel .tipp-site-link-icon setzt ohnehin nur height:20px + width:auto, siehe template/*/layout.tpl.php).
- Changelog: (neu) -  - Neues, optimiertes Icon (40×38px) für den Tippspiel-Link im Header/Footer, aus der app_tippspiel-Vorlage erstellt.

## assets/img/tippspiel-logo.png

- Changelog: (neu) - Gegen neue Vorlage mit echtem transparentem Hintergrund ausgetauscht (477×130px, natives Seitenverhältnis der neuen Vorlage beibehalten statt verzerrt - die CSS-Regel .tipp-section-heading-logo setzt ohnehin nur height:44px + width:auto).
- Changelog: (neu) -  - Neues, optimiertes Logo (452×130px) für die Tippspiel-Bereichsüberschrift, aus der tippspiel_logo-Vorlage erstellt.

## addon/ewige/lmo-ewigetab.php

- Changelog: 1.0.2 - Wie viewer 3.0.1: Frame-Schutz-Header wieder entfernt für die beabsichtigte iframe-Einbettung.
- Changelog: (verschoben) - Von addon/mini/ nach addon/ewige/ verschoben (Beitrag: Torsten Hofmann: "ewige Tabelle ist jetzt ein eigenständiges Addon, mini ist mehr für kleine Ansichten gedacht"), alle Pfad-Referenzen angepasst, Copyright-Hinweis ergänzt. Live getestet (Standard- und Verlaufs-Ansicht).

## template/addon/ewige/matrix.tpl.php

- Changelog: 1.1.0 - Copyright-Platzhalter ergänzt (Beitrag: Torsten Hofmann).

## addon/relegation/lmo-relegation.php

- Changelog: 1.0.1 - Wie viewer 3.0.1: Frame-Schutz-Header wieder entfernt für die beabsichtigte iframe-Einbettung.
- Changelog: (neu) - Neues eigenständiges Addon (Beitrag: Torsten Hofmann): Relegations-Rechner, zeigt die Mini-Liga zwischen Absteiger der Oberliga und Aufsteiger der Unterliga, ermittelt den relevanten Tabellenrang automatisch aus den Liga-Einstellungen "AB"/"CL". Live getestet mit zwei echten Ligen.

## addon/tabellenrechner/lmo-tabellenrechner.php

- Changelog: 1.0.7 - Wie viewer 3.0.1: Frame-Schutz-Header wieder entfernt für die beabsichtigte iframe-Einbettung.
- Changelog: (neu) - Neues eigenständiges Addon (Beitrag: Torsten Hofmann): Tabellenrechner für Was-wäre-wenn-Berechnungen zu einem bestimmten Spieltag. Live getestet.

## addon/viewer/lmo-viewer.php

- Changelog: 3.0.1 - Entfernt die von frontend/bootstrap.php gesetzten Frame-Schutz-Header wieder (header_remove()), da dieses Addon bewusst zum Einbetten via iframe auf fremden Websites gedacht ist. Live bestätigt: keine X-Frame-Options/CSP mehr in der Antwort, nosniff bleibt bestehen.
- Changelog: (neu) - Neues eigenständiges Addon (Beitrag: Torsten Hofmann): Spielplan-Betrachter mit zwei Varianten (Zeitraum-Ansicht und Wochenkacheln-Ansicht). Beide Varianten live getestet.
