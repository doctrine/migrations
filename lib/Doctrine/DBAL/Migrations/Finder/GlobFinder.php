<?php

namespace Doctrine\DBAL\Migrations\Finder;

/**
 * A MigrationFinderInterface implementation that uses `glob` and some special file and
 * class names to load migrations from a directory.
 *
 * The migrations are expected to reside in files with the filename
 * `VersionYYYYMMDDHHMMSS.php`. Each file should contain one class named
 * `VersionYYYYMMDDHHMMSS`.
 *
 * @since   1.0.0-alpha3
 */
final class GlobFinder extends AbstractFinder
{
    /**
     * {@inheritdoc}
     */
    public function findMigrations($directory, $namespace = null)
    {
        $dir = $this->getRealPath($directory);

        $files = glob(rtrim($dir, '/') . '/Version*.php');

        return $this->loadMigrations($files, $namespace);
    }
}
