Custom Configuration
====================

The ``AbstractCommand::setMigrationConfiguration()`` method allows you to set your own configuration.

This allows you to to build doctrine migration integration into your application or framework with
code that would looks like the following:

.. code-block:: php

    use Doctrine\DBAL\Migrations\Configuration\Configuration;

    $configuration = new Configuration();
    $configuration->setMigrationsTableName(...);
    $configuration->setMigrationsDirectory(...);
    $configuration->setMigrationsNamespace(...);
    $configuration->registerMigrationsFromDirectory(...);

    // My command that extends Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand
    $command->setMigrationConfiguration($configuration);
