<?php

namespace Doctrine\Migrations\DBAL\Executor;

use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\Executor\Executor;
use Doctrine\Migrations\Configuration;
use Doctrine\Migrations\DBAL\DBALMigration;
use Doctrine\DBAL\Connection;

class PhpFileExecutor implements Executor
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function execute(MigrationInfo $migration)
    {
        require_once $migration->script;

        $migrationClass = basename($migration->script, '.php');

        if (!class_exists($migrationClass)) {
            throw new \RuntimeException(sprintf(
                'No class exists with name "%s".', $migrationClass
            ));
        }

        $migration = new $migrationClass();

        if (!($migration instanceof DBALMigration)) {
            throw new \RuntimeException(
                sprintf('Class "%s" does not implement \Doctrine\Migrations\DBAL\DBALMigration.', $migrationClass)
            );
        }

        $migration->migrate($this->connection);
    }

    public function getType()
    {
        return 'PHP';
    }
}
