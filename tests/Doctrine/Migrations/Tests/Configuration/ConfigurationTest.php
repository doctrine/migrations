<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\MissingNamespaceConfiguration;
use Doctrine\Migrations\Configuration\Exception\UnknownConfigurationValue;
use Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testBase() : void
    {
        $storage = $this->createMock(MetadataStorageConfiguration::class);

        $config = new Configuration();
        $config->addMigrationsDirectory('foo', 'bar');
        $config->addMigrationsDirectory('a', 'b');

        $config->addMigrationClass('Foo');
        $config->addMigrationClass('Bar');

        $config->setName('test migration');
        $config->setAllOrNothing(false);
        $config->setCheckDatabasePlatform(false);
        $config->setMetadataStorageConfiguration($storage);
        $config->setIsDryRun(true);
        $config->setCustomTemplate('aaa.php');

        self::assertSame([
            'foo' => 'bar',
            'a' => 'b',
        ], $config->getMigrationDirectories());

        self::assertSame(['Foo', 'Bar'], $config->getMigrationClasses());
        self::assertSame('test migration', $config->getName());
        self::assertSame($storage, $config->getMetadataStorageConfiguration());
        self::assertFalse($config->isAllOrNothing());
        self::assertFalse($config->isDatabasePlatformChecked());
        self::assertTrue($config->isDryRun());
        self::assertSame('aaa.php', $config->getCustomTemplate());

        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
        self::assertFalse($config->areMigrationsOrganizedByYear());
    }

    public function testNoNamespaceConfigured() : void
    {
        $this->expectException(MissingNamespaceConfiguration::class);
        $this->expectExceptionMessage('There are no namespaces configured.');

        $config = new Configuration();
        $config->validate();
    }

    public function testMigrationOrganizationByYear() : void
    {
        $config = new Configuration();
        $config->setMigrationOrganization(Configuration::VERSIONS_ORGANIZATION_BY_YEAR);

        self::assertFalse($config->areMigrationsOrganizedByYearAndMonth());
        self::assertTrue($config->areMigrationsOrganizedByYear());
    }

    public function testMigrationOrganizationByYearAndMonth() : void
    {
        $config = new Configuration();
        $config->setMigrationOrganization(Configuration::VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH);

        self::assertTrue($config->areMigrationsOrganizedByYearAndMonth());
        self::assertTrue($config->areMigrationsOrganizedByYear());
    }

    public function testMigrationOrganizationWithWrongValue() : void
    {
        $this->expectException(UnknownConfigurationValue::class);
        $config = new Configuration();
        $config->setMigrationOrganization('foo');
    }
}
