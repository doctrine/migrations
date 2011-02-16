<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\Common\ClassLoader;
use Doctrine\DBAL\DriverManager;

abstract class MigrationTestCase extends \PHPUnit_Framework_TestCase
{
    public function getSqliteConnection()
    {
        $params = array('driver' => 'pdo_sqlite', 'memory' => true);
        return DriverManager::getConnection($params);
    }

    /**
     * @return \Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    public function getSqliteConfiguration()
    {
        $config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($this->getSqliteConnection());
        $config->setMigrationsDirectory(\sys_get_temp_dir());
        $config->setMigrationsNamespace('DoctrineMigrations');
        return $config;
    }
}