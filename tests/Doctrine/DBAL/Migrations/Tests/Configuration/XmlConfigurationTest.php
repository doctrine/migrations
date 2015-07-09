<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;

/**
 * @covers \Doctrine\DBAL\Migrations\Configuration\XmlConfiguration
 */
class XmlConfigurationTest extends AbstractConfigurationTest
{
    /**
     * @inheritdoc
     */
    public function loadConfiguration($config = '')
    {
        $configFile = 'config.xml';
        if ('' !== $config) {
            $configFile = 'config_' . $config . '.xml';
        }

        $config = new XmlConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $config;
    }
}
