<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\VersionFactory;
use PHPUnit\Framework\TestCase;

class MigrationRepositoryTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    /** @var MigrationFinder */
    private $migrationFinder;

    /** @var VersionFactory */
    private $versionFactory;

    /** @var MigrationRepository */
    private $migrationRepository;

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

        self::assertEquals($versionData, $this->migrationRepository->getVersionData($version));
    }

    public function testRegisterMigrationWithNonExistentClassCausesError()
    {
        $this->expectException(MigrationClassNotFound::class);

        $this->migrationRepository->registerMigration('123', DoesNotExistAtAll::class);
    }

    protected function setUp() : void
    {
        $this->configuration   = $this->createMock(Configuration::class);
        $this->connection      = $this->createMock(Connection::class);
        $this->migrationFinder = $this->createMock(MigrationFinder::class);
        $this->versionFactory  = $this->createMock(VersionFactory::class);

        $this->migrationRepository = new MigrationRepository(
            $this->configuration,
            $this->connection,
            $this->migrationFinder,
            $this->versionFactory
        );
    }
}
