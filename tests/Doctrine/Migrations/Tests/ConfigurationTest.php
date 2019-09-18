<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Events;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Tests\Stub\EventVerificationListener;
use Doctrine\Migrations\Tests\Stub\Version1Test;
use Doctrine\Migrations\Tests\Stub\Version2Test;
use Doctrine\Migrations\Tests\Stub\Version3Test;
use function sys_get_temp_dir;

class ConfigurationTest extends MigrationTestCase
{
    public function testValidateMigrationsNamespaceRequired() : void
    {
        $config = new Configuration();
        $this->expectException(MigrationException::class);
        $config->validate();
    }

    public function testValidateMigrations() : void
    {
        $config = new Configuration();
        $config->setMigrationsNamespace('DoctrineMigrations\\');
        $config->setMigrationsDirectory(sys_get_temp_dir());

        $config->validate();

        $this->addToAssertionCount(1);
    }

    public function testSetGetName() : void
    {
        $config = new Configuration();
        $config->setName('Test');

        self::assertSame('Test', $config->getName());
    }

    public function testEmptyProjectDefaults() : void
    {
        $config = $this->getSqliteConfiguration();
        self::assertNull($config->getPrevVersion(), 'no prev version');
        self::assertNull($config->getNextVersion(), 'no next version');
        self::assertSame('0', $config->getCurrentVersion(), 'current version 0');
        self::assertSame('0', $config->getLatestVersion(), 'latest version 0');
        self::assertSame(0, $config->getNumberOfAvailableMigrations(), 'number of available migrations 0');
        self::assertSame(0, $config->getNumberOfExecutedMigrations(), 'number of executed migrations 0');
        self::assertSame([], $config->getMigrations());
    }

    public function testGetUnknownVersion() : void
    {
        $config = $this->getSqliteConfiguration();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Could not find migration version 1234');

        $config->getVersion('1234');
    }

    public function testRegisterMigration() : void
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigration('1234', Version1Test::class);

        self::assertCount(1, $config->getMigrations(), 'One Migration registered.');
        self::assertTrue($config->hasVersion('1234'));

        $version = $config->getVersion('1234');
        self::assertSame('1234', $version->getVersion());
        self::assertFalse($version->isMigrated());
    }

    public function testRegisterMigrations() : void
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations([
            '1234' => Version1Test::class,
            '1235' => Version2Test::class,
        ]);

        self::assertCount(2, $config->getMigrations(), 'Two Migration registered.');

        $version = $config->getVersion('1234');
        self::assertSame('1234', $version->getVersion());

        $version = $config->getVersion('1235');
        self::assertSame('1235', $version->getVersion());
    }

    public function testRegisterDuplicateVersion() : void
    {
        $config = $this->getSqliteConfiguration();

        $config->registerMigration('1234', Version1Test::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage(
            'Migration version 1234 already registered with class Doctrine\Migrations\Version'
        );
        $config->registerMigration('1234', Version1Test::class);
    }

    public function testRelativeVersion() : void
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations([
            '0initial' => Version1Test::class,
            '1' => Version2Test::class,
            'final' => Version3Test::class,
        ]);

        // Relative to nonexistent version
        self::assertNull($config->getRelativeVersion('nonexistent', -1));
        self::assertNull($config->getRelativeVersion('nonexistent', 0));
        self::assertNull($config->getRelativeVersion('nonexistent', 1));

        // Relative to version '0' as int
        self::assertNull($config->getRelativeVersion('0', -1));
        self::assertSame('0', $config->getRelativeVersion('0', 0));
        self::assertSame('0initial', $config->getRelativeVersion('0', 1));
        self::assertSame('1', $config->getRelativeVersion('0', 2));
        self::assertSame('final', $config->getRelativeVersion('0', 3));
        self::assertNull($config->getRelativeVersion('0', 4));

        // Relative to version '0' as string
        self::assertNull($config->getRelativeVersion('0', -1));
        self::assertSame('0', $config->getRelativeVersion('0', 0));
        self::assertSame('0initial', $config->getRelativeVersion('0', 1));
        self::assertSame('1', $config->getRelativeVersion('0', 2));
        self::assertSame('final', $config->getRelativeVersion('0', 3));
        self::assertNull($config->getRelativeVersion('0', 4));

        // Relative to version '0initial'
        self::assertNull($config->getRelativeVersion('0initial', -2));
        self::assertSame('0', $config->getRelativeVersion('0initial', -1));
        self::assertSame('0initial', $config->getRelativeVersion('0initial', 0));
        self::assertSame('1', $config->getRelativeVersion('0initial', 1));
        self::assertSame('final', $config->getRelativeVersion('0initial', 2));
        self::assertNull($config->getRelativeVersion('0initial', 3));

        // Relative to version '1' as int
        self::assertNull($config->getRelativeVersion('1', -3));
        self::assertSame('0', $config->getRelativeVersion('1', -2));
        self::assertSame('0initial', $config->getRelativeVersion('1', -1));
        self::assertSame('1', $config->getRelativeVersion('1', 0));
        self::assertSame('final', $config->getRelativeVersion('1', 1));
        self::assertNull($config->getRelativeVersion('1', 2));

        // Relative to version '1' as string
        self::assertNull($config->getRelativeVersion('1', -3));
        self::assertSame('0', $config->getRelativeVersion('1', -2));
        self::assertSame('0initial', $config->getRelativeVersion('1', -1));
        self::assertSame('1', $config->getRelativeVersion('1', 0));
        self::assertSame('final', $config->getRelativeVersion('1', 1));
        self::assertNull($config->getRelativeVersion('1', 2));

        // Relative to version 'final'
        self::assertNull($config->getRelativeVersion('final', -4));
        self::assertSame('0', $config->getRelativeVersion('final', -3));
        self::assertSame('0initial', $config->getRelativeVersion('final', -2));
        self::assertSame('1', $config->getRelativeVersion('final', -1));
        self::assertSame('final', $config->getRelativeVersion('final', 0));
        self::assertNull($config->getRelativeVersion('final', 1));
    }

    public function testPreviousCurrentNextLatestVersion() : void
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations([
            '1234' => Version1Test::class,
            '1235' => Version2Test::class,
            '1236' => Version3Test::class,
        ]);

        self::assertNull($config->getPrevVersion(), 'no prev version');
        self::assertSame('0', $config->getCurrentVersion(), 'current version 0');
        self::assertSame('1234', $config->getNextVersion(), 'next version 1234');
        self::assertSame('1236', $config->getLatestVersion(), 'latest version 1236');

        $config->getVersion('1234')->markMigrated();

        self::assertSame('0', $config->getPrevVersion(), 'prev version 0');
        self::assertSame('1234', $config->getCurrentVersion(), 'current version 1234');
        self::assertSame('1235', $config->getNextVersion(), 'next version 1235');
        self::assertSame('1236', $config->getLatestVersion(), 'latest version 1236');

        $config->getVersion('1235')->markMigrated();

        self::assertSame('1234', $config->getPrevVersion(), 'prev version 1234');
        self::assertSame('1235', $config->getCurrentVersion(), 'current version 1235');
        self::assertSame('1236', $config->getNextVersion(), 'next version is 1236');
        self::assertSame('1236', $config->getLatestVersion(), 'latest version 1236');

        self::assertSame('0', $config->resolveVersionAlias('first'), 'first version 0');
        self::assertSame('1234', $config->resolveVersionAlias('prev'), 'prev version 1234');
        self::assertSame('1235', $config->resolveVersionAlias('current'), 'current version 1235');
        self::assertSame('1236', $config->resolveVersionAlias('next'), 'next version is 1236');
        self::assertSame('1236', $config->resolveVersionAlias('latest'), 'latest version 1236');
        self::assertSame('1236', $config->resolveVersionAlias('1236'), 'identical version');
        self::assertNull($config->resolveVersionAlias('123678'), 'unknown version');

        $config->getVersion('1236')->markMigrated();

        self::assertSame('1235', $config->getPrevVersion(), 'prev version 1235');
        self::assertSame('1236', $config->getCurrentVersion(), 'current version 1236');
        self::assertNull($config->getNextVersion(), 'no next version');
        self::assertSame('1236', $config->getLatestVersion(), 'latest version 1236');
    }

    public function testDeltaVersion() : void
    {
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations([
            '1234' => Version1Test::class,
            '1235' => Version2Test::class,
            '1236' => Version3Test::class,
        ]);

        self::assertNull($config->getDeltaVersion('-1'), 'no current-1 version');
        self::assertSame('1234', $config->getDeltaVersion('+1'), 'current+1 is 1234');
        self::assertSame('1235', $config->getDeltaVersion('+2'), 'current+2 is 1235');
        self::assertSame('1236', $config->getDeltaVersion('+3'), 'current+3 is 1236');
        self::assertNull($config->getDeltaVersion('+4'), 'no current+4 version');

        $config->getVersion('1234')->markMigrated();

        self::assertNull($config->getDeltaVersion('-2'), 'no current-2 version');
        self::assertSame('0', $config->getDeltaVersion('-1'), 'current-1 is 0');
        self::assertSame('1235', $config->getDeltaVersion('+1'), 'current+1 is 1235');
        self::assertSame('1236', $config->getDeltaVersion('+2'), 'current+2 is 1236');
        self::assertNull($config->getDeltaVersion('+3'), 'no current+3');

        $config->getVersion('1235')->markMigrated();

        self::assertNull($config->getDeltaVersion('-3'), 'no current-3 version');
        self::assertSame('0', $config->getDeltaVersion('-2'), 'current-2 is 0');
        self::assertSame('1234', $config->getDeltaVersion('-1'), 'current-1 is 1234');
        self::assertSame('1236', $config->getDeltaVersion('+1'), 'current+1 is 1236');
        self::assertNull($config->getDeltaVersion('+2'), 'no current+2');

        $config->getVersion('1236')->markMigrated();

        self::assertNull($config->getDeltaVersion('-4'), 'no current-4 version');
        self::assertSame('0', $config->getDeltaVersion('-3'), 'current-3 is 0');
        self::assertSame('1234', $config->getDeltaVersion('-2'), 'current-2 is 1234');
        self::assertSame('1235', $config->getDeltaVersion('-1'), 'current-1 is 1235');
        self::assertNull($config->getDeltaVersion('+1'), 'no current+1');
    }

    public function testGetAvailableVersions() : void
    {
        $config = $this->getSqliteConfiguration();

        $config->registerMigration('1234', Version1Test::class);
        self::assertSame(['1234'], $config->getAvailableVersions());
    }

    public function testDispatchEventProxiesToConnectionsEventManager() : void
    {
        $config                            = $this->getSqliteConfiguration();
        $config->getConnection()
            ->getEventManager()
            ->addEventSubscriber($listener = new EventVerificationListener());

        $config->dispatchEvent(
            Events::onMigrationsMigrating,
            $ea = new MigrationsEventArgs($config, 'up', false)
        );

        self::assertArrayHasKey(Events::onMigrationsMigrating, $listener->events);
        self::assertSame($ea, $listener->events[Events::onMigrationsMigrating][0]);
    }

    /**
     * @dataProvider autoloadVersionProvider
     */
    public function testGetVersionAutoloadVersion(string $version) : void
    {
        $config = $this->getSqliteConfiguration();
        $config->setMigrationsNamespace('Doctrine\Migrations\Tests\Stub\Configuration\AutoloadVersions');
        $config->setMigrationsDirectory(__DIR__ . '/Stub/Configuration/AutoloadVersions');

        $result = $config->getVersion($version);

        self::assertSame($version, $result->getVersion());
    }

    public function testGetVersionNotFound() : void
    {
        $config = $this->getSqliteConfiguration();

        $this->expectException(MigrationException::class);

        $config->getVersion('foo');
    }

    /**
     * @dataProvider versionProvider
     */
    public function testGetDatetime(string $version, string $return) : void
    {
        $config = $this->getSqliteConfiguration();

        self::assertSame($return, $config->getDateTime($version));
    }

    public function testDryRunMigratedAndCurrentVersions() : void
    {
        // migrations table created
        $config1 = $this->getSqliteConfiguration();
        $config1->setIsDryRun(false);

        self::assertSame('0', $config1->getCurrentVersion(), 'current version 0');
        self::assertTrue($config1->getConnection()->getSchemaManager()->tablesExist([$config1->getMigrationsTableName()]));

        // migrations table created
        $config2 = $this->getSqliteConfiguration();
        $config2->setIsDryRun(false);

        self::assertSame([], $config2->getMigratedVersions());
        self::assertTrue($config2->getConnection()->getSchemaManager()->tablesExist([$config2->getMigrationsTableName()]));

        // no migrations table created
        $config3 = $this->getSqliteConfiguration();
        $config3->setIsDryRun(true);

        self::assertSame('0', $config3->getCurrentVersion(), 'current version 0');
        self::assertFalse($config3->getConnection()->getSchemaManager()->tablesExist([$config3->getMigrationsTableName()]));

        self::assertSame([], $config3->getMigratedVersions());
        self::assertFalse($config3->getConnection()->getSchemaManager()->tablesExist([$config3->getMigrationsTableName()]));

        // gets the correct migration from the table, if the table exists
        $config = $this->getSqliteConfiguration();
        $config->registerMigrations([
            1234 => Version1Test::class,
            1235 => Version2Test::class,
        ]);

        $config->getVersion('1234')->markMigrated();
        $config->setIsDryRun(true);

        self::assertSame('1234', $config->getCurrentVersion(), 'current version 1234');
        self::assertSame('1235', $config->getNextVersion(), 'next version 1235');

        self::assertSame(['1234'], $config->getMigratedVersions());
        self::assertTrue($config->getConnection()->getSchemaManager()->tablesExist([$config->getMigrationsTableName()]));
    }

    /**
     * @return string[][]
     */
    public function versionProvider() : array
    {
        return [
            ['20150101123545Version', '2015-01-01 12:35:45'],
            ['tralalaVersion', ''],
            ['0000254Version', ''],
            ['0000254BaldlfqjdVersion', ''],
            ['20130101123545Version', '2013-01-01 12:35:45'],
            ['20150202042811', '2015-02-02 04:28:11'],
            ['20150202162811', '2015-02-02 16:28:11'],
        ];
    }

    /**
     * @return string[][]
     */
    public function autoloadVersionProvider() : array
    {
        return [
            ['1Test'],
            ['2Test'],
            ['3Test'],
            ['4Test'],
            ['5Test'],
        ];
    }

    /**
     * @dataProvider validCustomTemplates
     */
    public function testSetCustomTemplateShould(?string $template) : void
    {
        $config = new Configuration();
        $config->setCustomTemplate($template);

        self::assertSame($template, $config->getCustomTemplate());
    }

    /**
     * @return string[][]|null[][]
     */
    public function validCustomTemplates() : array
    {
        return [
            'null template'  => [null],
            'shiny template' => ['my-awesome-template.php'],
        ];
    }
}
