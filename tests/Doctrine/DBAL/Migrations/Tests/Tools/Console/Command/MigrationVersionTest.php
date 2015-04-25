<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationVersionTest extends MigrationTestCase
{
    /**
     * Test "range" option on migrate only versions in interval.
     */
    public function testRangeOption()
    {
        $command = $this
            ->getMockBuilder('Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand')
            ->setConstructorArgs(array('migrations:version'))
            ->setMethods(array('getMigrationConfiguration'))
            ->getMock();

        /** @var Configuration $configuration */
        $configuration = new Configuration($this->getSqliteConnection());

        $configuration->setMigrationsNamespace('DoctrineMigrations');
        $configuration->setMigrationsDirectory(sys_get_temp_dir());

        $command
            ->expects($this->once())
            ->method('getMigrationConfiguration')
            ->will($this->returnValue($configuration));

        $configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                '--add'       => true,
                '--range'     => true,
                'version'     => '1234',
                'version_end' => '1239',
            ),
            array(
                'interactive' => false,
            )
        );

        $this->assertFalse($configuration->getVersion('1233')->isMigrated());
        $this->assertTrue($configuration->getVersion('1234')->isMigrated());
        $this->assertTrue($configuration->getVersion('1235')->isMigrated());
        $this->assertTrue($configuration->getVersion('1239')->isMigrated());
        $this->assertFalse($configuration->getVersion('1240')->isMigrated());
    }
}
