<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Countable;
use Doctrine\Migrations\Exception\MigrationNotExecuted;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Version\Version;
use function array_values;
use function count;

class ExecutedMigrationsSet implements Countable
{
    /** @var ExecutedMigration[] */
    private $items = [];

    /**
     * @param ExecutedMigration[] $items
     */
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

    public function getFirst(int $offset = 0) : ExecutedMigration
    {
        if (! isset($this->items[$offset])) {
            throw NoMigrationsFoundWithCriteria::new('first' . ($offset>0 ? ('+' . $offset) : ''));
        }

        return $this->items[$offset];
    }

    public function getLast(int $offset = 0) : ExecutedMigration
    {
        $offset = count($this->items)-1-(-1*$offset);
        if (! isset($this->items[$offset])) {
            throw NoMigrationsFoundWithCriteria::new('first' . ($offset>0 ? ('+' . $offset) : ''));
        }

        return $this->items[$offset];
    }

    public function count() : int
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

    public function getMigration(Version $version) : ExecutedMigration
    {
        foreach ($this->items as $migration) {
            if ((string) $migration->getVersion() === (string) $version) {
                return $migration;
            }
        }
        throw MigrationNotExecuted::new((string) $version);
    }
}
