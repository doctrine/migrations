<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\Version;
use ReflectionMethod;

use function array_map;
use function glob;
use function is_file;
use function rmdir;
use function unlink;

class Helper
{
    public static function registerMigrationInstance(MigrationsRepository $repository, Version $version, AbstractMigration $migration): void
    {
        $reflection = new ReflectionMethod(FilesystemMigrationsRepository::class, 'registerMigrationInstance');
        $reflection->setAccessible(true);
        $reflection->invoke($repository, $version, $migration);
    }

    /**
     * Delete a directory.
     *
     * @see http://stackoverflow.com/a/8688278/1645517
     */
    public static function deleteDir(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        $classFunction = [self::class, __FUNCTION__];

        if (is_file($path)) {
            @unlink($path);

            return true;
        }

        $files = glob($path . '/*');

        if ($files !== [] && $files !== false) {
            array_map($classFunction, $files);

            @rmdir($path);

            return true;
        }

        return false;
    }
}
