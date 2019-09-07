<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

class MigrationPlan
{
    /** @var string */
    private $direction;

    /** @var MigrationPlanItem[] */
    private $items = [];

    public function __construct(array $items, string $direction)
    {
        $this->items     = $items;
        $this->direction = $direction;
    }

    /**
     * @return MigrationPlanItem[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getDirection() : string
    {
        return $this->direction;
    }

    public function getFirst():?MigrationPlanItem
    {
        return reset($this->items) ?: null;
    }

    public function getLast():?MigrationPlanItem
    {
        return end($this->items) ?: null;
    }
}
