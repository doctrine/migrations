<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;

class MigrateCommandTest extends CommandTestCase
{
    use DialogSupport;

    const VERSION = '20160705000000';

    private $migration;

    public function testPreviousVersionErrorsWhenThereIsNoPreviousVersion()
    {
        $this->willResolveVersionAlias('prev', null);

        list($tester, $statusCode) = $this->executeCommand([
            'version' => 'prev',
        ]);

        $this->assertSame(1, $statusCode);
        $this->assertContains('Already at first version', $tester->getDisplay());
    }

    public function testNextVersionErrorsWhenThereIsNoNextVersion()
    {
        $this->willResolveVersionAlias('next', null);

        list($tester, $statusCode) = $this->executeCommand([
            'version' => 'next',
        ]);

        $this->assertSame(1, $statusCode);
        $this->assertContains('Already at latest version', $tester->getDisplay());
    }

    public function testUnknownVersionAliasErrors()
    {
        $this->willResolveVersionAlias('nope', null);

        list($tester, $statusCode) = $this->executeCommand([
            'version' => 'nope',
        ]);

        $this->assertSame(1, $statusCode);
        $this->assertContains('Unknown version: nope', $tester->getDisplay());
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

        $this->assertSame(1, $statusCode);
        $this->assertContains('previously executed migrations in the database that are not registered', $tester->getDisplay());
    }

    public function testWriteSqlOutputsToCurrentWorkingDirWhenWriteSqlArgumentIsTrue()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('writeSqlFile')
            ->with(getcwd(), self::VERSION);

        list($tester, $statusCode) = $this->executeCommand([
            '--write-sql' => true,
        ]);

        $this->assertSame(0, $statusCode);
    }

    public function testWriteSqlOutputsToTheProvidedPathWhenProvided()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('writeSqlFile')
            ->with(__DIR__, self::VERSION);

        list($tester, $statusCode) = $this->executeCommand([
            '--write-sql' => __DIR__,
        ]);

        $this->assertSame(0, $statusCode);
    }

    public function testCommandExecutesMigrationsWithDryRunWhenProvided()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('migrate')
            ->with(self::VERSION, true, true);

        list($tester, $statusCode) = $this->executeCommand([
            '--dry-run' => true,
            '--query-time' => true,
        ]);

        $this->assertSame(0, $statusCode);
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

        list($tester, $statusCode) = $this->executeCommand([
            '--dry-run' => false
        ]);

        $this->assertSame(1, $statusCode);
        $this->assertContains('Migration cancelled', $tester->getDisplay());
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
                $this->assertTrue($confirm());
                return ['SELECT 1'];
            });

        list($tester, $statusCode) = $this->executeCommand([
            '--dry-run' => false
        ]);

        $this->assertSame(0, $statusCode);
    }

    public function testCommandMigratesWhenTheConsoleIsInNonInteractiveMode()
    {
        $this->willResolveVersionAlias('latest', self::VERSION);
        $this->withExecutedAndAvailableMigrations();
        $this->migration->expects($this->once())
            ->method('migrate')
            ->with(self::VERSION, false, false)
            ->willReturnCallback(function ($version, $dryRun, $timed, callable $confirm) {
                $this->assertTrue($confirm());
                return ['SELECT 1'];
            });

        list($tester, $statusCode) = $this->executeCommand([
            '--dry-run' => false
        ], ['interactive' => false]);

        $this->assertSame(0, $statusCode);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->configureDialogs($this->app);
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
