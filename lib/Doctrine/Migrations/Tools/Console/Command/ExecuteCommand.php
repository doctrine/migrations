<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function getcwd;
use function is_string;
use function is_writable;

/**
 * The ExecutCommand class is responsible for executing migration versions up or down manually.
 */
class ExecuteCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:execute';

    protected function configure() : void
    {
        $this
            ->setAliases(['execute'])
            ->setDescription(
                'Execute one or more migration versions up or down manually.'
            )
            ->addArgument(
                'versions',
                InputArgument::REQUIRED|InputArgument::IS_ARRAY,
                'The versions to execute.',
                null
            )
            ->addOption(
                'write-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to output the migration SQL file instead of executing it. Defaults to current working directory.',
                false
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration as a dry run.'
            )
            ->addOption(
                'up',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration up.'
            )
            ->addOption(
                'down',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration down.'
            )
            ->addOption(
                'query-time',
                null,
                InputOption::VALUE_NONE,
                'Time all the queries individually.'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes migration versions up or down manually:

    <info>%command.full_name% FQCN</info>

If no <comment>--up</comment> or <comment>--down</comment> option is specified it defaults to up:

    <info>%command.full_name% FQCN --down</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% FQCN --dry-run</info>

You can output the would be executed SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% FQCN --write-sql</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% FQCN --no-interaction</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        $versions  = $input->getArgument('versions');
        $path      = $input->getOption('write-sql');
        $direction = $input->getOption('down') !== false
            ? Direction::DOWN
            : Direction::UP;

        $migrator       = $this->getDependencyFactory()->getMigrator();
        $planCalculator = $this->getDependencyFactory()->getMigrationPlanCalculator();
        $plan           = $planCalculator->getPlanForVersions(array_map(static function (string $version) : Version {
            return new Version($version);
        }, $versions), $direction);

        $migratorConfigurationFactory = $this->getDependencyFactory()->getConsoleInputMigratorConfigurationFactory();
        $migratorConfiguration        = $migratorConfigurationFactory->getMigratorConfiguration($input);

        if ($path !== false) {
            $migratorConfiguration->setDryRun(true);
            $sql = $migrator->migrate($plan, $migratorConfiguration);

            $path = is_string($path) ? $path : getcwd();

            if (! is_string($path) || ! is_writable($path)) {
                $output->writeln('<error>Path not writeable!</error>');

                return 1;
            }

            $writer = $this->getDependencyFactory()->getQueryWriter();
            $writer->write($path, $direction, $sql);

            return 0;
        }

        $question = 'WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)';

        if (! $migratorConfiguration->isDryRun() && ! $this->canExecute($question, $input, $output)) {
            $output->writeln('<error>Migration cancelled!</error>');

            return 1;
        }

        $this->getDependencyFactory()->getMetadataStorage()->ensureInitialized();
        $migrator->migrate($plan, $migratorConfiguration);

        return 0;
    }
}
