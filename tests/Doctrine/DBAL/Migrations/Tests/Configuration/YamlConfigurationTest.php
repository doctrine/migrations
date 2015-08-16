<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;

class YamlConfigurationTest extends AbstractConfigurationTest
{
    /**
     * @inheritdoc
     */
    public function loadConfiguration(
        $configFileSuffix = '',
        OutputWriter $outputWriter = null,
        MigrationFinderInterface $migrationFinder = null
    ) {
        $configFile = 'config.yml';
        if ('' !== $configFileSuffix) {
            $configFile = 'config_' . $configFileSuffix . '.yml';
        }

        $configFileSuffix = new YamlConfiguration($this->getSqliteConnection(), $outputWriter, $migrationFinder);
        $configFileSuffix->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $configFileSuffix;
    }
}
