<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Output;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Output\OverviewBuilder;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ExceptionDay;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Season;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;
use PHPUnit\Framework\TestCase;

final class OverviewBuilderTest extends TestCase
{
    private const LABELS = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

    private OverviewBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new OverviewBuilder(new OpeningHoursResolver());
    }

    public function testStandardGroupsWeekdaysWithSameTimes(): void
    {
        $hours = new OpeningHours([new Slot([1, 2, 3, 4, 5], '09:00', '17:00'), new Slot([6], '10:00', '14:00')]);

        $view = $this->builder->build($hours, $this->at('2026-11-18'), self::LABELS);

        self::assertNotNull($view['standard']);
        self::assertSame(['Mo–Fr', 'Sa', 'So'], array_column($view['standard'], 'days'));
        self::assertSame([false, false, true], array_column($view['standard'], 'closed'));
        self::assertSame('09:00', $view['standard'][0]['windows'][0]['opens']);
        self::assertTrue($view['standardCurrent']);
    }

    public function testSeparateWeekdaysAndPairs(): void
    {
        $hours = new OpeningHours([new Slot([1, 3, 4], '09:00', '12:00')]);

        $view = $this->builder->build($hours, $this->at('2026-11-18'), self::LABELS);

        self::assertNotNull($view['standard']);
        self::assertSame('Mo, Mi, Do', $view['standard'][0]['days']);
        self::assertSame('Di, Fr–So', $view['standard'][1]['days']);
    }

    public function testNoStandardPlan(): void
    {
        $view = $this->builder->build(new OpeningHours(null), $this->at('2026-11-18'), self::LABELS);

        self::assertNull($view['standard']);
        self::assertSame([], $view['seasons']);
        self::assertSame([], $view['upcoming']);
    }

    public function testSeasonsCurrentFirstThenByNextStart(): void
    {
        $slots = [Slot::allDays('10:00', '18:00')];
        $hours = new OpeningHours([new Slot([1], '09:00', '10:00')], [
            new Season('2026-04-01', '2026-09-30', true, 0, $slots, 'Sommer'),
            new Season('2025-11-01', '2026-03-31', true, 0, $slots, 'Winter'),
            new Season('2024-07-01', '2024-07-31', false, 0, $slots, 'Vorbei'),
        ]);

        $view = $this->builder->build($hours, $this->at('2026-11-18'), self::LABELS);

        self::assertSame(['Winter', 'Sommer'], array_column($view['seasons'], 'title'));
        self::assertTrue($view['seasons'][0]['current']);
        self::assertFalse($view['seasons'][1]['current']);
        self::assertFalse($view['standardCurrent']);
        self::assertSame('Mo–So', $view['seasons'][0]['groups'][0]['days']);
    }

    public function testCurrentBlockComesFirst(): void
    {
        $slots = [Slot::allDays('10:00', '18:00')];
        $hours = new OpeningHours([new Slot([1], '09:00', '10:00')], [
            new Season('2026-04-01', '2026-12-31', true, 0, $slots, 'Sommer'),
            new Season('2026-01-01', '2026-03-31', true, 0, $slots, 'Winter'),
        ]);

        $view = $this->builder->build($hours, $this->at('2026-11-18'), self::LABELS);

        self::assertSame(['season', 'standard', 'season'], array_column($view['blocks'], 'kind'));
        self::assertSame('Sommer', $view['blocks'][0]['title']);
        self::assertTrue($view['blocks'][0]['current']);

        $noSeason = $this->builder->build($hours, $this->at('2026-03-01'), self::LABELS);
        self::assertSame(['season', 'standard', 'season'], array_column($noSeason['blocks'], 'kind'));

        $plain = $this->builder->build(new OpeningHours([Slot::allDays('09:00', '10:00')], [new Season('2026-04-01', '2026-09-30', true, 0, $slots, 'Sommer')]), $this->at('2026-11-18'), self::LABELS);
        self::assertSame(['standard', 'season'], array_column($plain['blocks'], 'kind'));
    }

    public function testUpcomingExceptionsAndHolidays(): void
    {
        $hours = new OpeningHours(
            [Slot::allDays('09:00', '17:00')],
            [],
            [
                new ExceptionDay('2026-12-24', null, true, false, [Slot::allDays('09:00', '12:00')], 'Heiligabend'),
                new ExceptionDay('2026-12-25', '2026-12-26', true, true, [], 'Weihnachten'),
                new ExceptionDay('2026-03-01', null, false, true, [], 'Zu spät'),
                new ExceptionDay('2026-11-01', null, false, true, [], 'Schon vorbei'),
            ],
            holidayRegion: 'BY',
        );

        $view = $this->builder->build($hours, $this->at('2026-11-18'), self::LABELS, 2);

        // 2 Monate: bis 17.01.2027; Neujahr und Heilige Drei Könige (BY) kommen dazu
        $titles = array_column($view['upcoming'], 'title');
        self::assertSame(['Heiligabend', 'Weihnachten', 'Neujahr', 'Heilige Drei Könige'], $titles);

        self::assertFalse($view['upcoming'][0]['closed']);
        self::assertSame('12:00', $view['upcoming'][0]['windows'][0]['closes']);
        self::assertSame('2026-12-26', $view['upcoming'][1]['stop']?->format('Y-m-d'));
        self::assertSame('holiday', $view['upcoming'][2]['kind']);
        self::assertTrue($view['upcoming'][2]['closed']);
        self::assertSame('2027-01-17', $view['until']->format('Y-m-d'));
    }

    public function testHolidayCoveredByExceptionIsShownOnlyOnce(): void
    {
        $hours = new OpeningHours(
            [Slot::allDays('09:00', '17:00')],
            [],
            [new ExceptionDay('2027-01-01', null, false, false, [Slot::allDays('12:00', '14:00')], 'Neujahrsempfang')],
            holidayRegion: 'DE',
        );

        $view = $this->builder->build($hours, $this->at('2026-12-20'), self::LABELS, 1);

        self::assertSame(['Neujahrsempfang'], array_column(array_filter($view['upcoming'], static fn (array $i): bool => '2027-01-01' === $i['start']->format('Y-m-d')), 'title'));
    }

    public function testRecurringRangeOverYearEndIsOngoing(): void
    {
        $hours = new OpeningHours([Slot::allDays('09:00', '17:00')], [], [new ExceptionDay('2025-12-20', '2026-01-06', true, true, [], 'Betriebsferien')]);

        $view = $this->builder->build($hours, $this->at('2026-01-02'), self::LABELS, 1);

        self::assertCount(1, $view['upcoming']);
        self::assertSame('2025-12-20', $view['upcoming'][0]['start']->format('Y-m-d'));
        self::assertSame('2026-01-06', $view['upcoming'][0]['stop']?->format('Y-m-d'));
    }

    private function at(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date.' 10:00', new DateTimeZone('Europe/Berlin'));
    }
}
