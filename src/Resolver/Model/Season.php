<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver\Model;

/**
 * Saisonplan mit Gültigkeitszeitraum. Er ersetzt die Standardzeiten komplett:
 * Wochentage ohne Zeitfenster sind in der Saison geschlossen.
 */
final readonly class Season
{
    /**
     * @param string     $start     erster Tag als "Y-m-d" (zählt mit)
     * @param string     $stop      letzter Tag als "Y-m-d" (zählt mit)
     * @param bool       $recurring jährlich wiederholen (nur Monat und Tag zählen, auch über den Jahreswechsel)
     * @param int        $priority  bei Überlappung gewinnt die höhere Zahl, bei Gleichstand die erste Saison
     * @param list<Slot> $slots
     * @param string     $title     Bezeichnung für die Übersicht, z. B. "Sommer" (leer = keine)
     */
    public function __construct(
        public string $start,
        public string $stop,
        public bool $recurring,
        public int $priority,
        public array $slots,
        public string $title = '',
    ) {
    }
}
