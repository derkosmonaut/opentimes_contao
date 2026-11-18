<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver\Model;

use DateTimeImmutable;

/**
 * Konkretes Zeitintervall: Beginn inklusiv, Ende exklusiv.
 */
final readonly class Interval
{
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
    }

    public function contains(DateTimeImmutable $time): bool
    {
        return $time >= $this->start && $time < $this->end;
    }
}
