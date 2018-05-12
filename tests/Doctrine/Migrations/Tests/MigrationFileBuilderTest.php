<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use DateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\MigrationFileBuilder;
use Doctrine\Migrations\VersionDirection;
use PHPUnit\Framework\TestCase;

class MigrationFileBuilderTest extends TestCase
{
    /** @var AbstractPlatform */
    private $platform;

    /** @var MigrationFileBuilder */
    private $migrationFileBuilder;

    public function testBuildMigrationFile() : void
    {
        $queriesByVersion = [
            '1' => [
                'SELECT 1',
                'SELECT 2',
            ],
            '2' => [
                'SELECT 3',
                'SELECT 4',
            ],
            '3' => [
                'SELECT 5',
                'SELECT 6',
            ],
        ];

        $direction = VersionDirection::UP;

        $now = new DateTime('2018-09-01');

        $this->platform->expects($this->any())
            ->method('getCurrentTimestampSQL')
            ->willReturn('CURRENT_TIMESTAMP');

        $expected = <<<'FILE'
-- Doctrine Migration File Generated on 2018-09-01 00:00:00

-- Version 1
SELECT 1;
SELECT 2;
INSERT INTO table_name (column_name, executed_at) VALUES ('1', CURRENT_TIMESTAMP);

-- Version 2
SELECT 3;
SELECT 4;
INSERT INTO table_name (column_name, executed_at) VALUES ('2', CURRENT_TIMESTAMP);

-- Version 3
SELECT 5;
SELECT 6;
INSERT INTO table_name (column_name, executed_at) VALUES ('3', CURRENT_TIMESTAMP);

FILE;

        $migrationFile = $this->migrationFileBuilder->buildMigrationFile($queriesByVersion, $direction, $now);

        self::assertEquals($expected, $migrationFile);
    }

    protected function setUp() : void
    {
        $this->platform = $this->createMock(AbstractPlatform::class);

        $this->migrationFileBuilder = new MigrationFileBuilder(
            $this->platform,
            'table_name',
            'column_name',
            'executed_at'
        );
    }
}
