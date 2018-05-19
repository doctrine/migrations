<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

use InvalidArgumentException;
use const PHP_EOL;
use const SORT_STRING;
use function basename;
use function is_dir;
use function realpath;
use function sprintf;
use function substr;
use function uasort;

abstract class Finder implements MigrationFinder
{
    protected static function requireOnce(string $path) : void
    {
        require_once $path;
    }

    protected function getRealPath(string $directory) : string
    {
        $dir = realpath($directory);

        if ($dir === false || ! is_dir($dir)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot load migrations from "%s" because it is not a valid directory',
                $directory
            ));
        }

        return $dir;
    }

    /**
     * @param string[] $files
     *
     * @return string[]
     */
    protected function loadMigrations(array $files, ?string $namespace) : array
    {
        $migrations = [];

        $includedFiles = [];
        foreach ($files as $file) {
            static::requireOnce($file);
            $includedFiles[] = realpath($file);
        }

        $classes = $this->loadMigrationClasses($includedFiles);
        $versions = [];
        foreach ($classes as $class) {
            $version = substr($class->getShortName(), 7);
            if ($version === '0') {
                throw new InvalidArgumentException(sprintf(
                    'Cannot load a migrations with the name "%s" because it is a reserved number by doctrine migrations' . PHP_EOL .
                    'It\'s used to revert all migrations including the first one.',
                    $version
                ));
            }
            $versions[$version] = $class->getName();
        }

        ksort($versions, SORT_STRING);

        return $versions;
    }

    protected function loadMigrationClasses(array $files) : array
    {
        $classes = [];
        foreach (get_declared_classes() as $class) {
            $ref = new \ReflectionClass($class);
            if (in_array($ref->getFileName(), $files)) {
                $classes[] = $ref;
            }
        }

        return $classes;
    }
}
