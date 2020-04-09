<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use function sys_get_temp_dir;

class DoctrineCommandTest extends MigrationTestCase
{
    public function testCommandFreezes() : void
    {
        $dependencyFactory = $this->getMockBuilder(DependencyFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['freeze'])
            ->getMock();

        $dependencyFactory
            ->expects(self::once())
            ->method('freeze');

        $command       = new class($dependencyFactory) extends DoctrineCommand
        {
            protected function execute(InputInterface $input, OutputInterface $output) : int
            {
                return 0;
            }
        };
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [],
            ['interactive' => false]
        );
    }

    public function testCustomConfiguration() : void
    {
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($conn)
        );

        $command       = new class($dependencyFactory) extends DoctrineCommand
        {
            protected function execute(InputInterface $input, OutputInterface $output) : int
            {
                $migrationDirectories = $this->getDependencyFactory()->getConfiguration()->getMigrationDirectories();
                DoctrineCommandTest::assertSame(['DoctrineMigrationsTest' => 'bar'], $migrationDirectories);

                return 0;
            }
        };
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            ['--configuration' => __DIR__ . '/_files/config.yml'],
            ['interactive' => false]
        );
    }
}
