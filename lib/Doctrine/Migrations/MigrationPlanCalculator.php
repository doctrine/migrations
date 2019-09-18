<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
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

        $toExecute = [];
        if ($to === null) {
            $direction = Direction::UP;
            foreach ($availableMigrations->getItems() as $availableMigration) {
                if ($executedMigrations->hasMigration($availableMigration->getVersion())) {
                    continue;
                }

                $toExecute[] = $availableMigration;
            }
        } else {
            $direction = $to == new Version('0') || ($executedMigrations->hasMigration($to) && $executedMigrations->getLast()->getVersion() !== $to) ? Direction::DOWN : Direction::UP;

            foreach ($direction === Direction::UP ? $availableMigrations->getItems() : array_reverse($availableMigrations->getItems()) as $availableMigration) {

                if ($direction === Direction::DOWN && $availableMigration->getVersion() == $to) {
                    break;
                }

                if ($direction === Direction::UP && ! $executedMigrations->hasMigration($availableMigration->getVersion())) {
                    $toExecute[] = $availableMigration;
                } elseif ($direction === Direction::DOWN && $executedMigrations->hasMigration($availableMigration->getVersion())) {
                    $toExecute[] = $availableMigration;
                }

                if ($direction === Direction::UP && $availableMigration->getVersion() == $to) {
                    break;
                }
            }
        }

        return new MigrationPlanList(array_map(static function (AvailableMigration $migration) use ($direction) {
            return new MigrationPlan($migration->getVersion(), $migration->getMigration(), $direction);
        }, $toExecute), $direction);
    }
}
