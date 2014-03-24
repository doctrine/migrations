<?php

namespace Doctrine\Migrations\DBAL\Executor;

use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\Executor\Executor;
use Doctrine\Migrations\Configuration;
use Doctrine\DBAL\Connection;

class SqlFileExecutor implements Executor
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function execute(MigrationInfo $migration)
    {
        $sql = file_get_contents($migration->script);

        $this->connection->exec($sql);
    }

    public function getType()
    {
        return 'SQL';
    }
}
