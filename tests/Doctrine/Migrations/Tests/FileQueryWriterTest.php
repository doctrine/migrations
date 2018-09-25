<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\FileQueryWriter;
use Doctrine\Migrations\Generator\FileBuilder;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Version\Direction;
use function file_get_contents;
use function sprintf;
use function unlink;

final class FileQueryWriterTest extends MigrationTestCase
{
    private const TABLE_NAME              = 'migration_versions';
    private const COLUMN_NAME             = 'version';
    private const EXECUTED_AT_COLUMN_NAME = 'executedAt';
    private const UP_QUERY                = 'INSERT INTO %s (%s, %s) VALUES (\'1\', CURRENT_TIMESTAMP)';
    private const DOWN_QUERY              = 'DELETE FROM %s WHERE %s = \'1\'';

    /**
     * @param string[][] $queries
     *
     * @dataProvider writeProvider
     */
    public function testWrite(
        string $path,
        string $direction,
        array $queries,
        OutputWriter $outputWriter
    ) : void {
        $platform = $this->createMock(AbstractPlatform::class);

        $migrationFileBuilder = new FileBuilder(
            $platform,
            self::TABLE_NAME,
            self::COLUMN_NAME,
            self::EXECUTED_AT_COLUMN_NAME
        );

        $writer = new FileQueryWriter(
            $outputWriter,
            $migrationFileBuilder
        );

        $platform->expects($this->any())
            ->method('getCurrentTimestampSQL')
            ->willReturn('CURRENT_TIMESTAMP');

        self::assertTrue($writer->write($path, $direction, $queries));

        $files = $this->getSqlFilesList($path);

        self::assertCount(1, $files);

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            unlink($file);

            if ($direction === Direction::UP) {
                $expectedQuery = self::UP_QUERY;

                $expectedSql = sprintf(
                    $expectedQuery,
                    self::TABLE_NAME,
                    self::COLUMN_NAME,
                    self::EXECUTED_AT_COLUMN_NAME
                );
            } else {
                $expectedQuery = self::DOWN_QUERY;

                $expectedSql = sprintf(
                    $expectedQuery,
                    self::TABLE_NAME,
                    self::COLUMN_NAME
                );
            }

            self::assertNotEmpty($contents);
            self::assertContains($expectedSql, $contents);
        }
    }

    /** @return string[][] */
    public function writeProvider() : array
    {
        $outputWriter = $this->createMock(OutputWriter::class);

        $outputWriter->expects($this->atLeastOnce())
            ->method('write')
            ->with($this->isType('string'));

        return [
            [__DIR__, Direction::UP, ['1' => ['SHOW DATABASES']], $outputWriter],
            [__DIR__, Direction::DOWN, ['1' => ['SHOW DATABASES']], $outputWriter],
        ];
    }
}
