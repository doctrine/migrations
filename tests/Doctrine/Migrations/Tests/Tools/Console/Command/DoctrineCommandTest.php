<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ConnectionRegistryConnection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\DoctrineRegistry;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function sys_get_temp_dir;

class DoctrineCommandTest extends MigrationTestCase
{
    public function testCommandFreezes(): void
    {
        $dependencyFactory = $this->getMockBuilder(DependencyFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['freeze'])
            ->getMock();

        $dependencyFactory
            ->expects(self::once())
            ->method('freeze');

        $command       = new class ($dependencyFactory) extends DoctrineCommand
        {
            protected function execute(InputInterface $input, OutputInterface $output): int
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

    public function testCustomConfiguration(): void
    {
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($conn)
        );

        $command       = new class ($dependencyFactory) extends DoctrineCommand
        {
            protected function execute(InputInterface $input, OutputInterface $output): int
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

    public function testDependencyFactoryIsSetFirst(): void
    {
        $dependencyFactory = $this->createMock(DependencyFactory::class);
        $command           = new class ($dependencyFactory) extends DoctrineCommand
        {
            protected function configure(): void
            {
                $this->getDependencyFactory();
            }
        };

        self::assertFalse($command->getDefinition()->hasOption('db-configuration'));
    }

    public function testCustomEntityManager(): void
    {
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $em1      = $this->createMock(EntityManager::class);
        $em2      = $this->createMock(EntityManager::class);
        $registry = new DoctrineRegistry([], ['foo' => $em1, 'bar' => $em2]);

        $dependencyFactory = DependencyFactory::fromEntityManager(
            new ExistingConfiguration($configuration),
            ManagerRegistryEntityManager::withSimpleDefault($registry)
        );

        $command       = new class ($em2, $dependencyFactory) extends DoctrineCommand
        {
            private EntityManager $expectedEm;

            public function __construct(EntityManager $entityManager, DependencyFactory $dependencyFactory)
            {
                parent::__construct($dependencyFactory);
                $this->expectedEm = $entityManager;
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $em = $this->getDependencyFactory()->getEntityManager();
                DoctrineCommandTest::assertSame($this->expectedEm, $em);

                return 0;
            }
        };
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            ['--em' => 'bar'],
            ['interactive' => false]
        );
    }

    public function testCustomConnection(): void
    {
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn1    = $this->createMock(Connection::class);
        $conn2    = $this->createMock(Connection::class);
        $registry = new DoctrineRegistry(['foo' => $conn1, 'bar' => $conn2]);

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            ConnectionRegistryConnection::withSimpleDefault($registry)
        );

        $command       = new class ($conn2, $dependencyFactory) extends DoctrineCommand
        {
            private Connection $expectedConnection;

            public function __construct(Connection $connection, DependencyFactory $dependencyFactory)
            {
                parent::__construct($dependencyFactory);
                $this->expectedConnection = $connection;
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $connection = $this->getDependencyFactory()->getConnection();
                DoctrineCommandTest::assertSame($this->expectedConnection, $connection);

                return 0;
            }
        };
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            ['--conn' => 'bar'],
            ['interactive' => false]
        );
    }

    public function testCanNotSpecifyBothEmAndConnection(): void
    {
        $this->expectException(InvalidOptionUsage::class);
        $this->expectExceptionMessage('You can specify only one of the --em and --conn options.');
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn       = $this->createMock(Connection::class);
        $connLoader = new ExistingConnection($conn);

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            $connLoader
        );

        $command       = new class ($dependencyFactory) extends DoctrineCommand
        {
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return 0;
            }
        };
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--conn' => 'bar',
                '--em' => 'foo',
            ],
            ['interactive' => false]
        );
    }
}
