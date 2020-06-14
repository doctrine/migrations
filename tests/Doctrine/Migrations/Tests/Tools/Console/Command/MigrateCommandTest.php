<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\TableDiff;
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
    /** @var DependencyFactory */
    private $dependencyFactory;

    /** @var Configuration */
    private $configuration;

    /** @var MigrateCommand */
    private $migrateCommand;

    /** @var CommandTester */
    private $migrateCommandTester;

    /** @var MetadataStorage */
    private $storage;

    /** @var MockObject */
    private $queryWriter;

    /** @var MockObject|QuestionHelper */
    private $questions;

    /** @var MigrationsRepository */
    private $migrationRepository;

    /** @var Connection */
    private $connection;

    /** @var TableMetadataStorageConfiguration */
    private $metadataConfiguration;

    public function testTargetUnknownVersion() : void
    {
        $result = new ExecutionResult(new Version('A'));
        $this->storage->complete($result);

        $this->migrateCommandTester->execute(
            ['version' => 'A'],
            ['interactive' => false]
        );

        self::assertStringContainsString('[ERROR] Unknown version: A', $this->migrateCommandTester->getDisplay(true));
        self::assertSame(1, $this->migrateCommandTester->getStatusCode());
    }

    /**
     * @return array<array<bool|string|null>>
     */
    public function getTargetAliases() : array
    {
        return [
            ['latest', true, 'A'],
            ['latest', false, 'A'],
            ['first', true, null],
            ['first', false, null],
            ['next', true, 'A'],
            ['next', false, 'A'],
            ['current+1', false, 'A'],
            ['current+1', true, 'A'],
        ];
    }

    /**
     * @dataProvider getTargetAliases
     */
    public function testExecuteAtVersion(string $targetAlias, bool $allowNoMigration, ?string $executedMigration) : void
    {
        if ($executedMigration !== null) {
            $result = new ExecutionResult(new Version($executedMigration));
            $this->storage->complete($result);
        }

        $this->migrateCommandTester->execute(
            [
                'version' => $targetAlias,
                '--allow-no-migration' => $allowNoMigration,
            ],
            ['interactive' => false]
        );

        $display = trim($this->migrateCommandTester->getDisplay(true));
        $aliases = ['next', 'latest'];

        if (in_array($targetAlias, $aliases, true)) {
            $message = '[%s] Already at "%s" version ("%s")';
        } else {
            $message = '[%s] The version "%s" couldn\'t be reached, you are at version "%s"';
        }

        self::assertStringContainsString(
            $display,
            sprintf(
                $message,
                ($allowNoMigration ? 'WARNING' : 'ERROR'),
                $targetAlias,
                ($executedMigration ?? '0')
            )
        );

        self::assertSame($allowNoMigration ? 0 : 1, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteUnknownVersion() : void
    {
        $this->migrateCommandTester->execute(
            ['version' => 'unknown'],
            ['interactive' => false]
        );

        self::assertTrue(strpos($this->migrateCommandTester->getDisplay(true), 'Unknown version: unknown') !== false);
        self::assertSame(1, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecutedUnavailableMigrationsCancel() : void
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
    public function testExecuteWriteSql(bool $dryRun, $arg, ?string $path) : void
    {
        $migrator = $this->createMock(DbalMigrator::class);

        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) use ($dryRun) : array {
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
    public function getWriteSqlValues() : array
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

    public function testExecuteMigrate() : void
    {
        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $this->migrateCommandTester->setInputs(['yes']);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) : array {
                self::assertCount(1, $planList);
                self::assertEquals(new Version('A'), $planList->getFirst()->getVersion());

                return ['A'];
            });

        $this->migrateCommandTester->execute([]);

        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
        self::assertStringContainsString('[notice] Migrating up to A', trim($this->migrateCommandTester->getDisplay(true)));
    }

    public function testExecuteMigrateUpdatesMigrationsTableWhenNeeded() : void
    {
        $this->alterMetadataTable();

        $this->migrateCommandTester->execute([], ['interactive' => false]);

        $refreshedTable = $this->connection->getSchemaManager()
            ->listTableDetails($this->metadataConfiguration->getTableName());

        self::assertFalse($refreshedTable->hasColumn('extra'));
    }

    public function testExecuteMigrateDoesNotUpdateMigrationsTableWhenSyaingNo() : void
    {
        $this->alterMetadataTable();

        $this->migrateCommandTester->setInputs(['no']);

        $this->migrateCommandTester->execute([]);

        $refreshedTable = $this->connection->getSchemaManager()
            ->listTableDetails($this->metadataConfiguration->getTableName());

        self::assertTrue($refreshedTable->hasColumn('extra'));
    }

    public function testExecuteMigrateDown() : void
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
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) : array {
                self::assertCount(1, $planList);
                self::assertEquals(new Version('B'), $planList->getFirst()->getVersion());

                return ['A'];
            });

        $this->migrateCommandTester->execute(['version' => 'prev']);

        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
        self::assertStringContainsString('[notice] Migrating down to A', trim($this->migrateCommandTester->getDisplay(true)));
    }

    public function testExecuteMigrateAllOrNothing() : void
    {
        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) : array {
                self::assertTrue($configuration->isAllOrNothing());
                self::assertCount(1, $planList);

                return ['A'];
            });

        $this->migrateCommandTester->execute(
            ['--all-or-nothing' => true],
            ['interactive' => false]
        );

        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteMigrateCancelExecutedUnavailableMigrations() : void
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
        self::assertStringContainsString('WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue?', $output);
        self::assertStringContainsString('[ERROR] Migration cancelled!', $output);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteMigrateCancel() : void
    {
        $migrator = $this->createMock(DbalMigrator::class);
        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::never())
            ->method('migrate');

        $this->migrateCommandTester->setInputs(['no']);

        $this->migrateCommandTester->execute(['version' => 'latest']);

        $output = $this->migrateCommandTester->getDisplay(true);

        self::assertStringContainsString('WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue?', $output);
        self::assertStringContainsString('[ERROR] Migration cancelled!', $output);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    protected function setUp() : void
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

    private function alterMetadataTable() : void
    {
        $originalTable = $this->connection->getSchemaManager()
            ->listTableDetails($this->metadataConfiguration->getTableName());

        $modifiedTable = clone $originalTable;
        $modifiedTable->addColumn('extra', Types::STRING, ['notnull' => false]);

        $comparator = new Comparator();
        $diff       = $comparator->diffTable($originalTable, $modifiedTable);
        if (! ($diff instanceof TableDiff)) {
            return;
        }

        $this->connection->getSchemaManager()->alterTable($diff);
    }
}
