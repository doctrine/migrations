<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class ConfigurationHelper
 * @package Doctrine\DBAL\Migrations\Tools\Console\Helper
 * @internal
 */
class MigrationDirectoryHelper extends Helper
{

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration;
    }

    public function getMigrationDirectory()
    {
        $dir = $this->configuration->getMigrationsDirectory();
        $dir = $dir ? $dir : getcwd();
        $dir = rtrim($dir, '/');

        if ( ! file_exists($dir)) {
            throw new \InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $dir));
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

    private function appendDir($dir)
    {
        return DIRECTORY_SEPARATOR . $dir;
    }

    private function createDirIfNotExists($dir)
    {
        if ( ! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'MigrationDirectory';
    }
}
