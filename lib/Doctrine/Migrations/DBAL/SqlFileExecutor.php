<?php

namespace Doctrine\Migrations\Executor;

use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\Executor\Executor;
use Doctrine\Migrations\Configuration;
use Doctrine\DBAL\Connection;

class SqlFileExecutor implements Executor
{
    private $connection;
    private $configuration;

    public function __construct(Connection $connection, Configuration $configuration)
    {
        $this->connection = $connection;
        $this->configuration = $configuration;
    }

    public function execute(MigrationInfo $migration)
    {
        $fileName = $this->configuration->getScriptDirectory() . "/" . $migration->script;

        $sql = file_get_contents($fileName);

        $this->connection->exec($sql);
    }
}
