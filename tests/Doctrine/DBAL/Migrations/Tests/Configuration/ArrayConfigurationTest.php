<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\ArrayConfiguration;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\OutputWriter;

class ArrayConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration(
        $configFileSuffix = '',
        OutputWriter $outputWriter = null,
        MigrationFinderInterface $migrationFinder = null
    )
    {
        $configFile = 'config.php';
        if ('' !== $configFileSuffix) {
            $configFile = 'config_' . $configFileSuffix . '.php';
        }

        $config = new ArrayConfiguration($this->getSqliteConnection(),$outputWriter, $migrationFinder);
        $config->load(__DIR__ . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . $configFile);

        return $config;
    }

    /**
     * Test that config file not exists exception
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Given config file does not exist
     */
    public function testThrowExceptionIfFileNotExist()
    {
        $config = new ArrayConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . "/_files/none.php");
    }

}
