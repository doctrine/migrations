<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationThrowsError;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutorInterface;
use Doctrine\Migrations\Version\Version;
use Exception;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use Throwable;
use const DIRECTORY_SEPARATOR;

require_once __DIR__ . '/realpath.php';

class MigratorTest extends MigrationTestCase
{
    /** @var Connection */
    private $conn;

    /** @var Configuration */
    private $config;

    /** @var StreamOutput */
    protected $output;

    /** @var MigratorConfiguration */
    private $migratorConfiguration;

    /** @var MockObject|ExecutorInterface */
    private $executor;

    /** @var TestLogger */
    private $logger;

    protected function setUp() : void
    {
        $this->conn   = $this->getSqliteConnection();
        $this->config = new Configuration();

        $this->migratorConfiguration = new MigratorConfiguration();
        $this->config->addMigrationsDirectory(
            'DoctrineMigrations\\',
            __DIR__ . DIRECTORY_SEPARATOR . 'Stub/migration-empty-folder'
        );
    }

    /**
     * @dataProvider getSqlProvider
     */
    public function testGetSql(?string $to) : void
    {
        $this->markTestSkipped();
        /** @var Migrator|MockObject $migration */
        $migration = $this->getMockBuilder(Migrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['migrate'])
            ->getMock();

        $expected = [['something']];

        $migration->expects(self::once())
            ->method('migrate')
            ->with($to)
            ->willReturn($expected);

        $result = $migration->getSql($to);

        self::assertSame($expected, $result);
    }

    /** @return mixed[][] */
    public function getSqlProvider() : array
    {
        return [
            [null],
            ['test'],
        ];
    }

    public function testEmptyPlanShowsMessage() : void
    {
        $migrator = $this->createTestMigrator();

        $planList = new MigrationPlanList([], Direction::UP);
        $migrator->migrate($planList, $this->migratorConfiguration);

        self::assertCount(1, $this->logger->logs, 'should output the no migrations message');
        self::assertContains('No migrations', $this->logger->logs[0]);
    }

    protected function createTestMigrator() : Migrator
    {
        $eventManager    = new EventManager();
        $eventDispatcher = new EventDispatcher($this->conn, $eventManager);
        $this->executor  = $this->createMock(ExecutorInterface::class);

        $this->logger = new TestLogger();

        $symfonyStopwatch = new SymfonyStopwatch();
        $stopwatch        = new Stopwatch($symfonyStopwatch);

        return new Migrator($this->conn, $eventDispatcher, $this->executor, $this->logger, $stopwatch);
    }

    public function testMigrateAllOrNothing() : void
    {
        $this->config->addMigrationsDirectory('DoctrineMigrations\\', __DIR__ . '/Stub/migrations-empty-folder');

        $migrator = $this->createTestMigrator();
        $this->migratorConfiguration->setAllOrNothing(true);

        $migration = new MigrateNotTouchingTheSchema($this->conn, $this->logger);
        $plan      = new MigrationPlan(new Version(MigrateNotTouchingTheSchema::class), $migration, Direction::UP);
        $planList  = new MigrationPlanList([$plan], Direction::UP);

        $sql = $migrator->migrate($planList, $this->migratorConfiguration);
        self::assertCount(1, $sql);
    }

    public function testMigrateAllOrNothingRollback() : void
    {
        $this->expectException(Throwable::class);

        $this->conn = $this->createMock(Connection::class);
        $this->conn
            ->expects(self::once())
            ->method('rollback');

        $migrator = $this->createTestMigrator();

        $this->executor
            ->expects(self::any())
            ->method('execute')
            ->willThrowException(new Exception());

        $this->migratorConfiguration->setAllOrNothing(true);

        $migration = new MigrationThrowsError($this->conn, $this->logger);
        $plan      = new MigrationPlan(new Version(MigrationThrowsError::class), $migration, Direction::UP);
        $planList  = new MigrationPlanList([$plan], Direction::UP);

        $migrator->migrate($planList, $this->migratorConfiguration);
    }
}
