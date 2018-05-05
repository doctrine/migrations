<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function array_replace;

abstract class CommandTestCase extends MigrationTestCase
{
    /** @var AbstractCommand */
    protected $command;

    /** @var Application */
    protected $app;

    /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var Connection */
    protected $connection;

    protected function setUp() : void
    {
        $this->connection = $this->createConnection();
        $this->config     = $this->createMock(Configuration::class);
        $this->config->method('getConnection')->willReturn($this->connection);
        $this->command = $this->createCommand();
        $this->command->setMigrationConfiguration($this->config);
        $this->app = new Application();
        $this->app->add($this->command);
    }

    abstract protected function createCommand() : AbstractCommand;

    protected function createConnection() : Connection
    {
        return $this->getSqliteConnection();
    }

    protected function createCommandTester() : CommandTester
    {
        return new CommandTester($this->app->find($this->command->getName()));
    }

    /**
     * @param mixed[] $args
     * @param mixed[] $options
     *
     * @return CommandTester|int[]
     */
    protected function executeCommand(array $args, array $options = []) : array
    {
        $tester = $this->createCommandTester();

        $statusCode = $tester->execute(array_replace([
            'command' => $this->command->getName(),
        ], $args), $options);

        return [$tester, $statusCode];
    }
}
