<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigrationTable;
use Doctrine\Migrations\MigrationTableManipulator;
use Doctrine\Migrations\MigrationTableStatus;
use Doctrine\Migrations\MigrationTableUpdater;
use PHPUnit\Framework\TestCase;

class MigrationTableManipulatorTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var MigrationTable */
    private $migrationTable;

    /** @var MigrationTableStatus */
    private $migrationTableStatus;

    /** @var MigrationTableUpdater */
    private $migrationTableUpdater;

    /** @var MigrationTableManipulator */
    private $migrationTableManipulator;

    public function testCreateMigrationTableAlreadyCreated() : void
    {
        $this->configuration->expects($this->once())
            ->method('validate');

        $this->configuration->expects($this->once())
            ->method('isDryRun')
            ->willReturn(false);

        $this->migrationTableStatus->expects($this->once())
            ->method('isCreated')
            ->willReturn(true);

        $this->migrationTableStatus->expects($this->once())
            ->method('isUpToDate')
            ->willReturn(true);

        self::assertFalse($this->migrationTableManipulator->createMigrationTable());
    }

    public function testCreateMigrationTableNotUpToDate() : void
    {
        $migrationTableManipulator = $this->getMockBuilder(MigrationTableManipulator::class)
            ->setConstructorArgs([
                $this->configuration,
                $this->schemaManager,
                $this->migrationTable,
                $this->migrationTableStatus,
                $this->migrationTableUpdater,
            ])
            ->setMethods(['createSchema'])
            ->getMock();

        $this->configuration->expects($this->once())
            ->method('validate');

        $this->configuration->expects($this->once())
            ->method('isDryRun')
            ->willReturn(false);

        $this->migrationTableStatus->expects($this->once())
            ->method('isCreated')
            ->willReturn(true);

        $this->migrationTableStatus->expects($this->once())
            ->method('isUpToDate')
            ->willReturn(false);

        $this->migrationTableUpdater->expects($this->once())
            ->method('updateMigrationTable');

        $this->migrationTableStatus->expects($this->once())
            ->method('setUpToDate')
            ->with(true);

        self::assertTrue($migrationTableManipulator->createMigrationTable());
    }

    public function testCreateMigrationTable() : void
    {
        $this->configuration->expects($this->once())
            ->method('validate');

        $this->configuration->expects($this->once())
            ->method('isDryRun')
            ->willReturn(false);

        $this->migrationTableStatus->expects($this->once())
            ->method('isCreated')
            ->willReturn(false);

        $table = $this->createMock(Table::class);

        $this->migrationTable->expects($this->once())
            ->method('getNewDBALTable')
            ->willReturn($table);

        $this->schemaManager->expects($this->once())
            ->method('createTable')
            ->with($table);

        self::assertTrue($this->migrationTableManipulator->createMigrationTable());
    }

    protected function setUp() : void
    {
        $this->configuration         = $this->createMock(Configuration::class);
        $this->schemaManager         = $this->createMock(AbstractSchemaManager::class);
        $this->migrationTable        = $this->createMock(MigrationTable::class);
        $this->migrationTableStatus  = $this->createMock(MigrationTableStatus::class);
        $this->migrationTableUpdater = $this->createMock(MigrationTableUpdater::class);

        $this->migrationTableManipulator = new MigrationTableManipulator(
            $this->configuration,
            $this->schemaManager,
            $this->migrationTable,
            $this->migrationTableStatus,
            $this->migrationTableUpdater
        );
    }
}
