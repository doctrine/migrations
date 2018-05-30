<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Index;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigrationTableManipulator;
use Doctrine\Migrations\MigrationTableStatus;
use Doctrine\Migrations\Tests\MigrationTestCase;

class MigrationTableManipulatorTest extends MigrationTestCase
{
    /** @var MigrationTableManipulator */
    private $migrationTableManipulator;

    /** @var MigrationTableStatus */
    private $migrationTableStatus;

    /** @var Connection */
    private $connection;

    public function testCreateMigrationTable() : void
    {
        $schemaManager = $this->connection->getSchemaManager();

        self::assertTrue($this->migrationTableManipulator->createMigrationTable());

        self::assertTrue($schemaManager->tablesExist(['doctrine_migration_versions']));
        self::assertTrue($this->migrationTableStatus->isCreated());
        self::assertTrue($this->migrationTableStatus->isUpToDate());

        $table = $schemaManager->listTableDetails('doctrine_migration_versions');

        self::assertTrue($table->hasColumn('version'));
        self::assertTrue($table->getColumn('version')->getNotnull());
        self::assertEquals(200, $table->getColumn('version')->getLength());

        self::assertTrue($table->hasColumn('executed_at'));
        self::assertTrue($table->getColumn('executed_at')->getNotnull());

        self::assertTrue($table->hasIndex('unique_version'));
        self::assertTrue($table->getIndex('unique_version')->isUnique());
    }

    public function testUpdateMigrationTable() : void
    {
        $createTablesSql = [
            'CREATE TABLE doctrine_migration_versions (version varchar(200) NOT NULL, PRIMARY KEY (version))',
            'CREATE TABLE test (test varchar(255) NOT NULL)',
        ];

        foreach ($createTablesSql as $createTableSql) {
            $this->connection->executeQuery($createTableSql);
        }

        self::assertTrue($this->migrationTableStatus->isCreated());
        self::assertFalse($this->migrationTableStatus->isUpToDate());

        self::assertTrue($this->migrationTableManipulator->createMigrationTable());

        self::assertTrue($this->migrationTableStatus->isUpToDate());

        $schemaManager = $this->connection->getSchemaManager();

        $table = $schemaManager->listTableDetails('doctrine_migration_versions');

        self::assertTrue($table->hasColumn('version'));
        self::assertTrue($table->getColumn('version')->getNotnull());
        self::assertEquals([
            'version'
        ], $table->getPrimaryKeyColumns());

        self::assertTrue($table->hasColumn('executed_at'), 'Check executedAt column was added');
        self::assertFalse($table->getColumn('executed_at')->getNotnull());

        self::assertTrue($schemaManager->tablesExist(['test']), 'Check table not related to Doctrine was not dropped');
    }

    protected function setUp() : void
    {
        $this->connection = $this->getTestConnection();

        $configuration = new Configuration($this->connection);
        $configuration->setMigrationsDirectory(__DIR__ . '/Stub/migration-empty-folder');
        $configuration->setMigrationsNamespace('DoctrineMigrations');
        $configuration->setMigrationsColumnLength(200);

        $dependencyFactory = $configuration->getDependencyFactory();

        $this->migrationTableManipulator = $dependencyFactory->getMigrationTableManipulator();
        $this->migrationTableStatus      = $dependencyFactory->getMigrationTableStatus();
    }

    private function getTestConnection() : Connection
    {
        $params = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'defaultTableOptions' => [
                'unique' => [
                    new Index('unique_version', ['version'], true),
                ],
            ],
        ];

        return DriverManager::getConnection($params);
    }
}
