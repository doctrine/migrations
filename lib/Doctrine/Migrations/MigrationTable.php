<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class MigrationTable
{
    public const MIGRATION_COLUMN_TYPE             = 'string';
    public const MIGRATION_EXECUTED_AT_COLUMN_TYPE = 'datetime_immutable';
    public const MIGRATION_DIRECTION_COLUMN_TYPE   = 'string';

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var string */
    private $name;

    /** @var string */
    private $columnName;

    /** @var int */
    private $columnLength;

    /** @var string */
    private $executedAtColumnName;

    /** @var string */
    private $directionColumnName;

    public function __construct(
        AbstractSchemaManager $schemaManager,
        string $name,
        string $columnName,
        int $columnLength,
        string $executedAtColumnName,
        string $directionColumnName
    ) {
        $this->schemaManager        = $schemaManager;
        $this->name                 = $name;
        $this->columnName           = $columnName;
        $this->columnLength         = $columnLength;
        $this->executedAtColumnName = $executedAtColumnName;
        $this->directionColumnName  = $directionColumnName;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getColumnName() : string
    {
        return $this->columnName;
    }

    public function getColumnLength() : int
    {
        return $this->columnLength;
    }

    public function getExecutedAtColumnName() : string
    {
        return $this->executedAtColumnName;
    }

    public function getMigrationsColumn() : Column
    {
        return new Column(
            $this->columnName,
            Type::getType(self::MIGRATION_COLUMN_TYPE),
            ['length' => $this->columnLength]
        );
    }

    public function getExecutedAtColumn() : Column
    {
        return new Column(
            $this->executedAtColumnName,
            Type::getType(self::MIGRATION_EXECUTED_AT_COLUMN_TYPE)
        );
    }

    public function getDirectionColumn() : Column
    {
        return new Column(
            $this->directionColumnName,
            Type::getType(self::MIGRATION_DIRECTION_COLUMN_TYPE),
            ['length' => 4, 'default' => VersionDirection::UP]
        );
    }

    /**
     * @return string[]
     */
    public function getColumnNames() : array
    {
        return [
            $this->columnName,
            $this->executedAtColumnName,
            $this->directionColumnName,
        ];
    }

    public function getDBALTable() : Table
    {
        $executedAtColumn = $this->getExecutedAtColumn();
        $executedAtColumn->setNotnull(false);

        $directionColumn = $this->getDirectionColumn();
        $directionColumn->setNotnull(false);

        $columns = [
            $this->columnName           => $this->getMigrationsColumn(),
            $this->executedAtColumnName => $executedAtColumn,
            $this->directionColumnName  => $directionColumn,
        ];

        return $this->createDBALTable($columns);
    }

    public function getNewDBALTable() : Table
    {
        $executedAtColumn = $this->getExecutedAtColumn();
        $executedAtColumn->setNotnull(true);

        $directionColumn = $this->getDirectionColumn();
        $directionColumn->setNotnull(true);

        $columns = [
            $this->columnName           => $this->getMigrationsColumn(),
            $this->executedAtColumnName => $executedAtColumn,
            $this->directionColumnName  => $directionColumn,
        ];

        return $this->createDBALTable($columns);
    }

    /**
     * @param Column[] $columns
     */
    public function createDBALTable(array $columns) : Table
    {
        $schemaConfig = $this->schemaManager->createSchemaConfig();

        $table = new Table($this->getName(), $columns);

        foreach ($schemaConfig->getDefaultTableOptions() as $name => $value) {
            $table->addOption($name, $value);
        }

        return $table;
    }
}
