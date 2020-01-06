<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\MigrationRepository;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Sorter;
use function strcmp;
use function uasort;

class CustomMigrationsSorter implements Sorter
{
    /** @param AvailableMigration[] $migrations */
    public function sort(array &$migrations) : void
    {
        uasort($migrations, static function (AvailableMigration $m1, AvailableMigration $m2) {
            return strcmp((string) $m1->getVersion(), (string) $m2->getVersion())*-1;
        });
    }
}
