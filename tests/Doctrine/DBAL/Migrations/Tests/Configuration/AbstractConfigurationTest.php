<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

abstract class AbstractConfigurationTest extends \Doctrine\DBAL\Migrations\Tests\MigrationTestCase
{
    /**
     * @param string $config Specify config to load.
     * @return \Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration
     */
    abstract public function loadConfiguration($config = '');

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
        $this->setExpectedException('Doctrine\DBAL\Migrations\MigrationException');
        $config->load($config->getFile());
    }

    public function testVersionsOrganizationNoConfig()
    {
        $config = $this->loadConfiguration();
        $this->assertFalse($config->versionsAreOrganizedByYear());
        $this->assertFalse($config->versionsAreOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYear()
    {
        $config = $this->loadConfiguration('organize_by_year');
        $this->assertTrue($config->versionsAreOrganizedByYear());
        $this->assertFalse($config->versionsAreOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYearAndMonth()
    {
        $config = $this->loadConfiguration('organize_by_year_and_month');
        $this->assertFalse($config->versionsAreOrganizedByYear());
        $this->assertTrue($config->versionsAreOrganizedByYearAndMonth());
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     */
    public function testVersionsOrganizationInvalid()
    {
        $this->loadConfiguration('organize_invalid');
    }
}
