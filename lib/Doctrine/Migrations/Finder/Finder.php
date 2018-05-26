<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

use InvalidArgumentException;
use ReflectionClass;
use const PHP_EOL;
use const SORT_STRING;
use function get_declared_classes;
use function in_array;
use function is_dir;
use function ksort;
use function realpath;
use function sprintf;
use function substr;

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
        $includedFiles = [];
        foreach ($files as $file) {
            static::requireOnce($file);
            $includedFiles[] = realpath($file);
        }

        $classes  = $this->loadMigrationClasses($includedFiles, $namespace);
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

    /**
     * Look up all declared classes and find those classes contained
     * in the give `$files` array.
     *
     * @param string[] $files The set of files that were `required`
     * @param string|null $namespace If not null only classes in this namespace will be returned
     * @return ReflectionClass[] the classes in `$files`
     */
    protected function loadMigrationClasses(array $files, ?string $namespace) : array
    {
        $classes = [];
        foreach (get_declared_classes() as $class) {
            $ref = new ReflectionClass($class);
            if (! in_array($ref->getFileName(), $files, true)) {
                continue;
            }

            if (null !== $namespace && $namespace !== $ref->getNamespaceName()) {
                continue;
            }

            $classes[] = $ref;
        }

        return $classes;
    }
}
