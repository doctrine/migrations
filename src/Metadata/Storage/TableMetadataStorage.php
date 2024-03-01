<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\MetadataStorageError;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Version\Comparator as MigrationsComparator;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use InvalidArgumentException;

use function array_change_key_case;
use function floatval;
use function round;
use function sprintf;
use function strlen;
use function strpos;
use function strtolower;
use function uasort;

use const CASE_LOWER;

final class TableMetadataStorage implements MetadataStorage
{
    private bool $isInitialized = false;

    private bool $schemaUpToDate = false;

    /** @var AbstractSchemaManager<AbstractPlatform> */
    private readonly AbstractSchemaManager $schemaManager;

    private readonly AbstractPlatform $platform;
    private readonly TableMetadataStorageConfiguration $configuration;

    public function __construct(
        private readonly Connection $connection,
        private readonly MigrationsComparator $comparator,
        MetadataStorageConfiguration|null $configuration = null,
        private readonly MigrationsRepository|null $migrationRepository = null,
    ) {
        $this->schemaManager = $connection->createSchemaManager();
        $this->platform      = $connection->getDatabasePlatform();

        if ($configuration !== null && ! ($configuration instanceof TableMetadataStorageConfiguration)) {
            throw new InvalidArgumentException(sprintf(
                '%s accepts only %s as configuration',
                self::class,
                TableMetadataStorageConfiguration::class,
            ));
        }

        $this->configuration = $configuration ?? new TableMetadataStorageConfiguration();
    }

    public function getExecutedMigrations(): ExecutedMigrationsList
    {
        if (! $this->isInitialized()) {
            return new ExecutedMigrationsList([]);
        }

        $this->checkInitialization();
        $rows = $this->connection->fetchAllAssociative(sprintf('SELECT * FROM %s', $this->configuration->getTableName()));

        $migrations = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);

            $version = new Version($row[strtolower($this->configuration->getVersionColumnName())]);

            $executedAt = $row[strtolower($this->configuration->getExecutedAtColumnName())] ?? '';
            $executedAt = $executedAt !== ''
                ? DateTimeImmutable::createFromFormat($this->platform->getDateTimeFormatString(), $executedAt)
                : null;

            $executionTime = isset($row[strtolower($this->configuration->getExecutionTimeColumnName())])
                ? floatval($row[strtolower($this->configuration->getExecutionTimeColumnName())] / 1000)
                : null;

            $migration = new ExecutedMigration(
                $version,
                $executedAt instanceof DateTimeImmutable ? $executedAt : null,
                $executionTime,
            );

            $migrations[(string) $version] = $migration;
        }

        uasort($migrations, fn (ExecutedMigration $a, ExecutedMigration $b): int => $this->comparator->compare($a->getVersion(), $b->getVersion()));

        return new ExecutedMigrationsList($migrations);
    }

    public function reset(): void
    {
        $this->checkInitialization();

        $this->connection->executeStatement(
            sprintf(
                'DELETE FROM %s WHERE 1 = 1',
                $this->platform->quoteIdentifier($this->configuration->getTableName()),
            ),
        );
    }

    public function complete(ExecutionResult $result): void
    {
        $this->checkInitialization();

        if ($result->getDirection() === Direction::DOWN) {
            $this->connection->delete($this->configuration->getTableName(), [
                $this->configuration->getVersionColumnName() => (string) $result->getVersion(),
            ]);
        } else {
            $this->connection->insert($this->configuration->getTableName(), [
                $this->configuration->getVersionColumnName() => (string) $result->getVersion(),
                $this->configuration->getExecutedAtColumnName() => $result->getExecutedAt(),
                $this->configuration->getExecutionTimeColumnName() => $result->getTime() === null ? null : (int) round($result->getTime() * 1000),
            ], [
                Types::STRING,
                Types::DATETIME_IMMUTABLE,
                Types::INTEGER,
            ]);
        }
    }

    /** @return iterable<Query> */
    public function getSql(ExecutionResult $result): iterable
    {
        yield new Query('-- Version ' . (string) $result->getVersion() . ' update table metadata');

        if ($result->getDirection() === Direction::DOWN) {
            yield new Query(sprintf(
                'DELETE FROM %s WHERE %s = %s',
                $this->configuration->getTableName(),
                $this->configuration->getVersionColumnName(),
                $this->connection->quote((string) $result->getVersion()),
            ));

            return;
        }

        yield new Query(sprintf(
            'INSERT INTO %s (%s, %s, %s) VALUES (%s, %s, 0)',
            $this->configuration->getTableName(),
            $this->configuration->getVersionColumnName(),
            $this->configuration->getExecutedAtColumnName(),
            $this->configuration->getExecutionTimeColumnName(),
            $this->connection->quote((string) $result->getVersion()),
            $this->connection->quote(($result->getExecutedAt() ?? new DateTimeImmutable())->format('Y-m-d H:i:s')),
        ));
    }

    public function ensureInitialized(): void
    {
        if (! $this->isInitialized()) {
            $expectedSchemaChangelog = $this->getExpectedTable();
            $this->schemaManager->createTable($expectedSchemaChangelog);
            $this->schemaUpToDate = true;
            $this->isInitialized  = true;

            return;
        }

        $this->isInitialized     = true;
        $expectedSchemaChangelog = $this->getExpectedTable();
        $diff                    = $this->needsUpdate($expectedSchemaChangelog);
        if ($diff === null) {
            $this->schemaUpToDate = true;

            return;
        }

        $this->schemaUpToDate = true;
        $this->schemaManager->alterTable($diff);
        $this->updateMigratedVersionsFromV1orV2toV3();
    }

    private function needsUpdate(Table $expectedTable): TableDiff|null
    {
        if ($this->schemaUpToDate) {
            return null;
        }

        $currentTable = $this->schemaManager->introspectTable($this->configuration->getTableName());
        $diff         = $this->schemaManager->createComparator()->compareTables($currentTable, $expectedTable);

        return $diff->isEmpty() ? null : $diff;
    }

    private function isInitialized(): bool
    {
        if ($this->isInitialized) {
            return $this->isInitialized;
        }

        if ($this->connection instanceof PrimaryReadReplicaConnection) {
            $this->connection->ensureConnectedToPrimary();
        }

        return $this->schemaManager->tablesExist([$this->configuration->getTableName()]);
    }

    private function checkInitialization(): void
    {
        if (! $this->isInitialized()) {
            throw MetadataStorageError::notInitialized();
        }

        $expectedTable = $this->getExpectedTable();

        if ($this->needsUpdate($expectedTable) !== null) {
            throw MetadataStorageError::notUpToDate();
        }
    }

    private function getExpectedTable(): Table
    {
        $schemaChangelog = new Table($this->configuration->getTableName());

        $schemaChangelog->addColumn(
            $this->configuration->getVersionColumnName(),
            'string',
            ['notnull' => true, 'length' => $this->configuration->getVersionColumnLength()],
        );
        $schemaChangelog->addColumn($this->configuration->getExecutedAtColumnName(), 'datetime', ['notnull' => false]);
        $schemaChangelog->addColumn($this->configuration->getExecutionTimeColumnName(), 'integer', ['notnull' => false]);

        $schemaChangelog->setPrimaryKey([$this->configuration->getVersionColumnName()]);

        return $schemaChangelog;
    }

    private function updateMigratedVersionsFromV1orV2toV3(): void
    {
        if ($this->migrationRepository === null) {
            return;
        }

        $availableMigrations = $this->migrationRepository->getMigrations()->getItems();
        $executedMigrations  = $this->getExecutedMigrations()->getItems();

        foreach ($availableMigrations as $availableMigration) {
            foreach ($executedMigrations as $k => $executedMigration) {
                if ($this->isAlreadyV3Format($availableMigration, $executedMigration)) {
                    continue;
                }

                $this->connection->update(
                    $this->configuration->getTableName(),
                    [
                        $this->configuration->getVersionColumnName() => (string) $availableMigration->getVersion(),
                    ],
                    [
                        $this->configuration->getVersionColumnName() => (string) $executedMigration->getVersion(),
                    ],
                );
                unset($executedMigrations[$k]);
            }
        }
    }

    private function isAlreadyV3Format(AvailableMigration $availableMigration, ExecutedMigration $executedMigration): bool
    {
        return strpos(
            (string) $availableMigration->getVersion(),
            (string) $executedMigration->getVersion(),
        ) !== strlen((string) $availableMigration->getVersion()) -
                strlen((string) $executedMigration->getVersion());
    }
}
