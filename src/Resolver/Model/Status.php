<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver\Model;

use DateTimeImmutable;

final readonly class Status
{
    public const OPEN = 'open';
    public const CLOSED = 'closed';
    public const UNKNOWN = 'unknown';

    /**
     * @param string                 $state           OPEN, CLOSED oder UNKNOWN
     * @param DateTimeImmutable|null $until           bei OPEN: wann die Öffnung endet
     * @param DateTimeImmutable|null $nextOpen        bei CLOSED: nächste Öffnung (null = keine absehbar)
     * @param string|null            $message         Hinweistext einer aktiven Schließung
     * @param bool                   $closedByClosure true, wenn eine Schließung der Grund ist
     */
    public function __construct(
        public string $state,
        public ?DateTimeImmutable $until = null,
        public ?DateTimeImmutable $nextOpen = null,
        public ?string $message = null,
        public bool $closedByClosure = false,
    ) {
    }

    public function isOpen(): bool
    {
        return self::OPEN === $this->state;
    }
}
