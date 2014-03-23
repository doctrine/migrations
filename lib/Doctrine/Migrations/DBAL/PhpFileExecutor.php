<?php

namespace Doctrine\Migrations\DBAL;

use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\Executor\Executor;
use Doctrine\Migrations\Configuration;
use Doctrine\DBAL\Connection;

class PhpFileExecutor implements Executor
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

        require_once $fileName;

        $migrationClass = basename($migration->script);

        if (!class_exists($migrationClass)) {
            throw new \RuntimeException(sprintf(
                'No class exists with name "%s".', $migrationClass
            ));
        }

        $migration = new $migrationClass();

        if (!($migration instanceof DBALMigration)) {
            throw new \RuntimeException(
                sprintf('Class "%s" does not implement \Doctrine\Migrations\DBALMigration.', $migrationClass)
            );
        }

        $migration->migrate($this->connection);
    }
}
