<?php

declare(strict_types=1);

use Derkosmonaut\OpenTimesBundle\Dca\OutputFields;

// Beim Inhaltselement stehen alle Felder immer in der Maske (kein Umschalten per Selector):
// "Ort" gilt für Voll und Kompakt, "Orte" nur für die Ortsliste.
$GLOBALS['TL_DCA']['tl_content']['palettes']['opentimes_hours'] =
    '{type_legend},type,headline;'
    .'{opentimes_legend},opentimes_layout,opentimes_location,opentimes_locations,opentimes_days,opentimes_week,opentimes_jsonld;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$fields = OutputFields::fields('tl_content', false);
// kein Neuladen der Maske nötig
$fields['opentimes_layout']['eval']['submitOnChange'] = false;

$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge($GLOBALS['TL_DCA']['tl_content']['fields'] ?? [], $fields);
