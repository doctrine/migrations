<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

use InvalidArgumentException;
use const PHP_EOL;
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

        uasort($files, $this->getFileSortCallback());

        foreach ($files as $file) {
            static::requireOnce($file);
            $className = basename($file, '.php');
            $version   = (string) substr($className, 7);

            if ($version === '0') {
                throw new InvalidArgumentException(sprintf(
                    'Cannot load a migrations with the name "%s" because it is a reserved number by doctrine migrations' . PHP_EOL .
                    'It\'s used to revert all migrations including the first one.',
                    $version
                ));
            }

            $migrations[$version] = sprintf('%s\\%s', $namespace, $className);
        }

        return $migrations;
    }

    protected function getFileSortCallback() : callable
    {
        return function (string $a, string $b) {
            return (basename($a) < basename($b)) ? -1 : 1;
        };
    }
}
