<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\MigrationTable;
use Doctrine\Migrations\MigrationTableStatus;
use PHPUnit\Framework\TestCase;

class MigrationTableStatusTest extends TestCase
{
    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var MigrationTable */
    private $migrationTable;

    /** @var MigrationTableStatus */
    private $migrationTableStatus;

    public function testSetCreated() : void
    {
        $this->migrationTableStatus->setCreated(true);

        self::assertTrue($this->migrationTableStatus->isCreated());
    }

    public function testIsCreatedTrue() : void
    {
        $this->migrationTable->expects($this->once())
            ->method('getName')
            ->willReturn('table_name');

        $this->schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['table_name'])
            ->willReturn(true);

        self::assertTrue($this->migrationTableStatus->isCreated());
    }

    public function testIsCreatedFalse() : void
    {
        $this->migrationTable->expects($this->once())
            ->method('getName')
            ->willReturn('table_name');

        $this->schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['table_name'])
            ->willReturn(false);

        self::assertFalse($this->migrationTableStatus->isCreated());
    }

    public function testSetUpToDate() : void
    {
        $this->migrationTableStatus->setUpTodate(true);

        self::assertTrue($this->migrationTableStatus->isUpToDate());
    }

    public function testIsUpToDateTrue() : void
    {
        $this->migrationTable->expects($this->once())
            ->method('getName')
            ->willReturn('table_name');

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects($this->once())
            ->method('listTableDetails')
            ->with('table_name')
            ->willReturn($table);

        $this->migrationTable->expects($this->once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table->expects($this->at(0))
            ->method('hasColumn')
            ->with('version')
            ->willReturn(true);

        $table->expects($this->at(1))
            ->method('hasColumn')
            ->with('executed_at')
            ->willReturn(true);

        self::assertTrue($this->migrationTableStatus->isUpTodate());
    }

    public function testIsUpToDateFalse() : void
    {
        $this->migrationTable->expects($this->once())
            ->method('getName')
            ->willReturn('table_name');

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects($this->once())
            ->method('listTableDetails')
            ->with('table_name')
            ->willReturn($table);

        $this->migrationTable->expects($this->once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table->expects($this->at(0))
            ->method('hasColumn')
            ->with('version')
            ->willReturn(true);

        $table->expects($this->at(1))
            ->method('hasColumn')
            ->with('executed_at')
            ->willReturn(false);

        self::assertFalse($this->migrationTableStatus->isUpTodate());
    }

    protected function setUp() : void
    {
        $this->schemaManager  = $this->createMock(AbstractSchemaManager::class);
        $this->migrationTable = $this->createMock(MigrationTable::class);

        $this->migrationTableStatus = new MigrationTableStatus(
            $this->schemaManager,
            $this->migrationTable
        );
    }
}
