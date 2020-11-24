<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;

use function class_exists;

/**
 * The FilesystemMigrationsRepository class is responsible for retrieving migrations, determining what the current migration
 * version, etc.
 *
 * @internal
 */
class FilesystemMigrationsRepository implements MigrationsRepository
{
    /** @var bool */
    private $migrationsLoaded = false;

    /** @var array<string, string> */
    private $migrationDirectories;

    /** @var MigrationFinder */
    private $migrationFinder;

    /** @var MigrationFactory */
    private $versionFactory;

    /** @var AvailableMigration[] */
    private $migrations = [];

    /**
     * @param string[]              $classes
     * @param array<string, string> $migrationDirectories
     */
    public function __construct(
        array $classes,
        array $migrationDirectories,
        MigrationFinder $migrationFinder,
        MigrationFactory $versionFactory
    ) {
        $this->migrationDirectories = $migrationDirectories;
        $this->migrationFinder      = $migrationFinder;
        $this->versionFactory       = $versionFactory;

        $this->registerMigrations($classes);
    }

    private function registerMigrationInstance(Version $version, AbstractMigration $migration): AvailableMigration
    {
        if (isset($this->migrations[(string) $version])) {
            throw DuplicateMigrationVersion::new(
                (string) $version,
                (string) $version
            );
        }

        $this->migrations[(string) $version] = new AvailableMigration($version, $migration);

        return $this->migrations[(string) $version];
    }

    /** @throws MigrationException */
    public function registerMigration(string $migrationClassName): AvailableMigration
    {
        $this->ensureMigrationClassExists($migrationClassName);

        $version   = new Version($migrationClassName);
        $migration = $this->versionFactory->createVersion($migrationClassName);

        return $this->registerMigrationInstance($version, $migration);
    }

    /**
     * @param string[] $migrations
     *
     * @return AvailableMigration[]
     */
    private function registerMigrations(array $migrations): array
    {
        $versions = [];

        foreach ($migrations as $class) {
            $versions[] = $this->registerMigration($class);
        }

        return $versions;
    }

    public function hasMigration(string $version): bool
    {
        $this->loadMigrationsFromDirectories();

        return isset($this->migrations[$version]);
    }

    public function getMigration(Version $version): AvailableMigration
    {
        $this->loadMigrationsFromDirectories();

        if (! isset($this->migrations[(string) $version])) {
            throw MigrationClassNotFound::new((string) $version);
        }

        return $this->migrations[(string) $version];
    }

    /**
     * Returns a non-sorted set of migrations.
     */
    public function getMigrations(): AvailableMigrationsSet
    {
        $this->loadMigrationsFromDirectories();

        return new AvailableMigrationsSet($this->migrations);
    }

    /** @throws MigrationException */
    private function ensureMigrationClassExists(string $class): void
    {
        if (! class_exists($class)) {
            throw MigrationClassNotFound::new($class);
        }
    }

    private function loadMigrationsFromDirectories(): void
    {
        $migrationDirectories = $this->migrationDirectories;

        if ($this->migrationsLoaded) {
            return;
        }

        $this->migrationsLoaded = true;

        foreach ($migrationDirectories as $namespace => $path) {
                $migrations = $this->migrationFinder->findMigrations(
                    $path,
                    $namespace
                );
                $this->registerMigrations($migrations);
        }
    }
}
