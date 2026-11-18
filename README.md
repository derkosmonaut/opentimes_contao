# opentimes_contao

*** noch nicht für den Liveeinsatz gedacht – noch in Entwicklung ***

Eine kleine Öffnungszeiten-Erweiterung für Contao (Bundle für Contao 5.7 oder neuer, PHP 8.5).

## Funktionsumfang (Ziel)

- Mehrere Orte
- Standardöffnungszeiten (Wochenraster, mehrere Zeitfenster pro Tag)
- Saisonzeiten (Zeitraum, jährlich wiederholbar, Priorität)
- Ausnahmen (z. B. Feiertage: geschlossen oder abweichende Zeiten)
- Spontane Schließung mit Hinweistext
- Nachtbetrieb (Fenster über Mitternacht)

## Auflösungsreihenfolge

Die speziellere Ebene schlägt die allgemeinere:

1. Spontane Schließung
2. Ausnahme
3. Saison
4. Standardzeiten

## Stand

Gerüst, Backend-Datenmodell (DCA), Resolver (`src/Resolver/`, reines PHP mit
Unit-Tests) und die Frontend-Ausgabe sind angelegt.

## Ausgabe

Im Backend unter „Inhalte → Öffnungszeiten“ Orte, Zeitpläne, Ausnahmen und
Schließungen pflegen. Auf der Seite das Inhaltselement „Öffnungszeiten“
einfügen und einen Ort wählen. Es gibt aus:

- den aktuellen Status („Jetzt geöffnet bis 17:00 Uhr“, Hinweistext bei Schließung)
- optional eine Wochenübersicht der nächsten sieben Tage
- optional strukturierte Daten nach schema.org (JSON-LD, `openingHoursSpecification`):
  reguläre Wochenzeiten plus abweichende Tage der nächsten 30 Tage
  (Ausnahmen, Saisonwechsel, Schließungen)

Die Templates liegen in `contao/templates/` und lassen sich im Theme überschreiben:
`content_element/opentimes_hours.html.twig` sowie die Bausteine
`opentimes/_status`, `opentimes/_week` und `opentimes/_json_ld`.
Die Ausgabe wird 60 Sekunden gecacht, damit spontane Schließungen zeitnah sichtbar sind.

Die Wochenübersicht ist eine Beschreibungsliste (`dl`) mit ausgeschriebenem „von … bis …“,
damit sie für Screenreader gut lesbar ist. Der Status trägt die Klasse
`opentimes__status--open`, `--closed` oder `--unknown`. Ein kleines Stylesheet
(`public/css/opentimes.css`) färbt geöffnet grün und geschlossen rot; die Farben lassen sich im
Theme über die CSS-Variablen `--opentimes-open`, `--opentimes-closed` und `--opentimes-unknown` anpassen.

## Tests

```
composer install
vendor/bin/phpunit
```

Die Tests laufen bei jedem Push auch in GitHub Actions.

## Lizenz

GPL-3.0-or-later, siehe `LICENSE`.
