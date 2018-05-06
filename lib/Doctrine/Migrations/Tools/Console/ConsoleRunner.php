<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\Migrations\MigrationsVersion;
use Doctrine\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

class ConsoleRunner
{
    /** @param AbstractCommand[] $commands */
    public static function run(HelperSet $helperSet, array $commands = []) : void
    {
        $cli = self::createApplication($helperSet, $commands);
        $cli->run();
    }

    /** @param AbstractCommand[] $commands */
    public static function createApplication(HelperSet $helperSet, array $commands = []) : Application
    {
        $cli = new Application('Doctrine Migrations', MigrationsVersion::VERSION());
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        self::addCommands($cli);
        $cli->addCommands($commands);

        return $cli;
    }

    public static function addCommands(Application $cli) : void
    {
        $cli->addCommands([
            new ExecuteCommand(),
            new GenerateCommand(),
            new LatestCommand(),
            new MigrateCommand(),
            new StatusCommand(),
            new VersionCommand(),
            new UpToDateCommand(),
        ]);

        if (! $cli->getHelperSet()->has('em')) {
            return;
        }

        $cli->add(new DiffCommand());
    }
}
