<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\Configuration\Configuration;

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
}