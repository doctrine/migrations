Custom Integration
==================

For up to date code take a look at `the doctrine migrations command line integration <https://github.com/doctrine/migrations/blob/master/bin/doctrine-migrations.php>`_.
None the less the main steps required to make a functional integration are presented below.

Installation
~~~~~~~~~~~~

First you need to require Doctrine Migrations as a dependency of your code

.. code-block:: sh

    composer require doctrine/migrations

Then you have to require the composer autoloader to use the classes from the `Doctrine\DBAL\Migrations`
namespace in your project:

.. code-block:: php

    $autoloadFiles = array(
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php'
    );

    $autoloader = false;
    foreach ($autoloadFiles as $autoloadFile) {
        if (file_exists($autoloadFile)) {
            require_once $autoloadFile;
            $autoloader = true;
        }
    }

    if (!$autoloader) {
        die('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
    }

Now the above autoloader is able to load a class like the following:

.. code-block:: bash

    /path/to/migrations/lib/Doctrine/DBAL/Migrations/Migrations/Migration.php

Register Console Commands
~~~~~~~~~~~~~~~~~~~~~~~~~

Now that we have setup the autoloaders we are ready to add the migration console
commands to our `Doctrine Command Line Interface <http://doctrine-orm.readthedocs.org/en/latest/reference/tools.html#adding-own-commands>`_:

.. code-block:: php

    // ...

    $cli->addCommands(array(
        // Migrations Commands
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\DumpSchemaCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\LatestCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\RollupCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
    ));

Register Console helpers
~~~~~~~~~~~~~~~~~~~~~~~~

Additionally you have to make sure the 'db' and 'dialog' Helpers are added to your Symfony
Console HelperSet in a cli-config.php file.

This file can be either in the directory you are calling the console tool from or in as config subfolder.

.. code-block:: php

    $db = \Doctrine\DBAL\DriverManager::getConnection($params);
    // or
    $em = \Doctrine\ORM\EntityManager::create($params);
    $db = $em->getConnection();

    $helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
        'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($db),
        'question' => new \Symfony\Component\Console\Helper\QuestionHelper(),
    ));

    return $helperset;

Note that the db helper is not required as you might want to pass the connection information
from the command line directly.

You will see that you have a few new commands when you execute the following command:

.. code-block:: bash

    $ ./doctrine list migrations
    Doctrine Migrations 2.0.0

    Usage:
      command [options] [arguments]

    Options:
      -h, --help            Display this help message
      -q, --quiet           Do not output any message
      -V, --version         Display this application version
          --ansi            Force ANSI output
          --no-ansi         Disable ANSI output
      -n, --no-interaction  Do not ask any interactive question
      -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

    Available commands:
      help                    Displays help for a command
      list                    Lists commands
     migrations
      migrations:diff         [diff] Generate a migration by comparing your current database to your mapping information.
      migrations:dump-schema  [dump-schema] Dump the schema for your database to a migration.
      migrations:execute      [execute] Execute a single migration version up or down manually.
      migrations:generate     [generate] Generate a blank migration class.
      migrations:latest       [latest] Outputs the latest version number
      migrations:migrate      [migrate] Execute a migration to a specified version or the latest available version.
      migrations:rollup       [rollup] Rollup migrations by deleting all tracked versions and insert the one version that exists.
      migrations:status       [status] View the status of a set of migrations.
      migrations:up-to-date   [up-to-date] Tells you if your schema is up-to-date.
      migrations:version      [version] Manually add and delete migration versions from the version table.
