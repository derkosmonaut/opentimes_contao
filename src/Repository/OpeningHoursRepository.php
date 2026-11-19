<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Repository;

use Contao\StringUtil;
use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Holiday\GermanHolidays;
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

    /**
     * @return list<int>
     */
    public function findAllLocationIds(): array
    {
        return array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM tl_oh_location ORDER BY id'));
    }

    public function loadHours(int $locationId, DateTimeZone $timezone): OpeningHours
    {
        $schedules = $this->connection->fetchAllAssociative(
            'SELECT id, type, title, `start`, `stop`, recurring, priority FROM tl_oh_schedule WHERE pid = ? ORDER BY id',
            [$locationId],
        );

        // Zeitfenster aller Zeitpläne des Ortes in einer Abfrage, nach Zeitplan gruppiert
        $slotRows = $this->connection->fetchAllAssociative(
            'SELECT s.pid, s.weekdays, s.opens, s.closes, s.opens2, s.closes2
               FROM tl_oh_slot s
         INNER JOIN tl_oh_schedule p ON p.id = s.pid
              WHERE p.pid = ?
           ORDER BY s.opens',
            [$locationId],
        );

        $slotsBySchedule = [];

        foreach ($slotRows as $row) {
            foreach ($this->createSlots($row, $timezone) as $slot) {
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

                // ein Ende vor dem Beginn ist ungültig und würde bei Wiederholung fast das ganze Jahr treffen
                if (null === $start || (null !== $stop && $stop < $start)) {
                    continue;
                }

                $seasons[] = new Season($start, $stop ?? $start, '' !== (string) $schedule['recurring'], (int) $schedule['priority'], $slots, (string) $schedule['title']);

                continue;
            }

            // mehrere Standardpläne werden zusammengeführt
            $defaultSlots = [...($defaultSlots ?? []), ...$slots];
        }

        return new OpeningHours(
            $defaultSlots,
            $seasons,
            $this->loadExceptions($locationId, $timezone),
            $this->loadClosures($locationId, $timezone),
            $this->loadHolidays($locationId, $timezone),
            $this->holidayRegion($locationId),
        );
    }

    private function holidayRegion(int $locationId): string
    {
        return (string) $this->connection->fetchOne('SELECT holiday_region FROM tl_oh_location WHERE id = ?', [$locationId]);
    }

    /**
     * Feiertage des eingestellten Bundeslandes für das Vorjahr bis zwei Jahre voraus.
     *
     * @return list<string>
     */
    private function loadHolidays(int $locationId, DateTimeZone $timezone): array
    {
        $region = $this->holidayRegion($locationId);

        if ('' === $region) {
            return [];
        }

        $year = (int) (new DateTimeImmutable('now', $timezone))->format('Y');

        return GermanHolidays::datesForYears($year - 1, $year + 2, $region);
    }

    /**
     * @return list<ExceptionDay>
     */
    private function loadExceptions(int $locationId, DateTimeZone $timezone): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT type, title, `start`, `stop`, recurring, opens, closes, opens2, closes2 FROM tl_oh_exception WHERE pid = ? ORDER BY `start`',
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

            $stop = $this->formatDate($row['stop'], $timezone);

            // ein Ende vor dem Beginn ist ungültig: dann gilt nur der Starttag
            if (null !== $stop && $stop < $start) {
                $stop = null;
            }

            $exceptions[] = new ExceptionDay(
                $start,
                $stop,
                '' !== (string) $row['recurring'],
                'hours' !== $row['type'] || [] === $slots,
                $slots,
                (string) $row['title'],
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
            'SELECT `start`, `stop`, active, message, alert FROM tl_oh_closure WHERE pid = ? ORDER BY `start`',
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
                '' !== (string) $row['alert'],
            );
        }

        return $closures;
    }

    /**
     * Ein Datensatz enthält ein Pflichtfenster und optional ein zweites Fenster für dieselben Wochentage.
     *
     * @param array<string, mixed> $row
     *
     * @return list<Slot>
     */
    private function createSlots(array $row, DateTimeZone $timezone): array
    {
        $weekdays = array_values(array_filter(
            array_map('intval', StringUtil::deserialize($row['weekdays'], true)),
            static fn (int $day): bool => $day >= 1 && $day <= 7,
        ));

        if ([] === $weekdays) {
            return [];
        }

        $slots = [];

        // ungültige Uhrzeiten überspringen, damit eine fehlerhafte Zeile nicht die ganze Seite stört
        foreach ([['opens', 'closes'], ['opens2', 'closes2']] as [$opensField, $closesField]) {
            $opens = TimeFormat::normalize((string) ($row[$opensField] ?? ''), false, $timezone);
            $closes = TimeFormat::normalize((string) ($row[$closesField] ?? ''), true, $timezone);

            if (null !== $opens && null !== $closes) {
                $slots[] = new Slot($weekdays, $opens, $closes);
            }
        }

        return $slots;
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
