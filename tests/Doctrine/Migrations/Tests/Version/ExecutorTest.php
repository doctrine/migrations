<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\Events;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\ParameterFormatterInterface;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\TestLogger;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\State;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\StopwatchEvent;

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

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var EventManager
     */
    private $eventManager;

    public function testAddSql() : void
    {
        $this->versionExecutor->addSql('SELECT 1', [1], [2]);

        self::assertSame(['SELECT 1'], $this->versionExecutor->getSql());
        self::assertSame([[1]], $this->versionExecutor->getParams());
        self::assertSame([[2]], $this->versionExecutor->getTypes());
    }

    public function testExecuteUp() : void
    {

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);

        $versionExecutionResult = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        self::assertSame(['SELECT 1', 'SELECT 2'], $versionExecutionResult->getSql());
        self::assertSame([[1], [2]], $versionExecutionResult->getParams());
        self::assertSame([[3], [4]], $versionExecutionResult->getTypes());
        self::assertNotNull($versionExecutionResult->getTime());
        self::assertSame(State::NONE, $versionExecutionResult->getState());
        self::assertTrue($this->migration->preUpExecuted);
        self::assertTrue($this->migration->postUpExecuted);
        self::assertFalse($this->migration->preDownExecuted);
        self::assertFalse($this->migration->postDownExecuted);


        self::assertSame(array (
            0 => '++ migrating test',
            1 => 'SELECT 1 ',
            2 => '100ms',
            3 => 'SELECT 2 ',
            4 => '100ms',
            5 => 'Migration test migrated (took 100ms, used 100 memory)',
        ), $this->logger->logs
        );
    }

    /**
     * @test
     */
    public function executeUpShouldAppendDescriptionWhenItIsNotEmpty() : void
    {
        $this->migration->setDescription('testing');

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);
        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $this->versionExecutor->execute($plan, $migratorConfiguration );

        self::assertSame('++ migrating test (testing)', $this->logger->logs[0]);
    }

    public function testExecuteDown() : void
    {
        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::DOWN);

        $versionExecutionResult = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        self::assertSame(['SELECT 3', 'SELECT 4'], $versionExecutionResult->getSql());
        self::assertSame([[5], [6]], $versionExecutionResult->getParams());
        self::assertSame([[7], [8]], $versionExecutionResult->getTypes());
        self::assertNotNull($versionExecutionResult->getTime());
        self::assertSame(State::NONE, $versionExecutionResult->getState());
        self::assertFalse($this->migration->preUpExecuted);
        self::assertFalse($this->migration->postUpExecuted);
        self::assertTrue($this->migration->preDownExecuted);
        self::assertTrue($this->migration->postDownExecuted);

        self::assertSame(array (
            0 => '++ reverting test',
              1 => 'SELECT 3 ',
              2 => '100ms',
              3 => 'SELECT 4 ',
              4 => '100ms',
              5 => 'Migration test reverted (took 100ms, used 100 memory)',
            ), $this->logger->logs
        );
    }

    /**
     * @test
     */
    public function testSkipMigration() : void
    {
        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $listener = new class() {
            public $onMigrationsVersionExecuting = false;
            public $onMigrationsVersionExecuted = false;
            public $onMigrationsVersionSkipped = false;

            public function onMigrationsVersionExecuting()
            {
                $this->onMigrationsVersionExecuting = true;
            }

            public function onMigrationsVersionExecuted()
            {
                $this->onMigrationsVersionExecuted = true;
            }

            public function onMigrationsVersionSkipped()
            {
                $this->onMigrationsVersionSkipped = true;
            }
        };
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuting, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuted, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionSkipped, $listener);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);
        $this->migration->skip = true;

        $versionExecutionResult = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        self::assertTrue($versionExecutionResult->isSkipped());
        self::assertSame([], $versionExecutionResult->getSql());
        self::assertSame(State::EXEC, $versionExecutionResult->getState());
        self::assertTrue($this->migration->preUpExecuted);
        self::assertFalse($this->migration->postUpExecuted);
        self::assertFalse($this->migration->preDownExecuted);
        self::assertFalse($this->migration->postDownExecuted);

        self::assertFalse($listener->onMigrationsVersionExecuted);
        self::assertTrue($listener->onMigrationsVersionSkipped);
        self::assertTrue($listener->onMigrationsVersionExecuting);

    }

    /**
     * @test
     */
    public function testMigrationEvents() : void
    {
        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);


        $listener = new class() {
            public $onMigrationsVersionExecuting = false;
            public $onMigrationsVersionExecuted = false;

            public function onMigrationsVersionExecuting()
            {
                $this->onMigrationsVersionExecuting = true;
            }

            public function onMigrationsVersionExecuted()
            {
                $this->onMigrationsVersionExecuted = true;
            }
        };
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuting, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuted, $listener);

        $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );
        self::assertTrue($listener->onMigrationsVersionExecuted);
        self::assertTrue($listener->onMigrationsVersionExecuting);
    }

    /**
     * @test
     */
    public function testErrorMigration() : void
    {
        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);
        $this->migration->error = true;

        $listener = new class() {
            public $onMigrationsVersionExecuting = false;
            public $onMigrationsVersionExecuted = false;
            public $onMigrationsVersionSkipped = false;

            public function onMigrationsVersionExecuting()
            {
                $this->onMigrationsVersionExecuting = true;
            }

            public function onMigrationsVersionExecuted()
            {
                $this->onMigrationsVersionExecuted = true;
            }

            public function onMigrationsVersionSkipped()
            {
                $this->onMigrationsVersionSkipped = true;
            }
        };
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuting, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuted, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionSkipped, $listener);

        try {
            $this->versionExecutor->execute(
                $plan,
                $migratorConfiguration
            );
            self::assertFalse(true);
        } catch (\Throwable $e){
            self::assertFalse($listener->onMigrationsVersionExecuted);
            self::assertTrue($listener->onMigrationsVersionSkipped);
            self::assertTrue($listener->onMigrationsVersionExecuting);

            $versionExecutionResult = $plan->getResult();

            self::assertTrue($versionExecutionResult->hasError());
            self::assertSame([], $versionExecutionResult->getSql());
            self::assertSame(State::EXEC, $versionExecutionResult->getState());
            self::assertTrue($this->migration->preUpExecuted);
            self::assertFalse($this->migration->postUpExecuted);
            self::assertFalse($this->migration->preDownExecuted);
            self::assertFalse($this->migration->postDownExecuted);
        }
    }

    /**
     * @test
     */
    public function executeDownShouldAppendDescriptionWhenItIsNotEmpty() : void
    {
        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::DOWN);

        $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        self::assertSame('++ reverting test', $this->logger->logs[0]);
    }

    protected function setUp() : void
    {
        $this->metadataStorage    = $this->createMock(MetadataStorage::class);
        $this->configuration      = $this->createMock(Configuration::class);
        $this->connection         = $this->createMock(Connection::class);
        $this->schemaDiffProvider = $this->createMock(SchemaDiffProviderInterface::class);
        $this->parameterFormatter = $this->createMock(ParameterFormatterInterface::class);

        $this->eventManager = new EventManager();
        $this->eventDispatcher = new EventDispatcher($this->connection, $this->configuration, $this->eventManager);

        $this->stopwatch          = $this->createMock(Stopwatch::class);
        $this->logger = new TestLogger();


        $this->versionExecutor = new Executor(
            $this->metadataStorage,
            $this->eventDispatcher,
            $this->connection,
            $this->schemaDiffProvider,
            $this->logger,
            $this->parameterFormatter,
            $this->stopwatch
        );

        $this->version = new Version('test');

        $this->migration = new VersionExecutorTestMigration($this->versionExecutor, $this->connection, $this->logger);

        $stopwatchEvent = $this->createMock(StopwatchEvent::class);

        $this->stopwatch->expects(self::any())
            ->method('start')
            ->willReturn($stopwatchEvent);

        $stopwatchEvent->expects(self::any())
            ->method('stop');

        $stopwatchEvent->expects(self::any())
            ->method('getDuration')
            ->willReturn(100);

        $stopwatchEvent->expects(self::any())
            ->method('getMemory')
            ->willReturn(100);
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
    private $description = '';

    public $skip = false;
    public $error = false;

    public function getDescription() : string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function preUp(Schema $fromSchema) : void
    {
        $this->preUpExecuted = true;
    }

    public function up(Schema $schema) : void
    {
        $this->skipIf($this->skip);
        $this->abortIf($this->error);

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
