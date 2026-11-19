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

// Darstellung wählt, welche weiteren Felder erscheinen (Subpalette je Wert)
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = OutputFields::SELECTOR;
$GLOBALS['TL_DCA']['tl_content']['subpalettes'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['subpalettes'] ?? [],
    OutputFields::SUBPALETTES,
);

$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['fields'] ?? [],
    OutputFields::fields('tl_content'),
);
