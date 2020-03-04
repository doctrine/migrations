<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Rollup;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use function trim;

final class RollupCommandTest extends TestCase
{
    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var Rollup|MockObject */
    private $rollup;

    /** @var RollupCommand */
    private $rollupCommand;

    /** @var CommandTester */
    private $rollupCommandTest;

    public function testExecute() : void
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
        self::assertSame('Rolled up migrations to version 1234', trim($output));
    }

    public function testExecutionContinuesWhenAnsweringYes() : void
    {
        $questions = $this->createMock(QuestionHelper::class);
        $questions->expects(self::once())
            ->method('ask')
            ->willReturn(true);

        $this->rollupCommand->setHelperSet(new HelperSet(['question' => $questions]));

        $this->dependencyFactory
            ->expects(self::once())
            ->method('getRollup')
            ->willReturn($this->rollup);

        $this->rollup->expects(self::once())
            ->method('rollup')
            ->willReturn(new Version('1234'));

        $this->rollupCommandTest->execute([]);

        $output = $this->rollupCommandTest->getDisplay(true);
        self::assertSame('Rolled up migrations to version 1234', trim($output));
    }

    public function testExecutionStoppedWhenAnsweringNo() : void
    {
        $questions = $this->createMock(QuestionHelper::class);
        $questions->expects(self::once())
            ->method('ask')
            ->willReturn(false);

        $this->dependencyFactory
            ->expects(self::never())
            ->method('getRollup');

        $this->rollup->expects(self::never())
            ->method('rollup');

        $this->rollupCommand->setHelperSet(new HelperSet(['question' => $questions]));
        $exitCode = $this->rollupCommandTest->execute([]);

        self::assertSame(3, $exitCode);

        $output = $this->rollupCommandTest->getDisplay(true);
        self::assertSame('Migration cancelled!', trim($output));
    }

    protected function setUp() : void
    {
        $this->rollup            = $this->createMock(Rollup::class);
        $this->dependencyFactory = $this->createMock(DependencyFactory::class);
        $this->rollupCommand     = new RollupCommand($this->dependencyFactory);
        $this->rollupCommandTest = new CommandTester($this->rollupCommand);
    }
}
