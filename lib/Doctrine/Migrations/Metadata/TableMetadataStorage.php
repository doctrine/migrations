<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use const CASE_LOWER;
use function array_change_key_case;

class TableMetadataStorage implements MetadataStorage
{
    /** @var Connection */
    private $connection;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var AbstractPlatform */
    private $platform;

    public function __construct(Connection $connection)
    {
        $this->connection    = $connection;
        $this->schemaManager = $connection->getSchemaManager();
        $this->platform      = $connection->getDatabasePlatform();
    }

    private function isInitialized() : bool
    {
        if ($this->connection instanceof MasterSlaveConnection) {
            $this->connection->connect('master');
        }

        return $this->schemaManager->tablesExist(['schema_changelog']);
    }

    private function initialize() : void
    {
        $schemaChangelog = new Table('schema_changelog');

        $schemaChangelog->addColumn('version', 'string', ['notnull' => true]);
        $schemaChangelog->addColumn('executed_on', 'datetime', ['notnull' => false]);
        $schemaChangelog->addColumn('execution_time', 'integer', ['notnull' => false]);

        $schemaChangelog->setPrimaryKey(['version']);

        $this->schemaManager->createTable($schemaChangelog);
    }

    /**
     * @return ExecutedMigrationsSet
     */
    public function getExecutedMigrations() : ExecutedMigrationsSet
    {
        if (! $this->isInitialized()) {
            $this->initialize();
        }

        $rows = $this->connection->fetchAll('SELECT * FROM schema_changelog');

        $migrations = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);

            $version = new Version($row['version']);

            $executedOn = DateTime::createFromFormat(
                $this->platform->getDateTimeFormatString(),
                $row['executed_on']
            );
            $migration = new ExecutedMigration($version, $executedOn, $row['execution_time']? intval($row['execution_time']) : null);

            $migrations[(string) $version] = $migration;
        }

        return new ExecutedMigrationsSet($migrations);
    }

    public function complete(ExecutionResult $result) : void
    {

        if ($result->getDirection() === Direction::DOWN) {
            $this->connection->delete('schema_changelog', [
                'version' => (string) $result->getVersion(),
            ]);
        } else {
            $this->connection->insert('schema_changelog', [
                'version' => (string) $result->getVersion(),
                'executed_on' => $result->getExecutedOn()->format($this->platform->getDateTimeFormatString()),
                'execution_time' => $result->getTime(),
            ]);
        }
    }
}
