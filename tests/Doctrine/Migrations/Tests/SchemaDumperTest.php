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
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const PHP_VERSION_ID;

class SchemaDumperTest extends TestCase
{
    /** @var AbstractPlatform&MockObject */
    private AbstractPlatform $platform;

    /** @var AbstractSchemaManager<AbstractPlatform>&MockObject */
    private AbstractSchemaManager $schemaManager;

    /** @var Generator&MockObject */
    private Generator $migrationGenerator;

    /** @var SqlGenerator&MockObject */
    private SqlGenerator $migrationSqlGenerator;

    private SchemaDumper $schemaDumper;

    public function testDumpNoTablesException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your database schema does not contain any tables.');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('introspectSchema')
            ->willReturn($schema);

        $schema->expects(self::once())
            ->method('getTables')
            ->willReturn([]);

        $this->schemaDumper->dump('Foo\\1234');
    }

    public function testDump(): void
    {
        $table = $this->createMock(Table::class);
        $table->expects(self::once())
            ->method('getName')
            ->willReturn('test');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('introspectSchema')
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

        $this->migrationSqlGenerator->expects(self::exactly(2))
            ->method('generate')
            ->with(self::logicalOr(
                self::equalTo(['CREATE TABLE test']),
                self::equalTo(['DROP TABLE test'])
            ))
            ->will(self::onConsecutiveCalls('up', 'down'));

        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('Foo\\1234', 'up', 'down')
            ->willReturn('/path/to/migration.php');

        self::assertSame('/path/to/migration.php', $this->schemaDumper->dump('Foo\\1234'));
    }

    public function testExcludedTableIsNotInTheDump(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your database schema does not contain any tables.');

        $table = $this->createMock(Table::class);
        $table->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('skipped_table_name');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('introspectSchema')
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

        self::assertSame('/path/to/migration.php', $this->schemaDumper->dump('Foo\\1234'));
    }

    public function testRegexErrorsAreConvertedToExceptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        if (PHP_VERSION_ID < 80200) {
            $this->expectExceptionMessage('Internal PCRE error, please check your Regex. Reported errors: preg_match(): Delimiter must not be alphanumeric or backslash.');
        } else {
            $this->expectExceptionMessage('Internal PCRE error, please check your Regex. Reported errors: preg_match(): Delimiter must not be alphanumeric, backslash, or NUL.');
        }

        $table = $this->createMock(Table::class);
        $table->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('other_skipped_table_name');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('introspectSchema')
            ->willReturn($schema);

        $schema->expects(self::once())
            ->method('getTables')
            ->willReturn([$table]);

        self::assertSame('/path/to/migration.php', $this->schemaDumper->dump('Foo\\1234', ['invalid regex']));
    }

    public function testExcludedTableViaParamIsNotInTheDump(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your database schema does not contain any tables.');

        $table = $this->createMock(Table::class);
        $table->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('other_skipped_table_name');

        $schema = $this->createMock(Schema::class);

        $this->schemaManager->expects(self::once())
            ->method('introspectSchema')
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

        self::assertSame('/path/to/migration.php', $this->schemaDumper->dump('Foo\\1234', ['/other_skipped_table_name/']));
    }

    protected function setUp(): void
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
