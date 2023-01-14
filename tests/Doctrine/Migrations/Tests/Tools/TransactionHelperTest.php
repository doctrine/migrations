<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Tools\TransactionHelper;
use LogicException;
use PDO;
use PHPUnit\Framework\TestCase;

final class TransactionHelperTest extends TestCase
{
    public function testItThrowsAnExceptionWhenAttemptingToCommitWhileNotInsideATransaction(): void
    {
        $connection        = $this->createStub(Connection::class);
        $wrappedConnection = $this->createStub(PDO::class);

        $connection->method('getNativeConnection')->willReturn($wrappedConnection);

        $wrappedConnection->method('inTransaction')->willReturn(false);

        $this->expectException(LogicException::class);
        TransactionHelper::commit($connection);
    }

    public function testItThrowsAnExceptionWhenAttemptingToRollbackWhileNotInsideATransaction(): void
    {
        $connection        = $this->createStub(Connection::class);
        $wrappedConnection = $this->createStub(PDO::class);

        $connection->method('getNativeConnection')->willReturn($wrappedConnection);

        $wrappedConnection->method('inTransaction')->willReturn(false);

        $this->expectException(LogicException::class);
        TransactionHelper::rollback($connection);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testItDoesNotThrowAnExceptionWhenUseless(): void
    {
        $connection        = $this->createStub(Connection::class);
        $wrappedConnection = $this->createStub(PDO::class);

        $connection->method('getNativeConnection')->willReturn($wrappedConnection);

        $wrappedConnection->method('inTransaction')->willReturn(true);

        TransactionHelper::commit($connection);
        TransactionHelper::rollback($connection);
    }
}
