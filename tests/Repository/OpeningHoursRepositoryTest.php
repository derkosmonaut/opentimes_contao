<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Repository\OpeningHoursRepository;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

/**
 * Liest echte Datensätze (SQLite im Speicher) und prüft das Ergebnis über den Resolver.
 */
final class OpeningHoursRepositoryTest extends TestCase
{
    private Connection $connection;
    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->timezone = new DateTimeZone('Europe/Berlin');
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        foreach ([
            'CREATE TABLE tl_oh_location (id INTEGER PRIMARY KEY, title TEXT, schema_type TEXT, street TEXT, postal TEXT, city TEXT, holiday_region TEXT)',
            'CREATE TABLE tl_oh_schedule (id INTEGER PRIMARY KEY, pid INTEGER, type TEXT, "start" INTEGER, "stop" INTEGER, recurring TEXT, priority INTEGER)',
            'CREATE TABLE tl_oh_slot (id INTEGER PRIMARY KEY, pid INTEGER, weekdays BLOB, opens TEXT, closes TEXT, opens2 TEXT, closes2 TEXT)',
            'CREATE TABLE tl_oh_exception (id INTEGER PRIMARY KEY, pid INTEGER, type TEXT, "start" INTEGER, "stop" INTEGER, recurring TEXT, opens TEXT, closes TEXT, opens2 TEXT, closes2 TEXT)',
            'CREATE TABLE tl_oh_closure (id INTEGER PRIMARY KEY, pid INTEGER, message TEXT, active TEXT, "start" INTEGER, "stop" INTEGER)',
        ] as $sql) {
            $this->connection->executeStatement($sql);
        }

        $this->connection->insert('tl_oh_location', ['id' => 1, 'title' => 'Museum', 'schema_type' => 'Museum', 'street' => '', 'postal' => '', 'city' => 'Hamburg', 'holiday_region' => '']);
    }

    private function repository(): OpeningHoursRepository
    {
        return new OpeningHoursRepository($this->connection);
    }

    private function timestamp(string $date): int
    {
        return (new DateTimeImmutable($date.' 00:00', $this->timezone))->getTimestamp();
    }

    private function addSlot(int $scheduleId, array $weekdays, string $opens, string $closes, string $opens2 = '', string $closes2 = ''): void
    {
        $this->connection->insert('tl_oh_slot', [
            'pid' => $scheduleId,
            'weekdays' => serialize(array_map('strval', $weekdays)),
            'opens' => $opens,
            'closes' => $closes,
            'opens2' => $opens2,
            'closes2' => $closes2,
        ]);
    }

    public function testFindLocation(): void
    {
        $location = $this->repository()->findLocation(1);

        self::assertSame('Museum', $location['title']);
        self::assertSame('Museum', $location['schemaType']);
        self::assertNull($this->repository()->findLocation(99));
    }

    public function testSlotWithTwoWindowsBecomesTwoIntervals(): void
    {
        $this->connection->insert('tl_oh_schedule', ['id' => 1, 'pid' => 1, 'type' => 'default', 'start' => null, 'stop' => null, 'recurring' => '', 'priority' => 0]);
        $this->addSlot(1, [1, 2, 3, 4, 5], '09:00', '12:00', '13:00', '17:00');

        $hours = $this->repository()->loadHours(1, $this->timezone);
        $resolver = new OpeningHoursResolver();
        $monday = new DateTimeImmutable('2026-11-16 00:00', $this->timezone);

        $windows = $resolver->getDayWindows($hours, $monday);

        self::assertCount(2, $windows);
        self::assertSame('09:00', $windows[0]->start->format('H:i'));
        self::assertSame('13:00', $windows[1]->start->format('H:i'));
        self::assertSame([], $resolver->getDayWindows($hours, new DateTimeImmutable('2026-11-21 00:00', $this->timezone)), 'Samstag ist geschlossen');
    }

    public function testSeasonOutsideItsRangeIsNotActive(): void
    {
        $this->connection->insert('tl_oh_schedule', ['id' => 2, 'pid' => 1, 'type' => 'season', 'start' => $this->timestamp('2026-04-01'), 'stop' => $this->timestamp('2026-10-31'), 'recurring' => '1', 'priority' => 0]);
        $this->addSlot(2, [1, 2, 3, 4, 5], '09:00', '10:00');

        $hours = $this->repository()->loadHours(1, $this->timezone);
        $resolver = new OpeningHoursResolver();

        self::assertTrue($resolver->getStatus($hours, new DateTimeImmutable('2026-10-20 09:30', $this->timezone))->isOpen());
        self::assertFalse($resolver->getStatus($hours, new DateTimeImmutable('2026-11-18 09:30', $this->timezone))->isOpen());
    }

    public function testExceptionWithEndBeforeStartOnlyAppliesToStartDay(): void
    {
        // Datenstand aus der Praxis: Beginn 24.12., Ende 24.11., jährlich
        $this->connection->insert('tl_oh_schedule', ['id' => 1, 'pid' => 1, 'type' => 'default', 'start' => null, 'stop' => null, 'recurring' => '', 'priority' => 0]);
        $this->addSlot(1, [1, 2, 3, 4, 5, 6, 7], '08:00', '18:00');
        $this->connection->insert('tl_oh_exception', ['pid' => 1, 'type' => 'hours', 'start' => $this->timestamp('2026-12-24'), 'stop' => $this->timestamp('2026-11-24'), 'recurring' => '1', 'opens' => '09:00', 'closes' => '10:00', 'opens2' => '', 'closes2' => '']);

        $hours = $this->repository()->loadHours(1, $this->timezone);
        $resolver = new OpeningHoursResolver();

        $normalDay = $resolver->getDayWindows($hours, new DateTimeImmutable('2026-11-18 00:00', $this->timezone));
        $christmasEve = $resolver->getDayWindows($hours, new DateTimeImmutable('2026-12-24 00:00', $this->timezone));

        self::assertSame('08:00', $normalDay[0]->start->format('H:i'));
        self::assertSame('09:00', $christmasEve[0]->start->format('H:i'));
    }

    public function testLegacyTimestampTimesAreConverted(): void
    {
        $this->connection->insert('tl_oh_schedule', ['id' => 1, 'pid' => 1, 'type' => 'default', 'start' => null, 'stop' => null, 'recurring' => '', 'priority' => 0]);
        $this->addSlot(1, [1], '28800', '32400');

        $hours = $this->repository()->loadHours(1, $this->timezone);
        $windows = (new OpeningHoursResolver())->getDayWindows($hours, new DateTimeImmutable('2026-11-16 00:00', $this->timezone));

        self::assertSame('09:00', $windows[0]->start->format('H:i'));
        self::assertSame('10:00', $windows[0]->end->format('H:i'));
    }

    public function testHolidayRegionClosesOnHolidays(): void
    {
        $this->connection->update('tl_oh_location', ['holiday_region' => 'HH'], ['id' => 1]);
        $this->connection->insert('tl_oh_schedule', ['id' => 1, 'pid' => 1, 'type' => 'default', 'start' => null, 'stop' => null, 'recurring' => '', 'priority' => 0]);
        $this->addSlot(1, [1, 2, 3, 4, 5, 6, 7], '08:00', '18:00');

        $hours = $this->repository()->loadHours(1, $this->timezone);
        $year = (int) (new DateTimeImmutable('now', $this->timezone))->format('Y');

        self::assertContains($year.'-12-25', $hours->holidays);
        self::assertContains($year.'-10-31', $hours->holidays, 'Reformationstag gilt in Hamburg');
        self::assertSame([], (new OpeningHoursResolver())->getDayWindows($hours, new DateTimeImmutable($year.'-12-25 00:00', $this->timezone)));
    }

    public function testNoHolidayRegionMeansNoHolidays(): void
    {
        $this->connection->insert('tl_oh_schedule', ['id' => 1, 'pid' => 1, 'type' => 'default', 'start' => null, 'stop' => null, 'recurring' => '', 'priority' => 0]);

        self::assertSame([], $this->repository()->loadHours(1, $this->timezone)->holidays);
    }
}
