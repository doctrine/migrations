<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tracking;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tracking\TableDefinition;
use Doctrine\Migrations\Tracking\TableManipulator;
use Doctrine\Migrations\Tracking\TableStatus;
use Doctrine\Migrations\Tracking\TableUpdater;
use PHPUnit\Framework\TestCase;

class TableManipulatorTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var TableDefinition */
    private $migrationTable;

    /** @var TableStatus */
    private $migrationTableStatus;

    /** @var TableUpdater */
    private $migrationTableUpdater;

    /** @var TableManipulator */
    private $migrationTableManipulator;

    public function testCreateMigrationTableAlreadyCreated() : void
    {
        $this->configuration->expects(self::once())
            ->method('validate');

        $this->configuration->expects(self::once())
            ->method('isDryRun')
            ->willReturn(false);

        $this->migrationTableStatus->expects(self::once())
            ->method('isCreated')
            ->willReturn(true);

        $this->migrationTableStatus->expects(self::once())
            ->method('isUpToDate')
            ->willReturn(true);

        self::assertFalse($this->migrationTableManipulator->createMigrationTable());
    }

    public function testCreateMigrationTableNotUpToDate() : void
    {
        $migrationTableManipulator = $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([
                $this->configuration,
                $this->schemaManager,
                $this->migrationTable,
                $this->migrationTableStatus,
                $this->migrationTableUpdater,
            ])
            ->setMethods(['createSchema'])
            ->getMock();

        $this->configuration->expects(self::once())
            ->method('validate');

        $this->configuration->expects(self::once())
            ->method('isDryRun')
            ->willReturn(false);

        $this->migrationTableStatus->expects(self::once())
            ->method('isCreated')
            ->willReturn(true);

        $this->migrationTableStatus->expects(self::once())
            ->method('isUpToDate')
            ->willReturn(false);

        $this->migrationTableUpdater->expects(self::once())
            ->method('updateMigrationTable');

        $this->migrationTableStatus->expects(self::once())
            ->method('setUpToDate')
            ->with(true);

        self::assertTrue($migrationTableManipulator->createMigrationTable());
    }

    public function testCreateMigrationTable() : void
    {
        $this->configuration->expects(self::once())
            ->method('validate');

        $this->configuration->expects(self::once())
            ->method('isDryRun')
            ->willReturn(false);

        $this->migrationTableStatus->expects(self::once())
            ->method('isCreated')
            ->willReturn(false);

        $table = $this->createMock(Table::class);

        $this->migrationTable->expects(self::once())
            ->method('getNewDBALTable')
            ->willReturn($table);

        $this->schemaManager->expects(self::once())
            ->method('createTable')
            ->with($table);

        self::assertTrue($this->migrationTableManipulator->createMigrationTable());
    }

    protected function setUp() : void
    {
        $this->configuration         = $this->createMock(Configuration::class);
        $this->schemaManager         = $this->createMock(AbstractSchemaManager::class);
        $this->migrationTable        = $this->createMock(TableDefinition::class);
        $this->migrationTableStatus  = $this->createMock(TableStatus::class);
        $this->migrationTableUpdater = $this->createMock(TableUpdater::class);

        $this->migrationTableManipulator = new TableManipulator(
            $this->configuration,
            $this->schemaManager,
            $this->migrationTable,
            $this->migrationTableStatus,
            $this->migrationTableUpdater
        );
    }
}
