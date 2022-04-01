<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Composer\InstalledVersions;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationFileWithFallback;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\CurrentCommand;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

use function assert;
use function file_exists;
use function getcwd;
use function is_readable;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * The ConsoleRunner class is used to create the Symfony Console application for the Doctrine Migrations console.
 *
 * @internal
 *
 * @see bin/doctrine-migrations.php
 */
class ConsoleRunner
{
    public static function findDependencyFactory(): ?DependencyFactory
    {
        // Support for using the Doctrine ORM convention of providing a `cli-config.php` file.
        $configurationDirectories = [
            getcwd(),
            getcwd() . DIRECTORY_SEPARATOR . 'config',
        ];

        $configurationFile = null;
        foreach ($configurationDirectories as $configurationDirectory) {
            $configurationFilePath = $configurationDirectory . DIRECTORY_SEPARATOR . 'cli-config.php';

            if (! file_exists($configurationFilePath)) {
                continue;
            }

            $configurationFile = $configurationFilePath;
            break;
        }

        $dependencyFactory = null;
        if ($configurationFile !== null) {
            if (! is_readable($configurationFile)) {
                throw new RuntimeException(sprintf(
                    'Configuration file "%s" cannot be read.',
                    $configurationFile
                ));
            }

            $dependencyFactory = require $configurationFile;
            $dependencyFactory = self::checkLegacyConfiguration($dependencyFactory, $configurationFile);
        }

        if ($dependencyFactory !== null && ! ($dependencyFactory instanceof DependencyFactory)) {
            throw new RuntimeException(sprintf(
                'Configuration file "%s" must return an instance of "%s"',
                $configurationFile,
                DependencyFactory::class
            ));
        }

        return $dependencyFactory;
    }

    /** @param DoctrineCommand[] $commands */
    public static function run(array $commands = [], ?DependencyFactory $dependencyFactory = null): void
    {
        $cli = static::createApplication($commands, $dependencyFactory);
        $cli->run();
    }

    /** @param DoctrineCommand[] $commands */
    public static function createApplication(array $commands = [], ?DependencyFactory $dependencyFactory = null): Application
    {
        $version = InstalledVersions::getVersion('doctrine/migrations');
        assert($version !== null);
        $cli = new Application('Doctrine Migrations', $version);
        $cli->setCatchExceptions(true);
        self::addCommands($cli, $dependencyFactory);
        $cli->addCommands($commands);

        return $cli;
    }

    public static function addCommands(Application $cli, ?DependencyFactory $dependencyFactory = null): void
    {
        $cli->addCommands([
            new CurrentCommand($dependencyFactory),
            new DumpSchemaCommand($dependencyFactory),
            new ExecuteCommand($dependencyFactory),
            new GenerateCommand($dependencyFactory),
            new LatestCommand($dependencyFactory),
            new MigrateCommand($dependencyFactory),
            new RollupCommand($dependencyFactory),
            new StatusCommand($dependencyFactory),
            new VersionCommand($dependencyFactory),
            new UpToDateCommand($dependencyFactory),
            new SyncMetadataCommand($dependencyFactory),
            new ListCommand($dependencyFactory),
        ]);

        if ($dependencyFactory === null || ! $dependencyFactory->hasSchemaProvider()) {
            return;
        }

        $cli->add(new DiffCommand($dependencyFactory));
    }

    /**
     * @param mixed|HelperSet $dependencyFactory
     *
     * @return mixed|DependencyFactory
     */
    private static function checkLegacyConfiguration($dependencyFactory, string $configurationFile)
    {
        if (! ($dependencyFactory instanceof HelperSet)) {
            return $dependencyFactory;
        }

        $configurations = new ConfigurationFileWithFallback();
        if ($dependencyFactory->has('em') && $dependencyFactory->get('em') instanceof EntityManagerHelper) {
            return DependencyFactory::fromEntityManager(
                $configurations,
                new ExistingEntityManager($dependencyFactory->get('em')->getEntityManager())
            );
        }

        throw new RuntimeException(sprintf(
            'Configuration HelperSet returned by "%s" does not have a valid "em" or the "db" helper.',
            $configurationFile
        ));
    }
}
