<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Version\Factory;
use Doctrine\Migrations\Version\Version;
use const SORT_STRING;
use function count;
use function get_class;
use function ksort;

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

    /** @var Connection */
    private $connection;

    /** @var MigrationFinder */
    private $migrationFinder;

    /** @var Factory */
    private $versionFactory;

    /** @var AvailableMigration[] */
    private $migrations = [];

    public function __construct(
        array $migrationDirectories,
        Connection $connection,
        MigrationFinder $migrationFinder,
        Factory $versionFactory
    ) {
        $this->migrationDirectories = $migrationDirectories;
        $this->connection      = $connection;
        $this->migrationFinder = $migrationFinder;
        $this->versionFactory  = $versionFactory;
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

        ksort($this->migrations, SORT_STRING);

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

    /**
     * @return AvailableMigration[]
     */
    public function getVersions() : array
    {
        $this->loadMigrationsFromDirectories();

        return $this->migrations;
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


    public function getMigrations() : AvailableMigrationsSet
    {
        $this->loadMigrationsFromDirectories();

        return new AvailableMigrationsSet($this->migrations);
    }

//    /**
//     * @return string[]
//     */
//    public function getExecutedUnavailableMigrations() : array
//    {
//        $executedMigrations  = $this->getMigratedVersions();
//        $availableMigrations = $this->getAvailableVersions();
//
//        return array_diff($executedMigrations, $availableMigrations);
//    }



    /** @throws MigrationException */
    private function ensureMigrationClassExists(string $class) : void
    {
        if (! class_exists($class)) {
            throw MigrationClassNotFound::new(
                $class,
                $class
            );
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
