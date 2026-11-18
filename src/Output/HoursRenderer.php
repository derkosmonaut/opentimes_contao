<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Output;

use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Contao\StringUtil;
use Contao\System;
use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Repository\OpeningHoursRepository;

/**
 * Befüllt das Template eines Content-Elements oder Frontend-Moduls.
 *
 * Beide lesen dieselben Felder (opentimes_*) und nutzen dieselben Twig-Bausteine.
 */
class HoursRenderer
{
    public const LAYOUTS = ['full', 'compact'];

    /** Kürzeste und längste Cache-Zeit in Sekunden. */
    private const MIN_AGE = 60;
    private const MAX_AGE = 300;

    public function __construct(
        private readonly OpeningHoursRepository $repository,
        private readonly ViewDataBuilder $viewDataBuilder,
        private readonly SchemaOrgBuilder $schemaOrgBuilder,
    ) {
    }

    /**
     * @return array{maxAge: int, tags: list<string>}|null null = nichts auszugeben
     */
    public function prepare(FragmentTemplate $template, Model $model): ?array
    {
        $row = $model->row();
        $layout = \in_array($row['opentimes_layout'] ?? '', self::LAYOUTS, true) ? (string) $row['opentimes_layout'] : 'full';
        // die frühere Ortsliste entspricht der kompakten Darstellung mit mehreren Orten
        if ('list' === ($row['opentimes_layout'] ?? '')) {
            $layout = 'compact';
        }
        $days = max(1, min(14, (int) ($row['opentimes_days'] ?? 0) ?: 7));
        $ids = $this->locationIds($row);

        $timezone = new DateTimeZone(date_default_timezone_get());
        $now = new DateTimeImmutable('now', $timezone);

        System::loadLanguageFile('default');
        System::loadLanguageFile('opentimes');

        $showJsonLd = (bool) ($row['opentimes_jsonld'] ?? false);
        $entries = [];
        $jsonLds = [];
        $maxAge = self::MAX_AGE;
        $tags = ['contao.db.tl_oh_location', 'contao.db.tl_oh_schedule', 'contao.db.tl_oh_slot', 'contao.db.tl_oh_exception', 'contao.db.tl_oh_closure'];

        foreach ($ids as $id) {
            $location = $this->repository->findLocation($id);

            if (null === $location) {
                continue;
            }

            $hours = $this->repository->loadHours($id, $timezone);
            $view = $this->viewDataBuilder->build($hours, $now, $this->getWeekdayLabels(), $days);
            $jsonLd = '';

            if ($showJsonLd) {
                // HEX-Flags verhindern, dass Inhalte das <script>-Element verlassen können
                $jsonLd = json_encode(
                    $this->schemaOrgBuilder->build($location, $hours, $now),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
                );
                $jsonLds[] = $jsonLd;
            }

            $entries[] = [
                'location' => $location,
                'status' => $view['status'],
                'has_plan' => $view['hasPlan'],
                'days' => $view['days'],
                'today' => $view['days'][0] ?? null,
                'json_ld' => $jsonLd,
            ];

            $maxAge = min($maxAge, $this->secondsUntilChange($view['status'], $now));
        }

        if ([] === $entries) {
            return null;
        }

        $template->set('labels', $GLOBALS['TL_LANG']['opentimes'] ?? []);
        $template->set('layout', $layout);
        $template->set('show_week', (bool) ($row['opentimes_week'] ?? false));
        $template->set('show_json_ld', $showJsonLd);
        $template->set('entries', $entries);
        $template->set('json_lds', $jsonLds);

        // einfache Layouts greifen direkt auf den ersten Ort zu
        foreach (['location', 'status', 'has_plan', 'days', 'today', 'json_ld'] as $key) {
            $template->set($key, $entries[0][$key]);
        }

        $tags = array_merge($tags, array_map(static fn (array $e): string => 'contao.db.tl_oh_location.'.$e['location']['id'], $entries));

        return ['maxAge' => max(self::MIN_AGE, $maxAge), 'tags' => $tags];
    }

    /**
     * Gewählte Orte; ältere Elemente kennen nur den früheren Einzel-Ort.
     *
     * @param array<string, mixed> $row
     *
     * @return list<int>
     */
    private function locationIds(array $row): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', StringUtil::deserialize($row['opentimes_locations'] ?? null, true)))));

        if ([] === $ids && (int) ($row['opentimes_location'] ?? 0) > 0) {
            return [(int) $row['opentimes_location']];
        }

        return $ids;
    }

    /**
     * Sekunden bis zur nächsten Statusänderung (Öffnen/Schließen), höchstens MAX_AGE.
     */
    private function secondsUntilChange(\Derkosmonaut\OpenTimesBundle\Resolver\Model\Status $status, DateTimeImmutable $now): int
    {
        $next = $status->isOpen() ? $status->until : $status->nextOpen;

        if (null === $next) {
            return self::MAX_AGE;
        }

        return min(self::MAX_AGE, max(0, $next->getTimestamp() - $now->getTimestamp()));
    }

    /**
     * @return array<int, string> ISO-Wochentag => Bezeichnung
     */
    private function getWeekdayLabels(): array
    {
        // Contao führt den Sonntag als Index 0
        $days = $GLOBALS['TL_LANG']['DAYS'] ?? ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        $labels = [];

        for ($weekday = 1; $weekday <= 7; ++$weekday) {
            $labels[$weekday] = (string) ($days[$weekday % 7] ?? $weekday);
        }

        return $labels;
    }
}
