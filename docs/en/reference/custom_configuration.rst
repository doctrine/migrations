Custom Configuration
====================

The ``AbstractCommand::setMigrationConfiguration()`` method allows you to set your own configuration.

This allows you to to build doctrine migration integration into your application or framework with
code that would looks like the following:

.. code-block:: php

    use Doctrine\DBAL\Migrations\Configuration\Configuration;

    $configuration = new Configuration();
    $configuration->setMigrationsTableName('doctrine_migration_versions');
    $configuration->setMigrationsColumnName('version');
    $configuration->setMigrationsColumnLength(255);
    $configuration->setMigrationsExecutedAtColumnName('executed_at');
    $configuration->setMigrationsDirectory('/path/to/project/src/App/Migrations');
    $configuration->setMigrationsNamespace('App/Migrations');
    $configuration->registerMigrationsFromDirectory('/path/to/project/src/App/Migrations');

    // My command that extends Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand
    $command->setMigrationConfiguration($configuration);
