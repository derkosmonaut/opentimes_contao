<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Diagnostics;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Diagnostics\HoursDiagnostics;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Season;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;
use PHPUnit\Framework\TestCase;

final class HoursDiagnosticsTest extends TestCase
{
    private HoursDiagnostics $diagnostics;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->diagnostics = new HoursDiagnostics(new OpeningHoursResolver());
        $this->now = new DateTimeImmutable('2026-11-18 12:00', new DateTimeZone('Europe/Berlin'));
    }

    /**
     * @param list<array{key: string, params: list<string>}> $warnings
     *
     * @return list<string>
     */
    private static function keys(array $warnings): array
    {
        return array_column($warnings, 'key');
    }

    public function testNoPlan(): void
    {
        self::assertSame(['no_plan'], self::keys($this->diagnostics->analyze(new OpeningHours(null), $this->now)));
    }

    public function testCleanPlanHasNoWarnings(): void
    {
        $hours = new OpeningHours([Slot::allDays('09:00', '17:00')], [
            new Season('2026-04-01', '2026-09-30', true, 0, [Slot::allDays('09:00', '18:00')]),
        ]);

        self::assertSame([], $this->diagnostics->analyze($hours, $this->now));
    }

    public function testSeasonsWithoutDefaultHours(): void
    {
        $hours = new OpeningHours(null, [new Season('2026-04-01', '2026-10-31', true, 0, [Slot::allDays('09:00', '10:00')])]);

        self::assertSame(['no_default'], self::keys($this->diagnostics->analyze($hours, $this->now)));
    }

    public function testEmptySeasonAndEmptyDefault(): void
    {
        $hours = new OpeningHours([], [new Season('2026-04-01', '2026-10-31', true, 0, [])]);

        self::assertSame(['default_empty', 'season_empty'], self::keys($this->diagnostics->analyze($hours, $this->now)));
    }

    public function testOverlappingSeasonsWithSamePriorityAreReportedOnce(): void
    {
        $slots = [Slot::allDays('09:00', '17:00')];
        $hours = new OpeningHours($slots, [
            new Season('2026-04-01', '2026-09-30', true, 0, $slots),
            new Season('2026-09-01', '2026-10-31', true, 0, $slots),
        ]);

        self::assertSame(['season_overlap'], self::keys($this->diagnostics->analyze($hours, $this->now)));
    }

    public function testOverlapWithDifferentPriorityIsFine(): void
    {
        $slots = [Slot::allDays('09:00', '17:00')];
        $hours = new OpeningHours($slots, [
            new Season('2026-04-01', '2026-09-30', true, 0, $slots),
            new Season('2026-09-01', '2026-10-31', true, 1, $slots),
        ]);

        self::assertSame([], $this->diagnostics->analyze($hours, $this->now));
    }
}
