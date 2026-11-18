<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_content']['palettes']['opentimes_hours'] =
    '{type_legend},type,headline;'
    .'{opentimes_legend},opentimes_location,opentimes_week,opentimes_jsonld;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['opentimes_location'] = [
    'inputType' => 'select',
    'foreignKey' => 'tl_oh_location.title',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
    'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['opentimes_week'] = [
    'inputType' => 'checkbox',
    'default' => true,
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['opentimes_jsonld'] = [
    'inputType' => 'checkbox',
    'default' => true,
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];
