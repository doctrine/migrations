<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

class TableMetadataStorageConfiguration implements MetadataStorageConfigration
{
    private $tableName = 'doctrine_migration_versions';

    private $versionColumnName = 'version';

    private $versionColumnLength = 2048;

    private $executedAtColumnName = 'executed_at';

    private $executionTimeColumnName = 'execution_time';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getVersionColumnName(): string
    {
        return $this->versionColumnName;
    }

    /**
     * @param string $versionColumnName
     */
    public function setVersionColumnName(string $versionColumnName): void
    {
        $this->versionColumnName = $versionColumnName;
    }

    /**
     * @return int
     */
    public function getVersionColumnLength(): int
    {
        return $this->versionColumnLength;
    }

    /**
     * @param int $versionColumnLength
     */
    public function setVersionColumnLength(int $versionColumnLength): void
    {
        $this->versionColumnLength = $versionColumnLength;
    }

    /**
     * @return string
     */
    public function getExecutedAtColumnName(): string
    {
        return $this->executedAtColumnName;
    }

    /**
     * @param string $executedAtColumnName
     */
    public function setExecutedAtColumnName(string $executedAtColumnName): void
    {
        $this->executedAtColumnName = $executedAtColumnName;
    }

    /**
     * @return string
     */
    public function getExecutionTimeColumnName(): string
    {
        return $this->executionTimeColumnName;
    }

    /**
     * @param string $executionTimeColumnName
     */
    public function setExecutionTimeColumnName(string $executionTimeColumnName): void
    {
        $this->executionTimeColumnName = $executionTimeColumnName;
    }

}
