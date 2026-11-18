<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Output;

use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Output\SchemaOrgBuilder;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ClosurePeriod;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\ExceptionDay;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Slot;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;
use PHPUnit\Framework\TestCase;

final class SchemaOrgBuilderTest extends TestCase
{
    private SchemaOrgBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SchemaOrgBuilder(new OpeningHoursResolver());
    }

    public function testLocationData(): void
    {
        $data = $this->builder->build(
            ['title' => 'Stadtmuseum', 'street' => 'Hauptstraße 1', 'postal' => '22844', 'city' => 'Norderstedt', 'schemaType' => 'Museum'],
            $this->museum(),
            $this->at('2026-01-07 10:00'),
        );

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Museum', $data['@type']);
        $this->assertSame('Stadtmuseum', $data['name']);
        $this->assertSame('PostalAddress', $data['address']['@type']);
        $this->assertSame('Norderstedt', $data['address']['addressLocality']);
    }

    public function testDefaultTypeAndNoAddress(): void
    {
        $data = $this->builder->build(['title' => 'Ort'], $this->museum(), $this->at('2026-01-07 10:00'));

        $this->assertSame('Place', $data['@type']);
        $this->assertArrayNotHasKey('address', $data);
    }

    public function testRegularSpecsAreGroupedByTimes(): void
    {
        $data = $this->builder->build(['title' => 'Ort'], $this->museum(), $this->at('2026-01-07 10:00'));

        $regular = array_values(array_filter(
            $data['openingHoursSpecification'],
            static fn (array $spec): bool => !isset($spec['validFrom']),
        ));

        $this->assertCount(3, $regular);

        $this->assertSame('OpeningHoursSpecification', $regular[0]['@type']);
        $this->assertSame('09:00', $regular[0]['opens']);
        $this->assertSame('12:00', $regular[0]['closes']);
        $this->assertSame([
            'https://schema.org/Monday',
            'https://schema.org/Tuesday',
            'https://schema.org/Wednesday',
            'https://schema.org/Thursday',
            'https://schema.org/Friday',
        ], $regular[0]['dayOfWeek']);

        $this->assertSame('13:00', $regular[1]['opens']);
        $this->assertSame('17:00', $regular[1]['closes']);

        $this->assertSame('10:00', $regular[2]['opens']);
        $this->assertSame(['https://schema.org/Saturday'], $regular[2]['dayOfWeek']);
    }

    public function testNoSpecialSpecsWithoutDeviations(): void
    {
        $data = $this->builder->build(['title' => 'Ort'], $this->museum(), $this->at('2026-01-07 10:00'));

        foreach ($data['openingHoursSpecification'] as $spec) {
            $this->assertArrayNotHasKey('validFrom', $spec);
        }
    }

    public function testExceptionsBecomeSpecialDays(): void
    {
        $hours = $this->museum([
            new ExceptionDay('2026-12-24', null, true, false, [Slot::allDays('09:00', '12:00')]),
            new ExceptionDay('2026-12-25', '2026-12-26', true, true),
        ]);

        $data = $this->builder->build(['title' => 'Ort'], $hours, $this->at('2026-12-20 10:00'));
        $special = $this->specialByDate($data);

        $this->assertSame('09:00', $special['2026-12-24'][0]['opens']);
        $this->assertSame('12:00', $special['2026-12-24'][0]['closes']);
        $this->assertCount(1, $special['2026-12-24']);

        $this->assertSame('00:00', $special['2026-12-25'][0]['opens']);
        $this->assertSame('00:00', $special['2026-12-25'][0]['closes']);
        $this->assertSame('2026-12-25', $special['2026-12-25'][0]['validThrough']);

        // Samstag ist regulär 10-14 Uhr, durch die Ausnahme geschlossen
        $this->assertSame('00:00', $special['2026-12-26'][0]['opens']);

        // Sonntag ist regulär und tatsächlich geschlossen: keine Abweichung
        $this->assertArrayNotHasKey('2026-12-27', $special);
    }

    public function testClosureBecomesSpecialDays(): void
    {
        $hours = $this->museum([], [new ClosurePeriod($this->at('2026-01-08 08:00'), null, true, 'Krank')]);

        $data = $this->builder->build(['title' => 'Ort'], $hours, $this->at('2026-01-07 10:00'));
        $special = $this->specialByDate($data);

        $this->assertArrayNotHasKey('2026-01-07', $special);
        $this->assertSame('00:00', $special['2026-01-08'][0]['opens']);
        $this->assertSame('https://schema.org/Thursday', $special['2026-01-08'][0]['dayOfWeek'][0]);
    }

    public function testNoPlanHasNoSpecification(): void
    {
        $data = $this->builder->build(['title' => 'Ort'], new OpeningHours(null), $this->at('2026-01-07 10:00'));

        $this->assertArrayNotHasKey('openingHoursSpecification', $data);
    }

    public function testOutputIsJsonEncodable(): void
    {
        $data = $this->builder->build(['title' => 'Café "Zum <Eck>"'], $this->museum(), $this->at('2026-01-07 10:00'));

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('OpeningHoursSpecification', $json);
        $this->assertStringNotContainsString('<Eck>', $json);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function specialByDate(array $data): array
    {
        $byDate = [];

        foreach ($data['openingHoursSpecification'] as $spec) {
            if (isset($spec['validFrom'])) {
                $byDate[$spec['validFrom']][] = $spec;
            }
        }

        return $byDate;
    }

    /**
     * @param list<ExceptionDay>  $exceptions
     * @param list<ClosurePeriod> $closures
     */
    private function museum(array $exceptions = [], array $closures = []): OpeningHours
    {
        $weekdays = [1, 2, 3, 4, 5];

        return new OpeningHours(
            [
                new Slot($weekdays, '09:00', '12:00'),
                new Slot($weekdays, '13:00', '17:00'),
                new Slot([6], '10:00', '14:00'),
            ],
            [],
            $exceptions,
            $closures,
        );
    }

    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($time, new DateTimeZone('Europe/Berlin'));
    }
}
