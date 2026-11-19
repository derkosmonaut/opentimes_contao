<?php

declare(strict_types=1);

use Derkosmonaut\OpenTimesBundle\Dca\OutputFields;

$GLOBALS['TL_DCA']['tl_content']['palettes']['opentimes_hours'] =
    '{type_legend},type,headline;'
    .OutputFields::PALETTE
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['fields'] ?? [],
    OutputFields::fields('tl_content'),
);
