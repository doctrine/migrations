<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand;
use InvalidArgumentException;
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
            ->setConstructorArgs(['migrations:version'])
            ->setMethods(['getMigrationConfiguration'])
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
     * Test "--add --range-to --range-from" options on migrate only versions in interval.
     */
    public function testAddRangeOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'        => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            [
                'interactive' => false,
            ]
        );

        $this->assertFalse($this->configuration->getVersion('1233')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1234')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1235')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1239')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add --range-from" options without "--range-to".
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Options --range-to and --range-from should be used together.
     */
    public function testAddRangeWithoutRangeToOption()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'        => true,
                '--range-from' => '1233',
            ],
            [
                'interactive' => false,
            ]
        );
    }

    /**
     * Test "--add --range-to" options without "--range-from".
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Options --range-to and --range-from should be used together.
     */
    public function testAddRangeWithoutRangeFromOption()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'      => true,
                '--range-to' => '1233',
            ],
            [
                'interactive' => false,
            ]
        );
    }

    /**
     * Test "--add --all --range-to" options.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Options --all and --range-to/--range-from both used. You should use only one of them.
     */
    public function testAddAllOptionsWithRangeTo()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'      => true,
                '--all'      => true,
                '--range-to' => '1233',
            ],
            [
                'interactive' => false,
            ]
        );
    }

    /**
     * Test "--add --all --range-from" options.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Options --all and --range-to/--range-from both used. You should use only one of them.
     */
    public function testAddAllOptionsWithRangeFrom()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'      => true,
                '--all'      => true,
                '--range-from' => '1233',
            ],
            [
                'interactive' => false,
            ]
        );
    }

    /**
     * Test "--delete --range-to --range-from" options on migrate down only versions in interval.
     */
    public function testDeleteRangeOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $this->configuration->getVersion('1233')->markMigrated();
        $this->configuration->getVersion('1234')->markMigrated();
        $this->configuration->getVersion('1239')->markMigrated();
        $this->configuration->getVersion('1240')->markMigrated();


        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--delete'     => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            [
                'interactive' => false,
            ]
        );

        $this->assertTrue($this->configuration->getVersion('1233')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1234')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1235')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1239')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add --all" options on migrate all versions.
     */
    public function testAddAllOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add' => true,
                '--all' => true,
            ],
            [
                'interactive' => false,
            ]
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
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1239, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1240, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $this->configuration->getVersion('1233')->markMigrated();
        $this->configuration->getVersion('1234')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--delete' => true,
                '--all'    => true,
            ],
            [
                'interactive' => false,
            ]
        );

        $this->assertFalse($this->configuration->getVersion('1233')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1234')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1235')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1239')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add" option on migrate one version.
     */
    public function testAddOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $this->configuration->getVersion('1233')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'   => true,
                'version' => 1234,
            ],
            [
                'interactive' => false,
            ]
        );

        $this->assertTrue($this->configuration->getVersion('1233')->isMigrated());
        $this->assertTrue($this->configuration->getVersion('1234')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1235')->isMigrated());
    }

    /**
     * Test "--delete" options on migrate down one version.
     */
    public function testDeleteOption()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1234, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->registerMigration(1235, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $this->configuration->getVersion('1234')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--delete' => true,
                'version'  => 1234,
            ],
            [
                'interactive' => false,
            ]
        );

        $this->assertFalse($this->configuration->getVersion('1233')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1234')->isMigrated());
        $this->assertFalse($this->configuration->getVersion('1235')->isMigrated());
    }

    /**
     * Test "--add" option on migrate already migrated version.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The version "1233" already exists in the version table.
     */
    public function testAddOptionIfVersionAlreadyMigrated()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');
        $this->configuration->getVersion('1233')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'   => true,
                'version' => 1233,
            ],
            [
                'interactive' => false,
            ]
        );
    }

    /**
     * Test "--delete" option on not migrated version.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The version "1233" does not exists in the version table.
     */
    public function testDeleteOptionIfVersionNotMigrated()
    {
        $this->configuration->registerMigration(1233, 'Doctrine\DBAL\Migrations\Tests\Stub\Version1Test');

        $commandTester = new CommandTester($this->command);

        $commandTester->execute(
            [
                '--delete' => true,
                'version'  => 1233,
            ],
            [
                'interactive' => false,
            ]
        );
    }
}
