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
        $wrappedConnection = $connection->getWrappedConnection();

        // Attempt to commit while no transaction is running results in exception since PHP 8 + pdo_mysql combination
        if ($wrappedConnection instanceof PDO && ! $wrappedConnection->inTransaction()) {
            return;
        }

        $connection->commit();
    }
}
