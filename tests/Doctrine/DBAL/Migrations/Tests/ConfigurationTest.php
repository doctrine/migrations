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

        $this->setExpectedException(
            "Doctrine\DBAL\Migrations\MigrationException",
            "Migrations namespace must be configured in order to use Doctrine migrations."
        );
        $config->validate();
    }

    public function testValidateMigrationsDirectoryRequired()
    {
        $config = new Configuration($this->getSqliteConnection());
        $config->setMigrationsNamespace("DoctrineMigrations\\");

        $this->setExpectedException(
            "Doctrine\\DBAL\\Migrations\\MigrationException",
            "Migrations directory must be configured in order to use Doctrine migrations."
        );
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

    public function testEmptyProjectDefaults()
    {
        $config = $this->getSqliteConfiguration();
        $this->assertSame(null, $config->getPrevVersion(), "no prev version");
        $this->assertSame(null, $config->getNextVersion(), "no next version");
        $this->assertSame('0', $config->getCurrentVersion(), "current version 0");
        $this->assertSame('0', $config->getLatestVersion(), "latest version 0");
        $this->assertEquals(0, $config->getNumberOfAvailableMigrations(), "number of available migrations 0");
        $this->assertEquals(0, $config->getNumberOfExecutedMigrations(), "number of executed migrations 0");
        $this->assertEquals([], $config->getMigrations());
    }

    public function testGetUnknownVersion()
    {
        $config = $this->getSqliteConfiguration();

        $this->setExpectedException(
            'Doctrine\DBAL\Migrations\MigrationException',
            'Could not find migration version 1234'
        );
        $config->getVersion(1234);
    }

    public function testRegisterMigration()
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $this->assertEquals(1, count($config->getMigrations()), "One Migration registered.");
        $this->assertTrue($config->hasVersion(1234));

        $version = $config->getVersion(1234);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Version', $version);
        $this->assertEquals(1234, $version->getVersion());
        $this->assertFalse($version->isMigrated());
    }

    public function testRegisterMigrations()
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations([
            1234 => 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test',
            1235 => 'Doctrine\DBAL\Migrations\Tests\Stub\Version2Test',
        ]);

        $this->assertEquals(2, count($config->getMigrations()), "Two Migration registered.");

        $version = $config->getVersion(1234);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Version', $version);

        $version = $config->getVersion(1235);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Version', $version);
    }

    public function testRegisterDuplicateVersion()
    {
        $config = $this->getSqliteConfiguration();

        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $this->setExpectedException(
            'Doctrine\DBAL\Migrations\MigrationException',
            'Migration version 1234 already registered with class Doctrine\DBAL\Migrations\Version'
        );
        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
    }

    public function testPreviousCurrentNextLatestVersion()
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations([
            1234 => 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test',
            1235 => 'Doctrine\DBAL\Migrations\Tests\Stub\Version2Test',
            1236 => 'Doctrine\DBAL\Migrations\Tests\Stub\Version3Test',
        ]);

        $this->assertSame(null, $config->getPrevVersion(), "no prev version");
        $this->assertSame('0', $config->getCurrentVersion(), "current version 0");
        $this->assertSame('1234', $config->getNextVersion(), "next version 1234");
        $this->assertSame('1236', $config->getLatestVersion(), "latest version 1236");

        $config->getVersion(1234)->markMigrated();

        $this->assertSame('0', $config->getPrevVersion(), "prev version 0");
        $this->assertSame('1234', $config->getCurrentVersion(), "current version 1234");
        $this->assertSame('1235', $config->getNextVersion(), "next version 1235");
        $this->assertSame('1236', $config->getLatestVersion(), "latest version 1236");

        $config->getVersion(1235)->markMigrated();

        $this->assertSame('1234', $config->getPrevVersion(), "prev version 1234");
        $this->assertSame('1235', $config->getCurrentVersion(), "current version 1235");
        $this->assertSame('1236', $config->getNextVersion(), "next version is 1236");
        $this->assertSame('1236', $config->getLatestVersion(), "latest version 1236");

        $this->assertSame('0', $config->resolveVersionAlias('first'), "first version 0");
        $this->assertSame('1234', $config->resolveVersionAlias('prev'), "prev version 1234");
        $this->assertSame('1235', $config->resolveVersionAlias('current'), "current version 1235");
        $this->assertSame('1236', $config->resolveVersionAlias('next'), "next version is 1236");
        $this->assertSame('1236', $config->resolveVersionAlias('latest'), "latest version 1236");
        $this->assertSame('1236', $config->resolveVersionAlias('1236'), "identical version");
        $this->assertSame(null, $config->resolveVersionAlias('123678'), "unknown version");

        $config->getVersion(1236)->markMigrated();

        $this->assertSame('1235', $config->getPrevVersion(), "prev version 1235");
        $this->assertSame('1236', $config->getCurrentVersion(), "current version 1236");
        $this->assertSame(null, $config->getNextVersion(), "no next version");
        $this->assertSame('1236', $config->getLatestVersion(), "latest version 1236");
    }

    public function testGetAvailableVersions()
    {
        $config = $this->getSqliteConfiguration();

        $config->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->assertEquals([1234], $config->getAvailableVersions());
    }

    /**
     * @dataProvider versionProvider
     */
    public function testFormatVersion($version, $return)
    {
        $config = $this->getSqliteConfiguration();

        $this->assertEquals($return, $config->formatVersion($version));
    }

    /**
     * @dataProvider versionProvider
     */
    public function testGetDatetime($version, $return)
    {
        $config = $this->getSqliteConfiguration();

        $this->assertEquals($return, $config->formatVersion($version));
    }

    public function versionProvider()
    {
        return [
            ['20150101123545Version', '2015-01-01 12:35:45'],
            ['tralalaVersion', ''],
            ['0000254Version', ''],
            ['0000254BaldlfqjdVersion', ''],
            ['20130101123545Version', '2013-01-01 12:35:45'],
            ['20150202042811', '2015-02-02 04:28:11'],
            ['20150202162811', '2015-02-02 16:28:11']
        ];
    }
}
