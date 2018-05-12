<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Configuration\Configuration;

/**
 * @internal
 */
final class MigrationTableCreator
{
    private const MIGRATION_COLUMN_TYPE = 'string';

    /** @var Configuration */
    private $configuration;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var bool|null */
    private $migrationTableCreated;

    public function __construct(Configuration $configuration, AbstractSchemaManager $schemaManager)
    {
        $this->configuration = $configuration;
        $this->schemaManager = $schemaManager;
    }

    public function isMigrationTableCreated() : bool
    {
        if ($this->migrationTableCreated === null) {
            $this->configuration->connect();

            $migrationsTableName = $this->configuration->getMigrationsTableName();

            if ($this->schemaManager->tablesExist([$migrationsTableName])) {
                $this->migrationTableCreated = true;
            } else {
                $this->migrationTableCreated = false;
            }
        }

        return $this->migrationTableCreated;
    }

    public function createMigrationTable() : bool
    {
        $this->configuration->validate();

        if ($this->configuration->isDryRun()) {
            return false;
        }

        if ($this->isMigrationTableCreated()) {
            return false;
        }

        $migrationsTableName  = $this->configuration->getMigrationsTableName();
        $migrationsColumnName = $this->configuration->getMigrationsColumnName();

        $columns = [
            $migrationsColumnName => $this->getMigrationsColumn(),
        ];

        $table = new Table($migrationsTableName, $columns);
        $table->setPrimaryKey([$migrationsColumnName]);

        $this->schemaManager->createTable($table);

        $this->migrationTableCreated = true;

        return true;
    }

    public function getMigrationsColumn() : Column
    {
        return new Column(
            $this->configuration->getMigrationsColumnName(),
            Type::getType(self::MIGRATION_COLUMN_TYPE),
            ['length' => $this->configuration->getMigrationsColumnLength()]
        );
    }
}
