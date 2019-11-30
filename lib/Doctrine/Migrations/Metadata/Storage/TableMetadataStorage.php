<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use InvalidArgumentException;
use const CASE_LOWER;
use function array_change_key_case;
use function intval;
use function sprintf;
use function strtolower;

final class TableMetadataStorage implements MetadataStorage
{
    /** @var Connection */
    private $connection;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var AbstractPlatform */
    private $platform;

    /** @var TableMetadataStorageConfiguration */
    private $configuration;

    public function __construct(Connection $connection, ?MetadataStorageConfiguration $configuration = null)
    {
        $this->connection    = $connection;
        $this->schemaManager = $connection->getSchemaManager();
        $this->platform      = $connection->getDatabasePlatform();

        if ($configuration !== null && ! ($configuration instanceof TableMetadataStorageConfiguration)) {
            throw new InvalidArgumentException(sprintf('%s accepts only %s as configuration', self::class, TableMetadataStorageConfiguration::class));
        }
        $this->configuration = $configuration ?: new TableMetadataStorageConfiguration();
    }

    private function isInitialized() : bool
    {
        if ($this->connection instanceof MasterSlaveConnection) {
            $this->connection->connect('master');
        }

        return $this->schemaManager->tablesExist([$this->configuration->getTableName()]);
    }

    private function initialize() : void
    {
        $schemaChangelog = new Table($this->configuration->getTableName());

        $schemaChangelog->addColumn($this->configuration->getVersionColumnName(), 'string', ['notnull' => true, 'length' => $this->configuration->getVersionColumnLength()]);
        $schemaChangelog->addColumn($this->configuration->getExecutedAtColumnName(), 'datetime', ['notnull' => false]);
        $schemaChangelog->addColumn($this->configuration->getExecutionTimeColumnName(), 'integer', ['notnull' => false]);

        $schemaChangelog->setPrimaryKey([$this->configuration->getVersionColumnName()]);

        $this->schemaManager->createTable($schemaChangelog);
    }

    public function getExecutedMigrations() : ExecutedMigrationsSet
    {
        if (! $this->isInitialized()) {
            $this->initialize();
        }

        $rows = $this->connection->fetchAll(sprintf('SELECT * FROM %s', $this->configuration->getTableName()));

        $migrations = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);

            $version = new Version($row[strtolower($this->configuration->getVersionColumnName())]);

            $executedAt = $row[strtolower($this->configuration->getExecutedAtColumnName())] ?? '';
            $executedAt = $executedAt !== ''
                ? DateTimeImmutable::createFromFormat($this->platform->getDateTimeFormatString(), $executedAt)
                : null;

            $executionTime = isset($row[strtolower($this->configuration->getExecutionTimeColumnName())])
                ? intval($row[strtolower($this->configuration->getExecutionTimeColumnName())])
                : null;

            $migration = new ExecutedMigration(
                $version,
                $executedAt instanceof DateTimeImmutable ? $executedAt : null,
                $executionTime
            );

            $migrations[(string) $version] = $migration;
        }

        return new ExecutedMigrationsSet($migrations);
    }

    public function reset() : void
    {
        $this->connection->executeUpdate(
            sprintf(
                'DELETE FROM %s WHERE 1 = 1',
                $this->platform->quoteIdentifier($this->configuration->getTableName())
            )
        );
    }

    public function complete(ExecutionResult $result) : void
    {
        if (! $this->isInitialized()) {
            $this->initialize();
        }

        if ($result->getDirection() === Direction::DOWN) {
            $this->connection->delete($this->configuration->getTableName(), [
                $this->configuration->getVersionColumnName() => (string) $result->getVersion(),
            ]);
        } else {
            $this->connection->insert($this->configuration->getTableName(), [
                $this->configuration->getVersionColumnName() => (string) $result->getVersion(),
                $this->configuration->getExecutedAtColumnName() => $result->getExecutedAt(),
                $this->configuration->getExecutionTimeColumnName() => $result->getTime(),
            ], [
                Type::STRING,
                Type::DATETIME,
                Type::INTEGER,
            ]);
        }
    }
}
