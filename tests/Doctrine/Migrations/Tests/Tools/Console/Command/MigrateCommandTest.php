<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use function getcwd;
use function strpos;
use function sys_get_temp_dir;

class MigrateCommandTest extends MigrationTestCase
{
    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var Configuration */
    private $configuration;

    /** @var MigrateCommand|MockObject */
    private $migrateCommand;

    /** @var CommandTester */
    private $migrateCommandTester;

    /** @var MetadataStorage */
    private $storage;

    /** @var MockObject */
    private $queryWriter;

    public function testExecuteEmptyMigrationPlanCausesException() : void
    {
        $result = new ExecutionResult(new Version('A'));
        $this->storage->complete($result);

        $this->migrateCommandTester->execute(
            ['version' => 'first'],
            ['interactive' => false]
        );

        self::assertTrue(strpos($this->migrateCommandTester->getDisplay(true), 'Could not find any migrations to execute') !== false);
        self::assertSame(1, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteAlreadyAtFirstVersion() : void
    {
        $result = new ExecutionResult(new Version('A'));
        $this->storage->complete($result);

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

        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::any())
            ->method('getMigrator')
            ->willReturn($migrator);

        $migrator->expects(self::never())
            ->method('migrate');

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(false);

        $this->migrateCommandTester->execute(['version' => 'prev']);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    /**
     * @param mixed $arg
     *
     * @dataProvider getWriteSqlValues
     */
    public function testExecuteWriteSql($arg, string $path) : void
    {
        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::any())
            ->method('getMigrator')
            ->willReturn($migrator);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) {
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
        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::any())
            ->method('getMigrator')
            ->willReturn($migrator);

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(true);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) {
                self::assertCount(1, $planList);
                self::assertEquals(new Version('A'), $planList->getFirst()->getVersion());

                return ['A'];
            });

        $this->migrateCommandTester->execute([]);

        self::assertSame(0, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteMigrateAllOrNothing() : void
    {
        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::any())
            ->method('getMigrator')
            ->willReturn($migrator);

        $migrator->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) {
                self::assertTrue($configuration->isAllOrNothing());
                self::assertCount(1, $planList);

                return ['A'];
            });

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(true);

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

        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($migrator);

        $migrator->expects(self::never())
            ->method('migrate');

        $this->migrateCommand->expects(self::at(0))
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(true);

        $this->migrateCommand->expects(self::at(1))
            ->method('canExecute')
            ->with('WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)')
            ->willReturn(false);

        $this->migrateCommandTester->execute(['version' => 'latest']);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    public function testExecuteMigrateCancel() : void
    {
        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($migrator);

        $migrator->expects(self::never())
            ->method('migrate');

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->with('WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)')
            ->willReturn(false);

        $this->migrateCommandTester->execute(['version' => 'latest']);

        self::assertSame(3, $this->migrateCommandTester->getStatusCode());
    }

    protected function setUp() : void
    {
        $this->configuration = new Configuration();
        $this->configuration->addMigrationsDirectory('FooNs', sys_get_temp_dir());

        $connection = $this->getSqliteConnection();

        $this->dependencyFactory = $this->getMockBuilder(DependencyFactory::class)
            ->setConstructorArgs([$this->configuration, $connection])
            ->setMethods(['getMigrator', 'getQueryWriter'])
            ->getMock();

        $this->queryWriter = $this->createMock(QueryWriter::class);
        $this->dependencyFactory->expects(self::any())
            ->method('getQueryWriter')
            ->willReturn($this->queryWriter);

        $migration = $this->createMock(AbstractMigration::class);

        $repo = $this->dependencyFactory->getMigrationRepository();
        $repo->registerMigrationInstance(new Version('A'), $migration);

        $this->migrateCommand = $this->getMockBuilder(MigrateCommand::class)
            ->setConstructorArgs([null, $this->dependencyFactory])
            ->setMethods(['canExecute'])
            ->getMock();

        $this->migrateCommandTester = new CommandTester($this->migrateCommand);

        $this->storage = $this->dependencyFactory->getMetadataStorage();
    }
}
