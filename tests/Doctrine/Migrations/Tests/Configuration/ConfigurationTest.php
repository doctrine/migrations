<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\Driver\IBMDB2\DB2Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\Configuration\AutoloadVersions\Version1Test;
use Doctrine\Migrations\Version;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use function array_keys;
use function call_user_func_array;
use function sprintf;
use function str_replace;

class ConfigurationTest extends MigrationTestCase
{
    public function testConstructorSetsOutputWriter() : void
    {
        $outputWriter = $this->getOutputWriterMock();

        $configuration = new Configuration(
            $this->getConnectionMock(),
            $outputWriter
        );

        self::assertSame($outputWriter, $configuration->getOutputWriter());
    }

    public function testOutputWriterIsCreatedIfNotInjected() : void
    {
        $configuration = new Configuration($this->getConnectionMock());

        self::assertInstanceOf(OutputWriter::class, $configuration->getOutputWriter());
    }

    public function testOutputWriterCanBeSet() : void
    {
        $outputWriter = $this->getOutputWriterMock();

        $configuration = new Configuration($this->getConnectionMock());
        $configuration->setOutputWriter($outputWriter);

        self::assertSame($outputWriter, $configuration->getOutputWriter());
    }

    public function testRegisterMigrationsClassExistCheck() : void
    {
        $migrationsDir = __DIR__ . '/ConfigurationTestSource/Migrations';

        $connection = $this->getConnectionMock();

        $platform      = $this->createMock(AbstractPlatform::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);

        $configuration = new Configuration($connection);
        $configuration->setMigrationsNamespace('Migrations');
        $configuration->setMigrationsDirectory($migrationsDir);

        $this->expectException(
            MigrationException::class,
            'Migration class "Migrations\Version123" was not found. Is it placed in "Migrations" namespace?'
        );
        $configuration->registerMigrationsFromDirectory($migrationsDir);
    }

    public function testGetSetMigrationsColumnName() : void
    {
        $configuration = new Configuration($this->getConnectionMock());

        self::assertSame('version', $configuration->getMigrationsColumnName());

        $configuration->setMigrationsColumnName('foobar');
        self::assertSame('foobar', $configuration->getMigrationsColumnName());
    }

    /**
     * @dataProvider methodsThatNeedsVersionsLoaded
     *
     * @param mixed[] $args
     * @param mixed   $expectedResult
     */
    public function testVersionsTryToGetLoadedIfNotAlreadyLoadedWhenAccessingMethodThatNeedThem(
        string $method,
        array $args,
        $expectedResult
    ) : void {
        $configuration = new Configuration($this->getSqliteConnection());
        $configuration->setMigrationsNamespace(str_replace('\Version1Test', '', Version1Test::class));
        $configuration->setMigrationsDirectory(__DIR__ . '/../Stub/Configuration/AutoloadVersions');

        $result = call_user_func_array([$configuration, $method], $args);

        if ($method === 'getMigrationsToExecute') {
            $result = array_keys($result);
        }

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider methodsThatNeedsVersionsLoadedWithAlreadyMigratedMigrations
     *
     * @param mixed[] $args
     * @param mixed   $expectedResult
     */
    public function testVersionsTryToGetLoadedIfNotAlreadyLoadedWhenAccessingMethodThatNeedThemEvenIfSomeMigrationsAreAlreadyMigrated(
        string $method,
        array $args,
        $expectedResult
    ) : void {
        $configuration = new Configuration($this->getSqliteConnection());
        $configuration->setMigrationsNamespace(str_replace('\Version1Test', '', Version1Test::class));
        $configuration->setMigrationsDirectory(__DIR__ . '/../Stub/Configuration/AutoloadVersions');

        $dependencyFactory = $configuration->getDependencyFactory();

        $symfonyStopwatch = new SymfonyStopwatch(true);
        $stopwatch        = new Stopwatch($symfonyStopwatch);

        $migrator = new Migrator(
            $configuration,
            $dependencyFactory->getMigrationRepository(),
            $dependencyFactory->getOutputWriter(),
            $stopwatch
        );
        $migrator->migrate('3Test');

        $result = call_user_func_array([$configuration, $method], $args);

        if ($method === 'getMigrationsToExecute') {
            $result = array_keys($result);
        }

        self::assertEquals($expectedResult, $result);
    }

    public function testGenerateVersionNumberFormatsTheDatePassedIn() : void
    {
        $configuration = new Configuration($this->getSqliteConnection());
        $now           = new DateTime('2016-07-05 01:00:00');

        $version = $configuration->generateVersionNumber($now);

        self::assertEquals('20160705010000', $version);
    }

    /**
     * We don't actually test the full "time" part of this, since that would fail
     * intermittently. Instead we just verify that we get a version number back
     * that has the current date, hour, and minute. We're really just testing
     * the `?: new DateTime(...)` bit of generateVersionNumber
     */
    public function testGenerateVersionNumberWithoutNowUsesTheCurrentTime() : void
    {
        $configuration = new Configuration($this->getSqliteConnection());

        $now     = new DateTime('now', new DateTimeZone('UTC'));
        $version = $configuration->generateVersionNumber();

        self::assertRegExp(sprintf('/^%s\d{2}$/', $now->format('YmdHi')), $version);
    }

    /**
     * Connection is tested via the `getMigratedVersions` method which is the
     * simplest to set up for.
     *
     * @see https://github.com/doctrine/migrations/issues/336
     */
    public function testMasterSlaveConnectionAlwaysConnectsToMaster() : void
    {
        $connection = $this->createMock(MasterSlaveConnection::class);

        $connection->expects($this->once())
            ->method('connect')
            ->with('master')
            ->willReturn(true);

        $configuration = new Configuration($connection);
        $configuration->setMigrationsNamespace(str_replace('\Version1Test', '', Version1Test::class));
        $configuration->setMigrationsDirectory(__DIR__ . '/../Stub/Configuration/AutoloadVersions');

        self::assertTrue($configuration->connect());
    }

    /** @return mixed[] */
    public function methodsThatNeedsVersionsLoadedWithAlreadyMigratedMigrations() : array
    {
        return [
            ['hasVersion', ['4Test'], true],
            ['getAvailableVersions', [], ['1Test', '2Test', '3Test', '4Test', '5Test']],
            ['getCurrentVersion', [], '3Test'],
            ['getRelativeVersion', ['3Test', -1], '2Test'],
            ['getNumberOfAvailableMigrations', [], 5],
            ['getLatestVersion', [], '5Test'],
            [
                'getMigrationsToExecute',
                ['up', '5'],
                [
                    '4Test',
                    '5Test',
                ],
            ],
            [
                'getMigrationsToExecute',
                ['up', '4'],
                ['4Test'],
            ],
            [
                'getMigrationsToExecute',
                ['down', '0'],
                [
                    '3Test',
                    '2Test',
                    '1Test',
                ],
            ],
            [
                'getMigrationsToExecute',
                ['down', '2'],
                ['3Test'],
            ],
        ];
    }

    /** @return mixed[] */
    public function methodsThatNeedsVersionsLoaded() : array
    {
        return [
            ['hasVersion', ['3Test'], true],
            ['getAvailableVersions', [], ['1Test', '2Test', '3Test', '4Test', '5Test']],
            ['getCurrentVersion', [], '0'],
            ['getRelativeVersion', ['3Test', -1], '2Test'],
            ['getNumberOfAvailableMigrations', [], 5],
            ['getLatestVersion', [], '5Test'],
            [
                'getMigrationsToExecute',
                ['up', '5'],
                [
                    '1Test',
                    '2Test',
                    '3Test',
                    '4Test',
                    '5Test',
                ],
            ],
            ['getMigrationsToExecute', ['down', '0'], []],
            ['getMigrationsToExecute', ['down', '2'], []],
        ];
    }

    public function testGetQueryWriterCreatesAnInstanceIfItWasNotConfigured() : void
    {
        $dp = $this->getMockForAbstractClass(AbstractPlatform::class, [], '', false, true, true, ['getReservedKeywordsClass']);

        $dp->method('getReservedKeywordsClass')
            ->willReturn(EmptyKeywordList::class);

        $conn = $this->getConnectionMock();
        $conn->method('getDatabasePlatform')
            ->willReturn($dp);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $conn->expects($this->any())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);

        $configuration = new Configuration($conn);
        $queryWriter   = $configuration->getQueryWriter();

        self::assertAttributeSame($configuration->getOutputWriter(), 'outputWriter', $queryWriter);
    }

    public function testGetQueryWriterShouldReturnTheObjectGivenOnTheConstructor() : void
    {
        $queryWriter   = $this->createMock(QueryWriter::class);
        $configuration = new Configuration($this->getConnectionMock(), null, null, $queryWriter);

        self::assertSame($queryWriter, $configuration->getQueryWriter());
    }

    public function testDBWhereVersionIsKeywordReturnsColumnNameWithQuotes() : void
    {
        $config = new Configuration(new Connection([], new DB2Driver()));

        self::assertEquals('"version"', $config->getQuotedMigrationsColumnName());
    }

    public function testGetVersionData() : void
    {
        $dependencyFactory   = $this->createMock(DependencyFactory::class);
        $migrationRepository = $this->createMock(MigrationRepository::class);
        $version             = $this->createMock(Version::class);

        $versionData = [
            'version' => '1234',
            'executed_at' => '2018-05-16 11:14:40',
        ];

        $dependencyFactory->expects($this->once())
            ->method('getMigrationRepository')
            ->willReturn($migrationRepository);

        $migrationRepository->expects($this->once())
            ->method('getVersionData')
            ->with($version)
            ->willReturn($versionData);

        $configuration = new Configuration($this->getConnectionMock(), null, null, null, $dependencyFactory);

        self::assertEquals($versionData, $configuration->getVersionData($version));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function getConnectionMock()
    {
        return $this->createMock(Connection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|OutputWriter
     */
    private function getOutputWriterMock()
    {
        return $this->createMock(OutputWriter::class);
    }
}

final class EmptyKeywordList extends KeywordList
{
    /** @return string[] */
    protected function getKeywords() : array
    {
        return [];
    }

    public function getName() : string
    {
        return 'EMPTY';
    }
}
