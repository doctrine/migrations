<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

abstract class AbstractConfigurationTest extends \Doctrine\DBAL\Migrations\Tests\MigrationTestCase
{
    /**
     * @var \Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    abstract public function loadConfiguration();

    public function testMigrationDirectory()
    {
        $config = $this->loadConfiguration();
        $this->assertEquals("/path/to/migrations/classes/DoctrineMigrations", $config->getMigrationsDirectory());
    }

    public function testMigrationNamespace()
    {
        $config = $this->loadConfiguration();
        $this->assertEquals("DoctrineMigrationsTest", $config->getMigrationsNamespace());
    }

    public function testMigrationName()
    {
        $config = $this->loadConfiguration();
        $this->assertEquals("Doctrine Sandbox Migrations", $config->getName());
    }

    public function testMigrationsTable()
    {
        $config = $this->loadConfiguration();
        $this->assertEquals('doctrine_migration_versions_test', $config->getMigrationsTableName());
    }
}