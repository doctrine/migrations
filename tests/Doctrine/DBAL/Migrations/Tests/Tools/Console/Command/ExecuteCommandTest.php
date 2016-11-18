<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;

class ExecuteCommandTest extends CommandTestCase
{
    use DialogSupport;

    const VERSION = '20160705000000';

    private $version;

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
        parent::setUp();
        $this->version = $this->mockWithoutConstructor(Version::class);
        $this->config->expects($this->once())
            ->method('getVersion')
            ->with(self::VERSION)
            ->willReturn($this->version);

        $this->configureDialogs($this->app);
    }

    protected function createCommand()
    {
        return new ExecuteCommand();
    }

    protected function executeCommand(array $args, array $options=[])
    {
        $args['version'] = self::VERSION;
        return parent::executeCommand($args, $options);
    }

    private function mockWithoutConstructor($cls)
    {
        return $this->getMockBuilder($cls)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
