<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\Version1Test;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Symfony\Component\Console\Tester\CommandTester;
use function preg_quote;

class MigrationStatusTest extends MigrationTestCase
{
    /** @var string */
    private $migrationDirectory;

    protected function setUp() : void
    {
        $this->migrationDirectory = __DIR__ . '/../../../Stub/migration-empty-folder';
    }

    /**
     * Tests the display of the previous/current/next/latest versions.
     */
    public function testVersions() : void
    {
        self::assertVersion('prev', '123', 'Previous Version', 'FORMATTED (123)');
        self::assertVersion('current', '234', 'Current Version', 'FORMATTED (234)');
        self::assertVersion('next', '345', 'Next Version', 'FORMATTED (345)');
        self::assertVersion('latest', '456', 'Latest Version', 'FORMATTED (456)');

        // Initial version is not formatted as date.
        self::assertVersion('prev', '0', 'Previous Version', '0');

        // The initial version has no previous version, and the latest has no next.
        self::assertVersion('prev', null, 'Previous Version', 'Already at first version');
        self::assertVersion('next', null, 'Next Version', 'Already at latest version');
    }

    /**
     * Asserts that one version is displayed correctly.
     *
     * @param  string      $alias   "prev", "current", "next", "latest".
     * @param  string|null $version The version corresponding to the $alias.
     * @param  string      $label   The expected row label.
     * @param  string      $output  The expected row value.
     */
    protected function assertVersion(string $alias, ?string $version, string $label, string $output) : void
    {
        $command = $this
            ->getMockBuilder(StatusCommand::class)
            ->setConstructorArgs(['migrations:status'])
            ->setMethods(
                ['getConfigurationuration']
            )
            ->getMock();

        $configuration = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs([$this->getSqliteConnection()])
            ->setMethods(['resolveVersionAlias', 'getDateTime', 'getAvailableVersions'])
            ->getMock();

        $configuration
            ->expects(self::exactly(4))
            ->method('resolveVersionAlias')
            ->will(self::returnCallback(static function ($argAlias) use ($alias, $version) {
                return $argAlias === $alias ? $version : '999';
            }));

        $configuration
            ->method('getDateTime')
            ->will(self::returnValue('FORMATTED'));

        $configuration
            ->method('getAvailableVersions')
            ->will(self::returnValue([]));

        $configuration->setMigrationsNamespace('DoctrineMigrations');
        $configuration->setMigrationsDirectory($this->migrationDirectory);

        $command
            ->expects(self::once())
            ->method('getConfigurationuration')
            ->will(self::returnValue($configuration));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [],
            []
        );

        $textOutput = $commandTester->getDisplay();
        self::assertRegExp('/\s+>> ' . $label . ':\s+' . preg_quote($output) . '/m', $textOutput);
    }

    public function testShowVersions() : void
    {
        $configuration = new Configuration($this->getSqliteConnection());
        $configuration->setMigrationsNamespace('DoctrineMigrations');
        $configuration->setMigrationsDirectory($this->migrationDirectory);

        $configuration->registerMigration('1233', Version1Test::class);
        $configuration->registerMigration('1234', Version1Test::class);
        $configuration->registerMigration('20170101010101', Version1Test::class);
        $configuration->registerMigration('20170101010102', Version1Test::class);
        $configuration->registerMigration('VeryLongMigrationName_VeryLongMigrationName_VeryLongMigrationName_1', Version1Test::class);

        $configuration->registerMigration('VeryLongMigrationName_VeryLongMigrationName_VeryLongMigrationName_2', Version1Test::class);

        $configuration->getVersion('1234')->markMigrated();
        $configuration->getVersion('20170101010101')->markMigrated();
        $configuration->getVersion('VeryLongMigrationName_VeryLongMigrationName_VeryLongMigrationName_1')->markMigrated();

        $command = $this
            ->getMockBuilder(StatusCommand::class)
            ->setConstructorArgs(['migrations:status'])
            ->setMethods(['getConfigurationuration'])
            ->getMock();

        $command
            ->expects(self::once())
            ->method('getConfigurationuration')
            ->will(self::returnValue($configuration));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--show-versions' => true],
            []
        );

        $textOutput = $commandTester->getDisplay();
        self::assertRegExp('/\s+>>  \(1233\)\s+not migrated/m', $textOutput);
        self::assertRegExp('/\s+>>  \(1234\)\s+migrated/m', $textOutput);
        self::assertRegExp('/\s+>> 2017-01-01 01:01:01 \(20170101010101\)\s+migrated/m', $textOutput);
        self::assertRegExp('/\s+>> 2017-01-01 01:01:02 \(20170101010102\)\s+not migrated/m', $textOutput);
        self::assertRegExp('/\s+>>  \(VeryLongMigrationName_VeryLongMigrationName_VeryLongMigrationName_1\)\s+migrated/m', $textOutput);
        self::assertRegExp('/\s+>>  \(VeryLongMigrationName_VeryLongMigrationName_VeryLongMigrationName_2\)\s+not migrated/m', $textOutput);
    }
}
