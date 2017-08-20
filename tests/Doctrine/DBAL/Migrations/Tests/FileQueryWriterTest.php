<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
