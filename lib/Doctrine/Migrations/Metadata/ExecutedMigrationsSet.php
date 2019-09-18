<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Countable;
use Doctrine\Migrations\Exception\MigrationNotExecuted;
use Doctrine\Migrations\Version\Version;
use function array_filter;
use function array_values;
use function count;

class ExecutedMigrationsSet implements Countable
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

    public function hasMigration(Version $version) : bool
    {
        foreach ($this->items as $migration) {
            if ((string) $migration->getVersion() === (string) $version) {
                return true;
            }
        }
        return false;
    }

    public function getMigration(Version $version) : ?ExecutedMigration
    {
        foreach ($this->items as $migration) {
            if ((string) $migration->getVersion() === (string) $version) {
                return $migration;
            }
        }
        throw MigrationNotExecuted::new((string) $version);
    }

    public function getExecutedUnavailableMigrations(AvailableMigrationsList $availableMigrationsSet) : ExecutedMigrationsSet
    {
        return new ExecutedMigrationsSet(array_filter($this->items, static function (ExecutedMigration $migrationInfo) use ($availableMigrationsSet) {
            return ! $availableMigrationsSet->hasMigration($migrationInfo->getVersion());
        }));
    }
}
