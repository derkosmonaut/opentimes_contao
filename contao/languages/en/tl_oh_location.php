<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_oh_location']['title'] = ['Name', 'Name of the location.'];
$GLOBALS['TL_LANG']['tl_oh_location']['schema_type'] = ['Type (schema.org)', 'Sets the type in the structured data for search engines, e.g. Museum or Library.'];
$GLOBALS['TL_LANG']['tl_oh_location']['street'] = ['Street', 'Street and house number.'];
$GLOBALS['TL_LANG']['tl_oh_location']['postal'] = ['Postal code', ''];
$GLOBALS['TL_LANG']['tl_oh_location']['city'] = ['City', ''];
$GLOBALS['TL_LANG']['tl_oh_location']['holiday_region'] = ['Public holidays', 'The location is closed on public holidays of this German state. An exception for the same day takes precedence.'];
$GLOBALS['TL_LANG']['tl_oh_location']['opentimes_preview'] = ['Preview', ''];
$GLOBALS['TL_LANG']['tl_oh_location']['preview'] = ['Preview (saved state)', ''];

$GLOBALS['TL_LANG']['tl_oh_location']['regions'] = [
    'DE' => 'Nationwide holidays only',
    'BW' => 'Baden-Württemberg', 'BY' => 'Bavaria', 'BE' => 'Berlin', 'BB' => 'Brandenburg',
    'HB' => 'Bremen', 'HH' => 'Hamburg', 'HE' => 'Hesse', 'MV' => 'Mecklenburg-Vorpommern',
    'NI' => 'Lower Saxony', 'NW' => 'North Rhine-Westphalia', 'RP' => 'Rhineland-Palatinate', 'SL' => 'Saarland',
    'SN' => 'Saxony', 'ST' => 'Saxony-Anhalt', 'SH' => 'Schleswig-Holstein', 'TH' => 'Thuringia',
];

$GLOBALS['TL_LANG']['tl_oh_location']['warnings'] = [
    'no_plan' => 'No schedule, exception or season has been added yet. The website shows "Opening hours on request".',
    'no_default' => 'There are no regular hours. Outside seasons and exceptions the location counts as closed, without a next opening.',
    'default_empty' => 'The regular hours contain no valid time window. The location is therefore always closed.',
    'season_empty' => 'A season (%s) has no time window. The location is closed during this season.',
    'season_overlap' => 'These seasons overlap with the same priority: %s. The first one created applies. Give the intended season a higher priority.',
];

$GLOBALS['TL_LANG']['tl_oh_location']['copy_suffix'] = ' (copy)';
$GLOBALS['TL_LANG']['tl_oh_location']['title_legend'] = 'Location';
$GLOBALS['TL_LANG']['tl_oh_location']['address_legend'] = 'Address';
$GLOBALS['TL_LANG']['tl_oh_location']['holiday_legend'] = 'Public holidays';
$GLOBALS['TL_LANG']['tl_oh_location']['preview_legend'] = 'Preview';

$GLOBALS['TL_LANG']['tl_oh_location']['edit'] = ['Edit location', 'Edit location ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['copy'] = ['Duplicate location', 'Duplicate location ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['copyChilds'] = ['Duplicate location with schedules, exceptions and closures', 'Duplicate location with schedules, exceptions and closures (ID %s)'];
$GLOBALS['TL_LANG']['tl_oh_location']['schedules'] = ['Schedules', 'Regular and seasonal hours of location ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['exceptions'] = ['Exceptions', 'Exceptions of location ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['closures'] = ['Closures', 'Spontaneous closures of location ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['delete'] = ['Delete location', 'Delete location ID %s'];
$GLOBALS['TL_LANG']['tl_oh_location']['show'] = ['Details', 'Details of location ID %s'];
