<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Finder\GlobFinder;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

abstract class AbstractConfigurationTest extends MigrationTestCase
{
    /**
     * @param string                   $configFileSuffix Specify config to load.
     * @param OutputWriter             $outputWriter
     * @param MigrationFinderInterface $migrationFinder
     * @return \Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration
     */
    abstract public function loadConfiguration(
        $configFileSuffix = '',
        OutputWriter $outputWriter = null,
        MigrationFinderInterface $migrationFinder = null
    );

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

    /**
     * @expectedException Doctrine\DBAL\Migrations\MigrationException
     */
    public function testFinderIsIncompatibleWithConfiguration()
    {
        $this->loadConfiguration('organize_by_year', null, new GlobFinder());
    }

    public function testSetMigrationFinder()
    {
        $migrationFinderProphecy = $this->prophesize('Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface');
        /** @var $migrationFinder MigrationFinderInterface */
        $migrationFinder = $migrationFinderProphecy->reveal();

        $config = $this->loadConfiguration();
        $config->setMigrationsFinder($migrationFinder);

        $migrationFinderPropertyReflected = new \ReflectionProperty(
            'Doctrine\DBAL\Migrations\Configuration\Configuration',
            'migrationFinder'
        );
        $migrationFinderPropertyReflected->setAccessible(true);
        $this->assertSame($migrationFinder, $migrationFinderPropertyReflected->getValue($config));
    }

    public function testThrowExceptionIfAlreadyLoaded()
    {
        $config = $this->loadConfiguration();
        $this->setExpectedException('Doctrine\DBAL\Migrations\MigrationException');
        $config->load($config->getFile());
    }

    public function testVersionsOrganizationNoConfig()
    {
        $config = $this->loadConfiguration();
        $this->assertFalse($config->areMigrationsOrganizedByYear());
        $this->assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYear()
    {
        $config = $this->loadConfiguration('organize_by_year');
        $this->assertTrue($config->areMigrationsOrganizedByYear());
        $this->assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYearAndMonth()
    {
        $config = $this->loadConfiguration('organize_by_year_and_month');
        $this->assertTrue($config->areMigrationsOrganizedByYear());
        $this->assertTrue($config->areMigrationsOrganizedByYearAndMonth());
    }

    /**
     * @expectedException Doctrine\DBAL\Migrations\MigrationException
     */
    public function testVersionsOrganizationInvalid()
    {
        $this->loadConfiguration('organize_invalid');
    }

    /**
     * @expectedException Doctrine\DBAL\Migrations\MigrationException
     */
    public function testVersionsOrganizationIncompatibleFinder()
    {
        $config = $this->loadConfiguration('organize_by_year_and_month');
        $config->setMigrationsFinder(new GlobFinder());
    }

    /**
     * @expectedException Doctrine\DBAL\Migrations\MigrationException
     * @expectedExceptionCode 10
     */
    public function testConfigurationWithInvalidOption()
    {
        $this->loadConfiguration('invalid');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConfigurationFileNotExists()
    {
        $this->loadConfiguration('file_not_exists');
    }

    public function testLoadMigrationsList()
    {
        $this->loadConfiguration('migrations_list');
        $this->loadConfiguration('migrations_list2');
    }
}
