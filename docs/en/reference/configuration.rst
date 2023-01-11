Configuration
=============

So you are ready to start configuring your migrations? We just need to provide
a few bits of information for the console application in order to get started.

Migrations Configuration
------------------------

First we need to configure information about your migrations. In ``/data/doctrine/migrations-docs-example``
go ahead and create a folder to store your migrations in:

.. code-block:: sh

    $ mkdir -p lib/MyProject/Migrations

Now, in the root of your project place a file named ``migrations.php``, ``migrations.yml``,
``migrations.xml`` or ``migrations.json`` and place the following contents:

.. configuration-block::

    .. code-block:: php

        <?php

        return [
            'table_storage' => [
                'table_name' => 'doctrine_migration_versions',
                'version_column_name' => 'version',
                'version_column_length' => 191,
                'executed_at_column_name' => 'executed_at',
                'execution_time_column_name' => 'execution_time',
            ],

            'migrations_paths' => [
                'MyProject\Migrations' => '/data/doctrine/migrations/lib/MyProject/Migrations',
                'MyProject\Component\Migrations' => './Component/MyProject/Migrations',
            ],

            'all_or_nothing' => true,
            'transactional' => true,
            'check_database_platform' => true,
            'organize_migrations' => 'none',
            'connection' => null,
            'em' => null,
        ];

    .. code-block:: yaml

        table_storage:
           table_name: doctrine_migration_versions
           version_column_name: version
           version_column_length: 191
           executed_at_column_name: executed_at
           execution_time_column_name: execution_time

        migrations_paths:
           'MyProject\Migrations': /data/doctrine/migrations/lib/MyProject/Migrations
           'MyProject\Component\Migrations': ./Component/MyProject/Migrations

        all_or_nothing: true
        transactional: true
        check_database_platform: true
        organize_migrations: none

        connection: null
        em: null

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-migrations xmlns="http://doctrine-project.org/schemas/migrations/configuration/3.0"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/migrations/configuration/3.0
                            http://doctrine-project.org/schemas/migrations/configuration-3.0.xsd">

            <connection>default</connection>
            <em>default</em>

            <storage>
                <table-storage
                        table-name="doctrine_migration_versions"
                        version-column-name="version"
                        version-column-length="191"
                        executed-at-column-name="executed_at"
                        execution-time-column-name="execution_time"
                />
            </storage>
            <migrations-paths>
                <path namespace="MyProject\Migrations">/data/doctrine/migrations/lib/MyProject/Migrations</path>
                <path namespace="MyProject\Component\Migrations">./Component/MyProject/Migrations</path>
            </migrations-paths>

            <all-or-nothing>true</all-or-nothing>
            <transactional>true</transactional>

            <check-database-platform>true</check-database-platform>
            <organize_migrations>none</organize_migrations>
        </doctrine-migrations>

    .. code-block:: json

        {
            "table_storage": {
               "table_name": "doctrine_migration_versions",
               "version_column_name": "version",
               "version_column_length": 191,
               "executed_at_column_name": "executed_at",
               "execution_time_column_name": "execution_time"
            },

            "migrations_paths": {
               "MyProject\\Migrations": "/data/doctrine/migrations/lib/MyProject/Migrations",
               "MyProject\\Component\\Migrations": "./Component/MyProject/Migrations"
            },

            "all_or_nothing": true,
            "transactional": true,
            "check_database_platform": true,
            "organize_migrations": "none",

            "connection": null,
            "em": null
        }

Please note that if you want to use the YAML configuration option, you will need to install the ``symfony/yaml`` package with composer:

.. code-block:: sh

    composer require symfony/yaml

Here are details about what each configuration option does:

+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| Name                       | Required   | Default                      | Description                                                                      |
+============================+============+==============================+==================================================================================+
| migrations_paths<string, string>       | yes        | null             | The PHP namespace your migration classes are located under and the path to a directory where to look for migration classes.                     |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| table_storage              | no         |                              | Used by doctrine migrations to track the currently executed migrations           |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| all_or_nothing             | no         | false                        | Whether or not to wrap multiple migrations in a single transaction.              |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| transactional              | no         | true                         | Whether or not to wrap migrations in a single transaction.                       |
|                            |            |                              |                                                                                  |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| migrations                 | no         | []                           | Manually specify the array of migration versions instead of finding migrations.  |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| check_database_platform    | no         | true                         | Whether to add a database platform check at the beginning of the generated code. |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| organize_migrations        | no         | ``none``                     | Whether to organize migration classes under year (``year``) or year and month (``year_and_month``) subdirectories. |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| connection                 | no         | null                         | The named connection to use (available only when ConnectionRegistryConnection is used). |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| em                         | no         | null                         | The named entity manager to use (available only when ManagerRegistryEntityManager is used). |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+


Here the possible options for ``table_storage``:

+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| Name                       | Required   | Default                      | Description                                                                      |
+============================+============+==============================+==================================================================================+
| table_name                 | no         | doctrine_migration_versions  | The name of the table to track executed migrations in.                           |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| version_column_name        | no         | version                      | The name of the column which stores the version name.                            |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| version_column_length      | no         | 191                         | The length of the column which stores the version name.                          |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| executed_at_column_name    | no         | executed_at                  | The name of the column which stores the date that a migration was executed.      |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+
| execution_time_column_name | no         | execution_time               | The name of the column which stores how long a migration took (milliseconds).    |
+----------------------------+------------+------------------------------+----------------------------------------------------------------------------------+

Manually Providing Migrations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you don't want to rely on Doctrine finding your migrations, you can explicitly specify the array of migration
classes using the ``migrations`` configuration setting:

.. configuration-block::

    .. code-block:: php

        <?php

        return [
            // ..

            'migrations' => [
                'MyProject\Migrations\NewMigration',
            ],
        ];

    .. code-block:: yaml

        // ...

        migrations:
            - "MyProject\Migrations\NewMigration"

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-migrations xmlns="http://doctrine-project.org/schemas/migrations/configuration"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/migrations/configuration
                            http://doctrine-project.org/schemas/migrations/configuration.xsd">

            // ...

            <migrations>
                <migration class="MyProject\Migrations\NewMigration" />
            </migrations>
        </doctrine-migrations>

    .. code-block:: json

        {
            // ...

            "migrations": [
                "DoctrineMigrations\NewMigration"
            ]
        }

All or Nothing Transaction
--------------------------

.. note::

    This only works if your database supports transactions for DDL statements.

When using the ``all_or_nothing`` option, multiple migrations ran at the same time will be wrapped in a single
transaction. If one migration fails, all migrations will be rolled back

Using or not using transactions
-------------------------------

By default, migrations are transactional, meaning code in a migration
is wrapped in a transaction.
Setting ``transactional`` to ``false`` will disable that.

From the Command Line
~~~~~~~~~~~~~~~~~~~~~

You can also set this option from the command line with the ``migrate`` command and the ``--all-or-nothing`` option:

.. code-block:: sh

    $ ./vendor/bin/doctrine-migrations migrate --all-or-nothing

If you have it enabled at the configuration level and want to change it for an individual migration you can
pass a value of ``0`` or ``1`` to ``--all-or-nothing``.

.. code-block:: sh

    $ ./vendor/bin/doctrine-migrations migrate --all-or-nothing=0

Connection Configuration
------------------------

Now that we've configured our migrations, the next thing we need to configure is how the migrations console
application knows how to get the connection to use for the migrations:

Simple
~~~~~~

The simplest configuration is to put a ``migrations-db.php`` file in the root of your
project and return an array of connection information that can be passed to the DBAL:

.. code-block:: php

    <?php

    return [
        'dbname' => 'migrations_docs_example',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ];

You will need to make sure the ``migrations_docs_example`` database exists. If you are using MySQL you can create it with
the following command:

.. code-block:: sh

    $ mysqladmin create migrations_docs_example


If you have already a DBAL connection available in your application, ``migrations-db.php`` can return it directly:

.. code-block:: php

    <?php
    use Doctrine\DBAL\DriverManager;

    return DriverManager::getConnection([
        'dbname' => 'migrations_docs_example',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ]);


Advanced
~~~~~~~~

If you require a more advanced configuration and you want to get the connection to use
from your existing application setup then you can use this method of configuration.

In the root of your project, place a file named ``cli-config.php`` with the following
contents. It can also be placed in a folder named ``config`` if you prefer to keep it
out of the root of your project.

.. code-block:: php

    <?php

    require 'vendor/autoload.php';

    use Doctrine\DBAL\DriverManager;
    use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
    use Doctrine\Migrations\Configuration\Migration\PhpFile;
    use Doctrine\Migrations\DependencyFactory;

    $config = new PhpFile('migrations.php'); // Or use one of the Doctrine\Migrations\Configuration\Configuration\* loaders

    $conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

    return DependencyFactory::fromConnection($config, new ExistingConnection($conn));


The above setup assumes you are not using the ORM. If you want to use the ORM, first require it in your project
with composer:

.. code-block:: sh

    composer require doctrine/orm

Now update your ``cli-config.php`` in the root of your project to look like the following:

.. code-block:: php

    <?php

    require 'vendor/autoload.php';

    use Doctrine\ORM\EntityManager;
    use Doctrine\ORM\Tools\Setup;
    use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
    use Doctrine\Migrations\DependencyFactory;

    $config = new PhpFile('migrations.php'); // Or use one of the Doctrine\Migrations\Configuration\Configuration\* loaders

    $paths = [__DIR__.'/lib/MyProject/Entities'];
    $isDevMode = true;

    $ORMconfig = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
    $entityManager = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $ORMconfig);

    return DependencyFactory::fromEntityManager($config, new ExistingEntityManager($entityManager));

Make sure to create the directory where your ORM entities will be located:

.. code-block:: sh

    $ mkdir lib/MyProject/Entities

:ref:`Next Chapter: Migration Classes <migration-classes>`
