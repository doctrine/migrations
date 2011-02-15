<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;

class YamlConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration()
    {
        $config = new YamlConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . "/_files/config.yml");

        return $config;
    }
}
