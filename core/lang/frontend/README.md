# Sprachdateien für den Besucherbereich

Übersetzungen für den öffentlichen Besucherbereich (Startseite `/home.php`,
Liga-Detailseite `/liga.php` mit Ergebnissen/Kalender/Spielplänen/Info).

## Struktur

Genau wie im Adminbereich (`lang/admin/`): eine Datei pro Sprache, gleicher
Aufbau, gleiche Engine (`lang/i18n.php`), aber komplett getrennter Wortschatz
und getrennte Spracheinstellung:

```
lang/frontend/de.php
lang/frontend/en.php
```

Jede Datei liefert `return [...]` mit `'schluessel' => 'Text',` – Deutsch
(`de.php`) ist die Referenzsprache; fehlende Schlüssel in anderen Sprachen
fallen automatisch auf Deutsch zurück.

## Verwendung im Code

```php
tf('home_heading_active_ligen')
tf('liga_no_results_yet')
```

`tf()` ist das Besucherbereich-Pendant zu `t()` und funktioniert identisch
(inkl. `{platzhalter}`-Ersetzung), lädt aber aus diesem Ordner statt aus
`lang/admin/`.

## Warum getrennt vom Adminbereich?

- Ein Admin kann die Verwaltung z.B. auf Englisch nutzen, während ein
  Website-Besucher unabhängig davon Deutsch (oder eine andere Sprache) sieht –
  beide Bereiche haben einen eigenen Session-Schlüssel
  (`lmonext_lang_admin` bzw. `lmonext_lang_frontend`).
- Unterschiedlicher Wortschatz: der Besucherbereich braucht andere Begriffe
  (z.B. "Tordifferenz", "Heimbilanz", "Spielplan") als der Adminbereich
  (z.B. "Speichern", "Duplikate zusammenführen"). Getrennte Dateien vermeiden
  Namenskollisionen und halten beide Wortschätze überschaubar.
- Beide Bereiche teilen sich aber dieselbe Sprachliste (`AVAILABLE_LANGUAGES`
  in `lang/i18n.php`) und dieselbe Erkennungs-/Umschalt-Logik – kein
  doppelter Code.
