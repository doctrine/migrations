<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Tests\MigrationTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use const DIRECTORY_SEPARATOR;

abstract class AbstractLoaderTest extends TestCase
{
    abstract public function getLoader() : Loader;
    abstract public function getExtension() : string;

    public function testLoad()
    {
        $loader = $this->getLoader();
        $config = $loader->load(__DIR__ ."/../_files/config.".$this->getExtension());

        self::assertSame("Doctrine Sandbox Migrations", $config->getName());
        self::assertSame(['DoctrineMigrationsTest' => dirname(__DIR__) . DIRECTORY_SEPARATOR . '_files'], $config->getMigrationDirectories());

        /**
         * @var $storage TableMetadataStorageConfiguration
         */
        $storage = $config->getMetadataStorageConfiguration();
        self::assertInstanceOf(TableMetadataStorageConfiguration::class, $storage);

        self::assertSame('doctrine_migration_versions_test', $storage->getTableName());
        self::assertSame('doctrine_migration_column_test', $storage->getVersionColumnName());
        self::assertSame(2000, $storage->getVersionColumnLength());
        self::assertSame('doctrine_migration_execution_time_column_test', $storage->getExecutionTimeColumnName());
        self::assertSame('doctrine_migration_executed_at_column_test', $storage->getExecutedAtColumnName());
    }

    public function testConfigurationFileNotExists() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $loader = $this->getLoader();
        $loader->load(__DIR__ ."/../_files/not_existent.".$this->getExtension());
    }

    public function testCustomTemplate() : void
    {
        $loader = $this->getLoader();
        $config = $loader->load(__DIR__ ."/../_files/config_custom_template.".$this->getExtension());

        self::assertSame('template.tpl', $config->getCustomTemplate());
    }

    public function testConfigurationWithInvalidOption() : void
    {
        $this->expectException(InvalidConfigurationKey::class);

        $loader = $this->getLoader();
        $loader->load(__DIR__ ."/../_files/config_invalid.".$this->getExtension());
    }
}
