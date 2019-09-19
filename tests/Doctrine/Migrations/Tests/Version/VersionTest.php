<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\ExceptionVersionDummy;
use Doctrine\Migrations\Tests\Stub\VersionDryRunNamedParams;
use Doctrine\Migrations\Tests\Stub\VersionDryRunQuestionMarkParams;
use Doctrine\Migrations\Tests\Stub\VersionDryRunTypes;
use Doctrine\Migrations\Tests\Stub\VersionDryRunWithoutParams;
use Doctrine\Migrations\Tests\Stub\VersionDummy;
use Doctrine\Migrations\Tests\Stub\VersionDummyDescription;
use Doctrine\Migrations\Tests\Stub\VersionOutputSql;
use Doctrine\Migrations\Tests\Stub\VersionOutputSqlWithParam;
use Doctrine\Migrations\Tests\Stub\VersionOutputSqlWithParamAndType;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\ExecutorInterface;
use Doctrine\Migrations\Version\State;
use Doctrine\Migrations\Version\Version;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PDO;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionClass;
use stdClass;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use Throwable;
use const DIRECTORY_SEPARATOR;
use function current;
use function date;
use function file_get_contents;
use function serialize;
use function sprintf;
use function strtotime;
use function sys_get_temp_dir;
use function trim;
use function unlink;

class VersionTest extends MigrationTestCase
{
    public function setUp()
    {
        $this->markTestSkipped();
    }
    public function testConstants() : void
    {
        self::assertSame('up', Direction::UP);
        self::assertSame('down', Direction::DOWN);
    }

    public function testCreateVersion() : void
    {
        $versionName = '003';

        $configuration = $this->getSqliteConfiguration();

        $version = $this->createTestVersion(
            $configuration,
            $versionName,
            VersionDummy::class
        );

        self::assertSame($versionName, $version->getVersion());
    }

    public function testShowSqlStatementsParameters() : void
    {
        $outputWriter = $this->getOutputWriter();

        $configuration = $this->getSqliteConfiguration();
        $configuration->setOutputWriter($outputWriter);

        $version   = $this->createTestVersion($configuration, '0004', VersionOutputSqlWithParam::class);
        $migration = $version->getMigration();

        self::assertInstanceOf(VersionOutputSqlWithParam::class, $migration);

        $migration->setParam([
            0 => 456,
            1 => 'tralala',
            2 => 456,
        ]);

        $version->execute(Direction::UP);

        self::assertContains('([456], [tralala], [456])', $this->getOutputStreamContent($this->output));
    }

    public function testShowSqlStatementsParametersWithTypes() : void
    {
        $outputWriter = $this->getOutputWriter();

        $configuration = $this->getSqliteConfiguration();
        $configuration->setOutputWriter($outputWriter);

        $version   = $this->createTestVersion($configuration, '0004', VersionOutputSqlWithParamAndType::class);
        $migration = $version->getMigration();

        self::assertInstanceOf(VersionOutputSqlWithParamAndType::class, $migration);

        $migration->setParam([
            0 => [
                456,
                3,
                456,
            ],
        ]);

        $migration->setType([Connection::PARAM_INT_ARRAY]);

        $version->execute(Direction::UP, (new MigratorConfiguration())
            ->setDryRun(true));

        self::assertContains('([456, 3, 456])', $this->getOutputStreamContent($this->output));
    }

    public function testCreateVersionWithCustomName() : void
    {
        $versionName        = '003';
        $versionDescription = 'My super migration';

        $configuration = $this->getSqliteConfiguration();

        $version = $this->createTestVersion(
            $configuration,
            $versionName,
            VersionDummyDescription::class
        );

        self::assertSame($versionName, $version->getVersion());
        self::assertSame($versionDescription, $version->getMigration()->getDescription());
    }

    /** @dataProvider stateProvider */
    public function testGetExecutionState(string $state) : void
    {
        $configuration = $this->getSqliteConfiguration();

        $version = $this->createTestVersion(
            $configuration,
            '003',
            VersionDummy::class
        );

        $reflectionVersion = new ReflectionClass(Version::class);

        $stateProperty = $reflectionVersion->getProperty('state');
        $stateProperty->setAccessible(true);
        $stateProperty->setValue($version, $state);

        self::assertNotEmpty($version->getExecutionState());
    }

    /** @return mixed[][] */
    public function stateProvider() : array
    {
        return [
            [State::NONE],
            [State::EXEC],
            [State::POST],
            [State::PRE],
            [-1],
        ];
    }

    public function testAddSql() : void
    {
        $configuration = $this->getSqliteConfiguration();

        $versionExecutor = $this->createMock(ExecutorInterface::class);

        $versionExecutor->expects(self::once())
            ->method('addSql')
            ->with('SELECT * FROM foo WHERE id = ?', [1], [PDO::PARAM_INT]);

        $version = new Version($configuration, '003', VersionDummy::class, $versionExecutor);

        $version->addSql('SELECT * FROM foo WHERE id = ?', [1], [PDO::PARAM_INT]);
    }

    /**
     * @param string[] $getSqlReturn
     *
     * @dataProvider writeSqlFileProvider
     */
    public function testWriteSqlFile(string $path, string $direction, array $getSqlReturn) : void
    {
        $version = '1';

        $connection   = $this->getSqliteConnection();
        $outputWriter = $this->createMock(OutputWriter::class);
        $queryWriter  = $this->createMock(QueryWriter::class);

        $outputWriter->expects(self::atLeastOnce())
            ->method('write');

        /** @var Configuration|PHPUnit_Framework_MockObject_MockObject $config */
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

        /** @var Version|PHPUnit_Framework_MockObject_MockObject $version */
        $version = $this->getMockBuilder(Version::class)
            ->setConstructorArgs($this->getMockVersionConstructorArgs($config, $version, TestMigration::class))
            ->setMethods(['execute'])
            ->getMock();

        $versionExecutionResult = new ExecutionResult($getSqlReturn);

        $version->expects(self::once())
            ->method('execute')
            ->with($direction)
            ->willReturn($versionExecutionResult);

        $queryWriter->method('write')
            ->with($path, $direction, [$version->getVersion() => $getSqlReturn])
            ->willReturn(true);

        self::assertTrue($version->writeSqlFile($path, $direction));
    }

    /** @return mixed[][] */
    public function writeSqlFileProvider() : array
    {
        return [
            [__DIR__, 'up', ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, 'down', ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__ . '/tmpfile.sql', 'up', ['1' => ['SHOW DATABASES']]], // tests something actually got written
        ];
    }

    public function testWarningWhenNoSqlStatementIsOutputed() : void
    {
        $outputWriter = $this->getOutputWriter();

        $config = $this->getSqliteConfiguration();
        $config->setOutputWriter($outputWriter);

        $version = $this->createTestVersion(
            $config,
            '003',
            VersionDummy::class
        );

        $version->execute('up');

        self::assertContains(
            'Migration 003 was executed but did not result in any SQL statements.',
            $this->getOutputStreamContent($this->output)
        );
    }

    public function testCatchExceptionDuringMigration() : void
    {
        $outputWriter = $this->getOutputWriter();

        $config = $this->getSqliteConfiguration();
        $config->setOutputWriter($outputWriter);

        $version = $this->createTestVersion(
            $config,
            '004',
            ExceptionVersionDummy::class
        );

        try {
            $version->execute('up');
        } catch (Throwable $e) {
            self::assertContains(
                'Migration 004 failed during Execution. Error Super Exception',
                $this->getOutputStreamContent($this->output)
            );
        }
    }

    public function testReturnTheSql() : void
    {
        $config = $this->getSqliteConfiguration();

        $version = $this->createTestVersion(
            $config,
            '005',
            VersionOutputSql::class
        );

        self::assertContains('Select 1', $version->execute('up')->getSql());
        self::assertContains('Select 1', $version->execute('down')->getSql());
    }

    public function testReturnTheSqlWithParams() : void
    {
        $config = $this->getSqliteConfiguration();

        $version = $this->createTestVersion(
            $config,
            '006',
            VersionOutputSqlWithParam::class
        );

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('contains a prepared statement.');

        $version->writeSqlFile('tralala');
    }

    /** @dataProvider sqlWriteProvider */
    public function testWriteSqlWriteToTheCorrectColumnName(
        string $direction,
        string $tableName,
        string $columnName,
        string $executedAtColumnName
    ) : void {
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsTableName($tableName);
        $configuration->setMigrationsColumnName($columnName);
        $configuration->setMigrationsExecutedAtColumnName($executedAtColumnName);

        $versionName = '005';

        $version = $this->createTestVersion(
            $configuration,
            $versionName,
            VersionOutputSql::class
        );

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migrations';

        $version->writeSqlFile($path, $direction);

        $files = $this->getSqlFilesList($path);

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            self::assertNotEmpty($contents);

            if ($direction === Direction::UP) {
                $sql = sprintf(
                    "INSERT INTO %s (%s, %s) VALUES ('%s', CURRENT_TIMESTAMP);",
                    $tableName,
                    $columnName,
                    $executedAtColumnName,
                    $versionName
                );

                self::assertContains($sql, $contents);
            } else {
                $sql = sprintf(
                    "DELETE FROM %s WHERE %s = '%s'",
                    $tableName,
                    $columnName,
                    $versionName
                );

                self::assertContains($sql, $contents);
            }

            unlink($file);
        }
    }

    /** @return string[][] */
    public function sqlWriteProvider() : array
    {
        return [
            [Direction::UP, 'fkqsdmfjl', 'balalala', 'executed_at'],
            [Direction::UP, 'balalala', 'fkqsdmfjl', 'executedAt'],
            [Direction::DOWN, 'fkqsdmfjl', 'balalala', 'executed_at'],
            [Direction::DOWN, 'balalala', 'fkqsdmfjl', 'executedAt'],
        ];
    }

    public function testWriteSqlFileShouldUseStandardCommentMarkerInSql() : void
    {
        $version   = '1';
        $direction = Direction::UP;

        $connection = $this->getSqliteConnection();

        /** @var Configuration|PHPUnit_Framework_MockObject_MockObject $migration */
        $config = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getOutputWriter',
                'getConnection',
                'getQuotedMigrationsColumnName',
                'getQuotedMigrationsExecutedAtColumnName',
            ])
            ->getMock();

        $config->method('getOutputWriter')
            ->willReturn($this->getOutputWriter());

        $config->method('getConnection')
            ->willReturn($connection);

        $config->method('getQuotedMigrationsColumnName')
            ->willReturn('version');

        $config->method('getQuotedMigrationsExecutedAtColumnName')
            ->willReturn('executed_at');

        /** @var Version|PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Version::class)
            ->setConstructorArgs($this->getMockVersionConstructorArgs($config, $version, TestMigration::class))
            ->setMethods(['execute'])
            ->getMock();

        $versionExecutionResult = new ExecutionResult(['SHOW DATABASES;']);

        $migration->method('execute')
            ->with($direction)
            ->willReturn($versionExecutionResult);

        $sqlFilesDir = vfsStream::setup('sql_files_dir');
        $migration->writeSqlFile(vfsStream::url('sql_files_dir'), $direction);

        self::assertRegExp('/^\s*-- Version 1/m', $this->getOutputStreamContent($this->output));

        /** @var vfsStreamFile $sqlMigrationFile */
        $sqlMigrationFile = current($sqlFilesDir->getChildren());

        self::assertNotRegExp('/^\s*#/m', $sqlMigrationFile->getContent());
    }

    public function testDryRunCausesSqlToBeOutputViaTheOutputWriter() : void
    {
        $messages = [];

        $ow = new OutputWriter(static function ($msg) use (&$messages) : void {
            $messages[] = trim($msg);
        });

        $config = $this->getSqliteConfiguration();
        $config->setOutputWriter($ow);

        $version = $this->createTestVersion(
            $config,
            '006',
            VersionDryRunWithoutParams::class
        );

        $version->execute(Direction::UP, (new MigratorConfiguration())
            ->setDryRun(true));

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('SELECT 1 WHERE 1', $messages[1]);
    }

    public function testDryRunWithQuestionMarkedParamsOutputsParamsWithSqlStatement() : void
    {
        $messages = [];

        $ow = new OutputWriter(static function ($msg) use (&$messages) : void {
            $messages[] = trim($msg);
        });

        $config = $this->getSqliteConfiguration();
        $config->setOutputWriter($ow);

        $version = $this->createTestVersion(
            $config,
            '006',
            VersionDryRunQuestionMarkParams::class
        );

        $version->execute(Direction::UP, (new MigratorConfiguration())
            ->setDryRun(true));

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('INSERT INTO test VALUES (?, ?)', $messages[1]);
        self::assertContains('with parameters ([one], [two])', $messages[1]);
    }

    public function testDryRunWithNamedParametersOutputsParamsAndNamesWithSqlStatement() : void
    {
        $messages = [];

        $ow = new OutputWriter(static function ($msg) use (&$messages) : void {
            $messages[] = trim($msg);
        });

        $config = $this->getSqliteConfiguration();
        $config->setOutputWriter($ow);

        $version = $this->createTestVersion(
            $config,
            '006',
            VersionDryRunNamedParams::class
        );

        $version->execute(Direction::UP, (new MigratorConfiguration())
            ->setDryRun(true));

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('INSERT INTO test VALUES (:one, :two)', $messages[1]);
        self::assertContains('with parameters (:one => [one], :two => [two])', $messages[1]);
    }

    /** @return mixed[][] */
    public static function dryRunTypes() : array
    {
        return [
            'datetime' => [[new DateTime('2016-07-05 01:00:00')], ['datetime'], '[2016-07-05 01:00:00]'],
            'array' => [[['one' => 'two']], ['array'], '[' . serialize(['one' => 'two']) . ']'],
            'doctrine_param' => [[[1, 2, 3, 4, 5]], [Connection::PARAM_INT_ARRAY], '[1, 2, 3, 4, 5]'],
            'doctrine_param_grouped' => [[[1, 2], [3, 4, 5]], [Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY], '[1, 2], [3, 4, 5]'],
            'boolean' => [[true], [''], '[true]'],
            'object' => [[new stdClass()], [''], '[?]'],
        ];
    }

    /**
     * @param mixed[] $value
     * @param mixed[] $type
     *
     * @dataProvider dryRunTypes
     */
    public function testDryRunWithParametersOfComplexTypesCorrectFormatsParameters(
        array $value,
        array $type,
        string $output
    ) : void {
        $messages = [];

        $ow = new OutputWriter(static function ($msg) use (&$messages) : void {
            $messages[] = trim($msg);
        });

        $config = $this->getSqliteConfiguration();
        $config->setOutputWriter($ow);

        $version   = $this->createTestVersion(
            $config,
            '006',
            VersionDryRunTypes::class
        );
        $migration = $version->getMigration();

        self::assertInstanceOf(VersionDryRunTypes::class, $migration);

        $migration->setParam($value, $type);

        $version->execute(Direction::UP, (new MigratorConfiguration())
            ->setDryRun(true));

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('INSERT INTO test VALUES (?)', $messages[1]);
        self::assertContains(sprintf('with parameters (%s)', $output), $messages[1]);
    }

    public function testRunWithInsertNullValue() : void
    {
        $messages = [];

        $ow = new OutputWriter(static function ($msg) use (&$messages) : void {
            $messages[] = trim($msg);
        });

        $config = $this->getSqliteConfiguration();
        $config->setOutputWriter($ow);

        $version = $this->createTestVersion(
            $config,
            '001',
            VersionDryRunTypes::class
        );

        $migration = $version->getMigration();

        self::assertInstanceOf(VersionDryRunTypes::class, $migration);

        $migration->setParam([null], []);

        $version->execute(Direction::UP, (new MigratorConfiguration())
            ->setDryRun(true));

        self::assertCount(3, $messages, 'should have written three messages (header, footer, 1 SQL statement)');
        self::assertContains('INSERT INTO test VALUES (?)', $messages[1]);
        self::assertContains('with parameters ([])', $messages[1]);
    }

    /**
     * @dataProvider getExecutedAtTimeZones
     */
    public function testExecutedAtTimeZone(string $timeZone) : void
    {
        $this->iniSet('date.timezone', $timeZone);

        $config = $this->getSqliteConfiguration();

        $version = $this->createTestVersion(
            $config,
            '001',
            VersionDryRunTypes::class
        );

        $version->markVersion(Direction::UP);

        $versionData = $config->getVersionData($version);

        $now = (new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('UTC'));

        self::assertSame($now->format('Y-m-d H:i'), date('Y-m-d H:i', strtotime($versionData['executed_at'])));

        $executedAt = $version->getExecutedAt();

        self::assertNotNull($executedAt);

        self::assertSame($timeZone, $executedAt->getTimezone()->getName());
    }

    /**
     * @return string[][]
     */
    public function getExecutedAtTimeZones() : array
    {
        return [
            ['America/New_York'],
            ['Indian/Chagos'],
            ['UTC'],
        ];
    }

    /**
     * @return mixed[]
     */
    private function getMockVersionConstructorArgs(
        Configuration $configuration,
        string $versionName,
        string $className
    ) : array {
        $schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);

        $parameterFormatter = new ParameterFormatter($configuration->getConnection());

        $symfonyStopwatch = new SymfonyStopwatch();
        $stopwatch        = new Stopwatch($symfonyStopwatch);

        $versionExecutor = new Executor(
            $configuration,
            $configuration->getConnection(),
            $schemaDiffProvider,
            $configuration->getOutputWriter(),
            $parameterFormatter,
            $stopwatch
        );

        return [$configuration, $versionName, $className, $versionExecutor];
    }

    private function createTestVersion(
        Configuration $configuration,
        string $versionName,
        string $className
    ) : Version {
        $schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);

        $parameterFormatter = new ParameterFormatter($configuration->getConnection());

        $symfonyStopwatch = new SymfonyStopwatch();
        $stopwatch        = new Stopwatch($symfonyStopwatch);

        $versionExecutor = new Executor(
            $configuration,
            $configuration->getConnection(),
            $schemaDiffProvider,
            $configuration->getOutputWriter(),
            $parameterFormatter,
            $stopwatch
        );

        return new Version($configuration, $versionName, $className, $versionExecutor);
    }
}

class TestMigration extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
    }

    public function down(Schema $schema) : void
    {
    }
}
