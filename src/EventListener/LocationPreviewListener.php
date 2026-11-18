<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Diagnostics\HoursDiagnostics;
use Derkosmonaut\OpenTimesBundle\Output\ViewDataBuilder;
use Derkosmonaut\OpenTimesBundle\Repository\OpeningHoursRepository;
use Derkosmonaut\OpenTimesBundle\Resolver\Model\Status;

/**
 * Vorschau im Backend: aktueller Status, die nächsten sieben Tage und Hinweise auf Unstimmigkeiten.
 * So lässt sich nach dem Speichern sofort prüfen, was im Frontend ausgegeben wird.
 */
#[AsCallback(table: 'tl_oh_location', target: 'fields.opentimes_preview.input_field_callback')]
class LocationPreviewListener
{
    public function __construct(
        private readonly OpeningHoursRepository $repository,
        private readonly ViewDataBuilder $viewDataBuilder,
        private readonly HoursDiagnostics $diagnostics,
    ) {
    }

    public function __invoke(DataContainer $dc): string
    {
        $id = (int) $dc->id;

        if ($id < 1 || null === $this->repository->findLocation($id)) {
            return '';
        }

        System::loadLanguageFile('default');
        System::loadLanguageFile('opentimes');

        $lang = $GLOBALS['TL_LANG']['opentimes'] ?? [];
        $own = $GLOBALS['TL_LANG']['tl_oh_location'] ?? [];

        $timezone = new DateTimeZone(date_default_timezone_get());
        $now = new DateTimeImmutable('now', $timezone);
        $hours = $this->repository->loadHours($id, $timezone);

        $days = $GLOBALS['TL_LANG']['DAYS'] ?? [];
        $labels = [];

        for ($weekday = 1; $weekday <= 7; ++$weekday) {
            $labels[$weekday] = (string) ($days[$weekday % 7] ?? $weekday);
        }

        $view = $this->viewDataBuilder->build($hours, $now, $labels, 7);
        $html = '<div class="widget clr"><h3>'.$this->e($own['preview'][0] ?? 'Vorschau').'</h3>';
        $html .= '<p><strong>'.$this->e($this->statusText($view['status'], $lang)).'</strong></p>';

        if ($view['hasPlan']) {
            $html .= '<table class="tl_listing" style="width:auto"><tbody>';

            foreach ($view['days'] as $day) {
                $times = [];

                foreach ($day['windows'] as $window) {
                    $times[] = $window['opens'].' – '.$window['closes'];
                }

                $html .= \sprintf(
                    '<tr><td style="padding-right:1.5em">%s %s</td><td>%s</td></tr>',
                    $this->e($day['label']),
                    $this->e($day['date']->format('d.m.')),
                    $this->e([] === $times ? (string) ($lang['closed'] ?? 'Geschlossen') : implode(', ', $times)),
                );
            }

            $html .= '</tbody></table>';
        }

        foreach ($this->diagnostics->analyze($hours, $now) as $warning) {
            $text = $own['warnings'][$warning['key']] ?? $warning['key'];
            $html .= '<p class="tl_info">'.$this->e(\sprintf($text, ...[implode(', ', $warning['params'])])).'</p>';
        }

        return $html.'</div>';
    }

    /**
     * @param array<string, mixed> $lang
     */
    private function statusText(Status $status, array $lang): string
    {
        if ($status->isOpen()) {
            return \sprintf('%s %s %s', $lang['status']['open_until'] ?? '', $status->until?->format('H:i'), $lang['time_suffix'] ?? '');
        }

        if (Status::CLOSED === $status->state) {
            $text = (string) ($lang['status']['closed'] ?? 'Zurzeit geschlossen');

            if (null !== $status->nextOpen) {
                $text .= ' – '.($lang['status']['opens_again'] ?? '').' '.$status->nextOpen->format('d.m.Y, H:i').' '.($lang['time_suffix'] ?? '');
            }

            return $text;
        }

        return (string) ($lang['status']['unknown'] ?? '');
    }

    private function e(string $value): string
    {
        return StringUtil::specialchars($value);
    }
}
