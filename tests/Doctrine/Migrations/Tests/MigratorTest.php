<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DbalMigrator;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\Exception\MigrationConfigurationConflict;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationThrowsError;
use Doctrine\Migrations\Tests\Stub\NonTransactional\MigrationNonTransactional;
use Doctrine\Migrations\Version\DbalExecutor;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

use function array_map;

use const DIRECTORY_SEPARATOR;

class MigratorTest extends MigrationTestCase
{
    /** @var Connection&MockObject */
    private Connection $conn;

    private Configuration $config;

    protected StreamOutput $output;

    private MigratorConfiguration $migratorConfiguration;

    private Executor $executor;

    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->conn       = $this->createMock(Connection::class);
        $driverConnection = $this->createStub(DriverConnection::class);
        $this->conn->method('getWrappedConnection')->willReturn($driverConnection);

        $this->config = new Configuration();

        $this->migratorConfiguration = new MigratorConfiguration();
        $this->config->addMigrationsDirectory(
            'DoctrineMigrations\\',
            __DIR__ . DIRECTORY_SEPARATOR . 'Stub/migration-empty-folder'
        );
    }

    public function testGetSql(): void
    {
        $this->config->addMigrationsDirectory('DoctrineMigrations\\', __DIR__ . '/Stub/migrations-empty-folder');

        $migrator = $this->createTestMigrator();

        $migration = new MigrateNotTouchingTheSchema($this->conn, $this->logger);
        $plan      = new MigrationPlan(new Version(MigrateNotTouchingTheSchema::class), $migration, Direction::UP);
        $planList  = new MigrationPlanList([$plan], Direction::UP);

        $sql = $migrator->migrate($planList, $this->migratorConfiguration);

        self::assertCount(1, $sql);
        self::assertArrayHasKey('Doctrine\\Migrations\\Tests\\Stub\\Functional\\MigrateNotTouchingTheSchema', $sql);
        self::assertSame(
            ['SELECT 1'],
            array_map('strval', $sql['Doctrine\\Migrations\\Tests\\Stub\\Functional\\MigrateNotTouchingTheSchema'])
        );
    }

    public function testEmptyPlanShowsMessage(): void
    {
        $migrator = $this->createTestMigrator();

        $planList = new MigrationPlanList([], Direction::UP);
        $migrator->migrate($planList, $this->migratorConfiguration);

        self::assertCount(1, $this->logger->logs, 'should output the no migrations message');
        self::assertStringContainsString('No migrations', $this->logger->logs[0]);
    }

    protected function createTestMigrator(): DbalMigrator
    {
        $eventManager    = new EventManager();
        $eventDispatcher = new EventDispatcher($this->conn, $eventManager);

        $this->logger = new TestLogger();

        $stopwatch      = new Stopwatch();
        $paramFormatter = $this->createMock(ParameterFormatter::class);
        $storage        = $this->createMock(MetadataStorage::class);
        $schemaDiff     = $this->createMock(SchemaDiffProvider::class);

        $this->executor = new DbalExecutor($storage, $eventDispatcher, $this->conn, $schemaDiff, $this->logger, $paramFormatter, $stopwatch);

        return new DbalMigrator($this->conn, $eventDispatcher, $this->executor, $this->logger, $stopwatch);
    }

    public function testMigrateAllOrNothing(): void
    {
        $this->config->addMigrationsDirectory('DoctrineMigrations\\', __DIR__ . '/Stub/migrations-empty-folder');

        $migrator = $this->createTestMigrator();
        $this->conn
            ->expects(self::exactly(2))
            ->method('beginTransaction');

        $this->conn
            ->expects(self::never())
            ->method('rollback');

        $this->conn
            ->expects(self::exactly(2))
            ->method('commit');

        $this->migratorConfiguration->setAllOrNothing(true);

        $migration = new MigrateNotTouchingTheSchema($this->conn, $this->logger);
        $plan      = new MigrationPlan(new Version(MigrateNotTouchingTheSchema::class), $migration, Direction::UP);
        $planList  = new MigrationPlanList([$plan], Direction::UP);

        $sql = $migrator->migrate($planList, $this->migratorConfiguration);

        self::assertCount(1, $sql);
        self::assertArrayHasKey('Doctrine\\Migrations\\Tests\\Stub\\Functional\\MigrateNotTouchingTheSchema', $sql);
        self::assertSame(
            ['SELECT 1'],
            array_map('strval', $sql['Doctrine\\Migrations\\Tests\\Stub\\Functional\\MigrateNotTouchingTheSchema'])
        );
    }

    public function testMigrateAllOrNothingRollback(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Migration up throws exception.');

        $this->conn
            ->expects(self::exactly(2))
            ->method('beginTransaction');

        $this->conn
            ->expects(self::never())
            ->method('commit');

        $this->conn
            ->expects(self::exactly(2))
            ->method('rollback');

        $migrator = $this->createTestMigrator();

        $this->migratorConfiguration->setAllOrNothing(true);

        $migration = new MigrationThrowsError($this->conn, $this->logger);
        $plan      = new MigrationPlan(new Version(MigrationThrowsError::class), $migration, Direction::UP);
        $planList  = new MigrationPlanList([$plan], Direction::UP);

        $migrator->migrate($planList, $this->migratorConfiguration);
    }

    public function testMigrateAllOrNothingNonTransactionalMigration(): void
    {
        $this->config->addMigrationsDirectory('DoctrineMigrations\\', __DIR__ . '/Stub/NonTransactional');

        $migrator = $this->createTestMigrator();

        $this->migratorConfiguration->setAllOrNothing(true);

        $migration = new MigrationNonTransactional($this->conn, $this->logger);
        $plan      = new MigrationPlan(new Version(MigrationNonTransactional::class), $migration, Direction::UP);
        $planList  = new MigrationPlanList([$plan], Direction::UP);

        self::expectException(MigrationConfigurationConflict::class);
        self::expectExceptionMessage(MigrationNonTransactional::class);

        $migrator->migrate($planList, $this->migratorConfiguration);
    }
}
