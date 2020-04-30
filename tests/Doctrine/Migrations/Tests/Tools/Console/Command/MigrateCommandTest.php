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
use function strpos;
use function sys_get_temp_dir;
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

    public function testExecuteEmptyMigrationPlanCausesException() : void
    {
        $result = new ExecutionResult(new Version('A'));
        $this->storage->complete($result);

        $this->migrateCommandTester->execute(
            ['version' => 'A'],
            ['interactive' => false]
        );

        self::assertTrue(strpos($this->migrateCommandTester->getDisplay(true), 'Could not find any migrations to execute') !== false);
        self::assertSame(1, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteAlreadyAtFirstVersion() : void
    {
        $this->migrateCommandTester->execute(
            [
                'version' => 'first',
                '--allow-no-migration' => true,
            ],
            ['interactive' => false]
        );

        self::assertTrue(strpos($this->migrateCommandTester->getDisplay(true), 'Already at first version.') !== false);
        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteAlreadyAtLatestVersion() : void
    {
        $result = new ExecutionResult(new Version('A'));
        $this->storage->complete($result);

        $this->migrateCommandTester->execute(
            [
                'version' => 'latest',
                '--allow-no-migration' => true,
            ],
            ['interactive' => false]
        );

        self::assertTrue(strpos($this->migrateCommandTester->getDisplay(true), 'Already at latest version.') !== false);
        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteTheDeltaCouldNotBeReached() : void
    {
        $result = new ExecutionResult(new Version('A'));
        $this->storage->complete($result);

        $this->migrateCommandTester->execute(
            ['version' => 'current+1'],
            ['interactive' => false]
        );

        self::assertTrue(strpos($this->migrateCommandTester->getDisplay(true), 'The delta couldn\'t be reached.') !== false);
        self::assertSame(1, $this->migrateCommandTester->getStatusCode());
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
     * @param mixed $arg
     *
     * @dataProvider getWriteSqlValues
     */
    public function testExecuteWriteSql($arg, string $path) : void
    {
        $migrator = $this->createMock(DbalMigrator::class);

        $this->dependencyFactory->setService(Migrator::class, $migrator);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) : array {
                return ['A'];
            });

        $this->queryWriter->expects(self::once())
            ->method('write')
            ->with($path, 'up', ['A']);

        $this->migrateCommandTester->execute(
            ['--write-sql' => $arg],
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
            [true, getcwd()],
            [ __DIR__ . '/_files', __DIR__ . '/_files'],
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

        $this->configuration->addMigrationsDirectory('FooNs', sys_get_temp_dir());

        $this->connection = $this->getSqliteConnection();

        $this->dependencyFactory = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));

        $this->queryWriter = $this->createMock(QueryWriter::class);
        $this->dependencyFactory->setService(QueryWriter::class, $this->queryWriter);

        $finder                    = $this->createMock(Finder::class);
        $factory                   = $this->createMock(MigrationFactory::class);
        $this->migrationRepository = new FilesystemMigrationsRepository([], [], $finder, $factory, new AlphabeticalComparator());

        $migration = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('A'), $migration);

        $this->dependencyFactory->setService(MigrationsRepository::class, $this->migrationRepository);

        $this->migrateCommand = new MigrateCommand($this->dependencyFactory);

        $this->questions = $this->createMock(QuestionHelper::class);
        $this->migrateCommand->setHelperSet(new HelperSet(['question' => $this->questions]));

        $this->migrateCommandTester = new CommandTester($this->migrateCommand);

        $this->storage = new TableMetadataStorage($this->connection, $this->metadataConfiguration, $this->migrationRepository);
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
