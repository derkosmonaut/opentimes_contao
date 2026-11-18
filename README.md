# opentimes_contao

Eine kleine Öffnungszeiten-Erweiterung für Contao (Bundle für Contao 5).

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

Gerüst und Backend-Datenmodell (DCA) sind angelegt. Als Nächstes folgen der
Resolver-Service mit Unit-Tests und danach die Frontend-Ausgabe.

## Lizenz

GPL-3.0-or-later, siehe `LICENSE`.
