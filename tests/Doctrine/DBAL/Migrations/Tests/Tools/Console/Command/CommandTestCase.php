<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

abstract class CommandTestCase extends MigrationTestCase
{
    protected $commmand, $app, $config, $connection;

    protected function setUp()
    {
        $this->connection = $this->createConnection();
        $this->config = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->command = $this->createCommand();
        $this->command->setMigrationConfiguration($this->config);
        $this->app = new Application();
        $this->app->add($this->command);
    }

    abstract protected function createCommand();

    protected function createConnection()
    {
        return $this->getSqliteConnection();
    }

    protected function createCommandTester()
    {
        return new CommandTester($this->app->find($this->command->getName()));
    }

    protected function executeCommand(array $args, array $options=[])
    {
        $tester = $this->createCommandTester();
        $statusCode = $tester->execute(array_replace([
            'command' => $this->command->getName(),
        ], $args), $options);

        return [$tester, $statusCode];
    }
}
