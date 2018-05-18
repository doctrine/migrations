<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\SchemaDumper;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Version;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DumpSchemaCommandTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var DependencyFactory */
    private $dependencyFactory;

    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var SchemaDumper */
    private $schemaDumper;

    /** @var GenerateCommand */
    private $dumpSchemaCommand;

    public function testExecuteThrowsRuntimeException() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delete your old historical migrations before dumping your schema.');

        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $version = $this->createMock(Version::class);

        $versions = [$version];

        $this->migrationRepository->expects($this->once())
            ->method('getVersions')
            ->willReturn($versions);

        $this->dumpSchemaCommand->execute($input, $output);
    }

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects($this->at(0))
            ->method('getOption')
            ->with('formatted')
            ->willReturn(true);

        $input->expects($this->at(1))
            ->method('getOption')
            ->with('line-length')
            ->willReturn(80);

        $input->expects($this->at(2))
            ->method('getOption')
            ->with('editor-cmd')
            ->willReturn('test');

        $versions = [];

        $this->migrationRepository->expects($this->once())
            ->method('getVersions')
            ->willReturn($versions);

        $this->configuration->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn('1234');

        $this->schemaDumper->expects($this->once())
            ->method('dump')
            ->with('1234', true, 80);

        $output->expects($this->once())
            ->method('writeln')
            ->with([
                'Dumped your schema to a new migration class at "<info></info>"',
                '',
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up 1234</info>',
                '',
                'To revert the migration you can use <info>migrations:execute --down 1234</info>',
                '',
                'To use this as a rollup migration you can use the <info>migrations:rollup</info> command.',
            ]);

        $this->dumpSchemaCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->configuration       = $this->createMock(Configuration::class);
        $this->dependencyFactory   = $this->createMock(DependencyFactory::class);
        $this->migrationRepository = $this->createMock(MigrationRepository::class);
        $this->schemaDumper        = $this->createMock(SchemaDumper::class);

        $this->dependencyFactory->expects($this->any())
            ->method('getSchemaDumper')
            ->willReturn($this->schemaDumper);

        $this->dumpSchemaCommand = $this->createPartialMock(DumpSchemaCommand::class, []);

        $this->dumpSchemaCommand->setMigrationConfiguration($this->configuration);
        $this->dumpSchemaCommand->setDependencyFactory($this->dependencyFactory);
        $this->dumpSchemaCommand->setMigrationRepository($this->migrationRepository);
    }
}
