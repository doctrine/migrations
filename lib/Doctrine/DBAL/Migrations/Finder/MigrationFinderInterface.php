<?php

namespace Doctrine\DBAL\Migrations\Finder;

/**
 * MigrationFinderInterface implementations locate migrations (classes that extend
 * `Doctrine\DBAL\Migrations\AbstractMigration`) in a directory.
 */
interface MigrationFinderInterface
{
    /**
     * Find all the migrations in a directory for the given path and namespace.
     *
     * @param   string      $directory The directory in which to look for migrations
     * @param   string|null $namespace The namespace of the classes to load
     *
     * @return  string[] An array of class names that were found with the version
     *          as keys.
     *
     * @throws  \InvalidArgumentException If the directory does not exist.
     */
    public function findMigrations($directory, $namespace = null);
}
