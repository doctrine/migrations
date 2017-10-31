<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command for executing a migration to a specified version or the latest available version.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class MigrateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrations:migrate')
            ->setDescription('Execute a migration to a specified version or the latest available version.')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.', 'latest')
            ->addOption('write-sql', null, InputOption::VALUE_OPTIONAL, 'The path to output the migration SQL file instead of executing it. Default to current working directory.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
            ->addOption('query-time', null, InputOption::VALUE_NONE, 'Time all the queries individually.')
            ->addOption('allow-no-migration', null, InputOption::VALUE_NONE, 'Don\'t throw an exception if no migration is available (CI).')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes a migration to a specified version or the latest available version:

    <info>%command.full_name%</info>

You can optionally manually specify the version you wish to migrate to:

    <info>%command.full_name% YYYYMMDDHHMMSS</info>

You can specify the version you wish to migrate to using an alias:

    <info>%command.full_name% prev</info>
    <info>These alias are defined : first, latest, prev, current and next</info>

You can specify the version you wish to migrate to using an number against the current version:

    <info>%command.full_name% current+3</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --dry-run</info>

You can output the would be executed SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --write-sql</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

You can also time all the different queries if you wanna know which one is taking so long:

    <info>%command.full_name% --query-time</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);
        $migration     = $this->createMigration($configuration);

        $this->outputHeader($configuration, $output);

        $timeAllqueries = $input->getOption('query-time');

        $executedMigrations  = $configuration->getMigratedVersions();
        $availableMigrations = $configuration->getAvailableVersions();

        $version = $this->getVersionNameFromAlias($input->getArgument('version'), $output, $configuration);
        if ($version === false) {
            return 1;
        }

        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);
        if ( ! empty($executedUnavailableMigrations)) {
            $output->writeln(sprintf(
                '<error>WARNING! You have %s previously executed migrations'
                . ' in the database that are not registered migrations.</error>',
                count($executedUnavailableMigrations)
            ));

            foreach ($executedUnavailableMigrations as $executedUnavailableMigration) {
                $output->writeln(sprintf(
                    '    <comment>>></comment> %s (<comment>%s</comment>)',
                    $configuration->getDateTime($executedUnavailableMigration),
                    $executedUnavailableMigration
                ));
            }

            $question = 'Are you sure you wish to continue? (y/n)';
            if ( ! $this->canExecute($question, $input, $output)) {
                $output->writeln('<error>Migration cancelled!</error>');

                return 1;
            }
        }

        if ($path = $input->getOption('write-sql')) {
            $path = is_bool($path) ? getcwd() : $path;
            $migration->writeSqlFile($path, $version);
            return 0;
        }

        $dryRun = (boolean) $input->getOption('dry-run');

        $cancelled = false;
        $migration->setNoMigrationException($input->getOption('allow-no-migration'));
        $result = $migration->migrate($version, $dryRun, $timeAllqueries, function () use ($input, $output, &$cancelled) {
            $question    = 'WARNING! You are about to execute a database migration'
                . ' that could result in schema changes and data lost.'
                . ' Are you sure you wish to continue? (y/n)';
            $canContinue = $this->canExecute($question, $input, $output);
            $cancelled   = ! $canContinue;

            return $canContinue;
        });

        if ($cancelled) {
            $output->writeln('<error>Migration cancelled!</error>');
            return 1;
        }
    }

    /**
     * Create a new migration instance to execute the migrations.
     *
     * @param Configuration $configuration The configuration with which the migrations will be executed
     * @return Migration a new migration instance
     */
    protected function createMigration(Configuration $configuration)
    {
        return new Migration($configuration);
    }

    /**
     * @param string $question
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function canExecute($question, InputInterface $input, OutputInterface $output)
    {
        if ($input->isInteractive() && ! $this->askConfirmation($question, $input, $output)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $versionAlias
     * @param OutputInterface $output
     * @param Configuration $configuration
     * @return bool|string
     */
    private function getVersionNameFromAlias($versionAlias, OutputInterface $output, Configuration $configuration)
    {
        $version = $configuration->resolveVersionAlias($versionAlias);
        if ($version === null) {
            if ($versionAlias == 'prev') {
                $output->writeln('<error>Already at first version.</error>');
                return false;
            }
            if ($versionAlias == 'next') {
                $output->writeln('<error>Already at latest version.</error>');
                return false;
            }
            if (substr($versionAlias, 0, 7) == 'current') {
                $output->writeln('<error>The delta couldn\'t be reached.</error>');
                return false;
            }

            $output->writeln(sprintf(
                '<error>Unknown version: %s</error>',
                OutputFormatter::escape($versionAlias)
            ));
            return false;
        }

        return $version;
    }
}
