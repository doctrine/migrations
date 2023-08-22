<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

use Doctrine\Migrations\Finder\Exception\InvalidDirectory;
use Doctrine\Migrations\Finder\Exception\NameIsReserved;
use ReflectionClass;

use function assert;
use function get_declared_classes;
use function in_array;
use function is_dir;
use function realpath;
use function strlen;
use function strncmp;

/**
 * The Finder class is responsible for for finding migrations on disk at a given path.
 */
abstract class Finder implements MigrationFinder
{
    protected static function requireOnce(string $path): void
    {
        require_once $path;
    }

    /** @throws InvalidDirectory */
    protected function getRealPath(string $directory): string
    {
        $dir = realpath($directory);

        if ($dir === false || ! is_dir($dir)) {
            throw InvalidDirectory::new($directory);
        }

        return $dir;
    }

    /**
     * @param string[] $files
     *
     * @return string[]
     *
     * @throws NameIsReserved
     */
    protected function loadMigrations(array $files, string|null $namespace): array
    {
        $includedFiles = [];
        foreach ($files as $file) {
            static::requireOnce($file);

            $realFile = realpath($file);
            assert($realFile !== false);

            $includedFiles[] = $realFile;
        }

        $classes  = $this->loadMigrationClasses($includedFiles, $namespace);
        $versions = [];
        foreach ($classes as $class) {
            $versions[] = $class->getName();
        }

        return $versions;
    }

    /**
     * Look up all declared classes and find those classes contained
     * in the given `$files` array.
     *
     * @param string[]    $files     The set of files that were `required`
     * @param string|null $namespace If not null only classes in this namespace will be returned
     *
     * @return ReflectionClass<object>[] the classes in `$files`
     */
    protected function loadMigrationClasses(array $files, string|null $namespace = null): array
    {
        $classes = [];
        foreach (get_declared_classes() as $class) {
            $reflectionClass = new ReflectionClass($class);

            if (! in_array($reflectionClass->getFileName(), $files, true)) {
                continue;
            }

            if ($namespace !== null && ! $this->isReflectionClassInNamespace($reflectionClass, $namespace)) {
                continue;
            }

            $classes[] = $reflectionClass;
        }

        return $classes;
    }

    /** @param ReflectionClass<object> $reflectionClass */
    private function isReflectionClassInNamespace(ReflectionClass $reflectionClass, string $namespace): bool
    {
        return strncmp($reflectionClass->getName(), $namespace . '\\', strlen($namespace) + 1) === 0;
    }
}
