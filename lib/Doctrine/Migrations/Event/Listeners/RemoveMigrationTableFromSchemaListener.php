<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Event\Listeners;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class RemoveMigrationTableFromSchemaListener
{
    private Configuration $configuration;

    public function __construct(
        DependencyFactory $dependencyFactory,
    ) {
        $this->configuration = $dependencyFactory->getConfiguration();
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $this->removeMigrationsTableFromSchema($args->getSchema());
    }

    public function postGenerateComparisonSchema(GenerateSchemaEventArgs $args): void
    {
        $this->removeMigrationsTableFromSchema($args->getSchema());
    }

    private function removeMigrationsTableFromSchema(Schema $schema): void
    {
        $metadataConfiguration = $this->configuration->getMetadataStorageConfiguration();

        if (! ($metadataConfiguration instanceof TableMetadataStorageConfiguration)) {
            return;
        }

        $tableName = $metadataConfiguration->getTableName();

        if (! $schema->hasTable($tableName)) {
            return;
        }

        $schema->dropTable($tableName);
    }
}
