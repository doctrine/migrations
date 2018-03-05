<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;

class MigrateCommandTest extends CommandTestCase
{
    use DialogSupport;

    private const VERSION = '20160705000000';

    /** @var Migration */
    private $migration;

    protected function setUp()
    {
        parent::setUp();
        $this->configureDialogs($this->app);
    }

    public function testPreviousVersionErrorsWhenThereIsNoPreviousVersion()
    {
        $this->willResolveVersionAlias('prev', null);

        list($tester, $statusCode) = $this->executeCommand(['version' => 'prev']);

        self::assertSame(1, $statusCode);
        self::assertContains('Already at first version', $tester->getDisplay());
    }

    public function testNextVersionErrorsWhenThereIsNoNextVersion()
    {
        $this->willResolveVersionAlias('next', null);

        list($tester, $statusCode) = $this->executeCommand(['version' => 'next']);

        self::assertSame(1, $statusCode);
        self::assertContains('Already at latest version', $tester->getDisplay());
    }

    public function testUnknownVersionAliasErrors()
    {
        $this->willResolveVersionAlias('nope', null);

        list($tester, $statusCode) = $this->executeCommand(['version' => 'nope']);

        self::assertSame(1, $statusCode);
        self::assertContains('Unknown version: nope', $tester->getDisplay());
    }

    public function testExecuteUnavailableMigrationsErrorWhenTheUserDeclinesToContinue()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->config->expects($this->once())
            ->method('getMigratedVersions')
            ->willReturn([self::VERSION]);
        $this->config->expects($this->once())
            ->method('getAvailableVersions')
            ->willReturn([]);
        $this->willAskConfirmationAndReturn(false);

        list($tester, $statusCode) = $this->executeCommand([]);

        self::assertSame(1, $statusCode);
        self::assertContains('previously executed migrations in the database that are not registered', $tester->getDisplay());
    }

    public function testWriteSqlOutputsToCurrentWorkingDirWhenWriteSqlArgumentIsTrue()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('writeSqlFile')
            ->with(getcwd(), self::VERSION);

        list($tester, $statusCode) = $this->executeCommand(['--write-sql' => true]);

        self::assertSame(0, $statusCode);
    }

    public function testWriteSqlOutputsToTheProvidedPathWhenProvided()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('writeSqlFile')
            ->with(__DIR__, self::VERSION);

        list($tester, $statusCode) = $this->executeCommand(['--write-sql' => __DIR__]);

        self::assertSame(0, $statusCode);
    }

    public function testCommandExecutesMigrationsWithDryRunWhenProvided()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('migrate')
            ->with(self::VERSION, true, true);

        // dry run is set before getting the migrated versions
        $this->config->expects($this->at(2))
            ->method('setIsDryRun')
            ->with(true);
        $this->config->expects($this->at(3))
            ->method('getMigratedVersions');
        $this->config->expects($this->at(4))
            ->method('getAvailableVersions');

        list($tester, $statusCode) = $this->executeCommand([
            '--dry-run' => true,
            '--query-time' => true,
        ]);

        self::assertSame(0, $statusCode);
    }

    public function testCommandExitsWithErrorWhenUserDeclinesToContinue()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->willAskConfirmationAndReturn(false);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('migrate')
            ->with(self::VERSION, false, false)
            ->willReturnCallback(function ($version, $dryRun, $timed, callable $confirm) {
                return $confirm();
            });

        list($tester, $statusCode) = $this->executeCommand(['--dry-run' => false]);

        self::assertSame(1, $statusCode);
        self::assertContains('Migration cancelled', $tester->getDisplay());
    }

    public function testCommandMigratesWhenTheUserAcceptsThePrompt()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->willAskConfirmationAndReturn(true);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('migrate')
            ->with(self::VERSION, false, false)
            ->willReturnCallback(function ($version, $dryRun, $timed, callable $confirm) {
                self::assertTrue($confirm());
                return ['SELECT 1'];
            });

        list($tester, $statusCode) = $this->executeCommand(['--dry-run' => false]);

        self::assertSame(0, $statusCode);
    }

    public function testCommandMigratesWhenTheConsoleIsInNonInteractiveMode()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('migrate')
            ->with(self::VERSION, false, false)
            ->willReturnCallback(function ($version, $dryRun, $timed, callable $confirm) {
                self::assertTrue($confirm());
                return ['SELECT 1'];
            });

        list($tester, $statusCode) = $this->executeCommand(['--dry-run' => false], ['interactive' => false]);

        self::assertSame(0, $statusCode);
    }

    /**
     * Mocks the `createMigration` method to return a mock migration class
     * so we can test.
     */
    protected function createCommand()
    {
        $this->migration = $this->createMock(Migration::class);

        $cmd = $this->getMockForAbstractClass(
            MigrateCommand::class,
            [],
            '',
            true,
            true,
            true,
            ['createMigration']
        );

        $cmd->method('createMigration')
            ->with($this->isInstanceOf(Configuration::class))
            ->willReturn($this->migration);

        return $cmd;
    }

    private function willResolveVersionAlias($alias, $returns)
    {
        $this->config->expects($this->once())
            ->method('resolveVersionAlias')
            ->with($alias)
            ->willReturn($returns);
    }

    private function withExecutedAndAvailableMigrations()
    {
        $this->config->expects($this->once())
            ->method('getMigratedVersions')
            ->willReturn([]);
        $this->config->expects($this->once())
            ->method('getAvailableVersions')
            ->willReturn([self::VERSION]);
    }
}
