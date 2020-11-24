<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Doctrine\Migrations\Tools\Console\Exception\VersionAlreadyExists;
use Doctrine\Migrations\Tools\Console\Exception\VersionDoesNotExist;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * The VersionCommand class is responsible for manually adding and deleting migration versions from the tracking table.
 */
final class VersionCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:version';

    /** @var bool */
    private $markMigrated;

    protected function configure(): void
    {
        $this
            ->setAliases(['version'])
            ->setDescription('Manually add and delete migration versions from the version table.')
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'The version to add or delete.',
                null
            )
            ->addOption(
                'add',
                null,
                InputOption::VALUE_NONE,
                'Add the specified version.'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Delete the specified version.'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Apply to all the versions.'
            )
            ->addOption(
                'range-from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Apply from specified version.'
            )
            ->addOption(
                'range-to',
                null,
                InputOption::VALUE_OPTIONAL,
                'Apply to specified version.'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to manually add, delete or synchronize migration versions from the version table:

    <info>%command.full_name% MIGRATION-FQCN --add</info>

If you want to delete a version you can use the <comment>--delete</comment> option:

    <info>%command.full_name% MIGRATION-FQCN --delete</info>

If you want to synchronize by adding or deleting all migration versions available in the version table you can use the <comment>--all</comment> option:

    <info>%command.full_name% --add --all</info>
    <info>%command.full_name% --delete --all</info>

If you want to synchronize by adding or deleting some range of migration versions available in the version table you can use the <comment>--range-from/--range-to</comment> option:

    <info>%command.full_name% --add --range-from=MIGRATION-FQCN --range-to=MIGRATION-FQCN</info>
    <info>%command.full_name% --delete --range-from=MIGRATION-FQCN --range-to=MIGRATION-FQCN</info>

You can also execute this command without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
EOT
            );

        parent::configure();
    }

    /**
     * @throws InvalidOptionUsage
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('add') === false && $input->getOption('delete') === false) {
            throw InvalidOptionUsage::new('You must specify whether you want to --add or --delete the specified version.');
        }

        $this->markMigrated = $input->getOption('add');

        if ($input->isInteractive()) {
            $question = 'WARNING! You are about to add, delete or synchronize migration versions from the version table that could result in data lost. Are you sure you wish to continue?';

            $confirmation = $this->io->confirm($question);

            if ($confirmation) {
                $this->markVersions($input, $output);
            } else {
                $this->io->error('Migration cancelled!');
            }
        } else {
            $this->markVersions($input, $output);
        }

        return 0;
    }

    /**
     * @throws InvalidOptionUsage
     */
    private function markVersions(InputInterface $input, OutputInterface $output): void
    {
        $affectedVersion = $input->getArgument('version');
        $allOption       = $input->getOption('all');
        $rangeFromOption = $input->getOption('range-from');
        $rangeToOption   = $input->getOption('range-to');

        if ($allOption === true && ($rangeFromOption !== null || $rangeToOption !== null)) {
            throw InvalidOptionUsage::new(
                'Options --all and --range-to/--range-from both used. You should use only one of them.'
            );
        }

        if ($rangeFromOption !== null xor $rangeToOption !== null) {
            throw InvalidOptionUsage::new(
                'Options --range-to and --range-from should be used together.'
            );
        }

        $executedMigrations = $this->getDependencyFactory()->getMetadataStorage()->getExecutedMigrations();
        $availableVersions  = $this->getDependencyFactory()->getMigrationPlanCalculator()->getMigrations();
        if ($allOption === true) {
            if ($input->getOption('delete') === true) {
                foreach ($executedMigrations->getItems() as $availableMigration) {
                    $this->mark($input, $output, $availableMigration->getVersion(), false, $executedMigrations);
                }
            }

            foreach ($availableVersions->getItems() as $availableMigration) {
                $this->mark($input, $output, $availableMigration->getVersion(), true, $executedMigrations);
            }
        } elseif ($affectedVersion !== null) {
            $this->mark($input, $output, new Version($affectedVersion), false, $executedMigrations);
        } elseif ($rangeFromOption !== null && $rangeToOption !== null) {
            $migrate = false;
            foreach ($availableVersions->getItems() as $availableMigration) {
                if ((string) $availableMigration->getVersion() === $rangeFromOption) {
                    $migrate = true;
                }

                if ($migrate) {
                    $this->mark($input, $output, $availableMigration->getVersion(), true, $executedMigrations);
                }

                if ((string) $availableMigration->getVersion() === $rangeToOption) {
                    break;
                }
            }
        } else {
            throw InvalidOptionUsage::new('You must specify the version or use the --all argument.');
        }
    }

    /**
     * @throws VersionAlreadyExists
     * @throws VersionDoesNotExist
     * @throws UnknownMigrationVersion
     */
    private function mark(InputInterface $input, OutputInterface $output, Version $version, bool $all, ExecutedMigrationsList $executedMigrations): void
    {
        try {
            $availableMigration = $this->getDependencyFactory()->getMigrationRepository()->getMigration($version);
        } catch (MigrationClassNotFound $e) {
            $availableMigration = null;
        }

        $storage = $this->getDependencyFactory()->getMetadataStorage();
        if ($availableMigration === null) {
            if ($input->getOption('delete') === false) {
                throw UnknownMigrationVersion::new((string) $version);
            }

            $question =
                'WARNING! You are about to delete a migration version from the version table that has no corresponding migration file.' .
                'Do you want to delete this migration from the migrations table?';

            $confirmation = $this->io->confirm($question);

            if ($confirmation) {
                $migrationResult = new ExecutionResult($version, Direction::DOWN);
                $storage->complete($migrationResult);
                $this->io->text(sprintf(
                    "<info>%s</info> deleted from the version table.\n",
                    (string) $version
                ));

                return;
            }
        }

        $marked = false;

        if ($this->markMigrated && $executedMigrations->hasMigration($version)) {
            if (! $all) {
                throw VersionAlreadyExists::new($version);
            }

            $marked = true;
        }

        if (! $this->markMigrated && ! $executedMigrations->hasMigration($version)) {
            if (! $all) {
                throw VersionDoesNotExist::new($version);
            }

            $marked = true;
        }

        if ($marked === true) {
            return;
        }

        if ($this->markMigrated) {
            $migrationResult = new ExecutionResult($version, Direction::UP);
            $storage->complete($migrationResult);

            $this->io->text(sprintf(
                "<info>%s</info> added to the version table.\n",
                (string) $version
            ));
        } else {
            $migrationResult = new ExecutionResult($version, Direction::DOWN);
            $storage->complete($migrationResult);

            $this->io->text(sprintf(
                "<info>%s</info> deleted from the version table.\n",
                (string) $version
            ));
        }
    }
}
