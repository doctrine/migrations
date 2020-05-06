<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Countable;
use Doctrine\Migrations\Exception\MigrationNotAvailable;
use Doctrine\Migrations\Version\Version;
use function array_values;
use function count;

/**
 * Represents a non sorted list of migrations that may or may not be already executed.
 */
final class AvailableMigrationsSet implements Countable
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

    public function count() : int
    {
        return count($this->items);
    }

    public function hasMigration(Version $version) : bool
    {
        foreach ($this->items as $migration) {
            if ($migration->getVersion()->equals($version)) {
                return true;
            }
        }

        return false;
    }

    public function getMigration(Version $version) : AvailableMigration
    {
        foreach ($this->items as $migration) {
            if ($migration->getVersion()->equals($version)) {
                return $migration;
            }
        }

        throw MigrationNotAvailable::forVersion($version);
    }
}
