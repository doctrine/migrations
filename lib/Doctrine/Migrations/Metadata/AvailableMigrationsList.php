<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Countable;
use Doctrine\Migrations\Exception\MigrationNotAvailable;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Version\Version;

use function array_filter;
use function array_values;
use function count;

/**
 * Represents a sorted list of migrations that may or maybe not be already executed.
 */
final class AvailableMigrationsList implements Countable
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
    public function getItems(): array
    {
        return $this->items;
    }

    public function getFirst(int $offset = 0): AvailableMigration
    {
        if (! isset($this->items[$offset])) {
            throw NoMigrationsFoundWithCriteria::new('first' . ($offset > 0 ? '+' . $offset : ''));
        }

        return $this->items[$offset];
    }

    public function getLast(int $offset = 0): AvailableMigration
    {
        $offset = count($this->items) - 1 - (-1 * $offset);
        if (! isset($this->items[$offset])) {
            throw NoMigrationsFoundWithCriteria::new('last' . ($offset > 0 ? '+' . $offset : ''));
        }

        return $this->items[$offset];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function hasMigration(Version $version): bool
    {
        foreach ($this->items as $migration) {
            if ($migration->getVersion()->equals($version)) {
                return true;
            }
        }

        return false;
    }

    public function getMigration(Version $version): AvailableMigration
    {
        foreach ($this->items as $migration) {
            if ($migration->getVersion()->equals($version)) {
                return $migration;
            }
        }

        throw MigrationNotAvailable::forVersion($version);
    }

    public function newSubset(ExecutedMigrationsList $executedMigrations): self
    {
        return new self(array_filter($this->getItems(), static function (AvailableMigration $migration) use ($executedMigrations): bool {
            return ! $executedMigrations->hasMigration($migration->getVersion());
        }));
    }
}
