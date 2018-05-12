<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;

class ExecuteCommandTest extends TestCase
{
    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var ExecuteCommand */
    private $executeCommand;

    public function testWriteSqlCustomPath() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects($this->once())
            ->method('getArgument')
            ->with('version')
            ->willReturn($versionName);

        $input->expects($this->at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn('/path');

        $input->expects($this->at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects($this->once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $version->expects($this->once())
            ->method('writeSqlFile')
            ->with('/path', 'down');

        self::assertEquals(0, $this->executeCommand->execute($input, $output));
    }

    public function testWriteSqlCurrentWorkingDirectory() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects($this->once())
            ->method('getArgument')
            ->with('version')
            ->willReturn($versionName);

        $input->expects($this->at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(null);

        $input->expects($this->at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects($this->once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $version->expects($this->once())
            ->method('writeSqlFile')
            ->with(getcwd(), 'down');

        self::assertEquals(0, $this->executeCommand->execute($input, $output));
    }

    public function testExecute() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects($this->once())
            ->method('getArgument')
            ->with('version')
            ->willReturn($versionName);

        $input->expects($this->at(1))
            ->method('getOption')
            ->with('query-time')
            ->willReturn(true);

        $input->expects($this->at(2))
            ->method('getOption')
            ->with('dry-run')
            ->willReturn(true);

        $input->expects($this->at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $input->expects($this->at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects($this->once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $this->executeCommand->expects($this->once())
            ->method('canExecute')
            ->willReturn(true);

        $version->expects($this->once())
            ->method('execute')
            ->with('down', true, true);

        self::assertEquals(0, $this->executeCommand->execute($input, $output));
    }

    public function testExecuteCanExecuteFalse() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects($this->once())
            ->method('getArgument')
            ->with('version')
            ->willReturn($versionName);

        $input->expects($this->at(1))
            ->method('getOption')
            ->with('query-time')
            ->willReturn(true);

        $input->expects($this->at(2))
            ->method('getOption')
            ->with('dry-run')
            ->willReturn(true);

        $input->expects($this->at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $input->expects($this->at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects($this->once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $this->executeCommand->expects($this->once())
            ->method('canExecute')
            ->willReturn(false);

        $version->expects($this->never())
            ->method('execute');

        self::assertEquals(1, $this->executeCommand->execute($input, $output));
    }

    protected function setUp() : void
    {
        $this->migrationRepository = $this->createMock(MigrationRepository::class);

        $this->executeCommand = $this->getMockBuilder(ExecuteCommand::class)
            ->setMethods(['canExecute'])
            ->getMock();

        $this->executeCommand->setMigrationRepository($this->migrationRepository);
    }
}
