# Changelog

## Unveröffentlicht

### Geändert (Backend und Ausgabe)
- Backend: Je nach „Darstellung“ erscheinen nur die passenden Felder: „Kompakt“ nur die Orte, „Voll“ zusätzlich Anzahl Tage und Wochenübersicht, „Übersicht“ zusätzlich den Vorschau-Zeitraum
- Übersicht: Überschriften der Blöcke sind keine `h3` mehr, sondern `span`, damit die Überschriftenhierarchie der Seite nicht gestört wird

- Dokumentation: Anwendung in sechs Schritten mit Screenshots

### Geändert
- Ort(e): Es gibt nur noch die Checkbox-Auswahl „Orte“. Beide Layouts („Voll“, „Kompakt“) geben einen oder mehrere Orte aus; die frühere „Ortsliste“ entfällt (entspricht „Kompakt“ mit mehreren Orten)
- Bestehende Elemente mit einem Einzel-Ort werden beim Öffnen im Backend übernommen und funktionieren bis dahin unverändert

### Neu
- Layout „Übersicht“: fasst alle Zeiten auf einen Blick zusammen: Standard (Wochentage mit gleichen Zeiten gebündelt, z. B. „Mo–Fr“), Saisonzeiten (aktuelle Saison markiert) sowie Ausnahmen und Feiertage der nächsten Monate. Der Vorschau-Zeitraum (1 bis 24 Monate) ist einstellbar. Neue Spalte `opentimes_months` in `tl_content` und `tl_module` (Datenbank im Contao-Installtool aktualisieren). Saisons und Ausnahmen zeigen ihre Bezeichnung
- Feiertage je Bundesland (Auswahl beim Ort)
- Vorschau im Backend beim Ort, mit Hinweisen auf Unstimmigkeiten
- Schnellauswahl der Wochentage („Mo–Fr“, „Wochenende“, „Alle Tage“)
- Frontend-Modul „Öffnungszeiten“ neben dem Content-Element
- Layouts „Voll“, „Kompakt“ und „Ortsliste“; Anzahl der Tage der Wochenübersicht einstellbar
- Englische Sprachdateien
- Cache bis zur nächsten Statusänderung (60 s bis 5 min) und Cache-Tags
- Tests mit Datenbank (SQLite), PHPStan und Release-Workflow in GitHub Actions

### Neu
- Frontend-Modul „Schließungs-Hinweis“ fürs Seiten-Layout: zeigt einen Hinweis, solange eine Schließung läuft (mit Text und voraussichtlichem Ende), sonst bleibt es leer. Nur Schließungen mit dem Haken „Als Alert ausspielen“ erscheinen dort (neue Spalte `alert` in `tl_oh_closure`). Schaltet sich zu Beginn und Ende einer Schließung automatisch ein und aus

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
