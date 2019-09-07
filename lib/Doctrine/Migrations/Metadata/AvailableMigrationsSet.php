<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Doctrine\Migrations\AbstractMigration;

class AvailableMigrationsSet
{

    /** @var AvailableMigration[] */
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

    public function getFirst(int $offset = 0):?AvailableMigration
    {
        return $this->items[$offset] ?? null;
    }

    public function getLast(int $offset = 0):?AvailableMigration
    {
        return $this->items[count($this->items)-1-$offset] ?? null;
    }

    public function getMigration(string $version): ?AvailableMigration
    {
        foreach ($this->items as $migration){
            if ((string)$migration->getVersion() === $version) {
                return $migration;
            }
        }
        return null;
    }

}
