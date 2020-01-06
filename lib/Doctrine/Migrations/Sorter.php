<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\AvailableMigration;

/**
 * The MigrationsSorter class is responsible for sorting the migrations.
 *
 * @internal
 */
interface Sorter
{
    /** @param AvailableMigration[] $migrations */
    public function sort(array &$migrations) : void;
}
