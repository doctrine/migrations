<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\Version;
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

    /** @var AliasResolver */
    private $aliasResolver;

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var MigrationPlanCalculator */
    private $migrationPlanCalculator;

    /** @var MigrationStatusCalculator */
    private $statusCalculator;

    public function __construct(
        Configuration $configuration,
        Connection $connection,
        AliasResolver $aliasResolver,
        MigrationPlanCalculator $migrationPlanCalculator,
        MigrationStatusCalculator $statusCalculator,
        MetadataStorage $metadataStorage
    ) {
        $this->configuration           = $configuration;
        $this->connection              = $connection;
        $this->aliasResolver           = $aliasResolver;
        $this->migrationPlanCalculator = $migrationPlanCalculator;
        $this->metadataStorage         = $metadataStorage;
        $this->statusCalculator        = $statusCalculator;
    }

    /**
     * @param Version[] $versions
     */
    public function listVersions(array $versions, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders(
            [
                [new TableCell('Migration Versions', ['colspan' => 4])],
                ['Migration', 'Status', 'Migrated At', 'Execution Time', 'Description'],
            ]
        );
        $executedMigrations  = $this->metadataStorage->getExecutedMigrations();
        $availableMigrations = $this->migrationPlanCalculator->getMigrations();

        foreach ($versions as $version) {
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
                $executionTime !== null ? $executionTime . 's' : '',
                $description,
            ]);
        }

        $table->render();
    }

    public function showMigrationsInfo(OutputInterface $output): void
    {
        $executedMigrations  = $this->metadataStorage->getExecutedMigrations();
        $availableMigrations = $this->migrationPlanCalculator->getMigrations();

        $newMigrations                 = $this->statusCalculator->getNewMigrations();
        $executedUnavailableMigrations = $this->statusCalculator->getExecutedUnavailableMigrations();

        $storage = $this->configuration->getMetadataStorageConfiguration();

        $table = new Table($output);
        $table->setHeaders(
            [
                [new TableCell('Configuration', ['colspan' => 3])],
            ]
        );

        $dataGroup = [
            'Storage' => [
                'Type' => $storage !== null ? get_class($storage) : null,
            ],
            'Database' => [
                'Driver' => get_class($this->connection->getDriver()),
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
        }

        $first = true;
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

            if (! $first) {
                $table->addRow([new TableSeparator(['colspan' => 3])]);
            }

            $first = false;
            array_unshift(
                $nsRows[0],
                new TableCell('<info>' . $group . '</info>', ['rowspan' => count($dataValues)])
            );
            $table->addRows($nsRows);
        }

        $table->render();
    }

    private function getFormattedVersionAlias(string $alias, ExecutedMigrationsList $executedMigrations): string
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
