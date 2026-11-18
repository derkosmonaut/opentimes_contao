<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Derkosmonaut\OpenTimesBundle\Output\HoursRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: 'opentimes_hours', category: 'miscellaneous', template: 'frontend_module/opentimes_hours')]
class OpeningHoursModuleController extends AbstractFrontendModuleController
{
    public function __construct(private readonly HoursRenderer $renderer)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $meta = $this->renderer->prepare($template, $model);

        if (null === $meta) {
            return new Response();
        }

        $this->tagResponse($meta['tags']);

        $response = $template->getResponse();
        $response->setSharedMaxAge($meta['maxAge']);

        return $response;
    }
}
