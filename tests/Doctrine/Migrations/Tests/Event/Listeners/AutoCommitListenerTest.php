<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Event\Listeners;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\Migrations\Event\Listeners\AutoCommitListener;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Version\Direction;
use PHPUnit\Framework\MockObject\MockObject;

class AutoCommitListenerTest extends MigrationTestCase
{
    /** @var Connection&MockObject */
    private Connection $conn;

    private AutoCommitListener $listener;

    public function testListenerDoesNothingDuringADryRun(): void
    {
        $this->willNotCommit();

        $this->listener->onMigrationsMigrated($this->createArgs(true));
    }

    public function testListenerDoesNothingWhenConnecitonAutoCommitIsOn(): void
    {
        $this->willNotCommit();
        $this->conn->expects(self::once())
            ->method('isAutoCommit')
            ->willReturn(true);

        $this->listener->onMigrationsMigrated($this->createArgs(false));
    }

    public function testListenerDoesFinalCommitWhenAutoCommitIsOff(): void
    {
        $this->conn->expects(self::once())
            ->method('isAutoCommit')
            ->willReturn(false);
        $this->conn->expects(self::once())
            ->method('commit');

        $this->listener->onMigrationsMigrated($this->createArgs(false));
    }

    protected function setUp(): void
    {
        $this->conn       = $this->createMock(Connection::class);
        $driverConnection = $this->createStub(DriverConnection::class);
        $this->conn->method('getWrappedConnection')->willReturn($driverConnection);

        $this->listener = new AutoCommitListener();
    }

    private function willNotCommit(): void
    {
        $this->conn->expects(self::never())
            ->method('commit');
    }

    private function createArgs(bool $isDryRun): MigrationsEventArgs
    {
        $plan = new MigrationPlanList([], Direction::UP);

        $configsMigration = new MigratorConfiguration();
        $configsMigration->setDryRun($isDryRun);

        return new MigrationsEventArgs($this->conn, $plan, $configsMigration);
    }
}
