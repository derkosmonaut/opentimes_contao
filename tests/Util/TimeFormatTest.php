<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Tests\Util;

use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Util\TimeFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TimeFormatTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool, string|null}>
     */
    public static function plainCases(): iterable
    {
        yield 'normal' => ['09:00', false, '09:00'];
        yield 'ohne führende Null' => ['9:05', false, '09:05'];
        yield 'mit Leerzeichen' => [' 17:30 ', false, '17:30'];
        yield 'Mitternacht' => ['00:00', false, '00:00'];
        yield 'letzte Minute' => ['23:59', false, '23:59'];
        yield '24:00 nur wenn erlaubt (verboten)' => ['24:00', false, null];
        yield '24:00 nur wenn erlaubt (erlaubt)' => ['24:00', true, '24:00'];
        yield '24:30 nie' => ['24:30', true, null];
        yield 'Stunde 25' => ['25:00', false, null];
        yield 'Minute 60' => ['12:60', false, null];
        yield 'leer' => ['', false, null];
        yield 'Text' => ['früh', false, null];
        yield 'Ziffern ohne Altdaten-Modus' => ['32400', false, null];
        yield 'mit Sekunden' => ['09:00:00', false, null];
    }

    #[DataProvider('plainCases')]
    public function testNormalize(string $input, bool $allow24, ?string $expected): void
    {
        $this->assertSame($expected, TimeFormat::normalize($input, $allow24));
    }

    public function testLegacyTimestampIsConvertedToLocalTime(): void
    {
        // 32400 s nach Mitternacht UTC = 09:00 UTC = 10:00 in Berlin (Winterzeit 1970)
        $this->assertSame('10:00', TimeFormat::normalize('32400', false, new DateTimeZone('Europe/Berlin')));
        $this->assertSame('09:00', TimeFormat::normalize('32400', false, new DateTimeZone('UTC')));
    }

    public function testLegacyModeStillRejectsGarbage(): void
    {
        $this->assertNull(TimeFormat::normalize('abc', false, new DateTimeZone('UTC')));
        $this->assertNull(TimeFormat::normalize('', false, new DateTimeZone('UTC')));
    }
}
