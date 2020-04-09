<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

final class TableMetadataStorageConfiguration implements MetadataStorageConfiguration
{
    /** @var string */
    private $tableName = 'doctrine_migration_versions';

    /** @var string */
    private $versionColumnName = 'version';

    /** @var int */
    private $versionColumnLength = 191;

    /** @var string */
    private $executedAtColumnName = 'executed_at';

    /** @var string */
    private $executionTimeColumnName = 'execution_time';

    public function getTableName() : string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName) : void
    {
        $this->tableName = $tableName;
    }

    public function getVersionColumnName() : string
    {
        return $this->versionColumnName;
    }

    public function setVersionColumnName(string $versionColumnName) : void
    {
        $this->versionColumnName = $versionColumnName;
    }

    public function getVersionColumnLength() : int
    {
        return $this->versionColumnLength;
    }

    public function setVersionColumnLength(int $versionColumnLength) : void
    {
        $this->versionColumnLength = $versionColumnLength;
    }

    public function getExecutedAtColumnName() : string
    {
        return $this->executedAtColumnName;
    }

    public function setExecutedAtColumnName(string $executedAtColumnName) : void
    {
        $this->executedAtColumnName = $executedAtColumnName;
    }

    public function getExecutionTimeColumnName() : string
    {
        return $this->executionTimeColumnName;
    }

    public function setExecutionTimeColumnName(string $executionTimeColumnName) : void
    {
        $this->executionTimeColumnName = $executionTimeColumnName;
    }
}
