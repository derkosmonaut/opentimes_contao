<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_oh_schedule']['title'] = ['Title', 'e.g. "Regular" or "Summer".'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['type'] = ['Type', 'Regular hours always apply, seasonal hours only within the given period.'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['start'] = ['Season start', 'First day of the season (inclusive).'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['stop'] = ['Season end', 'Last day of the season (inclusive).'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['recurring'] = ['Repeat yearly', 'The season applies every year in the same period.'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['priority'] = ['Priority', 'With overlapping seasons the higher number wins.'];

$GLOBALS['TL_LANG']['tl_oh_schedule']['types'] = [
    'default' => 'Regular hours',
    'season' => 'Seasonal hours',
];

$GLOBALS['TL_LANG']['tl_oh_schedule']['title_legend'] = 'Schedule';
$GLOBALS['TL_LANG']['tl_oh_schedule']['season_legend'] = 'Season';

$GLOBALS['TL_LANG']['tl_oh_schedule']['edit'] = ['Edit schedule', 'Edit schedule ID %s'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['slots'] = ['Time windows', 'Time windows of schedule ID %s'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['delete'] = ['Delete schedule', 'Delete schedule ID %s'];
$GLOBALS['TL_LANG']['tl_oh_schedule']['show'] = ['Details', 'Details of schedule ID %s'];
