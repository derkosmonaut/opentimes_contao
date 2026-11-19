<?php

declare(strict_types=1);

use Derkosmonaut\OpenTimesBundle\Dca\OutputFields;

$GLOBALS['TL_DCA']['tl_module']['palettes']['opentimes_hours'] =
    '{title_legend},name,type;'
    .OutputFields::PALETTE
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID';

// Darstellung wählt, welche weiteren Felder erscheinen (Subpalette je Wert)
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = OutputFields::SELECTOR;
$GLOBALS['TL_DCA']['tl_module']['subpalettes'] = array_merge(
    $GLOBALS['TL_DCA']['tl_module']['subpalettes'] ?? [],
    OutputFields::SUBPALETTES,
);

$GLOBALS['TL_DCA']['tl_module']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_module']['fields'] ?? [],
    OutputFields::fields('tl_module'),
);

// Schließungs-Hinweis fürs Seiten-Layout: leer, solange keine Schließung läuft
$GLOBALS['TL_DCA']['tl_module']['palettes']['opentimes_alert'] =
    '{title_legend},name,type;'
    .'{opentimes_legend},opentimes_alert_locations;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['opentimes_alert_locations'] = [
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_oh_location.title',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => 'blob NULL',
];
