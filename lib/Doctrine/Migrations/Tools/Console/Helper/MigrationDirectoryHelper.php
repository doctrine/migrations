<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tools\Console\Exception\DirectoryDoesNotExistAndCouldNotBeCreated;
use Throwable;

use function date;
use function file_exists;
use function mkdir;
use function rtrim;

use const DIRECTORY_SEPARATOR;

/**
 * The MigrationDirectoryHelper class is responsible for returning the directory that migrations are stored in.
 *
 * @internal
 */
class MigrationDirectoryHelper
{
    private const MIGRATION_DIRECTORY_PERMISSIONS = 755;

    /**
     * @throws DirectoryDoesNotExistAndCouldNotBeCreated
     */
    public function getMigrationDirectory(Configuration $configuration, string $dir): string
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);

        try {
            if (! file_exists($dir)) {
                $this->createDirIfNotExists($dir);
            }
        } catch (Throwable $ex) {
            throw DirectoryDoesNotExistAndCouldNotBeCreated::new($dir, $ex->getMessage());
        }

        if ($configuration->areMigrationsOrganizedByYear()) {
            $dir .= $this->appendDir(date('Y'));
        }

        if ($configuration->areMigrationsOrganizedByYearAndMonth()) {
            $dir .= $this->appendDir(date('m'));
        }

        return $dir;
    }

    private function appendDir(string $dir): string
    {
        return DIRECTORY_SEPARATOR . $dir;
    }

    private function createDirIfNotExists(string $dir): void
    {
        if (file_exists($dir)) {
            return;
        }

        mkdir($dir, self::MIGRATION_DIRECTORY_PERMISSIONS, true);
    }
}
