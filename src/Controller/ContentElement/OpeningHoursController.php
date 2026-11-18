<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Output\SchemaOrgBuilder;
use Derkosmonaut\OpenTimesBundle\Output\ViewDataBuilder;
use Derkosmonaut\OpenTimesBundle\Repository\OpeningHoursRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'opentimes_hours', category: 'texts', template: 'content_element/opentimes_hours')]
class OpeningHoursController extends AbstractContentElementController
{
    /** Kurze Cache-Zeit, damit spontane Schließungen und der Status zeitnah sichtbar werden. */
    private const SHARED_MAX_AGE = 60;

    public function __construct(
        private readonly OpeningHoursRepository $repository,
        private readonly ViewDataBuilder $viewDataBuilder,
        private readonly SchemaOrgBuilder $schemaOrgBuilder,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $location = $this->repository->findLocation((int) $model->opentimes_location);

        if (null === $location) {
            return new Response();
        }

        $timezone = new DateTimeZone(date_default_timezone_get());
        $now = new DateTimeImmutable('now', $timezone);
        $hours = $this->repository->loadHours($location['id'], $timezone);

        $view = $this->viewDataBuilder->build($hours, $now, $this->getWeekdayLabels());

        $template->set('location', $location);
        $template->set('status', $view['status']);
        $template->set('has_plan', $view['hasPlan']);
        $template->set('days', $view['days']);
        $template->set('show_week', (bool) $model->opentimes_week);
        $template->set('show_json_ld', (bool) $model->opentimes_jsonld);

        $jsonLd = '';

        if ($model->opentimes_jsonld) {
            // HEX-Flags verhindern, dass Inhalte das <script>-Element verlassen können
            $jsonLd = json_encode(
                $this->schemaOrgBuilder->build($location, $hours, $now),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
            );
        }

        $template->set('json_ld', $jsonLd);

        $response = $template->getResponse();
        $response->setSharedMaxAge(self::SHARED_MAX_AGE);

        return $response;
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
