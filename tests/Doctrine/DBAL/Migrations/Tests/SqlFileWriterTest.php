<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\SqlFileWriter;
use Doctrine\DBAL\Migrations\Version;
use PHPUnit\Framework\MockObject\MockObject;

class SqlFileWriterTest extends MigrationTestCase
{
    /** @var OutputWriter|MockObject */
    protected $ow;

    protected function setUp()
    {
        $this->ow = $this->createMock(OutputWriter::class);
    }

    public function testGoodConstructor()
    {
        $instance = new SqlFileWriter('version', 'test', '/tmp', $this->ow);
        self::assertInstanceOf(SqlFileWriter::class, $instance);
    }

    public function testConstructorEmptyColumnName()
    {
        $this->expectException(InvalidArgumentException::class);

        $instance = new SqlFileWriter('', 'test', '/tmp', $this->ow);
        self::assertInstanceOf(SqlFileWriter::class, $instance);
    }

    public function testConstructorEmptyTableName()
    {
        $this->expectException(InvalidArgumentException::class);

        $instance = new SqlFileWriter('version', '', '/tmp', $this->ow);
        self::assertInstanceOf(SqlFileWriter::class, $instance);
    }

    public function testConstructorEmptyDestPath()
    {
        $this->expectException(InvalidArgumentException::class);

        $instance = new SqlFileWriter('test', '', $this->ow);
        self::assertInstanceOf(SqlFileWriter::class, $instance);
    }

    /**
     * @param string[][] $queries
     *
     * @dataProvider writeProvider
     */
    public function testWrite($path, $direction, array $queries, $withOw)
    {
        $columnName = 'columnName';
        $tableName  = 'tableName';
        if ($withOw) {
            $instance = new SqlFileWriter($columnName, $tableName, $path, $this->ow);

            $this->ow->expects($this->once())
                     ->method('write')
                     ->with($this->isType('string'));
        } else {
            $instance = new SqlFileWriter($columnName, $tableName, $path);
        }
        $instance->write($queries, $direction);

        // file content tests & cleanup
        $files = $this->getSqlFilesList($path);

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            self::assertNotEmpty($contents);
            if ($direction === Version::DIRECTION_UP) {
                self::assertContains('INSERT INTO ' . $tableName . ' (' . $columnName . ") VALUES ('1');", $contents);
            } else {
                self::assertContains('DELETE FROM ' . $tableName . ' WHERE ' . $columnName . " = '1'", $contents);
            }
            unlink($file);
        }
    }

    /**
     * @return mixed[][]
     */
    public function writeProvider()
    {
        return [
            [__DIR__, Version::DIRECTION_UP, [1 => ['SHOW DATABASES']], true],
            [__DIR__, Version::DIRECTION_UP, [1 => ['SHOW DATABASES']], false],
            [__DIR__, Version::DIRECTION_DOWN, [1 => ['SHOW DATABASES']], true],
            [__DIR__, Version::DIRECTION_DOWN, [1 => ['SHOW DATABASES']], false],
        ];
    }
}
