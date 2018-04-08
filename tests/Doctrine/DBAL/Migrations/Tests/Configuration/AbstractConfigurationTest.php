<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Finder\GlobFinder;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

abstract class AbstractConfigurationTest extends MigrationTestCase
{
    /**
     * @param string $configFileSuffix Specify config to load.
     * @return \Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration
     */
    abstract public function loadConfiguration(
        $configFileSuffix = '',
        ?OutputWriter $outputWriter = null,
        ?MigrationFinderInterface $migrationFinder = null
    );

    public function testMigrationDirectory()
    {
        $config = $this->loadConfiguration();
        self::assertEquals(__DIR__ . DIRECTORY_SEPARATOR . '_files', $config->getMigrationsDirectory());
    }

    public function testMigrationNamespace()
    {
        $config = $this->loadConfiguration();
        self::assertEquals('DoctrineMigrationsTest', $config->getMigrationsNamespace());
    }

    public function testMigrationName()
    {
        $config = $this->loadConfiguration();
        self::assertEquals('Doctrine Sandbox Migrations', $config->getName());
    }

    public function testMigrationsTable()
    {
        $config = $this->loadConfiguration();
        self::assertEquals('doctrine_migration_versions_test', $config->getMigrationsTableName());
    }

    public function testMigrationColumnName()
    {
        $config = $this->loadConfiguration();
        self::assertEquals('doctrine_migration_column_test', $config->getMigrationsColumnName());
    }

    public function testFinderIsIncompatibleWithConfiguration()
    {
        $this->expectException(MigrationException::class);

        $this->loadConfiguration('organize_by_year', null, new GlobFinder());
    }

    public function testSetMigrationFinder()
    {
        $migrationFinderProphecy = $this->prophesize(MigrationFinderInterface::class);
        /** @var MigrationFinderInterface $migrationFinder */
        $migrationFinder = $migrationFinderProphecy->reveal();

        $config = $this->loadConfiguration();
        $config->setMigrationsFinder($migrationFinder);

        $migrationFinderPropertyReflected = new \ReflectionProperty(
            Configuration::class,
            'migrationFinder'
        );
        $migrationFinderPropertyReflected->setAccessible(true);
        self::assertSame($migrationFinder, $migrationFinderPropertyReflected->getValue($config));
    }

    public function testThrowExceptionIfAlreadyLoaded()
    {
        $config = $this->loadConfiguration();
        $this->expectException(MigrationException::class);
        $config->load($config->getFile());
    }

    public function testVersionsOrganizationNoConfig()
    {
        $config = $this->loadConfiguration();
        self::assertFalse($config->areMigrationsOrganizedByYear());
        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYear()
    {
        $config = $this->loadConfiguration('organize_by_year');
        self::assertTrue($config->areMigrationsOrganizedByYear());
        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYearAndMonth()
    {
        $config = $this->loadConfiguration('organize_by_year_and_month');
        self::assertTrue($config->areMigrationsOrganizedByYear());
        self::assertTrue($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationInvalid()
    {
        $this->expectException(MigrationException::class);

        $this->loadConfiguration('organize_invalid');
    }

    public function testVersionsOrganizationIncompatibleFinder()
    {
        $this->expectException(MigrationException::class);

        $config = $this->loadConfiguration('organize_by_year_and_month');
        $config->setMigrationsFinder(new GlobFinder());
    }

    public function testConfigurationWithInvalidOption()
    {
        $this->expectException(MigrationException::class);
        $this->expectExceptionCode(10);

        $this->loadConfiguration('invalid');
    }

    public function testConfigurationFileNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->loadConfiguration('file_not_exists');
    }

    public function testLoadMigrationsList()
    {
        self::assertInstanceOf(AbstractFileConfiguration::class, $this->loadConfiguration('migrations_list'));
        self::assertInstanceOf(AbstractFileConfiguration::class, $this->loadConfiguration('migrations_list2'));
    }

    /**
     * @dataProvider getConfigWithKeysInVariousOrder
     */
    public function testThatTheOrderOfConfigKeysDoesNotMatter($file)
    {
        self::assertInstanceOf(AbstractFileConfiguration::class, $this->loadConfiguration($file));
    }

    /**
     * @return string[][]
     */
    public function getConfigWithKeysInVariousOrder()
    {
        return [
            ['order_1'],
            ['order_2'],
        ];
    }
}
