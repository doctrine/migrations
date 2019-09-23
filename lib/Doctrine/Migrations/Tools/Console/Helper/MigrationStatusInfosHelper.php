<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\AliasResolverInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_unshift;
use function count;
use function get_class;
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
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    /** @var AliasResolverInterface */
    private $aliasResolver;

    public function __construct(
        Configuration $configuration,
        Connection $connection,
        AliasResolverInterface $aliasResolver
    ) {
        $this->configuration = $configuration;
        $this->connection    = $connection;
        $this->aliasResolver = $aliasResolver;
    }

    public function showMigrationsInfo(
        OutputInterface $output,
        AvailableMigrationsList $availableMigrations,
        ExecutedMigrationsSet $executedMigrations,
        AvailableMigrationsList $newMigrations,
        ExecutedMigrationsSet $executedUnavailableMigrations
    ) : void {
        $storage = $this->configuration->getMetadataStorageConfiguration();

        $table = new Table($output);
        $table->setHeaders(
            [
                [new TableCell('Configuration', ['colspan' => 3])],
            ]
        );
        $data = [
            'Project' => $this->configuration->getName() ?? 'Doctrine Database Migrations',
        ];
        foreach ($data as $k => $v) {
            $table->addRow([
                '<info>' . $k . '</info>',
                new TableCell($v, ['colspan' => 2]),
            ]);
        }
        $dataGroup = [
            'Storage' => [
                'Type' => $storage!== null ? get_class($storage) : null,
            ],
            'Database' => [
                'Driver' => $this->connection->getDriver()->getName(),
                'Host' => $this->connection->getHost(),
                'Name' => $this->connection->getDatabase(),
            ],
            'Versions' => [
                'Previous' => $this->getFormattedVersionAlias('prev', $executedMigrations),
                'Current' => $this->getFormattedVersionAlias('current', $executedMigrations),
                'Next' => $this->getFormattedVersionAlias('next', $executedMigrations),
                'Latest' => $this->getFormattedVersionAlias('latest', $executedMigrations),
            ],

            'Migrations' => [
                'Executed' => count($executedMigrations),
                'Executed Unavailable' => count($executedUnavailableMigrations) > 0 ? ('<error>' . count($executedUnavailableMigrations) . '</error>') : '0',
                'Available' => count($availableMigrations),
                'New' => count($newMigrations) > 0 ? ('<question>' . count($newMigrations) . '</question>') : '0',
            ],
            'Migration Namespaces' => $this->configuration->getMigrationDirectories(),

        ];
        if ($storage instanceof TableMetadataStorageConfiguration) {
            $dataGroup['Storage'] += [
                'Table Name' => $storage->getTableName(),
                'Column Name' => $storage->getVersionColumnName(),
            ];
            $table->addRow([new TableSeparator(['colspan' => 3])]);
            foreach ($data as $k => $v) {
                $table->addRow([
                    '<info>' . $k . '</info>',
                    new TableCell($v, ['colspan' => 2]),
                ]);
            }
        }

        foreach ($dataGroup as $group => $dataValues) {
            $nsRows = [];
            foreach ($dataValues as $k => $v) {
                $nsRows[] = [
                    $k,
                    $v,
                ];
            }
            if (count($nsRows) <= 0) {
                continue;
            }

            $table->addRow([new TableSeparator(['colspan' => 3])]);
            array_unshift(
                $nsRows[0],
                new TableCell('<info>' . $group . '</info>', ['rowspan' => count($dataValues)])
            );
            $table->addRows($nsRows);
        }

        $table->render();
    }

    private function getFormattedVersionAlias(string $alias, ExecutedMigrationsSet $executedMigrationsSet) : string
    {
        try {
            $version = $this->aliasResolver->resolveVersionAlias($alias);
        } catch (Throwable $e) {
            $version = null;
        }

        // No version found
        if ($version === null) {
            if ($alias === 'next') {
                return 'Already at latest version';
            }

            if ($alias === 'prev') {
                return 'Already at first version';
            }
        }
        if ($alias === 'latest' && $version!== null && $executedMigrationsSet->hasMigration($version)) {
            return 'Already at latest version';
        }
        // Before first version "virtual" version number
        if ((string) $version === '0') {
            return '<comment>0</comment>';
        }

        // Show normal version number
        return sprintf(
            '<comment>%s </comment>',
            (string) $version
        );
    }
}
