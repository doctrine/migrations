<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationVersionTest extends MigrationTestCase
{
    private $command;

    /** @var Configuration */
    private $configuration;

    public function setUp()
    {
        $this->command = $this
            ->getMockBuilder('Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand')
            ->setConstructorArgs(array('migrations:version'))
            ->setMethods(array('getMigrationConfiguration'))
            ->getMock();

        $this->configuration = new Configuration($this->getSqliteConnection());
        $this->configuration->setMigrationsNamespace('DoctrineMigrations');
        $this->configuration->setMigrationsDirectory(sys_get_temp_dir());

        $this->command
            ->expects($this->once())
            ->method('getMigrationConfiguration')
            ->will($this->returnValue($this->configuration));

    }

    /**
     * Test "--add --range" options on migrate only versions in interval.
     */
    public function testAddRangeOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');

        $commandTester = new CommandTester($this->command);
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

        $this->assertFalse($this->configuration->getVersion('1233')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1234')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1235')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1239')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--delete --range" options on migrate down only versions in interval.
     */
    public function testDeleteRangeOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');

        $this->configuration->getVersion('1233')->markMigrated();
        $this->configuration->getVersion('1234')->markMigrated();
        $this->configuration->getVersion('1239')->markMigrated();
        $this->configuration->getVersion('1240')->markMigrated();


        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array(
                '--delete'    => true,
                '--range'     => true,
                'version'     => '1234',
                'version_end' => '1239',
            ),
            array(
                'interactive' => false,
            )
        );

        $this->assertTrue($this->configuration->getVersion('1233')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1234')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1235')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1239')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add --all options on migrate all versions.
     */
    public function testAddAllOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array(
                '--add'       => true,
                '--all'       => true,
            ),
            array(
                'interactive' => false,
            )
        );

        $this->assertTrue($this->configuration->getVersion('1233')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1234')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1235')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1239')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--delete --all" options on migrate down all versions.
     */
    public function testDeleteAllOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\ConfigMigration');

        $this->configuration->getVersion('1233')->markMigrated();
        $this->configuration->getVersion('1234')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array(
                '--delete'    => true,
                '--all'       => true,
            ),
            array(
                'interactive' => false,
            )
        );

        $this->assertFalse($this->configuration->getVersion('1233')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1234')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1235')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1239')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1240')->isMigrated());
    }
}
