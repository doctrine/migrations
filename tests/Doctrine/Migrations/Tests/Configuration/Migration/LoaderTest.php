<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function dirname;

use const DIRECTORY_SEPARATOR;

abstract class LoaderTest extends TestCase
{
    abstract public function load(string $prefix = ''): Configuration;

    public function testLoad(): void
    {
        $config = $this->load();

        self::assertSame(['DoctrineMigrationsTest' => dirname(__DIR__) . DIRECTORY_SEPARATOR . '_files'], $config->getMigrationDirectories());

        self::assertSame(['Foo', 'Bar'], $config->getMigrationClasses());

        $storage = $config->getMetadataStorageConfiguration();
        self::assertInstanceOf(TableMetadataStorageConfiguration::class, $storage);

        self::assertSame('doctrine_migration_versions_test', $storage->getTableName());
        self::assertSame('doctrine_migration_column_test', $storage->getVersionColumnName());
        self::assertSame(2000, $storage->getVersionColumnLength());
        self::assertSame('doctrine_migration_execution_time_column_test', $storage->getExecutionTimeColumnName());
        self::assertSame('doctrine_migration_executed_at_column_test', $storage->getExecutedAtColumnName());
    }

    public function testLoadBasic(): void
    {
        $config = $this->load('basic');

        self::assertSame(['DoctrineMigrationsTest' => dirname(__DIR__) . DIRECTORY_SEPARATOR . '_files'], $config->getMigrationDirectories());

        self::assertSame([], $config->getMigrationClasses());

        $storage = $config->getMetadataStorageConfiguration();
        self::assertInstanceOf(TableMetadataStorageConfiguration::class, $storage);

        self::assertSame('doctrine_migration_versions', $storage->getTableName());
        self::assertSame('version', $storage->getVersionColumnName());
        self::assertSame(191, $storage->getVersionColumnLength());
        self::assertSame('execution_time', $storage->getExecutionTimeColumnName());
        self::assertSame('executed_at', $storage->getExecutedAtColumnName());
    }

    public function testConfigurationFileNotExists(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->load('not_existent');
    }

    public function testCustomTemplate(): void
    {
        $config = $this->load('custom_template');

        self::assertSame('template.tpl', $config->getCustomTemplate());
    }

    public function testConfigurationWithInvalidOption(): void
    {
        $this->expectException(InvalidConfigurationKey::class);

        $this->load('invalid');
    }

    public function testVersionsOrganizationNoConfig(): void
    {
        $config = $this->load();
        self::assertFalse($config->areMigrationsOrganizedByYear());
        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYear(): void
    {
        $config = $this->load('organize_by_year');
        self::assertTrue($config->areMigrationsOrganizedByYear());
        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationByYearAndMonth(): void
    {
        $config = $this->load('organize_by_year_and_month');
        self::assertTrue($config->areMigrationsOrganizedByYear());
        self::assertTrue($config->areMigrationsOrganizedByYearAndMonth());
    }

    public function testVersionsOrganizationInvalid(): void
    {
        $this->expectException(MigrationException::class);

        $this->load('organize_invalid');
    }
}
