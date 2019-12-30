<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Rollup;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RollupCommandTest extends TestCase
{
    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var Rollup|MockObject */
    private $rollup;

    /** @var RollupCommand */
    private $rollupCommand;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->rollup->expects(self::once())
            ->method('rollup')
            ->willReturn(new Version('1234'));

        $output->expects(self::once())
            ->method('writeln')
            ->with('Rolled up migrations to version <info>1234</info>');

        $this->rollupCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->rollup            = $this->createMock(Rollup::class);
        $this->dependencyFactory = $this->createMock(DependencyFactory::class);

        $this->dependencyFactory
            ->expects(self::once())
            ->method('getRollup')
            ->willReturn($this->rollup);

        $this->rollupCommand = new RollupCommand(null, $this->dependencyFactory);
    }
}
