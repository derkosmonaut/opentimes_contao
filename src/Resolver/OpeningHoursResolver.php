<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Resolver;

use Derkosmonaut\OpenTimesBundle\Resolver\Model\ClosurePeriod;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Interval;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Season;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Status;
use DateTimeImmutable;

/**
 * Berechnet aus den Plänen eines Ortes, was zu einem Zeitpunkt gilt.
 *
 * Reihenfolge, die speziellere Ebene gewinnt:
 *   1. Schließung (schneidet beliebige Zeiten heraus)
 *   2. Ausnahme   (ersetzt den ganzen Tag)
 *   2a. Feiertag  (geschlossen, aber schwächer als eine Ausnahme)
 *   3. Saison     (ersetzt die Standardzeiten, höhere Priorität gewinnt)
 *   4. Standardzeiten
 *
 * Ein Zeitfenster gehört zu seinem Starttag, auch wenn es über Mitternacht läuft.
 * Eine Ausnahme gilt daher nur für Fenster, die an ihrem Tag beginnen.
 * Alle Zeiten sind lokale Zeiten der Zeitzone des übergebenen DateTimeImmutable.
 */
final class OpeningHoursResolver
{
    /** Wie viele Tage voraus nach der nächsten Öffnung gesucht wird. */
    private const HORIZON_DAYS = 60;

    public function getStatus(OpeningHours $hours, DateTimeImmutable $now): Status
    {
        $closure = $this->findActiveClosure($hours, $now);

        if (!$hours->hasPlan()) {
            return null !== $closure
                ? new Status(Status::CLOSED, null, null, $closure->message, true)
                : new Status(Status::UNKNOWN);
        }

        // ab dem Vortag, damit Fenster über Mitternacht erfasst werden
        $from = $now->setTime(0, 0)->modify('-1 day');
        $intervals = $this->getIntervals($hours, $from, self::HORIZON_DAYS + 2);

        foreach ($intervals as $interval) {
            if ($interval->contains($now)) {
                return new Status(Status::OPEN, $interval->end);
            }
        }

        $nextOpen = null;

        foreach ($intervals as $interval) {
            if ($interval->start > $now) {
                $nextOpen = $interval->start;
                break;
            }
        }

        return new Status(Status::CLOSED, null, $nextOpen, $closure?->message, null !== $closure);
    }

    /**
     * Geplante Zeitfenster, die an diesem Tag beginnen (ohne Schließungen).
     * Grundlage für die Wochenübersicht, die reguläre Zeiten auch bei Schließung zeigt.
     *
     * @return list<Interval>
     */
    public function getDayWindows(OpeningHours $hours, DateTimeImmutable $date): array
    {
        $day = $date->setTime(0, 0);
        $ymd = $day->format('Y-m-d');

        $matching = array_values(array_filter(
            $hours->exceptions,
            fn ($e): bool => $this->inRange($ymd, $e->start, $e->stop, $e->recurring),
        ));

        if ([] !== $matching) {
            $intervals = [];

            foreach ($matching as $exception) {
                if ($exception->closed) {
                    return [];
                }

                array_push($intervals, ...$this->slotsToIntervals($exception->slots, $day));
            }

            return $this->merge($intervals);
        }

        // Feiertage schließen, solange keine eigene Ausnahme für den Tag etwas anderes festlegt
        if (\in_array($ymd, $hours->holidays, true)) {
            return [];
        }

        $season = $this->findSeason($hours, $ymd);

        if (null !== $season) {
            return $this->merge($this->slotsToIntervals($season->slots, $day));
        }

        if (null !== $hours->defaultSlots) {
            return $this->merge($this->slotsToIntervals($hours->defaultSlots, $day));
        }

        return [];
    }

    /**
     * Zusammengeführte Öffnungsintervalle ab $from, Schließungen bereits herausgeschnitten.
     *
     * @return list<Interval>
     */
    public function getIntervals(OpeningHours $hours, DateTimeImmutable $from, int $days): array
    {
        $all = [];
        $start = $from->setTime(0, 0);

        for ($i = 0; $i < $days; ++$i) {
            array_push($all, ...$this->getDayWindows($hours, $start->modify('+'.$i.' day')));
        }

        $active = array_values(array_filter($hours->closures, static fn (ClosurePeriod $c): bool => $c->active));

        return $this->merge($this->clip($this->merge($all), $active));
    }

    /**
     * Zeitfenster eines Tages nach Abzug aktiver Schließungen. Reine Tagessicht,
     * Fenster werden nicht über Tagesgrenzen hinweg zusammengeführt.
     *
     * @return list<Interval>
     */
    public function getDayIntervals(OpeningHours $hours, DateTimeImmutable $date): array
    {
        $active = array_values(array_filter($hours->closures, static fn (ClosurePeriod $c): bool => $c->active));

        return $this->merge($this->clip($this->getDayWindows($hours, $date), $active));
    }

    /**
     * Die regulären Wochenzeiten, die an diesem Datum gelten (Saison oder Standard),
     * ohne Ausnahmen und Schließungen. null = kein Standardplan hinterlegt.
     *
     * @return list<Slot>|null
     */
    public function getRegularSlots(OpeningHours $hours, DateTimeImmutable $date): ?array
    {
        $season = $this->findSeason($hours, $date->format('Y-m-d'));

        return $season?->slots ?? $hours->defaultSlots;
    }

    /**
     * Konkrete Zeitfenster einer Slot-Liste für einen Tag.
     *
     * @param list<Slot> $slots
     *
     * @return list<Interval>
     */
    public function getWindowsForSlots(array $slots, DateTimeImmutable $date): array
    {
        return $this->merge($this->slotsToIntervals($slots, $date->setTime(0, 0)));
    }

    /**
     * Alle Saisons, die an diesem Tag ("Y-m-d") gelten, unabhängig von der Priorität.
     *
     * @return list<Season>
     */
    public function getSeasonsOn(OpeningHours $hours, string $ymd): array
    {
        return array_values(array_filter(
            $hours->seasons,
            fn (Season $s): bool => $this->inRange($ymd, $s->start, $s->stop, $s->recurring),
        ));
    }

    private function findSeason(OpeningHours $hours, string $ymd): ?Season
    {
        $season = null;

        foreach ($hours->seasons as $candidate) {
            if (!$this->inRange($ymd, $candidate->start, $candidate->stop, $candidate->recurring)) {
                continue;
            }

            if (null === $season || $candidate->priority > $season->priority) {
                $season = $candidate;
            }
        }

        return $season;
    }

    private function findActiveClosure(OpeningHours $hours, DateTimeImmutable $now): ?ClosurePeriod
    {
        foreach ($hours->closures as $closure) {
            if ($closure->active && $closure->start <= $now && (null === $closure->stop || $now < $closure->stop)) {
                return $closure;
            }
        }

        return null;
    }

    /**
     * Liegt $ymd im Zeitraum? Bei Wiederholung zählen nur Monat und Tag,
     * auch über den Jahreswechsel hinweg (z. B. 1.11. bis 31.3.).
     */
    private function inRange(string $ymd, string $start, ?string $stop, bool $recurring): bool
    {
        $stop ??= $start;

        if (!$recurring) {
            return $ymd >= $start && $ymd <= $stop;
        }

        $md = substr($ymd, 5);
        $from = substr($start, 5);
        $to = substr($stop, 5);

        return $from <= $to ? ($md >= $from && $md <= $to) : ($md >= $from || $md <= $to);
    }

    /**
     * @param list<Slot> $slots
     *
     * @return list<Interval>
     */
    private function slotsToIntervals(array $slots, DateTimeImmutable $day): array
    {
        $weekday = (int) $day->format('N');
        $intervals = [];

        foreach ($slots as $slot) {
            if (\in_array($weekday, $slot->weekdays, true)) {
                $intervals[] = $this->createInterval($day, $slot->opens, $slot->closes);
            }
        }

        return $intervals;
    }

    private function createInterval(DateTimeImmutable $day, string $opens, string $closes): Interval
    {
        [$openHour, $openMinute] = $this->parseTime($opens, false);
        [$closeHour, $closeMinute] = $this->parseTime($closes, true);

        $start = $day->setTime($openHour, $openMinute);

        if (24 === $closeHour) {
            return new Interval($start, $day->modify('+1 day'));
        }

        $end = $day->setTime($closeHour, $closeMinute);

        if ($end <= $start) {
            $end = $day->modify('+1 day')->setTime($closeHour, $closeMinute);
        }

        return new Interval($start, $end);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseTime(string $time, bool $allow24): array
    {
        if ($allow24 && '24:00' === $time) {
            return [24, 0];
        }

        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
            throw new \InvalidArgumentException(\sprintf('Ungültige Uhrzeit "%s", erwartet HH:MM.', $time));
        }

        return [(int) $m[1], (int) $m[2]];
    }

    /**
     * Sortiert und führt überlappende oder aneinandergrenzende Intervalle zusammen.
     *
     * @param list<Interval> $intervals
     *
     * @return list<Interval>
     */
    private function merge(array $intervals): array
    {
        if ([] === $intervals) {
            return [];
        }

        usort($intervals, static fn (Interval $a, Interval $b): int => $a->start <=> $b->start);

        $merged = [];
        $current = $intervals[0];

        foreach (\array_slice($intervals, 1) as $next) {
            if ($next->start <= $current->end) {
                if ($next->end > $current->end) {
                    $current = new Interval($current->start, $next->end);
                }
            } else {
                $merged[] = $current;
                $current = $next;
            }
        }

        $merged[] = $current;

        return $merged;
    }

    /**
     * Schneidet die Schließungen aus den Intervallen heraus.
     *
     * @param list<Interval>      $intervals
     * @param list<ClosurePeriod> $closures
     *
     * @return list<Interval>
     */
    private function clip(array $intervals, array $closures): array
    {
        foreach ($closures as $closure) {
            $closureStart = $closure->start;
            $closureEnd = $closure->stop ?? $closure->start->modify('+100 years');
            $result = [];

            foreach ($intervals as $interval) {
                if ($closureEnd <= $interval->start || $closureStart >= $interval->end) {
                    $result[] = $interval;

                    continue;
                }

                if ($interval->start < $closureStart) {
                    $result[] = new Interval($interval->start, $closureStart);
                }

                if ($closureEnd < $interval->end) {
                    $result[] = new Interval($closureEnd, $interval->end);
                }
            }

            $intervals = $result;
        }

        return $intervals;
    }
}
