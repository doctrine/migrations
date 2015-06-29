<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

abstract class AbstractConfigurationTest extends \Doctrine\DBAL\Migrations\Tests\MigrationTestCase
{
    /**
     * @return \Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration
     */
    abstract public function loadConfiguration();

    public function testMigrationDirectory()
    {
        $config = $this->loadConfiguration();
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . '_files', $config->getMigrationsDirectory());
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

    public function testThrowExceptionIfAlreadyLoaded()
    {
        /** @var $config \Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration */
        $config = $this->loadConfiguration();
        $this->setExpectedException('Doctrine\DBAL\Migrations\Exception\MigrationException');
        $config->load($config->getFile());
    }

}
