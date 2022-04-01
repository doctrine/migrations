<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
use function explode;
use function sys_get_temp_dir;
use function trim;

class UpToDateCommandTest extends MigrationTestCase
{
    private MigrationsRepository $migrationRepository;

    private MetadataStorage $metadataStorage;

    private CommandTester $commandTester;

    private UpToDateCommand $command;

    private Connection $conn;

    private TableMetadataStorageConfiguration $metadataConfig;

    protected function setUp(): void
    {
        $this->metadataConfig = new TableMetadataStorageConfiguration();

        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration($this->metadataConfig);
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $this->conn = $this->getSqliteConnection();

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($this->conn)
        );

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage     = $dependencyFactory->getMetadataStorage();
        $this->metadataStorage->ensureInitialized();

        $this->command       = new UpToDateCommand($dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @param string[] $migrations
     * @param string[] $migratedVersions
     *
     * @throws MigrationException
     *
     * @dataProvider dataIsUpToDate
     */
    public function testIsUpToDate(array $migrations, array $migratedVersions, int $exitCode, bool $failOnUnregistered = false): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        foreach ($migrations as $version) {
            Helper::registerMigrationInstance($this->migrationRepository, new Version($version), $migrationClass);
        }

        foreach ($migratedVersions as $version) {
            $result = new ExecutionResult(new Version($version), Direction::UP);
            $this->metadataStorage->complete($result);
        }

        $this->commandTester->execute(['--fail-on-unregistered' => $failOnUnregistered]);

        self::assertSame($exitCode, $this->commandTester->getStatusCode());
    }

    public function testMigrationList(): void
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

        $this->commandTester->execute(['--list-migrations' => true]);

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay(true))));

        self::assertSame(
            [
                '[ERROR] Out-of-date! 1 migration is available to execute.',
                '',
                '[ERROR] You have 1 previously executed migration in the database that is not a registered migration.',
                '',
                '+-----------+-------------------------+---------------------+----------------+-------------+',
                '| Migration Versions                                                         |             |',
                '+-----------+-------------------------+---------------------+----------------+-------------+',
                '| Migration | Status                  | Migrated At         | Execution Time | Description |',
                '+-----------+-------------------------+---------------------+----------------+-------------+',
                '| 1229      | migrated, not available | 2010-01-01 02:03:04 |                |             |',
                '| 1231      | not migrated            |                     |                | foo         |',
                '+-----------+-------------------------+---------------------+----------------+-------------+',
            ],
            $lines
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataIsUpToDate(): array
    {
        return [
            'up-to-date' => [
                ['20160614015627'],
                ['20160614015627'],
                0,
            ],
            'empty-migration-set' => [
                [],
                [],
                0,
            ],
            'one-migration-available' => [
                ['20150614015627'],
                [],
                1,
            ],
            'many-migrations-available' => [
                [
                    '20110614015627',
                    '20120614015627',
                    '20130614015627',
                    '20140614015627',
                ],
                ['20110614015627'],
                1,
            ],
            'unregistered-migrations' => [
                [],
                ['20160614015627', '20120614015627'],
                0,
            ],
            'unregistered-migrations-fail' => [
                [],
                ['20160614015627', '20120614015627'],
                2,
                true,
            ],
        ];
    }
}
