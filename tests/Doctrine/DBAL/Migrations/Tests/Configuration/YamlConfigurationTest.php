<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Doctrine\DBAL\Migrations\Finder\MigrationFinder;
use Doctrine\DBAL\Migrations\OutputWriter;
use const DIRECTORY_SEPARATOR;

class YamlConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration(
        string $configFileSuffix = '',
        ?OutputWriter $outputWriter = null,
        ?MigrationFinder $migrationFinder = null
    ) : AbstractFileConfiguration {
        $configFile = 'config.yml';
        if ($configFileSuffix !== '') {
            $configFile = 'config_' . $configFileSuffix . '.yml';
        }

        $configFileSuffix = new YamlConfiguration($this->getSqliteConnection(), $outputWriter, $migrationFinder);
        $configFileSuffix->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $configFileSuffix;
    }
}
