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
                'options' => ['full', 'compact', 'list'],
                'reference' => &$GLOBALS['TL_LANG'][$table]['opentimes_layouts'],
                'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
                'sql' => "varchar(16) NOT NULL default 'full'",
            ],
            'opentimes_location' => [
                'inputType' => 'select',
                'foreignKey' => 'tl_oh_location.title',
                'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
                'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
                'sql' => 'int(10) unsigned NOT NULL default 0',
            ],
            'opentimes_locations' => [
                'inputType' => 'checkbox',
                'foreignKey' => 'tl_oh_location.title',
                'eval' => ['mandatory' => true, 'multiple' => true, 'tl_class' => 'clr'],
                'sql' => 'blob NULL',
            ],
            'opentimes_days' => [
                'inputType' => 'select',
                'default' => 7,
                'options' => range(1, 14),
                'eval' => ['tl_class' => 'w50'],
                'sql' => 'smallint(5) unsigned NOT NULL default 7',
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function subpalettes(): array
    {
        return [
            'opentimes_layout_full' => 'opentimes_location,opentimes_days,opentimes_week',
            'opentimes_layout_compact' => 'opentimes_location',
            'opentimes_layout_list' => 'opentimes_locations',
        ];
    }
}
