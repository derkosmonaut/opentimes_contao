<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Diagnostics;

use DateTimeImmutable;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\OpeningHours;
use Derkosmonaut\OpenTimesBundle\Resolver\OpeningHoursResolver;

/**
 * Findet Unstimmigkeiten in den Plänen eines Ortes, die zu unerwarteten Ausgaben führen können.
 *
 * Liefert Schlüssel mit Parametern; die Texte stehen in den Sprachdateien
 * ($GLOBALS['TL_LANG']['opentimes_backend']['warnings']).
 */
final class HoursDiagnostics
{
    public function __construct(private readonly OpeningHoursResolver $resolver)
    {
    }

    /**
     * @return list<array{key: string, params: list<string>}>
     */
    public function analyze(OpeningHours $hours, DateTimeImmutable $now): array
    {
        $warnings = [];

        if (!$hours->hasPlan()) {
            return [['key' => 'no_plan', 'params' => []]];
        }

        if (null === $hours->defaultSlots) {
            $warnings[] = ['key' => 'no_default', 'params' => []];
        } elseif ([] === $hours->defaultSlots) {
            $warnings[] = ['key' => 'default_empty', 'params' => []];
        }

        foreach ($hours->seasons as $season) {
            if ([] === $season->slots) {
                $warnings[] = ['key' => 'season_empty', 'params' => [$season->start, $season->stop]];
            }
        }

        // Saisons mit gleicher Priorität, die sich an einem Tag überlappen: dann gewinnt nur die zuerst angelegte
        $reported = [];
        $day = $now->setTime(0, 0);

        for ($i = 0; $i < 366; ++$i) {
            $ymd = $day->modify('+'.$i.' day')->format('Y-m-d');
            $matching = $this->resolver->getSeasonsOn($hours, $ymd);

            if (\count($matching) < 2) {
                continue;
            }

            $top = max(array_map(static fn ($s): int => $s->priority, $matching));
            $tied = array_values(array_filter($matching, static fn ($s): bool => $s->priority === $top));

            if (\count($tied) < 2) {
                continue;
            }

            foreach ($tied as $season) {
                $id = $season->start.'|'.$season->stop;

                if (isset($reported[$id])) {
                    continue 2;
                }
            }

            foreach ($tied as $season) {
                $reported[$season->start.'|'.$season->stop] = true;
            }

            $warnings[] = [
                'key' => 'season_overlap',
                'params' => array_map(static fn ($s): string => $s->start.' – '.$s->stop, $tied),
            ];
        }

        return $warnings;
    }
}
