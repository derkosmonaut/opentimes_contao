<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver\Model;

/**
 * Alle Daten eines Ortes, die der Resolver braucht. Unabhängig von Contao und Datenbank.
 */
final readonly class OpeningHours
{
    /**
     * @param list<Slot>|null     $defaultSlots Standardzeiten; null = kein Standardplan hinterlegt
     * @param list<Season>        $seasons
     * @param list<ExceptionDay>  $exceptions
     * @param list<ClosurePeriod> $closures
     * @param list<string>        $holidays     Feiertage als "Y-m-d": geschlossen, sofern keine Ausnahme für den Tag gilt
     * @param string              $holidayRegion Bundesland-Kürzel der Feiertage (leer = keine), für die Namen in der Übersicht
     */
    public function __construct(
        public ?array $defaultSlots,
        public array $seasons = [],
        public array $exceptions = [],
        public array $closures = [],
        public array $holidays = [],
        public string $holidayRegion = '',
    ) {
    }

    /**
     * Ohne jeden Plan kann kein Status berechnet werden ("unbekannt", nicht "geschlossen").
     */
    public function hasPlan(): bool
    {
        return null !== $this->defaultSlots || [] !== $this->seasons || [] !== $this->exceptions;
    }
}
