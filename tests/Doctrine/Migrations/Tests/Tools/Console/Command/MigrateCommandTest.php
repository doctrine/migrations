<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DbalMigrator;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Finder\Finder;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

use function getcwd;
use function in_array;
use function sprintf;
use function strpos;
use function trim;

class MigrateCommandTest extends MigrationTestCase
{
    private DependencyFactory $dependencyFactory;

    private Configuration $configuration;

    private MigrateCommand $migrateCommand;

    private CommandTester $migrateCommandTester;

    private MetadataStorage $storage;

    private MockObject $queryWriter;

    /** @var QuestionHelper&MockObject */
    private QuestionHelper $questions;

    private MigrationsRepository $migrationRepository;

    private Connection $connection;

    private TableMetadataStorageConfiguration $metadataConfiguration;

    public function testTargetUnknownVersion(): void
    {
        $this->migrateCommandTester->execute(
            ['version' => 'B'],
            ['interactive' => false]
        );

        self::assertStringContainsString('[ERROR] Unknown version: B', $this->migrateCommandTester->getDisplay(true));
        self::assertSame(1, $this->migrateCommandTester->getStatusCode());
    }

    /**
     * @return array<array<int, bool|int>>
     */
    public function getMigrateWithMigrationsOrWithout(): array
    {
        return [
            // migrations available, allow-no-migrations, expected exit code
            [false, false, 1],
            [true, false, 0],
            [false, true, 0],
            [true, true, 0],
        ];
    }

    /**
     * @dataProvider getMigrateWithMigrationsOrWithout
     */
    public function testMigrateWhenNoMigrationsAvailable(bool $hasMigrations, bool $allowNoMigration, int $expectedExitCode): void
    {
        $finder                    = $this->createMock(Finder::class);
        $factory                   = $this->createMock(MigrationFactory::class);
        $this->migrationRepository = new FilesystemMigrationsRepository([], [], $finder, $factory);
        $this->dependencyFactory->setService(MigrationsRepository::class, $this->migrationRepository);

        if ($hasMigrations) {
            $migration = $this->createMock(AbstractMigration::class);
            Helper::registerMigrationInstance($this->migrationRepository, new Version('A'), $migration);
        }

        $this->migrateCommandTester->execute(
            ['--allow-no-migration' => $allowNoMigration],
            ['interactive' => false]
        );

        if (! $hasMigrations) {
            $display = trim($this->migrateCommandTester->getDisplay(true));
            self::assertStringContainsString(
                sprintf(
                    '[%s] The version "latest" couldn\'t be reached, there are no registered migrations.',
                    $allowNoMigration ? 'WARNING' : 'ERROR'
                ),
                $display
            );
        }

        self::assertSame($expectedExitCode, $this->migrateCommandTester->getStatusCode());
    }

    /**
     * @return array<array<bool|string|null>>
     */
    public function getTargetAliases(): array
    {
        return [
            ['A', 'OK', 'A'], // already at A
            ['latest', 'OK', 'A'], // already at latest
            ['first', 'OK', null], // already at first
            ['next', 'ERROR', 'A'], // already at latest, no next available
            ['prev', 'ERROR', null], // no prev, already at first
            ['current', 'OK', 'A'], // already at latest, always
            ['current+1', 'ERROR', 'A'], // no current+1
        ];
    }

    /**
     * @dataProvider getTargetAliases
     */
    public function testExecuteAtVersion(string $targetAlias, string $level, ?string $executedMigration): void
    {
        if ($executedMigration !== null) {
            $result = new ExecutionResult(new Version($executedMigration));
            $this->storage->complete($result);
        }

        $this->migrateCommandTester->execute(
            ['version' => $targetAlias],
            ['interactive' => false]
        );

        $display = trim($this->migrateCommandTester->getDisplay(true));
        $aliases = ['current', 'latest', 'first'];

        if (in_array($targetAlias, $aliases, true)) {
            $message = sprintf(
                '[%s] Already at the %s version ("%s")',
                $level,
                $targetAlias,
                ($executedMigration ?? '0')
            );
        } elseif ($targetAlias === 'A') {
            $message = sprintf(
                '[%s] You are already at version "%s"',
                $level,
                $targetAlias
            );
        } else {
            $message = sprintf(
                '[%s] The version "%s" couldn\'t be reached, you are at version "%s"',
                $level,
                $targetAlias,
                ($executedMigration ?? '0')
            );
        }

        self::assertStringContainsString($message, $display);
        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteUnknownVersion(): void
    {
        $this->migrateCommandTester->execute(
            ['version' => 'unknown'],
            ['interactive' => false]
        );

        self::assertTrue(strpos($this->migrateCommandTester->getDisplay(true), 'Unknown version: unknown') !== false);
        self::assertSame(1, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecutedUnavailableMigrationsCancel(): void
    {
        $result = new ExecutionResult(new Version('345'));
        $this->storage->complete($result);

        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::never())
            ->method('migrate');

        $this->migrateCommandTester->setInputs(['no']);

        $this->migrateCommandTester->execute(['version' => 'prev']);

        $output = $this->migrateCommandTester->getDisplay(true);

        self::assertStringContainsString('Are you sure you wish to continue?', $output);
        self::assertStringContainsString('[ERROR] Migration cancelled!', $output);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    /**
     * @param bool|string|null $arg
     *
     * @dataProvider getWriteSqlValues
     */
    public function testExecuteWriteSql(bool $dryRun, $arg, ?string $path): void
    {
        $migrator = $this->createMock(DbalMigrator::class);

        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) use ($dryRun): array {
                self::assertSame($dryRun, $configuration->isDryRun());

                return ['A'];
            });

        if ($arg === false) {
            $this->queryWriter
                ->expects(self::never())
                ->method('write');
        } else {
            $this->queryWriter
                ->expects(self::once())
                ->method('write')
                ->with($path, 'up', ['A']);
        }

        $this->migrateCommandTester->execute(
            [
                '--write-sql' => $arg,
                '--dry-run' => $dryRun,
            ],
            ['interactive' => false]
        );
        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
    }

    /**
     * @return mixed[]
     */
    public function getWriteSqlValues(): array
    {
        return [
            // dry-run, write-path, path
            [true, false, null],
            [true, null, getcwd()],
            [true,  __DIR__ . '/_files', __DIR__ . '/_files'],

            [false, false, null],
            [false, null, getcwd()],
            [false,  __DIR__ . '/_files', __DIR__ . '/_files'],
        ];
    }

    public function testExecuteMigrate(): void
    {
        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $this->migrateCommandTester->setInputs(['yes']);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration): array {
                self::assertCount(1, $planList);
                self::assertEquals(new Version('A'), $planList->getFirst()->getVersion());

                return ['A'];
            });

        $this->migrateCommandTester->execute([]);

        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
        self::assertStringContainsString('[notice] Migrating up to A', trim($this->migrateCommandTester->getDisplay(true)));
        self::assertStringContainsString('[OK] Successfully migrated to version : A', trim($this->migrateCommandTester->getDisplay(true)));
    }

    public function testExecuteMigrateUpdatesMigrationsTableWhenNeeded(): void
    {
        $this->alterMetadataTable();
        // replace the old storage instance since it has cached the metadata state and we have altered it with alterMetadataTable() above
        $this->storage = new TableMetadataStorage(
            $this->connection,
            new AlphabeticalComparator(),
            $this->metadataConfiguration,
            $this->migrationRepository
        );
        $this->dependencyFactory->setService(MetadataStorage::class, $this->storage);

        $this->migrateCommandTester->execute([], ['interactive' => false]);

        $refreshedTable = $this->connection->createSchemaManager()
            ->introspectTable($this->metadataConfiguration->getTableName());

        self::assertFalse($refreshedTable->hasColumn('extra'));
    }

    public function testExecuteMigrateDoesNotUpdateMigrationsTableWhenSyaingNo(): void
    {
        $this->alterMetadataTable();

        $this->migrateCommandTester->setInputs(['no']);

        $this->migrateCommandTester->execute([]);

        $refreshedTable = $this->connection->createSchemaManager()
            ->introspectTable($this->metadataConfiguration->getTableName());

        self::assertTrue($refreshedTable->hasColumn('extra'));
    }

    public function testExecuteMigrateDown(): void
    {
        $migration = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('B'), $migration);

        $result = new ExecutionResult(new Version('A'));
        $this->storage->complete($result);

        $result = new ExecutionResult(new Version('B'));
        $this->storage->complete($result);

        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $this->migrateCommandTester->setInputs(['yes']);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration): array {
                self::assertCount(1, $planList);
                self::assertEquals(new Version('B'), $planList->getFirst()->getVersion());

                return ['A'];
            });

        $this->migrateCommandTester->execute(['version' => 'prev']);

        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
        self::assertStringContainsString('[notice] Migrating down to A', trim($this->migrateCommandTester->getDisplay(true)));
    }

    /**
     * @psalm-param array<string, bool|int|string|null> $input
     *
     * @dataProvider allOrNothing
     */
    public function testExecuteMigrateAllOrNothing(bool $default, array $input, bool $expected): void
    {
        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);
        $this->configuration->setAllOrNothing($default);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) use ($expected): array {
                self::assertSame($expected, $configuration->isAllOrNothing());
                self::assertCount(1, $planList);

                return ['A'];
            });

        $this->migrateCommandTester->execute(
            $input,
            ['interactive' => false]
        );

        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
    }

    /**
     * @psalm-return Generator<array{bool, array<string, bool|int|string|null>, bool}>
     */
    public function allOrNothing(): Generator
    {
        yield [false, ['--all-or-nothing' => false], false];
        yield [false, ['--all-or-nothing' => 0], false];
        yield [false, ['--all-or-nothing' => '0'], false];

        yield [false, ['--all-or-nothing' => true], true];
        yield [false, ['--all-or-nothing' => 1], true];
        yield [false, ['--all-or-nothing' => '1'], true];
        yield [false, ['--all-or-nothing' => null], true];

        yield [true, ['--all-or-nothing' => false], false];
        yield [true, ['--all-or-nothing' => 0], false];
        yield [true, ['--all-or-nothing' => '0'], false];

        yield [true, [], true];
        yield [false, [], false];
    }

    public function testExecuteMigrateCancelExecutedUnavailableMigrations(): void
    {
        $result = new ExecutionResult(new Version('345'));
        $this->storage->complete($result);

        $migrator = $this->createMock(DbalMigrator::class);

        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::never())
            ->method('migrate');

        $this->migrateCommandTester->setInputs(['yes', 'no']);

        $this->migrateCommandTester->execute(['version' => 'latest']);

        $output = $this->migrateCommandTester->getDisplay(true);

        self::assertStringContainsString('[WARNING] You have 1 previously executed migrations in the database that are not registered migrations.', $output);
        self::assertStringContainsString('WARNING! You are about to execute a migration in database "main" that could result in schema changes and data loss. Are you sure you wish to continue?', $output);
        self::assertStringContainsString('[ERROR] Migration cancelled!', $output);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteMigrateCancel(): void
    {
        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::never())
            ->method('migrate');

        $this->migrateCommandTester->setInputs(['no']);

        $this->migrateCommandTester->execute(['version' => 'latest']);

        $output = $this->migrateCommandTester->getDisplay(true);

        self::assertStringContainsString('WARNING! You are about to execute a migration in database "main" that could result in schema changes and data loss. Are you sure you wish to continue?', $output);
        self::assertStringContainsString('[ERROR] Migration cancelled!', $output);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    protected function setUp(): void
    {
        $this->metadataConfiguration = new TableMetadataStorageConfiguration();

        $this->configuration = new Configuration();
        $this->configuration->setMetadataStorageConfiguration($this->metadataConfiguration);

        $this->connection = $this->getSqliteConnection();

        $this->dependencyFactory = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));

        $this->queryWriter = $this->createMock(QueryWriter::class);
        $this->dependencyFactory->setService(QueryWriter::class, $this->queryWriter);

        $finder                    = $this->createMock(Finder::class);
        $factory                   = $this->createMock(MigrationFactory::class);
        $this->migrationRepository = new FilesystemMigrationsRepository([], [], $finder, $factory);

        $migration = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('A'), $migration);

        $this->dependencyFactory->setService(MigrationsRepository::class, $this->migrationRepository);

        $this->migrateCommand = new MigrateCommand($this->dependencyFactory);

        $this->questions = $this->createMock(QuestionHelper::class);
        $this->migrateCommand->setHelperSet(new HelperSet(['question' => $this->questions]));

        $this->migrateCommandTester = new CommandTester($this->migrateCommand);

        $this->storage = new TableMetadataStorage(
            $this->connection,
            new AlphabeticalComparator(),
            $this->metadataConfiguration,
            $this->migrationRepository
        );
        $this->storage->ensureInitialized();

        $this->dependencyFactory->setService(MetadataStorage::class, $this->storage);
    }

    private function alterMetadataTable(): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $originalTable = $schemaManager
            ->introspectTable($this->metadataConfiguration->getTableName());

        $modifiedTable = clone $originalTable;
        $modifiedTable->addColumn('extra', Types::STRING, ['notnull' => false]);

        $diff = $schemaManager->createComparator()->compareTables($originalTable, $modifiedTable);
        if ($diff->isEmpty()) {
            return;
        }

        $this->connection->createSchemaManager()->alterTable($diff);
    }
}
