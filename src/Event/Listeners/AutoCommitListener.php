<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Event\Listeners;

use Doctrine\Common\EventSubscriber;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Events;
use Doctrine\Migrations\Tools\TransactionHelper;

/**
 * Listens for `onMigrationsMigrated` and, if the connection has autocommit
 * makes sure to do the final commit to ensure changes stick around.
 *
 * @internal
 */
final class AutoCommitListener implements EventSubscriber
{
    public function onMigrationsMigrated(MigrationsEventArgs $args): void
    {
        $conn = $args->getConnection();
        $conf = $args->getMigratorConfiguration();

        if ($conf->isDryRun() || $conn->isAutoCommit()) {
            return;
        }

        TransactionHelper::commitIfInTransaction($conn);
    }

    /** {@inheritDoc} */
    public function getSubscribedEvents()
    {
        return [Events::onMigrationsMigrated];
    }
}
