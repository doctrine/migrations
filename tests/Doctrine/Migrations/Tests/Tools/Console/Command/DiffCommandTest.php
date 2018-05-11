<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\MigrationDiffGenerator;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DiffCommandTest extends TestCase
{
    /** @var MigrationDiffGenerator */
    private $migrationDiffGenerator;

    /** @var DiffCommand */
    private $diffCommand;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects($this->at(0))
            ->method('getOption')
            ->with('filter-expression')
            ->willReturn('filter expression');

        $input->expects($this->at(1))
            ->method('getOption')
            ->with('formatted')
            ->willReturn(true);

        $input->expects($this->at(2))
            ->method('getOption')
            ->with('line-length')
            ->willReturn(80);

        $input->expects($this->at(3))
            ->method('getOption')
            ->with('editor-cmd')
            ->willReturn('mate');

        $this->migrationDiffGenerator->expects($this->once())
            ->method('generate')
            ->with('filter expression', true, 80)
            ->willReturn('/path/to/migration.php');

        $this->diffCommand->expects($this->once())
            ->method('procOpen')
            ->with('mate', '/path/to/migration.php');

        $output->expects($this->once())
            ->method('writeln')
            ->with('Generated new migration class to "<info>/path/to/migration.php</info>"');

        $this->diffCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->migrationDiffGenerator = $this->createMock(MigrationDiffGenerator::class);

        $this->diffCommand = $this->getMockBuilder(DiffCommand::class)
            ->setMethods(['createMigrationDiffGenerator', 'procOpen'])
            ->getMock();

        $this->diffCommand->expects($this->once())
            ->method('createMigrationDiffGenerator')
            ->willReturn($this->migrationDiffGenerator);
    }
}
