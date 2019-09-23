<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function count;
use function sprintf;

/**
 * The UpToDateCommand class outputs if your database is up to date or if there are new migrations
 * that need to be executed.
 */
class UpToDateCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:up-to-date';

    protected function configure() : void
    {
        $this
            ->setAliases(['up-to-date'])
            ->setDescription('Tells you if your schema is up-to-date.')
            ->addOption('fail-on-unregistered', 'u', InputOption::VALUE_NONE, 'Whether to fail when there are unregistered extra migrations found')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command tells you if your schema is up-to-date:

    <info>%command.full_name%</info>
EOT
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        $planCalculator                = $this->getDependencyFactory()->getMigrationPlanCalculator();
        $executedUnavailableMigrations = $planCalculator->getExecutedUnavailableMigrations();
        $newMigrations                 = $planCalculator->getNewMigrations();

        $newMigrationsCount                 = count($newMigrations);
        $executedUnavailableMigrationsCount =  count($executedUnavailableMigrations);

        if ($newMigrationsCount === 0 && $executedUnavailableMigrationsCount ===0) {
            $output->writeln('<comment>Up-to-date! No migrations to execute.</comment>');

            return 0;
        }

        if ($newMigrationsCount > 0) {
            $output->writeln(sprintf(
                '<error>Out-of-date! %u migration%s available to execute.</error>',
                $newMigrationsCount,
                $newMigrationsCount > 1 ? 's are' : ' is'
            ));

            return 1;
        }

        // negative number means that there are unregistered migrations in the database
        if ($executedUnavailableMigrationsCount > 0) {
            $output->writeln(sprintf(
                '<error>You have %1$u previously executed migration%3$s in the database that %2$s registered migration%3$s.</error>',
                $executedUnavailableMigrationsCount,
                $executedUnavailableMigrationsCount > 1 ? 'are not' : 'is not a',
                $executedUnavailableMigrationsCount > 1 ? 's' : ''
            ));
        }

        return $executedUnavailableMigrationsCount > 0 && $input->getOption('fail-on-unregistered') === true ? 2 : 0;
    }
}
