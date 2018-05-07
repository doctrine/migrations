<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\FileQueryWriter;
use Doctrine\Migrations\MigrationFileBuilder;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Version;
use function file_get_contents;
use function sprintf;
use function unlink;

final class FileQueryWriterTest extends MigrationTestCase
{
    private const COLUMN_NAME = 'columnName';
    private const TABLE_NAME  = 'tableName';
    private const UP_QUERY    = 'INSERT INTO %s (%s) VALUES (\'1\')';
    private const DOWN_QUERY  = 'DELETE FROM %s WHERE %s = \'1\'';

    /**
     * @dataProvider writeProvider
     *
     * @param string[][] $queries
     */
    public function testWrite(
        string $path,
        string $direction,
        array $queries,
        OutputWriter $outputWriter
    ) : void {
        $migrationFileBuilder = new MigrationFileBuilder(
            self::TABLE_NAME,
            self::COLUMN_NAME
        );

        $writer = new FileQueryWriter(
            $outputWriter,
            $migrationFileBuilder
        );

        self::assertTrue($writer->write($path, $direction, $queries));

        $files = $this->getSqlFilesList($path);

        self::assertCount(1, $files);

        foreach ($files as $file) {
            $contents      = file_get_contents($file);
            $expectedQuery = $direction === Version::DIRECTION_UP ? self::UP_QUERY : self::DOWN_QUERY;

            self::assertNotEmpty($contents);
            self::assertContains(sprintf($expectedQuery, self::TABLE_NAME, self::COLUMN_NAME), $contents);

            unlink($file);
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
            [__DIR__, Version::DIRECTION_UP, ['1' => ['SHOW DATABASES']], $outputWriter],
            [__DIR__, Version::DIRECTION_DOWN, ['1' => ['SHOW DATABASES']], $outputWriter],
        ];
    }
}
