<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Countable;
use Doctrine\Migrations\Exception\MigrationNotAvailable;
use Doctrine\Migrations\Version\Version;
use function array_values;
use function count;

class AvailableMigrationsList implements Countable
{
    /** @var AvailableMigration[] */
    private $items = [];

    /**
     * @param AvailableMigration[] $items
     */
    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @return AvailableMigration[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getFirst(int $offset = 0) : ?AvailableMigration
    {
        return $this->items[$offset] ?? null;
    }

    public function getLast(int $offset = 0) : ?AvailableMigration
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

    public function getMigration(Version $version) : ?AvailableMigration
    {
        foreach ($this->items as $migration) {
            if ((string) $migration->getVersion() === (string) $version) {
                return $migration;
            }
        }

        throw MigrationNotAvailable::new((string) $version);
    }
}
