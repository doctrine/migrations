<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use InvalidArgumentException;
use const DIRECTORY_SEPARATOR;
use function date;
use function file_exists;
use function getcwd;
use function mkdir;
use function rtrim;
use function sprintf;

/**
 * @internal
 */
class MigrationDirectoryHelper
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /** @throws InvalidArgumentException */
    public function getMigrationDirectory() : string
    {
        $dir = $this->configuration->getMigrationsDirectory();
        $dir = $dir ?? getcwd();
        $dir = rtrim($dir, '/');

        if (! file_exists($dir)) {
            throw new InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $dir));
        }

        if ($this->configuration->areMigrationsOrganizedByYear()) {
            $dir .= $this->appendDir(date('Y'));
        }

        if ($this->configuration->areMigrationsOrganizedByYearAndMonth()) {
            $dir .= $this->appendDir(date('m'));
        }

        $this->createDirIfNotExists($dir);

        return $dir;
    }

    private function appendDir(string $dir) : string
    {
        return DIRECTORY_SEPARATOR . $dir;
    }

    private function createDirIfNotExists(string $dir) : void
    {
        if (file_exists($dir)) {
            return;
        }

        mkdir($dir, 0755, true);
    }

    public function getName() : string
    {
        return 'MigrationDirectory';
    }
}
