<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Output;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Repository\OpeningHoursRepository;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ClosurePeriod;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;

/**
 * Ermittelt die gerade laufenden Schließungen für das Alert-Modul.
 */
class AlertBuilder
{
    /** Kürzeste und längste Cache-Zeit in Sekunden. */
    private const MIN_AGE = 15;
    private const MAX_AGE = 300;

    public function __construct(
        private readonly OpeningHoursRepository $repository,
        private readonly OpeningHoursResolver $resolver,
    ) {
    }

    /**
     * @param list<int> $locationIds leer = alle Orte
     *
     * @return array{alerts: list<array{location: string, message: string, since: DateTimeImmutable, until: DateTimeImmutable|null}>, maxAge: int}
     */
    public function build(array $locationIds, DateTimeImmutable $now, DateTimeZone $timezone): array
    {
        if ([] === $locationIds) {
            $locationIds = $this->repository->findAllLocationIds();
        }

        $alerts = [];
        $maxAge = self::MAX_AGE;

        foreach ($locationIds as $id) {
            $location = $this->repository->findLocation($id);

            if (null === $location) {
                continue;
            }

            $loaded = $this->repository->loadHours($id, $timezone);

            // nur Schließungen, die als Alert ausgespielt werden sollen
            $hours = new OpeningHours(
                $loaded->defaultSlots,
                $loaded->seasons,
                $loaded->exceptions,
                array_values(array_filter($loaded->closures, static fn (ClosurePeriod $c): bool => $c->alert)),
                $loaded->holidays,
            );
            $closure = $this->resolver->getActiveClosure($hours, $now);

            if (null !== $closure) {
                $alerts[] = [
                    'location' => $location['title'],
                    'message' => trim($closure->message),
                    'since' => $closure->start,
                    'until' => $closure->stop,
                ];
            }

            // Cache nur bis zum nächsten Beginn oder Ende, damit sich der Alert pünktlich ein- und ausschaltet
            $change = $this->resolver->getNextClosureChange($hours, $now);

            if (null !== $change) {
                $maxAge = min($maxAge, $change->getTimestamp() - $now->getTimestamp());
            }
        }

        return ['alerts' => $alerts, 'maxAge' => max(self::MIN_AGE, $maxAge)];
    }
}
