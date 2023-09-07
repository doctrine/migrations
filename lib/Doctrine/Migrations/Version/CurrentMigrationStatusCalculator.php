<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;

/**
 * The MigrationPlanCalculator is responsible for calculating the plan for migrating from the current
 * version to another version.
 */
final class CurrentMigrationStatusCalculator implements MigrationStatusCalculator
{
    public function __construct(
        private readonly MigrationPlanCalculator $migrationPlanCalculator,
        private readonly MetadataStorage $metadataStorage,
    ) {
    }

    public function getExecutedUnavailableMigrations(): ExecutedMigrationsList
    {
        $executedMigrations = $this->metadataStorage->getExecutedMigrations();
        $availableMigration = $this->migrationPlanCalculator->getMigrations();

        return $executedMigrations->unavailableSubset($availableMigration);
    }

    public function getNewMigrations(): AvailableMigrationsList
    {
        $executedMigrations = $this->metadataStorage->getExecutedMigrations();
        $availableMigration = $this->migrationPlanCalculator->getMigrations();

        return $availableMigration->newSubset($executedMigrations);
    }
}
