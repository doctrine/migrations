<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigrationTableCreator;
use PHPUnit\Framework\TestCase;

class MigrationTableCreatorTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var SchemaManager */
    private $schemaManager;

    /** @var MigrationTableCreator */
    private $migrationTableCreator;

    public function testIsMigrationTableCreated() : void
    {
        $this->configuration->expects($this->once())
            ->method('connect');

        $this->configuration->expects($this->once())
            ->method('getMigrationsTableName')
            ->willReturn('table_name');

        $this->schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['table_name'])
            ->willReturn(true);

        self::assertTrue($this->migrationTableCreator->isMigrationTableCreated());
    }

    public function testDreateMigrationTableAlreadyCreated() : void
    {
        $this->configuration->expects($this->once())
            ->method('validate');

        $this->configuration->expects($this->once())
            ->method('connect');

        $this->configuration->expects($this->once())
            ->method('getMigrationsTableName')
            ->willReturn('table_name');

        $this->schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['table_name'])
            ->willReturn(true);

        self::assertFalse($this->migrationTableCreator->createMigrationTable());
    }

    public function testCreateMigrationTable() : void
    {
        $this->configuration->expects($this->once())
            ->method('validate');

        $this->configuration->expects($this->once())
            ->method('connect');

        $this->configuration->expects($this->exactly(2))
            ->method('getMigrationsTableName')
            ->willReturn('table_name');

        $this->schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['table_name'])
            ->willReturn(false);

        $this->configuration->expects($this->once())
            ->method('isDryRun')
            ->willReturn(false);

        $this->configuration->expects($this->exactly(2))
            ->method('getMigrationsTableName')
            ->willReturn('table_name');

        $this->configuration->expects($this->exactly(2))
            ->method('getMigrationsColumnName')
            ->willReturn('column_name');

        $this->configuration->expects($this->once())
            ->method('getMigrationsColumnLength')
            ->willReturn(255);

        $this->schemaManager->expects($this->once())
            ->method('createTable');

        self::assertTrue($this->migrationTableCreator->createMigrationTable());
    }

    protected function setUp() : void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);

        $this->migrationTableCreator = new MigrationTableCreator(
            $this->configuration,
            $this->schemaManager
        );
    }
}
