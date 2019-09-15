<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\MigrationInfo;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanItem;
use Doctrine\Migrations\Version\Direction;
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
    public function getMigrationsToExecute(AvailableMigrationsSet $availableMigrations, ExecutedMigrationsSet $executedMigrations, ?string $to) : MigrationPlan
    {
        $toExecute = [];
        if ($to === null) {
            $direction = Direction::UP;
            foreach ($availableMigrations->getItems() as $availableMigration) {
                if ($executedMigrations->getMigration((string) $availableMigration->getVersion())) {
                    continue;
                }

                $toExecute[] = $availableMigration;
            }
        } else {
            $direction = $to === '0' || ($executedMigrations->getMigration($to) && (string) $executedMigrations->getLast()->getVersion() !== $to) ? Direction::DOWN : Direction::UP;

            foreach ($direction === Direction::UP ? $availableMigrations->getItems() : array_reverse($availableMigrations->getItems()) as $availableMigration) {
                if ($direction === Direction::UP && ! $executedMigrations->getMigration((string) $availableMigration->getVersion())) {
                    $toExecute[] = $availableMigration;
                } elseif ($direction === Direction::DOWN && $executedMigrations->getMigration((string) $availableMigration->getVersion()) && (string) $availableMigration->getVersion() !== $to) {
                    $toExecute[] = $availableMigration;
                }

                if ((string) $availableMigration->getVersion() === $to) {
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
