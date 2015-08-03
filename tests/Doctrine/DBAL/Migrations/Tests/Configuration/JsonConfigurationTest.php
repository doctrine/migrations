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

    /**
     * Test that config file not exists exception
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Given config file does not exist
     */
    public function testThrowExceptionIfFileNotExist()
    {
        $config = new JsonConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . "/_files/none.json");
    }
}
