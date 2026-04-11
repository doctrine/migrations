<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

final class TableMetadataStorageConfiguration implements MetadataStorageConfiguration
{
    /** @var non-empty-string */
    private string $tableName = 'doctrine_migration_versions';

    /** @var non-empty-string */
    private string $versionColumnName = 'version';

    private int $versionColumnLength = 191;

    private string $executedAtColumnName = 'executed_at';

    private string $executionTimeColumnName = 'execution_time';

    /** @var non-empty-string|null */
    private string|null $schemaName = null;

    /** @return non-empty-string */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /** @param non-empty-string $tableName */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /** @return non-empty-string|null */
    public function getSchemaName(): string|null
    {
        return $this->schemaName;
    }

    /** @param non-empty-string|null $schemaName */
    public function setSchemaName(string|null $schemaName): void
    {
        $this->schemaName = $schemaName;
    }

    /** @return non-empty-string */
    public function getVersionColumnName(): string
    {
        return $this->versionColumnName;
    }

    /** @param non-empty-string $versionColumnName */
    public function setVersionColumnName(string $versionColumnName): void
    {
        $this->versionColumnName = $versionColumnName;
    }

    public function getVersionColumnLength(): int
    {
        return $this->versionColumnLength;
    }

    public function setVersionColumnLength(int $versionColumnLength): void
    {
        $this->versionColumnLength = $versionColumnLength;
    }

    public function getExecutedAtColumnName(): string
    {
        return $this->executedAtColumnName;
    }

    public function setExecutedAtColumnName(string $executedAtColumnName): void
    {
        $this->executedAtColumnName = $executedAtColumnName;
    }

    public function getExecutionTimeColumnName(): string
    {
        return $this->executionTimeColumnName;
    }

    public function setExecutionTimeColumnName(string $executionTimeColumnName): void
    {
        $this->executionTimeColumnName = $executionTimeColumnName;
    }
}
