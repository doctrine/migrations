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

use Doctrine\DBAL\Migrations\SqlFileWriter;
use \Mockery as m;

class SqlFileWriterTest extends MigrationTestCase
{

    /** @var \Mockery\Mock */
    protected $ow;

    public function setUp()
    {
        $this->ow = m::mock('Doctrine\DBAL\Migrations\OutputWriter');
    }

    public function testGoodConstructor()
    {
        $instance = new SqlFileWriter('test', '/tmp', $this->ow);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\SqlFileWriter', $instance);
    }

    public function testConstructorEmptyTableName()
    {
        $expectedException = class_exists('Doctrine\DBAL\Exception\InvalidArgumentException') ?
            'Doctrine\DBAL\Exception\InvalidArgumentException' :
            'Doctrine\DBAL\DBALException';
        $this->setExpectedException($expectedException);
        $instance = new SqlFileWriter('', '/tmp', $this->ow);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\SqlFileWriter', $instance);
    }

    public function testConstructorEmptyDestPath()
    {
        $expectedException = class_exists('Doctrine\DBAL\Exception\InvalidArgumentException') ?
            'Doctrine\DBAL\Exception\InvalidArgumentException' :
            'Doctrine\DBAL\DBALException';
        $this->setExpectedException($expectedException);
        $instance = new SqlFileWriter('test', '', $this->ow);
        $this->assertInstanceOf('Doctrine\DBAL\Migrations\SqlFileWriter', $instance);
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
        if ($withOw) {
            $instance = new SqlFileWriter('test', $path, $this->ow);
            $this->ow->shouldReceive('write')->with(m::type('string'))->once();
        } else {
            $instance = new SqlFileWriter('test', $path);
        }
        $instance->write($queries, $direction);

        // file content tests & cleanup
        $files = [];
        if (is_dir($path)) {
            $files = glob(realpath($path) . '/*.sql');
        } elseif(is_file($path)) {
            $files = [$path];
        }
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $this->assertNotEmpty($contents);
            unlink($file);
        }
    }

    public function writeProvider()
    {
        return [
            [__DIR__, 'up', [1 => ['SHOW DATABASES']], true],
            [__DIR__, 'up', [1 => ['SHOW DATABASES']], false],
            [__DIR__, 'down', [1 => ['SHOW DATABASES']], true],
            [__DIR__, 'down', [1 => ['SHOW DATABASES']], false],
        ];
    }

}

