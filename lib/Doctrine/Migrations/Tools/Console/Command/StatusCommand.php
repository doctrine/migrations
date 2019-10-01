<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use DateTimeImmutable;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function count;

/**
 * The StatusCommand class is responsible for outputting what the current state is of all your migrations. It shows
 * what your current version is, how many new versions you have to execute, etc. and details about each of your migrations.
 */
class StatusCommand extends AbstractCommand
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

You can output a list of all available migrations and their status with <comment>--show-versions</comment>:

    <info>%command.full_name% --show-versions</info>
EOT
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        $storage        = $this->getDependencyFactory()->getMetadataStorage();
        $migrationRepo  = $this->getDependencyFactory()->getMigrationRepository();
        $planCalculator = $this->getDependencyFactory()->getMigrationPlanCalculator();

        $availableMigrations = $migrationRepo->getMigrations();
        $executedMigrations  = $storage->getExecutedMigrations();

        $newMigrations                 = $planCalculator->getNewMigrations();
        $executedUnavailableMigrations = $planCalculator->getExecutedUnavailableMigrations();

        $infosHelper = $this->getDependencyFactory()->getMigrationStatusInfosHelper();
        $infosHelper->showMigrationsInfo($output, $availableMigrations, $executedMigrations, $newMigrations, $executedUnavailableMigrations);

        if ($input->getOption('show-versions') === false) {
            return 0;
        }

        if (count($availableMigrations) !== 0) {
            $this->showVersions($availableMigrations, $executedMigrations, $output);
        }

        if (count($executedUnavailableMigrations) !== 0) {
            $this->showUnavailableVersions($output, $executedUnavailableMigrations);
        }

        return 0;
    }

    private function showUnavailableVersions(OutputInterface $output, ExecutedMigrationsSet $executedUnavailableMigrations) : void
    {
        $table = new Table($output);
        $table->setHeaders(
            [
                [new TableCell('<error>Previously Executed Unavailable Migration Versions</error>', ['colspan' => 2])],
                ['Migration', 'Migrated At'],
            ]
        );
        foreach ($executedUnavailableMigrations->getItems() as $executedUnavailableMigration) {
            $table->addRow([
                (string) $executedUnavailableMigration->getVersion(),
                $executedUnavailableMigration->getExecutedAt() !== null
                    ? $executedUnavailableMigration->getExecutedAt()->format('Y-m-d H:i:s')
                    : null,
            ]);
        }
        $table->render();
    }

    private function showVersions(
        AvailableMigrationsList $availableMigrationsSet,
        ExecutedMigrationsSet $executedMigrationsSet,
        OutputInterface $output
    ) : void {
        $table = new Table($output);
        $table->setHeaders(
            [
                [new TableCell('Available Migration Versions', ['colspan' => 4])],
                ['Migration', 'Migrated', 'Migrated At', 'Description'],
            ]
        );
        foreach ($availableMigrationsSet->getItems() as $availableMigration) {
            $executedMigration = $executedMigrationsSet->hasMigration($availableMigration->getVersion())
                ? $executedMigrationsSet->getMigration($availableMigration->getVersion())
                : null;

            $executedAt = $executedMigration!==null && $executedMigration->getExecutedAt() instanceof DateTimeImmutable
                ? $executedMigration->getExecutedAt()->format('Y-m-d H:i:s')
                : null;

            $description = $availableMigration->getMigration()->getDescription();

            $table->addRow([
                (string) $availableMigration->getVersion(),
                $executedMigration !== null ? '<comment>migrated</comment>' : '<error>not migrated</error>',
                (string) $executedAt,
                $description,
            ]);
        }
        $table->render();
    }
}
