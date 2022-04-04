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
    private MigrationPlanCalculator $migrationPlanCalculator;

    private MetadataStorage $metadataStorage;

    public function __construct(
        MigrationPlanCalculator $migrationPlanCalculator,
        MetadataStorage $metadataStorage
    ) {
        $this->migrationPlanCalculator = $migrationPlanCalculator;
        $this->metadataStorage         = $metadataStorage;
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
