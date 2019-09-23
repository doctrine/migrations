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

    public function testWriteSqlDown() : void
    {
        $this->markTestSkipped();
        $configuration       = $this->createMock(Configuration::class);
        $migrationRepository = $this->createMock(MigrationRepository::class);
        $outputWriter        = $this->createMock(OutputWriter::class);
        $stopwatch           = $this->createMock(Stopwatch::class);
        $queryWriter         = $this->createMock(QueryWriter::class);

        $sql = ['SELECT 1'];

        $migration = $this->getMockBuilder(Migrator::class)
            ->setConstructorArgs([
                $configuration,
                $migrationRepository,
                $outputWriter,
                $stopwatch,
            ])
            ->setMethods(['getSql'])
            ->getMock();

        $migration->expects(self::once())
            ->method('getSql')
            ->with('1')
            ->willReturn($sql);

        $migrationRepository->expects(self::once())
            ->method('getCurrentVersion')
            ->willReturn('5');

        $outputWriter->expects(self::once())
            ->method('write')
            ->with("-- Migrating from 5 to 1\n");

        $configuration->expects(self::once())
            ->method('getQueryWriter')
            ->willReturn($queryWriter);

        $queryWriter->expects(self::once())
            ->method('write')
            ->with('/path', Direction::DOWN, $sql);

        $migration->writeSqlFile('/path', '1');
    }

    public function testMigrateWithNoMigrationsThrowsException() : void
    {
        $this->markTestSkipped();
        $migration = $this->createTestMigrator();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Could not find any migrations to execute.');

        $migration->migrate();
    }

    public function testMigrateWithNoMigrationsDontThrowsExceptionIfContiniousIntegrationOption() : void
    {
        $this->markTestSkipped();
        $migrator = $this->createTestMigrator();

        $this->migratorConfiguration->setNoMigrationException(true);

        $planList = new MigrationPlanList([], Direction::UP);
        $migrator->migrate($planList, $this->migratorConfiguration);

        self::assertCount(1, $this->logger->logs, 'should output the no migrations message');
        self::assertContains('No migrations', $this->logger->logs[0]);

        $messages = [];

        $callback = static function ($msg) use (&$messages) : void {
            $messages[] = $msg;
        };

        $this->config->getOutputWriter()->setCallback($callback);

        $migrator = $this->createTestMigrator();

        $migratorConfiguration = (new MigratorConfiguration())
            ->setNoMigrationException(true);

        $migrator->migrate(null, $migratorConfiguration);

        self::assertCount(2, $messages, 'should output header and no migrations message');
        self::assertContains('No migrations', $messages[1]);
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

    /**
     * @param string[] $getSqlReturn
     *
     * @dataProvider writeSqlFileProvider
     */
    public function testWriteSqlFile(string $path, string $from, ?string $to, array $getSqlReturn) : void
    {
        $this->markTestSkipped();
        $queryWriter  = $this->createMock(QueryWriter::class);
        $outputWriter = $this->createMock(OutputWriter::class);

        $queryWriter->method('write')
            ->with($path, new RegularExpression('/(up|down)/'), $getSqlReturn)
            ->willReturn(true);

        $outputWriter->expects(self::atLeastOnce())
            ->method('write');

        /** @var Configuration|PHPUnit_Framework_MockObject_MockObject $migration */
        $config = $this->createMock(Configuration::class);

        $dependencyFactory   = $this->createMock(DependencyFactory::class);
        $migrationRepository = $this->createMock(MigrationRepository::class);

        $config->expects(self::once())
            ->method('getDependencyFactory')
            ->willReturn($dependencyFactory);

        $dependencyFactory->expects(self::once())
            ->method('getMigrationRepository')
            ->willReturn($migrationRepository);

        $dependencyFactory->expects(self::once())
            ->method('getOutputWriter')
            ->willReturn($outputWriter);

        $config->method('getCurrentVersion')
            ->willReturn($from);

        $config->method('getOutputWriter')
            ->willReturn($outputWriter);

        $config->method('getQueryWriter')
            ->willReturn($queryWriter);

        if ($to === null) { // this will always just test the "up" direction
            $config->method('getLatestVersion')
                ->willReturn((int) $from + 1);
        }

        /** @var Migrator|MockObject $migration */
        $migration = $this->getMockBuilder(Migrator::class)
            ->setConstructorArgs($this->getMigratorConstructorArgs($config))
            ->setMethods(['getSql'])
            ->getMock();

        $migration->expects(self::once())
            ->method('getSql')
            ->with($to)
            ->willReturn($getSqlReturn);

        self::assertTrue($migration->writeSqlFile($path, $to));
    }

    /**
     * @return mixed[][]
     */
    public function writeSqlFileProvider() : array
    {
        return [
            [__DIR__, '0', '1', ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, '0', null, ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, '1', '1', ['1' => ['SHOW DATABASES;']]], // up (same)
            [__DIR__, '1', '0', ['1' => ['SHOW DATABASES;']]], // down
            [__DIR__ . '/tmpfile.sql', '0', '1', ['1' => ['SHOW DATABASES']]], // tests something actually got written
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
