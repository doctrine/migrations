<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;

class XmlConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration()
    {
        $config = new XmlConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . "/_files/config.xml");

        return $config;
    }
}
