<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata\Storage;

use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\Migrations\Exception\MetadataStorageError;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use function sprintf;

class TableMetadataStorageTest extends TestCase
{
    /** @var Connection */
    private $connection;

    /** @var TableMetadataStorage */
    private $storage;

    /** @var TableMetadataStorageConfiguration */
    private $config;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    private function getSqliteConnection() : Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }

    public function setUp() : void
    {
        $this->connection    = $this->getSqliteConnection();
        $this->schemaManager = $this->connection->getSchemaManager();

        $this->config  = new TableMetadataStorageConfiguration();
        $this->storage = new TableMetadataStorage($this->connection, $this->config);
    }

    public function testDifferentTableNotUpdatedOnRead() : void
    {
        $this->expectException(MetadataStorageError::class);
        $this->expectExceptionMessage('The metadata storage is not up to date, please run the sync-metadata-storage command to fix this issue.');

        $table = new Table($this->config->getTableName());
        $table->addColumn($this->config->getVersionColumnName(), 'string', ['notnull' => true, 'length' => 10]);
        $table->setPrimaryKey([$this->config->getVersionColumnName()]);
        $this->schemaManager->createTable($table);

        $this->storage->getExecutedMigrations();
    }

    public function testTableNotCreatedOnRead() : void
    {
        $this->expectException(MetadataStorageError::class);
        $this->expectExceptionMessage('The metadata storage is not initialized, please run the sync-metadata-storage command to fix this issue.');
        $this->storage->getExecutedMigrations();
    }

    public function testTableStructureUpdate() : void
    {
        $config = new TableMetadataStorageConfiguration();
        $config->setTableName('a');
        $config->setVersionColumnName('b');
        $config->setVersionColumnLength(199);
        $config->setExecutedAtColumnName('c');
        $config->setExecutionTimeColumnName('d');

        $table = new Table($config->getTableName());
        $table->addColumn($config->getVersionColumnName(), 'string', ['notnull' => true, 'length' => 10]);
        $table->setPrimaryKey([$config->getVersionColumnName()]);
        $this->schemaManager->createTable($table);

        $storage = new TableMetadataStorage($this->connection, $config);

        $storage->ensureInitialized();

        $table = $this->schemaManager->listTableDetails($config->getTableName());

        self::assertInstanceOf(StringType::class, $table->getColumn('b')->getType());
        self::assertInstanceOf(DateTimeType::class, $table->getColumn('c')->getType());
        self::assertInstanceOf(IntegerType::class, $table->getColumn('d')->getType());
    }

    public function testTableStructure() : void
    {
        $config = new TableMetadataStorageConfiguration();
        $config->setTableName('a');
        $config->setVersionColumnName('b');
        $config->setVersionColumnLength(199);
        $config->setExecutedAtColumnName('c');
        $config->setExecutionTimeColumnName('d');

        $storage = new TableMetadataStorage($this->connection, $config);

        $storage->ensureInitialized();

        $table = $this->schemaManager->listTableDetails($config->getTableName());

        self::assertInstanceOf(StringType::class, $table->getColumn('b')->getType());
        self::assertInstanceOf(DateTimeType::class, $table->getColumn('c')->getType());
        self::assertInstanceOf(IntegerType::class, $table->getColumn('d')->getType());
    }

    public function testComplete() : void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        $sql  = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        $rows = $this->connection->fetchAll($sql);
        self::assertSame([
            0 =>
                [
                    'version' => '1230',
                    'executed_at' => '2010-01-05 10:30:21',
                    'execution_time' => '31000',
                ],
        ], $rows);
    }

    public function testCompleteWithFloatTime() : void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.49);
        $this->storage->complete($result);

        $sql  = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        $rows = $this->connection->fetchAll($sql);
        self::assertSame([
            0 =>
                [
                    'version' => '1230',
                    'executed_at' => '2010-01-05 10:30:21',
                    'execution_time' => '31490',
                ],
        ], $rows);
    }

    public function testRead() : void
    {
        $this->storage->ensureInitialized();

        $date    = new DateTimeImmutable('2010-01-05 10:30:21');
        $result1 = new ExecutionResult(new Version('1230'), Direction::UP, $date);
        $result1->setTime(31.0);
        $this->storage->complete($result1);

        $result2 = new ExecutionResult(new Version('1231'), Direction::UP);
        $this->storage->complete($result2);

        $executedMigrations = $this->storage->getExecutedMigrations();

        self::assertTrue($executedMigrations->hasMigration(new Version('1230')));
        self::assertTrue($executedMigrations->hasMigration(new Version('1231')));
        self::assertFalse($executedMigrations->hasMigration(new Version('1232')));

        $m1 = $executedMigrations->getMigration($result1->getVersion());

        self::assertEquals($result1->getVersion(), $m1->getVersion());
        self::assertNotNull($m1->getExecutedAt());
        self::assertSame($date->format(DateTime::ISO8601), $m1->getExecutedAt()->format(DateTime::ISO8601));
        self::assertSame(31.0, $m1->getExecutionTime());

        $m2 = $executedMigrations->getMigration($result2->getVersion());

        self::assertEquals($result2->getVersion(), $m2->getVersion());
        self::assertNull($m2->getExecutedAt());
        self::assertNull($m2->getExecutionTime());
    }

    public function testExecutedMigrationWithTiming() : void
    {
        $date = new DateTimeImmutable();
        $m1   = new ExecutedMigration(new Version('A'), $date, 123.0);

        self::assertSame($date, $m1->getExecutedAt());
        self::assertSame(123.0, $m1->getExecutionTime());
    }

    public function testCompleteDownRemovesTheRow() : void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        $sql = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        self::assertCount(1, $this->connection->fetchAll($sql));

        $result = new ExecutionResult(new Version('1230'), Direction::DOWN, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        self::assertCount(0, $this->connection->fetchAll($sql));
    }

    public function testReset() : void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        $sql = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        self::assertCount(1, $this->connection->fetchAll($sql));

        $this->storage->reset();

        self::assertCount(0, $this->connection->fetchAll($sql));
    }

    public function testResetWithEmptySchema() : void
    {
        $this->storage->ensureInitialized();

        $this->storage->reset();

        $sql = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        self::assertCount(0, $this->connection->fetchAll($sql));
    }
}
