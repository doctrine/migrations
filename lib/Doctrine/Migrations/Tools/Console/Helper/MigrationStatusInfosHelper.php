<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\MetadataStorage;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\AliasResolver;
use function count;
use function sprintf;

/**
 * The MigrationStatusInfosHelper class is responsible for building the array of information used when displaying
 * the status of your migrations.
 *
 * @internal
 *
 * @see Doctrine\Migrations\Tools\Console\Command\StatusCommand
 */
class MigrationStatusInfosHelper
{
    /** @var Configuration  */
    private $configuration;

    /** @var MigrationRepository  */
    private $migrationRepository;

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var Connection */
    private $connection;

    /** @var AliasResolver */
    private $aliasResolver;

    public function __construct(
        Configuration $configuration,
        Connection $connection,
        AliasResolver $aliasResolver
    ) {
        $this->configuration = $configuration;
        $this->connection    = $connection;
        $this->aliasResolver = $aliasResolver;
    }

    /** @return string[]|int[]|null[] */
    public function getMigrationsInfos(ExecutedMigrationsSet $executedMigrations, AvailableMigrationsSet $availableMigrations) : array
    {
        $newMigrations                 = $availableMigrations->getNewMigrations($executedMigrations);
        $executedUnavailableMigrations = $executedMigrations->getExecutedUnavailableMigrations($availableMigrations);

        return [
            'Name'                              => $this->configuration->getName() ?? 'Doctrine Database Migrations',
            'Database Driver'                   => $this->connection->getDriver()->getName(),
            'Database Host'                     => $this->connection->getHost(),
            'Database Name'                     => $this->connection->getDatabase(),
            'Configuration Source'              => $this->configuration instanceof AbstractFileConfiguration ? $this->configuration->getFile() : 'manually configured',
            'Version Table Name'                => $this->configuration->getMigrationsTableName(),
            'Version Column Name'               => $this->configuration->getMigrationsColumnName(),
//            'Migrations Namespace'              => $this->configuration->getMigrationsNamespace(),
//            'Migrations Directory'              => $this->configuration->getMigrationsDirectory(),
            'Previous Version'                  => $this->getFormattedVersionAlias('prev'),
            'Current Version'                   => $this->getFormattedVersionAlias('current'),
            'Next Version'                      => $this->getFormattedVersionAlias('next'),
            'Latest Version'                    => $this->getFormattedVersionAlias('latest'),
            'Executed Migrations'               => count($executedMigrations->getItems()),
            'Executed Unavailable Migrations'   => count($executedUnavailableMigrations->getItems()),
            'Available Migrations'              => count($availableMigrations->getItems()),
            'New Migrations'                    => count($newMigrations->getItems()),
        ];
    }

    private function getFormattedVersionAlias(string $alias) : string
    {
        $version = $this->aliasResolver->resolveVersionAlias($alias);

        // No version found
        if ($version === null) {
            if ($alias === 'next') {
                return 'Already at latest version';
            }

            if ($alias === 'prev') {
                return 'Already at first version';
            }
        }

        // Before first version "virtual" version number
        if ($version === '0') {
            return '<comment>0</comment>';
        }

        // Show normal version number
        return sprintf(
            '<comment>%s</comment>',
            $version
        );
    }
}
