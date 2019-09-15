<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\MetadataStorage;
use Doctrine\Migrations\Metadata\MigrationInfo;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanItem;
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
    /**
     * @var MigrationRepository
     */
    private $migrationRepository;

    /**
     * @var MetadataStorage
     */
    private $metadataStorage;

    public function __construct(MigrationRepository $migrationRepository, MetadataStorage $metadataStorage)
    {

        $this->migrationRepository = $migrationRepository;
        $this->metadataStorage = $metadataStorage;
    }

    public function getPlanForExactVersion(Version $version, string $direction): MigrationPlan
    {
        $migration = $this->migrationRepository->getMigration($version);

        $info = new MigrationInfo($migration->getVersion());

        $planItem = new MigrationPlanItem($info, $migration->getMigration(), $direction);

        return new MigrationPlan([$planItem], $direction);
    }

    public function getPlanUntilVersion(Version $to = null) : MigrationPlan
    {
        $availableMigrations = $this->migrationRepository->getMigrations();
        $executedMigrations = $this->metadataStorage->getExecutedMigrations();

        $toExecute = [];
        if ($to === null) {
            $direction = Direction::UP;
            foreach ($availableMigrations->getItems() as $availableMigration) {
                if ($executedMigrations->getMigration($availableMigration->getVersion())) {
                    continue;
                }

                $toExecute[] = $availableMigration;
            }
        } else {
            $direction = $to == new Version('0') || ($executedMigrations->getMigration($to) && $executedMigrations->getLast()->getVersion() != $to) ? Direction::DOWN : Direction::UP;

            foreach ($direction === Direction::UP ? $availableMigrations->getItems() : array_reverse($availableMigrations->getItems()) as $availableMigration) {
                if ($direction === Direction::UP && ! $executedMigrations->getMigration($availableMigration->getVersion())) {
                    $toExecute[] = $availableMigration;
                } elseif ($direction === Direction::DOWN && $executedMigrations->getMigration($availableMigration->getVersion()) && $availableMigration->getVersion() != $to) {
                    $toExecute[] = $availableMigration;
                }

                if ((string) $availableMigration->getVersion() == $to) {
                    break;
                }
            }
        }

        return new MigrationPlan(array_map(static function (AvailableMigration $migration) use ($direction) {
            $info = new MigrationInfo($migration->getVersion());

            return new MigrationPlanItem($info, $migration->getMigration(), $direction);
        }, $toExecute), $direction);
    }
}
