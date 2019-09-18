<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

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
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\Configuration\AutoloadVersions\Version1Test;
use Doctrine\Migrations\Version\Version;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use function array_keys;
use function call_user_func_array;
use function sprintf;
use function str_replace;

class MoreConfigurationTest extends MigrationTestCase
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
        $dependencyFactory = $this->createMock(DependencyFactory::class);

        $outputWriter = $this->getOutputWriterMock();

        $dependencyFactory->expects(self::once())
            ->method('getOutputWriter')
            ->willReturn($outputWriter);

        $configuration = new Configuration($this->getConnectionMock(), null, null, null, $dependencyFactory);

        self::assertSame($outputWriter, $configuration->getOutputWriter());
    }

    public function testOutputWriterCanBeSet() : void
    {
        $outputWriter = $this->getOutputWriterMock();

        $configuration = new Configuration($this->getConnectionMock());
        $configuration->setOutputWriter($outputWriter);

        self::assertSame($outputWriter, $configuration->getOutputWriter());
    }

    public function testGetSetMigrationsColumnName() : void
    {
        $configuration = new Configuration($this->getConnectionMock());

        self::assertSame('version', $configuration->getMigrationsColumnName());

        $configuration->setMigrationsColumnName('foobar');
        self::assertSame('foobar', $configuration->getMigrationsColumnName());
    }

    /**
     * @param mixed[] $args
     * @param mixed   $expectedResult
     *
     * @dataProvider methodsThatNeedsVersionsLoaded
     */
    public function testVersionsTryToGetLoadedIfNotAlreadyLoadedWhenAccessingMethodThatNeedThem(
        string $method,
        array $args,
        $expectedResult
    ) : void {
        $configuration = new Configuration($this->getSqliteConnection());
        $configuration->setMigrationsNamespace(str_replace('\Version1Test', '', Version1Test::class));
        $configuration->setMigrationsDirectory(__DIR__ . '/../Stub/Configuration/AutoloadVersions');

        $result = $configuration->$method(...$args);

        if ($method === 'getMigrationsToExecute') {
            $result = array_keys($result);
        }

        self::assertSame($expectedResult, $result);
    }

    /**
     * @param mixed[] $args
     * @param mixed   $expectedResult
     *
     * @dataProvider methodsThatNeedsVersionsLoadedWithAlreadyMigratedMigrations
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

        /** @var callable $callable */
        $callable = [$configuration, $method];

        $result = call_user_func_array($callable, $args);

        if ($method === 'getMigrationsToExecute') {
            $result = array_keys($result);
        }

        self::assertSame($expectedResult, $result);
    }

    public function testGenerateVersionNumberFormatsTheDatePassedIn() : void
    {
        $configuration = new Configuration($this->getSqliteConnection());
        $now           = new DateTime('2016-07-05 01:00:00');

        $version = $configuration->generateVersionNumber($now);

        self::assertSame('20160705010000', $version);
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

        $connection->expects(self::once())
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

        $conn->expects(self::any())
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

        self::assertSame('"version"', $config->getQuotedMigrationsColumnName());
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

        $dependencyFactory->expects(self::once())
            ->method('getMigrationRepository')
            ->willReturn($migrationRepository);

        $migrationRepository->expects(self::once())
            ->method('getVersionData')
            ->with($version)
            ->willReturn($versionData);

        $configuration = new Configuration($this->getConnectionMock(), null, null, null, $dependencyFactory);

        self::assertSame($versionData, $configuration->getVersionData($version));
    }

    public function testGetSetAllOrNothing() : void
    {
        $configuration = $this->createPartialMock(Configuration::class, []);

        self::assertFalse($configuration->isAllOrNothing());

        $configuration->setAllOrNothing(true);

        self::assertTrue($configuration->isAllOrNothing());
    }

    public function testGetSetCheckDatabasePlatform() : void
    {
        $configuration = $this->createPartialMock(Configuration::class, []);

        self::assertTrue($configuration->isDatabasePlatformChecked());

        $configuration->setCheckDatabasePlatform(false);

        self::assertFalse($configuration->isDatabasePlatformChecked());
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function getConnectionMock()
    {
        return $this->createMock(Connection::class);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|OutputWriter
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
