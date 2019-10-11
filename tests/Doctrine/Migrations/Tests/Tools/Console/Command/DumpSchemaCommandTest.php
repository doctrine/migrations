<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Generator\ClassNameGenerator;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\SchemaDumper;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function sys_get_temp_dir;

final class DumpSchemaCommandTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var MigrationRepository|MockObject */
    private $migrationRepository;

    /** @var SchemaDumper|MockObject */
    private $schemaDumper;

    /** @var DumpSchemaCommand */
    private $dumpSchemaCommand;

    public function testExecuteThrowsRuntimeException() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delete any previous migrations before dumping your schema.');

        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $migration = new AvailableMigration(new Version('1'), $this->createMock(AbstractMigration::class));

        $this->migrationRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$migration]));

        $this->dumpSchemaCommand->execute($input, $output);
    }

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::at(0))
            ->method('getOption')
            ->with('formatted')
            ->willReturn(true);

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('line-length')
            ->willReturn(80);

        $input->expects(self::at(2))
            ->method('getOption')
            ->with('namespace')
            ->willReturn(null);

        $input->expects(self::at(3))
            ->method('getOption')
            ->with('filter-tables')
            ->willReturn(['/foo/']);

        $input->expects(self::at(4))
            ->method('getOption')
            ->with('editor-cmd')
            ->willReturn('test');

        $this->migrationRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([]));

        $this->schemaDumper->expects(self::once())
            ->method('dump')
            ->with('FooNs\\Version1234', ['/foo/'], true, 80);

        $output->expects(self::once())
            ->method('writeln')
            ->with([
                'Dumped your schema to a new migration class at "<info></info>"',
                '',
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up \'FooNs\\\\Version1234\'</info>',
                '',
                'To revert the migration you can use <info>migrations:execute --down \'FooNs\\\\Version1234\'</info>',
                '',
                'To use this as a rollup migration you can use the <info>migrations:rollup</info> command.',
            ]);

        $this->dumpSchemaCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->configuration = new Configuration();
        $this->configuration->addMigrationsDirectory('FooNs', sys_get_temp_dir());

        $this->dependencyFactory   = $this->createMock(DependencyFactory::class);
        $this->migrationRepository = $this->createMock(MigrationRepository::class);
        $this->schemaDumper        = $this->createMock(SchemaDumper::class);

        $classNameGenerator = $this->createMock(ClassNameGenerator::class);
        $classNameGenerator->expects(self::any())
            ->method('generateClassName')
            ->with('FooNs')
            ->willReturn('FooNs\\Version1234');

        $this->dependencyFactory->expects(self::any())
            ->method('getClassNameGenerator')
            ->willReturn($classNameGenerator);

        $this->dependencyFactory->expects(self::any())
            ->method('getSchemaDumper')
            ->willReturn($this->schemaDumper);

        $this->dependencyFactory->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->dependencyFactory->expects(self::any())
            ->method('getMigrationRepository')
            ->willReturn($this->migrationRepository);

        $this->dumpSchemaCommand = new DumpSchemaCommand(null, $this->dependencyFactory);
    }
}
