<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use Doctrine\DBAL\Connection;

use function method_exists;

/**
 * @internal
 */
final class TransactionHelper
{
    public static function commitIfInTransaction(Connection $connection): void
    {
        if (! self::inTransaction($connection)) {
            return;
        }

        $connection->commit();
    }

    public static function rollbackIfInTransaction(Connection $connection): void
    {
        if (! self::inTransaction($connection)) {
            return;
        }

        $connection->rollBack();
    }

    private static function inTransaction(Connection $connection): bool
    {
        $wrappedConnection = $connection->getWrappedConnection();

        /* Attempt to commit or rollback while no transaction is running
           results in an exception since PHP 8 + pdo_mysql combination */
        if (method_exists($wrappedConnection, 'inTransaction')) {
            return $wrappedConnection->inTransaction();
        }

        return true;
    }
}
