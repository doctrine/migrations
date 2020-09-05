Custom Configuration
====================

It is possible to build a custom configuration where you manually build the ``Doctrine\Migrations\Configuration\Configuration``
instance instead of using YAML, XML, etc. In order to do this, you will need to setup a :ref:`Custom Integration <custom-integration>`.

Once you have your custom integration setup, you can modify it to look like the following:

.. code-block:: php

    #!/usr/bin/env php
    <?php

    require_once __DIR__.'/vendor/autoload.php';

    use Doctrine\DBAL\DriverManager;
    use Doctrine\Migrations\Configuration\Configuration;
    use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
    use Doctrine\Migrations\Configuration\Configuration\ExistingConfiguration;
    use Doctrine\Migrations\DependencyFactory;
    use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
    use Doctrine\Migrations\Tools\Console\Command;
    use Symfony\Component\Console\Application;

    $dbParams = [
        'dbname' => 'migrations_docs_example',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ];

    $connection = DriverManager::getConnection($dbParams);

    $configuration = new Configuration($connection);

    $configuration->setName('My Project Migrations');
    $configuration->addMigrationsDirectory('MyProject\Migrations', '/data/doctrine/migrations-docs-example/lib/MyProject/Migrations');
    $configuration->setAllOrNothing(true);
    $configuration->setCheckDatabasePlatform(false);

    $storageConfiguration = new TableMetadataStorageConfiguration();
    $storageConfiguration->setTableName('doctrine_migration_versions');

    $configuration->setMetadataStorageConfiguration($storageConfiguration);

    $dependencyFactory = DependencyFactory::fromConnection(
        new ExistingConfiguration($configuration),
        new ExistingConnection($connection)
    );

    $cli = new Application('Doctrine Migrations');
    $cli->setCatchExceptions(true);

    $cli->addCommands(array(
        new Command\DumpSchemaCommand($dependencyFactory),
        new Command\ExecuteCommand($dependencyFactory),
        new Command\GenerateCommand($dependencyFactory),
        new Command\LatestCommand($dependencyFactory),
        new Command\ListCommand($dependencyFactory),
        new Command\MigrateCommand($dependencyFactory),
        new Command\RollupCommand($dependencyFactory),
        new Command\StatusCommand($dependencyFactory),
        new Command\SyncMetadataCommand($dependencyFactory),
        new Command\VersionCommand($dependencyFactory),
    ));

    $cli->run();

:ref:`Next Chapter: Migrations Events <events>`


It is possible to use multiple entity managers or connections, this is a way to configure your application:


.. code-block:: php

    #!/usr/bin/env php
    <?php

    require_once __DIR__.'/vendor/autoload.php';

    use Doctrine\DBAL\DriverManager;
    use Doctrine\Migrations\Configuration\Configuration;
    use Doctrine\Migrations\Configuration\Configuration\ExistingConfiguration;
    use Doctrine\Migrations\Configuration\Connection\ConnectionRegistryConnection;
    use Doctrine\Migrations\DependencyFactory;
    use Doctrine\Migrations\Tools\Console\Command;
    use Symfony\Component\Console\Application;

    $connection1 = DriverManager::getConnection([...]);
    $connection2 = DriverManager::getConnection([...]);

    $connectionRegistry = new class(
        'some_registry',
        ['foo' => $connection1, 'bar' => $connection2],
        [], // entity managers
        'foo', // default connection
        null, // default entity manager
        'Doctrine\Persistence\Proxy' // proxy class
    ) extends AbstractManagerRegistry {
        // implement abstract methods here
    };

    $configuration = new Configuration($connection);
    $configuration->addMigrationsDirectory('MyProject\Migrations', 'some path');
    $configurationLoader = new ExistingConfiguration($configuration);

    $connectionLoader = ConnectionRegistryConnection::withSimpleDefault($connectionRegistry);

    $dependencyFactory = DependencyFactory::fromConnection(
        $configurationLoader,
        $connectionLoader
    );

    $cli = new Application('Doctrine Migrations');
    $cli->setCatchExceptions(true);

    $cli->addCommands(array(
        new Command\MigrateCommand($dependencyFactory),
        // more commands here
    ));

    $cli->run();

With this configuration you can use the ``--conn`` parameter to specify a connection that will be used for running
migrations. If the parameter is not passed, it will fallback to the one passed in the configuration,
and if that is also not provided it will fallback to the default connection name specified when creating
the connection registry.
