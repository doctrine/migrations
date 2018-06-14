<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\Factory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class MigrationRepositoryTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    /** @var MigrationFinder */
    private $migrationFinder;

    /** @var Factory */
    private $versionFactory;

    /** @var MigrationRepository */
    private $migrationRepository;

    public function testGetDeltaVersionReturnsNull() : void
    {
        self::assertNull($this->migrationRepository->getDeltaVersion('00'));
        self::assertNull($this->migrationRepository->getDeltaVersion('01'));
    }

    public function testGetVersions() : void
    {
        $version1 = $this->createMock(Version::class);
        $version1->expects($this->once())
            ->method('getVersion')
            ->willReturn('01');

        $version2 = $this->createMock(Version::class);
        $version2->expects($this->once())
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
        $version = $this->createMock(Version::class);

        $this->configuration->expects($this->once())
            ->method('connect');

        $this->configuration->expects($this->once())
            ->method('createMigrationTable');

        $this->configuration->expects($this->exactly(2))
            ->method('getQuotedMigrationsColumnName')
            ->willReturn('version');

        $this->configuration->expects($this->once())
            ->method('getQuotedMigrationsExecutedAtColumnName')
            ->willReturn('executed_at');

        $this->configuration->expects($this->once())
            ->method('getMigrationsTableName')
            ->willReturn('versions');

        $versionData = [
            'version' => '1234',
            'executed_at' => '2018-05-16 11:14:40',
        ];

        $this->connection->expects($this->once())
            ->method('fetchAssoc')
            ->with('SELECT version, executed_at FROM versions WHERE version = ?')
            ->willReturn($versionData);

        self::assertSame($versionData, $this->migrationRepository->getVersionData($version));
    }

    public function testRegisterMigrationWithNonExistentClassCausesError() : void
    {
        $this->expectException(MigrationClassNotFound::class);

        $this->migrationRepository->registerMigration('123', DoesNotExistAtAll::class);
    }

    protected function setUp() : void
    {
        $this->configuration   = $this->createMock(Configuration::class);
        $this->connection      = $this->createMock(Connection::class);
        $this->migrationFinder = $this->createMock(MigrationFinder::class);
        $this->versionFactory  = $this->createMock(Factory::class);

        $this->migrationRepository = new MigrationRepository(
            $this->configuration,
            $this->connection,
            $this->migrationFinder,
            $this->versionFactory
        );
    }
}
