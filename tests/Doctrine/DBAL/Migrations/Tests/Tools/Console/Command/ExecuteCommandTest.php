<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;

class ExecuteCommandTest extends MigrationTestCase
{
    const VERSION = '20160705000000';

    private $commmand, $app, $config, $version, $questions, $isDialogHelper;

    public function testWriteSqlCommandOutputsSqlFileToTheCurrentWorkingDirectory()
    {
        $this->version->expects($this->once())
            ->method('writeSqlFile')
            ->with(getcwd(), 'up');

        list(, $statusCode) = $this->executeCommand([
            '--write-sql' => true,
            '--up' => true,
        ]);

        $this->assertSame(0, $statusCode);
    }

    public function testWriteSqlOutputsSqlFileToTheSpecifiedDirectory()
    {
        $this->version->expects($this->once())
            ->method('writeSqlFile')
            ->with(__DIR__, 'down');

        list(, $statusCode) = $this->executeCommand([
            '--write-sql' => __DIR__,
            '--down' => true,
        ]);

        $this->assertSame(0, $statusCode);
    }

    public function testNoMigrationIsExecuteWhenTheUserDoesNotConfirmTheAction()
    {
        $this->willAskConfirmationAndReturn(false);
        $this->version->expects($this->never())
            ->method('execute');

        list($tester, $statusCode) = $this->executeCommand([]);

        $this->assertSame(0, $statusCode);
        $this->assertContains('Migration cancelled', $tester->getDisplay());
    }

    public function testMigrationsIsExecutedWhenTheUserConfirmsTheAction()
    {
        $this->willAskConfirmationAndReturn(true);
        $this->version->expects($this->once())
            ->method('execute')
            ->with('up', true, true);

        list(, $statusCode) = $this->executeCommand([
            '--dry-run' => true,
            '--query-time' => true,
        ]);

        $this->assertSame(0, $statusCode);
    }

    public function testMigrationIsExecutedWhenTheConsoleIsNotInInteractiveMode()
    {
        $this->questions->expects($this->never())
            ->method($this->isDialogHelper ? 'askConfirmation' : 'ask');
        $this->version->expects($this->once())
            ->method('execute')
            ->with('up', true, true);

        list(, $statusCode) = $this->executeCommand([
            '--dry-run' => true,
            '--query-time' => true,
        ], ['interactive' => false]);

        $this->assertSame(0, $statusCode);
    }

    protected function setUp()
    {
        $this->config = $this->mockWithoutConstructor(Configuration::class);
        $this->config->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->getSqliteConnection());
        $this->version = $this->mockWithoutConstructor(Version::class);
        $this->config->expects($this->once())
            ->method('getVersion')
            ->with(self::VERSION)
            ->willReturn($this->version);
        $this->command = new ExecuteCommand();
        $this->command->setMigrationConfiguration($this->config);
        $this->app = new Application();
        $this->app->add($this->command);

        if (class_exists(QuestionHelper::class)) {
            $this->isDialogHelper = false;
            $this->questions = $this->getMock(QuestionHelper::class);
        } else {
            $this->isDialogHelper = true;
            $this->questions = $this->getMock(DialogHelper::class);
        }
        $this->app->getHelperSet()->set($this->questions, $this->isDialogHelper ? 'dialog' : 'question');
    }

    private function mockWithoutConstructor($cls)
    {
        return $this->getMockBuilder($cls)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createCommandTester()
    {
        return new CommandTester($this->app->find('migrations:execute'));
    }

    private function executeCommand(array $args, array $options=[])
    {
        $tester = $this->createCommandTester();
        $statusCode = $tester->execute(array_replace([
            'command' => 'migrations:execute',
            'version' => self::VERSION,
        ], $args), $options);

        return [$tester, $statusCode];
    }

    private function willAskConfirmationAndReturn($bool)
    {
        $this->questions->expects($this->once())
            ->method($this->isDialogHelper ? 'askConfirmation' : 'ask')
            ->willReturn($bool);
    }
}
