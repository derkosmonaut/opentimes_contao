<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Resolver;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ClosurePeriod;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ExceptionDay;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Season;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Status;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Testfälle aus der Konzeptphase (Beispielort: Museum).
 *
 * Standard:  Mo-Fr 09-12 und 13-17, Sa 10-14, So zu
 * Sommer:    1.4.-30.9. jährlich, Mo-Fr 09-12 und 13-18, Sa/So 10-16
 * Ausnahmen: 24.12. 09-12, 25.-26.12. zu, 1.5. zu (jeweils jährlich)
 *
 * Kalenderhilfe 2026: 07.01. Mi, 09.01. Fr, 10.01. Sa, 11.01. So, 12.01. Mo,
 * 01.04. Mi, 30.09. Mi, 01.10. Do, 01.05. Fr, 24.12. Do.
 */
final class OpeningHoursResolverTest extends TestCase
{
    private OpeningHoursResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OpeningHoursResolver();
    }

    /**
     * Fälle 1-8 und 11-15: Standard, Saison, Ausnahmen.
     *
     * @return iterable<string, array{string, string, string|null, string|null}>
     */
    public static function museumCases(): iterable
    {
        // [Zeitpunkt, Zustand, offen bis, nächste Öffnung]
        yield '1 Winter-Mittwoch 10:30' => ['2026-01-07 10:30', Status::OPEN, '2026-01-07 12:00', null];
        yield '2 Winter-Mittwoch 12:30' => ['2026-01-07 12:30', Status::CLOSED, null, '2026-01-07 13:00'];
        yield '3a genau 12:00 (Ende exklusiv)' => ['2026-01-07 12:00', Status::CLOSED, null, '2026-01-07 13:00'];
        yield '3b genau 13:00 (Beginn inklusiv)' => ['2026-01-07 13:00', Status::OPEN, '2026-01-07 17:00', null];
        yield '4 Sonntag im Winter' => ['2026-01-11 11:00', Status::CLOSED, null, '2026-01-12 09:00'];
        yield '5 Freitag nach Feierabend' => ['2026-01-09 17:30', Status::CLOSED, null, '2026-01-10 10:00'];
        yield '6 Saisonbeginn 1.4.' => ['2026-04-01 17:30', Status::OPEN, '2026-04-01 18:00', null];
        yield '7 Saisonende 30.9.' => ['2026-09-30 17:30', Status::OPEN, '2026-09-30 18:00', null];
        yield '8 einen Tag nach der Saison' => ['2026-10-01 17:30', Status::CLOSED, null, '2026-10-02 09:00'];
        yield '11 Heiligabend vormittags' => ['2026-12-24 10:00', Status::OPEN, '2026-12-24 12:00', null];
        yield '12 Heiligabend nachmittags' => ['2026-12-24 14:00', Status::CLOSED, null, '2026-12-28 09:00'];
        yield '13 erster Weihnachtstag' => ['2026-12-25 10:00', Status::CLOSED, null, '2026-12-28 09:00'];
        yield '14 Ausnahme schlägt Saison (1.5.)' => ['2026-05-01 10:00', Status::CLOSED, null, '2026-05-02 10:00'];
        yield '15a wiederkehrende Ausnahme im Folgejahr' => ['2027-12-24 10:00', Status::OPEN, '2027-12-24 12:00', null];
        yield '15b Folgejahr nachmittags' => ['2027-12-24 14:00', Status::CLOSED, null, '2027-12-27 09:00'];
    }

    #[DataProvider('museumCases')]
    public function testMuseum(string $now, string $state, ?string $until, ?string $nextOpen): void
    {
        $status = $this->resolver->getStatus($this->museum(), $this->at($now));

        $this->assertSame($state, $status->state);
        $this->assertSame($until, $this->fmt($status->until));
        $this->assertSame($nextOpen, $this->fmt($status->nextOpen));
    }

    public function testSeasonSpanningNewYear(): void // Fall 9
    {
        $winter = new Season('2025-11-01', '2026-03-31', true, 0, [new Slot([1, 2, 3, 4, 5], '10:00', '16:00')]);
        $hours = $this->museum(seasons: [$winter]);

        $open = $this->resolver->getStatus($hours, $this->at('2026-01-15 15:30'));
        $this->assertTrue($open->isOpen());
        $this->assertSame('2026-01-15 16:00', $this->fmt($open->until));

        // der Standardplan wäre um 16:30 noch offen, die Saison ersetzt ihn
        $this->assertFalse($this->resolver->getStatus($hours, $this->at('2026-01-15 16:30'))->isOpen());
    }

    public function testOverlappingSeasonsHigherPriorityWins(): void // Fall 10
    {
        $ferien = new Season('2026-07-15', '2026-08-31', true, 5, [Slot::allDays('10:00', '20:00')]);
        $hours = $this->museum(seasons: [$ferien]);

        $status = $this->resolver->getStatus($hours, $this->at('2026-07-20 19:00'));

        $this->assertTrue($status->isOpen());
        $this->assertSame('2026-07-20 20:00', $this->fmt($status->until));
    }

    public function testExceptionCanOpenOnClosedDay(): void // Fall 16
    {
        $sunday = new ExceptionDay('2026-01-11', null, false, false, [Slot::allDays('12:00', '16:00')]);
        $hours = $this->museum(exceptions: [$sunday]);

        $status = $this->resolver->getStatus($hours, $this->at('2026-01-11 13:00'));

        $this->assertTrue($status->isOpen());
        $this->assertSame('2026-01-11 16:00', $this->fmt($status->until));
    }

    public function testActiveClosureWithoutEnd(): void // Fall 17
    {
        $hours = $this->museum(closures: [new ClosurePeriod($this->at('2026-01-07 08:00'), null, true, 'Krank')]);

        $status = $this->resolver->getStatus($hours, $this->at('2026-01-07 10:30'));

        $this->assertSame(Status::CLOSED, $status->state);
        $this->assertTrue($status->closedByClosure);
        $this->assertSame('Krank', $status->message);
        $this->assertNull($status->nextOpen);
    }

    public function testClosureWithEnd(): void // Fall 18
    {
        $hours = $this->museum(closures: [
            new ClosurePeriod($this->at('2026-01-07 08:00'), $this->at('2026-01-07 15:00')),
        ]);

        $before = $this->resolver->getStatus($hours, $this->at('2026-01-07 14:59'));
        $this->assertSame(Status::CLOSED, $before->state);
        $this->assertTrue($before->closedByClosure);
        $this->assertSame('2026-01-07 15:00', $this->fmt($before->nextOpen));

        $after = $this->resolver->getStatus($hours, $this->at('2026-01-07 15:01'));
        $this->assertTrue($after->isOpen());
        $this->assertSame('2026-01-07 17:00', $this->fmt($after->until));
    }

    public function testInactiveClosureIsIgnored(): void // Fall 19
    {
        $hours = $this->museum(closures: [new ClosurePeriod($this->at('2026-01-07 08:00'), null, false, 'x')]);

        $this->assertTrue($this->resolver->getStatus($hours, $this->at('2026-01-07 10:30'))->isOpen());
    }

    public function testClosureBeatsException(): void // Fall 20
    {
        $hours = $this->museum(closures: [new ClosurePeriod($this->at('2026-12-24 08:00'), null)]);

        $status = $this->resolver->getStatus($hours, $this->at('2026-12-24 10:00'));

        $this->assertSame(Status::CLOSED, $status->state);
        $this->assertTrue($status->closedByClosure);
    }

    public function testFutureClosure(): void // Fall 21
    {
        $hours = $this->museum(closures: [new ClosurePeriod($this->at('2026-01-08 08:00'), null)]);

        $today = $this->resolver->getStatus($hours, $this->at('2026-01-07 10:30'));
        $this->assertTrue($today->isOpen());
        $this->assertSame('2026-01-07 12:00', $this->fmt($today->until));

        $evening = $this->resolver->getStatus($hours, $this->at('2026-01-07 17:30'));
        $this->assertSame(Status::CLOSED, $evening->state);
        $this->assertFalse($evening->closedByClosure);
        $this->assertNull($evening->nextOpen);
    }

    public function testOpenWindowIsCutAtClosureStart(): void
    {
        $hours = $this->museum(closures: [new ClosurePeriod($this->at('2026-01-07 11:00'), null)]);

        $status = $this->resolver->getStatus($hours, $this->at('2026-01-07 10:30'));

        $this->assertTrue($status->isOpen());
        $this->assertSame('2026-01-07 11:00', $this->fmt($status->until));
    }

    public function testNightWindow(): void // Fälle 22-24
    {
        $bar = new OpeningHours([new Slot([5], '22:00', '02:00')]);

        $friday = $this->resolver->getStatus($bar, $this->at('2026-01-09 23:30'));
        $this->assertTrue($friday->isOpen());
        $this->assertSame('2026-01-10 02:00', $this->fmt($friday->until));

        $saturday = $this->resolver->getStatus($bar, $this->at('2026-01-10 01:00'));
        $this->assertTrue($saturday->isOpen());
        $this->assertSame('2026-01-10 02:00', $this->fmt($saturday->until));

        $end = $this->resolver->getStatus($bar, $this->at('2026-01-10 02:00'));
        $this->assertSame(Status::CLOSED, $end->state);
        $this->assertSame('2026-01-16 22:00', $this->fmt($end->nextOpen));
    }

    public function testExceptionOnFollowingDayDoesNotCutNightWindow(): void // Fall 25
    {
        $bar = new OpeningHours(
            [new Slot([5], '22:00', '02:00')],
            exceptions: [new ExceptionDay('2026-01-10', null, false, true)],
        );

        $this->assertTrue($this->resolver->getStatus($bar, $this->at('2026-01-10 01:00'))->isOpen());
    }

    public function testNightWindowAcrossSeasonEnd(): void // Fall 26
    {
        $hours = new OpeningHours(
            [],
            seasons: [new Season('2026-04-01', '2026-09-30', true, 0, [new Slot([3], '22:00', '02:00')])],
        );

        $late = $this->resolver->getStatus($hours, $this->at('2026-09-30 23:00'));
        $this->assertTrue($late->isOpen());
        $this->assertSame('2026-10-01 02:00', $this->fmt($late->until));

        $this->assertTrue($this->resolver->getStatus($hours, $this->at('2026-10-01 01:00'))->isOpen());
    }

    public function testOverlappingSlotsAreMerged(): void // Fall 27
    {
        $hours = new OpeningHours([
            new Slot([1, 2, 3, 4, 5], '09:00', '13:00'),
            new Slot([1, 2, 3, 4, 5], '12:00', '17:00'),
        ]);

        $windows = $this->resolver->getDayWindows($hours, $this->at('2026-01-07 00:00'));

        $this->assertCount(1, $windows);
        $this->assertSame('2026-01-07 09:00', $this->fmt($windows[0]->start));
        $this->assertSame('2026-01-07 17:00', $this->fmt($windows[0]->end));
    }

    public function testNoPlanIsUnknownNotClosed(): void // Fall 28
    {
        $status = $this->resolver->getStatus(new OpeningHours(null), $this->at('2026-01-07 10:30'));

        $this->assertSame(Status::UNKNOWN, $status->state);
    }

    public function testClosureWithoutPlanStillShowsClosed(): void
    {
        $hours = new OpeningHours(null, closures: [new ClosurePeriod($this->at('2026-01-07 08:00'), null, true, 'Zu')]);

        $status = $this->resolver->getStatus($hours, $this->at('2026-01-07 10:30'));

        $this->assertSame(Status::CLOSED, $status->state);
        $this->assertSame('Zu', $status->message);
    }

    public function testAroundTheClock(): void // Fall 29
    {
        $hours = new OpeningHours([Slot::allDays('00:00', '24:00')]);

        $windows = $this->resolver->getDayWindows($hours, $this->at('2026-01-07 00:00'));
        $this->assertCount(1, $windows);
        $this->assertSame('2026-01-07 00:00', $this->fmt($windows[0]->start));
        $this->assertSame('2026-01-08 00:00', $this->fmt($windows[0]->end));

        $this->assertTrue($this->resolver->getStatus($hours, $this->at('2026-01-07 03:00'))->isOpen());
    }

    public function testDaylightSavingNightDoesNotBreak(): void // Fall 30
    {
        // 02:30 existiert am 29.03.2026 nicht (Umstellung 02:00 -> 03:00)
        $hours = new OpeningHours([new Slot([6], '22:00', '02:30')]);

        $this->assertTrue($this->resolver->getStatus($hours, $this->at('2026-03-29 01:30'))->isOpen());
    }

    public function testInvalidTimeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->resolver->getStatus(new OpeningHours([new Slot([3], '25:00', '12:00')]), $this->at('2026-01-07 10:00'));
    }

    /**
     * @param list<Season>        $seasons
     * @param list<ExceptionDay>  $exceptions
     * @param list<ClosurePeriod> $closures
     */
    private function museum(array $seasons = [], array $exceptions = [], array $closures = []): OpeningHours
    {
        $weekdays = [1, 2, 3, 4, 5];

        $summer = new Season('2026-04-01', '2026-09-30', true, 0, [
            new Slot($weekdays, '09:00', '12:00'),
            new Slot($weekdays, '13:00', '18:00'),
            new Slot([6, 7], '10:00', '16:00'),
        ]);

        return new OpeningHours(
            [
                new Slot($weekdays, '09:00', '12:00'),
                new Slot($weekdays, '13:00', '17:00'),
                new Slot([6], '10:00', '14:00'),
            ],
            [$summer, ...$seasons],
            [
                new ExceptionDay('2026-12-24', null, true, false, [Slot::allDays('09:00', '12:00')]),
                new ExceptionDay('2026-12-25', '2026-12-26', true, true),
                new ExceptionDay('2026-05-01', null, true, true),
                ...$exceptions,
            ],
            $closures,
        );
    }

    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($time, new DateTimeZone('Europe/Berlin'));
    }

    private function fmt(?DateTimeImmutable $date): ?string
    {
        return $date?->format('Y-m-d H:i');
    }
}
