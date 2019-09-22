<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationPlanCalculator;
use Doctrine\Migrations\MigrationRepository;
use function count;
use function substr;

/**
 * The AliasResolver class is responsible for resolving aliases like first, current, etc. to the actual version number.
 *
 * @internal
 */
final class AliasResolver implements AliasResolverInterface
{
    private const ALIAS_FIRST   = 'first';
    private const ALIAS_CURRENT = 'current';
    private const ALIAS_PREV    = 'prev';
    private const ALIAS_NEXT    = 'next';
    private const ALIAS_LATEST  = 'latest';

    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var MigrationPlanCalculator */
    private $migrationPlanCalculator;

    public function __construct(MigrationRepository $migrationRepository, MetadataStorage $metadataStorage, MigrationPlanCalculator $migrationPlanCalculator)
    {
        $this->migrationRepository     = $migrationRepository;
        $this->metadataStorage         = $metadataStorage;
        $this->migrationPlanCalculator = $migrationPlanCalculator;
    }

    /**
     * Returns the version number from an alias.
     *
     * Supported aliases are:
     *
     * - first: The very first version before any migrations have been run.
     * - current: The current version.
     * - prev: The version prior to the current version.
     * - next: The version following the current version.
     * - latest: The latest available version.
     *
     * If an existing version number is specified, it is returned verbatimly.
     */
    public function resolveVersionAlias(string $alias) : Version
    {
        $availableMigrations = $this->migrationRepository->getMigrations();
        $executedMigrations  = $this->metadataStorage->getExecutedMigrations();

        switch ($alias) {
            case self::ALIAS_FIRST:
                if (count($availableMigrations) === 0) {
                    throw NoMigrationsToExecute::new();
                }

                $info = $availableMigrations->getFirst();

                if ($info !== null) {
                    return $info->getVersion();
                }
                throw UnknownMigrationVersion::new($alias);
            case self::ALIAS_CURRENT:
                $info = $executedMigrations->getLast();

                return $info !== null ? $info->getVersion() : new Version('0');
            case self::ALIAS_PREV:
                $info = $executedMigrations->getLast(-1);

                return $info !== null ? $info->getVersion() : new Version('0');
            case self::ALIAS_NEXT:
                $newMigrations = $this->migrationPlanCalculator->getNewMigrations();

                $info = $newMigrations->getFirst();
                if ($info !== null) {
                    return $info->getVersion();
                }

                throw NoMigrationsToExecute::new();
            case self::ALIAS_LATEST:
                $availableMigration = $availableMigrations->getLast();

                if ($availableMigration !== null) {
                    return $availableMigration->getVersion();
                }
                throw NoMigrationsToExecute::new();

            default:
                if ($availableMigrations->hasMigration(new Version($alias))) {
                    return $availableMigrations->getMigration(new Version($alias))->getVersion();
                }

                if (substr($alias, 0, 7) === self::ALIAS_CURRENT) {
                    $val             = (int) substr($alias, 7);
                    $targetMigration = null;
                    if ($val > 0) {
                        $newMigrations = $this->migrationPlanCalculator->getNewMigrations();

                        $targetMigration = $newMigrations->getFirst($val - 1);
                    } else {
                        $targetMigration = $executedMigrations->getLast($val);
                    }

                    if ($targetMigration !== null) {
                        return $targetMigration->getVersion();
                    }
                }
        }

        throw UnknownMigrationVersion::new($alias);
    }
}
