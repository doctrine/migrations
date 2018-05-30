<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use Doctrine\Migrations\Tests\Stub\Functional\MigrationThrowsError;
use Doctrine\Migrations\Version\Direction;
use PHPUnit\Framework\Constraint\RegularExpression;
use Symfony\Component\Console\Output\StreamOutput;
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

    protected function setUp() : void
    {
        $this->conn   = $this->getSqliteConnection();
        $this->config = new Configuration($this->conn);
        $this->config->setMigrationsDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Stub/migration-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
    }

    public function testWriteSqlDown() : void
    {
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

        $migration->expects($this->once())
            ->method('getSql')
            ->with('1')
            ->willReturn($sql);

        $migrationRepository->expects($this->once())
            ->method('getCurrentVersion')
            ->willReturn('5');

        $outputWriter->expects($this->once())
            ->method('write')
            ->with("-- Migrating from 5 to 1\n");

        $configuration->expects($this->once())
            ->method('getQueryWriter')
            ->willReturn($queryWriter);

        $queryWriter->expects($this->once())
            ->method('write')
            ->with('/path', Direction::DOWN, $sql);

        $migration->writeSqlFile('/path', '1');
    }

    public function testMigrateToUnknownVersionThrowsException() : void
    {
        $migration = $this->createTestMigrator($this->config);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Could not find migration version 1234');

        $migration->migrate('1234');
    }

    public function testMigrateWithNoMigrationsThrowsException() : void
    {
        $migration = $this->createTestMigrator($this->config);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Could not find any migrations to execute.');

        $migration->migrate();
    }

    public function testMigrateWithNoMigrationsDontThrowsExceptionIfContiniousIntegrationOption() : void
    {
        $messages = [];

        $callback = function ($msg) use (&$messages) : void {
            $messages[] = $msg;
        };

        $this->config->getOutputWriter()->setCallback($callback);

        $migrator = $this->createTestMigrator($this->config);

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
        /** @var Migration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Migrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['migrate'])
            ->getMock();

        $expected = ['something'];

        $migration->expects($this->once())
            ->method('migrate')
            ->with($to)
            ->willReturn($expected);

        $result = $migration->getSql($to);

        self::assertEquals($expected, $result);
    }

    /** @return string|null[] */
    public function getSqlProvider() : array
    {
        return [
            [null],
            ['test'],
        ];
    }

    /**
     * @dataProvider writeSqlFileProvider
     *
     * @param string[] $getSqlReturn
     */
    public function testWriteSqlFile(string $path, string $from, ?string $to, array $getSqlReturn) : void
    {
        $queryWriter  = $this->createMock(QueryWriter::class);
        $outputWriter = $this->createMock(OutputWriter::class);

        $queryWriter->method('write')
            ->with($path, new RegularExpression('/(up|down)/'), $getSqlReturn)
            ->willReturn(true);

        $outputWriter->expects($this->atLeastOnce())
            ->method('write');

        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $config = $this->createMock(Configuration::class);

        $dependencyFactory   = $this->createMock(DependencyFactory::class);
        $migrationRepository = $this->createMock(MigrationRepository::class);

        $config->expects($this->once())
            ->method('getDependencyFactory')
            ->willReturn($dependencyFactory);

        $dependencyFactory->expects($this->once())
            ->method('getMigrationRepository')
            ->willReturn($migrationRepository);

        $dependencyFactory->expects($this->once())
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
                ->willReturn($from + 1);
        }

        /** @var Migration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Migrator::class)
            ->setConstructorArgs($this->getMigratorConstructorArgs($config))
            ->setMethods(['getSql'])
            ->getMock();

        $migration->expects($this->once())
            ->method('getSql')
            ->with($to)
            ->willReturn($getSqlReturn);

        self::assertTrue($migration->writeSqlFile($path, $to));
    }

    /**
     * @return string[][]
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

    public function testMigrateWithMigrationsAndAddTheCurrentVersionOutputsANoMigrationsMessage() : void
    {
        $messages = [];

        $callback = function ($msg) use (&$messages) : void {
            $messages[] = $msg;
        };

        $this->config->getOutputWriter()->setCallback($callback);
        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrateNotTouchingTheSchema::class);
        $this->config->createMigrationTable();
        $this->conn->insert($this->config->getMigrationsTableName(), [
            'version' => '20160707000000',
            'executed_at' => '2018-05-14 20:44:28',
        ]);

        $migration = $this->createTestMigrator($this->config);

        $migration->migrate();

        self::assertCount(1, $messages, 'should output the no migrations message');
        self::assertContains('No migrations', $messages[0]);
    }

    public function testMigrateAllOrNothing() : void
    {
        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrateNotTouchingTheSchema::class);

        $migration = $this->createTestMigrator($this->config);

        $sql = $migration->migrate(null, (new MigratorConfiguration())
            ->setAllOrNothing(true));

        self::assertCount(1, $sql);
    }

    public function testMigrateAllOrNothingRollback() : void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Migration up throws exception.');

        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrationThrowsError::class);

        $migration = $this->createTestMigrator($this->config);

        $migration->migrate(null, (new MigratorConfiguration())
            ->setAllOrNothing(true));
    }
}
