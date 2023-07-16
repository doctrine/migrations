<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tools\Console\Exception\DirectoryDoesNotExist;

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
    /** @throws DirectoryDoesNotExist */
    public function getMigrationDirectory(Configuration $configuration, string $dir): string
    {
        $dir = rtrim($dir, '/');

        if (! file_exists($dir)) {
            throw DirectoryDoesNotExist::new($dir);
        }

        if ($configuration->areMigrationsOrganizedByYear()) {
            $dir .= $this->appendDir(date('Y'));
        }

        if ($configuration->areMigrationsOrganizedByYearAndMonth()) {
            $dir .= $this->appendDir(date('m'));
        }

        $this->createDirIfNotExists($dir);

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

        mkdir($dir, 0755, true);
    }
}
