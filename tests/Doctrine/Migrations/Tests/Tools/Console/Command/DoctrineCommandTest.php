<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
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

        $command = $this->getMockBuilder(DoctrineCommand::class)
            ->setConstructorArgs([$dependencyFactory])
            ->onlyMethods(['execute'])
            ->getMockForAbstractClass();

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [],
            ['interactive' => false]
        );
    }
}
