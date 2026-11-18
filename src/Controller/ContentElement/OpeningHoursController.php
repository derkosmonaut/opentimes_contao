<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Derkosmonaut\OpenTimesBundle\Output\HoursRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'opentimes_hours', category: 'texts', template: 'content_element/opentimes_hours')]
class OpeningHoursController extends AbstractContentElementController
{
    public function __construct(private readonly HoursRenderer $renderer)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $meta = $this->renderer->prepare($template, $model);

        if (null === $meta) {
            return new Response();
        }

        $this->tagResponse($meta['tags']);

        $response = $template->getResponse();
        // kurze Cache-Zeit bis zur nächsten Statusänderung, damit der Status zeitnah stimmt
        $response->setSharedMaxAge($meta['maxAge']);

        return $response;
    }
}
