<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Generator\ClassNameGenerator;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
use function explode;
use function sys_get_temp_dir;
use function trim;

final class GenerateCommandTest extends TestCase
{
    private Configuration $configuration;

    /** @var DependencyFactory&MockObject */
    private DependencyFactory $dependencyFactory;

    /** @var Generator&MockObject */
    private Generator $migrationGenerator;

    private GenerateCommand $generateCommand;

    private CommandTester $generateCommandTest;

    public function testExecute(): void
    {
        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('FooNs\\Version1234')
            ->willReturn('/path/to/migration.php');

        $this->generateCommandTest->execute([]);
        $output = $this->generateCommandTest->getDisplay(true);

        self::assertSame([
            'Generated new migration class to "/path/to/migration.php"',
            '',
            'To run just this migration for testing purposes, you can use migrations:execute --up \'FooNs\Version1234\'',
            '',
            'To revert the migration you can use migrations:execute --down \'FooNs\Version1234\'',
        ], array_map('trim', explode("\n", trim($output))));
    }

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->configuration->addMigrationsDirectory('FooNs', sys_get_temp_dir());

        $this->dependencyFactory  = $this->createMock(DependencyFactory::class);
        $this->migrationGenerator = $this->createMock(Generator::class);

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

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrationGenerator')
            ->willReturn($this->migrationGenerator);

        $this->generateCommand = new GenerateCommand($this->dependencyFactory);

        $this->generateCommandTest = new CommandTester($this->generateCommand);
    }
}
