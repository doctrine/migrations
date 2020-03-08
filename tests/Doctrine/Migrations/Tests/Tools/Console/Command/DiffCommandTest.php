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
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function sys_get_temp_dir;

final class DiffCommandTest extends TestCase
{
    /** @var DiffGenerator|MockObject */
    private $migrationDiffGenerator;

    /** @var MigrationStatusCalculator|MockObject */
    private $migrationStatusCalculator;

    /** @var Configuration */
    private $configuration;

    /** @var DiffCommand */
    private $diffCommand;

    /** @var MockObject|DependencyFactory */
    private $dependencyFactory;

    /** @var MockObject|QuestionHelper */
    private $questions;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::at(0))
            ->method('getOption')
            ->with('filter-expression')
            ->willReturn('filter expression');

        $input->expects(self::at(1))
            ->method('getOption')
            ->with('formatted')
            ->willReturn(true);

        $input->expects(self::at(2))
            ->method('getOption')
            ->with('line-length')
            ->willReturn(80);

        $input->expects(self::at(3))
            ->method('getOption')
            ->with('allow-empty-diff')
            ->willReturn(true);

        $input->expects(self::at(4))
            ->method('getOption')
            ->with('check-database-platform')
            ->willReturn(true);

        $input->expects(self::at(5))
            ->method('getOption')
            ->with('namespace')
            ->willReturn('FooNs');

        $this->migrationDiffGenerator->expects(self::once())
            ->method('generate')
            ->with('FooNs\\Version1234', 'filter expression', true, 80)
            ->willReturn('/path/to/migration.php');

        $output->expects(self::once())
            ->method('writeln')
            ->with([
                'Generated new migration class to "<info>/path/to/migration.php</info>"',
                '',
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up \'FooNs\\\\Version1234\'</info>',
                '',
                'To revert the migration you can use <info>migrations:execute --down \'FooNs\\\\Version1234\'</info>',
            ]);

        $this->diffCommand->execute($input, $output);
    }

    public function testAvailableMigrationsCancel() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $m1 = new AvailableMigration(new Version('A'), $this->createMock(AbstractMigration::class));

        $this->migrationStatusCalculator
            ->method('getNewMigrations')
            ->willReturn(new AvailableMigrationsList([$m1]));

        $this->diffCommand->expects(self::once())
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(false);

        $this->migrationDiffGenerator->expects(self::never())->method('generate');

        $statusCode = $this->diffCommand->execute($input, $output);

        self::assertSame(3, $statusCode);
    }

    public function testExecutedUnavailableMigrationsCancel() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $e1 = new ExecutedMigration(new Version('A'));

        $this->migrationStatusCalculator
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1]));

        $this->diffCommand->expects(self::once())
            ->method('canExecute')
            ->with('Are you sure you wish to continue? (y/n)')
            ->willReturn(false);

        $this->migrationDiffGenerator->expects(self::never())->method('generate');

        $statusCode = $this->diffCommand->execute($input, $output);

        self::assertSame(3, $statusCode);
    }

    protected function setUp() : void
    {
        $this->migrationDiffGenerator    = $this->createMock(DiffGenerator::class);
        $this->migrationStatusCalculator = $this->createMock(MigrationStatusCalculator::class);
        $this->configuration             = new Configuration();
        $this->configuration->addMigrationsDirectory('FooNs', sys_get_temp_dir());

        $this->dependencyFactory = $this->createMock(DependencyFactory::class);

        $classNameGenerator = $this->createMock(ClassNameGenerator::class);
        $classNameGenerator->expects(self::once())
            ->method('generateClassName')
            ->with('FooNs')
            ->willReturn('FooNs\\Version1234');

        $this->dependencyFactory->expects(self::once())
            ->method('getClassNameGenerator')
            ->willReturn($classNameGenerator);

        $this->dependencyFactory->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->dependencyFactory->expects(self::any())
            ->method('getDiffGenerator')
            ->willReturn($this->migrationDiffGenerator);

        $this->dependencyFactory->expects(self::any())
            ->method('getMigrationStatusCalculator')
            ->willReturn($this->migrationStatusCalculator);

        $this->diffCommand = new DiffCommand($this->dependencyFactory);

        $this->questions = $this->createMock(QuestionHelper::class);

        $this->diffCommand->setHelperSet(new HelperSet(['question' => $this->questions]));
    }
}
