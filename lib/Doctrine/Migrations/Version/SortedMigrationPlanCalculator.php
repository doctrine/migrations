<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Metadata;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationsRepository;

use function array_diff;
use function array_filter;
use function array_map;
use function array_reverse;
use function count;
use function in_array;
use function reset;
use function uasort;

/**
 * The MigrationPlanCalculator is responsible for calculating the plan for migrating from the current
 * version to another version.
 *
 * @internal
 */
final class SortedMigrationPlanCalculator implements MigrationPlanCalculator
{
    /** @var MigrationsRepository */
    private $migrationRepository;

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var Comparator */
    private $sorter;

    public function __construct(
        MigrationsRepository $migrationRepository,
        MetadataStorage $metadataStorage,
        Comparator $sorter
    ) {
        $this->migrationRepository = $migrationRepository;
        $this->metadataStorage     = $metadataStorage;
        $this->sorter              = $sorter;
    }

    /**
     * @param Version[] $versions
     */
    public function getPlanForVersions(array $versions, string $direction): MigrationPlanList
    {
        $migrationsToCheck   = $this->arrangeMigrationsForDirection($direction, $this->getMigrations());
        $availableMigrations = array_filter($migrationsToCheck, static function (AvailableMigration $availableMigration) use ($versions): bool {
            // in_array third parameter is intentionally false to force object to string casting
            return in_array($availableMigration->getVersion(), $versions, false);
        });

        $planItems = array_map(static function (AvailableMigration $availableMigration) use ($direction): MigrationPlan {
            return new MigrationPlan($availableMigration->getVersion(), $availableMigration->getMigration(), $direction);
        }, $availableMigrations);

        if (count($planItems) !== count($versions)) {
            $plannedVersions = array_map(static function (MigrationPlan $migrationPlan): Version {
                return $migrationPlan->getVersion();
            }, $planItems);
            $diff            = array_diff($versions, $plannedVersions);

            throw MigrationClassNotFound::new((string) reset($diff));
        }

        return new MigrationPlanList($planItems, $direction);
    }

    public function getPlanUntilVersion(Version $to): MigrationPlanList
    {
        if ((string) $to !== '0' && ! $this->migrationRepository->hasMigration((string) $to)) {
            throw MigrationClassNotFound::new((string) $to);
        }

        $availableMigrations = $this->getMigrations(); // migrations are sorted at this point
        $executedMigrations  = $this->metadataStorage->getExecutedMigrations();

        $direction = $this->findDirection($to, $executedMigrations, $availableMigrations);

        $migrationsToCheck = $this->arrangeMigrationsForDirection($direction, $availableMigrations);

        $toExecute = $this->findMigrationsToExecute($to, $migrationsToCheck, $direction, $executedMigrations);

        return new MigrationPlanList(array_map(static function (AvailableMigration $migration) use ($direction): MigrationPlan {
            return new MigrationPlan($migration->getVersion(), $migration->getMigration(), $direction);
        }, $toExecute), $direction);
    }

    public function getMigrations(): AvailableMigrationsList
    {
        $availableMigrations = $this->migrationRepository->getMigrations()->getItems();
        uasort($availableMigrations, function (AvailableMigration $a, AvailableMigration $b): int {
            return $this->sorter->compare($a->getVersion(), $b->getVersion());
        });

        return new AvailableMigrationsList($availableMigrations);
    }

    private function findDirection(Version $to, ExecutedMigrationsList $executedMigrations, AvailableMigrationsList $availableMigrations): string
    {
        if ((string) $to === '0') {
            return Direction::DOWN;
        }

        foreach ($availableMigrations->getItems() as $availableMigration) {
            if ($availableMigration->getVersion()->equals($to)) {
                break;
            }

            if (! $executedMigrations->hasMigration($availableMigration->getVersion())) {
                return Direction::UP;
            }
        }

        if ($executedMigrations->hasMigration($to) && ! $executedMigrations->getLast()->getVersion()->equals($to)) {
            return Direction::DOWN;
        }

        return Direction::UP;
    }

    /**
     * @return  AvailableMigration[]
     */
    private function arrangeMigrationsForDirection(string $direction, Metadata\AvailableMigrationsList $availableMigrations): array
    {
        return $direction === Direction::UP ? $availableMigrations->getItems() : array_reverse($availableMigrations->getItems());
    }

    /**
     * @param AvailableMigration[] $migrationsToCheck
     *
     * @return AvailableMigration[]
     */
    private function findMigrationsToExecute(Version $to, array $migrationsToCheck, string $direction, ExecutedMigrationsList $executedMigrations): array
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
}
