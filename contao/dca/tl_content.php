<?php

declare(strict_types=1);

use Derkosmonaut\OpenTimesBundle\Dca\OutputFields;

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'opentimes_layout';

$GLOBALS['TL_DCA']['tl_content']['palettes']['opentimes_hours'] =
    '{type_legend},type,headline;'
    .'{opentimes_legend},opentimes_layout,opentimes_jsonld;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['subpalettes'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['subpalettes'] ?? [],
    OutputFields::subpalettes(),
);

$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['fields'] ?? [],
    OutputFields::fields('tl_content'),
);
