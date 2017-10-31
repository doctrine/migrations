<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\FileQueryWriter;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Version;

final class FileQueryWriterTest extends MigrationTestCase
{
    private const COLUMN_NAME = 'columnName';
    private const TABLE_NAME  = 'tableName';
    private const UP_QUERY    = 'INSERT INTO %s (%s) VALUES (\'1\')';
    private const DOWN_QUERY  = 'DELETE FROM %s WHERE %s = \'1\'';

    /**
     * @dataProvider writeProvider
     */
    public function testWrite(string $path, string $direction, array $queries, ?OutputWriter $outputWriter) : void
    {
        $writer = new FileQueryWriter(self::COLUMN_NAME, self::TABLE_NAME, $outputWriter);

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

    public function writeProvider() : array
    {
        $outputWriter = $this->createMock(OutputWriter::class);

        $outputWriter->expects($this->atLeastOnce())
                     ->method('write')
                     ->with($this->isType('string'));

        return [
            [__DIR__, Version::DIRECTION_UP, [1 => ['SHOW DATABASES']], $outputWriter],
            [__DIR__, Version::DIRECTION_DOWN, [1 => ['SHOW DATABASES']], $outputWriter],
            [__DIR__, Version::DIRECTION_UP, [1 => ['SHOW DATABASES']], null],
            [__DIR__, Version::DIRECTION_DOWN, [1 => ['SHOW DATABASES']], null],
        ];
    }
}
