<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use DateTimeImmutable;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
use function explode;
use function sys_get_temp_dir;
use function trim;

class ListCommandTest extends MigrationTestCase
{
    private ListCommand $command;

    private MigrationsRepository $migrationRepository;

    private MetadataStorage $metadataStorage;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($conn)
        );

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage     = $dependencyFactory->getMetadataStorage();

        $this->metadataStorage->ensureInitialized();

        $this->command       = new ListCommand($dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        $migrationClass
            ->expects(self::atLeastOnce())
            ->method('getDescription')
            ->willReturn('foo');

        Helper::registerMigrationInstance($this->migrationRepository, new Version('1231'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1230'), $migrationClass);

        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-01 02:03:04'));
        $result->setTime(10.0);
        $this->metadataStorage->complete($result);

        $result = new ExecutionResult(new Version('1229'), Direction::UP, new DateTimeImmutable('2010-01-01 02:03:04'));
        $this->metadataStorage->complete($result);

        $this->commandTester->execute([]);

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay(true))));

        self::assertSame(
            [
                0 => '+-----------+-------------------------+---------------------+----------------+-------------+',
                1 => '| Migration Versions                                                         |             |',
                2 => '+-----------+-------------------------+---------------------+----------------+-------------+',
                3 => '| Migration | Status                  | Migrated At         | Execution Time | Description |',
                4 => '+-----------+-------------------------+---------------------+----------------+-------------+',
                5 => '| 1229      | migrated, not available | 2010-01-01 02:03:04 |                |             |',
                6 => '| 1230      | migrated                | 2010-01-01 02:03:04 | 10s            | foo         |',
                7 => '| 1231      | not migrated            |                     |                | foo         |',
                8 => '+-----------+-------------------------+---------------------+----------------+-------------+',
            ],
            $lines
        );
    }
}
