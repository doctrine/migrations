<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\ParameterFormatterInterface;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\Stopwatch\StopwatchPeriod;

class ExecutorTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $configuration;

    /** @var Connection|MockObject */
    private $connection;

    /** @var SchemaDiffProviderInterface|MockObject */
    private $schemaDiffProvider;

    /** @var OutputWriter|MockObject */
    private $outputWriter;

    /** @var ParameterFormatter|MockObject */
    private $parameterFormatter;

    /** @var Stopwatch|MockObject */
    private $stopwatch;

    /** @var Executor */
    private $versionExecutor;

    /** @var Version */
    private $version;

    /** @var VersionExecutorTestMigration */
    private $migration;

    public function testAddSql() : void
    {
        $this->versionExecutor->addSql('SELECT 1', [1], [2]);

        self::assertSame(['SELECT 1'], $this->versionExecutor->getSql());
        self::assertSame([[1]], $this->versionExecutor->getParams());
        self::assertSame([[2]], $this->versionExecutor->getTypes());
    }

    public function testExecuteUp() : void
    {
        $this->outputWriter->expects(self::at(0))
            ->method('write')
            ->with("\n  <info>++</info> migrating <comment>001</comment>\n");

        $this->outputWriter->expects(self::at(1))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 1');

        $this->outputWriter->expects(self::at(2))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects(self::at(3))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 2');

        $this->outputWriter->expects(self::at(4))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects(self::at(5))
            ->method('write')
            ->with("\n  <info>++</info> migrated (took 100ms, used 100 memory)");

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $versionExecutionResult = $this->versionExecutor->execute(
            $this->version,
            $this->migration,
            Direction::UP,
            $migratorConfiguration
        );

        self::assertSame(['SELECT 1', 'SELECT 2'], $versionExecutionResult->getSql());
        self::assertSame([[1], [2]], $versionExecutionResult->getParams());
        self::assertSame([[3], [4]], $versionExecutionResult->getTypes());
        self::assertNotNull($versionExecutionResult->getTime());
        self::assertSame('No State', $this->version->getExecutionState());
        self::assertTrue($this->migration->preUpExecuted);
        self::assertTrue($this->migration->postUpExecuted);
        self::assertFalse($this->migration->preDownExecuted);
        self::assertFalse($this->migration->postDownExecuted);
    }

    /**
     * @test
     */
    public function executeUpShouldAppendDescriptionWhenItIsNotEmpty() : void
    {
        $this->outputWriter->expects(self::at(0))
            ->method('write')
            ->with("\n  <info>++</info> migrating <comment>001 (testing)</comment>\n");

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $this->versionExecutor->execute(
            $this->version,
            new VersionExecutorTestMigration($this->version, 'testing'),
            Direction::UP,
            $migratorConfiguration
        );
    }

    public function testExecuteDown() : void
    {
        $this->outputWriter->expects(self::at(0))
            ->method('write')
            ->with("\n  <info>--</info> reverting <comment>001</comment>\n");

        $this->outputWriter->expects(self::at(1))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 3');

        $this->outputWriter->expects(self::at(2))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects(self::at(3))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 4');

        $this->outputWriter->expects(self::at(4))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects(self::at(5))
            ->method('write')
            ->with("\n  <info>--</info> reverted (took 100ms, used 100 memory)");

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $versionExecutionResult = $this->versionExecutor->execute(
            $this->version,
            $this->migration,
            Direction::DOWN,
            $migratorConfiguration
        );

        self::assertSame(['SELECT 3', 'SELECT 4'], $versionExecutionResult->getSql());
        self::assertSame([[5], [6]], $versionExecutionResult->getParams());
        self::assertSame([[7], [8]], $versionExecutionResult->getTypes());
        self::assertNotNull($versionExecutionResult->getTime());
        self::assertSame('No State', $this->version->getExecutionState());
        self::assertFalse($this->migration->preUpExecuted);
        self::assertFalse($this->migration->postUpExecuted);
        self::assertTrue($this->migration->preDownExecuted);
        self::assertTrue($this->migration->postDownExecuted);
    }

    /**
     * @test
     */
    public function executeDownShouldAppendDescriptionWhenItIsNotEmpty() : void
    {
        $this->outputWriter->expects(self::at(0))
            ->method('write')
            ->with("\n  <info>--</info> reverting <comment>001 (testing)</comment>\n");

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $this->versionExecutor->execute(
            $this->version,
            new VersionExecutorTestMigration($this->version, 'testing'),
            Direction::DOWN,
            $migratorConfiguration
        );
    }

    protected function setUp() : void
    {
        $this->configuration      = $this->createMock(Configuration::class);
        $this->connection         = $this->createMock(Connection::class);
        $this->schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);
        $this->outputWriter       = $this->createMock(OutputWriter::class);
        $this->parameterFormatter = $this->createMock(ParameterFormatterInterface::class);
        $this->stopwatch          = $this->createMock(Stopwatch::class);

        $this->versionExecutor = new Executor(
            $this->configuration,
            $this->connection,
            $this->schemaDiffProvider,
            $this->outputWriter,
            $this->parameterFormatter,
            $this->stopwatch
        );

        $this->configuration->method('getConnection')
            ->willReturn($this->connection);

        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));

        $this->version = new Version(
            $this->configuration,
            '001',
            VersionExecutorTestMigration::class,
            $this->versionExecutor
        );

        $this->migration = new VersionExecutorTestMigration($this->version);

        $stopwatchEvent = $this->createMock(StopwatchEvent::class);

        $this->stopwatch->method('start')
            ->willReturn($stopwatchEvent);

        $stopwatchEvent->method('stop');

        $stopwatchEvent->method('getDuration')
            ->willReturn(100);

        $stopwatchEvent->method('getMemory')
            ->willReturn(100);

        $stopWatchPeriod = $this->createMock(StopwatchPeriod::class);
        $stopWatchPeriod->method('getDuration')
            ->willReturn(100);
        $stopWatchPeriod->method('getMemory')
            ->willReturn(100);

        $stopwatchEvent->method('getPeriods')
            ->willReturn([$stopWatchPeriod]);
    }
}

class VersionExecutorTestMigration extends AbstractMigration
{
    /** @var bool */
    public $preUpExecuted = false;

    /** @var bool */
    public $preDownExecuted = false;

    /** @var bool */
    public $postUpExecuted = false;

    /** @var bool */
    public $postDownExecuted = false;

    /** @var string */
    private $description;

    public function __construct(Version $version, string $description = '')
    {
        parent::__construct($version);

        $this->description = $description;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function preUp(Schema $fromSchema) : void
    {
        $this->preUpExecuted = true;
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('SELECT 1', [1], [3]);
        $this->addSql('SELECT 2', [2], [4]);
    }

    public function postUp(Schema $toSchema) : void
    {
        $this->postUpExecuted = true;
    }

    public function preDown(Schema $fromSchema) : void
    {
        $this->preDownExecuted = true;
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('SELECT 3', [5], [7]);
        $this->addSql('SELECT 4', [6], [8]);
    }

    public function postDown(Schema $toSchema) : void
    {
        $this->postDownExecuted = true;
    }
}
