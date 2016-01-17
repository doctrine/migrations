<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationStatusTest extends MigrationTestCase
{

    private $migrationDirectory;

    public function __construct()
    {
        parent::__construct(null, [], null);
        $this->migrationDirectory = __DIR__ . '/../../../Stub/migration-empty-folder';
    }

    /**
     * Tests the display of the previous/current/next/latest versions.
     */
    public function testVersions()
    {
        $this->assertVersion('prev',    '123', 'Previous Version', 'FORMATTED (123)');
        $this->assertVersion('current', '234', 'Current Version',  'FORMATTED (234)');
        $this->assertVersion('next',    '345', 'Next Version',     'FORMATTED (345)');
        $this->assertVersion('latest',  '456', 'Latest Version',   'FORMATTED (456)');

        // Initial version is not formatted as date.
        $this->assertVersion('prev',    '0',   'Previous Version', '0');

        // The initial version has no previous version, and the latest has no next.
        $this->assertVersion('prev',    null,  'Previous Version', 'Already at first version');
        $this->assertVersion('next',    null,  'Next Version',     'Already at latest version');
    }

    /**
     * Asserts that one version is displayed correctly.
     * @param  string      $alias   "prev", "current", "next", "latest".
     * @param  string|null $version The version corresponding to the $alias.
     * @param  string      $label   The expected row label.
     * @param  string      $output  The expected row value.
     */
    protected function assertVersion($alias, $version, $label, $output)
    {
        $command = $this
            ->getMockBuilder('Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand')
            ->setConstructorArgs(['migrations:status'])
            ->setMethods(
                [
                    'getMigrationConfiguration',
                ]
            )
            ->getMock();

        $configuration = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->setConstructorArgs([$this->getSqliteConnection()])
            ->setMethods(['resolveVersionAlias', 'getDateTime', 'getAvailableVersions'])
            ->getMock();

        $configuration
            ->expects($this->exactly(4))
            ->method('resolveVersionAlias')
            ->will($this->returnCallback(function($argAlias) use ($alias, $version) {
                return $argAlias === $alias ? $version : '999';
            }));

        $configuration
            ->expects($this->any())
            ->method('getDateTime')
            ->will($this->returnValue('FORMATTED'));

        $configuration
            ->expects($this->any())
            ->method('getAvailableVersions')
            ->will($this->returnValue([]));

        $configuration->setMigrationsNamespace('DoctrineMigrations');
        $configuration->setMigrationsDirectory($this->migrationDirectory);

        $command
            ->expects($this->once())
            ->method('getMigrationConfiguration')
            ->will($this->returnValue($configuration));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [],
            []
        );

        $textOutput = $commandTester->getDisplay();
        $this->assertRegexp('/\s+>> ' . $label . ':\s+' . preg_quote($output) . '/m', $textOutput);
    }

    /**
     * Tests if the amount of new migrations remains valid.
     *
     * This test prevents an incorrect amount of new migrations when unavailable migrations were executed. When there
     * are still new ones, it should show the correct number of new migrations.
     */
    public function testIfAmountNewMigrationsIsCorrectWithUnavailableMigrations()
    {
        $command = $this
            ->getMockBuilder('Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand')
            ->setConstructorArgs(['migrations:status'])
            ->setMethods(
                [
                    'getMigrationConfiguration',
                ]
            )
            ->getMock();

        $configuration = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->setConstructorArgs([$this->getSqliteConnection()])
            ->setMethods(['getMigratedVersions', 'getAvailableVersions', 'getCurrentVersion'])
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue([1234,1235,1237,1238,1239]));

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue([1234,1235,1239,1240]));

        $configuration
            ->expects($this->any())
            ->method('getCurrentVersion')
            ->will($this->returnValue(1239));

        $configuration->setMigrationsNamespace('DoctrineMigrations');
        $configuration->setMigrationsDirectory($this->migrationDirectory);

        $command
            ->expects($this->once())
            ->method('getMigrationConfiguration')
            ->will($this->returnValue($configuration));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [],
            []
        );

        $textOutput = $commandTester->getDisplay();
        $this->assertRegexp('/\s+>> New Migrations:\s+1/m', $textOutput);
    }
}
