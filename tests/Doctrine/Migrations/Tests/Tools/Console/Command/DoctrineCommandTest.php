<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class DoctrineCommandTest extends MigrationTestCase
{
    public function testCommandFreezes() : void
    {
        $dependencyFactory = $this->getMockBuilder(DependencyFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['freeze'])
            ->getMock();

        $dependencyFactory
            ->expects(self::once())
            ->method('freeze');

        $command       = new class($dependencyFactory) extends DoctrineCommand
        {
            protected function execute(InputInterface $input, OutputInterface $output) : int
            {
                return 0;
            }
        };
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [],
            ['interactive' => false]
        );
    }
}
