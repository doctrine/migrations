<?php

namespace Doctrine\Migrations\DBAL;

use Doctrine\Migrations\MetadataStorage;
use Doctrine\Migrations\MigrationInfo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class TableMetadataStorage
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->schemaManager = $connection->getSchemaManager();
        $this->platform = $connection->getDatabasePlatform();
    }

    public function isInitialized()
    {
        return $this->schemaManager->tablesExist(array('schema_changelog'));
    }

    public function initialize()
    {
        $schemaChangelog = new Table('schema_changelog');
        $schemaChangelog->addColumn('version', 'string');
        $schemaChangelog->addColumn('version_rank', 'integer', array('notnull' => false));
        $schemaChangelog->addColumn('installed_rank', 'integer');
        $schemaChangelog->addColumn('description', 'string');
        $schemaChangelog->addColumn('type', 'string', array('length' => 20));
        $schemaChangelog->addColumn('script', 'string', array('length' => 1000));
        $schemaChangelog->addColumn('checksum', 'string', array('length' => 32));
        $schemaChangelog->addColumn('installed_by', 'string', array('length' => 100));
        $schemaChangelog->addColumn('installed_on', 'datetime');
        $schemaChangelog->addColumn('execution_time', 'integer');
        $schemaChangelog->addColumn('success', 'boolean');
        $schemaChangelog->setPrimaryKey(array('version'));

        $this->schemaManager->createTable($schemaChangelog);
    }

    public function getExecutedMigrations()
    {
        $sql = 'SELECT * FROM schema_changelog';
        $rows = $this->connection->fetchAll($sql);

        $migrations = new MigrationSet();

        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $migration = new MigrationInfo(
                new Version($row['version']),
                $row['description'],
                $row['type'],
                $this->path . '/' . $row['script'],
                $row['checksum']
            );
            $migration->installedOn = Type::getType('datetime')->convertToPhpValue($row['installed_on'], $this->platform);
            $migration->installedBy = $row['installed_by'];
            $migration->executionTime = $row['execution_time'];
            $migration->success = $row['success'] ? true : false;
            $migration->installedRank = $row['installed_rank'];

            $migrations->add($migration);
        }

        return $migrations;
    }

    public function delete(MigrationInfo $migration)
    {
    }

    public function start(MigrationInfo $migration)
    {
    }

    public function complete(MigrationInfo $migration)
    {
    }
}
