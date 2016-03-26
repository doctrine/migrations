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

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\SqlFileWriter;
use Doctrine\DBAL\Migrations\Version;
use \Mockery as m;

class SqlFileWriterTest extends MigrationTestCase
{

    /** @var \Mockery\Mock */
    protected $ow;

    protected function setUp()
    {
        $this->ow = m::mock(OutputWriter::class);
    }

    public function testGoodConstructor()
    {
        $instance = new SqlFileWriter('version', 'test', '/tmp', $this->ow);
        $this->assertInstanceOf(SqlFileWriter::class, $instance);
    }

    public function testConstructorEmptyColumnName()
    {
        $expectedException = class_exists('Doctrine\DBAL\Exception\InvalidArgumentException') ?
            InvalidArgumentException::class :
            DBALException::class;
        $this->setExpectedException($expectedException);
        $instance = new SqlFileWriter('', 'test', '/tmp', $this->ow);
        $this->assertInstanceOf(SqlFileWriter::class, $instance);
    }

    public function testConstructorEmptyTableName()
    {
        $expectedException = class_exists('Doctrine\DBAL\Exception\InvalidArgumentException') ?
            InvalidArgumentException::class :
            DBALException::class;
        $this->setExpectedException($expectedException);
        $instance = new SqlFileWriter('version', '', '/tmp', $this->ow);
        $this->assertInstanceOf(SqlFileWriter::class, $instance);
    }

    public function testConstructorEmptyDestPath()
    {
        $expectedException = class_exists('Doctrine\DBAL\Exception\InvalidArgumentException') ?
            InvalidArgumentException::class :
            DBALException::class;
        $this->setExpectedException($expectedException);
        $instance = new SqlFileWriter('test', '', $this->ow);
        $this->assertInstanceOf(SqlFileWriter::class, $instance);
    }

    /**
     * @param $path
     * @param $direction
     * @param array $queries
     * @param $withOw
     *
     * @dataProvider writeProvider
     */
    public function testWrite($path, $direction, array $queries, $withOw)
    {
        $columnName = 'columnName';
        $tableName = 'tableName';
        if ($withOw) {
            $instance = new SqlFileWriter($columnName, $tableName, $path, $this->ow);
            $this->ow->shouldReceive('write')->with(m::type('string'))->once();
        } else {
            $instance = new SqlFileWriter($columnName, $tableName, $path);
        }
        $instance->write($queries, $direction);

        // file content tests & cleanup
        $files = $this->getSqlFilesList($path);

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $this->assertNotEmpty($contents);
            if ($direction == Version::DIRECTION_UP) {
                $this->assertContains("INSERT INTO $tableName ($columnName) VALUES ('1');", $contents);
            } else {
                $this->assertContains("DELETE FROM $tableName WHERE $columnName = '1'", $contents);
            }
            unlink($file);
        }
    }

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

