<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MigrationStatusInfosHelperTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $configuration;

    /** @var Connection|MockObject */
    private $connection;

    /** @var Driver|MockObject */
    private $driver;

    /** @var MigrationRepository|MockObject */
    private $migrationRepository;

    /** @var MigrationStatusInfosHelper */
    private $migrationStatusInfosHelper;

    public function testGetMigrationsInfos(): void
    {
        $this->driver->expects(self::once())
            ->method('getName')
            ->willReturn('pdo_mysql');

        $this->connection->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');

        $this->connection->expects(self::once())
            ->method('getDatabase')
            ->willReturn('dbname');

        $this->configuration->expects(self::once())
            ->method('getMigrationsTableName')
            ->willReturn('table_name');

        $this->configuration->expects(self::once())
            ->method('getMigrationsColumnName')
            ->willReturn('column_name');

        $this->configuration->expects(self::once())
            ->method('getMigrationsNamespace')
            ->willReturn('Doctrine');

        $this->configuration->expects(self::once())
            ->method('getMigrationsDirectory')
            ->willReturn('/path/to/migrations');

        $this->configuration->expects(self::any())
            ->method('resolveVersionAlias')
            ->willReturn('001');

        $this->configuration->expects(self::any())
            ->method('getDateTime')
            ->willReturn('2017-09-01 01:01:01');

        $expected = [
            'Name'                            => 'Doctrine Database Migrations',
            'Database Driver'                 => 'pdo_mysql',
            'Database Host'                   => 'localhost',
            'Database Name'                   => 'dbname',
            'Configuration Source'            => 'manually configured',
            'Version Table Name'              => 'table_name',
            'Version Column Name'             => 'column_name',
            'Migrations Namespace'            => 'Doctrine',
            'Migrations Directory'            => '/path/to/migrations',
            'Previous Version'                => '2017-09-01 01:01:01 (<comment>001</comment>)',
            'Current Version'                 => '2017-09-01 01:01:01 (<comment>001</comment>)',
            'Next Version'                    => '2017-09-01 01:01:01 (<comment>001</comment>)',
            'Latest Version'                  => '2017-09-01 01:01:01 (<comment>001</comment>)',
            'Executed Migrations'             => 3,
            'Executed Unavailable Migrations' => 1,
            'Available Migrations'            => 3,
            'New Migrations'                  => 1,
        ];

        $infos = $this->migrationStatusInfosHelper->getMigrationsInfos();

        self::assertSame($expected, $infos);
    }

    protected function setUp(): void
    {
        $this->configuration       = $this->createMock(Configuration::class);
        $this->migrationRepository = $this->createMock(MigrationRepository::class);
        $this->connection          = $this->createMock(Connection::class);
        $this->driver              = $this->createMock(Driver::class);

        $this->configuration->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->connection->expects(self::any())
            ->method('getDriver')
            ->willReturn($this->driver);

        $this->migrationRepository->expects(self::once())
            ->method('getMigratedVersions')
            ->willReturn([
                '001',
                '002',
                '003',
            ]);

        $this->migrationRepository->expects(self::once())
            ->method('getAvailableVersions')
            ->willReturn([
                '001',
                '002',
                '004',
            ]);

        $this->migrationRepository->expects(self::once())
            ->method('getNewVersions')
            ->willReturn(['004']);

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['001']);

        $this->migrationStatusInfosHelper = new MigrationStatusInfosHelper(
            $this->configuration,
            $this->migrationRepository
        );
    }
}
