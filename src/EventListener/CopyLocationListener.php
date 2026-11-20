<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;

/**
 * Hängt beim Duplizieren eines Ortes " (Kopie)" an den Titel, damit Original und Kopie unterscheidbar sind.
 */
class CopyLocationListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_oh_location', target: 'config.oncopy')]
    public function onCopy(int|string $insertId, DataContainer $dc): void
    {
        System::loadLanguageFile('tl_oh_location');

        $suffix = (string) ($GLOBALS['TL_LANG']['tl_oh_location']['copy_suffix'] ?? ' (Kopie)');

        $this->connection->executeStatement(
            'UPDATE tl_oh_location SET title = ? WHERE id = ?',
            [
                (string) $this->connection->fetchOne('SELECT title FROM tl_oh_location WHERE id = ?', [(int) $insertId]).$suffix,
                (int) $insertId,
            ],
        );
    }
}
