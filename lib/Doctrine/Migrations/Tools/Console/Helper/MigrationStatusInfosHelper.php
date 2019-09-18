<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
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
    public function getMigrationsInfos(ExecutedMigrationsSet $executedMigrations, AvailableMigrationsList $availableMigrations) : array
    {
        $newMigrations                 = $availableMigrations->getNewMigrations($executedMigrations);
        $executedUnavailableMigrations = $executedMigrations->getExecutedUnavailableMigrations($availableMigrations);


        $storage = $this->configuration->getMetadataStorageConfiguration();

        $data = [
            'Name'                              => $this->configuration->getName() ?? 'Doctrine Database Migrations',
            'Database Driver'                   => $this->connection->getDriver()->getName(),
            'Database Host'                     => $this->connection->getHost(),
            'Database Name'                     => $this->connection->getDatabase(),
            'Configuration Source'              => $this->configuration instanceof AbstractFileConfiguration ? $this->configuration->getFile() : 'manually configured',
            'Version storage'                   => get_class($storage),
            'Previous Version'                  => $this->getFormattedVersionAlias('prev'),
            'Current Version'                   => $this->getFormattedVersionAlias('current'),
            'Next Version'                      => $this->getFormattedVersionAlias('next'),
            'Latest Version'                    => $this->getFormattedVersionAlias('latest'),
            'Executed Migrations'               => count($executedMigrations),
            'Executed Unavailable Migrations'   => count($executedUnavailableMigrations),
            'Available Migrations'              => count($availableMigrations),
            'New Migrations'                    => count($newMigrations),
        ];

        foreach ($this->configuration->getMigrationDirectories() as $ns => $directory){
            //@todo
        }

        if ($storage instanceof TableMetadataStorageConfiguration){
            $data +=  [
                'Version Table Name'                => $storage->getTableName(),
                'Version Column Name'               => $storage->getVersionColumnName(),
            ];
        }
        return $data;
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
