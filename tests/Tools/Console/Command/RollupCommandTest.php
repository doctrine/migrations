<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Rollup;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function trim;

final class RollupCommandTest extends TestCase
{
    /** @var DependencyFactory&MockObject */
    private DependencyFactory $dependencyFactory;

    /** @var Rollup&MockObject */
    private Rollup $rollup;

    private RollupCommand $rollupCommand;

    private CommandTester $rollupCommandTest;

    public function testExecute(): void
    {
        $this->dependencyFactory
            ->expects(self::once())
            ->method('getRollup')
            ->willReturn($this->rollup);

        $this->rollup->expects(self::once())
            ->method('rollup')
            ->willReturn(new Version('1234'));

        $this->rollupCommandTest->execute([], ['interactive' => false]);

        $output = $this->rollupCommandTest->getDisplay(true);
        self::assertSame('[OK] Rolled up migrations to version 1234', trim($output));
    }

    public function testExecutionContinuesWhenAnsweringYes(): void
    {
        $this->rollupCommandTest->setInputs(['yes']);

        $this->dependencyFactory
            ->expects(self::once())
            ->method('getRollup')
            ->willReturn($this->rollup);

        $this->rollup->expects(self::once())
            ->method('rollup')
            ->willReturn(new Version('1234'));

        $this->rollupCommandTest->execute([]);

        $output = $this->rollupCommandTest->getDisplay(true);
        self::assertStringContainsString('[OK] Rolled up migrations to version 1234', trim($output));
    }

    public function testExecutionStoppedWhenAnsweringNo(): void
    {
        $this->rollupCommandTest->setInputs(['no']);

        $this->dependencyFactory
            ->expects(self::never())
            ->method('getRollup');

        $this->rollup->expects(self::never())
            ->method('rollup');

        $exitCode = $this->rollupCommandTest->execute([]);

        self::assertSame(3, $exitCode);

        $output = $this->rollupCommandTest->getDisplay(true);
        self::assertStringContainsString('[ERROR] Migration cancelled!', trim($output));
    }

    protected function setUp(): void
    {
        $this->rollup            = $this->createMock(Rollup::class);
        $this->dependencyFactory = $this->createMock(DependencyFactory::class);
        $this->rollupCommand     = new RollupCommand($this->dependencyFactory);
        $this->rollupCommandTest = new CommandTester($this->rollupCommand);
    }
}
