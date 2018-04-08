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

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\QueryWriter;
use Doctrine\DBAL\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\Constraint\RegularExpression;
use Symfony\Component\Console\Output\StreamOutput;

require_once __DIR__ . '/realpath.php';

class MigrationTest extends MigrationTestCase
{
    /** @var Connection */
    private $conn;

    /** @var Configuration */
    private $config;

    /** @var StreamOutput|null */
    protected $output;

    protected function setUp()
    {
        $this->conn   = $this->getSqliteConnection();
        $this->config = new Configuration($this->conn);
        $this->config->setMigrationsDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Stub/migration-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
    }

    public function testMigrateToUnknownVersionThrowsException()
    {
        $migration = new Migration($this->config);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Could not find migration version 1234');

        $migration->migrate('1234');
    }

    public function testMigrateWithNoMigrationsThrowsException()
    {
        $migration = new Migration($this->config);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Could not find any migrations to execute.');

        $migration->migrate();
    }

    public function testMigrateWithNoMigrationsDontThrowsExceptionIfContiniousIntegrationOption()
    {
        $messages = [];
        $output   = new OutputWriter(function ($msg) use (&$messages) {
            $messages[] = $msg;
        });
        $this->config->setOutputWriter($output);
        $migration = new Migration($this->config);

        $migration->setNoMigrationException(true);
        $migration->migrate();

        self::assertCount(2, $messages, 'should output header and no migrations message');
        self::assertContains('No migrations', $messages[1]);
    }

    /**
     * @dataProvider getSqlProvider
     */
    public function testGetSql($to)
    {
        /** @var Migration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Migration::class)
                          ->disableOriginalConstructor()
                          ->setMethods(['migrate'])
                          ->getMock();

        $expected = 'something';

        $migration->expects($this->once())
                  ->method('migrate')
                  ->with($to, true)
                  ->willReturn($expected);

        $result = $migration->getSql($to);

        self::assertEquals($expected, $result);
    }

    /**
     * Data provider for ::testGetSql()
     * @return mixed[][]
     */
    public function getSqlProvider()
    {
        return [
            [null],
            ['test'],
        ];
    }

    /**
     * @param string[] $getSqlReturn
     *
     * @dataProvider writeSqlFileProvider
     */
    public function testWriteSqlFile($path, $from, $to, array $getSqlReturn)
    {
        $queryWriter  = $this->createMock(QueryWriter::class);
        $outputWriter = $this->createMock(OutputWriter::class);

        $queryWriter->method('write')
                    ->with($path, new RegularExpression('/(up|down)/'), $getSqlReturn)
                    ->willReturn(true);

        $outputWriter->expects($this->atLeastOnce())
                     ->method('write');

        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $config = $this->getMockBuilder(Configuration::class)
                          ->disableOriginalConstructor()
                          ->setMethods(['getCurrentVersion', 'getOutputWriter', 'getLatestVersion', 'getQueryWriter'])
                          ->getMock();

        $config->method('getCurrentVersion')
               ->willReturn($from);

        $config->method('getOutputWriter')
               ->willReturn($outputWriter);

        $config->method('getQueryWriter')
               ->willReturn($queryWriter);

        if ($to === null) { // this will always just test the "up" direction
            $config->method('getLatestVersion')
                   ->willReturn($from + 1);
        }

        /** @var Migration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Migration::class)
                          ->setConstructorArgs([$config])
                          ->setMethods(['getSql'])
                          ->getMock();

        $migration->expects($this->once())
                  ->method('getSql')
                  ->with($to)
                  ->willReturn($getSqlReturn);

        self::assertTrue($migration->writeSqlFile($path, $to));
    }

    /**
     * @return mixed[][]
     */
    public function writeSqlFileProvider()
    {
        return [
            [__DIR__, 0, 1, ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, 0, null, ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, 1, 1, ['1' => ['SHOW DATABASES;']]], // up (same)
            [__DIR__, 1, 0, ['1' => ['SHOW DATABASES;']]], // down
            [__DIR__ . '/tmpfile.sql', 0, 1, ['1' => ['SHOW DATABASES']]], // tests something actually got written
        ];
    }

    public function testWriteSqlFileShouldUseStandardCommentMarkerInSql()
    {
        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $config = $this->getMockBuilder(Configuration::class)
                       ->disableOriginalConstructor()
                       ->setMethods(['getCurrentVersion', 'getOutputWriter', 'getLatestVersion'])
                       ->getMock();

        $config->method('getCurrentVersion')
               ->willReturn(0);

        $config->method('getOutputWriter')
               ->willReturn($this->getOutputWriter());

        /** @var Migration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Migration::class)
                          ->setConstructorArgs([$config])
                          ->setMethods(['getSql'])
                          ->getMock();

        $migration->method('getSql')
                  ->willReturn(['1' => ['SHOW DATABASES']]);

        $sqlFilesDir = vfsStream::setup('sql_files_dir');
        $migration->writeSqlFile(vfsStream::url('sql_files_dir'), 1);

        self::assertRegExp('/^\s*-- Migrating from 0 to 1/m', $this->getOutputStreamContent($this->output));

        /** @var vfsStreamFile $sqlMigrationFile */
        $sqlMigrationFile = current($sqlFilesDir->getChildren());
        self::assertInstanceOf(vfsStreamFile::class, $sqlMigrationFile);
        self::assertRegExp('/^\s*-- Doctrine Migration File Generated on/m', $sqlMigrationFile->getContent());
        self::assertRegExp('/^\s*-- Version 1/m', $sqlMigrationFile->getContent());
        self::assertNotRegExp('/^\s*#/m', $sqlMigrationFile->getContent());
    }

    public function testMigrateWithMigrationsAndAddTheCurrentVersionOutputsANoMigrationsMessage()
    {
        $messages = [];
        $output   = new OutputWriter(function ($msg) use (&$messages) {
            $messages[] = $msg;
        });
        $this->config->setOutputWriter($output);
        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrateNotTouchingTheSchema::class);
        $this->config->createMigrationTable();
        $this->conn->insert($this->config->getMigrationsTableName(), ['version' => '20160707000000']);

        $migration = new Migration($this->config);

        $migration->migrate();

        self::assertCount(1, $messages, 'should output the no migrations message');
        self::assertContains('No migrations', $messages[0]);
    }

    public function testMigrateReturnsFalseWhenTheConfirmationIsDeclined()
    {
        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrateNotTouchingTheSchema::class);
        $this->config->createMigrationTable();
        $called    = false;
        $migration = new Migration($this->config);

        $result = $migration->migrate(null, false, false, function () use (&$called) {
            $called = true;
            return false;
        });

        self::assertEmpty($result);
        self::assertTrue($called, 'should have called the confirmation callback');
    }

    public function testMigrateWithDryRunDoesNotCallTheConfirmationCallback()
    {
        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrateNotTouchingTheSchema::class);
        $this->config->createMigrationTable();
        $called    = false;
        $migration = new Migration($this->config);

        $result = $migration->migrate(null, true, false, function () use (&$called) {
            $called = true;
            return false;
        });

        self::assertFalse($called);
        self::assertEquals(['20160707000000' => ['SELECT 1']], $result);
    }
}
