<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Output;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Output\ViewDataBuilder;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ClosurePeriod;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;
use PHPUnit\Framework\TestCase;

final class ViewDataBuilderTest extends TestCase
{
    private const LABELS = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

    private ViewDataBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ViewDataBuilder(new OpeningHoursResolver());
    }

    public function testWeekOverview(): void
    {
        $view = $this->builder->build($this->museum(), $this->at('2026-01-07 10:30'), self::LABELS);

        $this->assertCount(7, $view['days']);
        $this->assertTrue($view['hasPlan']);
        $this->assertTrue($view['status']->isOpen());

        $today = $view['days'][0];
        $this->assertTrue($today['isToday']);
        $this->assertSame(3, $today['weekday']);
        $this->assertSame('Mittwoch', $today['label']);
        $this->assertFalse($today['closed']);
        $this->assertSame(
            [
                ['opens' => '09:00', 'closes' => '12:00', 'overnight' => false],
                ['opens' => '13:00', 'closes' => '17:00', 'overnight' => false],
            ],
            $today['windows'],
        );

        $this->assertFalse($view['days'][1]['isToday']);
        $this->assertCount(1, $view['days'][3]['windows']); // Samstag
        $this->assertTrue($view['days'][4]['closed']); // Sonntag
        $this->assertSame([], $view['days'][4]['windows']);
    }

    public function testClosureKeepsPlannedTimesButMarksTheDay(): void
    {
        $hours = $this->museum([new ClosurePeriod($this->at('2026-01-07 08:00'), null, true, 'Krank')]);

        $view = $this->builder->build($hours, $this->at('2026-01-07 10:30'), self::LABELS);

        $this->assertFalse($view['status']->isOpen());
        $this->assertTrue($view['status']->closedByClosure);
        $this->assertCount(2, $view['days'][0]['windows']);
        $this->assertTrue($view['days'][0]['closureNote']);
        $this->assertTrue($view['days'][6]['closureNote']); // offene Schließung betrifft alle Folgetage
    }

    public function testNoClosureNote(): void
    {
        $view = $this->builder->build($this->museum(), $this->at('2026-01-07 10:30'), self::LABELS);

        foreach ($view['days'] as $day) {
            $this->assertFalse($day['closureNote']);
        }
    }

    public function testNightWindowAndMidnightEnd(): void
    {
        $night = new OpeningHours([new Slot([5], '22:00', '02:00'), new Slot([6], '00:00', '24:00')]);

        $view = $this->builder->build($night, $this->at('2026-01-09 12:00'), self::LABELS);

        $this->assertSame([['opens' => '22:00', 'closes' => '02:00', 'overnight' => true]], $view['days'][0]['windows']);
        $this->assertSame([['opens' => '00:00', 'closes' => '24:00', 'overnight' => false]], $view['days'][1]['windows']);
    }

    public function testUnknownLabelFallsBackToNumber(): void
    {
        $view = $this->builder->build($this->museum(), $this->at('2026-01-07 10:30'), []);

        $this->assertSame('3', $view['days'][0]['label']);
    }

    /**
     * @param list<ClosurePeriod> $closures
     */
    private function museum(array $closures = []): OpeningHours
    {
        $weekdays = [1, 2, 3, 4, 5];

        return new OpeningHours(
            [
                new Slot($weekdays, '09:00', '12:00'),
                new Slot($weekdays, '13:00', '17:00'),
                new Slot([6], '10:00', '14:00'),
            ],
            [],
            [],
            $closures,
        );
    }

    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($time, new DateTimeZone('Europe/Berlin'));
    }
}
