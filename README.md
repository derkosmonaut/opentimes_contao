<p align="center"><img src="docs/logo.svg" alt="Logo" width="96"></p>

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
- Gesetzliche Feiertage je Bundesland (geschlossen, eine Ausnahme hat Vorrang)
- Zwei Zeitfenster pro Eintrag, Vorschau und Hinweise im Backend
- Content-Element und Frontend-Modul, Layouts „Voll“, „Kompakt“ und „Ortsliste“
- Deutsche und englische Texte

## Auflösungsreihenfolge

Die speziellere Ebene schlägt die allgemeinere:

1. Spontane Schließung
2. Ausnahme
3. Feiertag (geschlossen, aber schwächer als eine Ausnahme)
4. Saison
5. Standardzeiten

## Stand

Gerüst, Backend-Datenmodell (DCA), Resolver (`src/Resolver/`, reines PHP mit
Unit-Tests) und die Frontend-Ausgabe sind angelegt.

## Ausgabe

Im Backend unter „Inhalte → Öffnungszeiten“ Orte, Zeitpläne, Ausnahmen und
Schließungen pflegen. Beim Ort zeigt eine Vorschau Status und die nächsten sieben Tage und
weist auf Unstimmigkeiten hin (z. B. überlappende Saisons mit gleicher Priorität). Bei den
Wochentagen eines Zeitfensters gibt es eine Schnellauswahl („Mo–Fr“, „Wochenende“, „Alle Tage“).

Auf der Seite das Inhaltselement oder das Frontend-Modul „Öffnungszeiten“ einfügen. Die Darstellung:

- **Voll:** Status, optional Wochenübersicht (1 bis 14 Tage)
- **Kompakt:** Status und die Zeiten von heute, z. B. für Kopf- oder Fußbereich
- **Ortsliste:** mehrere Orte, je mit Status und den Zeiten von heute

Optional gibt es strukturierte Daten nach schema.org (JSON-LD, `openingHoursSpecification`):
reguläre Wochenzeiten plus abweichende Tage der nächsten 30 Tage
(Ausnahmen, Feiertage, Saisonwechsel, Schließungen).

Die Templates liegen in `contao/templates/` und lassen sich im Theme überschreiben:
`content_element/opentimes_hours.html.twig`, `frontend_module/opentimes_hours.html.twig` sowie die Bausteine
`opentimes/_output`, `_status`, `_week`, `_compact`, `_locations` und `_json_ld`.

Die Wochenübersicht ist eine Beschreibungsliste (`dl`) mit ausgeschriebenem „von … bis …“,
damit sie für Screenreader gut lesbar ist. Der Status trägt die Klasse
`opentimes__status--open`, `--closed` oder `--unknown`. Ein kleines Stylesheet
(`public/css/opentimes.css`) färbt geöffnet grün und geschlossen rot; die Farben lassen sich im
Theme über die CSS-Variablen `--opentimes-open`, `--opentimes-closed` und `--opentimes-unknown` anpassen.

### Schließungs-Hinweis (Alert)

Das Frontend-Modul „Schließungs-Hinweis“ lässt sich im Seiten-Layout einbinden. Solange keine
Schließung läuft, gibt es nichts aus. Beginnt eine aktive Schließung mit dem Haken „Als Alert ausspielen“ (Beginn erreicht, Ende noch
nicht), erscheint ein Hinweis mit Ortsname, Text und, falls gesetzt, dem voraussichtlichen Ende;
nach dem Ende verschwindet er wieder. Schließungen ohne den Haken erscheinen nur in der
Öffnungszeiten-Ausgabe (Status „geschlossen“ mit Hinweistext), nicht als Alert. Ohne Orte-Auswahl gelten alle Orte. Der Hinweis lässt sich über
`.opentimes-alert` gestalten und über das Template `frontend_module/opentimes_alert` überschreiben.

Die Antwort wird bis zum nächsten Beginn oder Ende einer Schließung gecacht (15 Sekunden bis 5 Minuten).
Hinweis: Ein Modul im Seiten-Layout verkürzt damit auch die Cache-Zeit der ganzen Seite.

### Cache

Die Antwort wird bis zur nächsten Statusänderung gecacht, mindestens 60 Sekunden und höchstens
5 Minuten. Zusätzlich trägt sie Cache-Tags der Tabellen, damit Änderungen im Backend die Seite
bei einem Tag-fähigen Cache gleich ungültig machen.

### Feiertage

Beim Ort lässt sich ein Bundesland wählen. Dessen gesetzliche Feiertage gelten als geschlossen,
sofern keine eigene Ausnahme für denselben Tag besteht. Regional unterschiedliche Feiertage
(z. B. Mariä Himmelfahrt in Bayern) sind nicht enthalten und lassen sich als Ausnahme anlegen.

## Tests

```
composer install
vendor/bin/phpunit
```

Die Tests laufen bei jedem Push auch in GitHub Actions, dazu eine statische Analyse (PHPStan).
Beim Veröffentlichen eines Releases laufen die Tests erneut; Packagist wird bei hinterlegten Zugangsdaten sofort benachrichtigt.

## Branding im Contao Manager

Logo und Beschreibung kommen aus `contao/package-metadata`; vorbereitete Dateien liegen in `docs/package-metadata/`.

## Lizenz

GPL-3.0-or-later, siehe `LICENSE`.
