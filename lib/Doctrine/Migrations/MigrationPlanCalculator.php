<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use function array_filter;
use function array_map;
use function array_reverse;

/**
 * The MigrationPlanCalculator is responsible for calculating the plan for migrating from the current
 * version to another version.
 *
 * @internal
 */
final class MigrationPlanCalculator
{
    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var MetadataStorage */
    private $metadataStorage;

    public function __construct(MigrationRepository $migrationRepository, MetadataStorage $metadataStorage)
    {
        $this->migrationRepository = $migrationRepository;
        $this->metadataStorage     = $metadataStorage;
    }

    public function getPlanForExactVersion(Version $version, string $direction) : MigrationPlanList
    {
        $migration = $this->migrationRepository->getMigration($version);

        $planItem = new MigrationPlan($migration->getVersion(), $migration->getMigration(), $direction);

        return new MigrationPlanList([$planItem], $direction);
    }

    public function getPlanUntilVersion(?Version $to = null) : MigrationPlanList
    {
        $availableMigrations = $this->migrationRepository->getMigrations();
        $executedMigrations  = $this->metadataStorage->getExecutedMigrations();

        try {
            $to = $to ?: $availableMigrations->getLast()->getVersion();
        } catch (NoMigrationsFoundWithCriteria $e) {
            throw NoMigrationsToExecute::new($e);
        }

        $direction = $this->findDirection($to, $executedMigrations);

        $migrationsToCheck = $this->arrangeMigrationsForDirection($direction, $availableMigrations);

        $toExecute = $this->findMigrationsToExecute($to, $migrationsToCheck, $direction, $executedMigrations);

        return new MigrationPlanList(array_map(static function (AvailableMigration $migration) use ($direction) {
            return new MigrationPlan($migration->getVersion(), $migration->getMigration(), $direction);
        }, $toExecute), $direction);
    }

    private function findDirection(Version $to, Metadata\ExecutedMigrationsSet $executedMigrations) : string
    {
        if ((string) $to === '0' || ($executedMigrations->hasMigration($to) && ! $executedMigrations->getLast()->getVersion()->equals($to))) {
            return Direction::DOWN;
        }

        return Direction::UP;
    }

    /**
     * @return  AvailableMigration[]
     */
    private function arrangeMigrationsForDirection(string $direction, Metadata\AvailableMigrationsList $availableMigrations) : array
    {
        return $direction === Direction::UP ? $availableMigrations->getItems() : array_reverse($availableMigrations->getItems());
    }

    /**
     * @param AvailableMigration[] $migrationsToCheck
     *
     * @return AvailableMigration[]
     */
    private function findMigrationsToExecute(Version $to, array $migrationsToCheck, string $direction, Metadata\ExecutedMigrationsSet $executedMigrations) : array
    {
        $toExecute = [];
        foreach ($migrationsToCheck as $availableMigration) {
            if ($direction === Direction::DOWN && $availableMigration->getVersion()->equals($to)) {
                break;
            }

            if ($direction === Direction::UP && ! $executedMigrations->hasMigration($availableMigration->getVersion())) {
                $toExecute[] = $availableMigration;
            } elseif ($direction === Direction::DOWN && $executedMigrations->hasMigration($availableMigration->getVersion())) {
                $toExecute[] = $availableMigration;
            }

            if ($direction === Direction::UP && $availableMigration->getVersion()->equals($to)) {
                break;
            }
        }

        return $toExecute;
    }

    public function getExecutedUnavailableMigrations() : ExecutedMigrationsSet
    {
        $executedMigrationsSet  = $this->metadataStorage->getExecutedMigrations();
        $availableMigrationsSet = $this->migrationRepository->getMigrations();

        return new ExecutedMigrationsSet(array_filter($executedMigrationsSet->getItems(), static function (ExecutedMigration $migrationInfo) use ($availableMigrationsSet) {
            return ! $availableMigrationsSet->hasMigration($migrationInfo->getVersion());
        }));
    }

    public function getNewMigrations() : AvailableMigrationsList
    {
        $executedMigrationsSet  = $this->metadataStorage->getExecutedMigrations();
        $availableMigrationsSet = $this->migrationRepository->getMigrations();

        return new AvailableMigrationsList(array_filter($availableMigrationsSet->getItems(), static function (AvailableMigration $migrationInfo) use ($executedMigrationsSet) {
            return ! $executedMigrationsSet->hasMigration($migrationInfo->getVersion());
        }));
    }
}
