<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tracking;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Tracking\TableDefinition;
use Doctrine\Migrations\Tracking\TableStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TableStatusTest extends TestCase
{
    /** @var AbstractSchemaManager|MockObject */
    private $schemaManager;

    /** @var TableDefinition|MockObject */
    private $migrationTable;

    /** @var TableStatus */
    private $migrationTableStatus;

    public function testSetCreated() : void
    {
        $this->migrationTableStatus->setCreated(true);

        self::assertTrue($this->migrationTableStatus->isCreated());
    }

    public function testIsCreatedTrue() : void
    {
        $this->migrationTable->expects(self::once())
            ->method('getName')
            ->willReturn('table_name');

        $this->schemaManager->expects(self::once())
            ->method('tablesExist')
            ->with(['table_name'])
            ->willReturn(true);

        self::assertTrue($this->migrationTableStatus->isCreated());
    }

    public function testIsCreatedFalse() : void
    {
        $this->migrationTable->expects(self::once())
            ->method('getName')
            ->willReturn('table_name');

        $this->schemaManager->expects(self::once())
            ->method('tablesExist')
            ->with(['table_name'])
            ->willReturn(false);

        self::assertFalse($this->migrationTableStatus->isCreated());
    }

    public function testSetUpToDate() : void
    {
        $this->migrationTableStatus->setUpToDate(true);

        self::assertTrue($this->migrationTableStatus->isUpToDate());
    }

    public function testIsUpToDateTrue() : void
    {
        $this->migrationTable->expects(self::once())
            ->method('getName')
            ->willReturn('table_name');

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects(self::once())
            ->method('listTableDetails')
            ->with('table_name')
            ->willReturn($table);

        $this->migrationTable->expects(self::once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table->expects(self::at(0))
            ->method('hasColumn')
            ->with('version')
            ->willReturn(true);

        $table->expects(self::at(1))
            ->method('hasColumn')
            ->with('executed_at')
            ->willReturn(true);

        self::assertTrue($this->migrationTableStatus->isUpToDate());
    }

    public function testIsUpToDateFalse() : void
    {
        $this->migrationTable->expects(self::once())
            ->method('getName')
            ->willReturn('table_name');

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects(self::once())
            ->method('listTableDetails')
            ->with('table_name')
            ->willReturn($table);

        $this->migrationTable->expects(self::once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table->expects(self::at(0))
            ->method('hasColumn')
            ->with('version')
            ->willReturn(true);

        $table->expects(self::at(1))
            ->method('hasColumn')
            ->with('executed_at')
            ->willReturn(false);

        self::assertFalse($this->migrationTableStatus->isUpToDate());
    }

    protected function setUp() : void
    {
        $this->schemaManager  = $this->createMock(AbstractSchemaManager::class);
        $this->migrationTable = $this->createMock(TableDefinition::class);

        $this->migrationTableStatus = new TableStatus(
            $this->schemaManager,
            $this->migrationTable
        );
    }
}
