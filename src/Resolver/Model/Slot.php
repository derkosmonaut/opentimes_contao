<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver\Model;

/**
 * Ein Zeitfenster für bestimmte Wochentage.
 *
 * Zeiten als "HH:MM" (Ende auch "24:00"). Liegt das Ende vor oder gleich dem
 * Beginn, endet das Fenster am Folgetag (Nachtbetrieb) und gehört zum Starttag.
 */
final readonly class Slot
{
    /**
     * @param list<int> $weekdays ISO-Wochentage, 1 = Montag ... 7 = Sonntag
     */
    public function __construct(
        public array $weekdays,
        public string $opens,
        public string $closes,
    ) {
    }

    public static function allDays(string $opens, string $closes): self
    {
        return new self([1, 2, 3, 4, 5, 6, 7], $opens, $closes);
    }
}
