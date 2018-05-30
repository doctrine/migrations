<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tracking;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\Migrations\Tracking\TableDefinition;
use PHPUnit\Framework\TestCase;

class TableDefinitionTest extends TestCase
{
    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var TableDefinition */
    private $migrationTable;

    public function testGetName() : void
    {
        self::assertEquals('versions', $this->migrationTable->getName());
    }

    public function testGetColumnName() : void
    {
        self::assertEquals('version_name', $this->migrationTable->getColumnName());
    }

    public function testGetColumnLength() : void
    {
        self::assertEquals(200, $this->migrationTable->getColumnLength());
    }

    public function testGetExecutedAtColumnName() : void
    {
        self::assertEquals('executed_datetime', $this->migrationTable->getExecutedAtColumnName());
    }

    public function testGetMigrationsColumn() : void
    {
        $column = $this->migrationTable->getMigrationsColumn();

        self::assertEquals('version_name', $column->getName());
        self::assertEquals(200, $column->getLength());
    }

    public function testGetExecutedAtColumn() : void
    {
        $column = $this->migrationTable->getExecutedAtColumn();

        self::assertEquals('executed_datetime', $column->getName());
        self::assertTrue($column->getNotnull());
    }

    public function testGetColumnNames() : void
    {
        self::assertEquals(['version_name', 'executed_datetime'], $this->migrationTable->getColumnNames());
    }

    public function testGetDBALTable() : void
    {
        $schemaConfig = $this->createMock(SchemaConfig::class);

        $this->schemaManager->expects($this->once())
            ->method('createSchemaConfig')
            ->willReturn($schemaConfig);

        $schemaConfig->expects($this->once())
            ->method('getDefaultTableOptions')
            ->willReturn(['test_option' => true]);

        $table = $this->migrationTable->getDBALTable();

        self::assertCount(2, $table->getColumns());

        self::assertTrue($table->hasOption('test_option'));
        self::assertEquals(true, $table->getOption('test_option'));

        self::assertTrue($table->hasColumn('version_name'));
        self::assertTrue($table->getColumn('version_name')->getNotnull());

        self::assertTrue($table->hasColumn('executed_datetime'));
        self::assertFalse($table->getColumn('executed_datetime')->getNotnull());
    }

    public function testGetNewDBALTable() : void
    {
        $schemaConfig = $this->createMock(SchemaConfig::class);

        $this->schemaManager->expects($this->once())
            ->method('createSchemaConfig')
            ->willReturn($schemaConfig);

        $schemaConfig->expects($this->once())
            ->method('getDefaultTableOptions')
            ->willReturn(['test_option' => true]);

        $table = $this->migrationTable->getNewDBALTable();

        self::assertCount(2, $table->getColumns());

        self::assertTrue($table->hasOption('test_option'));
        self::assertEquals(true, $table->getOption('test_option'));

        self::assertTrue($table->hasColumn('version_name'));
        self::assertTrue($table->getColumn('version_name')->getNotnull());

        self::assertTrue($table->hasColumn('executed_datetime'));
        self::assertTrue($table->getColumn('executed_datetime')->getNotnull());
    }

    protected function setUp() : void
    {
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);

        $this->migrationTable = new TableDefinition(
            $this->schemaManager,
            'versions',
            'version_name',
            200,
            'executed_datetime'
        );
    }
}
