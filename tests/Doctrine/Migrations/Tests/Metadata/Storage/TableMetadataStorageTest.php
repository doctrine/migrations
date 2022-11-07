<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata\Storage;

use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver as SQLiteDriver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\MetadataStorageError;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

use function sprintf;

class TableMetadataStorageTest extends TestCase
{
    private Connection $connection;

    private Configuration $connectionConfig;

    private TableMetadataStorage $storage;

    private TableMetadataStorageConfiguration $config;

    /** @var AbstractSchemaManager<AbstractPlatform> */
    private AbstractSchemaManager $schemaManager;

    private DebugLogger $debugLogger;

    private function getSqliteConnection(?Configuration $configuration = null): Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params, $configuration);
    }

    public function setUp(): void
    {
        $this->connectionConfig = new Configuration();
        $this->debugLogger      = new DebugLogger();
        $this->connectionConfig->setMiddlewares([new Middleware($this->debugLogger)]);
        $this->connection    = $this->getSqliteConnection($this->connectionConfig);
        $this->schemaManager = $this->connection->createSchemaManager();

        $this->config  = new TableMetadataStorageConfiguration();
        $this->storage = new TableMetadataStorage($this->connection, new AlphabeticalComparator(), $this->config);
    }

    public function testSchemaIntrospectionExecutedOnlyOnce(): void
    {
        $this->storage->ensureInitialized();

        $oldQueryCount = $this->debugLogger->count;
        $this->storage->ensureInitialized();
        self::assertSame(0, $this->debugLogger->count - $oldQueryCount);

        $oldQueryCount = $this->debugLogger->count;
        $this->storage->getExecutedMigrations();
        self::assertSame(1, $this->debugLogger->count - $oldQueryCount);
    }

    public function testDifferentTableNotUpdatedOnRead(): void
    {
        $this->expectException(MetadataStorageError::class);
        $this->expectExceptionMessage('The metadata storage is not up to date, please run the sync-metadata-storage command to fix this issue.');

        $table = new Table($this->config->getTableName());
        $table->addColumn($this->config->getVersionColumnName(), 'string', ['notnull' => true, 'length' => 10]);
        $table->setPrimaryKey([$this->config->getVersionColumnName()]);
        $this->schemaManager->createTable($table);

        $this->storage->getExecutedMigrations();
    }

    public function testTableNotCreatedOnReadButReadingWorks(): void
    {
        $executedMigrations = $this->storage->getExecutedMigrations();

        self::assertSame([], $executedMigrations->getItems());
        self::assertFalse($this->schemaManager->tablesExist([$this->config->getTableName()]));
    }

    public function testTableStructureUpdate(): void
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

        $storage = new TableMetadataStorage($this->connection, new AlphabeticalComparator(), $config);

        $storage->ensureInitialized();

        $table = $this->schemaManager->introspectTable($config->getTableName());

        self::assertInstanceOf(StringType::class, $table->getColumn('b')->getType());
        self::assertInstanceOf(DateTimeType::class, $table->getColumn('c')->getType());
        self::assertInstanceOf(IntegerType::class, $table->getColumn('d')->getType());
    }

    public function testTableNotUpToDateTriggersExcepton(): void
    {
        $this->expectException(MetadataStorageError::class);
        $this->expectExceptionMessage('The metadata storage is not up to date, please run the sync-metadata-storage command to fix this issue.');

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

        $storage = new TableMetadataStorage($this->connection, new AlphabeticalComparator(), $config);
        $storage->getExecutedMigrations();
    }

    public function testTableStructure(): void
    {
        $config = new TableMetadataStorageConfiguration();
        $config->setTableName('a');
        $config->setVersionColumnName('b');
        $config->setVersionColumnLength(199);
        $config->setExecutedAtColumnName('c');
        $config->setExecutionTimeColumnName('d');

        $storage = new TableMetadataStorage($this->connection, new AlphabeticalComparator(), $config);

        $storage->ensureInitialized();

        $table = $this->schemaManager->introspectTable($config->getTableName());

        self::assertInstanceOf(StringType::class, $table->getColumn('b')->getType());
        self::assertInstanceOf(DateTimeType::class, $table->getColumn('c')->getType());
        self::assertInstanceOf(IntegerType::class, $table->getColumn('d')->getType());
    }

    public function testComplete(): void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        $sql  = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        $rows = $this->connection->fetchAllAssociative($sql);
        self::assertEquals([
            0 =>
                [
                    // Depending on the database driver, execution_time might be returned either as string or int.
                    'version' => '1230',
                    'executed_at' => '2010-01-05 10:30:21',
                    'execution_time' => '31000',
                ],
        ], $rows);
    }

    public function testCompleteWithFloatTime(): void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.49);
        $this->storage->complete($result);

        $sql  = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        $rows = $this->connection->fetchAllAssociative($sql);
        self::assertEquals([
            0 =>
                [
                    // Depending on the database driver, execution_time might be returned either as string or int.
                    'version' => '1230',
                    'executed_at' => '2010-01-05 10:30:21',
                    'execution_time' => '31490',
                ],
        ], $rows);
    }

    public function testCompleteWillAlwaysCastTimeToInteger(): void
    {
        $config     = new TableMetadataStorageConfiguration();
        $executedAt = new DateTimeImmutable('2010-01-05 10:30:21');

        $connection = $this->getSqliteConnection();
        $pdo        = $connection->getNativeConnection();

        $connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([
                ['pdo' => $pdo],
                new SQLiteDriver(),
            ])
            ->onlyMethods(['insert'])
            ->getMock();

        $connection
            ->expects(self::once())
            ->method('insert')
            ->willReturnCallback(static function ($table, $params, $types) use ($config, $executedAt): int {
                self::assertSame($config->getTableName(), $table);
                self::assertSame([
                    $config->getVersionColumnName() => '1230',
                    $config->getExecutedAtColumnName() => $executedAt,
                    $config->getExecutionTimeColumnName() => 31000,
                ], $params);
                self::assertSame([
                    Types::STRING,
                    Types::DATETIME_MUTABLE,
                    Types::INTEGER,
                ], $types);

                return 1;
            });

        $storage = new TableMetadataStorage($connection, new AlphabeticalComparator(), $config);
        $storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, $executedAt);
        $result->setTime(31.0);

        $storage->complete($result);
    }

    public function testRead(): void
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

    public function testReadIsSorted(): void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('9000'), Direction::UP);
        $this->storage->complete($result);

        $result = new ExecutionResult(new Version('1000'), Direction::UP);
        $this->storage->complete($result);

        $executedMigrations = $this->storage->getExecutedMigrations();

        self::assertEquals(new Version('1000'), $executedMigrations->getItems()[0]->getVersion());
        self::assertEquals(new Version('9000'), $executedMigrations->getItems()[1]->getVersion());
    }

    public function testCompleteDownRemovesTheRow(): void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        $sql = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        self::assertCount(1, $this->connection->fetchAllAssociative($sql));

        $result = new ExecutionResult(new Version('1230'), Direction::DOWN, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        self::assertCount(0, $this->connection->fetchAllAssociative($sql));
    }

    public function testReset(): void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));
        $result->setTime(31.0);
        $this->storage->complete($result);

        $sql = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        self::assertCount(1, $this->connection->fetchAllAssociative($sql));

        $this->storage->reset();

        self::assertCount(0, $this->connection->fetchAllAssociative($sql));
    }

    public function testResetWithEmptySchema(): void
    {
        $this->storage->ensureInitialized();

        $this->storage->reset();

        $sql = sprintf(
            'SELECT * FROM %s',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );
        self::assertCount(0, $this->connection->fetchAllAssociative($sql));
    }

    public function testGetSql(): void
    {
        $this->storage->ensureInitialized();

        $result = new ExecutionResult(new Version('2230'), Direction::UP, new DateTimeImmutable('2010-01-05 10:30:21'));

        $queries = [...$this->storage->getSql($result)];

        self::assertCount(2, $queries);
        self::assertSame('-- Version 2230 update table metadata', $queries[0]->getStatement());
        self::assertSame(sprintf(
            "INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES ('%s', '%s', 0)",
            '2230',
            '2010-01-05 10:30:21'
        ), $queries[1]->getStatement());

        foreach ($queries as $query) {
            $this->connection->executeStatement($query->getStatement());
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE version = 2230',
            $this->connection->getDatabasePlatform()->quoteIdentifier($this->config->getTableName())
        );

        self::assertCount(1, $this->connection->fetchAllAssociative($sql));

        $result = new ExecutionResult(new Version('2230'), Direction::DOWN, new DateTimeImmutable('2010-01-05 10:30:21'));

        $queries = [...$this->storage->getSql($result)];

        self::assertCount(2, $queries);
        self::assertSame('-- Version 2230 update table metadata', $queries[0]->getStatement());
        self::assertSame(sprintf(
            "DELETE FROM doctrine_migration_versions WHERE version = '%s'",
            '2230'
        ), $queries[1]->getStatement());

        foreach ($queries as $query) {
            $this->connection->executeStatement($query->getStatement());
        }

        self::assertCount(0, $this->connection->fetchAllAssociative($sql));
    }
}
