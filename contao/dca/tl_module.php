<?php

declare(strict_types=1);

use Derkosmonaut\OpenTimesBundle\Dca\OutputFields;

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'opentimes_layout';

$GLOBALS['TL_DCA']['tl_module']['palettes']['opentimes_hours'] =
    '{title_legend},name,type;'
    .'{opentimes_legend},opentimes_layout,opentimes_jsonld;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['subpalettes'] = array_merge(
    $GLOBALS['TL_DCA']['tl_module']['subpalettes'] ?? [],
    OutputFields::subpalettes(),
);

$GLOBALS['TL_DCA']['tl_module']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_module']['fields'] ?? [],
    OutputFields::fields('tl_module'),
);
