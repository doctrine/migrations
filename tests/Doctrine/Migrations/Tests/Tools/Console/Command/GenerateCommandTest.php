<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCommandTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var DependencyFactory */
    private $dependencyFactory;

    /** @var Generator */
    private $migrationGenerator;

    /** @var GenerateCommand */
    private $generateCommand;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects($this->once())
            ->method('getOption')
            ->with('editor-cmd')
            ->willReturn('mate');

        $this->configuration->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn('1234');

        $this->migrationGenerator->expects($this->once())
            ->method('generateMigration')
            ->with('1234')
            ->willReturn('/path/to/migration.php');

        $this->generateCommand->expects($this->once())
            ->method('procOpen')
            ->with('mate', '/path/to/migration.php');

        $output->expects($this->once())
            ->method('writeln')
            ->with([
                'Generated new migration class to "<info>/path/to/migration.php</info>"',
                '',
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up 1234</info>',
                '',
                'To revert the migration you can use <info>migrations:execute --down 1234</info>',
            ]);

        $this->generateCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->configuration      = $this->createMock(Configuration::class);
        $this->dependencyFactory  = $this->createMock(DependencyFactory::class);
        $this->migrationGenerator = $this->createMock(Generator::class);

        $this->dependencyFactory->expects($this->once())
            ->method('getMigrationGenerator')
            ->willReturn($this->migrationGenerator);

        $this->generateCommand = $this->getMockBuilder(GenerateCommand::class)
            ->setMethods(['procOpen'])
            ->getMock();

        $this->generateCommand->setMigrationConfiguration($this->configuration);
        $this->generateCommand->setDependencyFactory($this->dependencyFactory);
    }
}
