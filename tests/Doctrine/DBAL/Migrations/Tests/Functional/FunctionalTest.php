<?php

namespace Doctrine\DBAL\Migrations\Tests\Functional;

use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
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
    }

    public function testMigrateUp()
    {
        $version = new \Doctrine\DBAL\Migrations\Version($this->config, 1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertTrue($schema->hasTable('foo'));
        $this->assertTrue($schema->getTable('foo')->hasColumn('id'));
        $this->assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateDown()
    {
        $version = new \Doctrine\DBAL\Migrations\Version($this->config, 1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');

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
        $version = new \Doctrine\DBAL\Migrations\Version($this->config, 1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationSkipMigration');

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('foo'));

        $this->assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateSeveralSteps()
    {
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');
        $this->config->registerMigration(2, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationSkipMigration');
        $this->config->registerMigration(3, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $this->assertEquals(0, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrationsToExecute('up', 3);

        $this->assertEquals(3, count($migrations));
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp', $migrations[1]->getMigration());
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationSkipMigration', $migrations[2]->getMigration());
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther', $migrations[3]->getMigration());

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
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
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');
        $this->config->registerMigration(2, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationSkipMigration');
        $this->config->registerMigration(3, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
        $migration->migrate();

        $this->assertEquals(3, $this->config->getCurrentVersion());
        $migrations = $this->config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[2]->isMigrated());
        $this->assertTrue($migrations[3]->isMigrated());
    }

    public function testDryRunMigration()
    {
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');
        $this->config->registerMigration(2, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationSkipMigration');
        $this->config->registerMigration(3, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
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
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');
        $this->config->registerMigration(2, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationSkipMigration');
        $this->config->registerMigration(3, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
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
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest');

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
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

    public function testVersionInDatabaseWithoutRegisteredMigrationStillMigrates()
    {
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest');
        $this->config->registerMigration(10, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
        $migration->migrate();

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest');
        $config->registerMigration(2, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');

        $migration = new \Doctrine\DBAL\Migrations\Migration($config);
        $migration->migrate();

        $migrations = $config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[2]->isMigrated());

        $this->assertEquals(2, $config->getCurrentVersion());
    }

    public function testInterweavedMigrationsAreExecuted()
    {
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest');
        $this->config->registerMigration(3, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
        $migration->migrate();

        $migrations = $this->config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[3]->isMigrated());
        $this->assertEquals(3, $this->config->getCurrentVersion());

        $config = new Configuration($this->connection);
        $config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $config->setMigrationsDirectory('.');
        $config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest');
        $config->registerMigration(2, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateUp');
        $config->registerMigration(3, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $this->assertEquals(1, count($config->getMigrationsToExecute('up', 3)));
        $migrations = $config->getMigrationsToExecute('up', 3);
        $this->assertTrue(isset($migrations[2]));
        $this->assertEquals(2, $migrations[2]->getVersion());

        $migration = new \Doctrine\DBAL\Migrations\Migration($config);
        $migration->migrate();

        $migrations = $config->getMigrations();
        $this->assertTrue($migrations[1]->isMigrated());
        $this->assertTrue($migrations[2]->isMigrated());
        $this->assertTrue($migrations[3]->isMigrated());

        $this->assertEquals(3, $config->getCurrentVersion());
    }

    public function testMigrateToCurrentVersionReturnsEmpty()
    {
        $this->config->registerMigration(1, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest');
        $this->config->registerMigration(2, 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther');

        $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
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
            $migration = new \Doctrine\DBAL\Migrations\Migration($this->config);
            $this->config->registerMigration($key, $class);
            $sql = $migration->migrate();
            $this->assertCount(1, $sql, 'should have executed one migration');
        }

    }

    public function provideTestMigrationNames()
    {
        return [
            [[
                '20120228123443' => 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest',
                '20120228114838' => 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther',
            ]],
            [[
                '002Test' => 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateAddSqlTest',
                '001Test' => 'Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrationMigrateFurther',
            ]]
        ];
    }
}
