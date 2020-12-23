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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

class TableUpdaterTest extends TestCase
{
    /** @var Connection|MockObject */
    private $connection;

    /** @var AbstractSchemaManager|MockObject */
    private $schemaManager;

    /** @var TableDefinition|MockObject */
    private $migrationTable;

    /** @var AbstractPlatform|MockObject */
    private $platform;

    /** @var TableUpdater|MockObject */
    private $migrationTableUpdater;

    public function testUpdateMigrationTable(): void
    {
        $this->migrationTable->expects(self::once())
            ->method('getName')
            ->willReturn('table_name');

        $this->migrationTable->expects(self::once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects(self::once())
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

        $this->migrationTable->expects(self::once())
            ->method('createDBALTable')
            ->with([
                $versionColumn,
                $executedAt,
            ])
            ->willReturn($table);

        $table->expects(self::any())
            ->method('getColumns')
            ->willReturn([
                $versionColumn,
                $executedAt,
            ]);

        $fromSchema = $this->createMock(Schema::class);
        $toSchema   = $this->createMock(Schema::class);

        $this->migrationTableUpdater->expects(self::at(0))
            ->method('createSchema')
            ->willReturn($fromSchema);

        $this->migrationTableUpdater->expects(self::at(1))
            ->method('createSchema')
            ->willReturn($toSchema);

        $fromSchema->expects(self::once())
            ->method('getMigrateToSql')
            ->with($toSchema, $this->platform)
            ->willReturn(['ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL']);

        $this->connection->expects(self::once())
            ->method('beginTransaction');

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with('ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL');

        $this->connection->expects(self::once())
            ->method('commit');

        $this->migrationTableUpdater->updateMigrationTable();
    }

    public function testUpdateMigrationTableRollback(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Rolling back.');

        $this->migrationTable->expects(self::once())
            ->method('getName')
            ->willReturn('table_name');

        $this->migrationTable->expects(self::once())
            ->method('getColumnNames')
            ->willReturn([
                'version',
                'executed_at',
            ]);

        $table = $this->createMock(Table::class);

        $this->schemaManager->expects(self::once())
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

        $this->migrationTable->expects(self::once())
            ->method('createDBALTable')
            ->with([
                $versionColumn,
                $executedAt,
            ])
            ->willReturn($table);

        $table->expects(self::any())
            ->method('getColumns')
            ->willReturn([
                $versionColumn,
                $executedAt,
            ]);

        $fromSchema = $this->createMock(Schema::class);
        $toSchema   = $this->createMock(Schema::class);

        $this->migrationTableUpdater->expects(self::at(0))
            ->method('createSchema')
            ->willReturn($fromSchema);

        $this->migrationTableUpdater->expects(self::at(1))
            ->method('createSchema')
            ->willReturn($toSchema);

        $fromSchema->expects(self::once())
            ->method('getMigrateToSql')
            ->with($toSchema, $this->platform)
            ->willReturn(['ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL']);

        $this->connection->expects(self::once())
            ->method('beginTransaction');

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with('ALTER TABLE table_name ADD COLUMN executed_at DATETIME DEFAULT NULL')
            ->willThrowException(new Exception('Rolling back.'));

        $this->connection->expects(self::once())
            ->method('rollback');

        $this->migrationTableUpdater->updateMigrationTable();
    }

    protected function setUp(): void
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
            ->getMock();
    }
}
