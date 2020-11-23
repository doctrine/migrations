<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;

/**
 * The MigrationStatusCalculator is responsible for calculating the current status of
 * migrated and not available versions.
 */
interface MigrationStatusCalculator
{
    public function getExecutedUnavailableMigrations(): ExecutedMigrationsList;

    public function getNewMigrations(): AvailableMigrationsList;
}
