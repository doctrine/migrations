<?php

namespace Doctrine\DBAL\Migrations\Finder;

/**
 * Abstract base class for MigrationFinders
 *
 * @since   1.0.0-alpha3
 */
abstract class AbstractFinder implements MigrationFinderInterface
{
    protected static function requireOnce($path)
    {
        require_once $path;
    }

    protected function getRealPath($directory)
    {
        $dir = realpath($directory);
        if (false === $dir || ! is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot load migrations from "%s" because it is not a valid directory',
                $directory
            ));
        }

        return $dir;
    }

    /**
     * Load the migrations and return an array of thoses loaded migrations
     * @param $files array of migration filename found
     * @param $namespace namespace of thoses migrations
     * @return array constructed with the migration name as key and the value is the fully qualified name of the migration
     */
    protected function loadMigrations($files, $namespace)
    {
        $migrations = [];

        uasort($files, $this->getFileSortCallback());

        foreach ($files as $file) {
            static::requireOnce($file);
            $className = basename($file, '.php');
            $version   = (string) substr($className, 7);
            if ($version === '0') {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot load a migrations with the name "%s" because it is a reserved number by doctrine migrations' . PHP_EOL .
                    'It\'s used to revert all migrations including the first one.',
                    $version
                ));
            }
            $migrations[$version] = sprintf('%s\\%s', $namespace, $className);
        }

        return $migrations;
    }

    /**
     * Return callable for files basename uasort
     *
     * @return callable
     */
    protected function getFileSortCallback()
    {
        return function ($a, $b) {
            return (basename($a) < basename($b)) ? -1 : 1;
        };
    }
}
