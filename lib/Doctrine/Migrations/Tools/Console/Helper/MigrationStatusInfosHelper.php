<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use function array_diff;
use function count;
use function sprintf;

class MigrationStatusInfosHelper
{
    /** @var string[] */
    private $executedMigrations;

    /** @var string[] */
    private $availableMigrations;

    /** @var string[] */
    private $executedUnavailableMigrations;

    /** @var Configuration  */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration                 = $configuration;
        $this->executedMigrations            = $this->configuration->getMigratedVersions();
        $this->availableMigrations           = $this->configuration->getAvailableVersions();
        $this->executedUnavailableMigrations = array_diff(
            $this->executedMigrations,
            $this->availableMigrations
        );
    }

    /** @return string[]|int[]|null[] */
    public function getMigrationsInfos() : array
    {
        $numExecutedUnavailableMigrations = count($this->executedUnavailableMigrations);

        $numNewMigrations = count(array_diff(
            $this->availableMigrations,
            $this->executedMigrations
        ));

        $infos = [
            'Name'                              => $this->configuration->getName() ? $this->configuration->getName() : 'Doctrine Database Migrations',
            'Database Driver'                   => $this->configuration->getConnection()->getDriver()->getName(),
            'Database Host'                     => $this->configuration->getConnection()->getHost(),
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

    private function getFormattedVersionAlias(string $alias) : string
    {
        $version = $this->configuration->resolveVersionAlias($alias);

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
            '%s (<comment>%s</comment>)',
            $this->configuration->getDateTime((string) $version),
            $version
        );
    }

    /** @return string[] */
    public function getExecutedUnavailableMigrations() : array
    {
        return $this->executedUnavailableMigrations;
    }
}
