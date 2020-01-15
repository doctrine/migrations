<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The StatusCommand class is responsible for outputting what the current state is of all your migrations. It shows
 * what your current version is, how many new versions you have to execute, etc. and details about each of your migrations.
 */
class StatusCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:status';

    protected function configure() : void
    {
        $this
            ->setAliases(['status'])
            ->setDescription('View the status of a set of migrations.')
            ->addOption(
                'show-versions',
                null,
                InputOption::VALUE_NONE,
                'This will display a list of all available migrations and their status'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs the status of a set of migrations:

    <info>%command.full_name%</info>
EOT
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        $storage       = $this->getDependencyFactory()->getMetadataStorage();
        $migrationRepo = $this->getDependencyFactory()->getMigrationRepository();

        $availableMigrations = $migrationRepo->getMigrations();
        $executedMigrations  = $storage->getExecutedMigrations();

        $statusCalculator              = $this->getDependencyFactory()->getMigrationStatusCalculator();
        $newMigrations                 = $statusCalculator->getNewMigrations();
        $executedUnavailableMigrations = $statusCalculator->getExecutedUnavailableMigrations();

        $infosHelper = $this->getDependencyFactory()->getMigrationStatusInfosHelper();
        $infosHelper->showMigrationsInfo($output, $availableMigrations, $executedMigrations, $newMigrations, $executedUnavailableMigrations);

        return 0;
    }
}
