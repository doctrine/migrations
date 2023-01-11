<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use Doctrine\DBAL\Connection;
use LogicException;
use PDO;

use function method_exists;

/**
 * @internal
 */
final class TransactionHelper
{
    public static function commit(Connection $connection): void
    {
        if (! self::inTransaction($connection)) {
            throw new LogicException(
                <<<'EXCEPTION'
Context: trying to commit a transaction
Problem: the transaction is already committed.
Solution: override `AbstractMigration::isTransactional()` so that it returns false.
Automate that by setting `transactional` to false in the configuration.
More details at https://www.doctrine-project.org/projects/doctrine-migrations/en/3.2/explanation/implicit-commits.html
EXCEPTION
            );
        }

        $connection->commit();
    }

    public static function rollback(Connection $connection): void
    {
        if (! self::inTransaction($connection)) {
            throw new LogicException(
                <<<'EXCEPTION'
Context: trying to rollback a transaction
Problem: the transaction is already rolled back.
Solution: override `AbstractMigration::isTransactional()` so that it returns false.
Automate that by setting `transactional` to false in the configuration.
More details at https://www.doctrine-project.org/projects/doctrine-migrations/en/3.2/explanation/implicit-commits.html
EXCEPTION
            );
        }

        $connection->rollBack();
    }

    private static function inTransaction(Connection $connection): bool
    {
        $innermostConnection = self::getInnerConnection($connection);

        /* Attempt to commit or rollback while no transaction is running
           results in an exception since PHP 8 + pdo_mysql combination */
        return ! $innermostConnection instanceof PDO || $innermostConnection->inTransaction();
    }

    /**
     * @return object|resource|null
     */
    private static function getInnerConnection(Connection $connection)
    {
        try {
            return $connection->getNativeConnection();
        } catch (LogicException $e) {
        }

        $innermostConnection = $connection;
        while (method_exists($innermostConnection, 'getWrappedConnection')) {
            $innermostConnection = $innermostConnection->getWrappedConnection();
        }

        return $innermostConnection;
    }
}
