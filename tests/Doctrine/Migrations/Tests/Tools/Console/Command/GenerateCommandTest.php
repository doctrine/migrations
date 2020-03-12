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
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use function explode;
use function sys_get_temp_dir;
use function trim;

final class GenerateCommandTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var Generator|MockObject */
    private $migrationGenerator;

    /** @var GenerateCommand */
    private $generateCommand;

    /** @var CommandTester */
    private $generateCommandTest;

    /** @var MockObject|ProcessHelper */
    private $process;

    public function testExecute() : void
    {
        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('FooNs\\Version1234')
            ->willReturn('/path/to/migration.php');

        $this->process->expects(self::once())
            ->method('mustRun')
            ->willReturnCallback(static function ($output, Process $process, $err, $callback) : void {
                self::assertSame("'mate' '/path/to/migration.php'", $process->getCommandLine());
                self::assertNotNull($callback);
            });

        $this->generateCommandTest->execute(['--editor-cmd' => 'mate']);
        $output = $this->generateCommandTest->getDisplay(true);

        self::assertSame([
            'Generated new migration class to "/path/to/migration.php"',
            '',
            'To run just this migration for testing purposes, you can use migrations:execute --up \'FooNs\Version1234\'',
            '',
            'To revert the migration you can use migrations:execute --down \'FooNs\Version1234\'',
        ], explode("\n", trim($output)));
    }

    protected function setUp() : void
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

        $this->process = $this->createMock(ProcessHelper::class);
        $this->generateCommand->setHelperSet(new HelperSet(['process' => $this->process]));

        $this->generateCommandTest = new CommandTester($this->generateCommand);
    }
}
