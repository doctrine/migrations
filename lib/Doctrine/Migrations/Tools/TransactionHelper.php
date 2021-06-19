<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\Deprecations\Deprecation;
use PDO;

/**
 * @internal
 */
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
More details at https://www.doctrine-project.org/projects/doctrine-migrations/en/3.2/explanation/implicit-commits.html
DEPRECATION
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
More details at https://www.doctrine-project.org/projects/doctrine-migrations/en/3.2/explanation/implicit-commits.html
DEPRECATION
            );

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
