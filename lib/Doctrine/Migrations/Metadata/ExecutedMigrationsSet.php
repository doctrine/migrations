<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

class ExecutedMigrationsSet
{

    /** @var MigrationInfo[] */
    private $items = [];

    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @return MigrationPlanItem[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getFirst(int $offset = 0):?MigrationInfo
    {
        return $this->items[$offset] ?? null;
    }

    public function getLast(int $offset = 0):?MigrationInfo
    {
        return $this->items[count($this->items)-1-(-1*$offset)] ?? null;
    }

    public function getMigration(string $version): ?MigrationInfo
    {
        foreach ($this->items as $migration) {
            if ((string)$migration->getVersion() === $version) {
                return $migration;
            }
        }
        return null;
    }

}
