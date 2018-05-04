<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\DBAL\Migrations\Version;
use function getcwd;

class ExecuteCommandTest extends CommandTestCase
{
    use DialogSupport;

    public const VERSION = '20160705000000';

    /** @var Version */
    private $version;

    public function testWriteSqlCommandOutputsSqlFileToTheCurrentWorkingDirectory() : void
    {
        $this->version->expects($this->once())
            ->method('writeSqlFile')
            ->with(getcwd(), 'up');

        list(, $statusCode) = $this->executeCommand([
            '--write-sql' => true,
            '--up' => true,
        ]);

        self::assertSame(0, $statusCode);
    }

    public function testWriteSqlOutputsSqlFileToTheSpecifiedDirectory() : void
    {
        $this->version->expects($this->once())
            ->method('writeSqlFile')
            ->with(__DIR__, 'down');

        list(, $statusCode) = $this->executeCommand([
            '--write-sql' => __DIR__,
            '--down' => true,
        ]);

        self::assertSame(0, $statusCode);
    }

    public function testNoMigrationIsExecuteWhenTheUserDoesNotConfirmTheAction() : void
    {
        $this->willAskConfirmationAndReturn(false);
        $this->version->expects($this->never())
            ->method('execute');

        list($tester, $statusCode) = $this->executeCommand([]);

        self::assertSame(0, $statusCode);
        self::assertContains('Migration cancelled', $tester->getDisplay());
    }

    public function testMigrationsIsExecutedWhenTheUserConfirmsTheAction() : void
    {
        $this->willAskConfirmationAndReturn(true);
        $this->version->expects($this->once())
            ->method('execute')
            ->with('up', true, true);

        list(, $statusCode) = $this->executeCommand([
            '--dry-run' => true,
            '--query-time' => true,
        ]);

        self::assertSame(0, $statusCode);
    }

    public function testMigrationIsExecutedWhenTheConsoleIsNotInInteractiveMode() : void
    {
        $this->questions->expects($this->never())
            ->method('ask');

        $this->version->expects($this->once())
            ->method('execute')
            ->with('up', true, true);

        list(, $statusCode) = $this->executeCommand([
            '--dry-run' => true,
            '--query-time' => true,
        ], ['interactive' => false]);

        self::assertSame(0, $statusCode);
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->version = $this->getMockBuilder(Version::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with(self::VERSION)
            ->willReturn($this->version);

        $this->configureDialogs($this->app);
    }

    protected function createCommand() : AbstractCommand
    {
        return new ExecuteCommand();
    }

    /**
     * @param mixed[] $args
     * @param mixed[] $options
     *
     * @return CommandTester|int[]
     */
    protected function executeCommand(array $args, array $options = []) : array
    {
        $args['version'] = self::VERSION;

        return parent::executeCommand($args, $options);
    }
}
