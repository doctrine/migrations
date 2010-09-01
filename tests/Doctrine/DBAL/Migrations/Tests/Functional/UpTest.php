<?php

namespace Doctrine\DBAL\Migrations\Tests\Functional;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

class UpTest extends \Doctrine\DBAL\Migrations\Tests\MigrationTestCase
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Connection
     */
    private $connection;

    public function setUp()
    {
        $params = array('driver' => 'pdo_sqlite', 'memory' => true);
        $this->connection = DriverManager::getConnection($params);
        $this->config = new Configuration($this->connection);
        $this->config->setMigrationsNamespace('Doctrine\DBAL\Migrations\Tests\Functional');
        $this->config->setMigrationsDirectory('.');
    }

    public function testMigrateUp()
    {
        $version = new \Doctrine\DBAL\Migrations\Version($this->config, 1, 'Doctrine\DBAL\Migrations\Tests\Functional\MigrationMigrateUp');

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');

        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertTrue($schema->hasTable('foo'));
        $this->assertTrue($schema->getTable('foo')->hasColumn('id'));
        $this->assertTrue($this->config->hasVersionMigrated($version));
    }

    public function testMigrateDown()
    {
        $version = new \Doctrine\DBAL\Migrations\Version($this->config, 1, 'Doctrine\DBAL\Migrations\Tests\Functional\MigrationMigrateUp');

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

    public function testSKipMigrateUp()
    {
        $version = new \Doctrine\DBAL\Migrations\Version($this->config, 1, 'Doctrine\DBAL\Migrations\Tests\Functional\MigrationSkipMigration');

        $this->assertFalse($this->config->hasVersionMigrated($version));
        $version->execute('up');
        
        $schema = $this->connection->getSchemaManager()->createSchema();
        $this->assertFalse($schema->hasTable('foo'));

        $this->assertTrue($this->config->hasVersionMigrated($version));
    }
}

class MigrationMigrateUp extends \Doctrine\DBAL\Migrations\AbstractMigration
{
    public function down(Schema $schema)
    {
      $schema->dropTable('foo');
    }
    
    public function up(Schema $schema)
    {
        $table = $schema->createTable('foo');
        $table->addColumn('id', 'integer');
    }
}

class MigrationSkipMigration extends MigrationMigrateUp
{
    public function preUp(Schema $schema) {
        $this->skipIf(true);
    }
}