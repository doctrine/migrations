<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

abstract class MigrationTestCase extends \PHPUnit_Framework_TestCase
{
    public function getSqliteConnection()
    {
        $params = array('driver' => 'pdo_sqlite', 'memory' => true);

        return DriverManager::getConnection($params);
    }

    /**
     * @return Configuration
     */
    public function getSqliteConfiguration()
    {
        $config = new Configuration($this->getSqliteConnection());
        $config->setMigrationsDirectory(\sys_get_temp_dir());
        $config->setMigrationsNamespace('DoctrineMigrations');

        return $config;
    }
}
