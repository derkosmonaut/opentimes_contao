<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Zeitfenster eines Zeitplans. Ein Fenster kann für mehrere Wochentage gelten
 * (ISO: 1 = Montag ... 7 = Sonntag). Liegt "closes" vor oder gleich "opens",
 * endet das Fenster am Folgetag (Nachtbetrieb); es gehört zum Starttag.
 */
// Schnellauswahl für die Wochentage (Mo–Fr, Wochenende, alle)
$GLOBALS['TL_JAVASCRIPT']['opentimes_backend'] = 'bundles/derkosmonautopentimes/js/backend.js';

$GLOBALS['TL_DCA']['tl_oh_slot'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_oh_schedule',
        'sql' => [
            'keys' => ['id' => 'primary', 'pid' => 'index'],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['opens'],
            'headerFields' => ['title', 'type'],
            'panelLayout' => 'limit',
        ],
        'label' => [
            'fields' => ['weekdays', 'opens', 'closes', 'opens2', 'closes2'],
            'label_callback' => static function (array $row): string {
                $names = $GLOBALS['TL_LANG']['tl_oh_slot']['weekday_names'] ?? [];
                $days = array_map(
                    static fn ($day): string => mb_substr((string) ($names[(int) $day] ?? $day), 0, 2),
                    (array) Contao\StringUtil::deserialize($row['weekdays'], true),
                );
                $times = [$row['opens'].' – '.$row['closes']];

                if ('' !== (string) $row['opens2'] && '' !== (string) $row['closes2']) {
                    $times[] = $row['opens2'].' – '.$row['closes2'];
                }

                return implode(', ', $days).': '.implode(' und ', $times);
            },
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
            'copy' => ['href' => 'act=copy', 'icon' => 'copy.svg'],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => [
        'default' => '{slot_legend},weekdays,opens,closes,opens2,closes2',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_oh_schedule.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'weekdays' => [
            'inputType' => 'checkbox',
            'options' => [1, 2, 3, 4, 5, 6, 7],
            'reference' => &$GLOBALS['TL_LANG']['tl_oh_slot']['weekday_names'],
            'eval' => ['multiple' => true, 'mandatory' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        // Uhrzeiten als "HH:MM", geprüft und vereinheitlicht durch TimeInputCallback
        'opens' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 5, 'placeholder' => '09:00', 'tl_class' => 'w50'],
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
            'eval' => ['maxlength' => 5, 'placeholder' => '14:00', 'tl_class' => 'w50'],
            'save_callback' => [['Derkosmonaut\\OpenTimesBundle\\Dca\\TimeInputCallback', 'validateOpens']],
            'sql' => "varchar(5) NOT NULL default ''",
        ],
        'closes2' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 5, 'placeholder' => '18:00', 'tl_class' => 'w50'],
            'save_callback' => [['Derkosmonaut\\OpenTimesBundle\\Dca\\TimeInputCallback', 'validateCloses']],
            'sql' => "varchar(5) NOT NULL default ''",
        ],
    ],
];
