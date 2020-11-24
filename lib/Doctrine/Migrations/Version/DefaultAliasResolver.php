<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;

use function substr;

/**
 * The DefaultAliasResolver class is responsible for resolving aliases like first, current, etc. to the actual version number.
 *
 * @internal
 */
final class DefaultAliasResolver implements AliasResolver
{
    private const ALIAS_FIRST   = 'first';
    private const ALIAS_CURRENT = 'current';
    private const ALIAS_PREV    = 'prev';
    private const ALIAS_NEXT    = 'next';
    private const ALIAS_LATEST  = 'latest';

    /** @var MigrationPlanCalculator */
    private $migrationPlanCalculator;

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var MigrationStatusCalculator */
    private $migrationStatusCalculator;

    public function __construct(
        MigrationPlanCalculator $migrationPlanCalculator,
        MetadataStorage $metadataStorage,
        MigrationStatusCalculator $migrationStatusCalculator
    ) {
        $this->migrationPlanCalculator   = $migrationPlanCalculator;
        $this->metadataStorage           = $metadataStorage;
        $this->migrationStatusCalculator = $migrationStatusCalculator;
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
     *
     * @throws NoMigrationsToExecute
     * @throws UnknownMigrationVersion
     * @throws NoMigrationsFoundWithCriteria
     */
    public function resolveVersionAlias(string $alias): Version
    {
        $availableMigrations = $this->migrationPlanCalculator->getMigrations();
        $executedMigrations  = $this->metadataStorage->getExecutedMigrations();

        switch ($alias) {
            case self::ALIAS_FIRST:
            case '0':
                return new Version('0');

            case self::ALIAS_CURRENT:
                try {
                    return $executedMigrations->getLast()->getVersion();
                } catch (NoMigrationsFoundWithCriteria $e) {
                    return new Version('0');
                }

                // no break because of return
            case self::ALIAS_PREV:
                try {
                    return $executedMigrations->getLast(-1)->getVersion();
                } catch (NoMigrationsFoundWithCriteria $e) {
                    return new Version('0');
                }

                // no break because of return
            case self::ALIAS_NEXT:
                $newMigrations = $this->migrationStatusCalculator->getNewMigrations();

                try {
                    return $newMigrations->getFirst()->getVersion();
                } catch (NoMigrationsFoundWithCriteria $e) {
                    throw NoMigrationsToExecute::new($e);
                }

                // no break because of return
            case self::ALIAS_LATEST:
                try {
                    return $availableMigrations->getLast()->getVersion();
                } catch (NoMigrationsFoundWithCriteria $e) {
                    return $this->resolveVersionAlias(self::ALIAS_CURRENT);
                }

                // no break because of return
            default:
                if ($availableMigrations->hasMigration(new Version($alias))) {
                    return $availableMigrations->getMigration(new Version($alias))->getVersion();
                }

                if (substr($alias, 0, 7) === self::ALIAS_CURRENT) {
                    $val             = (int) substr($alias, 7);
                    $targetMigration = null;
                    if ($val > 0) {
                        $newMigrations = $this->migrationStatusCalculator->getNewMigrations();

                        return $newMigrations->getFirst($val - 1)->getVersion();
                    }

                    return $executedMigrations->getLast($val)->getVersion();
                }
        }

        throw UnknownMigrationVersion::new($alias);
    }
}
