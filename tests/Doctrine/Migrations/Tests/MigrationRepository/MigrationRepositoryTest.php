<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\MigrationRepository;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\A;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\B;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\B\C;
use Doctrine\Migrations\Version\Factory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MigrationRepositoryTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $configuration;

    /** @var Connection|MockObject */
    private $connection;

    /** @var MigrationFinder|MockObject */
    private $migrationFinder;

    /** @var Factory|MockObject */
    private $versionFactory;

    /** @var MigrationRepository */
    private $migrationRepository;

    public function testGetDeltaVersionReturnsNull() : void
    {
        $this->markTestSkipped();
        self::assertNull($this->migrationRepository->getDeltaVersion('00'));
        self::assertNull($this->migrationRepository->getDeltaVersion('01'));
    }

    public function testGetVersions() : void
    {
        $this->markTestSkipped();
        $version1 = $this->createMock(Version::class);
        $version1->expects(self::once())
            ->method('getVersion')
            ->willReturn('01');

        $version2 = $this->createMock(Version::class);
        $version2->expects(self::once())
            ->method('getVersion')
            ->willReturn('02');

        $versions = [
            '01' => $version1,
            '02' => $version2,
        ];

        $this->migrationRepository->addVersions($versions);

        self::assertSame($versions, $this->migrationRepository->getVersions());

        $this->migrationRepository->clearVersions();

        self::assertEmpty($this->migrationRepository->getVersions());
    }

    public function testGetVersionData() : void
    {
        $this->markTestSkipped();
        $version = $this->createMock(Version::class);

        $this->configuration->expects(self::once())
            ->method('connect');

        $this->configuration->expects(self::once())
            ->method('createMigrationTable');

        $this->configuration->expects(self::exactly(2))
            ->method('getQuotedMigrationsColumnName')
            ->willReturn('version');

        $this->configuration->expects(self::once())
            ->method('getQuotedMigrationsExecutedAtColumnName')
            ->willReturn('executed_at');

        $this->configuration->expects(self::once())
            ->method('getMigrationsTableName')
            ->willReturn('versions');

        $versionData = [
            'version' => '1234',
            'executed_at' => '2018-05-16 11:14:40',
        ];

        $this->connection->expects(self::once())
            ->method('fetchAssoc')
            ->with('SELECT version, executed_at FROM versions WHERE version = ?')
            ->willReturn($versionData);

        self::assertSame($versionData, $this->migrationRepository->getVersionData($version));
    }

    public function testRegisterMigrationWithNonExistentClassCausesError() : void
    {
        $this->markTestSkipped();
        $this->expectException(MigrationClassNotFound::class);

//        $this->migrationRepository->registerMigration('123', DoesNotExistAtAll::class);
    }

    public function testRemoveMigrationVersionFromDatabase() : void
    {
        $this->markTestSkipped();
        $migrationsTableName  = 'migration_versions';
        $migrationsColumnName = 'version';
        $version              = '123';

        $this->configuration->expects(self::once())
            ->method('getMigrationsTableName')
            ->willReturn($migrationsTableName);

        $this->configuration->expects(self::once())
            ->method('getMigrationsColumnName')
            ->willReturn($migrationsColumnName);

        $this->connection->expects(self::once())
            ->method('delete')
            ->with($migrationsTableName, [$migrationsColumnName => $version])
            ->willReturn(1);

        $this->migrationRepository->removeMigrationVersionFromDatabase($version);
    }

    public function testCheckNonExistentMigration()
    {
        self::assertFalse($this->migrationRepository->hasMigration('non_existent'));
    }

    public function testGetNonExistentMigration()
    {
        $this->expectException(MigrationClassNotFound::class);

        $this->migrationRepository->getMigration(new Version('non_existent'));
    }

    public function testGetOneMigration()
    {
        $migration = $this->migrationRepository->getMigration(new Version(A::class));

        self::assertSame(A::class, (string)$migration->getVersion());
        self::assertInstanceOf(A::class, $migration->getMigration());
    }


    public function testFindMigrations()
    {
        $migrations = $this->migrationRepository->getMigrations();

        self::assertCount(3, $migrations);

        self::assertSame(A::class, (string)$migrations->getItems()[0]->getVersion());
        self::assertSame(B::class, (string)$migrations->getItems()[1]->getVersion());
        self::assertSame(C::class, (string)$migrations->getItems()[2]->getVersion());

        self::assertInstanceOf(A::class, $migrations->getItems()[0]->getMigration());
        self::assertInstanceOf(B::class, $migrations->getItems()[1]->getMigration());
        self::assertInstanceOf(C::class, $migrations->getItems()[2]->getMigration());
    }

    protected function setUp() : void
    {
        $this->configuration   = $this->createMock(Configuration::class);
        $this->connection      = $this->createMock(Connection::class);
        $this->versionFactory  = $this->createMock(Factory::class);
        $this->versionFactory
            ->expects($this->exactly(3))
            ->method('createVersion')
            ->willReturnCallback(function ($class){
                return $this->createMock($class);
            });

        $this->migrationRepository = new MigrationRepository(
            [
                'Doctrine\Migrations\Tests\MigrationRepository\Migrations' => __DIR__. '/Migrations'
            ],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory
        );
    }
}
