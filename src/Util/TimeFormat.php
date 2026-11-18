<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Util;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Prüft und vereinheitlicht Uhrzeiten im Format "HH:MM".
 */
final class TimeFormat
{
    /**
     * @param bool              $allow24        "24:00" zulassen (nur sinnvoll als Ende eines Zeitfensters)
     * @param DateTimeZone|null $legacyTimezone wenn gesetzt, werden reine Ziffernfolgen als Zeitstempel eines
     *                                          Contao-Zeitfelds (rgxp "time") gelesen und in diese Zeitzone umgerechnet
     *
     * @return string|null "HH:MM" oder null, wenn der Wert keine gültige Uhrzeit ist
     */
    public static function normalize(string $value, bool $allow24 = false, ?DateTimeZone $legacyTimezone = null): ?string
    {
        $value = trim($value);

        if ('' === $value) {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            $hour = (int) $m[1];
            $minute = (int) $m[2];

            if ($minute > 59) {
                return null;
            }

            if ($hour > 23 && !($allow24 && 24 === $hour && 0 === $minute)) {
                return null;
            }

            return \sprintf('%02d:%02d', $hour, $minute);
        }

        // Altdaten: frühere Versionen nutzten ein Contao-Zeitfeld, das einen Zeitstempel speichert
        if (null !== $legacyTimezone && ctype_digit($value)) {
            return (new DateTimeImmutable('@'.$value))->setTimezone($legacyTimezone)->format('H:i');
        }

        return null;
    }
}
