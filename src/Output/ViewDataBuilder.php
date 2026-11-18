<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Output;

use DateTimeImmutable;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ClosurePeriod;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Interval;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Status;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;

/**
 * Bereitet die Daten für die Twig-Templates auf: aktueller Status und Wochenübersicht.
 *
 * Die Wochenübersicht zeigt die geplanten Zeiten auch bei einer Schließung weiter,
 * markiert betroffene Tage aber über "closureNote".
 */
final class ViewDataBuilder
{
    public function __construct(private readonly OpeningHoursResolver $resolver)
    {
    }

    /**
     * @param array<int, string> $weekdayLabels Bezeichnungen je ISO-Wochentag (1 = Montag ... 7 = Sonntag)
     *
     * @return array{
     *     status: Status,
     *     hasPlan: bool,
     *     days: list<array{
     *         date: DateTimeImmutable,
     *         weekday: int,
     *         label: string,
     *         isToday: bool,
     *         closed: bool,
     *         closureNote: bool,
     *         windows: list<array{opens: string, closes: string, overnight: bool}>
     *     }>
     * }
     */
    public function build(OpeningHours $hours, DateTimeImmutable $now, array $weekdayLabels, int $days = 7): array
    {
        $day0 = $now->setTime(0, 0);
        $rows = [];

        for ($i = 0; $i < $days; ++$i) {
            $date = $day0->modify('+'.$i.' day');
            $weekday = (int) $date->format('N');

            $windows = array_map(
                fn (Interval $interval): array => $this->formatWindow($interval),
                $this->resolver->getDayWindows($hours, $date),
            );

            $rows[] = [
                'date' => $date,
                'weekday' => $weekday,
                'label' => $weekdayLabels[$weekday] ?? (string) $weekday,
                'isToday' => 0 === $i,
                'closed' => [] === $windows,
                'closureNote' => $this->touchesClosure($hours, $date, $date->modify('+1 day')),
                'windows' => $windows,
            ];
        }

        return [
            'status' => $this->resolver->getStatus($hours, $now),
            'hasPlan' => $hours->hasPlan(),
            'days' => $rows,
        ];
    }

    /**
     * @return array{opens: string, closes: string, overnight: bool}
     */
    private function formatWindow(Interval $interval): array
    {
        $closes = $interval->end->format('H:i');
        $nextDay = $interval->end->format('Y-m-d') !== $interval->start->format('Y-m-d');

        if ($nextDay && '00:00' === $closes) {
            return ['opens' => $interval->start->format('H:i'), 'closes' => '24:00', 'overnight' => false];
        }

        return ['opens' => $interval->start->format('H:i'), 'closes' => $closes, 'overnight' => $nextDay];
    }

    private function touchesClosure(OpeningHours $hours, DateTimeImmutable $dayStart, DateTimeImmutable $dayEnd): bool
    {
        foreach ($hours->closures as $closure) {
            /** @var ClosurePeriod $closure */
            if ($closure->active && $closure->start < $dayEnd && (null === $closure->stop || $closure->stop > $dayStart)) {
                return true;
            }
        }

        return false;
    }
}
