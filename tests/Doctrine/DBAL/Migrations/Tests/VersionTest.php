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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\QueryWriter;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDryRunNamedParams;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDryRunQuestionMarkParams;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDryRunTypes;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDryRunWithoutParams;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDummy;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDummyDescription;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDummyException;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionOutputSql;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionOutputSqlWithParam;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionOutputSqlWithParamAndType;
use Doctrine\DBAL\Migrations\Version;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

require_once __DIR__ . '/realpath.php';

class VersionTest extends MigrationTestCase
{
    private $config;

    protected $outputWriter;

    protected $output;

    public function testConstants()
    {
        self::assertSame('up', Version::DIRECTION_UP);
        self::assertSame('down', Version::DIRECTION_DOWN);
    }

    /**
     * Create simple migration
     */
    public function testCreateVersion()
    {
        $version         = new Version(
            new Configuration($this->getSqliteConnection()),
            $versionName = '003',
            VersionDummy::class
        );
        self::assertEquals($versionName, $version->getVersion());
    }

    public function testShowSqlStatementsParameters()
    {
        $outputWriter  = $this->getOutputWriter();
        $configuration = new Configuration($this->getSqliteConnection(), $outputWriter);
        $configuration->setMigrationsNamespace('sdfq');
        $configuration->setMigrationsDirectory('.');
        $version = new Version($configuration, '0004', VersionOutputSqlWithParam::class);
        $version->getMigration()->setParam([
            0 => 456,
            1 => 'tralala',
            2 => 456,
        ]);
        $version->execute(Version::DIRECTION_UP);
        $this->assertContains('(456, tralala, 456)', $this->getOutputStreamContent($this->output));
    }

    public function testShowSqlStatementsParametersWithTypes()
    {
        $outputWriter  = $this->getOutputWriter();
        $configuration = new Configuration($this->getSqliteConnection(), $outputWriter);
        $configuration->setMigrationsNamespace('sdfq');
        $configuration->setMigrationsDirectory('.');
        $version = new Version($configuration, '0004', VersionOutputSqlWithParamAndType::class);
        $version->getMigration()->setParam([
            0 => [
                456,
                3,
                456,
            ],
        ]);
        $version->getMigration()->setType([Connection::PARAM_INT_ARRAY]);
        $version->execute(Version::DIRECTION_UP, true);
        $this->assertContains('(456, 3, 456)', $this->getOutputStreamContent($this->output));
    }

    /**
     * Create migration with description
     */
    public function testCreateVersionWithCustomName()
    {
        $versionName        = '003';
        $versionDescription = 'My super migration';
        $version            = new Version(
            new Configuration($this->getSqliteConnection()),
            $versionName,
            VersionDummyDescription::class
        );
        self::assertEquals($versionName, $version->getVersion());
        self::assertEquals($versionDescription, $version->getMigration()->getDescription());
    }

    /**
     * Test outputQueryTime on all queries
     */
    public function testOutputQueryTimeAllQueries()
    {
        $outputWriterMock = $this->createMock(OutputWriter::class);
        $outputWriterMock->expects($this->once())->method('write');
        $configuration     = new Configuration($this->getSqliteConnection(), $outputWriterMock);
        $reflectionVersion = new \ReflectionClass(Version::class);
        $method            = $reflectionVersion->getMethod('outputQueryTime');
        $method->setAccessible(true);

        $version         = new Version(
            $configuration,
            $versionName = '003',
            VersionDummy::class
        );
        self::assertNull($method->invoke($version, 0, true));
    }

    /**
     * Test outputQueryTime not on all queries
     */
    public function testOutputQueryTimeNotAllQueries()
    {
        $outputWriterMock = $this->createMock(OutputWriter::class);
        $outputWriterMock->expects($this->exactly(0))->method('write');
        $configuration     = new Configuration($this->getSqliteConnection(), $outputWriterMock);
        $reflectionVersion = new \ReflectionClass(Version::class);
        $method            = $reflectionVersion->getMethod('outputQueryTime');
        $method->setAccessible(true);

        $version         = new Version(
            $configuration,
            $versionName = '003',
            VersionDummy::class
        );
        self::assertNull($method->invoke($version, 0, false));
    }

    /**
     * Test outputQueryTime not on all queries
     * @dataProvider stateProvider
     */
    public function testGetExecutionState($state)
    {
        $configuration     = new Configuration($this->getSqliteConnection());
        $version           = new Version(
            $configuration,
            $versionName   = '003',
            VersionDummy::class
        );
        $reflectionVersion = new \ReflectionClass(Version::class);
        $stateProperty     = $reflectionVersion->getProperty('state');
        $stateProperty->setAccessible(true);
        $stateProperty->setValue($version, $state);
        self::assertNotEmpty($version->getExecutionState());
    }

    /**
     * Provides states
     * @return array
     */
    public function stateProvider()
    {
        return [
            [Version::STATE_NONE],
            [Version::STATE_EXEC],
            [Version::STATE_POST],
            [Version::STATE_PRE],
            [-1],
        ];
    }

    /**
     * Test add sql
     */
    public function testAddSql()
    {
        $configuration   = new Configuration($this->getSqliteConnection());
        $version         = new Version(
            $configuration,
            $versionName = '003',
            VersionDummy::class
        );
        self::assertNull($version->addSql('SELECT * FROM foo'));
        self::assertNull($version->addSql(['SELECT * FROM foo']));
        self::assertNull($version->addSql(['SELECT * FROM foo WHERE id = ?'], [1]));
        self::assertNull($version->addSql(['SELECT * FROM foo WHERE id = ?'], [1], [\PDO::PARAM_INT]));
    }

    /**
     * @param $path
     * @param $to
     * @param $getSqlReturn
     *
     * @dataProvider writeSqlFileProvider
     */
    public function testWriteSqlFile($path, $direction, $getSqlReturn)
    {
        $version = 1;

        $connection   = $this->getSqliteConnection();
        $outputWriter = $this->createMock(OutputWriter::class);
        $queryWriter  = $this->createMock(QueryWriter::class);

        $outputWriter->expects($this->atLeastOnce())
                     ->method('write');

        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMockBuilder(Configuration::class)
                       ->disableOriginalConstructor()
                       ->setMethods(['getConnection', 'getOutputWriter', 'getQueryWriter'])
                       ->getMock();

        $config->method('getOutputWriter')
               ->willReturn($outputWriter);

        $config->method('getConnection')
               ->willReturn($connection);

        $config->method('getQueryWriter')
               ->willReturn($queryWriter);

        /** @var Version|\PHPUnit_Framework_MockObject_MockObject $version */
        $version = $this->getMockBuilder(Version::class)
                          ->setConstructorArgs([$config, $version, 'stdClass'])
                          ->setMethods(['execute'])
                          ->getMock();

        $version->expects($this->once())
                ->method('execute')
                ->with($direction, true)
                ->willReturn($getSqlReturn);

        $queryWriter->method('write')
                    ->with($path, $direction, [$version->getVersion() => $getSqlReturn])
                    ->willReturn(true);

        self::assertTrue($version->writeSqlFile($path, $direction));
    }

    public function writeSqlFileProvider()
    {
        return [
            [__DIR__, 'up', ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, 'down', ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__ . '/tmpfile.sql', 'up', ['1' => ['SHOW DATABASES']]], // tests something actually got written
        ];
    }

    public function testWarningWhenNoSqlStatementIsOutputed()
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $version         = new Version(
            $this->config,
            $versionName = '003',
            VersionDummy::class
        );

        $version->execute('up');
        self::assertContains(
            'Migration 003 was executed but did not result in any SQL statements.',
            $this->getOutputStreamContent($this->output)
        );
    }

    public function testCatchExceptionDuringMigration()
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $version         = new Version(
            $this->config,
            $versionName = '004',
            VersionDummyException::class
        );

        try {
            $version->execute('up');
        } catch (\Exception $e) {
            self::assertContains(
                'Migration 004 failed during Execution. Error Super Exception',
                $this->getOutputStreamContent($this->output)
            );
        }
    }

    public function testReturnTheSql()
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $version         = new Version(
            $this->config,
            $versionName = '005',
            VersionOutputSql::class
        );

        self::assertContains('Select 1', $version->execute('up'));
        self::assertContains('Select 1', $version->execute('down'));
    }

    public function testReturnTheSqlWithParams()
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $version         = new Version(
            $this->config,
            $versionName = '006',
            VersionOutputSqlWithParam::class
        );

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('contains a prepared statement.');
        $version->writeSqlFile('tralala');
    }

    /**
     * @param $direction
     * @param $columnName
     * @param $tableName
     *
     * @dataProvider sqlWriteProvider
     */
    public function testWriteSqlWriteToTheCorrectColumnName($direction, $columnName, $tableName)
    {
        $connection    = $this->getSqliteConnection();
        $configuration = new Configuration($connection, $this->outputWriter);
        $configuration->setMigrationsColumnName($columnName);
        $configuration->setMigrationsTableName($tableName);

        $version         = new Version(
            $configuration,
            $versionName = '005',
            VersionOutputSql::class
        );
        $path            = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migrations';

        $version->writeSqlFile($path, $direction);

        $files = $this->getSqlFilesList($path);

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            self::assertNotEmpty($contents);
            if ($direction == Version::DIRECTION_UP) {
                self::assertContains("INSERT INTO $tableName ($columnName) VALUES ('$versionName');", $contents);
            } else {
                self::assertContains("DELETE FROM $tableName WHERE $columnName = '$versionName'", $contents);
            }
            unlink($file);
        }
    }

    public function sqlWriteProvider()
    {
        return [
            [Version::DIRECTION_UP, 'balalala', 'fkqsdmfjl'],
            [Version::DIRECTION_UP, 'fkqsdmfjl', 'balalala'],
            [Version::DIRECTION_DOWN, 'balalala', 'fkqsdmfjl'],
            [Version::DIRECTION_DOWN, 'fkqsdmfjl', 'balalala'],
        ];
    }

    public function testWriteSqlFileShouldUseStandardCommentMarkerInSql()
    {
        $version = 1;

        $connection = $this->getSqliteConnection();


        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $config = $this->getMockBuilder(Configuration::class)
                       ->disableOriginalConstructor()
                       ->setMethods(['getOutputWriter', 'getConnection'])
                       ->getMock();

        $config->method('getOutputWriter')
               ->willReturn($this->getOutputWriter());

        $config->method('getConnection')
               ->willReturn($connection);


        /** @var Version|\PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Version::class)
                          ->setConstructorArgs([$config, $version, 'stdClass'])
                          ->setMethods(['execute'])
                          ->getMock();

        $migration->method('execute')->willReturn(['SHOW DATABASES;']);

        $sqlFilesDir = vfsStream::setup('sql_files_dir');
        $migration->writeSqlFile(vfsStream::url('sql_files_dir'), Version::DIRECTION_UP);

        self::assertRegExp('/^\s*-- Version 1/m', $this->getOutputStreamContent($this->output));

        /** @var vfsStreamFile $sqlMigrationFile */
        $sqlMigrationFile = current($sqlFilesDir->getChildren());
        self::assertInstanceOf(vfsStreamFile::class, $sqlMigrationFile);
        self::assertNotRegExp('/^\s*#/m', $sqlMigrationFile->getContent());
    }

    public function testDryRunCausesSqlToBeOutputViaTheOutputWriter()
    {
        $messages = [];
        $ow       = new OutputWriter(function ($msg) use (&$messages) {
            $messages[] = trim($msg);
        });
        $config   = new Configuration($this->getSqliteConnection(), $ow);
        $version  = new Version(
            $config,
            '006',
            VersionDryRunWithoutParams::class
        );

        $version->execute(Version::DIRECTION_UP, true);

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('SELECT 1 WHERE 1', $messages[1]);
    }

    public function testDryRunWithQuestionMarkedParamsOutputsParamsWithSqlStatement()
    {
        $messages = [];
        $ow       = new OutputWriter(function ($msg) use (&$messages) {
            $messages[] = trim($msg);
        });
        $config   = new Configuration($this->getSqliteConnection(), $ow);
        $version  = new Version(
            $config,
            '006',
            VersionDryRunQuestionMarkParams::class
        );

        $version->execute(Version::DIRECTION_UP, true);

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('INSERT INTO test VALUES (?, ?)', $messages[1]);
        self::assertContains('with parameters (one, two)', $messages[1]);
    }

    public function testDryRunWithNamedParametersOutputsParamsAndNamesWithSqlStatement()
    {
        $messages = [];
        $ow       = new OutputWriter(function ($msg) use (&$messages) {
            $messages[] = trim($msg);
        });
        $config   = new Configuration($this->getSqliteConnection(), $ow);
        $version  = new Version(
            $config,
            '006',
            VersionDryRunNamedParams::class
        );

        $version->execute(Version::DIRECTION_UP, true);

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('INSERT INTO test VALUES (:one, :two)', $messages[1]);
        self::assertContains('with parameters (:one => one, :two => two)', $messages[1]);
    }

    public static function dryRunTypes()
    {
        return [
            'datetime' => [new \DateTime('2016-07-05 01:00:00'), 'datetime', '2016-07-05 01:00:00'],
            'array' => [['one' => 'two'], 'array', serialize(['one' => 'two'])],
            'doctrine_param' => [[1,2,3,4,5], Connection::PARAM_INT_ARRAY, '1, 2, 3, 4, 5'],
            'boolean' => [[true], '', 'true'],
        ];
    }

    /**
     * @dataProvider dryRunTypes
     */
    public function testDryRunWithParametersOfComplexTypesCorrectFormatsParameters($value, $type, $output)
    {
        $messages = [];
        $ow       = new OutputWriter(function ($msg) use (&$messages) {
            $messages[] = trim($msg);
        });
        $config   = new Configuration($this->getSqliteConnection(), $ow);
        $version  = new Version(
            $config,
            '006',
            VersionDryRunTypes::class
        );
        $version->getMigration()->setParam($value, $type);

        $version->execute(Version::DIRECTION_UP, true);

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('INSERT INTO test VALUES (?)', $messages[1]);
        self::assertContains(sprintf('with parameters (%s)', $output), $messages[1]);
    }
}
