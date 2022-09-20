<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Generator\ClassNameGenerator;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\SchemaDumper;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
use function explode;
use function sys_get_temp_dir;
use function trim;

final class DumpSchemaCommandTest extends TestCase
{
    private Configuration $configuration;

    /** @var DependencyFactory&MockObject */
    private DependencyFactory $dependencyFactory;

    /** @var MigrationsRepository&MockObject */
    private MigrationsRepository $migrationRepository;

    /** @var SchemaDumper&MockObject */
    private SchemaDumper $schemaDumper;

    private DumpSchemaCommand $dumpSchemaCommand;

    private CommandTester $dumpSchemaCommandTester;

    public function testExecuteThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delete any previous migrations in the namespace "FooNs" before dumping your schema.');

        $migration = new AvailableMigration(new Version('FooNs\Abc'), $this->createMock(AbstractMigration::class));

        $this->migrationRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet([$migration]));

        $this->dumpSchemaCommandTester->execute([]);
    }

    public function testExecute(): void
    {
        $this->migrationRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet([]));

        $this->schemaDumper->expects(self::once())
            ->method('dump')
            ->with('FooNs\\Version1234', ['/foo/'], true, 80);

        $this->dumpSchemaCommandTester->execute([
            '--filter-tables' => ['/foo/'],
            '--line-length' => 80,
            '--formatted' => true,
        ]);

        $output = $this->dumpSchemaCommandTester->getDisplay(true);

        self::assertSame(
            [
                'Dumped your schema to a new migration class at ""',
                '',
                'To run just this migration for testing purposes, you can use migrations:execute --up \'FooNs\\\\Version1234\'',
                '',
                'To revert the migration you can use migrations:execute --down \'FooNs\\\\Version1234\'',
                '',
                'To use this as a rollup migration you can use the migrations:rollup command.',
            ],
            array_map('trim', explode("\n", trim($output)))
        );
    }

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->configuration->addMigrationsDirectory('FooNs', sys_get_temp_dir());

        $this->dependencyFactory   = $this->createMock(DependencyFactory::class);
        $this->migrationRepository = $this->createMock(FilesystemMigrationsRepository::class);
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

        $this->dumpSchemaCommand = new DumpSchemaCommand($this->dependencyFactory);

        $this->dumpSchemaCommandTester = new CommandTester($this->dumpSchemaCommand);
    }
}
