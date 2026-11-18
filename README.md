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

Gerüst, Backend-Datenmodell (DCA) und der Resolver (`src/Resolver/`, reines PHP
mit Unit-Tests) sind angelegt. Als Nächstes folgen die Anbindung der
Datenbank an den Resolver und die Frontend-Ausgabe.

## Tests

```
composer install
vendor/bin/phpunit
```

Die Tests laufen bei jedem Push auch in GitHub Actions.

## Lizenz

GPL-3.0-or-later, siehe `LICENSE`.
