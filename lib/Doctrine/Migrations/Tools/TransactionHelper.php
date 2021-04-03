<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use Doctrine\DBAL\Connection;
use PDO;

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
        return ! $wrappedConnection instanceof PDO || $wrappedConnection->inTransaction();
    }
}
