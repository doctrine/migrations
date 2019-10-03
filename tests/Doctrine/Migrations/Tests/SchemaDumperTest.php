<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\SchemaDumper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SchemaDumperTest extends TestCase
{
    /** @var AbstractPlatform|MockObject */
    private $platform;

    /** @var AbstractSchemaManager|MockObject */
    private $schemaManager;

    /** @var Generator|MockObject */
    private $migrationGenerator;

    /** @var SqlGenerator|MockObject */
    private $migrationSqlGenerator;

    /** @var SchemaDumper */
    private $schemaDumper;

    public function testDumpNoTablesException() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your database schema does not contain any tables.');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('createSchema')
            ->willReturn($schema);

        $schema->expects(self::once())
            ->method('getTables')
            ->willReturn([]);

        $this->schemaDumper->dump('1234', 'Foo');
    }

    public function testDump() : void
    {
        $table = $this->createMock(Table::class);
        $table->expects(self::once())
            ->method('getName')
            ->willReturn('test');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('createSchema')
            ->willReturn($schema);

        $schema->expects(self::once())
            ->method('getTables')
            ->willReturn([$table]);

        $this->platform->expects(self::once())
            ->method('getCreateTableSQL')
            ->willReturn(['CREATE TABLE test']);

        $this->platform->expects(self::once())
            ->method('getDropTableSQL')
            ->willReturn('DROP TABLE test');

        $this->migrationSqlGenerator->expects(self::at(0))
            ->method('generate')
            ->with(['CREATE TABLE test'])
            ->willReturn('up');

        $this->migrationSqlGenerator->expects(self::at(1))
            ->method('generate')
            ->with(['DROP TABLE test'])
            ->willReturn('down');

        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('1234', 'Foo', 'up', 'down')
            ->willReturn('/path/to/migration.php');

        self::assertSame('/path/to/migration.php', $this->schemaDumper->dump('1234', 'Foo'));
    }

    public function testExcludedTableIsNotInTheDump() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your database schema does not contain any tables.');

        $table = $this->createMock(Table::class);
        $table->expects(self::once())
            ->method('getName')
            ->willReturn('skipped_table_name');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('createSchema')
            ->willReturn($schema);

        $schema->expects(self::once())
            ->method('getTables')
            ->willReturn([$table]);

        $this->platform->expects(self::never())
            ->method('getCreateTableSQL');

        $this->platform->expects(self::never())
            ->method('getDropTableSQL');

        $this->migrationSqlGenerator->expects(self::never())
            ->method('generate');

        $this->migrationGenerator->expects(self::never())
            ->method('generateMigration');

        self::assertSame('/path/to/migration.php', $this->schemaDumper->dump('1234', 'Foo'));
    }

    protected function setUp() : void
    {
        $this->platform              = $this->createMock(AbstractPlatform::class);
        $this->schemaManager         = $this->createMock(AbstractSchemaManager::class);
        $this->migrationGenerator    = $this->createMock(Generator::class);
        $this->migrationSqlGenerator = $this->createMock(SqlGenerator::class);

        $this->schemaDumper = new SchemaDumper(
            $this->platform,
            $this->schemaManager,
            $this->migrationGenerator,
            $this->migrationSqlGenerator,
            ['/skipped_table_name/']
        );
    }
}
