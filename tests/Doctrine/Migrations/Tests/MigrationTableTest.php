<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\MigrationTable;
use PHPUnit\Framework\TestCase;

class MigrationTableTest extends TestCase
{
    /** @var MigrationTable */
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
        $table = $this->migrationTable->getDBALTable();

        self::assertCount(2, $table->getColumns());

        self::assertTrue($table->hasColumn('version_name'));
        self::assertTrue($table->getColumn('version_name')->getNotnull());

        self::assertTrue($table->hasColumn('executed_datetime'));
        self::assertFalse($table->getColumn('executed_datetime')->getNotnull());
    }

    public function testGetNewDBALTable() : void
    {
        $table = $this->migrationTable->getNewDBALTable();

        self::assertCount(2, $table->getColumns());

        self::assertTrue($table->hasColumn('version_name'));
        self::assertTrue($table->getColumn('version_name')->getNotnull());

        self::assertTrue($table->hasColumn('executed_datetime'));
        self::assertTrue($table->getColumn('executed_datetime')->getNotnull());
    }

    protected function setUp() : void
    {
        $this->migrationTable = new MigrationTable(
            'versions',
            'version_name',
            200,
            'executed_datetime'
        );
    }
}
