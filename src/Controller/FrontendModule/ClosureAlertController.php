<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use DateTimeImmutable;
use DateTimeZone;
use Derkosmonaut\OpenTimesBundle\Output\AlertBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zeigt einen Hinweis, solange an einem der Orte eine Schließung läuft, sonst nichts.
 * Gedacht für das Seiten-Layout (z. B. oberhalb des Inhalts).
 */
#[AsFrontendModule(type: 'opentimes_alert', category: 'miscellaneous', template: 'frontend_module/opentimes_alert')]
class ClosureAlertController extends AbstractFrontendModuleController
{
    public function __construct(private readonly AlertBuilder $alertBuilder)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $now = new DateTimeImmutable('now', $timezone);
        $ids = array_values(array_filter(array_map('intval', StringUtil::deserialize($model->row()['opentimes_alert_locations'] ?? null, true))));

        $result = $this->alertBuilder->build($ids, $now, $timezone);

        $this->tagResponse(['contao.db.tl_oh_closure', 'contao.db.tl_oh_location']);

        if ([] === $result['alerts']) {
            // leer ausgeben, aber kurz cachen, damit der Alert zum Beginn einer Schließung erscheint
            $response = new Response('');
        } else {
            System::loadLanguageFile('opentimes');

            $template->set('labels', $GLOBALS['TL_LANG']['opentimes'] ?? []);
            $template->set('alerts', $result['alerts']);

            $response = $template->getResponse();
        }

        $response->setSharedMaxAge($result['maxAge']);

        return $response;
    }
}
