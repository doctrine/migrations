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
use Doctrine\Migrations\Migration;
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\EventVerificationListener;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateAddSqlPostAndPreUpAndDownTest;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateAddSqlTest;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateWithDataModification;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationMigrateFurther;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationMigrateUp;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationModifySchemaInPreAndPost;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationSkipMigration;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\VersionExecutor;
use Symfony\Component\Process\Process;
use function file_exists;
use function get_class_methods;
use function in_array;
use function sprintf;
use function unlink;

class FunctionalTest extends MigrationTestCase
{
    /** @var Configuration */
    private $config;

    /** @var Connection */
    private $connection;

    protected function setUp() : void
    {
        $this->connection = $this->getSqliteConnection();
        $this->config     = self::createConfiguration($this->connection);
    }

    public function testDoctrineMigrationsBin() : void
    {
        $process = new Process(__DIR__ . '/../../../../../bin/doctrine-migrations');
        $process->run();

        self::assertTrue($process->isSuccessful());

        $output = $process->getOutput();

        self::assertNotEmpty($output);

        self::assertContains('migrations:execute', $output);
        self::assertContains('migrations:generate', $output);
        self::assertContains('migrations:latest', $output);
        self::assertContains('migrations:migrate', $output);
        self::assertContains('migrations:status', $output);
        self::assertContains('migrations:up-to-date', $output);
        self::assertContains('migrations:version', $output);
    }

    public function testMigrateUp() : void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationMigrateUp::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'));
        self::assertTrue($schema->getTable('foo')->hasColumn('id'));
        self::assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateDown() : void
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

    public function testSkipMigrateUp() : void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationSkipMigration::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));

        self::assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateSeveralSteps() : void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        self::assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrationsToExecute('up', '3');

        self::assertCount(3, $migrations);
        self::assertInstanceOf(MigrationMigrateUp::class, $migrations['1']->getMigration());
        self::assertInstanceOf(MigrationSkipMigration::class, $migrations['2']->getMigration());
        self::assertInstanceOf(MigrationMigrateFurther::class, $migrations['3']->getMigration());

        $migration = new Migration($this->config);
        $migration->migrate('3');

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'));
        self::assertTrue($schema->hasTable('bar'));

        self::assertEquals(3, $this->config->getCurrentVersion());
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());
    }

    public function testMigrateToLastVersion() : void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        self::assertEquals(3, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());
    }

    public function testDryRunMigration() : void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate('3', true);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));
        self::assertFalse($schema->hasTable('bar'));

        self::assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertFalse($migrations['1']->isMigrated());
        self::assertFalse($migrations['2']->isMigrated());
        self::assertFalse($migrations['3']->isMigrated());
    }

    public function testMigrateDownSeveralSteps() : void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);
        $this->config->registerMigration('2', MigrationSkipMigration::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate('3');
        self::assertEquals(3, $this->config->getCurrentVersion());
        $migration->migrate('0');

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));
        self::assertFalse($schema->hasTable('bar'));

        self::assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertFalse($migrations['1']->isMigrated());
        self::assertFalse($migrations['2']->isMigrated());
        self::assertFalse($migrations['3']->isMigrated());
    }

    public function testAddSql() : void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);

        $migration = new Migration($this->config);
        $migration->migrate('1');

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('test_add_sql_table'));
        $check = $this->config->getConnection()->fetchAll('select * from test_add_sql_table');
        self::assertNotEmpty($check);
        self::assertEquals('test', $check[0]['test']);

        $migration->migrate('0');
        self::assertFalse($migrations['1']->isMigrated());
        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('test_add_sql_table'));
    }

    public function testAddSqlInPostUp() : void
    {
        $this->config->registerMigration('1', MigrateAddSqlPostAndPreUpAndDownTest::class);
        $tableName = MigrateAddSqlPostAndPreUpAndDownTest::TABLE_NAME;

        $this->config->getConnection()->executeQuery(sprintf('CREATE TABLE IF NOT EXISTS %s (test INT)', $tableName));

        $migration = new Migration($this->config);
        $migration->migrate('1');

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());

        $check = $this->config->getConnection()->fetchColumn(
            sprintf('select SUM(test) as sum from %s', $tableName)
        );

        self::assertNotEmpty($check);
        self::assertEquals(3, $check);

        $migration->migrate('0');
        self::assertFalse($migrations['1']->isMigrated());

        $check = $this->config->getConnection()->fetchColumn(
            sprintf('select SUM(test) as sum from %s', $tableName)
        );

        self::assertNotEmpty($check);
        self::assertEquals(12, $check);

        $this->config->getConnection()->executeQuery(sprintf('DROP TABLE %s ', $tableName));
    }

    public function testVersionInDatabaseWithoutRegisteredMigrationStillMigrates() : void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);
        $this->config->registerMigration('10', MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration('1', MigrateAddSqlTest::class);
        $config->registerMigration('2', MigrationMigrateUp::class);
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        $migration = new Migration($config);
        $migration->migrate();

        $migrations = $config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());

        self::assertEquals(2, $config->getCurrentVersion());
    }

    public function testInterweavedMigrationsAreExecuted() : void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);
        $this->config->registerMigration('3', MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());
        self::assertEquals(3, $this->config->getCurrentVersion());

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
        self::assertEquals(2, $migrations['2']->getVersion());

        $migration = new Migration($config);
        $migration->migrate();

        $migrations = $config->getMigrations();
        self::assertTrue($migrations['1']->isMigrated());
        self::assertTrue($migrations['2']->isMigrated());
        self::assertTrue($migrations['3']->isMigrated());

        self::assertEquals(3, $config->getCurrentVersion());
    }

    public function testMigrateToCurrentVersionReturnsEmpty() : void
    {
        $this->config->registerMigration('1', MigrateAddSqlTest::class);
        $this->config->registerMigration('2', MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $sql = $migration->migrate();

        self::assertEquals([], $sql);
    }

    /**
     * @param string[] $migrations
     *
     * @see https://github.com/doctrine/migrations/issues/61
     * @group regression
     * @dataProvider provideTestMigrationNames
     */
    public function testMigrateExecutesOlderVersionsThatHaveNetYetBeenMigrated(array $migrations) : void
    {
        foreach ($migrations as $key => $class) {
            $migration = new Migration($this->config);
            $this->config->registerMigration((string) $key, $class);
            $sql = $migration->migrate();
            self::assertCount(1, $sql, 'should have executed one migration');
        }
    }

    public function testSchemaChangeAreNotTakenIntoAccountInPreAndPostMethod() : void
    {
        $version = $this->createTestVersion($this->config, '1', MigrationModifySchemaInPreAndPost::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $queries = $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'), 'The table foo is not present');
        self::assertFalse($schema->hasTable('bar'), 'The table bar is present');
        self::assertFalse($schema->hasTable('bar2'), 'The table bar2 is present');

        foreach ($queries as $query) {
            self::assertNotContains('bar', $query);
            self::assertNotContains('bar2', $query);
        };

        $queries = $version->execute('down');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'), 'The table foo is present');
        self::assertFalse($schema->hasTable('bar'), 'The table bar is present');
        self::assertFalse($schema->hasTable('bar2'), 'The table bar2 is present');

        foreach ($queries as $query) {
            self::assertNotContains('bar', $query);
            self::assertNotContains('bar2', $query);
        };
    }

    /**
     * @return string[][]
     */
    public function provideTestMigrationNames() : array
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

    public function testMigrationWorksWhenNoCallsAreMadeToTheSchema() : void
    {
        $schema             = $this->createMock(Schema::class);
        $schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);

        $schemaDiffProvider->method('createFromSchema')->willReturn($schema);
        $schemaDiffProvider->method('getSqlDiffToMigrate')->willReturn([]);
        $schemaDiffProvider
            ->method('createToSchema')
            ->willReturnCallback(function () use ($schema) {
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

            $schema->expects($this->never())->method($method);
        }
    }

    public function testSuccessfulMigrationDispatchesTheExpectedEvents() : void
    {
        $this->config->registerMigration('1', MigrationMigrateUp::class);

        $this->config->getConnection()->getEventManager()->addEventSubscriber(
            $listener = new EventVerificationListener()
        );

        $migration = new Migration($this->config);
        $migration->migrate();

        self::assertCount(4, $listener->events);

        foreach ([
            Events::onMigrationsMigrating,
            Events::onMigrationsMigrated,
            Events::onMigrationsVersionExecuting,
            Events::onMigrationsVersionExecuted,
        ] as $eventName) {
            self::assertArrayHasKey($eventName, $listener->events);
        }
    }

    public function testSkippedMigrationsDispatchesTheExpectedEvents() : void
    {
        $this->config->registerMigration('1', MigrationSkipMigration::class);

        $this->config->getConnection()->getEventManager()->addEventSubscriber(
            $listener = new EventVerificationListener()
        );

        $migration = new Migration($this->config);
        $migration->migrate();

        self::assertCount(4, $listener->events);

        foreach ([
            Events::onMigrationsMigrating,
            Events::onMigrationsMigrated,
            Events::onMigrationsVersionExecuting,
            Events::onMigrationsVersionSkipped,
        ] as $eventName) {
            self::assertArrayHasKey($eventName, $listener->events);
        }
    }

    /**
     * This uses a file path based SQL database to actually test the closing
     * of a connection with autocommit mode and re-opening it.
     * @group https://github.com/doctrine/migrations/issues/496
     */
    public function testMigrateWithConnectionWithAutoCommitOffStillPersistsChanges() : void
    {
        $listener            = new AutoCommitListener();
        list($conn, $config) = self::fileConnectionAndConfig();
        $config->registerMigration('1', MigrateWithDataModification::class);
        $migration = new Migration($config);
        $conn->getEventManager()->addEventSubscriber($listener);
        $conn->exec('CREATE TABLE test_data_migration (test INTEGER)');
        $conn->commit();

        $migration->migrate();

        self::assertCount(3, $conn->fetchAll('SELECT * FROM test_data_migration'), 'migration did not execute');
        $conn->close();
        self::assertCount(3, $conn->fetchAll('SELECT * FROM test_data_migration'));
    }

    /**
     * @return Connection|Configuration[]
     */
    private static function fileConnectionAndConfig() : array
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

    private static function createConfiguration(Connection $conn) : Configuration
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
    ) : Version {
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

        $versionExecutor = new VersionExecutor(
            $this->config,
            $this->connection,
            $schemaDiffProvider,
            $this->config->getOutputWriter(),
            $parameterFormatter
        );

        return new Version($configuration, $versionName, $className, $versionExecutor);
    }
}
