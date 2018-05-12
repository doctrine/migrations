<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\ParameterFormatterInterface;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\VersionDirection;
use Doctrine\Migrations\VersionExecutionResult;
use Doctrine\Migrations\VersionExecutor;
use PHPUnit\Framework\TestCase;

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

    /** @var VersionExecutor */
    private $versionExecutor;

    /** @var Version */
    private $version;

    /** @var Migration */
    private $migration;

    protected function setUp() : void
    {
        $this->configuration      = $this->createMock(Configuration::class);
        $this->connection         = $this->createMock(Connection::class);
        $this->schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);
        $this->outputWriter       = $this->createMock(OutputWriter::class);
        $this->parameterFormatter = $this->createMock(ParameterFormatterInterface::class);

        $this->versionExecutor = new VersionExecutor(
            $this->configuration,
            $this->connection,
            $this->schemaDiffProvider,
            $this->outputWriter,
            $this->parameterFormatter
        );

        $this->version = new Version(
            $this->configuration,
            '001',
            VersionExecutorTestMigration::class,
            $this->versionExecutor
        );

        $this->migration = new VersionExecutorTestMigration($this->version);
    }

    public function testAddSql() : void
    {
        $this->versionExecutor->addSql('SELECT 1', [1], [2]);

        self::assertEquals(['SELECT 1'], $this->versionExecutor->getSql());
        self::assertEquals([[1]], $this->versionExecutor->getParams());
        self::assertEquals([[2]], $this->versionExecutor->getTypes());
    }

    public function testExecuteUp() : void
    {
        $versionExecutionResult = $this->versionExecutor->execute(
            $this->version,
            $this->migration,
            VersionDirection::UP,
            false,
            true
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
        $versionExecutionResult = $this->versionExecutor->execute(
            $this->version,
            $this->migration,
            VersionDirection::DOWN,
            false,
            true
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
