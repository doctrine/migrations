<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use function array_filter;
use function array_values;
use function count;

class ExecutedMigrationsSet
{
    /** @var MigrationInfo[] */
    private $items = [];

    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @return MigrationInfo[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getFirst(int $offset = 0) : ?MigrationInfo
    {
        return $this->items[$offset] ?? null;
    }

    public function getLast(int $offset = 0) : ?MigrationInfo
    {
        return $this->items[count($this->items)-1-(-1*$offset)] ?? null;
    }

    public function getMigration(string $version) : ?MigrationInfo
    {
        foreach ($this->items as $migration) {
            if ((string) $migration->getVersion() === $version) {
                return $migration;
            }
        }

        return null;
    }

    public function getExecutedUnavailableMigrations(AvailableMigrationsSet $availableMigrationsSet) : ExecutedMigrationsSet
    {
        return new ExecutedMigrationsSet(array_filter($this->items, static function (MigrationInfo $migrationInfo) use ($availableMigrationsSet) {
            return $availableMigrationsSet->getMigration((string) $migrationInfo->getVersion());
        }));
    }
}
