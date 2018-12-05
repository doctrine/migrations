<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;

class MigrateCommandTest extends TestCase
{
    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var MigrationRepository|MockObject */
    private $migrationRepository;

    /** @var Configuration|MockObject */
    private $configuration;

    /** @var MigrateCommand|MockObject */
    private $migrateCommand;

    public function testExecuteCouldNotResolveAlias() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('1234');

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('1234')
            ->willReturn('');

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteAlreadyAtFirstVersion() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn(null);

        $output->expects(self::at(4))
            ->method('writeln')
            ->with('<error>Already at first version.</error>');

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteAlreadyAtLatestVersion() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('next');

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('next')
            ->willReturn(null);

        $output->expects(self::at(4))
            ->method('writeln')
            ->with('<error>Already at latest version.</error>');

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteTheDeltaCouldNotBeReached() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('current-1');

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('current-1')
            ->willReturn(null);

        $output->expects(self::at(4))
            ->method('writeln')
            ->with('<error>The delta couldn\'t be reached.</error>');

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteUnknownVersion() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('unknown');

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('unknown')
            ->willReturn(null);

        $output->expects(self::at(4))
            ->method('writeln')
            ->with('<error>Unknown version: unknown</error>');

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    public function testExecutedUnavailableMigrationsCancel() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn('1234');

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1235']);

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(false);

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteWriteSqlCustomPath() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $migration = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($migration);

        $migration->expects(self::once())
            ->method('writeSqlFile')
            ->with('test', '1234');

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn('test');

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn('1234');

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1235']);

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(true);

        self::assertSame(0, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteWriteSqlCurrentWorkingDirectory() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($migrator);

        $migrator->expects(self::once())
            ->method('writeSqlFile')
            ->with(getcwd(), '1234');

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(null);

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn('1234');

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1235']);

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(true);

        self::assertSame(0, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteMigrate() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($migrator);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn('1234');

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1235']);

        $this->migrateCommand->expects(self::at(0))
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(true);

        $this->migrateCommand->expects(self::at(1))
            ->method('canExecute')
            ->with('WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)')
            ->willReturn(true);

        $migrator->expects(self::once())
            ->method('migrate')
            ->with('1234');

        self::assertSame(0, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteMigrateAllOrNothing() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($migrator);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $input->expects(self::at(2))
            ->method('getOption')
            ->with('allow-no-migration')
            ->willReturn(false);

        $input->expects(self::at(3))
            ->method('getOption')
            ->with('query-time')
            ->willReturn(false);

        $input->expects(self::at(4))
            ->method('getOption')
            ->with('dry-run')
            ->willReturn(false);

        $input->expects(self::at(5))
            ->method('getOption')
            ->with('all-or-nothing')
            ->willReturn(true);

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn('1234');

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1235']);

        $this->migrateCommand->expects(self::at(0))
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(true);

        $this->migrateCommand->expects(self::at(1))
            ->method('canExecute')
            ->with('WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)')
            ->willReturn(true);

        $migrator->expects(self::once())
            ->method('migrate')
            ->with('1234');

        self::assertSame(0, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteMigrateCancelExecutedUnavailableMigrations() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::never())
            ->method('getMigrator')
            ->willReturn($migrator);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn('1234');

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1235']);

        $this->migrateCommand->expects(self::once())
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(false);

        $migrator->expects(self::never())
            ->method('migrate');

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    public function testExecuteMigrateCancel() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $migrator = $this->createMock(Migrator::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($migrator);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('version')
            ->willReturn('prev');

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $this->configuration->expects(self::once())
            ->method('setIsDryRun')
            ->with(false);

        $this->configuration->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('prev')
            ->willReturn('1234');

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1235']);

        $this->migrateCommand->expects(self::at(0))
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(true);

        $this->migrateCommand->expects(self::at(1))
            ->method('canExecute')
            ->with('WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)')
            ->willReturn(false);

        $migrator->expects(self::never())
            ->method('migrate');

        self::assertSame(1, $this->migrateCommand->execute($input, $output));
    }

    protected function setUp() : void
    {
        $this->configuration       = $this->createMock(Configuration::class);
        $this->migrationRepository = $this->createMock(MigrationRepository::class);
        $this->dependencyFactory   = $this->createMock(DependencyFactory::class);

        $this->migrateCommand = $this->getMockBuilder(MigrateCommand::class)
            ->setMethods(['canExecute'])
            ->getMock();

        $this->migrateCommand->setMigrationConfiguration($this->configuration);
        $this->migrateCommand->setMigrationRepository($this->migrationRepository);
        $this->migrateCommand->setDependencyFactory($this->dependencyFactory);
    }
}
