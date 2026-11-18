<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_oh_location']['title'] = ['Name', 'Name des Ortes.'];
$GLOBALS['TL_LANG']['tl_oh_location']['schema_type'] = ['Typ (schema.org)', 'Bestimmt den Typ in den strukturierten Daten für Suchmaschinen, z. B. Museum oder Bibliothek.'];
$GLOBALS['TL_LANG']['tl_oh_location']['street'] =['Straße', 'Straße und Hausnummer.'];
$GLOBALS['TL_LANG']['tl_oh_location']['postal'] = ['Postleitzahl', ''];
$GLOBALS['TL_LANG']['tl_oh_location']['city'] = ['Ort', ''];
$GLOBALS['TL_LANG']['tl_oh_location']['holiday_region'] = ['Feiertage', 'An gesetzlichen Feiertagen dieses Bundeslandes ist geschlossen. Eine Ausnahme für denselben Tag hat Vorrang.'];
$GLOBALS['TL_LANG']['tl_oh_location']['opentimes_preview'] = ['Vorschau', ''];
$GLOBALS['TL_LANG']['tl_oh_location']['preview'] = ['Vorschau (gespeicherter Stand)', ''];

$GLOBALS['TL_LANG']['tl_oh_location']['regions'] = [
    'DE' => 'Nur bundesweite Feiertage',
    'BW' => 'Baden-Württemberg', 'BY' => 'Bayern', 'BE' => 'Berlin', 'BB' => 'Brandenburg',
    'HB' => 'Bremen', 'HH' => 'Hamburg', 'HE' => 'Hessen', 'MV' => 'Mecklenburg-Vorpommern',
    'NI' => 'Niedersachsen', 'NW' => 'Nordrhein-Westfalen', 'RP' => 'Rheinland-Pfalz', 'SL' => 'Saarland',
    'SN' => 'Sachsen', 'ST' => 'Sachsen-Anhalt', 'SH' => 'Schleswig-Holstein', 'TH' => 'Thüringen',
];

$GLOBALS['TL_LANG']['tl_oh_location']['warnings'] = [
    'no_plan' => 'Es ist noch kein Zeitplan, keine Ausnahme und keine Saison hinterlegt. Im Frontend erscheint „Öffnungszeiten auf Anfrage“.',
    'no_default' => 'Es gibt keine Standardzeiten. Außerhalb der Saisons und Ausnahmen ist der Ort „geschlossen“, ohne nächste Öffnung.',
    'default_empty' => 'Die Standardzeiten enthalten kein gültiges Zeitfenster. Der Ort ist dadurch dauerhaft geschlossen.',
    'season_empty' => 'Eine Saison (%s) hat kein Zeitfenster. In dieser Saison ist der Ort geschlossen.',
    'season_overlap' => 'Diese Saisons überlappen sich bei gleicher Priorität: %s. Es gilt die zuerst angelegte. Gib der gewünschten Saison eine höhere Priorität.',
];

$GLOBALS['TL_LANG']['tl_oh_location']['title_legend'] = 'Ort';
$GLOBALS['TL_LANG']['tl_oh_location']['address_legend'] = 'Adresse';
$GLOBALS['TL_LANG']['tl_oh_location']['holiday_legend'] = 'Feiertage';
$GLOBALS['TL_LANG']['tl_oh_location']['preview_legend'] = 'Vorschau';

$GLOBALS['TL_LANG']['tl_oh_location']['edit'] = ['Ort bearbeiten', 'Ort ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_oh_location']['schedules'] = ['Zeitpläne', 'Standard- und Saisonzeiten von Ort ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['exceptions'] = ['Ausnahmen', 'Ausnahmen von Ort ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['closures'] = ['Schließungen', 'Spontane Schließungen von Ort ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['delete'] = ['Ort löschen', 'Ort ID %s löschen'];
$GLOBALS['TL_LANG']['tl_oh_location']['show'] = ['Details', 'Details von Ort ID %s'];
