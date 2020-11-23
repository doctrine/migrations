<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Countable;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;

use function count;
use function end;
use function reset;

/**
 * Represents a sorted list of MigrationPlan instances to execute.
 */
final class MigrationPlanList implements Countable
{
    /** @var string */
    private $direction;

    /** @var MigrationPlan[] */
    private $items = [];

    /**
     * @param MigrationPlan[] $items
     */
    public function __construct(array $items, string $direction)
    {
        $this->items     = $items;
        $this->direction = $direction;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return MigrationPlan[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getFirst(): MigrationPlan
    {
        if (count($this->items) === 0) {
            throw NoMigrationsFoundWithCriteria::new('first');
        }

        return reset($this->items);
    }

    public function getLast(): MigrationPlan
    {
        if (count($this->items) === 0) {
            throw NoMigrationsFoundWithCriteria::new('last');
        }

        return end($this->items);
    }
}
