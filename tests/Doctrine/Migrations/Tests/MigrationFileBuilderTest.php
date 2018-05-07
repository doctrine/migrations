<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use DateTime;
use Doctrine\Migrations\MigrationFileBuilder;
use Doctrine\Migrations\Version;
use PHPUnit\Framework\TestCase;

class MigrationFileBuilderTest extends TestCase
{
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

        $direction = Version::DIRECTION_UP;

        $now = new DateTime('2018-09-01');

        $expected = <<<'FILE'
-- Doctrine Migration File Generated on 2018-09-01 00:00:00

-- Version 1
SELECT 1;
SELECT 2;
INSERT INTO table_name (column_name) VALUES ('1');

-- Version 2
SELECT 3;
SELECT 4;
INSERT INTO table_name (column_name) VALUES ('2');

-- Version 3
SELECT 5;
SELECT 6;
INSERT INTO table_name (column_name) VALUES ('3');

FILE;

        $migrationFile = $this->migrationFileBuilder->buildMigrationFile($queriesByVersion, $direction, $now);

        self::assertEquals($expected, $migrationFile);
    }

    protected function setUp() : void
    {
        $this->migrationFileBuilder = new MigrationFileBuilder('table_name', 'column_name');
    }
}
