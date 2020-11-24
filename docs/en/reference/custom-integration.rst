Custom Integration
==================

If you don't want to use the ``./vendor/bin/doctrine-migrations`` script that comes with the project,
you can always setup your own custom integration.

In the root of your project, create a file named ``migrations`` and make it executable:

.. code-block:: bash

    $ chmod +x migrations

Now place the following code in the ``migrations`` file:

.. code-block:: php

    #!/usr/bin/env php
    <?php

    require_once __DIR__.'/vendor/autoload.php';

    use Doctrine\DBAL\DriverManager;
    use Doctrine\Migrations\DependencyFactory;
    use Doctrine\Migrations\Configuration\Migration\PhpFile;
    use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
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

    $config = new PhpFile('migrations.php'); // Or use one of the Doctrine\Migrations\Configuration\Configuration\* loaders

    $dependencyFactory = DependencyFactory::fromConnection($config, new ExistingConnection($connection));

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


Now you can execute the migrations console application like this:

.. code-block:: bash

    $ ./migrations
