<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\PDO\Connection as PDOConnection;
use Doctrine\Migrations\Tools\TransactionHelper;
use PHPUnit\Framework\TestCase;

final class TransactionHelperTest extends TestCase
{
    /**
     * @param class-string $driverConnectionFqcn
     *
     * @dataProvider getDriverConnectionClassesImplementingInTransactionMethod
     */
    public function testCommitIfInTransactionWithConnectionImplementingInTransactionMethod(string $driverConnectionFqcn, bool $commitExpected): void
    {
        $wrappedConnection = $this->createMock($driverConnectionFqcn);
        $wrappedConnection->expects(self::once())
            ->method('inTransaction')
            ->willReturn($commitExpected);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getWrappedConnection')
            ->willReturn($wrappedConnection);

        $connection->expects($commitExpected ? self::once() : self::never())
            ->method('commit');

        TransactionHelper::commitIfInTransaction($connection);
    }

    public function testCommitIfInTransactionWithConnectionNotImplementingInTransactionMethod(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getWrappedConnection')
            ->willReturn($this->createMock(DriverConnection::class));

        $connection->expects(self::once())
            ->method('commit');

        TransactionHelper::commitIfInTransaction($connection);
    }

    /**
     * @param class-string $driverConnectionFqcn
     *
     * @dataProvider getDriverConnectionClassesImplementingInTransactionMethod
     */
    public function testRollbackIfInTransactionWithConnectionImplementingInTransactionMethod(string $driverConnectionFqcn, bool $commitExpected): void
    {
        $wrappedConnection = $this->createMock($driverConnectionFqcn);
        $wrappedConnection->expects(self::once())
            ->method('inTransaction')
            ->willReturn($commitExpected);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getWrappedConnection')
            ->willReturn($wrappedConnection);

        $connection->expects($commitExpected ? self::once() : self::never())
            ->method('rollback');

        TransactionHelper::rollbackIfInTransaction($connection);
    }

    public function testRollbackIfInTransactionWithConnectionNotImplementingInTransactionMethod(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getWrappedConnection')
            ->willReturn($this->createMock(DriverConnection::class));

        $connection->expects(self::once())
            ->method('rollback');

        TransactionHelper::rollbackIfInTransaction($connection);
    }

    /**
     * @return list<array{class-string<DriverConnection>, bool}>
     */
    public function getDriverConnectionClassesImplementingInTransactionMethod(): array
    {
        return [
            [PDOConnection::class, true],
            [PDOConnection::class, false],
        ];
    }
}
