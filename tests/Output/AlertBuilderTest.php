<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Output;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Output\AlertBuilder;
use Derkosmonaut\OpenTimesBundle\Repository\OpeningHoursRepository;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class AlertBuilderTest extends TestCase
{
    private Connection $connection;
    private DateTimeZone $timezone;
    private AlertBuilder $builder;

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

        foreach ([1 => 'Museum', 2 => 'Café'] as $id => $title) {
            $this->connection->insert('tl_oh_location', ['id' => $id, 'title' => $title, 'schema_type' => 'Place', 'street' => '', 'postal' => '', 'city' => '', 'holiday_region' => '']);
        }

        $this->builder = new AlertBuilder(new OpeningHoursRepository($this->connection), new OpeningHoursResolver());
    }

    private function at(string $dateTime): DateTimeImmutable
    {
        return new DateTimeImmutable($dateTime, $this->timezone);
    }

    private function addClosure(int $location, string $message, ?string $start, ?string $stop, string $active = '1'): void
    {
        $this->connection->insert('tl_oh_closure', [
            'pid' => $location,
            'message' => $message,
            'active' => $active,
            'start' => null === $start ? null : $this->at($start)->getTimestamp(),
            'stop' => null === $stop ? null : $this->at($stop)->getTimestamp(),
        ]);
    }

    public function testEmptyWithoutClosure(): void
    {
        $result = $this->builder->build([], $this->at('2026-11-18 12:00'), $this->timezone);

        self::assertSame([], $result['alerts']);
        self::assertSame(300, $result['maxAge']);
    }

    public function testShowsRunningClosureForAllLocationsWhenNoneSelected(): void
    {
        $this->addClosure(1, 'Wegen Krankheit geschlossen', '2026-11-18 08:00', '2026-11-19 08:00');

        $result = $this->builder->build([], $this->at('2026-11-18 12:00'), $this->timezone);

        self::assertCount(1, $result['alerts']);
        self::assertSame('Museum', $result['alerts'][0]['location']);
        self::assertSame('Wegen Krankheit geschlossen', $result['alerts'][0]['message']);
        self::assertNotNull($result['alerts'][0]['until']);
    }

    public function testOnlySelectedLocationsAreChecked(): void
    {
        $this->addClosure(1, 'Museum zu', '2026-11-18 08:00', null);

        self::assertSame([], $this->builder->build([2], $this->at('2026-11-18 12:00'), $this->timezone)['alerts']);
        self::assertCount(1, $this->builder->build([1, 2], $this->at('2026-11-18 12:00'), $this->timezone)['alerts']);
    }

    public function testOpenEndedClosureHasNoUntil(): void
    {
        $this->addClosure(1, '', '2026-11-18 08:00', null);

        $alert = $this->builder->build([1], $this->at('2026-11-18 12:00'), $this->timezone)['alerts'][0];

        self::assertNull($alert['until']);
        self::assertSame('', $alert['message']);
    }

    public function testFutureAndEndedClosuresAreNotShown(): void
    {
        $this->addClosure(1, 'später', '2026-11-20 08:00', null);
        $this->addClosure(1, 'vorbei', '2026-11-10 08:00', '2026-11-11 08:00');

        self::assertSame([], $this->builder->build([1], $this->at('2026-11-18 12:00'), $this->timezone)['alerts']);
    }

    public function testInactiveClosureIsIgnored(): void
    {
        $this->addClosure(1, 'aus', '2026-11-18 08:00', null, '');

        self::assertSame([], $this->builder->build([1], $this->at('2026-11-18 12:00'), $this->timezone)['alerts']);
    }

    public function testCacheEndsWhenAClosureStarts(): void
    {
        $this->addClosure(1, 'gleich', '2026-11-18 12:02', null);

        $result = $this->builder->build([1], $this->at('2026-11-18 12:00'), $this->timezone);

        self::assertSame([], $result['alerts']);
        self::assertSame(120, $result['maxAge']);
    }

    public function testCacheNeverBelowMinimum(): void
    {
        $this->addClosure(1, 'sofort', '2026-11-18 12:00:05', null);

        self::assertSame(15, $this->builder->build([1], $this->at('2026-11-18 12:00'), $this->timezone)['maxAge']);
    }
}
