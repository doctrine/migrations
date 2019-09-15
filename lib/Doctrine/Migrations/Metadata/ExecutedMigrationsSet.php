<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Doctrine\Migrations\Version\Version;
use function array_filter;
use function array_values;
use function count;

class ExecutedMigrationsSet implements \Countable
{
    /** @var ExecutedMigration[] */
    private $items = [];

    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @return ExecutedMigration[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getFirst(int $offset = 0) : ?ExecutedMigration
    {
        return $this->items[$offset] ?? null;
    }

    public function getLast(int $offset = 0) : ?ExecutedMigration
    {
        return $this->items[count($this->items)-1-(-1*$offset)] ?? null;
    }

    public function count()
    {
        return count($this->items);
    }

    public function getMigration(Version $version) : ?ExecutedMigration
    {
        foreach ($this->items as $migration) {
            if ((string)$migration->getVersion() == (string)$version) {
                return $migration;
            }
        }

        return null;
    }

    public function getExecutedUnavailableMigrations(AvailableMigrationsList $availableMigrationsSet) : ExecutedMigrationsSet
    {
        return new ExecutedMigrationsSet(array_filter($this->items, static function (ExecutedMigration $migrationInfo) use ($availableMigrationsSet) {
            return !$availableMigrationsSet->getMigration($migrationInfo->getVersion());
        }));
    }
}
