<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\MigrationGenerator;
use Doctrine\Migrations\MigrationSqlGenerator;
use Doctrine\Migrations\SchemaDumper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SchemaDumperTest extends TestCase
{
    /** @var AbstractPlatform */
    private $platform;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var MigrationGenerator */
    private $migrationGenerator;

    /** @var MigrationSqlGenerator */
    private $migrationSqlGenerator;

    /** @var SchemaDumper */
    private $schemaDumper;

    public function testDumpNoTablesException() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your database schema does not contain any tables.');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects($this->once())
            ->method('createSchema')
            ->willReturn($schema);

        $schema->expects($this->once())
            ->method('getTables')
            ->willReturn([]);

        $this->schemaDumper->dump('1234');
    }

    public function testDump() : void
    {
        $table = $this->createMock(Table::class);

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects($this->once())
            ->method('createSchema')
            ->willReturn($schema);

        $schema->expects($this->once())
            ->method('getTables')
            ->willReturn([$table]);

        $this->platform->expects($this->once())
            ->method('getCreateTableSQL')
            ->willReturn(['CREATE TABLE test']);

        $this->platform->expects($this->once())
            ->method('getDropTableSQL')
            ->willReturn('DROP TABLE test');

        $this->migrationSqlGenerator->expects($this->at(0))
            ->method('generate')
            ->with(['CREATE TABLE test'])
            ->willReturn('up');

        $this->migrationSqlGenerator->expects($this->at(1))
            ->method('generate')
            ->with(['DROP TABLE test'])
            ->willReturn('down');

        $this->migrationGenerator->expects($this->once())
            ->method('generateMigration')
            ->with('1234', 'up', 'down')
            ->willReturn('/path/to/migration.php');

        self::assertEquals('/path/to/migration.php', $this->schemaDumper->dump('1234'));
    }

    protected function setUp() : void
    {
        $this->platform              = $this->createMock(AbstractPlatform::class);
        $this->schemaManager         = $this->createMock(AbstractSchemaManager::class);
        $this->migrationGenerator    = $this->createMock(MigrationGenerator::class);
        $this->migrationSqlGenerator = $this->createMock(MigrationSqlGenerator::class);

        $this->schemaDumper = new SchemaDumper(
            $this->platform,
            $this->schemaManager,
            $this->migrationGenerator,
            $this->migrationSqlGenerator
        );
    }
}
