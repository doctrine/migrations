<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\Migrations\Tools\TransactionHelper;
use PDO;
use PHPUnit\Framework\TestCase;

use function method_exists;

final class TransactionHelperTest extends TestCase
{
    use VerifyDeprecations;

    public function testItTriggersADeprecationWhenUseful(): void
    {
        $connection        = $this->createStub(Connection::class);
        $wrappedConnection = $this->createStub(PDO::class);

        if (method_exists(Connection::class, 'getNativeConnection')) {
            $connection->method('getNativeConnection')->willReturn($wrappedConnection);
        } else {
            $connection->method('getWrappedConnection')->willReturn($wrappedConnection);
        }

        $wrappedConnection->method('inTransaction')->willReturn(false);

        $this->expectDeprecationWithIdentifier(
            'https://github.com/doctrine/migrations/issues/1169'
        );
        TransactionHelper::commitIfInTransaction($connection);

        $this->expectDeprecationWithIdentifier(
            'https://github.com/doctrine/migrations/issues/1169'
        );
        TransactionHelper::rollbackIfInTransaction($connection);
    }

    public function testItDoesNotTriggerADeprecationWhenUseless(): void
    {
        $connection        = $this->createStub(Connection::class);
        $wrappedConnection = $this->createStub(PDO::class);

        if (method_exists(Connection::class, 'getNativeConnection')) {
            $connection->method('getNativeConnection')->willReturn($wrappedConnection);
        } else {
            $connection->method('getWrappedConnection')->willReturn($wrappedConnection);
        }

        $wrappedConnection->method('inTransaction')->willReturn(true);

        $this->expectNoDeprecationWithIdentifier(
            'https://github.com/doctrine/migrations/issues/1169'
        );
        TransactionHelper::commitIfInTransaction($connection);

        $this->expectNoDeprecationWithIdentifier(
            'https://github.com/doctrine/migrations/issues/1169'
        );
        TransactionHelper::rollbackIfInTransaction($connection);
    }
}
