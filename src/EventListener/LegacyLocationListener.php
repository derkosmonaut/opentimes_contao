<?php

declare(strict_types=1);

namespace Derkosmonaut\OpenTimesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

/**
 * Übernimmt den früheren Einzel-Ort (opentimes_location) in die Orte-Auswahl (opentimes_locations),
 * sobald ein bestehendes Element im Backend geöffnet wird.
 */
class LegacyLocationListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function onLoadContent(DataContainer $dc): void
    {
        $this->migrate('tl_content', (int) $dc->id);
    }

    #[AsCallback(table: 'tl_module', target: 'config.onload')]
    public function onLoadModule(DataContainer $dc): void
    {
        $this->migrate('tl_module', (int) $dc->id);
    }

    private function migrate(string $table, int $id): void
    {
        if ($id < 1) {
            return;
        }

        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT type, opentimes_location, opentimes_locations FROM %s WHERE id = ?', $table),
            [$id],
        );

        if (false === $row || 'opentimes_hours' !== $row['type'] || (int) $row['opentimes_location'] < 1) {
            return;
        }

        $locations = null === $row['opentimes_locations'] ? [] : @unserialize((string) $row['opentimes_locations'], ['allowed_classes' => false]);

        if ([] !== $locations && false !== $locations) {
            return;
        }

        $this->connection->update($table, [
            'opentimes_locations' => serialize([(string) (int) $row['opentimes_location']]),
        ], ['id' => $id]);
    }
}
