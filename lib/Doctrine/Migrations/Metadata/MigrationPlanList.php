<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Countable;
use function count;
use function end;
use function reset;

class MigrationPlanList implements Countable
{
    /** @var string */
    private $direction;

    /** @var MigrationPlan[] */
    private $items = [];

    public function __construct(array $items, string $direction)
    {
        $this->items     = $items;
        $this->direction = $direction;
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * @return MigrationPlan[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getDirection() : string
    {
        return $this->direction;
    }

    public function getFirst() : ?MigrationPlan
    {
        return reset($this->items) ?: null;
    }

    public function getLast() : ?MigrationPlan
    {
        return end($this->items) ?: null;
    }
}
