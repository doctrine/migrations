<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;

/**
 * @covers \Doctrine\DBAL\Migrations\Configuration\YamlConfiguration
 */
class YamlConfigurationTest extends AbstractConfigurationTest
{
    /**
     * @inheritdoc
     */
    public function loadConfiguration($config = '')
    {
        $configFile = 'config.yml';
        if ('' !== $config) {
            $configFile = 'config_' . $config . '.yml';
        }

        $config = new YamlConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $config;
    }
}
