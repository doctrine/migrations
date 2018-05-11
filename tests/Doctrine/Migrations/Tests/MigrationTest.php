<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Migration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Tests\Stub\Functional\MigrateNotTouchingTheSchema;
use Doctrine\Migrations\Version;
use PHPUnit\Framework\Constraint\RegularExpression;
use Symfony\Component\Console\Output\StreamOutput;
use const DIRECTORY_SEPARATOR;

require_once __DIR__ . '/realpath.php';

class MigrationTest extends MigrationTestCase
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
        $queryWriter         = $this->createMock(QueryWriter::class);

        $sql = ['SELECT 1'];

        $migration = $this->getMockBuilder(Migration::class)
            ->setConstructorArgs([$configuration, $migrationRepository, $outputWriter])
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
            ->with('/path', Version::DIRECTION_DOWN, $sql);

        $migration->writeSqlFile('/path', '1');
    }

    public function testMigrateToUnknownVersionThrowsException() : void
    {
        $migration = $this->createTestMigration($this->config);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Could not find migration version 1234');

        $migration->migrate('1234');
    }

    public function testMigrateWithNoMigrationsThrowsException() : void
    {
        $migration = $this->createTestMigration($this->config);

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

        $migration = $this->createTestMigration($this->config);

        $migration->setNoMigrationException(true);
        $migration->migrate();

        self::assertCount(2, $messages, 'should output header and no migrations message');
        self::assertContains('No migrations', $messages[1]);
    }

    /**
     * @dataProvider getSqlProvider
     */
    public function testGetSql(?string $to) : void
    {
        /** @var Migration|\PHPUnit_Framework_MockObject_MockObject $migration */
        $migration = $this->getMockBuilder(Migration::class)
          ->disableOriginalConstructor()
          ->setMethods(['migrate'])
          ->getMock();

        $expected = ['something'];

        $migration->expects($this->once())
                  ->method('migrate')
                  ->with($to, true)
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
        $migration = $this->getMockBuilder(Migration::class)
            ->setConstructorArgs($this->getMigrationConstructorArgs($config))
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
        $this->conn->insert($this->config->getMigrationsTableName(), ['version' => '20160707000000']);

        $migration = $this->createTestMigration($this->config);

        $migration->migrate();

        self::assertCount(1, $messages, 'should output the no migrations message');
        self::assertContains('No migrations', $messages[0]);
    }

    public function testMigrateReturnsFalseWhenTheConfirmationIsDeclined() : void
    {
        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrateNotTouchingTheSchema::class);
        $this->config->createMigrationTable();
        $called    = false;
        $migration = $this->createTestMigration($this->config);

        $result = $migration->migrate(null, false, false, function () use (&$called) {
            $called = true;
            return false;
        });

        self::assertEmpty($result);
        self::assertTrue($called, 'should have called the confirmation callback');
    }

    public function testMigrateWithDryRunDoesNotCallTheConfirmationCallback() : void
    {
        $this->config->setMigrationsDirectory(__DIR__ . '/Stub/migrations-empty-folder');
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
        $this->config->registerMigration('20160707000000', MigrateNotTouchingTheSchema::class);
        $this->config->createMigrationTable();
        $called    = false;
        $migration = $this->createTestMigration($this->config);

        $result = $migration->migrate(null, true, false, function () use (&$called) {
            $called = true;
            return false;
        });

        self::assertFalse($called);
        self::assertEquals(['20160707000000' => ['SELECT 1']], $result);
    }
}
