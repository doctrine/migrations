<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;

/**
 * The MigrationStatusCalculator is responsible for calculating the current status of
 * migrated and not available versions.
 */
interface MigrationStatusCalculator
{
    public function getExecutedUnavailableMigrations() : ExecutedMigrationsSet;

    public function getNewMigrations() : AvailableMigrationsList;
}
