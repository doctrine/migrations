<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use DateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Generator\ConcatenationFileBuilder;
use Doctrine\Migrations\Generator\FileBuilder;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Version\Direction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConcatenationFileBuilderTest extends TestCase
{
    /** @var AbstractPlatform&MockObject */
    private AbstractPlatform $platform;

    private FileBuilder $migrationFileBuilder;

    public function testBuildMigrationFile(): void
    {
        $queriesByVersion = [
            'foo' => [
                new Query('SELECT 1'),
                new Query('SELECT 2'),
            ],
            'bar' => [
                new Query('SELECT 3'),
                new Query('SELECT 4'),
            ],
            'baz' => [
                new Query('SELECT 5'),
                new Query('SELECT 6'),
            ],
        ];

        $direction = Direction::UP;

        $now = new DateTime('2018-09-01');

        $this->platform->expects(self::any())
            ->method('getCurrentTimestampSQL')
            ->willReturn('CURRENT_TIMESTAMP');

        $expected = <<<'FILE'
-- Doctrine Migration File Generated on 2018-09-01 00:00:00

-- Version foo
SELECT 1;
SELECT 2;

-- Version bar
SELECT 3;
SELECT 4;

-- Version baz
SELECT 5;
SELECT 6;

FILE;

        $migrationFile = $this->migrationFileBuilder->buildMigrationFile($queriesByVersion, $direction, $now);

        self::assertSame($expected, $migrationFile);
    }

    protected function setUp(): void
    {
        $this->platform = $this->createMock(AbstractPlatform::class);

        $this->migrationFileBuilder = new ConcatenationFileBuilder();
    }
}
