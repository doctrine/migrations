<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\Events;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\TestLogger;
use Doctrine\Migrations\Tests\Version\Fixture\EmptyTestMigration;
use Doctrine\Migrations\Tests\Version\Fixture\VersionExecutorTestMigration;
use Doctrine\Migrations\Version\DbalExecutor;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\State;
use Doctrine\Migrations\Version\Version;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Throwable;

class ExecutorTest extends TestCase
{
    /** @var Connection|MockObject */
    private $connection;

    /** @var SchemaDiffProvider|MockObject */
    private $schemaDiffProvider;

    /** @var ParameterFormatter|MockObject */
    private $parameterFormatter;

    /** @var Stopwatch|MockObject */
    private $stopwatch;

    /** @var DbalExecutor */
    private $versionExecutor;

    /** @var Version */
    private $version;

    /** @var VersionExecutorTestMigration */
    private $migration;

    /** @var TestLogger */
    private $logger;

    /** @var EventDispatcher */
    private $eventDispatcher;

    /** @var EventManager */
    private $eventManager;

    /** @var MockObject */
    private $metadataStorage;

    public function testAddSql() : void
    {
        $query = new Query('SELECT 1', [1], [2]);
        $this->versionExecutor->addSql($query);

        self::assertCount(1, $this->versionExecutor->getSql());
        self::assertSame($query, $this->versionExecutor->getSql()[0]);
    }

    public function testExecuteWithNoQueries() : void
    {
        $migratorConfiguration = new MigratorConfiguration();

        $migration = new EmptyTestMigration($this->connection, $this->logger);
        $version   = new Version('xx');
        $plan      = new MigrationPlan($version, $migration, Direction::UP);

        $result = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        $queries = $result->getSql();
        self::assertCount(0, $queries);

        self::assertSame(0.1, $result->getTime());
        self::assertSame(State::NONE, $result->getState());

        self::assertSame([
            '++ migrating xx',
            'Migration xx was executed but did not result in any SQL statements.',
            'Migration xx migrated (took 100ms, used 100 memory)',
        ], $this->logger->logs);
    }

    public function testExecuteUp() : void
    {
        $this->metadataStorage
            ->expects(self::once())
            ->method('complete')->willReturnCallback(static function (ExecutionResult $result) : void {
                self::assertSame(Direction::UP, $result->getDirection());
                self::assertNotNull($result->getTime());
                self::assertNotNull($result->getExecutedAt());
            });

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);

        $result = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        $queries = $result->getSql();
        self::assertCount(2, $queries);
        self::assertSame('SELECT 1', $queries[0]->getStatement());
        self::assertSame([1], $queries[0]->getParameters());
        self::assertSame([3], $queries[0]->getTypes());

        self::assertSame('SELECT 2', $queries[1]->getStatement());
        self::assertSame([], $queries[1]->getParameters());
        self::assertSame([], $queries[1]->getTypes());

        self::assertNotNull($result->getTime());
        self::assertSame(State::NONE, $result->getState());
        self::assertTrue($this->migration->preUpExecuted);
        self::assertTrue($this->migration->postUpExecuted);
        self::assertFalse($this->migration->preDownExecuted);
        self::assertFalse($this->migration->postDownExecuted);

        self::assertSame([
            0 => '++ migrating test',
            1 => 'SELECT 1 ',
            2 => '100ms',
            3 => 'SELECT 2 ',
            4 => '100ms',
            5 => 'Migration test migrated (took 100ms, used 100 memory)',
        ], $this->logger->logs);
    }

    public function testExecuteUsedExecuteUpdate() : void
    {
        $this->connection
            ->expects(self::never())
            ->method('executeQuery');
        $this->connection
            ->expects(self::exactly(2))
            ->method('executeUpdate');

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );
    }

    /**
     * @test
     */
    public function executeUpShouldAppendDescriptionWhenItIsNotEmpty() : void
    {
        $this->migration->setDescription('testing');

        $plan                  = new MigrationPlan($this->version, $this->migration, Direction::UP);
        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $this->versionExecutor->execute($plan, $migratorConfiguration);

        self::assertSame('++ migrating test (testing)', $this->logger->logs[0]);
    }

    public function testExecuteDown() : void
    {
        $this->metadataStorage
            ->expects(self::once())
            ->method('complete')->willReturnCallback(static function (ExecutionResult $result) : void {
                self::assertSame(Direction::DOWN, $result->getDirection());
                self::assertNotNull($result->getTime());
                self::assertNotNull($result->getExecutedAt());
            });

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::DOWN);

        $result = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        $queries = $result->getSql();
        self::assertCount(2, $queries);
        self::assertSame('SELECT 3', $queries[0]->getStatement());
        self::assertSame([5], $queries[0]->getParameters());
        self::assertSame([7], $queries[0]->getTypes());

        self::assertSame('SELECT 4', $queries[1]->getStatement());
        self::assertSame([6], $queries[1]->getParameters());
        self::assertSame([8], $queries[1]->getTypes());

        self::assertNotNull($result->getTime());
        self::assertSame(State::NONE, $result->getState());
        self::assertFalse($this->migration->preUpExecuted);
        self::assertFalse($this->migration->postUpExecuted);
        self::assertTrue($this->migration->preDownExecuted);
        self::assertTrue($this->migration->postDownExecuted);

        self::assertSame([
            0 => '++ reverting test',
            1 => 'SELECT 3 ',
            2 => '100ms',
            3 => 'SELECT 4 ',
            4 => '100ms',
            5 => 'Migration test reverted (took 100ms, used 100 memory)',
        ], $this->logger->logs);
    }

    public function testExecuteDryRun() : void
    {
        $this->metadataStorage
            ->expects(self::never())
            ->method('complete');

        $this->connection
            ->expects(self::never())
            ->method('executeQuery');

        $this->connection
            ->expects(self::never())
            ->method('executeUpdate');

        $migratorConfiguration = (new MigratorConfiguration())
            ->setDryRun(true)
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);

        $result = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        $queries = $result->getSql();
        self::assertCount(2, $queries);
        self::assertSame('SELECT 1', $queries[0]->getStatement());
        self::assertSame([1], $queries[0]->getParameters());
        self::assertSame([3], $queries[0]->getTypes());

        self::assertSame('SELECT 2', $queries[1]->getStatement());
        self::assertSame([], $queries[1]->getParameters());
        self::assertSame([], $queries[1]->getTypes());

        self::assertNotNull($result->getTime());
        self::assertSame(State::NONE, $result->getState());
        self::assertTrue($this->migration->preUpExecuted);
        self::assertTrue($this->migration->postUpExecuted);
        self::assertFalse($this->migration->preDownExecuted);
        self::assertFalse($this->migration->postDownExecuted);

        self::assertSame([
            '++ migrating test',
            'SELECT 1 ',
            'SELECT 2 ',
            'Migration test migrated (took 100ms, used 100 memory)',
        ], $this->logger->logs);
    }

    /**
     * @test
     */
    public function testSkipMigration() : void
    {
        $this->metadataStorage
            ->expects(self::never())
            ->method('complete');

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $listener = new class() {
            /** @var bool */
            public $onMigrationsVersionExecuting = false;
            /** @var bool */
            public $onMigrationsVersionExecuted = false;
            /** @var bool */
            public $onMigrationsVersionSkipped = false;

            public function onMigrationsVersionExecuting() : void
            {
                $this->onMigrationsVersionExecuting = true;
            }

            public function onMigrationsVersionExecuted() : void
            {
                $this->onMigrationsVersionExecuted = true;
            }

            public function onMigrationsVersionSkipped() : void
            {
                $this->onMigrationsVersionSkipped = true;
            }
        };
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuting, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuted, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionSkipped, $listener);

        $plan                  = new MigrationPlan($this->version, $this->migration, Direction::UP);
        $this->migration->skip = true;

        $result = $this->versionExecutor->execute(
            $plan,
            $migratorConfiguration
        );

        self::assertTrue($result->isSkipped());
        self::assertSame([], $result->getSql());
        self::assertSame(State::EXEC, $result->getState());
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
            /** @var bool */
            public $onMigrationsVersionExecuting = false;
            /** @var bool */
            public $onMigrationsVersionExecuted = false;

            public function onMigrationsVersionExecuting() : void
            {
                $this->onMigrationsVersionExecuting = true;
            }

            public function onMigrationsVersionExecuted() : void
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
        $this->metadataStorage
            ->expects(self::never())
            ->method('complete');

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan                   = new MigrationPlan($this->version, $this->migration, Direction::UP);
        $this->migration->error = true;

        $listener = new class() {
            /** @var bool */
            public $onMigrationsVersionExecuting = false;
            /** @var bool */
            public $onMigrationsVersionExecuted = false;
            /** @var bool */
            public $onMigrationsVersionSkipped = false;

            public function onMigrationsVersionExecuting() : void
            {
                $this->onMigrationsVersionExecuting = true;
            }

            public function onMigrationsVersionExecuted() : void
            {
                $this->onMigrationsVersionExecuted = true;
            }

            public function onMigrationsVersionSkipped() : void
            {
                $this->onMigrationsVersionSkipped = true;
            }
        };
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuting, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuted, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionSkipped, $listener);

        $migrationSucceed = false;
        try {
            $this->versionExecutor->execute(
                $plan,
                $migratorConfiguration
            );
            $migrationSucceed = true;
        } catch (Throwable $e) {
            self::assertFalse($listener->onMigrationsVersionExecuted);
            self::assertTrue($listener->onMigrationsVersionSkipped);
            self::assertTrue($listener->onMigrationsVersionExecuting);

            $result = $plan->getResult();
            self::assertNotNull($result);
            self::assertSame([], $result->getSql());
            self::assertSame([], $result->getSql());
            self::assertSame(State::EXEC, $result->getState());
            self::assertTrue($this->migration->preUpExecuted);
            self::assertFalse($this->migration->postUpExecuted);
            self::assertFalse($this->migration->preDownExecuted);
            self::assertFalse($this->migration->postDownExecuted);
        }

        self::assertFalse($migrationSucceed);
    }

    public function testChangesNotCommittedIfMetadataFailure() : void
    {
        $this->metadataStorage
            ->expects(self::once())
            ->method('complete')
            ->willThrowException(new Exception('foo'));

        $this->connection
            ->expects(self::never())
            ->method('commit');

        $this->connection
            ->expects(self::once())
            ->method('rollBack');

        $migratorConfiguration = (new MigratorConfiguration())
            ->setTimeAllQueries(true);

        $plan = new MigrationPlan($this->version, $this->migration, Direction::UP);

        $listener = new class() {
            /** @var bool */
            public $onMigrationsVersionExecuting = false;
            /** @var bool */
            public $onMigrationsVersionExecuted = false;
            /** @var bool */
            public $onMigrationsVersionSkipped = false;

            public function onMigrationsVersionExecuting() : void
            {
                $this->onMigrationsVersionExecuting = true;
            }

            public function onMigrationsVersionExecuted() : void
            {
                $this->onMigrationsVersionExecuted = true;
            }

            public function onMigrationsVersionSkipped() : void
            {
                $this->onMigrationsVersionSkipped = true;
            }
        };
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuting, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionExecuted, $listener);
        $this->eventManager->addEventListener(Events::onMigrationsVersionSkipped, $listener);

        $migrationSucceed = false;
        try {
            $this->versionExecutor->execute(
                $plan,
                $migratorConfiguration
            );
            $migrationSucceed = true;
        } catch (Throwable $e) {
            self::assertFalse($listener->onMigrationsVersionExecuted);
            self::assertTrue($listener->onMigrationsVersionSkipped);
            self::assertTrue($listener->onMigrationsVersionExecuting);

            $result = $plan->getResult();
            self::assertNotNull($result);
            self::assertSame([], $result->getSql());
            self::assertSame([], $result->getSql());
            self::assertSame(State::POST, $result->getState());
            self::assertTrue($this->migration->preUpExecuted);
            self::assertTrue($this->migration->postUpExecuted);
            self::assertFalse($this->migration->preDownExecuted);
            self::assertFalse($this->migration->postDownExecuted);
        }

        self::assertFalse($migrationSucceed);
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
        $this->connection         = $this->createMock(Connection::class);
        $this->schemaDiffProvider = $this->createMock(SchemaDiffProvider::class);
        $this->parameterFormatter = $this->createMock(ParameterFormatter::class);

        $this->eventManager    = new EventManager();
        $this->eventDispatcher = new EventDispatcher($this->connection, $this->eventManager);

        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->logger    = new TestLogger();

        $this->versionExecutor = new DbalExecutor(
            $this->metadataStorage,
            $this->eventDispatcher,
            $this->connection,
            $this->schemaDiffProvider,
            $this->logger,
            $this->parameterFormatter,
            $this->stopwatch
        );

        $this->version = new Version('test');

        $this->migration = new VersionExecutorTestMigration($this->connection, $this->logger);

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
