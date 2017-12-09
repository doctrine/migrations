<?php

namespace Doctrine\DBAL\Migrations\Tests\Functional;

use Doctrine\DBAL\Configuration as DbalConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Events;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\Event\Listeners\AutoCommitListener;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tests\Stub\EventVerificationListener;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlPostAndPreUpAndDownTest;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateWithDataModification;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationSkipMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationModifySchemaInPreAndPost;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

class FunctionalTest extends MigrationTestCase
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->getSqliteConnection();
        $this->config     = self::createConfiguration($this->connection);
    }

    public function testMigrateUp()
    {
        $version = new Version($this->config, 1, MigrationMigrateUp::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'));
        self::assertTrue($schema->getTable('foo')->hasColumn('id'));
        self::assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateDown()
    {
        $version = new Version($this->config, 1, MigrationMigrateUp::class);

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

    public function testSkipMigrateUp()
    {
        $version = new Version($this->config, 1, MigrationSkipMigration::class);

        self::assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));

        self::assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateSeveralSteps()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        self::assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrationsToExecute('up', 3);

        self::assertCount(3, $migrations);
        self::assertInstanceOf(MigrationMigrateUp::class, $migrations[1]->getMigration());
        self::assertInstanceOf(MigrationSkipMigration::class, $migrations[2]->getMigration());
        self::assertInstanceOf(MigrationMigrateFurther::class, $migrations[3]->getMigration());

        $migration = new Migration($this->config);
        $migration->migrate(3);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('foo'));
        self::assertTrue($schema->hasTable('bar'));

        self::assertEquals(3, $this->config->getCurrentVersion());
        self::assertTrue($migrations[1]->isMigrated());
        self::assertTrue($migrations[2]->isMigrated());
        self::assertTrue($migrations[3]->isMigrated());
    }

    public function testMigrateToLastVersion()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        self::assertEquals(3, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations[1]->isMigrated());
        self::assertTrue($migrations[2]->isMigrated());
        self::assertTrue($migrations[3]->isMigrated());
    }

    public function testDryRunMigration()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate(3, true);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));
        self::assertFalse($schema->hasTable('bar'));

        self::assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertFalse($migrations[1]->isMigrated());
        self::assertFalse($migrations[2]->isMigrated());
        self::assertFalse($migrations[3]->isMigrated());
    }

    public function testMigrateDownSeveralSteps()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate(3);
        self::assertEquals(3, $this->config->getCurrentVersion());
        $migration->migrate(0);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('foo'));
        self::assertFalse($schema->hasTable('bar'));

        self::assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        self::assertFalse($migrations[1]->isMigrated());
        self::assertFalse($migrations[2]->isMigrated());
        self::assertFalse($migrations[3]->isMigrated());
    }

    public function testAddSql()
    {
        $this->config->registerMigration(1, MigrateAddSqlTest::class);

        $migration = new Migration($this->config);
        $migration->migrate(1);

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations[1]->isMigrated());

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertTrue($schema->hasTable('test_add_sql_table'));
        $check = $this->config->getConnection()->fetchAll('select * from test_add_sql_table');
        self::assertNotEmpty($check);
        self::assertEquals('test', $check[0]['test']);

        $migration->migrate(0);
        self::assertFalse($migrations[1]->isMigrated());
        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        self::assertFalse($schema->hasTable('test_add_sql_table'));
    }

    public function testAddSqlInPostUp()
    {
        $this->config->registerMigration(1, MigrateAddSqlPostAndPreUpAndDownTest::class);
        $tableName = MigrateAddSqlPostAndPreUpAndDownTest::TABLE_NAME;

        $this->config->getConnection()->executeQuery(sprintf("CREATE TABLE IF NOT EXISTS %s (test INT)", $tableName));

        $migration = new Migration($this->config);
        $migration->migrate(1);

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations[1]->isMigrated());

        $check = $this->config->getConnection()->fetchColumn("select SUM(test) as sum from $tableName");

        self::assertNotEmpty($check);
        self::assertEquals(3, $check);

        $migration->migrate(0);
        self::assertFalse($migrations[1]->isMigrated());
        $check = $this->config->getConnection()->fetchColumn("select SUM(test) as sum from $tableName");
        self::assertNotEmpty($check);
        self::assertEquals(12, $check);


        $this->config->getConnection()->executeQuery(sprintf("DROP TABLE %s ", $tableName));
    }

    public function testVersionInDatabaseWithoutRegisteredMigrationStillMigrates()
    {
        $this->config->registerMigration(1, MigrateAddSqlTest::class);
        $this->config->registerMigration(10, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration(1, MigrateAddSqlTest::class);
        $config->registerMigration(2, MigrationMigrateUp::class);
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        $migration = new Migration($config);
        $migration->migrate();

        $migrations = $config->getMigrations();
        self::assertTrue($migrations[1]->isMigrated());
        self::assertTrue($migrations[2]->isMigrated());

        self::assertEquals(2, $config->getCurrentVersion());
    }

    public function testInterweavedMigrationsAreExecuted()
    {
        $this->config->registerMigration(1, MigrateAddSqlTest::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $migrations = $this->config->getMigrations();
        self::assertTrue($migrations[1]->isMigrated());
        self::assertTrue($migrations[3]->isMigrated());
        self::assertEquals(3, $this->config->getCurrentVersion());

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration(1, MigrateAddSqlTest::class);
        $config->registerMigration(2, MigrationMigrateUp::class);
        $config->registerMigration(3, MigrationMigrateFurther::class);
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        self::assertCount(1, $config->getMigrationsToExecute('up', 3));
        $migrations = $config->getMigrationsToExecute('up', 3);
        self::assertArrayHasKey(2, $migrations);
        self::assertEquals(2, $migrations[2]->getVersion());

        $migration = new Migration($config);
        $migration->migrate();

        $migrations = $config->getMigrations();
        self::assertTrue($migrations[1]->isMigrated());
        self::assertTrue($migrations[2]->isMigrated());
        self::assertTrue($migrations[3]->isMigrated());

        self::assertEquals(3, $config->getCurrentVersion());
    }

    public function testMigrateToCurrentVersionReturnsEmpty()
    {
        $this->config->registerMigration(1, MigrateAddSqlTest::class);
        $this->config->registerMigration(2, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $sql = $migration->migrate();

        self::assertEquals([], $sql);
    }

    /**
     * @see https://github.com/doctrine/migrations/issues/61
     * @group regresion
     * @dataProvider provideTestMigrationNames
     */
    public function testMigrateExecutesOlderVersionsThatHaveNetYetBeenMigrated(array $migrations)
    {
        foreach ($migrations as $key => $class) {
            $migration = new Migration($this->config);
            $this->config->registerMigration($key, $class);
            $sql = $migration->migrate();
            self::assertCount(1, $sql, 'should have executed one migration');
        }
    }

    public function testSchemaChangeAreNotTakenIntoAccountInPreAndPostMethod()
    {
        $version = new Version($this->config, 1, MigrationModifySchemaInPreAndPost::class);

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

    public function provideTestMigrationNames()
    {
        return [
            [[
                '20120228123443' => MigrateAddSqlTest::class,
                '20120228114838' => MigrationMigrateFurther::class,
            ]],
            [[
                '002Test' => MigrateAddSqlTest::class,
                '001Test' => MigrationMigrateFurther::class,
            ]]
        ];
    }

    public function testMigrationWorksWhenNoCallsAreMadeToTheSchema()
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

        $version = new Version($this->config, 1, MigrateNotTouchingTheSchema::class, $schemaDiffProvider);
        $version->execute('up');

        foreach (get_class_methods(Schema::class) as $method) {
            if (in_array($method, ['__construct', '__clone'], true)) {
                continue;
            }

            $schema->expects($this->never())->method($method);
        }
    }

    public function testSuccessfulMigrationDispatchesTheExpectedEvents()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
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

    public function testSkippedMigrationsDispatchesTheExpectedEvents()
    {
        $this->config->registerMigration(1, MigrationSkipMigration::class);
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
    public function testMigrateWithConnectionWithAutoCommitOffStillPersistsChanges()
    {
        $listener            = new AutoCommitListener();
        list($conn, $config) = self::fileConnectionAndConfig();
        $config->registerMigration(1, MigrateWithDataModification::class);
        $migration = new Migration($config);
        $conn->getEventManager()->addEventSubscriber($listener);
        $conn->exec('CREATE TABLE test_data_migration (test INTEGER)');
        $conn->commit();

        $migration->migrate();

        self::assertCount(3, $conn->fetchAll('SELECT * FROM test_data_migration'), 'migration did not execute');
        $conn->close();
        self::assertCount(3, $conn->fetchAll('SELECT * FROM test_data_migration'));
    }

    private static function fileConnectionAndConfig()
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

    private static function createConfiguration(Connection $conn)
    {
        $config = new Configuration($conn);
        $config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        return $config;
    }
}
