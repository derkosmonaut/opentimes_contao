<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Output;

use DateTimeImmutable;
use Derkosmonaut\OpenTimesBundle\Holiday\GermanHolidays;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ExceptionDay;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Season;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;

/**
 * Baut die Zeitübersicht: Standardzeiten, Saisonzeiten und die Ausnahmen und
 * Feiertage der nächsten Monate auf einen Blick.
 *
 * @phpstan-type Window array{opens: string, closes: string, overnight: bool}
 * @phpstan-type Group array{days: string, closed: bool, windows: list<Window>}
 */
final class OverviewBuilder
{
    /** Ein Montag als Bezugstag, um Slots je Wochentag in Zeitfenster zu übersetzen. */
    private const REFERENCE_MONDAY = '2001-01-01';

    public function __construct(private readonly OpeningHoursResolver $resolver)
    {
    }

    /**
     * @param array<int, string> $weekdayLabels Kurzbezeichnungen je ISO-Wochentag (1 = Montag ... 7 = Sonntag)
     * @param int                $months        Zeitraum für Ausnahmen und Feiertage ab heute (1 bis 24)
     *
     * @return array{
     *     standard: list<Group>|null,
     *     standardCurrent: bool,
     *     seasons: list<array{title: string, start: DateTimeImmutable, stop: DateTimeImmutable, recurring: bool, current: bool, groups: list<Group>}>,
     *     upcoming: list<array{kind: string, title: string, start: DateTimeImmutable, stop: DateTimeImmutable|null, closed: bool, windows: list<Window>}>,
     *     until: DateTimeImmutable
     * }
     */
    public function build(OpeningHours $hours, DateTimeImmutable $now, array $weekdayLabels, int $months = 6): array
    {
        $today = $now->setTime(0, 0);
        $until = $today->modify('+'.max(1, min(24, $months)).' month');
        $active = $this->resolver->getActiveSeason($hours, $today);

        return [
            'standard' => null === $hours->defaultSlots ? null : $this->groups($hours->defaultSlots, $weekdayLabels),
            'standardCurrent' => null === $active,
            'seasons' => $this->seasons($hours, $today, $active, $weekdayLabels),
            'upcoming' => $this->upcoming($hours, $today, $until),
            'until' => $until->modify('-1 day'),
        ];
    }

    /**
     * Wochentage mit gleichen Zeiten werden zusammengefasst, z. B. "Mo–Fr".
     *
     * @param list<Slot>         $slots
     * @param array<int, string> $weekdayLabels
     *
     * @return list<Group>
     */
    private function groups(array $slots, array $weekdayLabels): array
    {
        $byKey = [];
        $reference = new DateTimeImmutable(self::REFERENCE_MONDAY);

        for ($weekday = 1; $weekday <= 7; ++$weekday) {
            $windows = array_map(
                ViewDataBuilder::formatWindow(...),
                $this->resolver->getWindowsForSlots($slots, $reference->modify('+'.($weekday - 1).' day')),
            );
            $key = json_encode($windows, JSON_THROW_ON_ERROR);
            $byKey[$key]['windows'] = $windows;
            $byKey[$key]['weekdays'][] = $weekday;
        }

        $groups = [];

        foreach ($byKey as $entry) {
            $groups[] = [
                'first' => $entry['weekdays'][0],
                'days' => $this->formatWeekdays($entry['weekdays'], $weekdayLabels),
                'closed' => [] === $entry['windows'],
                'windows' => $entry['windows'],
            ];
        }

        // geöffnete Tage nach dem ersten Wochentag, geschlossene Tage zuletzt
        usort($groups, static fn (array $a, array $b): int => [$a['closed'], $a['first']] <=> [$b['closed'], $b['first']]);

        return array_map(static fn (array $g): array => ['days' => $g['days'], 'closed' => $g['closed'], 'windows' => $g['windows']], $groups);
    }

    /**
     * @param list<int>          $weekdays aufsteigend
     * @param array<int, string> $labels
     */
    private function formatWeekdays(array $weekdays, array $labels): string
    {
        $runs = [];
        $run = [];

        foreach ($weekdays as $weekday) {
            if ([] !== $run && $weekday !== end($run) + 1) {
                $runs[] = $run;
                $run = [];
            }

            $run[] = $weekday;
        }

        $runs[] = $run;
        $parts = [];

        foreach ($runs as $run) {
            $first = $labels[$run[0]] ?? (string) $run[0];
            $last = $labels[end($run)] ?? (string) end($run);

            $parts[] = match (true) {
                1 === \count($run) => $first,
                2 === \count($run) => $first.', '.$last,
                default => $first.'–'.$last,
            };
        }

        return implode(', ', $parts);
    }

    /**
     * Saisons, die noch laufen oder kommen: die aktuelle zuerst, dann nach dem nächsten Beginn.
     *
     * @param array<int, string> $weekdayLabels
     *
     * @return list<array{title: string, start: DateTimeImmutable, stop: DateTimeImmutable, recurring: bool, current: bool, groups: list<Group>}>
     */
    private function seasons(OpeningHours $hours, DateTimeImmutable $today, ?Season $active, array $weekdayLabels): array
    {
        $rows = [];

        foreach ($hours->seasons as $season) {
            $start = new DateTimeImmutable($season->start, $today->getTimezone());
            $stop = new DateTimeImmutable($season->stop, $today->getTimezone());

            // einmalige Saisons, die vorbei sind, interessieren nicht mehr
            if (!$season->recurring && $stop < $today) {
                continue;
            }

            $current = $season === $active;

            $rows[] = [
                'sort' => $current ? '' : $this->nextStart($season, $today),
                'row' => [
                    'title' => $season->title,
                    'start' => $start,
                    'stop' => $stop,
                    'recurring' => $season->recurring,
                    'current' => $current,
                    'groups' => $this->groups($season->slots, $weekdayLabels),
                ],
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(static fn (array $r): array => $r['row'], $rows);
    }

    private function nextStart(Season $season, DateTimeImmutable $today): string
    {
        if (!$season->recurring) {
            return $season->start;
        }

        $date = $today->format('Y').substr($season->start, 4);

        return $date < $today->format('Y-m-d') ? ((int) $today->format('Y') + 1).substr($season->start, 4) : $date;
    }

    /**
     * Ausnahmen und Feiertage von heute bis zum Ende des Zeitraums, nach Datum sortiert.
     *
     * @return list<array{kind: string, title: string, start: DateTimeImmutable, stop: DateTimeImmutable|null, closed: bool, windows: list<Window>}>
     */
    private function upcoming(OpeningHours $hours, DateTimeImmutable $today, DateTimeImmutable $until): array
    {
        $items = [];
        $covered = [];

        foreach ($hours->exceptions as $exception) {
            foreach ($this->occurrences($exception, $today, $until) as [$start, $stop]) {
                $covered[] = [$start, $stop ?? $start];
                $items[] = [
                    'kind' => 'exception',
                    'title' => $exception->title,
                    'start' => $start,
                    'stop' => $stop,
                    'closed' => $exception->closed,
                    'windows' => $exception->closed ? [] : $this->windowsOf($exception->slots, $start),
                ];
            }
        }

        if ('' !== $hours->holidayRegion) {
            $lastYear = (int) $until->format('Y');

            for ($year = (int) $today->format('Y'); $year <= $lastYear; ++$year) {
                foreach (GermanHolidays::forYear($year, $hours->holidayRegion) as $ymd => $name) {
                    $date = new DateTimeImmutable($ymd, $today->getTimezone());

                    if ($date < $today || $date >= $until || $this->isCovered($date, $covered)) {
                        continue;
                    }

                    $items[] = ['kind' => 'holiday', 'title' => $name, 'start' => $date, 'stop' => null, 'closed' => true, 'windows' => []];
                }
            }
        }

        usort($items, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        return $items;
    }

    /**
     * @param list<array{0: DateTimeImmutable, 1: DateTimeImmutable}> $covered
     */
    private function isCovered(DateTimeImmutable $date, array $covered): bool
    {
        foreach ($covered as [$start, $stop]) {
            if ($date >= $start && $date <= $stop) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alle Vorkommen der Ausnahme, die den Zeitraum berühren. Wiederkehrende zählen in jedem Jahr.
     *
     * @return list<array{0: DateTimeImmutable, 1: DateTimeImmutable|null}>
     */
    private function occurrences(ExceptionDay $exception, DateTimeImmutable $today, DateTimeImmutable $until): array
    {
        $timezone = $today->getTimezone();
        $start = new DateTimeImmutable($exception->start, $timezone);
        $stop = null === $exception->stop ? null : new DateTimeImmutable($exception->stop, $timezone);
        $result = [];

        $years = $exception->recurring
            ? range((int) $today->format('Y') - 1, (int) $until->format('Y'))
            : [null];

        foreach ($years as $year) {
            $from = $start;
            $to = $stop;

            if (null !== $year) {
                $from = $start->setDate($year, (int) $start->format('n'), (int) $start->format('j'));

                if (null !== $stop) {
                    // endet der Zeitraum im Kalender vor dem Beginn, geht er ins nächste Jahr (z. B. 20.12. bis 6.1.)
                    $stopYear = $stop->format('md') < $start->format('md') ? $year + 1 : $year;
                    $to = $stop->setDate($stopYear, (int) $stop->format('n'), (int) $stop->format('j'));
                }
            }

            $end = $to ?? $from;

            if ($end >= $today && $from < $until) {
                $result[] = [$from, null !== $to && $to->getTimestamp() !== $from->getTimestamp() ? $to : null];
            }
        }

        return $result;
    }

    /**
     * @param list<Slot> $slots
     *
     * @return list<Window>
     */
    private function windowsOf(array $slots, DateTimeImmutable $date): array
    {
        return array_map(ViewDataBuilder::formatWindow(...), $this->resolver->getWindowsForSlots($slots, $date));
    }
}
