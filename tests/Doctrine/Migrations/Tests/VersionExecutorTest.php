<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigratorConfig;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\ParameterFormatterInterface;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\VersionDirection;
use Doctrine\Migrations\VersionExecutionResult;
use Doctrine\Migrations\VersionExecutor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\StopwatchEvent;

class VersionExecutorTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    /** @var SchemaDiffProviderInterface */
    private $schemaDiffProvider;

    /** @var OutputWriter */
    private $outputWriter;

    /** @var ParameterFormatter */
    private $parameterFormatter;

    /** @var Stopwatch */
    private $stopwatch;

    /** @var VersionExecutor */
    private $versionExecutor;

    /** @var Version */
    private $version;

    /** @var Migration */
    private $migration;

    public function testAddSql() : void
    {
        $this->versionExecutor->addSql('SELECT 1', [1], [2]);

        self::assertEquals(['SELECT 1'], $this->versionExecutor->getSql());
        self::assertEquals([[1]], $this->versionExecutor->getParams());
        self::assertEquals([[2]], $this->versionExecutor->getTypes());
    }

    public function testExecuteUp() : void
    {
        $platform = $this->createMock(AbstractPlatform::class);

        $this->connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $stopwatchEvent = $this->createMock(StopwatchEvent::class);

        $this->stopwatch->expects($this->any())
            ->method('start')
            ->willReturn($stopwatchEvent);

        $stopwatchEvent->expects($this->any())
            ->method('stop');

        $stopwatchEvent->expects($this->any())
            ->method('getDuration')
            ->willReturn(100);

        $stopwatchEvent->expects($this->any())
            ->method('getMemory')
            ->willReturn(100);

        $this->outputWriter->expects($this->at(0))
            ->method('write')
            ->with("\n  <info>++</info> migrating <comment>001</comment>\n");

        $this->outputWriter->expects($this->at(1))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 1');

        $this->outputWriter->expects($this->at(2))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects($this->at(3))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 2');

        $this->outputWriter->expects($this->at(4))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects($this->at(5))
            ->method('write')
            ->with("\n  <info>++</info> migrated (took 100ms, used 100 memory)");

        $migratorConfig = (new MigratorConfig())
            ->setTimeAllQueries(true)
        ;

        $versionExecutionResult = $this->versionExecutor->execute(
            $this->version,
            $this->migration,
            VersionDirection::UP,
            $migratorConfig
        );

        self::assertInstanceOf(VersionExecutionResult::class, $versionExecutionResult);
        self::assertEquals(['SELECT 1', 'SELECT 2'], $versionExecutionResult->getSql());
        self::assertEquals([[1], [2]], $versionExecutionResult->getParams());
        self::assertEquals([[3], [4]], $versionExecutionResult->getTypes());
        self::assertNotNull($versionExecutionResult->getTime());
        self::assertEquals('No State', $this->version->getExecutionState());
        self::assertTrue($this->migration->preUpExecuted);
        self::assertTrue($this->migration->postUpExecuted);
        self::assertFalse($this->migration->preDownExecuted);
        self::assertFalse($this->migration->postDownExecuted);
    }

    public function testExecuteDown() : void
    {
        $stopwatchEvent = $this->createMock(StopwatchEvent::class);

        $this->stopwatch->expects($this->any())
            ->method('start')
            ->willReturn($stopwatchEvent);

        $stopwatchEvent->expects($this->any())
            ->method('stop');

        $stopwatchEvent->expects($this->any())
            ->method('getDuration')
            ->willReturn(100);

        $stopwatchEvent->expects($this->any())
            ->method('getMemory')
            ->willReturn(100);

        $this->outputWriter->expects($this->at(0))
            ->method('write')
            ->with("\n  <info>--</info> reverting <comment>001</comment>\n");

        $this->outputWriter->expects($this->at(1))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 3');

        $this->outputWriter->expects($this->at(2))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects($this->at(3))
            ->method('write')
            ->with('     <comment>-></comment> SELECT 4');

        $this->outputWriter->expects($this->at(4))
            ->method('write')
            ->with('  <info>100ms</info>');

        $this->outputWriter->expects($this->at(5))
            ->method('write')
            ->with("\n  <info>--</info> reverted (took 100ms, used 100 memory)");

        $migratorConfig = (new MigratorConfig())
            ->setTimeAllQueries(true)
        ;

        $versionExecutionResult = $this->versionExecutor->execute(
            $this->version,
            $this->migration,
            VersionDirection::DOWN,
            $migratorConfig
        );

        self::assertInstanceOf(VersionExecutionResult::class, $versionExecutionResult);
        self::assertEquals(['SELECT 3', 'SELECT 4'], $versionExecutionResult->getSql());
        self::assertEquals([[5], [6]], $versionExecutionResult->getParams());
        self::assertEquals([[7], [8]], $versionExecutionResult->getTypes());
        self::assertNotNull($versionExecutionResult->getTime());
        self::assertEquals('No State', $this->version->getExecutionState());
        self::assertFalse($this->migration->preUpExecuted);
        self::assertFalse($this->migration->postUpExecuted);
        self::assertTrue($this->migration->preDownExecuted);
        self::assertTrue($this->migration->postDownExecuted);
    }

    protected function setUp() : void
    {
        $this->configuration      = $this->createMock(Configuration::class);
        $this->connection         = $this->createMock(Connection::class);
        $this->schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);
        $this->outputWriter       = $this->createMock(OutputWriter::class);
        $this->parameterFormatter = $this->createMock(ParameterFormatterInterface::class);
        $this->stopwatch          = $this->createMock(Stopwatch::class);

        $this->versionExecutor = new VersionExecutor(
            $this->configuration,
            $this->connection,
            $this->schemaDiffProvider,
            $this->outputWriter,
            $this->parameterFormatter,
            $this->stopwatch
        );

        $this->configuration->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->version = new Version(
            $this->configuration,
            '001',
            VersionExecutorTestMigration::class,
            $this->versionExecutor
        );

        $this->migration = new VersionExecutorTestMigration($this->version);
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
