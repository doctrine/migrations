<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function sprintf;
use function uasort;

/**
 * The UpToDateCommand class outputs if your database is up to date or if there are new migrations
 * that need to be executed.
 */
final class UpToDateCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:up-to-date';

    protected function configure() : void
    {
        $this
            ->setAliases(['up-to-date'])
            ->setDescription('Tells you if your schema is up-to-date.')
            ->addOption('fail-on-unregistered', 'u', InputOption::VALUE_NONE, 'Whether to fail when there are unregistered extra migrations found')
            ->addOption('list-migrations', 'l', InputOption::VALUE_NONE, 'Show a list of missing or not migrated versions.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command tells you if your schema is up-to-date:

    <info>%command.full_name%</info>
EOT
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $statusCalculator = $this->getDependencyFactory()->getMigrationStatusCalculator();

        $executedUnavailableMigrations      = $statusCalculator->getExecutedUnavailableMigrations();
        $newMigrations                      = $statusCalculator->getNewMigrations();
        $newMigrationsCount                 = count($newMigrations);
        $executedUnavailableMigrationsCount = count($executedUnavailableMigrations);

        if ($newMigrationsCount === 0 && $executedUnavailableMigrationsCount === 0) {
            $this->io->success('Up-to-date! No migrations to execute.');

            return 0;
        }

        $exitCode = 0;
        if ($newMigrationsCount > 0) {
            $this->io->error(sprintf(
                'Out-of-date! %u migration%s available to execute.',
                $newMigrationsCount,
                $newMigrationsCount > 1 ? 's are' : ' is'
            ));
            $exitCode = 1;
        }

        if ($executedUnavailableMigrationsCount > 0) {
            $this->io->error(sprintf(
                'You have %1$u previously executed migration%3$s in the database that %2$s registered migration%3$s.',
                $executedUnavailableMigrationsCount,
                $executedUnavailableMigrationsCount > 1 ? 'are not' : 'is not a',
                $executedUnavailableMigrationsCount > 1 ? 's' : ''
            ));
            if ($input->getOption('fail-on-unregistered')) {
                $exitCode = 2;
            }
        }

        if ($input->getOption('list-migrations')) {
            $versions = $this->getSortedVersions($newMigrations, $executedUnavailableMigrations);
            $this->getDependencyFactory()->getMigrationStatusInfosHelper()->listVersions($versions, $output);

            $this->io->newLine();
        }

        return $exitCode;
    }

    /**
     * @return Version[]
     */
    private function getSortedVersions(AvailableMigrationsList $newMigrations, ExecutedMigrationsList $executedUnavailableMigrations) : array
    {
        $executedUnavailableVersion = array_map(static function (ExecutedMigration $executedMigration) : Version {
            return $executedMigration->getVersion();
        }, $executedUnavailableMigrations->getItems());

        $newVersions = array_map(static function (AvailableMigration $availableMigration) : Version {
            return $availableMigration->getVersion();
        }, $newMigrations->getItems());

        $versions = array_unique(array_merge($executedUnavailableVersion, $newVersions));

        $comparator = $this->getDependencyFactory()->getVersionComparator();
        uasort($versions, static function (Version $a, Version $b) use ($comparator) : int {
            return $comparator->compare($a, $b);
        });

        return $versions;
    }
}
