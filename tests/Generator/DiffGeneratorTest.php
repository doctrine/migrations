<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Generator\DiffGenerator;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Provider\SchemaProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function in_array;

class DiffGeneratorTest extends TestCase
{
    private DBALConfiguration&MockObject $dbalConfiguration;

    /** @var AbstractSchemaManager<AbstractPlatform>&MockObject */
    private AbstractSchemaManager&MockObject $schemaManager;

    private SchemaProvider&MockObject $schemaProvider;
    private AbstractPlatform&MockObject $platform;
    private Generator&MockObject $migrationGenerator;
    private SqlGenerator&MockObject $migrationSqlGenerator;
    private DiffGenerator $migrationDiffGenerator;
    private SchemaProvider&MockObject $emptySchemaProvider;

    public function testGenerate(): void
    {
        $fromSchema = $this->createMock(Schema::class);
        $toSchema   = $this->createMock(Schema::class);

        $this->dbalConfiguration->expects(self::once())
            ->method('setSchemaAssetsFilter');

        $this->dbalConfiguration->expects(self::once())
            ->method('getSchemaAssetsFilter')
            ->willReturn(
                static fn ($name): bool => in_array($name, ['table_name1', 'table_name2_id_seq'], true),
            );

        $table1 = $this->createMock(Table::class);
        $table1->expects(self::once())
            ->method('getName')
            ->willReturn('schema.table_name1');

        $table2 = $this->createMock(Table::class);
        $table2->expects(self::once())
            ->method('getName')
            ->willReturn('schema.table_name2');

        $table3 = $this->createMock(Table::class);
        $table3->expects(self::once())
            ->method('getName')
            ->willReturn('schema.table_name3');

        $sequence1 = $this->createMock(Sequence::class);
        $sequence1->expects(self::once())
            ->method('getName')
            ->willReturn('table_name1_id_seq');

        $sequence2 = $this->createMock(Sequence::class);
        $sequence2->expects(self::once())
            ->method('getName')
            ->willReturn('table_name2_id_seq');

        $toSchema->expects(self::once())
            ->method('getTables')
            ->willReturn([$table1, $table2, $table3]);

        $toSchema->expects(self::once())
            ->method('getSequences')
            ->willReturn([$sequence1, $sequence2]);

        $this->emptySchemaProvider->expects(self::never())
            ->method('createSchema');

        $this->schemaManager->expects(self::once())
            ->method('introspectSchema')
            ->willReturn($fromSchema);

        $this->schemaProvider->expects(self::once())
            ->method('createSchema')
            ->willReturn($toSchema);

        $toSchema->expects(self::exactly(2))
            ->method('dropTable')
            ->willReturnSelf();

        $toSchema->expects(self::once())
            ->method('dropSequence')
            ->with('table_name1_id_seq');

        $schemaDiff = self::createStub(SchemaDiff::class);

        $this->platform->method('getAlterSchemaSQL')->willReturnCallback(static function (): array {
            static $i = 0;
            if ($i++ === 0) {
                return ['UPDATE table SET value = 2'];
            }

            return ['UPDATE table SET value = 1'];
        });

        $comparator = $this->mockComparator($schemaDiff);

        $this->schemaManager->expects(self::once())
            ->method('createComparator')
            ->willReturn($comparator);

        $this->migrationSqlGenerator->expects(self::exactly(2))
            ->method('generate')
            ->with(self::logicalOr(
                self::equalTo(['UPDATE table SET value = 2']),
                self::equalTo(['UPDATE table SET value = 1']),
            ), true, 80)
            ->willReturnOnConsecutiveCalls('test1', 'test2');

        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('1234', 'test1', 'test2')
            ->willReturn('path');

        self::assertSame('path', $this->migrationDiffGenerator->generate(
            '1234',
            '/table_name1/',
            true,
            80,
        ));
    }

    public function testGenerateFromEmptySchema(): void
    {
        $emptySchema = $this->createMock(Schema::class);
        $toSchema    = $this->createMock(Schema::class);

        $this->dbalConfiguration->expects(self::never())
            ->method('setSchemaAssetsFilter');

        $this->dbalConfiguration->expects(self::once())
            ->method('getSchemaAssetsFilter')
            ->willReturn(static fn () => true);

        $toSchema->method('getTables')
            ->willReturn([new Table('table_name')]);

        $this->emptySchemaProvider->expects(self::once())
            ->method('createSchema')
            ->willReturn($emptySchema);

        $this->schemaManager->expects(self::never())
            ->method('introspectSchema');

        $this->schemaProvider->expects(self::once())
            ->method('createSchema')
            ->willReturn($toSchema);

        $toSchema->expects(self::never())
            ->method('dropTable');

        $schemaDiff = self::createStub(SchemaDiff::class);
        $this->platform->method('getAlterSchemaSQL')->willReturnCallback(static function (): array {
            static $i = 0;
            if ($i++ === 0) {
                return ['CREATE TABLE table_name'];
            }

            return ['DROP TABLE table_name'];
        });

        // regular mocks cannot be used here, because the method is static
        $comparator = $this->mockComparator($schemaDiff);

        $this->schemaManager->expects(self::once())
            ->method('createComparator')
            ->willReturn($comparator);

        $this->migrationSqlGenerator->expects(self::exactly(2))
            ->method('generate')
            ->with(self::logicalOr(
                self::equalTo(['CREATE TABLE table_name']),
                self::equalTo(['DROP TABLE table_name']),
            ), false, 120, true)
            ->willReturnOnConsecutiveCalls('test up', 'test down');

        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('2345', 'test up', 'test down')
            ->willReturn('path2');

        self::assertSame('path2', $this->migrationDiffGenerator->generate('2345', null, false, 120, true, true));
    }

    protected function setUp(): void
    {
        $this->dbalConfiguration      = $this->createMock(DBALConfiguration::class);
        $this->schemaManager          = $this->createMock(AbstractSchemaManager::class);
        $this->schemaProvider         = $this->createMock(SchemaProvider::class);
        $this->platform               = $this->createMock(AbstractPlatform::class);
        $this->migrationGenerator     = $this->createMock(Generator::class);
        $this->migrationSqlGenerator  = $this->createMock(SqlGenerator::class);
        $this->emptySchemaProvider    = $this->createMock(SchemaProvider::class);
        $this->migrationDiffGenerator = new DiffGenerator(
            $this->dbalConfiguration,
            $this->schemaManager,
            $this->schemaProvider,
            $this->platform,
            $this->migrationGenerator,
            $this->migrationSqlGenerator,
            $this->emptySchemaProvider,
        );
    }

    private function mockComparator(SchemaDiff $schemaDiff): Comparator
    {
        $comparator = new class (self::createStub(AbstractPlatform::class)) extends Comparator {
            public static SchemaDiff $schemaDiff;

            public function compareSchemas(Schema $oldSchema, Schema $newSchema): SchemaDiff
            {
                return self::$schemaDiff;
            }
        };

        $comparator::$schemaDiff = $schemaDiff;

        return $comparator;
    }
}
