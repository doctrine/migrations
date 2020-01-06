<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\AvailableMigration;
use function strcmp;
use function uasort;

/**
 * The MigrationsSorter class is responsible for sorting the migrations.
 *
 * @internal
 */
final class MigrationsSorter implements Sorter
{
    /** @var callable */
    private $sortFunc;

    public function __construct(?callable $sortFunc = null)
    {
        $this->sortFunc = $sortFunc ?: static function (AvailableMigration $m1, AvailableMigration $m2) {
            return strcmp((string) $m1->getVersion(), (string) $m2->getVersion());
        };
    }

    /** @param AvailableMigration[] $migrations */
    public function sort(array &$migrations) : void
    {
        uasort($migrations, $this->sortFunc);
    }
}
