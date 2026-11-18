<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Output;

use DateTimeImmutable;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Interval;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;

/**
 * Erzeugt schema.org-Daten (JSON-LD) aus den Öffnungszeiten eines Ortes.
 *
 * - Reguläre Zeiten: die Wochenzeiten, die am Referenzdatum gelten (Saison oder Standard),
 *   als OpeningHoursSpecification ohne Gültigkeitsangabe.
 * - Sondertage: jeder Tag im Vorausblick, an dem die tatsächlichen Zeiten (Ausnahmen,
 *   Saisonwechsel, Schließungen) von den regulären abweichen, mit validFrom = validThrough.
 *   Geschlossene Tage werden, wie bei Google üblich, mit opens = closes = "00:00" angegeben.
 *
 * Die Daten gelten für den Moment der Erzeugung; die Ausgabe sollte entsprechend kurz gecacht werden.
 */
final class SchemaOrgBuilder
{
    private const DAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public function __construct(private readonly OpeningHoursResolver $resolver)
    {
    }

    /**
     * @param array{title: string, street?: string, postal?: string, city?: string, schemaType?: string} $location
     *
     * @return array<string, mixed>
     */
    public function build(array $location, OpeningHours $hours, DateTimeImmutable $now, int $horizonDays = 30): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => '' !== ($location['schemaType'] ?? '') ? $location['schemaType'] : 'Place',
            'name' => $location['title'],
        ];

        $address = array_filter([
            'streetAddress' => $location['street'] ?? '',
            'postalCode' => $location['postal'] ?? '',
            'addressLocality' => $location['city'] ?? '',
        ], static fn (string $value): bool => '' !== $value);

        if ([] !== $address) {
            $data['address'] = ['@type' => 'PostalAddress'] + $address;
        }

        if ($hours->hasPlan()) {
            $specs = [...$this->regularSpecs($hours, $now), ...$this->specialSpecs($hours, $now, $horizonDays)];

            if ([] !== $specs) {
                $data['openingHoursSpecification'] = $specs;
            }
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function regularSpecs(OpeningHours $hours, DateTimeImmutable $now): array
    {
        $slots = $this->resolver->getRegularSlots($hours, $now) ?? [];
        $day0 = $now->setTime(0, 0);
        $groups = [];

        for ($i = 0; $i < 7; ++$i) {
            $date = $day0->modify('+'.$i.' day');
            $weekday = (int) $date->format('N');

            foreach ($this->resolver->getWindowsForSlots($slots, $date) as $interval) {
                [$opens, $closes] = $this->times($interval);

                $groups[$opens.'|'.$closes]['opens'] = $opens;
                $groups[$opens.'|'.$closes]['closes'] = $closes;
                $groups[$opens.'|'.$closes]['days'][] = $weekday;
            }
        }

        $specs = [];

        foreach ($groups as $group) {
            sort($group['days']);

            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_map(static fn (int $day): string => 'https://schema.org/'.self::DAYS[$day], $group['days']),
                'opens' => $group['opens'],
                'closes' => $group['closes'],
            ];
        }

        return $specs;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function specialSpecs(OpeningHours $hours, DateTimeImmutable $now, int $horizonDays): array
    {
        $slots = $this->resolver->getRegularSlots($hours, $now) ?? [];
        $day0 = $now->setTime(0, 0);
        $specs = [];

        for ($i = 0; $i < $horizonDays; ++$i) {
            $date = $day0->modify('+'.$i.' day');
            $ymd = $date->format('Y-m-d');

            $expected = array_map(
                fn (Interval $interval): array => $this->times($interval),
                $this->resolver->getWindowsForSlots($slots, $date),
            );
            $actual = array_map(
                fn (Interval $interval): array => $this->times($interval),
                $this->resolver->getDayIntervals($hours, $date),
            );

            if ($expected === $actual) {
                continue;
            }

            $base = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['https://schema.org/'.self::DAYS[(int) $date->format('N')]],
                'validFrom' => $ymd,
                'validThrough' => $ymd,
            ];

            if ([] === $actual) {
                $specs[] = $base + ['opens' => '00:00', 'closes' => '00:00'];

                continue;
            }

            foreach ($actual as [$opens, $closes]) {
                $specs[] = $base + ['opens' => $opens, 'closes' => $closes];
            }
        }

        return $specs;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function times(Interval $interval): array
    {
        $closes = $interval->end->format('H:i');

        // Mitternacht als Ende wird als 23:59 ausgegeben
        if ('00:00' === $closes) {
            $closes = '23:59';
        }

        return [$interval->start->format('H:i'), $closes];
    }
}
