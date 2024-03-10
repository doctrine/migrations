<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\Deprecations\Deprecation;
use LogicException;
use PDO;

use function method_exists;

/** @internal */
final class TransactionHelper
{
    public static function commitIfInTransaction(Connection $connection): void
    {
        if (! self::inTransaction($connection)) {
            Deprecation::trigger(
                'doctrine/migrations',
                'https://github.com/doctrine/migrations/issues/1169',
                <<<'DEPRECATION'
Context: trying to commit a transaction
Problem: the transaction is already committed, relying on silencing is deprecated.
Solution: override `AbstractMigration::isTransactional()` so that it returns false.
Automate that by setting `transactional` to false in the configuration.
More details at https://www.doctrine-project.org/projects/doctrine-migrations/en/stable/explanation/implicit-commits.html
DEPRECATION,
            );

            return;
        }

        $connection->commit();
    }

    public static function rollbackIfInTransaction(Connection $connection): void
    {
        if (! self::inTransaction($connection)) {
            Deprecation::trigger(
                'doctrine/migrations',
                'https://github.com/doctrine/migrations/issues/1169',
                <<<'DEPRECATION'
Context: trying to rollback a transaction
Problem: the transaction is already rolled back, relying on silencing is deprecated.
Solution: override `AbstractMigration::isTransactional()` so that it returns false.
Automate that by setting `transactional` to false in the configuration.
More details at https://www.doctrine-project.org/projects/doctrine-migrations/en/stable/explanation/implicit-commits.html
DEPRECATION,
            );

            return;
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

    /** @return object|resource|null */
    private static function getInnerConnection(Connection $connection)
    {
        try {
            return $connection->getNativeConnection();
        } catch (LogicException) {
        }

        $innermostConnection = $connection;
        while (method_exists($innermostConnection, 'getWrappedConnection')) {
            $innermostConnection = $innermostConnection->getWrappedConnection();
        }

        return $innermostConnection;
    }
}
