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

        $schemaChangelog->addColumn('version', 'string');
        $schemaChangelog->addColumn('executed_on', 'datetime');
        $schemaChangelog->addColumn('execution_time', 'integer', ['notnull' => false]);
        $schemaChangelog->addColumn('success', 'boolean');
        $schemaChangelog->setPrimaryKey(['version']);

        $this->schemaManager->createTable($schemaChangelog);
    }

    /**
     * @return MigrationInfo[]
     */
    public function getExecutedMigrations() : ExecutedMigrationsSet
    {
        if (!$this->isInitialized()) {
            $this->initialize();
        }

        $rows = $this->connection->fetchAll('SELECT * FROM schema_changelog');

        $migrations = [];
        foreach ($rows as $row) {

            $row = array_change_key_case($row, CASE_LOWER);

            $version   = new Version($row['version']);
            $migration = new MigrationInfo($version);

            $migration->setExecutedOn(DateTime::createFromFormat(
                $this->platform->getDateTimeFormatString(),
                $row['executed_on']
            ));
//            $migration->setExecutionTime($row['execution_time']);
//            $migration->success = $row['success'] ? true : false;

            $migrations[(string) $version] = $migration;
        }

        return new ExecutedMigrationsSet($migrations);
    }

    public function start(MigrationPlanItem $plan) : void
    {
        // not handled, in the future could use to add debug info when the migrations starts to run
    }

    public function complete(ExecutionResult $result) : void
    {
        $plan = $result->getPlan();
        $info = $plan->getInfo();

        if ($plan->getDirection() === Direction::DOWN) {
            $this->connection->delete('schema_changelog', [
                'version' => (string) $info->getVersion(),
            ]);
        } else {
            $this->connection->insert('schema_changelog', [
                'version' => (string) $info->getVersion(),
                'executed_on' => $info->getExecutedOn()->format($this->platform->getDateTimeFormatString()),
                'execution_time' => $result->getTime(),
                'success' => 1,
            ]);
        }
    }
}
