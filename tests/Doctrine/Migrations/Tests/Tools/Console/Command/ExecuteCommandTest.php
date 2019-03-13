<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;

class ExecuteCommandTest extends TestCase
{
    /** @var MigrationRepository|MockObject */
    private $migrationRepository;

    /** @var ExecuteCommand|MockObject */
    private $executeCommand;

    public function testWriteSqlCustomPath() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('versions')
            ->willReturn($versionName);

        $input->expects(self::at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn('/path');

        $input->expects(self::at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects(self::once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $version->expects(self::once())
            ->method('writeSqlFile')
            ->with('/path', 'down');

        self::assertSame(0, $this->executeCommand->execute($input, $output));
    }

    public function testWriteSqlCurrentWorkingDirectory() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('versions')
            ->willReturn($versionName);

        $input->expects(self::at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(null);

        $input->expects(self::at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects(self::once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $version->expects(self::once())
            ->method('writeSqlFile')
            ->with(getcwd(), 'down');

        self::assertSame(0, $this->executeCommand->execute($input, $output));
    }

    public function testExecute() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('versions')
            ->willReturn($versionName);

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('query-time')
            ->willReturn(true);

        $input->expects(self::at(2))
            ->method('getOption')
            ->with('dry-run')
            ->willReturn(true);

        $input->expects(self::at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $input->expects(self::at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects(self::once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $version->expects(self::once())
            ->method('execute')
            ->with('down');

        self::assertSame(0, $this->executeCommand->execute($input, $output));
    }

    public function testExecuteCancel() : void
    {
        $versionName = '1';

        $input   = $this->createMock(InputInterface::class);
        $output  = $this->createMock(OutputInterface::class);
        $version = $this->createMock(Version::class);

        $input->expects(self::once())
            ->method('getArgument')
            ->with('versions')
            ->willReturn($versionName);

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('query-time')
            ->willReturn(true);

        $input->expects(self::at(2))
            ->method('getOption')
            ->with('dry-run')
            ->willReturn(false);

        $input->expects(self::at(3))
            ->method('getOption')
            ->with('write-sql')
            ->willReturn(false);

        $input->expects(self::at(4))
            ->method('getOption')
            ->with('down')
            ->willReturn(true);

        $this->migrationRepository->expects(self::once())
            ->method('getVersion')
            ->with($versionName)
            ->willReturn($version);

        $this->executeCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(false);

        $version->expects(self::never())
            ->method('execute');

        self::assertSame(1, $this->executeCommand->execute($input, $output));
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
