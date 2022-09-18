<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Generator\ClassNameGenerator;
use Doctrine\Migrations\Generator\DiffGenerator;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
use function explode;
use function sys_get_temp_dir;
use function trim;

final class DiffCommandTest extends TestCase
{
    /** @var DiffGenerator&MockObject */
    private DiffGenerator $migrationDiffGenerator;

    /** @var MigrationStatusCalculator&MockObject */
    private MigrationStatusCalculator $migrationStatusCalculator;

    private Configuration $configuration;

    private DiffCommand $diffCommand;

    /** @var DependencyFactory&MockObject */
    private DependencyFactory $dependencyFactory;

    private CommandTester $diffCommandTester;

    /** @var ClassNameGenerator&MockObject */
    private ClassNameGenerator $classNameGenerator;

    public function testExecute(): void
    {
        $this->migrationStatusCalculator
            ->method('getNewMigrations')
            ->willReturn(new AvailableMigrationsList([]));

        $this->migrationStatusCalculator
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(new ExecutedMigrationsList([]));

        $this->classNameGenerator->expects(self::once())
            ->method('generateClassName')
            ->with('FooNs')
            ->willReturn('FooNs\\Version1234');

        $this->migrationDiffGenerator->expects(self::once())
            ->method('generate')
            ->with('FooNs\\Version1234', 'filter expression', true, 80)
            ->willReturn('/path/to/migration.php');

        $this->diffCommandTester->execute([
            '--filter-expression' => 'filter expression',
            '--formatted' => true,
            '--line-length' => 80,
            '--allow-empty-diff' => true,
            '--check-database-platform' => true,
            '--namespace' => 'FooNs',
        ]);

        $output = $this->diffCommandTester->getDisplay(true);

        self::assertSame([
            'Generated new migration class to "/path/to/migration.php"',
            '',
            'To run just this migration for testing purposes, you can use migrations:execute --up \'FooNs\\\\Version1234\'',
            '',
            'To revert the migration you can use migrations:execute --down \'FooNs\\\\Version1234\'',
        ], array_map('trim', explode("\n", trim($output))));
    }

    public function testAvailableMigrationsCancel(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->createStub(AbstractMigration::class));

        $this->migrationStatusCalculator
            ->method('getNewMigrations')
            ->willReturn(new AvailableMigrationsList([$m1]));

        $this->migrationStatusCalculator
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(new ExecutedMigrationsList([]));

        $this->diffCommandTester->setInputs(['no']);

        $this->migrationDiffGenerator->expects(self::never())->method('generate');

        $statusCode = $this->diffCommandTester->execute([]);

        $output = $this->diffCommandTester->getDisplay(true);

        self::assertStringContainsString('[WARNING] You have 1 available migrations to execute.', $output);
        self::assertStringContainsString('[ERROR] Migration cancelled!', $output);

        self::assertSame(3, $statusCode);
    }

    public function testExecutedUnavailableMigrationsCancel(): void
    {
        $e1 = new ExecutedMigration(new Version('B'));
        $m1 = new AvailableMigration(new Version('A'), $this->createStub(AbstractMigration::class));

        $this->migrationStatusCalculator
            ->method('getNewMigrations')
            ->willReturn(new AvailableMigrationsList([$m1]));

        $this->migrationStatusCalculator
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(new ExecutedMigrationsList([$e1]));

        $this->diffCommandTester->setInputs(['no']);

        $this->migrationDiffGenerator->expects(self::never())->method('generate');

        $statusCode = $this->diffCommandTester->execute([]);

        $output = $this->diffCommandTester->getDisplay(true);

        self::assertStringContainsString('[WARNING] You have 1 available migrations to execute.', $output);
        self::assertStringContainsString('[WARNING] You have 1 previously executed migrations in the database that are not registered migrations.', $output);
        self::assertStringContainsString('[ERROR] Migration cancelled!', $output);

        self::assertSame(3, $statusCode);
    }

    protected function setUp(): void
    {
        $this->migrationDiffGenerator    = $this->createMock(DiffGenerator::class);
        $this->migrationStatusCalculator = $this->createMock(MigrationStatusCalculator::class);
        $this->configuration             = new Configuration();
        $this->configuration->addMigrationsDirectory('FooNs', sys_get_temp_dir());

        $this->dependencyFactory = $this->createMock(DependencyFactory::class);

        $this->classNameGenerator = $this->createMock(ClassNameGenerator::class);

        $this->dependencyFactory
            ->method('getClassNameGenerator')
            ->willReturn($this->classNameGenerator);

        $this->dependencyFactory
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->dependencyFactory->expects(self::any())
            ->method('getDiffGenerator')
            ->willReturn($this->migrationDiffGenerator);

        $this->dependencyFactory->method('getMigrationStatusCalculator')
            ->willReturn($this->migrationStatusCalculator);

        $this->diffCommand       = new DiffCommand($this->dependencyFactory);
        $this->diffCommandTester = new CommandTester($this->diffCommand);
    }
}
