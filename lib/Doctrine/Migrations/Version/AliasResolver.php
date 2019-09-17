<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

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

        if ($availableMigration = $availableMigrations->getMigration(new Version($alias))) {
            return $availableMigration->getVersion();
        }

        $executedMigrations = $this->metadataStorage->getExecutedMigrations();

        switch ($alias) {
            case self::ALIAS_FIRST:
                $info = $executedMigrations->getFirst();

                return $info ? $info->getVersion() : null;
            case self::ALIAS_CURRENT:
                $info = $executedMigrations->getLast();

                return $info ? $info->getVersion() : null;
            case self::ALIAS_PREV:
                $info = $executedMigrations->getLast(-1);

                return $info ? $info->getVersion() : new Version('0');
            case self::ALIAS_NEXT:
                foreach ($availableMigrations->getItems() as $availableMigration) {
                    if (! $executedMigrations->getMigration($availableMigration->getVersion())) {
                        return $availableMigration->getVersion();
                    }
                }

                return null;
            case self::ALIAS_LATEST:
                $availableMigration = $availableMigrations->getLast();

                return $availableMigration ? $availableMigration->getVersion() : null;
            default:
                if (substr($alias, 0, 7) === self::ALIAS_CURRENT) {
                    $availableMigration = $availableMigrations->getFirst((int) substr($alias, 7));

                    return $availableMigration ? $availableMigration->getVersion() : null;
                }

                return null;
        }
    }
}
