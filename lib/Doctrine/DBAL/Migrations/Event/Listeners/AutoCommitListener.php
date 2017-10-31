<?php

namespace Doctrine\DBAL\Migrations\Event\Listeners;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Migrations\Events;
use Doctrine\DBAL\Migrations\Event\MigrationsEventArgs;

/**
 * Listens for `onMigrationsMigrated` and, if the conneciton is has autocommit
 * makes sure to do the final commit to ensure changes stick around.
 *
 * @since 1.6
 */
final class AutoCommitListener implements EventSubscriber
{
    public function onMigrationsMigrated(MigrationsEventArgs $args)
    {
        $conn = $args->getConnection();
        if ( ! $args->isDryRun() && ! $conn->isAutoCommit()) {
            $conn->commit();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::onMigrationsMigrated];
    }
}
