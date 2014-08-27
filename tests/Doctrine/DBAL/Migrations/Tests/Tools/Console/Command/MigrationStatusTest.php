<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationStatusTest extends MigrationTestCase
{
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
            ->setConstructorArgs(array('migrations:status'))
            ->setMethods(
                array(
                    'getMigrationConfiguration',
                )
            )
            ->getMock();

        $configuration = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->setConstructorArgs(array($this->getSqliteConnection()))
            ->setMethods(array('getMigratedVersions', 'getAvailableVersions', 'getCurrentVersion'))
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue(array(1234,1235,1237,1238,1239)));

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue(array(1234,1235,1239,1240)));

        $configuration
            ->expects($this->once())
            ->method('getCurrentVersion')
            ->will($this->returnValue(1239));

        $configuration->setMigrationsNamespace('DoctrineMigrations');
        $configuration->setMigrationsDirectory(sys_get_temp_dir());

        $command
            ->expects($this->once())
            ->method('getMigrationConfiguration')
            ->will($this->returnValue($configuration));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(),
            array()
        );

        $textOutput = $commandTester->getDisplay();
        $this->assertRegexp('/\s+>> New Migrations:\s+1/m', $textOutput);
    }
}
