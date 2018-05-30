<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tracking;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Tracking\TableDefinition;
use Doctrine\Migrations\Tracking\TableUpdater;
use Exception;
use PHPUnit\Framework\TestCase;

class TableUpdaterTest extends TestCase
{
    /** @var Connection */
    private $connection;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var TableDefinition */
    private $migrationTable;

    /** @var AbstractPlatform */
    private $platform;

    /** @var TableUpdater */
    private $migrationTableUpdater;

    public function testUpdateMigrationTable() : void
    {
        $this->migrationTable->expects($this->once())
            ->method('getName')
            ->willReturn('table_name');

        $this->migrationTable->expects($this->once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects($this->once())
            ->method('listTableDetails')
            ->willReturn($table);

        $versionColumn = new Column(
            'version',
            Type::getType('string'),
            ['length' => 255]
        );

        $executedAt = new Column(
            'executed_at',
            Type::getType('datetime_immutable')
        );

        $this->migrationTable->expects($this->once())
            ->method('createDBALTable')
            ->with([
                $versionColumn,
                $executedAt,
            ])
            ->willReturn($table);

        $table->expects($this->any())
            ->method('getColumns')
            ->willReturn([
                $versionColumn,
                $executedAt,
            ]);

        $fromSchema = $this->createMock(Schema::class);
        $toSchema   = $this->createMock(Schema::class);

        $this->migrationTableUpdater->expects($this->at(0))
            ->method('createSchema')
            ->willReturn($fromSchema);

        $this->migrationTableUpdater->expects($this->at(1))
            ->method('createSchema')
            ->willReturn($toSchema);

        $fromSchema->expects($this->once())
            ->method('getMigrateToSql')
            ->with($toSchema, $this->platform)
            ->willReturn(['ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL']);

        $this->connection->expects($this->once())
            ->method('beginTransaction');

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL');

        $this->connection->expects($this->once())
            ->method('commit');

        $this->migrationTableUpdater->updateMigrationTable();
    }

    public function testUpdateMigrationTableRollback() : void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Rolling back.');

        $this->migrationTable->expects($this->once())
            ->method('getName')
            ->willReturn('table_name');

        $this->migrationTable->expects($this->once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects($this->once())
            ->method('listTableDetails')
            ->willReturn($table);

        $versionColumn = new Column(
            'version',
            Type::getType('string'),
            ['length' => 255]
        );

        $executedAt = new Column(
            'executed_at',
            Type::getType('datetime_immutable')
        );

        $this->migrationTable->expects($this->once())
            ->method('createDBALTable')
            ->with([
                $versionColumn,
                $executedAt,
            ])
            ->willReturn($table);

        $table->expects($this->any())
            ->method('getColumns')
            ->willReturn([
                $versionColumn,
                $executedAt,
            ]);

        $fromSchema = $this->createMock(Schema::class);
        $toSchema   = $this->createMock(Schema::class);

        $this->migrationTableUpdater->expects($this->at(0))
            ->method('createSchema')
            ->willReturn($fromSchema);

        $this->migrationTableUpdater->expects($this->at(1))
            ->method('createSchema')
            ->willReturn($toSchema);

        $fromSchema->expects($this->once())
            ->method('getMigrateToSql')
            ->with($toSchema, $this->platform)
            ->willReturn(['ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL']);

        $this->connection->expects($this->once())
            ->method('beginTransaction');

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL')
            ->willThrowException(new Exception('Rolling back.'));

        $this->connection->expects($this->once())
            ->method('rollback');

        $this->migrationTableUpdater->updateMigrationTable();
    }

    protected function setUp() : void
    {
        $this->connection     = $this->createMock(Connection::class);
        $this->schemaManager  = $this->createMock(AbstractSchemaManager::class);
        $this->migrationTable = $this->createMock(TableDefinition::class);
        $this->platform       = $this->createMock(AbstractPlatform::class);

        $this->migrationTableUpdater = $this->getMockBuilder(TableUpdater::class)
            ->setConstructorArgs([
                $this->connection,
                $this->schemaManager,
                $this->migrationTable,
                $this->platform,
            ])
            ->setMethods(['createSchema'])
            ->getMock()
        ;
    }
}
