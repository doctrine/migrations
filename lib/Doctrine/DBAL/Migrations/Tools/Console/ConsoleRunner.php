<?php
namespace Doctrine\DBAL\Migrations\Tools\Console;

use Doctrine\DBAL\Migrations\MigrationsVersion;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Handles running the Console Tools inside Symfony Console context.
 */
class ConsoleRunner
{
    /**
     * Runs console with the given helperset.
     *
     * @param \Symfony\Component\Console\Helper\HelperSet  $helperSet
     * @param \Symfony\Component\Console\Command\Command[] $commands
     *
     * @return void
     */
    public static function run(HelperSet $helperSet, $commands = [])
    {
        $cli = self::createApplication($helperSet, $commands);
        $cli->run();
    }

    /**
     * Creates a console application with the given helperset and
     * optional commands.
     *
     * @param \Symfony\Component\Console\Helper\HelperSet $helperSet
     * @param array $commands
     *
     * @return \Symfony\Component\Console\Application
     */
    public static function createApplication(HelperSet $helperSet, $commands = [])
    {
        $cli = new Application('Doctrine Migrations', MigrationsVersion::VERSION());
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        self::addCommands($cli);
        $cli->addCommands($commands);

        return $cli;
    }

    /**
     * @param Application $cli
     *
     * @return void
     */
    public static function addCommands(Application $cli)
    {
        $cli->addCommands([
            new Command\ExecuteCommand(),
            new Command\GenerateCommand(),
            new Command\LatestCommand(),
            new Command\MigrateCommand(),
            new Command\StatusCommand(),
            new Command\VersionCommand(),
            new Command\UpToDateCommand(),
        ]);

        if ($cli->getHelperSet()->has('em')) {
            $cli->add(new Command\DiffCommand());
        }
    }
}
