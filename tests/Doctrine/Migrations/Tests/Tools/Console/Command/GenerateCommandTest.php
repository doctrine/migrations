<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCommandTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $configuration;

    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var Generator|MockObject */
    private $migrationGenerator;

    /** @var GenerateCommand|MockObject */
    private $generateCommand;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::at(0))
            ->method('getOption')
            ->with('namespace')
            ->willReturn(null);

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('editor-cmd')
            ->willReturn('mate');

        $this->configuration->expects(self::once())
            ->method('generateVersionNumber')
            ->willReturn('1234');

        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('1234')
            ->willReturn('/path/to/migration.php');

        $this->generateCommand->expects(self::once())
            ->method('procOpen')
            ->with('mate', '/path/to/migration.php');

        $output->expects(self::once())
            ->method('writeln')
            ->with([
                'Generated new migration class to "<info>/path/to/migration.php</info>"',
                '',
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up \'FooNs\Version1234\'</info>',
                '',
                'To revert the migration you can use <info>migrations:execute --down \'FooNs\Version1234\'</info>',
            ]);

        $this->generateCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->configuration      = $this->createMock(Configuration::class);
        $this->configuration->expects(self::any())
            ->method('getMigrationDirectories')
            ->willReturn([
                'FooNs' => sys_get_temp_dir()
            ]);

        $this->dependencyFactory  = $this->createMock(DependencyFactory::class);
        $this->migrationGenerator = $this->createMock(Generator::class);

        $this->dependencyFactory->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrationGenerator')
            ->willReturn($this->migrationGenerator);


        $this->generateCommand = $this->getMockBuilder(GenerateCommand::class)
            ->setConstructorArgs([null, $this->dependencyFactory])
            ->setMethods(['procOpen'])
            ->getMock();
    }
}
