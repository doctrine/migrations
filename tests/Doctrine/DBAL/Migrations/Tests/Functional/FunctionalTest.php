<?php

namespace Doctrine\DBAL\Migrations\Tests\Functional;

use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlPostAndPreUpAndDownTest;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
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

    public function setUp()
    {
        $this->connection = $this->getSqliteConnection();
        $this->config = new Configuration($this->connection);
        $this->config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $this->config->setMigrationsDirectory('.');
        $this->config->setMigrationsTableName('test_migrations_table');
        $this->config->setMigrationsColumnName('current_version');
    }

    public function testMigrateUp()
    {
        $version = new Version($this->config, 1, MigrationMigrateUp::class);

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertTrue($schema->hasTable('foo'));
        $this->assertTrue($schema->getTable('foo')->hasColumn('id'));
        $this->assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateDown()
    {
        $version = new Version($this->config, 1, MigrationMigrateUp::class);

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertTrue($schema->hasTable('foo'));
        $this->assertTrue($schema->getTable('foo')->hasColumn('id'));
        $this->assertTrue($this->config->hasVersionMigrated($version));

        $version->execute('down');
        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('foo'));
        $this->assertFalse($this->config->hasVersionMigrated($version));

    }

    public function testSkipMigrateUp()
    {
        $version = new Version($this->config, 1, MigrationSkipMigration::class);

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('foo'));

        $this->assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateSeveralSteps()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $this->assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrationsToExecute('up', 3);

        $this->assertEquals(3, count($migrations));
        $this->assertInstanceOf(MigrationMigrateUp::class, $migrations[1]->getMigration());
        $this->assertInstanceOf(MigrationSkipMigration::class, $migrations[2]->getMigration());
        $this->assertInstanceOf(MigrationMigrateFurther::class, $migrations[3]->getMigration());

        $migration = new Migration($this->config);
        $migration->migrate(3);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        $this->assertTrue($schema->hasTable('foo'));
        $this->assertTrue($schema->hasTable('bar'));

        $this->assertEquals(3, $this->config->getCurrentVersion());
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[2]->isMigrated());
        $this->assertTrue($migrations[3]->isMigrated());
    }

    public function testMigrateToLastVersion()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $this->assertEquals(3, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[2]->isMigrated());
        $this->assertTrue($migrations[3]->isMigrated());
    }

    public function testDryRunMigration()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate(3, true);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('foo'));
        $this->assertFalse($schema->hasTable('bar'));

        $this->assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        $this->assertFalse($migrations[1]->isMigrated());
        $this->assertFalse($migrations[2]->isMigrated());
        $this->assertFalse($migrations[3]->isMigrated());
    }

    public function testMigrateDownSeveralSteps()
    {
        $this->config->registerMigration(1, MigrationMigrateUp::class);
        $this->config->registerMigration(2, MigrationSkipMigration::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate(3);
        $this->assertEquals(3, $this->config->getCurrentVersion());
        $migration->migrate(0);

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('foo'));
        $this->assertFalse($schema->hasTable('bar'));

        $this->assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        $this->assertFalse($migrations[1]->isMigrated());
        $this->assertFalse($migrations[2]->isMigrated());
        $this->assertFalse($migrations[3]->isMigrated());
    }

    public function testAddSql()
    {
        $this->config->registerMigration(1, MigrateAddSqlTest::class);

        $migration = new Migration($this->config);
        $migration->migrate(1);

        $migrations = $this->config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());

        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        $this->assertTrue($schema->hasTable('test_add_sql_table'));
        $check = $this->config->getConnection()->fetchAll('select * from test_add_sql_table');
        $this->assertNotEmpty($check);
        $this->assertEquals('test', $check[0]['test']);

        $migration->migrate(0);
        $this->assertFalse($migrations[1]->isMigrated());
        $schema = $this->config->getConnection()->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('test_add_sql_table'));
    }

    public function testAddSqlInPostUp()
    {
        $this->config->registerMigration(1, MigrateAddSqlPostAndPreUpAndDownTest::class);
        $tableName = MigrateAddSqlPostAndPreUpAndDownTest::TABLE_NAME;

        $this->config->getConnection()->executeQuery(sprintf("CREATE TABLE IF NOT EXISTS %s (test INT)", $tableName));

        $migration = new Migration($this->config);
        $migration->migrate(1);

        $migrations = $this->config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());

        $check = $this->config->getConnection()->fetchColumn("select SUM(test) as sum from $tableName");

        $this->assertNotEmpty($check);
        $this->assertEquals(3, $check);

        $migration->migrate(0);
        $this->assertFalse($migrations[1]->isMigrated());
        $check = $this->config->getConnection()->fetchColumn("select SUM(test) as sum from $tableName");
        $this->assertNotEmpty($check);
        $this->assertEquals(12, $check);


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
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[2]->isMigrated());

        $this->assertEquals(2, $config->getCurrentVersion());
    }

    public function testInterweavedMigrationsAreExecuted()
    {
        $this->config->registerMigration(1, MigrateAddSqlTest::class);
        $this->config->registerMigration(3, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $migrations = $this->config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[3]->isMigrated());
        $this->assertEquals(3, $this->config->getCurrentVersion());

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration(1, MigrateAddSqlTest::class);
        $config->registerMigration(2, MigrationMigrateUp::class);
        $config->registerMigration(3, MigrationMigrateFurther::class);
        $config->setMigrationsTableName('test_migrations_table');
        $config->setMigrationsColumnName('current_version');

        $this->assertEquals(1, count($config->getMigrationsToExecute('up', 3)));
        $migrations = $config->getMigrationsToExecute('up', 3);
        $this->assertTrue(isset($migrations[2]));
        $this->assertEquals(2, $migrations[2]->getVersion());

        $migration = new Migration($config);
        $migration->migrate();

        $migrations = $config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[2]->isMigrated());
        $this->assertTrue($migrations[3]->isMigrated());

        $this->assertEquals(3, $config->getCurrentVersion());
    }

    public function testMigrateToCurrentVersionReturnsEmpty()
    {
        $this->config->registerMigration(1, MigrateAddSqlTest::class);
        $this->config->registerMigration(2, MigrationMigrateFurther::class);

        $migration = new Migration($this->config);
        $migration->migrate();

        $sql = $migration->migrate();

        $this->assertEquals([], $sql);
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
            $this->assertCount(1, $sql, 'should have executed one migration');
        }

    }

    public function testSchemaChangeAreNotTakenIntoAccountInPreAndPostMethod()
    {
        $version = new Version($this->config, 1, MigrationModifySchemaInPreAndPost::class);

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $queries = $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertTrue($schema->hasTable('foo'), 'The table foo is not present');
        $this->assertFalse($schema->hasTable('bar'), 'The table bar is present');
        $this->assertFalse($schema->hasTable('bar2'), 'The table bar2 is present');

        foreach($queries as $query) {
            $this->assertNotContains('bar', $query);
            $this->assertNotContains('bar2', $query);
        };

        $queries = $version->execute('down');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('foo'), 'The table foo is present');
        $this->assertFalse($schema->hasTable('bar'), 'The table bar is present');
        $this->assertFalse($schema->hasTable('bar2'), 'The table bar2 is present');

        foreach($queries as $query) {
            $this->assertNotContains('bar', $query);
            $this->assertNotContains('bar2', $query);
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

        $schema = $this->getMock(Schema::class);
        $schemaDiffProvider = $this->getMock(SchemaDiffProviderInterface::class);

        $schemaDiffProvider->expects(self::any())->method('createFromSchema')->willReturn($schema);
        $schemaDiffProvider->expects(self::any())->method('getSqlDiffToMigrate')->willReturn([]);
        $schemaDiffProvider
            ->expects(self::any())
            ->method('createToSchema')
            ->willReturnCallback(function () use ($schema) { return $schema; });

        $version = new Version($this->config, 1, MigrateNotTouchingTheSchema::class, $schemaDiffProvider);
        $version->execute('up');

        $methods = get_class_methods(Schema::class);
        foreach($methods as $method) {
            $schema->expects($this->never())->method($method);
        }
    }
}
