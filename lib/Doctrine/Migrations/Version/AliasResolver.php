<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationRepository;
use function substr;

/**
 * The AliasResolver class is responsible for resolving aliases like first, current, etc. to the actual version number.
 *
 * @internal
 */
final class AliasResolver
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

    public function __construct(MigrationRepository $migrationRepository, MetadataStorage $metadataStorage)
    {
        $this->migrationRepository = $migrationRepository;
        $this->metadataStorage     = $metadataStorage;
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
    public function resolveVersionAlias(string $alias) : ?Version
    {
        $availableMigrations = $this->migrationRepository->getMigrations();
        $executedMigrations = $this->metadataStorage->getExecutedMigrations();

        switch ($alias) {
            case self::ALIAS_FIRST:
                if (!count($availableMigrations)) {
                    throw NoMigrationsToExecute::new();
                }

                $info = $availableMigrations->getFirst();

                return $info ? $info->getVersion() : null;
            case self::ALIAS_CURRENT:
                $info = $executedMigrations->getLast();

                return $info ? $info->getVersion() : null;
            case self::ALIAS_PREV:
                $info = $executedMigrations->getLast(-1);

                return $info ? $info->getVersion() : new Version('0');
            case self::ALIAS_NEXT:
                if (!count($availableMigrations)) {
                    throw NoMigrationsToExecute::new();
                }

                $newMigrations = $availableMigrations->getNewMigrations($executedMigrations);

                return $newMigrations->getFirst() ? $newMigrations->getFirst()->getVersion() : null;
            case self::ALIAS_LATEST:
                if (!count($availableMigrations)) {
                    throw NoMigrationsToExecute::new();
                }
                $availableMigration = $availableMigrations->getLast();

                return $availableMigration ? $availableMigration->getVersion() : null;
            default:
                if ($availableMigrations->hasMigration(new Version($alias))) {
                    return $availableMigrations->getMigration(new Version($alias))->getVersion();
                }

                if (substr($alias, 0, 7) === self::ALIAS_CURRENT) {
                    $val             = (int) substr($alias, 7);
                    $targetMigration = null;
                    if ($val > 0) {
                        $newMigrations = $availableMigrations->getNewMigrations($executedMigrations);

                        return $newMigrations->getFirst($val-1) ? $newMigrations->getFirst($val-1)->getVersion() : null;
                    } else {
                        $targetMigration = $executedMigrations->getLast($val);
                    }

                    return $targetMigration ? $targetMigration->getVersion() : null;
                }

                return null;
        }
    }
}
