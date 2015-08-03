<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\ArrayConfiguration;

class ArrayConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration()
    {
        $config = new ArrayConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . "/_files/config.php");

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
        $config = new ArrayConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . "/_files/none.php");
    }

}
