<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\JsonConfiguration;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\OutputWriter;

class JsonConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration(
        $configFileSuffix = '',
        ?OutputWriter $outputWriter = null,
        ?MigrationFinderInterface $migrationFinder = null
    ) {
        $configFile = 'config.json';

        if ($configFileSuffix !== '') {
            $configFile = 'config_' . $configFileSuffix . '.json';
        }

        $config = new JsonConfiguration($this->getSqliteConnection(), $outputWriter, $migrationFinder);
        $config->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $config;
    }

    /**
     * Test that config file not exists exception
     */
    public function testThrowExceptionIfFileNotExist()
    {
        $config = new JsonConfiguration($this->getSqliteConnection());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given config file does not exist');

        $config->load(__DIR__ . '/_files/none.json');
    }
}
