<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Generator\DiffGenerator;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Provider\SchemaProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DiffGeneratorTest extends TestCase
{
    /** @var DBALConfiguration|MockObject */
    private $dbalConfiguration;

    /** @var AbstractSchemaManager|MockObject */
    private $schemaManager;

    /** @var SchemaProvider|MockObject */
    private $schemaProvider;

    /** @var AbstractPlatform|MockObject */
    private $platform;

    /** @var Generator|MockObject */
    private $migrationGenerator;

    /** @var SqlGenerator|MockObject */
    private $migrationSqlGenerator;

    /** @var DiffGenerator */
    private $migrationDiffGenerator;

    public function testGenerate() : void
    {
        $fromSchema = $this->createMock(Schema::class);
        $toSchema   = $this->createMock(Schema::class);

        $this->dbalConfiguration->expects(self::once())
            ->method('setSchemaAssetsFilter');

        $this->dbalConfiguration->expects(self::once())
            ->method('getSchemaAssetsFilter')
            ->willReturn(
                static function ($name) {
                    return $name === 'table_name1';
                }
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

        $toSchema->expects(self::once())
            ->method('getTables')
            ->willReturn([$table1, $table2, $table3]);

        $this->schemaManager->expects(self::once())
            ->method('createSchema')
            ->willReturn($fromSchema);

        $this->schemaProvider->expects(self::once())
            ->method('createSchema')
            ->willReturn($toSchema);

        $toSchema->expects(self::at(1))
            ->method('dropTable')
            ->with('schema.table_name2');

        $toSchema->expects(self::at(2))
            ->method('dropTable')
            ->with('schema.table_name3');

        $fromSchema->expects(self::once())
            ->method('getMigrateToSql')
            ->with($toSchema, $this->platform)
            ->willReturn(['UPDATE table SET value = 2']);

        $fromSchema->expects(self::once())
            ->method('getMigrateFromSql')
            ->with($toSchema, $this->platform)
            ->willReturn(['UPDATE table SET value = 1']);

        $this->migrationSqlGenerator->expects(self::at(0))
            ->method('generate')
            ->with(['UPDATE table SET value = 2'], true, 80)
            ->willReturn('test1');

        $this->migrationSqlGenerator->expects(self::at(1))
            ->method('generate')
            ->with(['UPDATE table SET value = 1'], true, 80)
            ->willReturn('test2');

        $this->migrationGenerator->expects(self::once())
            ->method('generateMigration')
            ->with('1234', 'test1', 'test2')
            ->willReturn('path');

        self::assertSame('path', $this->migrationDiffGenerator->generate('1234', '/table_name1/', true, 80));
    }

    protected function setUp() : void
    {
        $this->dbalConfiguration      = $this->createMock(DBALConfiguration::class);
        $this->schemaManager          = $this->createMock(AbstractSchemaManager::class);
        $this->schemaProvider         = $this->createMock(SchemaProvider::class);
        $this->platform               = $this->createMock(AbstractPlatform::class);
        $this->migrationGenerator     = $this->createMock(Generator::class);
        $this->migrationSqlGenerator  = $this->createMock(SqlGenerator::class);
        $this->migrationDiffGenerator = new DiffGenerator(
            $this->dbalConfiguration,
            $this->schemaManager,
            $this->schemaProvider,
            $this->platform,
            $this->migrationGenerator,
            $this->migrationSqlGenerator
        );
    }
}
