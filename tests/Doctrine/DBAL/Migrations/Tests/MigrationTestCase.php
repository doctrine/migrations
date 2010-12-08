<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;


abstract class MigrationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    private $connection;


    /**
     * Get connection, create if needed
     *
     * @return Connection
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $params = array('driver' => 'pdo_sqlite', 'memory' => true);
            $this->connection = DriverManager::getConnection($params);
        }
        return $this->connection;
    }


    /**
     * Make configuration
     *
     * @return Configuration
     */
    protected function makeConfiguration()
    {
        return new Configuration($this->getConnection());
    }

}
