<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Repository;

use Contao\StringUtil;
use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ClosurePeriod;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ExceptionDay;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Season;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Util\TimeFormat;
use Doctrine\DBAL\Connection;

/**
 * Liest die Backend-Daten eines Ortes und übersetzt sie in die Modelle des Resolvers.
 */
class OpeningHoursRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{id: int, title: string, street: string, postal: string, city: string, schemaType: string}|null
     */
    public function findLocation(int $id): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, title, street, postal, city, schema_type FROM tl_oh_location WHERE id = ?',
            [$id],
        );

        if (false === $row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'street' => (string) $row['street'],
            'postal' => (string) $row['postal'],
            'city' => (string) $row['city'],
            'schemaType' => (string) $row['schema_type'],
        ];
    }

    public function loadHours(int $locationId, DateTimeZone $timezone): OpeningHours
    {
        $schedules = $this->connection->fetchAllAssociative(
            'SELECT id, type, `start`, `stop`, recurring, priority FROM tl_oh_schedule WHERE pid = ? ORDER BY id',
            [$locationId],
        );

        // Zeitfenster aller Zeitpläne des Ortes in einer Abfrage, nach Zeitplan gruppiert
        $slotRows = $this->connection->fetchAllAssociative(
            'SELECT s.pid, s.weekdays, s.opens, s.closes
               FROM tl_oh_slot s
         INNER JOIN tl_oh_schedule p ON p.id = s.pid
              WHERE p.pid = ?
           ORDER BY s.opens',
            [$locationId],
        );

        $slotsBySchedule = [];

        foreach ($slotRows as $row) {
            $slot = $this->createSlot($row, $timezone);

            if (null !== $slot) {
                $slotsBySchedule[(int) $row['pid']][] = $slot;
            }
        }

        $defaultSlots = null;
        $seasons = [];

        foreach ($schedules as $schedule) {
            $slots = $slotsBySchedule[(int) $schedule['id']] ?? [];

            if ('season' === $schedule['type']) {
                $start = $this->formatDate($schedule['start'], $timezone);
                $stop = $this->formatDate($schedule['stop'], $timezone);

                if (null === $start) {
                    continue;
                }

                $seasons[] = new Season($start, $stop ?? $start, '' !== (string) $schedule['recurring'], (int) $schedule['priority'], $slots);

                continue;
            }

            // mehrere Standardpläne werden zusammengeführt
            $defaultSlots = [...($defaultSlots ?? []), ...$slots];
        }

        return new OpeningHours($defaultSlots, $seasons, $this->loadExceptions($locationId, $timezone), $this->loadClosures($locationId, $timezone));
    }

    /**
     * @return list<ExceptionDay>
     */
    private function loadExceptions(int $locationId, DateTimeZone $timezone): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT type, `start`, `stop`, recurring, opens, closes, opens2, closes2 FROM tl_oh_exception WHERE pid = ? ORDER BY `start`',
            [$locationId],
        );

        $exceptions = [];

        foreach ($rows as $row) {
            $start = $this->formatDate($row['start'], $timezone);

            if (null === $start) {
                continue;
            }

            $slots = [];

            if ('hours' === $row['type']) {
                foreach ([['opens', 'closes'], ['opens2', 'closes2']] as [$opens, $closes]) {
                    $from = TimeFormat::normalize((string) $row[$opens], false, $timezone);
                    $to = TimeFormat::normalize((string) $row[$closes], true, $timezone);

                    if (null !== $from && null !== $to) {
                        $slots[] = Slot::allDays($from, $to);
                    }
                }
            }

            $exceptions[] = new ExceptionDay(
                $start,
                $this->formatDate($row['stop'], $timezone),
                '' !== (string) $row['recurring'],
                'hours' !== $row['type'] || [] === $slots,
                $slots,
            );
        }

        return $exceptions;
    }

    /**
     * @return list<ClosurePeriod>
     */
    private function loadClosures(int $locationId, DateTimeZone $timezone): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT `start`, `stop`, active, message FROM tl_oh_closure WHERE pid = ? ORDER BY `start`',
            [$locationId],
        );

        $closures = [];

        foreach ($rows as $row) {
            if (empty($row['start'])) {
                continue;
            }

            $closures[] = new ClosurePeriod(
                $this->createDateTime((int) $row['start'], $timezone),
                empty($row['stop']) ? null : $this->createDateTime((int) $row['stop'], $timezone),
                '' !== (string) $row['active'],
                (string) $row['message'],
            );
        }

        return $closures;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createSlot(array $row, DateTimeZone $timezone): ?Slot
    {
        // ungültige Uhrzeiten überspringen, damit eine fehlerhafte Zeile nicht die ganze Seite stört
        $opens = TimeFormat::normalize((string) $row['opens'], false, $timezone) ?? '';
        $closes = TimeFormat::normalize((string) $row['closes'], true, $timezone) ?? '';

        $weekdays = array_values(array_filter(
            array_map('intval', StringUtil::deserialize($row['weekdays'], true)),
            static fn (int $day): bool => $day >= 1 && $day <= 7,
        ));

        if ('' === $opens || '' === $closes || [] === $weekdays) {
            return null;
        }

        return new Slot($weekdays, $opens, $closes);
    }

    private function formatDate(mixed $timestamp, DateTimeZone $timezone): ?string
    {
        if (empty($timestamp)) {
            return null;
        }

        return $this->createDateTime((int) $timestamp, $timezone)->format('Y-m-d');
    }

    private function createDateTime(int $timestamp, DateTimeZone $timezone): DateTimeImmutable
    {
        return (new DateTimeImmutable('@'.$timestamp))->setTimezone($timezone);
    }
}
