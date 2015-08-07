<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;

class XmlConfigurationTest extends AbstractConfigurationTest
{
    /**
     * @inheritdoc
     */
    public function loadConfiguration(
        $configFileSuffix = '',
        OutputWriter $outputWriter = null,
        MigrationFinderInterface $migrationFinder = null
    ) {
        $configFile = 'config.xml';
        if ('' !== $configFileSuffix) {
            $configFile = 'config_' . $configFileSuffix . '.xml';
        }

        $configFileSuffix = new XmlConfiguration($this->getSqliteConnection(), $outputWriter, $migrationFinder);
        $configFileSuffix->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $configFileSuffix;
    }
}
