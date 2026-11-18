# Changelog

## Unveröffentlicht

### Behoben
- Inhaltselement: Die Darstellung „Ortsliste“ ließ sich nicht wählen, weil die Maske nicht auf die Orte-Auswahl umschaltete. Jetzt stehen „Ort“ und „Orte“ immer in der Maske (nur das Frontend-Modul schaltet um)

### Neu
- Feiertage je Bundesland (Auswahl beim Ort)
- Vorschau im Backend beim Ort, mit Hinweisen auf Unstimmigkeiten
- Schnellauswahl der Wochentage („Mo–Fr“, „Wochenende“, „Alle Tage“)
- Frontend-Modul „Öffnungszeiten“ neben dem Content-Element
- Layouts „Voll“, „Kompakt“ und „Ortsliste“; Anzahl der Tage der Wochenübersicht einstellbar
- Englische Sprachdateien
- Cache bis zur nächsten Statusänderung (60 s bis 5 min) und Cache-Tags
- Tests mit Datenbank (SQLite), PHPStan und Release-Workflow in GitHub Actions

### Geändert
- Die Ausgabe wird über den Dienst `HoursRenderer` befüllt, Content-Element und Modul teilen sich die Templates
- Datenbank: neue Spalten `holiday_region` (tl_oh_location), `opentimes_layout`, `opentimes_locations`,
  `opentimes_days` (tl_content, tl_module) – Datenbank im Contao-Install-Tool aktualisieren

## 0.1.2
- Enddatum vor dem Beginn wird verhindert und beim Auslesen toleriert

## 0.1.1
- Zweites Zeitfenster bei Standard- und Saisonzeiten
- Wochenübersicht als Beschreibungsliste, Statusfarben, Texte über den Controller
- Uhrzeiten als HH:MM, Altdaten werden umgerechnet; neues Icon für Ausnahmen

## 0.1.0
- Erste Version: Orte, Standard- und Saisonzeiten, Ausnahmen, Schließungen, schema.org-Daten
