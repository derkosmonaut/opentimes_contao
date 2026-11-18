<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Ausnahme für ein Datum oder einen Datumsbereich. Sie ersetzt den ganzen Tag:
 * entweder "geschlossen" oder abweichende Zeiten (bis zu zwei Fenster).
 */
$GLOBALS['TL_DCA']['tl_oh_exception'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_oh_location',
        'sql' => [
            'keys' => ['id' => 'primary', 'pid' => 'index'],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['start'],
            'headerFields' => ['title', 'city'],
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'start'],
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
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => [
        '__selector__' => ['type'],
        'default' => '{title_legend},title;{date_legend},start,stop,recurring;{hours_legend},type',
        'closed' => '{title_legend},title;{date_legend},start,stop,recurring;{hours_legend},type',
        'hours' => '{title_legend},title;{date_legend},start,stop,recurring;{hours_legend},type,opens,closes,opens2,closes2',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_oh_location.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
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
        'start' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'mandatory' => true, 'tl_class' => 'w50 wizard'],
            'sql' => 'int(10) unsigned NULL',
        ],
        // leer = nur ein einzelner Tag
        'stop' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => 'int(10) unsigned NULL',
        ],
        'recurring' => [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'type' => [
            'inputType' => 'select',
            'options' => ['closed', 'hours'],
            'reference' => &$GLOBALS['TL_LANG']['tl_oh_exception']['types'],
            'filter' => true,
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'closed'",
        ],
        'opens' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 5, 'placeholder' => '09:00', 'tl_class' => 'w50 clr'],
            'save_callback' => [['Derkosmonaut\\OpenTimesBundle\\Dca\\TimeInputCallback', 'validateOpens']],
            'sql' => "varchar(5) NOT NULL default ''",
        ],
        'closes' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 5, 'placeholder' => '09:00', 'tl_class' => 'w50'],
            'save_callback' => [['Derkosmonaut\\OpenTimesBundle\\Dca\\TimeInputCallback', 'validateCloses']],
            'sql' => "varchar(5) NOT NULL default ''",
        ],
        // optionales zweites Fenster (z. B. nach einer Mittagspause)
        'opens2' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 5, 'placeholder' => '09:00', 'tl_class' => 'w50'],
            'save_callback' => [['Derkosmonaut\\OpenTimesBundle\\Dca\\TimeInputCallback', 'validateOpens']],
            'sql' => "varchar(5) NOT NULL default ''",
        ],
        'closes2' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 5, 'placeholder' => '09:00', 'tl_class' => 'w50'],
            'save_callback' => [['Derkosmonaut\\OpenTimesBundle\\Dca\\TimeInputCallback', 'validateCloses']],
            'sql' => "varchar(5) NOT NULL default ''",
        ],
    ],
];
