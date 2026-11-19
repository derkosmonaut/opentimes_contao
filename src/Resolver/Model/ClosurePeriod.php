<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver\Model;

use DateTimeImmutable;

/**
 * Spontane (oder geplante) Schließung mit höchster Priorität.
 */
final readonly class ClosurePeriod
{
    /**
     * @param DateTimeImmutable      $start   Beginn (kann in der Zukunft liegen)
     * @param DateTimeImmutable|null $stop    Ende (exklusiv); null = bis auf Weiteres
     * @param bool                   $active  deaktivierte Schließungen werden ignoriert
     * @param string                 $message Hinweistext für die Ausgabe
     * @param bool                   $alert   soll das Alert-Modul die Schließung anzeigen?
     */
    public function __construct(
        public DateTimeImmutable $start,
        public ?DateTimeImmutable $stop,
        public bool $active = true,
        public string $message = '',
        public bool $alert = false,
    ) {
    }
}
