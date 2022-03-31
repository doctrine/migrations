<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function dirname;
use function getcwd;
use function in_array;
use function is_dir;
use function is_string;
use function is_writable;
use function sprintf;
use function strpos;

/**
 * The MigrateCommand class is responsible for executing a migration from the current version to another
 * version up or down. It will calculate all the migration versions that need to be executed and execute them.
 */
final class MigrateCommand extends DoctrineCommand
{
    /** @var string|null */
    protected static $defaultName = 'migrations:migrate';

    protected function configure(): void
    {
        $this
            ->setAliases(['migrate'])
            ->setDescription(
                'Execute a migration to a specified version or the latest available version.'
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'The version FQCN or alias (first, prev, next, latest) to migrate to.',
                'latest'
            )
            ->addOption(
                'write-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to output the migration SQL file. Defaults to current working directory.',
                false
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration as a dry run.'
            )
            ->addOption(
                'query-time',
                null,
                InputOption::VALUE_NONE,
                'Time all the queries individually.'
            )
            ->addOption(
                'allow-no-migration',
                null,
                InputOption::VALUE_NONE,
                'Do not throw an exception if no migration is available.'
            )
            ->addOption(
                'all-or-nothing',
                null,
                InputOption::VALUE_OPTIONAL,
                'Wrap the entire migration in a transaction.'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes a migration to a specified version or the latest available version:

    <info>%command.full_name%</info>

You can show more information about the process by increasing the verbosity level. To see the
executed queries, set the level to debug with <comment>-vv</comment>:

    <info>%command.full_name% -vv</info>

You can optionally manually specify the version you wish to migrate to:

    <info>%command.full_name% FQCN</info>

You can specify the version you wish to migrate to using an alias:

    <info>%command.full_name% prev</info>
    <info>These alias are defined : first, latest, prev, current and next</info>

You can specify the version you wish to migrate to using an number against the current version:

    <info>%command.full_name% current+3</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% FQCN --dry-run</info>

You can output the prepared SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% FQCN --write-sql</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

You can also time all the different queries if you wanna know which one is taking so long:

    <info>%command.full_name% --query-time</info>

Use the --all-or-nothing option to wrap the entire migration in a transaction.

EOT
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migratorConfigurationFactory = $this->getDependencyFactory()->getConsoleInputMigratorConfigurationFactory();
        $migratorConfiguration        = $migratorConfigurationFactory->getMigratorConfiguration($input);

        $databaseName = (string) $this->getDependencyFactory()->getConnection()->getDatabase();
        $question     = sprintf(
            'WARNING! You are about to execute a migration in database "%s" that could result in schema changes and data loss. Are you sure you wish to continue?',
            $databaseName === '' ? '<unnamed>' : $databaseName
        );
        if (! $migratorConfiguration->isDryRun() && ! $this->canExecute($question, $input)) {
            $this->io->error('Migration cancelled!');

            return 3;
        }

        $this->getDependencyFactory()->getMetadataStorage()->ensureInitialized();

        $allowNoMigration = $input->getOption('allow-no-migration');
        $versionAlias     = $input->getArgument('version');

        $path = $input->getOption('write-sql') ?? getcwd();

        if (is_string($path) && ! $this->isPathWritable($path)) {
            $this->io->error(sprintf('The path "%s" not writeable!', $path));

            return 1;
        }

        $migrationRepository = $this->getDependencyFactory()->getMigrationRepository();
        if (count($migrationRepository->getMigrations()) === 0) {
            $message = sprintf(
                'The version "%s" couldn\'t be reached, there are no registered migrations.',
                $versionAlias
            );

            if ($allowNoMigration) {
                $this->io->warning($message);

                return 0;
            }

            $this->io->error($message);

            return 1;
        }

        try {
            $version = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias($versionAlias);
        } catch (UnknownMigrationVersion $e) {
            $this->io->error(sprintf(
                'Unknown version: %s',
                OutputFormatter::escape($versionAlias)
            ));

            return 1;
        } catch (NoMigrationsToExecute | NoMigrationsFoundWithCriteria $e) {
            return $this->exitForAlias($versionAlias);
        }

        $planCalculator                = $this->getDependencyFactory()->getMigrationPlanCalculator();
        $statusCalculator              = $this->getDependencyFactory()->getMigrationStatusCalculator();
        $executedUnavailableMigrations = $statusCalculator->getExecutedUnavailableMigrations();

        if ($this->checkExecutedUnavailableMigrations($executedUnavailableMigrations, $input) === false) {
            return 3;
        }

        $plan = $planCalculator->getPlanUntilVersion($version);

        if (count($plan) === 0) {
            return $this->exitForAlias($versionAlias);
        }

        $this->getDependencyFactory()->getLogger()->notice(
            'Migrating' . ($migratorConfiguration->isDryRun() ? ' (dry-run)' : '') . ' {direction} to {to}',
            [
                'direction' => $plan->getDirection(),
                'to' => (string) $version,
            ]
        );

        $migrator = $this->getDependencyFactory()->getMigrator();
        $sql      = $migrator->migrate($plan, $migratorConfiguration);

        if (is_string($path)) {
            $writer = $this->getDependencyFactory()->getQueryWriter();
            $writer->write($path, $plan->getDirection(), $sql);
        }

        $this->io->newLine();

        return 0;
    }

    private function checkExecutedUnavailableMigrations(
        ExecutedMigrationsList $executedUnavailableMigrations,
        InputInterface $input
    ): bool {
        if (count($executedUnavailableMigrations) !== 0) {
            $this->io->warning(sprintf(
                'You have %s previously executed migrations in the database that are not registered migrations.',
                count($executedUnavailableMigrations)
            ));

            foreach ($executedUnavailableMigrations->getItems() as $executedUnavailableMigration) {
                $this->io->text(sprintf(
                    '<comment>>></comment> %s (<comment>%s</comment>)',
                    $executedUnavailableMigration->getExecutedAt() !== null
                        ? $executedUnavailableMigration->getExecutedAt()->format('Y-m-d H:i:s')
                        : null,
                    $executedUnavailableMigration->getVersion()
                ));
            }

            $question = 'Are you sure you wish to continue?';

            if (! $this->canExecute($question, $input)) {
                $this->io->error('Migration cancelled!');

                return false;
            }
        }

        return true;
    }

    private function exitForAlias(string $versionAlias): int
    {
        $version = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current');

        // Allow meaningful message when latest version already reached.
        if (in_array($versionAlias, ['current', 'latest', 'first'], true)) {
            $message = sprintf(
                'Already at the %s version ("%s")',
                $versionAlias,
                (string) $version
            );

            $this->io->success($message);
        } elseif (in_array($versionAlias, ['next', 'prev'], true) || strpos($versionAlias, 'current') === 0) {
            $message = sprintf(
                'The version "%s" couldn\'t be reached, you are at version "%s"',
                $versionAlias,
                (string) $version
            );

            $this->io->error($message);
        } else {
            $message = sprintf(
                'You are already at version "%s"',
                (string) $version
            );

            $this->io->success($message);
        }

        return 0;
    }

    private function isPathWritable(string $path): bool
    {
        return is_writable($path) || is_dir($path) || is_writable(dirname($path));
    }
}
