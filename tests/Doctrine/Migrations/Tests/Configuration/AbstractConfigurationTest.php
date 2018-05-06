<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\MigrationException;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Tests\MigrationTestCase;
use InvalidArgumentException;
use ReflectionProperty;
use const DIRECTORY_SEPARATOR;

abstract class AbstractConfigurationTest extends MigrationTestCase
{
    abstract public function loadConfiguration(
        string $configFileSuffix = '',
        ?OutputWriter $outputWriter = null,
        ?MigrationFinder $migrationFinder = null
    ) : AbstractFileConfiguration;

    public function testMigrationDirectory() : void
    {
        $config = $this->loadConfiguration();
        self::assertEquals(__DIR__ . DIRECTORY_SEPARATOR . '_files', $config->getMigrationsDirectory());
    }

    public function testMigrationNamespace() : void
    {
        $config = $this->loadConfiguration();
        self::assertEquals('DoctrineMigrationsTest', $config->getMigrationsNamespace());
    }

    public function testMigrationName() : void
    {
        $config = $this->loadConfiguration();
        self::assertEquals('Doctrine Sandbox Migrations', $config->getName());
    }

    public function testMigrationsTable() : void
    {
        $config = $this->loadConfiguration();
        self::assertEquals('doctrine_migration_versions_test', $config->getMigrationsTableName());
    }

    public function testMigrationColumnName() : void
    {
        $config = $this->loadConfiguration();
        self::assertEquals('doctrine_migration_column_test', $config->getMigrationsColumnName());
    }

    public function testFinderIsIncompatibleWithConfiguration() : void
    {
        $this->expectException(MigrationException::class);

        $this->loadConfiguration('organize_by_year', null, new GlobFinder());
    }

    public function testSetMigrationFinder() : void
    {
        $migrationFinderProphecy = $this->prophesize(MigrationFinder::class);
        /** @var MigrationFinder $migrationFinder */
        $migrationFinder = $migrationFinderProphecy->reveal();

        $config = $this->loadConfiguration();
        $config->setMigrationsFinder($migrationFinder);

        $migrationFinderPropertyReflected = new ReflectionProperty(
            Configuration::class,
            'migrationFinder'
        );
        $migrationFinderPropertyReflected->setAccessible(true);
        self::assertSame($migrationFinder, $migrationFinderPropertyReflected->getValue($config));
    }

    public function testThrowExceptionIfAlreadyLoaded() : void
    {
        $config = $this->loadConfiguration();
        $this->expectException(MigrationException::class);
        $config->load($config->getFile());
    }

    public function testVersionsOrganizationNoConfig() : void
    {
        $config = $this->loadConfiguration();
        self::assertFalse($config->areMigrationsOrganizedByYear());
        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYear() : void
    {
        $config = $this->loadConfiguration('organize_by_year');
        self::assertTrue($config->areMigrationsOrganizedByYear());
        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYearAndMonth() : void
    {
        $config = $this->loadConfiguration('organize_by_year_and_month');
        self::assertTrue($config->areMigrationsOrganizedByYear());
        self::assertTrue($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationInvalid() : void
    {
        $this->expectException(MigrationException::class);

        $this->loadConfiguration('organize_invalid');
    }

    public function testVersionsOrganizationIncompatibleFinder() : void
    {
        $this->expectException(MigrationException::class);

        $config = $this->loadConfiguration('organize_by_year_and_month');
        $config->setMigrationsFinder(new GlobFinder());
    }

    public function testConfigurationWithInvalidOption() : void
    {
        $this->expectException(MigrationException::class);
        $this->expectExceptionCode(10);

        $this->loadConfiguration('invalid');
    }

    public function testConfigurationFileNotExists() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loadConfiguration('file_not_exists');
    }

    public function testLoadMigrationsList() : void
    {
        self::assertInstanceOf(AbstractFileConfiguration::class, $this->loadConfiguration('migrations_list'));
        self::assertInstanceOf(AbstractFileConfiguration::class, $this->loadConfiguration('migrations_list2'));
    }

    /**
     * @dataProvider getConfigWithKeysInVariousOrder
     */
    public function testThatTheOrderOfConfigKeysDoesNotMatter(string $file) : void
    {
        self::assertInstanceOf(AbstractFileConfiguration::class, $this->loadConfiguration($file));
    }

    /** @return string[] */
    public function getConfigWithKeysInVariousOrder() : array
    {
        return [
            ['order_1'],
            ['order_2'],
        ];
    }
}
