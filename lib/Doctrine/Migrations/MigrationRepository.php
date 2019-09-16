<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Version\Factory;
use Doctrine\Migrations\Version\Version;
use function class_exists;
use function count;
use function get_class;
use function strcmp;
use function uasort;

/**
 * The MigrationRepository class is responsible for retrieving migrations, determing what the current migration
 * version, etc.
 *
 * @internal
 */
class MigrationRepository
{
    /** @var array<string, string> */
    private $migrationDirectories;

    /** @var MigrationFinder */
    private $migrationFinder;

    /** @var Factory */
    private $versionFactory;

    /** @var AvailableMigration[] */
    private $migrations = [];

    /** @var callable */
    private $sorter;

    public function __construct(
        array $migrationDirectories,
        MigrationFinder $migrationFinder,
        Factory $versionFactory,
        ?callable $sorter = null
    ) {
        $this->migrationDirectories = $migrationDirectories;
        $this->migrationFinder      = $migrationFinder;
        $this->versionFactory       = $versionFactory;
        $this->sorter               = $sorter ?: static function (AvailableMigration $m1, AvailableMigration $m2) {
            return strcmp((string) $m1->getVersion(), (string) $m2->getVersion());
        };
    }

    /** @throws MigrationException */
    public function registerMigration(string $migrationClassName) : AvailableMigration
    {
        $this->ensureMigrationClassExists($migrationClassName);

        if (isset($this->migrations[$migrationClassName])) {
            throw DuplicateMigrationVersion::new(
                $migrationClassName,
                get_class($migrationClassName)
            );
        }

        $migration = $this->versionFactory->createVersion($migrationClassName);

        $this->migrations[$migrationClassName] = new AvailableMigration(new Version($migrationClassName), $migration);

        uasort($this->migrations, $this->sorter);

        return $this->migrations[$migrationClassName];
    }

    /**
     * @param string[] $migrations
     *
     * @return AvailableMigration[]
     */
    public function registerMigrations(array $migrations) : array
    {
        $versions = [];

        foreach ($migrations as $class) {
            $versions[] = $this->registerMigration($class);
        }

        return $versions;
    }

    public function clearVersions() : void
    {
        $this->migrations = [];
    }

    public function hasVersion(string $version) : bool
    {
        $this->loadMigrationsFromDirectories();

        return isset($this->migrations[$version]);
    }

    public function getMigration(Version $version) : AvailableMigration
    {
        $this->loadMigrationsFromDirectories();

        if (! isset($this->migrations[(string) $version])) {
            throw MigrationClassNotFound::new((string) $version);
        }

        return $this->migrations[(string) $version];
    }

    public function getMigrations() : AvailableMigrationsList
    {
        $this->loadMigrationsFromDirectories();

        return new AvailableMigrationsList($this->migrations);
    }

    /** @throws MigrationException */
    private function ensureMigrationClassExists(string $class) : void
    {
        if (! class_exists($class)) {
            throw MigrationClassNotFound::new($class);
        }
    }

    private function loadMigrationsFromDirectories() : void
    {
        $migrationDirectories = $this->migrationDirectories;

        if (count($this->migrations) !== 0 || count($migrationDirectories) === 0) {
            return;
        }

        foreach ($migrationDirectories as $namespace => $path) {
                $migrations = $this->migrationFinder->findMigrations(
                    $path,
                    $namespace
                );
                $this->registerMigrations($migrations);
        }
    }
}
