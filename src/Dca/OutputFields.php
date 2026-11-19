<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\Dca;

/**
 * Felder der Ausgabe, gemeinsam für Content-Element (tl_content) und Frontend-Modul (tl_module).
 */
final class OutputFields
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fields(string $table): array
    {
        return [
            'opentimes_layout' => [
                'inputType' => 'select',
                'default' => 'full',
                'options' => ['full', 'compact'],
                'reference' => &$GLOBALS['TL_LANG'][$table]['opentimes_layouts'],
                'eval' => ['tl_class' => 'w50'],
                'sql' => "varchar(16) NOT NULL default 'full'",
            ],
            'opentimes_days' => [
                'inputType' => 'select',
                'default' => 7,
                'options' => range(1, 14),
                'eval' => ['tl_class' => 'w50'],
                'sql' => 'smallint(5) unsigned NOT NULL default 7',
            ],
            'opentimes_locations' => [
                'inputType' => 'checkbox',
                'foreignKey' => 'tl_oh_location.title',
                'eval' => ['mandatory' => true, 'multiple' => true, 'tl_class' => 'clr'],
                'sql' => 'blob NULL',
            ],
            'opentimes_week' => [
                'inputType' => 'checkbox',
                'default' => true,
                'eval' => ['tl_class' => 'w50 m12'],
                'sql' => "char(1) NOT NULL default '1'",
            ],
            'opentimes_jsonld' => [
                'inputType' => 'checkbox',
                'default' => true,
                'eval' => ['tl_class' => 'w50 m12'],
                'sql' => "char(1) NOT NULL default '1'",
            ],
            // Altfeld aus Version 0.1/0.2 (ein einzelner Ort): nur noch Spalte, wird beim Öffnen übernommen
            'opentimes_location' => [
                'sql' => 'int(10) unsigned NOT NULL default 0',
            ],
        ];
    }

    public const PALETTE = '{opentimes_legend},opentimes_locations,opentimes_layout,opentimes_days,opentimes_week,opentimes_jsonld;';
}
