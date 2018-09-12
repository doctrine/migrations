<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Rollup;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RollupTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var Rollup */
    private $rollup;

    public function testRollupNoMigrtionsFoundException() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No migrations found.');

        $this->migrationRepository->expects(self::once())
            ->method('getVersions')
            ->willReturn([]);

        $this->rollup->rollup();
    }

    public function testRollupTooManyMigrationsException() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Too many migrations.');

        $version1 = $this->createMock(Version::class);
        $version2 = $this->createMock(Version::class);

        $versions = [
            '01' => $version1,
            '02' => $version2,
        ];

        $this->migrationRepository->expects(self::once())
            ->method('getVersions')
            ->willReturn($versions);

        $this->rollup->rollup();
    }

    public function testRollup() : void
    {
        $version1 = $this->createMock(Version::class);

        $versions = ['01' => $version1];

        $this->migrationRepository->expects(self::once())
            ->method('getVersions')
            ->willReturn($versions);

        $this->configuration->expects(self::once())
            ->method('getMigrationsTableName')
            ->willReturn('versions');

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with('DELETE FROM versions');

        $version1->expects(self::once())
            ->method('markMigrated');

        $this->assertSame($version1, $this->rollup->rollup());
    }

    protected function setUp() : void
    {
        $this->configuration       = $this->createMock(Configuration::class);
        $this->connection          = $this->createMock(Connection::class);
        $this->migrationRepository = $this->createMock(MigrationRepository::class);

        $this->rollup = new Rollup(
            $this->configuration,
            $this->connection,
            $this->migrationRepository
        );
    }
}
