<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\Version;
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

    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $versions = $this->getSortedVersions(
            $this->getDependencyFactory()->getMigrationPlanCalculator()->getMigrations(), // available migrations
            $this->getDependencyFactory()->getMetadataStorage()->getExecutedMigrations() // executed migrations
        );

        $this->getDependencyFactory()->getMigrationStatusInfosHelper()->listVersions($versions, $output);

        return 0;
    }

    /**
     * @return Version[]
     */
    private function getSortedVersions(AvailableMigrationsList $availableMigrations, ExecutedMigrationsList $executedMigrations): array
    {
        $availableVersions = array_map(static function (AvailableMigration $availableMigration): Version {
            return $availableMigration->getVersion();
        }, $availableMigrations->getItems());

        $executedVersions = array_map(static function (ExecutedMigration $executedMigration): Version {
            return $executedMigration->getVersion();
        }, $executedMigrations->getItems());

        $versions = array_unique(array_merge($availableVersions, $executedVersions));

        $comparator = $this->getDependencyFactory()->getVersionComparator();
        uasort($versions, static function (Version $a, Version $b) use ($comparator): int {
            return $comparator->compare($a, $b);
        });

        return $versions;
    }
}
