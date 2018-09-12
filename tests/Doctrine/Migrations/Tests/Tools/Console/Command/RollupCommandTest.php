<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Rollup;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RollupCommandTest extends TestCase
{
    /** @var DependencyFactory */
    private $dependencyFactory;

    /** @var Rollup */
    private $rollup;

    /** @var RollupCommand */
    private $rollupCommand;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $version = $this->createMock(Version::class);
        $version->expects(self::once())
            ->method('getVersion')
            ->willReturn('1234');

        $this->rollup->expects(self::once())
            ->method('rollup')
            ->willReturn($version);

        $output->expects(self::once())
            ->method('writeln')
            ->with('Rolled up migrations to version <info>1234</info>');

        $this->rollupCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->rollup            = $this->createMock(Rollup::class);
        $this->dependencyFactory = $this->createMock(DependencyFactory::class);

        $this->dependencyFactory->expects(self::any())
            ->method('getRollup')
            ->willReturn($this->rollup);

        $this->rollupCommand = $this->createPartialMock(RollupCommand::class, []);

        $this->rollupCommand->setDependencyFactory($this->dependencyFactory);
    }
}
