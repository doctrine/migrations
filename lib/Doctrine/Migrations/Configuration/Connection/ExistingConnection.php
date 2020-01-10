<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;

/**
 * The ConfigurationFileLoader class is responsible for loading a Doctrine\DBAL\Connection from a PHP file
 * that returns an array of connection information which is used to instantiate a connection with DriverManager::getConnection()
 */
final class ExistingConnection implements ConnectionLoader
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection() : Connection
    {
        return $this->connection;
    }
}
