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
}