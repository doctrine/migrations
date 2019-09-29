<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use DateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Generator\FileBuilder;
use Doctrine\Migrations\Version\Direction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileBuilderTest extends TestCase
{
    /** @var AbstractPlatform|MockObject */
    private $platform;

    /** @var FileBuilder */
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

        $direction = Direction::UP;

        $now = new DateTime('2018-09-01');

        $this->platform->expects(self::any())
            ->method('getCurrentTimestampSQL')
            ->willReturn('CURRENT_TIMESTAMP');

        $expected = <<<'FILE'
-- Doctrine Migration File Generated on 2018-09-01 00:00:00

-- Version 1
SELECT 1;
SELECT 2;

-- Version 2
SELECT 3;
SELECT 4;

-- Version 3
SELECT 5;
SELECT 6;

FILE;

        $migrationFile = $this->migrationFileBuilder->buildMigrationFile($queriesByVersion, $direction, $now);

        self::assertSame($expected, $migrationFile);
    }

    protected function setUp() : void
    {
        $this->platform = $this->createMock(AbstractPlatform::class);

        $this->migrationFileBuilder = new FileBuilder();
    }
}
