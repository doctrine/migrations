<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Event\Listeners;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Event\Listeners\RemoveMigrationTableFromSchemaListener;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class RemoveMigrationTableFromSchemaListenerTest extends MigrationTestCase
{
    private RemoveMigrationTableFromSchemaListener $listener;
    private Configuration $configuration;
    private EntityManagerInterface $entityManager;

    public function testListenerRemovesMigrationSchema(): void
    {
        $metadataConfiguration = new TableMetadataStorageConfiguration();
        $tableName             = $metadataConfiguration->getTableName();
        $this->configuration->setMetadataStorageConfiguration($metadataConfiguration);

        $schema = new Schema();
        $schema->createTable($tableName);

        static::assertTrue($schema->hasTable($tableName));

        $this->listener->postGenerateSchema(new GenerateSchemaEventArgs($this->entityManager, $schema));

        static::assertFalse($schema->hasTable($tableName));
    }

    public function testListenerIgnoresMissingTable(): void
    {
        $metadataConfiguration = new TableMetadataStorageConfiguration();
        $tableName             = $metadataConfiguration->getTableName();
        $this->configuration->setMetadataStorageConfiguration($metadataConfiguration);

        $schema = new Schema();

        static::assertFalse($schema->hasTable($tableName));

        $this->listener->postGenerateSchema(new GenerateSchemaEventArgs($this->entityManager, $schema));

        static::assertFalse($schema->hasTable($tableName));
    }

    public function testListenerIgnoresMissingConfiguration(): void
    {
        static::expectNotToPerformAssertions();

        $this->listener->postGenerateSchema(new GenerateSchemaEventArgs($this->entityManager, new Schema()));
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->configuration = new Configuration();

        $dependencyFactory = DependencyFactory::fromEntityManager(
            new ExistingConfiguration($this->configuration),
            new ExistingEntityManager($this->entityManager),
        );

        $this->listener = new RemoveMigrationTableFromSchemaListener($dependencyFactory);
    }
}
