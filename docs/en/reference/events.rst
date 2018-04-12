Migrations Events
=================

The migrations library emits a series of events during the migration process.

- ``onMigrationsMigrating``: fired immediately before starting to execute
  versions. This does not fire if there are no versions to be executed.
- ``onMigrationsVersionExecuting``: fired before a single version
  executes.
- ``onMigrationsVersionExecuted``: fired after a single version executes.
- ``onMigrationsVersionSkipped``: fired when a single version is skipped.
- ``onMigrationsMigrated``: fired when all versions have been executed.

All of these events are emitted via the connection's event manager. Here's an
example event subscriber that listens for all possible migrations events.

.. code-block:: php

    <?php
    use Doctrine\Common\EventSubscriber;
    use Doctrine\DBAL\Migrations\Event\MigrationsEventArgs;
    use Doctrine\DBAL\Migrations\Event\MigrationsVersionEventArgs;

    class MigrationsListener implements EventSubscriber
    {
        public function getSubscribedEvents()
        {
            return [
                Events::onMigrationsMigrating,
                Events::onMigrationsMigrated,
                Events::onMigrationsVersionExecuting,
                Events::onMigrationsVersionExecuted,
                Events::onMigrationsVersionSkipped,
            ];
        }

        public function onMigrationsMigrating(MigrationsEventArgs $args)
        {
            // ...
        }

        public function onMigrationsMigrated(MigrationsEventArgs $args)
        {
            // ...
        }

        public function onMigrationsVersionExecuting(MigrationsVersionEventArgs $args)
        {
            // ...
        }

        public function onMigrationsVersionExecuted(MigrationsVersionEventArgs $args)
        {
            // ...
        }

        public function onMigrationsVersionSkipped(MigrationsVersionEventArgs $args)
        {
            // ...
        }
    }

To hook a migrations event subscriber into a connection, use its event manager.
This might go in the ``cli-config.php`` file or somewhere in a frameworks
container or dependency injection configuration.

.. code-block:: php

    <?php
    use Doctrine\DBAL\DriverManager;

    $conn = DriverManager::getConnection([
      // ...
    ]);

    $conn->getEventManager()->addEventSubscriber(new MigrationsListener());

    // rest of the cli set up...
