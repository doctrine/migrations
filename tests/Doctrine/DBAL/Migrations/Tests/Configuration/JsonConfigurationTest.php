<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\JsonConfiguration;

class JsonConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration()
    {
        $config = new JsonConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . "/_files/config.json");

        return $config;
    }
}
