<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver\Model;

/**
 * Ausnahme für ein Datum oder einen Datumsbereich. Sie ersetzt die Zeiten des
 * ganzen Tages: entweder geschlossen oder abweichende Zeiten.
 */
final readonly class ExceptionDay
{
    /**
     * @param string      $start     erster Tag als "Y-m-d"
     * @param string|null $stop      letzter Tag als "Y-m-d"; null = nur ein Tag
     * @param bool        $recurring jährlich wiederholen
     * @param bool        $closed    true = ganztägig geschlossen (slots werden ignoriert)
     * @param list<Slot>  $slots     abweichende Zeiten, gelten unabhängig vom Wochentag (Slot::allDays)
     * @param string      $title     Bezeichnung für die Übersicht, z. B. "Betriebsferien" (leer = keine)
     */
    public function __construct(
        public string $start,
        public ?string $stop,
        public bool $recurring,
        public bool $closed,
        public array $slots = [],
        public string $title = '',
    ) {
    }
}
