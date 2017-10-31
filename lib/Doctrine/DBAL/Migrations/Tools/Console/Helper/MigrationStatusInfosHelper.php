<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Version;

class MigrationStatusInfosHelper
{
    /** @var Version[] */
    private $executedMigrations;

    /** @var Version[] */
    private $availableMigrations;

    /** @var Version[] */
    private $executedUnavailableMigrations;

    /** @var Configuration  */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration                 = $configuration;
        $this->executedMigrations            = $this->configuration->getMigratedVersions();
        $this->availableMigrations           = $this->configuration->getAvailableVersions();
        $this->executedUnavailableMigrations = array_diff($this->executedMigrations, $this->availableMigrations);
    }

    public function getMigrationsInfos()
    {
        $numExecutedUnavailableMigrations = count($this->executedUnavailableMigrations);
        $numNewMigrations                 = count(array_diff($this->availableMigrations, $this->executedMigrations));

        $infos = [
            'Name'                              => $this->configuration->getName() ? $this->configuration->getName() : 'Doctrine Database Migrations',
            'Database Driver'                   => $this->configuration->getConnection()->getDriver()->getName(),
            'Database Name'                     => $this->configuration->getConnection()->getDatabase(),
            'Configuration Source'              => $this->configuration instanceof AbstractFileConfiguration ? $this->configuration->getFile() : 'manually configured',
            'Version Table Name'                => $this->configuration->getMigrationsTableName(),
            'Version Column Name'               => $this->configuration->getMigrationsColumnName(),
            'Migrations Namespace'              => $this->configuration->getMigrationsNamespace(),
            'Migrations Directory'              => $this->configuration->getMigrationsDirectory(),
            'Previous Version'                  => $this->getFormattedVersionAlias('prev'),
            'Current Version'                   => $this->getFormattedVersionAlias('current'),
            'Next Version'                      => $this->getFormattedVersionAlias('next'),
            'Latest Version'                    => $this->getFormattedVersionAlias('latest'),
            'Executed Migrations'               => count($this->executedMigrations),
            'Executed Unavailable Migrations'   => $numExecutedUnavailableMigrations,
            'Available Migrations'              => count($this->availableMigrations),
            'New Migrations'                    => $numNewMigrations,
        ];

        return $infos;
    }

    private function getFormattedVersionAlias($alias)
    {
        $version = $this->configuration->resolveVersionAlias($alias);
        //No version found
        if ($version === null) {
            if ($alias === 'next') {
                return 'Already at latest version';
            }

            if ($alias === 'prev') {
                return 'Already at first version';
            }
        }
        //Before first version "virtual" version number
        if ($version === '0') {
            return '<comment>0</comment>';
        }

        //Show normal version number
        return $this->configuration->getDateTime($version) . ' (<comment>' . $version . '</comment>)';
    }

    /**
     * @return Version[]
     */
    public function getExecutedUnavailableMigrations()
    {
        return $this->executedUnavailableMigrations;
    }
}
