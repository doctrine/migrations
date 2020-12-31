<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Functional;

use Doctrine\DBAL\Configuration as DbalConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Event\Listeners\AutoCommitListener;
use Doctrine\Migrations\Events;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\EventVerificationListener;
use Doctrine\Migrations\Tests\Stub\Functional\DryRun\DryRun1;
use Doctrine\Migrations\Tests\Stub\Functional\DryRun\DryRun2;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateAddSqlPostAndPreUpAndDownTest;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateAddSqlTest;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateWithDataModification;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationMigrateFurther;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationMigrateUp;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationModifySchemaInPreAndPost;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationSkipMigration;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Process\Process;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;

use function assert;
use function file_exists;
use function get_class_methods;
use function in_array;
use function is_array;
use function sprintf;
use function strtotime;
use function unlink;

class FunctionalTest extends MigrationTestCase
{
    /** @var Configuration */
    private $config;

    /** @var Connection */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = $this->getSqliteConnection();
        $this->config     = self::createConfiguration($this->connection);
    }

    /**
     * @requires OS Linux|Darwin
     */
    public function testDoctrineMigrationsBin(): void
    {
        $process = new Process([__DIR__ . '/../../../../../bin/doctrine-migrations']);
        $process->run();

        self::assertTrue($process->isSuccessful());

        $output = $process->getOutput();

        self::assertNotEmpty($output);

        self::assertStringContainsString('migrations:execute', $output);
        self::assertStringContainsString('migrations:generate', $output);
        self::assertStringContainsString('migrations:latest', $output);
        self::assertStringContainsString('migrations:migrate', $output);
        self::assertStringContainsString('migrations:status', $output);
        self::assertStringContainsString('migrations:up-to-date', $output);
        self::assertStringContainsString('migrations:version', $output);
    }

    public function testMigrateUp(): void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationMigrateUp::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'));
        self::assertTrue($schema->getTable('foo')->hasColumn('id'));
        self::assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateDown(): void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationMigrateUp::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'));
        self::assertTrue($schema->getTable('foo')->hasColumn('id'));
        self::assertTrue($this->config->hasVersionMigrated($version));

        $version->execute('down');
        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));
        self::assertFalse($this->config->hasVersionMigrated($version));
    }

    public function testSkipMigrateUp(): void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationSkipMigration::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));

        self::assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateSeveralSteps(): void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        self::assertSame('0', $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrationsToExecute('up', '3');

        self::assertCount(3, $migrations);
        self::assertInstanceOf(MigrationMigrateUp::class, $migrations['1']->getMigration());
        self::assertInstanceOf(MigrationSkipMigration::class, $migrations['2']->getMigration());
        self::assertInstanceOf(MigrationMigrateFurther::class, $migrations['3']->getMigration());

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate('3');

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'));
        self::assertTrue($schema->hasTable('bar'));

        self::assertSame('3', $this->config->getCurrentVersion());
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());
    }

    public function testMigrateToLastVersion(): void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate();

        self::assertSame('3', $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());
    }

    public function testDryRunMigration(): void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migratorConfiguration = (new MigratorConfiguration())
            ->setDryRun(true);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate('3', $migratorConfiguration);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));
        self::assertFalse($schema->hasTable('bar'));

        self::assertSame('0', $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertFalse($migrations['1']->isMigrated());
        self::assertFalse($migrations['2']->isMigrated());
        self::assertFalse($migrations['3']->isMigrated());
    }

    public function testDryRunWithTableCreatedWithSchemaInFirstMigration(): void
    {
        $this->config->registerMigration('1', DryRun1::class);
        $this->config->registerMigration('2', DryRun2::class);

        $migratorConfiguration = (new MigratorConfiguration())
            ->setDryRun(true);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate('2', $migratorConfiguration);

        $schema = $migratorConfiguration->getFromSchema();

        self::assertInstanceOf(Schema::class, $schema);
        self::assertTrue($schema->hasTable('foo'));

        $table = $schema->getTable('foo');
        self::assertTrue($table->hasColumn('bar'));
    }

    public function testMigrateDownSeveralSteps(): void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate('3');

        self::assertSame('3', $this->config->getCurrentVersion());
        $migrator->migrate('0');

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));
        self::assertFalse($schema->hasTable('bar'));

        self::assertSame('0', $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertFalse($migrations['1']->isMigrated());
        self::assertFalse($migrations['2']->isMigrated());
        self::assertFalse($migrations['3']->isMigrated());
    }

    public function testAddSql(): void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate('1');

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('test_add_sql_table'));
        $check = $this->config->getConnection()->fetchAll('select * from test_add_sql_table');
        self::assertNotEmpty($check);
        self::assertSame('test', $check[0]['test']);

        $migrator->migrate('0');
        self::assertFalse($migrations['1']->isMigrated());
        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('test_add_sql_table'));
    }

    public function testAddSqlInPostUp(): void
    {
        $this->config->registerMigration('1', MigrateAddSqlPostAndPreUpAndDownTest::class);
        $tableName = MigrateAddSqlPostAndPreUpAndDownTest::TABLE_NAME;

        $this->config->getConnection()->executeQuery(sprintf('CREATE TABLE IF NOT EXISTS %s (test INT)', $tableName));

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate('1');

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());

        $check = $this->config->getConnection()->fetchColumn(
            sprintf('select SUM(test) as sum from %s', $tableName)
        );

        self::assertNotEmpty($check);
        self::assertSame('3', $check);

        $migrator->migrate('0');
        self::assertFalse($migrations['1']->isMigrated());

        $check = $this->config->getConnection()->fetchColumn(
            sprintf('select SUM(test) as sum from %s', $tableName)
        );

        self::assertNotEmpty($check);
        self::assertSame('12', $check);

        $this->config->getConnection()->executeQuery(sprintf('DROP TABLE %s ', $tableName));
    }

    public function testVersionInDatabaseWithoutRegisteredMigrationStillMigrates(): void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);
        $this->config->registerMigration('10', MigrationMigrateFurther::class);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate();

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration('1', MigrateAddSqlTest::class);
        $config->registerMigration('2', MigrationMigrateUp::class);
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        $migrator = $this->createTestMigrator($config);
        $migrator->migrate();

        $migrations = $config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());

        self::assertSame('2', $config->getCurrentVersion());
    }

    public function testInterweavedMigrationsAreExecuted(): void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate();

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());
        self::assertSame('3', $this->config->getCurrentVersion());

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration('1', MigrateAddSqlTest::class);
        $config->registerMigration('2', MigrationMigrateUp::class);
        $config->registerMigration('3', MigrationMigrateFurther::class);
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        self::assertCount(1, $config->getMigrationsToExecute('up', '3'));
        $migrations = $config->getMigrationsToExecute('up', '3');
        self::assertArrayHasKey(2, $migrations);
        self::assertSame('2', $migrations['2']->getVersion());

        $migrator = $this->createTestMigrator($config);
        $migrator->migrate();

        $migrations = $config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());

        self::assertSame('3', $config->getCurrentVersion());
    }

    public function testMigrateToCurrentVersionReturnsEmpty(): void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);
        $this->config->registerMigration('2', MigrationMigrateFurther::class);

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate();

        $sql = $migrator->migrate();

        self::assertSame([], $sql);
    }

    /**
     * @see https://github.com/doctrine/migrations/issues/61
     *
     * @param string[] $migrations
     *
     * @group regression
     * @dataProvider provideTestMigrationNames
     */
    public function testMigrateExecutesOlderVersionsThatHaveNetYetBeenMigrated(array $migrations): void
    {
        foreach ($migrations as $key => $class) {
            $migrator = $this->createTestMigrator($this->config);
            $this->config->registerMigration((string) $key, $class);
            $sql = $migrator->migrate();
            self::assertCount(1, $sql, 'should have executed one migration');
        }
    }

    public function testSchemaChangeAreNotTakenIntoAccountInPreAndPostMethod(): void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationModifySchemaInPreAndPost::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $queries = $version->execute('up')->getSql();

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'), 'The table foo is not present');
        self::assertFalse($schema->hasTable('bar'), 'The table bar is present');
        self::assertFalse($schema->hasTable('bar2'), 'The table bar2 is present');

        foreach ($queries as $query) {
            self::assertStringNotContainsString('bar', $query);
            self::assertStringNotContainsString('bar2', $query);
        }

        $queries = $version->execute('down')->getSql();

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'), 'The table foo is present');
        self::assertFalse($schema->hasTable('bar'), 'The table bar is present');
        self::assertFalse($schema->hasTable('bar2'), 'The table bar2 is present');

        foreach ($queries as $query) {
            self::assertStringNotContainsString('bar', $query);
            self::assertStringNotContainsString('bar2', $query);
        }
    }

    /**
     * @return mixed[][]
     */
    public function provideTestMigrationNames(): array
    {
        return [
            [
                [
                    '20120228123443' => MigrateAddSqlTest::class,
                    '20120228114838' => MigrationMigrateFurther::class,
                ],
            ],
            [
                [
                    '002Test' => MigrateAddSqlTest::class,
                    '001Test' => MigrationMigrateFurther::class,
                ],
            ],
        ];
    }

    public function testMigrationWorksWhenNoCallsAreMadeToTheSchema(): void
    {
        $schema             = $this->createMock(Schema::class);
        $schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);

        $schemaDiffProvider->method('createFromSchema')->willReturn($schema);
        $schemaDiffProvider->method('getSqlDiffToMigrate')->willReturn([]);
        $schemaDiffProvider
            ->method('createToSchema')
            ->willReturnCallback(static function () use ($schema) {
                return $schema;
            });

        $version = $this->createTestVersion(
            $this->config,
            '1',
            MigrateNotTouchingTheSchema::class,
            $schemaDiffProvider
        );

        $version->execute('up');

        foreach (get_class_methods(Schema::class) as $method) {
            if (in_array($method, ['__construct', '__clone'], true)) {
                continue;
            }

            $schema->expects(self::never())->method($method);
        }
    }

    public function testSuccessfulMigrationDispatchesTheExpectedEvents(): void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);

        $this->config->getConnection()->getEventManager()->addEventSubscriber(
            $listener = new EventVerificationListener()
        );

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate();

        self::assertCount(4, $listener->events);

        foreach (
            [
                Events::onMigrationsMigrating,
                Events::onMigrationsMigrated,
                Events::onMigrationsVersionExecuting,
                Events::onMigrationsVersionExecuted,
            ] as $eventName
        ) {
            self::assertCount(1, $listener->events[$eventName]);
            self::assertArrayHasKey($eventName, $listener->events);
        }
    }

    public function testSkippedMigrationsDispatchesTheExpectedEvents(): void
    {
        $this->config->registerMigration('1', MigrationSkipMigration::class);

        $this->config->getConnection()->getEventManager()->addEventSubscriber(
            $listener = new EventVerificationListener()
        );

        $migrator = $this->createTestMigrator($this->config);
        $migrator->migrate();

        self::assertCount(4, $listener->events);

        foreach (
            [
                Events::onMigrationsMigrating,
                Events::onMigrationsMigrated,
                Events::onMigrationsVersionExecuting,
                Events::onMigrationsVersionSkipped,
            ] as $eventName
        ) {
            self::assertArrayHasKey($eventName, $listener->events);
        }
    }

    /**
     * This uses a file path based SQL database to actually test the closing
     * of a connection with autocommit mode and re-opening it.
     *
     * @group https://github.com/doctrine/migrations/issues/496
     */
    public function testMigrateWithConnectionWithAutoCommitOffStillPersistsChanges(): void
    {
        $listener        = new AutoCommitListener();
        [$conn, $config] = self::fileConnectionAndConfig();
        $config->registerMigration('1', MigrateWithDataModification::class);
        $migrator = $this->createTestMigrator($config);
        $conn->getEventManager()->addEventSubscriber($listener);
        $conn->exec('CREATE TABLE test_data_migration (test INTEGER)');
        $conn->commit();

        $migrator->migrate();

        self::assertCount(3, $conn->fetchAll('SELECT * FROM test_data_migration'), 'migration did not execute');
        $conn->close();
        self::assertCount(3, $conn->fetchAll('SELECT * FROM test_data_migration'));
    }

    public function testMigrationExecutedAt(): void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationMigrateUp::class);
        $version->execute('up');

        $row = $this->config->getConnection()
            ->fetchAssoc('SELECT * FROM test_migrations_table');

        assert(is_array($row));

        self::assertSame('1', $row['current_version']);
        self::assertTrue(isset($row['executed_at']));
        self::assertNotNull($row['executed_at']);
        self::assertNotFalse(strtotime($row['executed_at']));
    }

    /**
     * @return Connection[]|Configuration[]
     *
     * @psalm-return array{Connection,Configuration}
     */
    private static function fileConnectionAndConfig(): array
    {
        $path = __DIR__ . '/_files/db/sqlite_file_config.db';

        if (file_exists($path)) {
            @unlink($path);
        }

        $dbalConfig = new DbalConfig();
        $dbalConfig->setAutoCommit(false);
        $conn = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $path,
        ], $dbalConfig);

        return [$conn, self::createConfiguration($conn)];
    }

    private static function createConfiguration(Connection $conn): Configuration
    {
        $config = new Configuration($conn);
        $config->setMigrationsNamespace('Doctrine\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        return $config;
    }

    private function createTestVersion(
        Configuration $configuration,
        string $versionName,
        string $className,
        ?SchemaDiffProviderInterface $schemaDiffProvider = null
    ): Version {
        if ($schemaDiffProvider === null) {
            $schemaDiffProvider = new SchemaDiffProvider(
                $this->connection->getSchemaManager(),
                $this->connection->getDatabasePlatform()
            );

            $schemaDiffProvider = LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration(
                $schemaDiffProvider
            );
        }

        $parameterFormatter = new ParameterFormatter($this->connection);

        $symfonyStopwatch = new SymfonyStopwatch();
        $stopwatch        = new Stopwatch($symfonyStopwatch);

        $versionExecutor = new Executor(
            $this->config,
            $this->connection,
            $schemaDiffProvider,
            $this->config->getOutputWriter(),
            $parameterFormatter,
            $stopwatch
        );

        return new Version($configuration, $versionName, $className, $versionExecutor);
    }
}
