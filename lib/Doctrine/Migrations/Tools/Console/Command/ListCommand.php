<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use DateTimeInterface;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function array_merge;
use function array_unique;
use function uasort;

/**
 * The ListCommand class is responsible for outputting a list of all available migrations and their status.
 */
final class ListCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:list';

    protected function configure() : void
    {
        $this
            ->setAliases(['list-migrations'])
            ->setDescription('Display a list of all available migrations and their status.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs a list of all available migrations and their status:

    <info>%command.full_name%</info>
EOT
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->showVersions(
            $this->getDependencyFactory()->getMigrationRepository()->getMigrations(), // available migrations
            $this->getDependencyFactory()->getMetadataStorage()->getExecutedMigrations(), // executed migrations
            $output
        );

        return 0;
    }

    private function showVersions(
        AvailableMigrationsList $availableMigrations,
        ExecutedMigrationsSet $executedMigrations,
        OutputInterface $output
    ) : void {
        $table = new Table($output);
        $table->setHeaders(
            [
                [new TableCell('Migration Versions', ['colspan' => 4])],
                ['Migration', 'Status', 'Migrated At', 'Execution Time', 'Description'],
            ]
        );

        foreach ($this->getSortedVersions($availableMigrations, $executedMigrations) as $version) {
            $description   = null;
            $executedAt    = null;
            $executionTime = null;

            if ($executedMigrations->hasMigration($version)) {
                $executedMigration = $executedMigrations->getMigration($version);
                $executionTime     = $executedMigration->getExecutionTime();
                $executedAt        = $executedMigration->getExecutedAt() instanceof DateTimeInterface
                    ? $executedMigration->getExecutedAt()->format('Y-m-d H:i:s')
                    : null;
            }

            if ($availableMigrations->hasMigration($version)) {
                $description = $availableMigrations->getMigration($version)->getMigration()->getDescription();
            }

            if ($executedMigrations->hasMigration($version) && $availableMigrations->hasMigration($version)) {
                $status = '<info>migrated</info>';
            } elseif ($executedMigrations->hasMigration($version)) {
                $status = '<error>migrated, not available</error>';
            } else {
                $status = '<comment>not migrated</comment>';
            }

            $table->addRow([
                (string) $version,
                $status,
                (string) $executedAt,
                $executionTime !== null ? $executionTime . 's': '',
                $description,
            ]);
        }

        $table->render();
    }

    /**
     * @return Version[]
     */
    private function getSortedVersions(AvailableMigrationsList $availableMigrations, ExecutedMigrationsSet $executedMigrations) : array
    {
        $availableVersions = array_map(static function (AvailableMigration $availableMigration) : Version {
            return $availableMigration->getVersion();
        }, $availableMigrations->getItems());

        $executedVersions = array_map(static function (ExecutedMigration $executedMigration) : Version {
            return $executedMigration->getVersion();
        }, $executedMigrations->getItems());

        $versions = array_unique(array_merge($availableVersions, $executedVersions));

        $comparator = $this->getDependencyFactory()->getVersionComparator();
        uasort($versions, static function (Version $a, Version $b) use ($comparator) : int {
            return $comparator->compare($a, $b);
        });

        return $versions;
    }
}
