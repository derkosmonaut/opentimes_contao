<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_oh_location'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_oh_schedule', 'tl_oh_exception', 'tl_oh_closure'],
        'sql' => [
            'keys' => ['id' => 'primary'],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'city'],
            'format' => '%s <span class="tl_gray">%s</span>',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => ['href' => 'act=edit', 'icon' => 'edit.svg'],
            'schedules' => ['href' => 'table=tl_oh_schedule', 'icon' => 'children.svg'],
            'exceptions' => ['href' => 'table=tl_oh_exception', 'icon' => 'bundles/derkosmonautopentimes/icons/calendar-day.svg'],
            'closures' => ['href' => 'table=tl_oh_closure', 'icon' => 'bundles/derkosmonautopentimes/icons/bell.svg'],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title,schema_type;{address_legend},street,postal,city;{holiday_legend},holiday_region;{preview_legend},opentimes_preview',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'title' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        // schema.org-Typ für die strukturierten Daten
        'schema_type' => [
            'inputType' => 'select',
            'default' => 'Place',
            'options' => ['Place', 'LocalBusiness', 'Museum', 'Library', 'Store', 'Restaurant', 'CafeOrCoffeeShop', 'GovernmentOffice', 'CivicStructure'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default 'Place'",
        ],
        'street' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'postal' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 32, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'city' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        // Feiertage dieses Bundeslandes gelten als geschlossen, sofern keine Ausnahme etwas anderes festlegt
        'holiday_region' => [
            'inputType' => 'select',
            'options' => ['DE', 'BW', 'BY', 'BE', 'BB', 'HB', 'HH', 'HE', 'MV', 'NI', 'NW', 'RP', 'SL', 'SN', 'ST', 'SH', 'TH'],
            'reference' => &$GLOBALS['TL_LANG']['tl_oh_location']['regions'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default ''",
        ],
        // nur Anzeige, wird über LocationPreviewListener gefüllt
        'opentimes_preview' => [
            'eval' => ['tl_class' => 'clr'],
        ],
    ],
];
