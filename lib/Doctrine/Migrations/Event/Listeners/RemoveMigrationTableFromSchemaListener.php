<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Event\Listeners;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class RemoveMigrationTableFromSchemaListener
{
    private TableMetadataStorageConfiguration|null $configuration = null;

    public function __construct(
        private readonly DependencyFactory $dependencyFactory,
    ) {
        $configuration = $this->dependencyFactory->getConfiguration()->getMetadataStorageConfiguration();

        if ($configuration instanceof TableMetadataStorageConfiguration) {
            $this->configuration = $configuration;
        }
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $this->removeMigrationsTableFromSchema($args->getSchema());
    }

    public function postGenerateComparisonSchema(GenerateSchemaEventArgs $args): void
    {
        $this->removeMigrationsTableFromSchema($args->getSchema());
    }

    private function removeMigrationsTableFromSchema(Schema $schema)
    {
        if (!($this->configuration instanceof TableMetadataStorageConfiguration)) {
            return;
        }

        $tableName = $this->configuration->getTableName();

        if ($schema->hasTable($tableName)) {
            $schema->dropTable($tableName);
        }
    }
}
