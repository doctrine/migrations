<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Schema\Schema;

class ConfigurationTest extends MigrationTestCase
{
    public function testGetConnection()
    {
        $conn = $this->getSqliteConnection();
        $config = new Configuration($conn);

        $this->assertSame($conn, $config->getConnection());
    }

    public function testValidateMigrationsNamespaceRequired()
    {
        $config = new Configuration($this->getSqliteConnection());

        $this->setExpectedException("Doctrine\DBAL\Migrations\MigrationException", "Migrations namespace must be configured in order to use Doctrine migrations.");
        $config->validate();
    }

    public function testValidateMigrationsDirectoryRequired()
    {
        $config = new Configuration($this->getSqliteConnection());
        $config->setMigrationsNamespace("DoctrineMigrations\\");

        $this->setExpectedException("Doctrine\DBAL\Migrations\MigrationException", "Migrations directory must be configured in order to use Doctrine migrations.");
        $config->validate();
    }

    public function testValidateMigrations()
    {
        $config = new Configuration($this->getSqliteConnection());
        $config->setMigrationsNamespace("DoctrineMigrations\\");
        $config->setMigrationsDirectory(sys_get_temp_dir());

        $config->validate();
    }

    public function testSetGetName()
    {
        $config = new Configuration($this->getSqliteConnection());
        $config->setName('Test');

        $this->assertEquals('Test', $config->getName());
    }

    public function testMigrationsTable()
    {
        $config = new Configuration($this->getSqliteConnection());

        $this->assertEquals("doctrine_migration_versions", $config->getMigrationsTableName());
    }

    public function testEmptyProjectDefaults()
    {
        $config = $this->getSqliteConfiguration();
        $this->assertEquals(0, $config->getCurrentVersion(), "current version 0");
        $this->assertEquals(0, $config->getLatestVersion(), "latest version 0");
        $this->assertEquals(0, $config->getNumberOfAvailableMigrations(), "number of available migrations 0");
        $this->assertEquals(0, $config->getNumberOfExecutedMigrations(), "number of executed migrations 0");
        $this->assertEquals(array(), $config->getMigrations());
    }

    public function testGetUnknownVersion()
    {
        $config = $this->getSqliteConfiguration();

        $this->setExpectedException('Doctrine\DBAL\Migrations\MigrationException', 'Could not find migration version 1234');
        $config->getVersion(1234);
    }

    public function testRegisterMigration()
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');

        $this->assertEquals(1, count($config->getMigrations()), "One Migration registered.");
        $this->assertTrue($config->hasVersion(1234));

        $version = $config->getVersion(1234);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Version', $version);
        $this->assertEquals(1234, $version->getVersion());
        $this->assertFalse($version->isMigrated());
    }

    public function testRegisterMigrations()
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations(array(
            1234 => 'Doctrine\DBAL\Migrations\Tests\ConfigMigration',
            1235 => 'Doctrine\DBAL\Migrations\Tests\Config2Migration',
        ));

        $this->assertEquals(2, count($config->getMigrations()), "Two Migration registered.");

        $version = $config->getVersion(1234);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Version', $version);

        $version = $config->getVersion(1235);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Version', $version);
    }

    public function testRegisterDuplicateVersion()
    {
        $config = $this->getSqliteConfiguration();

        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');

        $this->setExpectedException('Doctrine\DBAL\Migrations\MigrationException', 'Migration version 1234 already registered with class Doctrine\DBAL\Migrations\Version');
        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
    }

    public function testGetAvailableVersions()
    {
        $config = $this->getSqliteConfiguration();

        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->assertEquals(array(1234), $config->getAvailableVersions());
    }
}

class ConfigMigration extends \Doctrine\DBAL\Migrations\AbstractMigration
{
    public function down(Schema $schema)
    {
        
    }
    
    public function up(Schema $schema)
    {

    }
}

class Config2Migration extends \Doctrine\DBAL\Migrations\AbstractMigration
{
    public function down(Schema $schema)
    {

    }

    public function up(Schema $schema)
    {

    }
}