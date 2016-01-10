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
        $this->configuration = $configuration;
        $this->executedMigrations = $this->configuration->getMigratedVersions();
        $this->availableMigrations = $this->configuration->getAvailableVersions();
        $this->executedUnavailableMigrations = array_diff($this->executedMigrations, $this->availableMigrations);
    }

    public function getMigrationsInfos()
    {
        $formattedVersions = [];
        foreach (['prev', 'current', 'next', 'latest'] as $alias) {
            $version = $this->configuration->resolveVersionAlias($alias);
            if ($version === null) {
                if ($alias == 'next') {
                    $formattedVersions[$alias] = 'Already at latest version';
                } elseif ($alias == 'prev') {
                    $formattedVersions[$alias] = 'Already at first version';
                }
            } elseif ($version === '0') {
                $formattedVersions[$alias] = '<comment>0</comment>';
            } else {
                $formattedVersions[$alias] = $this->configuration->getDateTime($version) . ' (<comment>' . $version . '</comment>)';
            }
        }

        $numExecutedUnavailableMigrations = count($this->executedUnavailableMigrations);
        $numNewMigrations = count(array_diff($this->availableMigrations, $this->executedMigrations));

        $infos = [
            'Name'                              => $this->configuration->getName() ? $this->configuration->getName() : 'Doctrine Database Migrations',
            'Database Driver'                   => $this->configuration->getConnection()->getDriver()->getName(),
            'Database Name'                     => $this->configuration->getConnection()->getDatabase(),
            'Configuration Source'              => $this->configuration instanceof AbstractFileConfiguration ? $this->configuration->getFile() : 'manually configured',
            'Version Table Name'                => $this->configuration->getMigrationsTableName(),
            'Version Column Name'               => $this->configuration->getMigrationsColumnName(),
            'Migrations Namespace'              => $this->configuration->getMigrationsNamespace(),
            'Migrations Directory'              => $this->configuration->getMigrationsDirectory(),
            'Previous Version'                  => $formattedVersions['prev'],
            'Current Version'                   => $formattedVersions['current'],
            'Next Version'                      => $formattedVersions['next'],
            'Latest Version'                    => $formattedVersions['latest'],
            'Executed Migrations'               => count($this->executedMigrations),
            'Executed Unavailable Migrations'   => $numExecutedUnavailableMigrations,
            'Available Migrations'              => count($this->availableMigrations),
            'New Migrations'                    => $numNewMigrations,
        ];

        return $infos;
    }

    /** @var Version[] */
    public function getExecutedUnavailableMigrations()
    {
        return $this->executedUnavailableMigrations;
    }
}
